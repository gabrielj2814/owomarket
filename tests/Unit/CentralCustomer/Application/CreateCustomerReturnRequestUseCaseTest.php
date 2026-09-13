<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Src\CentralCustomer\Application\Contracts\ClaimableOrderLocator;
use Src\CentralCustomer\Application\DTOs\ClaimableOrderData;
use Src\CentralCustomer\Application\Service\ClaimWindow;
use Src\CentralCustomer\Application\UseCases\CreateCustomerReturnRequestUseCase;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest;
use Src\Monetization\Infrastructure\Eloquent\Models\OrderDeliveryConfirmation;
use Src\Notification\Application\Contracts\NotificationDispatcher;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenantDeleted;

uses(Tests\TestCase::class);

/**
 * Las REGLAS de abrir una reclamacion, sin base de datos de pedidos.
 *
 * Desde la fase 2 del escaparate el pedido entra por `ClaimableOrderLocator`, asi que este
 * test ya no necesita montar `central_orders` ni saber que un pedido central existe: le da al
 * caso de uso un localizador de mentira y comprueba lo unico que el caso de uso decide.
 *
 * Ese es justo el motivo de que el puerto exista. Antes, para probar «sin cedula no se
 * reclama» habia que crear un pedido entero con sus articulos.
 */
final class LocalizadorDePrueba implements ClaimableOrderLocator
{
    public function __construct(private readonly ?ClaimableOrderData $pedido) {}

    public function find(string $orderId, string $productId, string $customerId): ?ClaimableOrderData
    {
        return $this->pedido;
    }
}

/**
 * Un despachador que apunta a quien se aviso, en vez de avisar.
 *
 * Que esto quepa en ocho lineas es justo lo que el puerto compraba: el caso de uso puede
 * probarse sin buzon, sin usuarios y sin tocar la tabla de notificaciones.
 */
final class DespachadorEspia implements NotificationDispatcher
{
    /** @var array<int, string> */
    public array $reclamacionesAvisadas = [];

    public function claimOpened(string $claimId): void
    {
        $this->reclamacionesAvisadas[] = $claimId;
    }

    public function deliveryDeclared(string $tenantOrderId): void {}
}

/** @return array{0: CreateCustomerReturnRequestUseCase, 1: ClaimableOrderData, 2: DespachadorEspia} */
function casoDeUsoCon(string $tenantOrderId, string $origen = 'central'): array
{
    $pedido = new ClaimableOrderData(
        orderId: $origen === 'central' ? (string) Str::uuid() : $tenantOrderId,
        orderSource: $origen,
        orderNumber: 'ORD-2026-RET100',
        customerEmail: 'comprador@example.com',
        tenantId: 'store-returns',
        tenantOrderId: $tenantOrderId,
        productId: 'prod-123',
        productName: 'Camisa Polo Talla M',
        amount: 45.00,
    );

    $espia = new DespachadorEspia;

    return [
        new CreateCustomerReturnRequestUseCase(new LocalizadorDePrueba($pedido), new ClaimWindow, $espia),
        $pedido,
        $espia,
    ];
}

function compradorConCedula(bool $conCedula = true): CentralCustomer
{
    return CentralCustomer::create([
        'id' => (string) Str::uuid(),
        'name' => 'Return User',
        'email' => 'return_'.bin2hex(random_bytes(3)).'@example.com',
        'document_id' => $conCedula ? 'V-'.random_int(10000000, 29999999) : null,
        'password' => 'secret',
    ]);
}

function entregaDeclaradaHace(string $tenantOrderId, int $dias): void
{
    OrderDeliveryConfirmation::create([
        'id' => (string) Str::uuid(),
        'tenant_id' => 'store-returns',
        'order_id' => $tenantOrderId,
        'declared_delivered_at' => now()->subDays($dias),
    ]);
}

