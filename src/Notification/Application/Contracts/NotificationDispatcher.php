<?php

declare(strict_types=1);

namespace Src\Notification\Application\Contracts;

/**
 * Avisar de que algo pasó.
 *
 * **Esta interfaz es el catálogo de todo lo que la plataforma anuncia.** Leerla de arriba abajo
 * dice qué se entera cada audiencia, sin abrir nada más.
 *
 * ## Por qué recibe identificadores y no modelos
 *
 * Quien llama es un caso de uso que acaba de hacer su trabajo —resolver una reclamación,
 * aprobar un retiro— y lo único que tiene que decir es **qué pasó**. Quién se entera, por qué
 * canal y con qué palabras no es asunto suyo.
 *
 * Pasar identificadores en vez de modelos cuesta una consulta y compra dos cosas: el caso de uso
 * no necesita haber cargado nada en concreto, y este contrato no depende de Eloquent. Son
 * eventos raros, así que la consulta no es un coste que importe.
 *
 * ## Un método por evento, no un `send()` genérico
 *
 * `send(Notification $n)` obligaría a los casos de uso a construir la notificación, y con ella a
 * saber a quién va y qué dice. Volverían a decidir cosas de notificaciones desde el dominio, que
 * es exactamente lo que este puerto existe para impedir.
 *
 * Los métodos son por EVENTO y no por destinatario: `claimResolved` avisa al comprador siempre y
 * además al comerciante si la resolvió el reloj. Quién recibe qué es una decisión del módulo, no
 * de quien anuncia.
 *
 * ## Ninguna implementación puede lanzar
 *
 * Es parte del contrato, no un detalle. Varios de estos avisos salen desde dentro de
 * transacciones que mueven dinero —revertir comisiones, aprobar un retiro—: si un aviso fallara
 * hacia arriba, un mailer caído desharía la operación. Las implementaciones capturan y
 * registran. **Un aviso perdido es un problema; un retiro deshecho es otro mucho peor.**
 */
interface NotificationDispatcher
{
    // ---------------------------------------------------------------- Garantías

    /**
     * Se abrió una reclamación contra una tienda. Avisa a sus dueños.
     *
     * Es el aviso más urgente del sistema: el reloj de `AutoResolveStaleReturnsUseCase` resuelve
     * a favor del comprador cuando vence el plazo, así que un comerciante que no se entera
     * pierde la venta sin haber sabido nunca que tenía que contestar.
     */
    public function claimOpened(string $claimId): void;

    /**
     * Se resolvió una reclamación. Avisa al comprador **y también al comerciante si la resolvió
     * el reloj**.
     *
     * Que el comerciante se entere de una resolución por silencio es lo que hace que la
     * siguiente sí la conteste: perder una venta sin saber por qué no enseña nada.
     */
    public function claimResolved(string $claimId): void;

    /**
     * La tienda declaró que entregó un pedido. Avisa al comprador.
     *
     * Confirmar es lo que **libera el dinero** del comerciante, así que sin este aviso la venta
     * se libera siempre por vencimiento del plazo — y el comprador gasta su ventana para
     * reclamar sin enterarse de que estaba corriendo.
     */
    public function deliveryDeclared(string $tenantOrderId): void;

    // ---------------------------------------------------------------- Identidad

    /** Una tienda envió su identidad para verificar. Avisa a la plataforma. */
    public function kycSubmitted(string $kycProfileId): void;

    /**
     * Se verificó o rechazó la identidad de una tienda. Avisa a sus dueños.
     *
     * Sin KYC verificado **una tienda no puede cobrar**, así que este aviso es la diferencia
     * entre esperar sabiendo y esperar sin saber.
     */
    public function kycReviewed(string $kycProfileId): void;

    // ---------------------------------------------------------------- Dinero

    /** Una tienda pidió retirar su saldo. Avisa a la plataforma. */
    public function payoutRequested(string $settlementId): void;

    /** Se aprobó o rechazó un retiro. Avisa a los dueños de la tienda. */
    public function payoutResolved(string $settlementId): void;

    // ---------------------------------------------------------------- Suscripción

    /** Una tienda pidió cambiar de plan. Avisa a la plataforma. */
    public function planChangeRequested(string $requestId): void;

    /**
     * Se resolvió un cambio de plan. Avisa a los dueños de la tienda.
     *
     * Es la promesa que la aplicación ya hacía y no podía cumplir: la pantalla responde
     * *«Te avisaremos cuando la revisemos»* desde el 23/08/2026.
     */
    public function planChangeResolved(string $requestId): void;

    // ---------------------------------------------------------------- Lo periódico

    /**
     * A esta reclamación le queda un día. Avisa otra vez a los dueños de la tienda.
     *
     * **Es lo que convierte el reloj en justo.** Perder una venta por silencio después de un
     * aviso al abrirse y otro antes de vencer es una decisión del comerciante; perderla con un
     * solo aviso de hace cinco días es un descuido que la plataforma podía haber evitado.
     *
     * Lo dispara un comando diario, así que **lleva freno**: sin él saldría cada madrugada hasta
     * que alguien conteste.
     */
    public function claimAboutToExpire(string $claimId): void;

    /**
     * El gasto mensual en coberturas pasó del techo. Avisa a la plataforma.
     *
     * La decisión de garantías pide una «revisión obligatoria» al superarlo. `MonthlyCoverageSpend`
     * calcula el número desde el 12/09/2026 y lo pinta donde el administrador entra, pero **nada
     * le obligaba a mirarlo**. Esto es el empujón que faltaba.
     *
     * No corta ningún pago, igual que el techo: solo pide que alguien mire la causa.
     *
     * Lleva freno **por mes**: una vez pasado el techo, el mes sigue pasado mañana y pasado
     * mañana. Un aviso diario de lo mismo deja de leerse justo cuando importa.
     */
    public function coverageCeilingExceeded(): void;
}
