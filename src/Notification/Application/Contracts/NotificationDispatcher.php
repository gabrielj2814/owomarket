<?php

declare(strict_types=1);

namespace Src\Notification\Application\Contracts;

/**
 * Avisar de que algo pasó.
 *
 * ## Por qué recibe identificadores y no modelos
 *
 * Quien llama es un caso de uso que acaba de hacer su trabajo —resolver una reclamación,
 * declarar una entrega— y lo único que tiene que decir es **qué pasó**. Quién se entera, por
 * qué canal y con qué palabras no es asunto suyo.
 *
 * Pasar identificadores en vez de modelos cuesta una consulta y compra dos cosas: el caso de
 * uso no necesita haber cargado nada en concreto, y este contrato no depende de Eloquent. Son
 * eventos raros —una reclamación, una entrega— así que la consulta no es un coste que importe.
 *
 * ## Un método por evento, no un `send()` genérico
 *
 * `send(Notification $n)` obligaría a los casos de uso a construir la notificación, y con ella a
 * saber a quién va y qué dice. Volverían a decidir cosas de notificaciones desde el dominio, que
 * es exactamente lo que este puerto existe para impedir.
 *
 * ## Ninguna implementación puede lanzar
 *
 * Es parte del contrato, no un detalle. `ResolveReturnRequestUseCase` corre dentro de una
 * transacción que revierte comisiones: si un aviso fallara hacia arriba, un mailer caído
 * desharía la resolución de una reclamación y con ella el movimiento de dinero. Las
 * implementaciones capturan y registran. **Un aviso perdido es un problema; una reclamación
 * deshecha es otro mucho peor.**
 */
interface NotificationDispatcher
{
    /**
     * Se abrió una reclamación contra una tienda. Avisa a sus dueños.
     *
     * Es el aviso más urgente del sistema: el reloj de `AutoResolveStaleReturnsUseCase` resuelve
     * a favor del comprador cuando vence el plazo, así que un comerciante que no se entera
     * pierde la venta sin haber sabido nunca que tenía que contestar.
     */
    public function claimOpened(string $claimId): void;

    /**
     * La tienda declaró que entregó un pedido. Avisa al comprador.
     *
     * Confirmar es lo que **libera el dinero** del comerciante, así que sin este aviso la venta
     * se libera siempre por vencimiento del plazo — y el comprador gasta su ventana para
     * reclamar sin enterarse de que estaba corriendo.
     */
    public function deliveryDeclared(string $tenantOrderId): void;
}