/** @return array{order_id: string, product_id: string, reason: string, description: string} */
function datosDeReclamacion(string $orderId): array
{
    return [
        'order_id' => $orderId,
        'product_id' => 'prod-123',
        'reason' => 'Talla incorrecta',
        'description' => 'La talla M queda muy grande, solicito cambio por talla S.',
    ];
}

beforeEach(function () {
    Event::fake([TenantCreated::class, TenantDeleted::class]);

    config([
        'tenancy.bootstrappers' => array_values(array_filter(
            config('tenancy.bootstrappers', []),
            fn ($bootstrapper) => $bootstrapper !== DatabaseTenancyBootstrapper::class
        )),
    ]);

    if (! Schema::hasTable('central_customers')) {
        (require base_path('database/migrations/2026_08_19_000001_create_central_customers_tables.php'))->up();
    }
    if (! Schema::hasTable('order_delivery_confirmations')) {
        (require base_path('database/migrations/2026_09_10_110000_create_order_delivery_confirmations_table.php'))->up();
    }
    if (! Schema::hasTable('customer_return_requests')) {
        (require base_path('database/migrations/2026_08_19_000010_create_customer_return_requests_table.php'))->up();
        // Subsistema 5: las columnas de resolucion, reloj y medicion. Este test monta su
        // esquema a mano --los `Unit` no pasan por `RefreshDatabase`-- asi que cada migracion
        // nueva sobre esta tabla hay que traerla aqui tambien.
        (require base_path('database/migrations/2026_09_11_140000_add_resolution_to_customer_return_requests.php'))->up();
        (require base_path('database/migrations/2026_09_12_120000_add_order_source_to_customer_return_requests.php'))->up();
    }
});

test('un pedido entregado y en plazo se puede reclamar', function () {
    $comprador = compradorConCedula();
    $tenantOrderId = (string) Str::uuid();
    entregaDeclaradaHace($tenantOrderId, 3);

    [$casoDeUso, $pedido] = casoDeUsoCon($tenantOrderId);
    $resultado = $casoDeUso->execute($comprador->id, datosDeReclamacion($pedido->orderId));

    expect($resultado)->toBeInstanceOf(CustomerReturnRequest::class);
    expect($resultado->status)->toBe('requested');
    expect($resultado->product_name)->toBe('Camisa Polo Talla M');
    expect($resultado->order_source)->toBe('central');
    // Medicion: el importe y la fecha de entrega son lo unico que permitira revisar a los 90
    // dias si los 60 dias de retencion del fondo eran los correctos.
    expect((float) $resultado->amount)->toBe(45.00);
    expect($resultado->delivered_at)->not->toBeNull();
});

test('un pedido de escaparate se reclama igual, y queda marcado como tal', function () {
    $comprador = compradorConCedula();
    $tenantOrderId = (string) Str::uuid();
    entregaDeclaradaHace($tenantOrderId, 1);

    [$casoDeUso, $pedido] = casoDeUsoCon($tenantOrderId, 'storefront');
    $resultado = $casoDeUso->execute($comprador->id, datosDeReclamacion($pedido->orderId));

    expect($resultado->order_source)->toBe('storefront');
    // En una venta de escaparate el pedido de tienda ES el pedido: sin esta igualdad, resolver
    // la reclamacion no encontraria la comision que hay que revertir.
    expect($resultado->order_id)->toBe($tenantOrderId);
    expect($resultado->tenant_order_id)->toBe($tenantOrderId);
});

test('sin cedula no se puede reclamar', function () {
    $comprador = compradorConCedula(conCedula: false);
    $tenantOrderId = (string) Str::uuid();
    entregaDeclaradaHace($tenantOrderId, 3);

    [$casoDeUso, $pedido] = casoDeUsoCon($tenantOrderId);

    expect(fn () => $casoDeUso->execute($comprador->id, datosDeReclamacion($pedido->orderId)))
        ->toThrow(Exception::class, 'necesitamos tu cédula');
});

/*
 * Las dos reglas que la fase 2 cerro. El filtro de pedidos `completed` vivia SOLO en la
 * pantalla del portal: contra la API se podia reclamar un pedido recien creado y sin pagar.
 */
