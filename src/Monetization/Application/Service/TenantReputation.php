<?php

declare(strict_types=1);

namespace Src\Monetization\Application\Service;

use Illuminate\Support\Facades\DB;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Throwable;

/**
 * El nivel de reputación de una tienda (subsistema 5, fase C).
 *
 * ## Por qué NO se guarda en una columna
 *
 * Era lo primero que pedía el cuerpo, y es peor: un nivel almacenado necesita disparadores de
 * recálculo, y **un nivel desfasado es un porcentaje de reserva equivocado** — o sea, dinero
 * mal retenido a alguien que no se lo merece, en los dos sentidos.
 *
 * Derivarlo son dos cuentas, y se usa justo donde eso es barato: al liberar una venta, una vez
 * por pedido. Sin columna no hay estado que pueda mentir.
 *
 * `ponytail:` derivado con dos COUNT por consulta. Si algún día una pantalla lista doscientas
 * tiendas con su nivel, eso es un N+1 y ahí sí habrá que cachear o materializar.
 *
 * ## La señal es una sola, y a propósito
 *
 * **Reclamaciones resueltas por silencio** (`resolved_by = 'timeout'`): la tienda no respondió
 * y la plataforma tuvo que decidir por ella. Habrá presión para meter entregas tarde, reseñas
 * y cancelaciones en el mismo índice; un índice compuesto es imposible de explicarle a un
 * comerciante enfadado, y todo lo que no se puede explicar se acaba no aplicando.
 */
final class TenantReputation
{
    public const ALTO = 'alto';

    public const MEDIO = 'medio';

    public const BAJO = 'bajo';

    /** Ventana en la que un silencio sigue pesando. */
    private const DIAS_DE_MEMORIA = 90;

    /** Entregas confirmadas que hay que acumular para llegar a `alto`. */
    private const ENTREGAS_PARA_ALTO = 10;

    /**
     * Porcentaje de la parte del comerciante que queda retenido, por nivel.
     *
     * `medio` conserva el 10% que ya regía para todos, así que ninguna tienda nota un cambio
     * salvo que se lo haya ganado. Importa por la asimetría de siempre: bajar una retención es
     * un regalo, subirla es una discusión con cada tienda.
     */
    private const RESERVA = [
        self::ALTO => 5.0,
        self::MEDIO => 10.0,
        self::BAJO => 20.0,
    ];

    public function __construct(
        private readonly TenantAvailableBalance $balance
    ) {}

    public function level(string $tenantId): string
    {
        if ($this->silenciosRecientes($tenantId) > 0) {
            return self::BAJO;
        }

        // La deuda entra AQUI y no como un tope aparte: una tienda que le debe dinero a la
        // plataforma no llega a `alto` por muy bien que se porte, pero tampoco se la hunde a
        // `bajo` si no ha ignorado a nadie -- se paga vendiendo, y para vender necesita cobrar.
        if ($this->balance->debt($tenantId) > 0.0) {
            return self::MEDIO;
        }

        return $this->entregasConfirmadas($tenantId) >= self::ENTREGAS_PARA_ALTO
            ? self::ALTO
            : self::MEDIO;
    }

    public function reservePercent(string $tenantId): float
    {
        return self::RESERVA[$this->level($tenantId)];
    }

    /**
     * Qué le falta a la tienda para subir, para poder enseñárselo.
     *
     * La decisión insiste en que el progreso sea visible: un nivel que baja sin decir por qué
     * ni cómo se recupera no corrige a nadie — empuja a abrir otra tienda con otro nombre.
     *
     * @return array{level: string, deliveries: int, deliveries_for_next: int, unanswered_claims: int, has_debt: bool}
     */
    public function progress(string $tenantId): array
    {
        $nivel = $this->level($tenantId);

        return [
            'level' => $nivel,
            'deliveries' => $this->entregasConfirmadas($tenantId),
            'deliveries_for_next' => self::ENTREGAS_PARA_ALTO,
            'unanswered_claims' => $this->silenciosRecientes($tenantId),
            'has_debt' => $this->balance->debt($tenantId) > 0.0,
        ];
    }

    /**
     * Reclamaciones que la tienda dejó sin responder y resolvió el reloj.
     *
     * Con memoria: pasados 90 días deja de contar. Sin caducidad, una tienda que fallara una
     * vez quedaría en `bajo` para siempre, y una sanción de la que no se puede salir no
     * corrige comportamiento: solo expulsa.
     */
    private function silenciosRecientes(string $tenantId): int
    {
        try {
            return (int) DB::connection($this->conexion())
                ->table('customer_return_requests')
                ->where('tenant_id', $tenantId)
                ->where('resolved_by', 'timeout')
                ->where('resolved_at', '>=', now()->subDays(self::DIAS_DE_MEMORIA))
                ->count();
        } catch (Throwable) {
            // Sin poder leer la señal, la tienda no pierde nivel por un fallo de la
            // plataforma: se la trata como sin silencios y decide el resto de la regla.
            return 0;
        }
    }

    /**
     * Ventas efectivamente entregadas: el historial limpio que hace subir.
     *
     * Se cuentan las liberadas del subsistema 3 --confirmadas por el comprador o vencidas por
     * plazo-- y no los pedidos sin más: una venta que nunca llegó a entregarse no dice nada
     * sobre cómo se porta la tienda.
     */
    private function entregasConfirmadas(string $tenantId): int
    {
        try {
            return OrderDeliveryConfirmation::where('tenant_id', $tenantId)
                ->whereNotNull('released_at')
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function conexion(): string
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }
}