test('no se puede reclamar un pedido cuya entrega la tienda no ha declarado', function () {
    $comprador = compradorConCedula();

    // Sin expediente de entrega. Solo lo crea `DeclareOrderDeliveredUseCase`, asi que su
    // ausencia significa exactamente que la tienda no ha marcado nada.
    [$casoDeUso, $pedido] = casoDeUsoCon((string) Str::uuid());

    expect(fn () => $casoDeUso->execute($comprador->id, datosDeReclamacion($pedido->orderId)))
        ->toThrow(Exception::class, 'la tienda no ha marcado la entrega');
});

test('no se puede reclamar pasada la ventana', function () {
    $comprador = compradorConCedula();
    $tenantOrderId = (string) Str::uuid();
    // Un dia mas alla del valor por defecto de `ClaimWindow`.
    entregaDeclaradaHace($tenantOrderId, 61);

    [$casoDeUso, $pedido] = casoDeUsoCon($tenantOrderId);

    expect(fn () => $casoDeUso->execute($comprador->id, datosDeReclamacion($pedido->orderId)))
        ->toThrow(Exception::class, 'El plazo para reclamar');
});

test('no se puede abrir una segunda reclamacion sobre el mismo articulo', function () {
    $comprador = compradorConCedula();
    $tenantOrderId = (string) Str::uuid();
    entregaDeclaradaHace($tenantOrderId, 3);

    [$casoDeUso, $pedido] = casoDeUsoCon($tenantOrderId);
    $casoDeUso->execute($comprador->id, datosDeReclamacion($pedido->orderId));

    expect(fn () => $casoDeUso->execute($comprador->id, datosDeReclamacion($pedido->orderId)))
        ->toThrow(Exception::class, 'Ya existe una solicitud');
});

test('un pedido que el localizador no encuentra da 404 y no filtra por que', function () {
    $comprador = compradorConCedula();

    $casoDeUso = new CreateCustomerReturnRequestUseCase(new LocalizadorDePrueba(null), new ClaimWindow, new DespachadorEspia);

    // Mismo mensaje para «no existe», «no es tuyo» y «ese producto no esta en el pedido»:
    // distinguirlos le contaria a quien prueba identificadores ajenos cual de sus intentos
    // acerto.
    expect(fn () => $casoDeUso->execute($comprador->id, datosDeReclamacion((string) Str::uuid())))
        ->toThrow(Exception::class, 'no fue encontrado o no pertenece a tu cuenta');
});

test('abrir una reclamacion avisa a la tienda', function () {
    /*
     * Es el aviso mas urgente del sistema: sin el, `AutoResolveStaleReturnsUseCase` resuelve a
     * favor del comprador al vencer el plazo y el comerciante pierde la venta sin haber sabido
     * nunca que tenia que contestar.
     */
    $comprador = compradorConCedula();
    $tenantOrderId = (string) Str::uuid();
    entregaDeclaradaHace($tenantOrderId, 3);

    [$casoDeUso, $pedido, $espia] = casoDeUsoCon($tenantOrderId);
    $resultado = $casoDeUso->execute($comprador->id, datosDeReclamacion($pedido->orderId));

    expect($espia->reclamacionesAvisadas)->toBe([$resultado->id]);
});

test('una reclamacion rechazada no avisa a nadie', function () {
    // El aviso va DESPUES de guardar. Si saliera antes de las comprobaciones, una tienda
    // recibiria avisos de reclamaciones que nunca existieron.
    $comprador = compradorConCedula(conCedula: false);
    $tenantOrderId = (string) Str::uuid();
    entregaDeclaradaHace($tenantOrderId, 3);

    [$casoDeUso, $pedido, $espia] = casoDeUsoCon($tenantOrderId);

    expect(fn () => $casoDeUso->execute($comprador->id, datosDeReclamacion($pedido->orderId)))
        ->toThrow(Exception::class);
    expect($espia->reclamacionesAvisadas)->toBe([]);
});
