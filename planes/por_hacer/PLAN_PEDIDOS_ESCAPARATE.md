# Plan — Los pedidos del comprador en el escaparate

> **Estado:** ✅ TERMINADO el 12/09/2026 · Redactado el 12/09/2026
>
> Última vista pendiente de [`PLAN_VISTAS_PENDIENTES.md`](PLAN_VISTAS_PENDIENTES.md).
> Escrito para retomarlo sin contexto previo.

---

## El plan anterior se equivocaba sobre el obstáculo

`PLAN_VISTAS_PENDIENTES.md` decía: *«antes de construirla hay que decidir cómo se autentica ese
comprador… esa decisión condiciona toda la vista»*.

**Esa decisión ya estaba tomada y construida.** El escaparate tiene SSO funcionando:
`/sso/consume` deja en la sesión de la tienda los dos identificadores —`tenant_customer_id` y
`central_customer_id`— y `customers.central_uuid` existe en la base de cada inquilino.

El obstáculo real es otro:

### En el escaparate se compra como invitado, y el pedido no tiene dueño

`CreateStorefrontOrderPOSTController` identifica al comprador por **el correo que escribe en el
formulario** (`Customer::firstOrCreate` por email), **nunca mira la sesión** y **nunca rellena
`central_uuid`** — ni siquiera cuando el comprador sí tiene sesión abierta.

Dos consecuencias, y la segunda no la veía nadie:

1. Una pantalla de «mis pedidos» filtrada por correo sería un agujero: escribes el correo de
   otro y ves sus compras y sus direcciones, y puedes confirmarle una entrega.

2. **El subsistema 3 ya está roto en el escaparate, hoy, sin que nadie lo note.**
   `DeclareOrderDeliveredUseCase::compradorDe()` resuelve al comprador de una venta de
   escaparate leyendo `customers.central_uuid`, y su propio comentario avisa: *«un comprador de
   escaparate sin cuenta central se queda sin enlace, y ese pedido solo podrá liberarse por
   plazo»*. Como esa columna nunca se rellena, **toda venta de escaparate nace con
   `customer_id = null` en su expediente de entrega**: nadie puede confirmar y todas se liberan
   por silencio.

El subsistema se construyó esperando ese enlace. Le faltaba el cable.

---

## El camino elegido

De tres opciones —exigir sesión para comprar, enlazar cuando la haya, o enlaces mágicos por
correo— se eligió **enlazar cuando la haya**:

- Es **el paso más pequeño que es seguro**.
- No toca la conversión: quien quiera comprar como invitado sigue pudiendo.
- Aprovecha el SSO que ya existe en vez de inventar un mecanismo de autenticación nuevo.

Exigir sesión para comprar cerraría el problema entero, pero es una decisión sobre el embudo de
venta, no sobre el código. Los enlaces mágicos además dependen de un sistema de notificaciones
que todavía no existe.

**Precio aceptado:** quedan dos clases de pedido. Los de invitado no se pueden seguir ni
confirmar. **Eso hay que decirlo en el checkout**, no dejar que se descubra cuando importa.

---

## Fase 1 — Ver y confirmar ✅ (12/09/2026)

> **Verificado en el escaparate real**, no solo en tests: un comprador entró por SSO en
> `tecs.owomarket.local`, vio su pedido, pulsó «Confirmar que lo recibí» y el expediente quedó
> con `released_by = customer`. Esa venta se liberó por confirmación del comprador — lo que
> hasta ese momento era imposible en una tienda.
>
> **Un fallo que cazó el test y no el ojo:** un error de red caía en el vacío de «todavía no hay
> pedidos aquí», así que un fallo de la plataforma se leía como «tus compras no existen». Es el
> hallazgo N35 de este repositorio repetido en una pantalla nueva.

### El cable que falta

En `CreateStorefrontOrderPOSTController`: si la sesión de la tienda trae `central_customer_id`,
se guarda en `customers.central_uuid`. Con eso el expediente de entrega nace con dueño y el
subsistema 3 empieza a funcionar en el escaparate.

Es el cambio más pequeño de todo el trabajo y el que más desbloquea.

### Una puerta nueva, no un caso de uso nuevo

Confirmar entrega ya funciona: `ConfirmOrderDeliveryUseCase` compara contra el `customer_id` del
expediente y no le importa de dónde venga el pedido. Lo que falta es una ruta **en el dominio de
la tienda** que resuelva la identidad desde `session('central_customer_id')` en lugar del guard
central `auth('central_customer')`.

**Mismo caso de uso, segunda puerta.** Dos copias de esta lógica acabarían divergiendo, y una de
ellas mueve dinero.

### La pantalla

`/mis-pedidos` en el escaparate, bajo `StorefrontLayout`: los pedidos de ese comprador con su
estado, y el panel de confirmación cuando la tienda ya declaró la entrega.

Sigue el aspecto que el escaparate ya tiene. **No se migra a Flowbite aquí**: el escaparate no
tiene tema todavía y decidirlo es otro trabajo ([`PLAN_MIGRACION_FLOWBITE.md`](PLAN_MIGRACION_FLOWBITE.md)).
Mezclar las dos cosas haría esta pantalla imposible de revisar.

`DeliveryConfirmationPanel` se reutiliza en vez de copiarse: recibe sus dos llamadas por
parámetro, con el servicio central por defecto.

### Y decirlo en el checkout

Comprar con sesión permite seguir el pedido y confirmar la entrega; comprar como invitado, no.
Hoy la diferencia es invisible hasta que importa.

---

## Fase 2 — Reclamar desde el escaparate ✅ (12/09/2026)

> **Verificado en la tienda real**, no solo en tests: un comprador entró por SSO en
> `tecs.owomarket.local`, pulsó «Tengo un problema» sobre su pedido, escribió el motivo y la
> reclamación quedó en la tabla **central** con `order_source = storefront`. El panel del
> comerciante la ve como abierta y el reloj de respuesta arrancó con sus 5 días.

### Este plan también exageraba su propio obstáculo

Decía que generalizarlo era «un cambio en el corazón del subsistema 5». No lo era. Al rastrear
quién lee `order_id` **después** de crear la reclamación:

| Consumidor | Qué usa de verdad |
| :--- | :--- |
| `ResolveReturnRequestUseCase` (revierte comisión, calcula cobertura) | `tenant_order_id` |
| `BuildClaimDossierUseCase` (expediente + PDF) | `tenant_order_id` y `order_number` |
| `ListTenantReturnsGETController` / `ListCustomerReturnRequestsUseCase` | `tenant_id` / `customer_id` |
| `AutoResolveStaleReturnsUseCase` | solo `status` |

**Nadie aguas abajo vuelve a buscar el `CentralOrder`.** Y `order_id` es un `string` suelto en
la migración, sin clave foránea. Todo el acoplamiento vivía dentro del caso de uso que crea la
reclamación. La maquinaria que mueve el dinero funcionó sin tocar una línea.

### Un localizador, dos adaptadores

`ClaimableOrderLocator` devuelve un `ClaimableOrderData` —número de pedido, correo, `tenant_id`,
`tenant_order_id`, el artículo y su importe— y **comprueba la propiedad dentro**. No hay una
versión que devuelva el pedido «sin comprobar»: esa firma permitiría olvidarse, y el precio de
olvidarlo es que alguien reclame el pedido de otro.

Cada adaptador sabe qué significa «es tuyo» en su mundo: en el central lo dice
`central_orders.customer_id`; en una tienda, `customers.central_uuid`. **Nunca el correo.**

El caso de uso se quedó solo con las reglas, y eso las volvió probables sin base de datos de
pedidos: antes, para probar «sin cédula no se reclama» había que crear un pedido entero.

### El enlace contextual va sobre el CASO DE USO, no sobre el localizador

El contenedor de Laravel resuelve lo contextual mirando solo al **padre inmediato** que está
construyendo. `when(Controlador)->needs(Localizador)` no llega nunca, porque para entonces el
padre en la pila ya es el caso de uso. Lo peligroso es que no falla: el escaparate seguiría
funcionando con el adaptador central, que jamás encuentra un pedido de tienda.

### Dos reglas que se cerraron de paso, para los dos caminos

La pantalla del portal solo ofrecía pedidos `completed`, pero **ese filtro vivía solo en el
navegador**: contra la API se podía abrir una reclamación sobre un pedido recién creado y sin
pagar. Al escribir la segunda puerta había que elegir entre replicar el agujero o cerrarlo.

1. **Entregado.** Un `OrderDeliveryConfirmation` solo nace en `DeclareOrderDeliveredUseCase`,
   así que su mera existencia ya significa que la tienda declaró la entrega. No hizo falta
   inventar ningún estado.
2. **Dentro de plazo.** `ClaimWindow`: 60 días desde la entrega, configurables en
   `central_claim_window_days`.

Es un cambio de comportamiento **también para el portal central**, y es deliberado.

### Por qué la ventana tiene su propio ajuste

La tentación era leer `central_guarantee_reserve_days` —la reserva del subsistema 4, que es el
colchón que paga una reclamación— para que no puedan divergir. No se hizo: son dos preguntas de
negocio distintas. Bajar la reserva a 30 días para mejorar el flujo de caja de los comerciantes
le recortaría al comprador la mitad de su plazo sin que nadie lo hubiera decidido.

Empiezan valiendo lo mismo. Si divergen será porque alguien lo quiso.

**Y la que NO sirve como ancla:** `central_payout_hold_days` vale **1 día por defecto y cero es
legítimo** («lo entregado se puede retirar en el acto»). Atar la ventana ahí daría un día para
reclamar, o ninguno.

### La pantalla

Cada artículo de `/mis-pedidos` lleva su botón, con `can_claim` decidido **por el servidor** con
las mismas condiciones que aplicará al recibir la reclamación. Si ya hay una viva, se enseña su
estado **en lugar** del botón: dejarlo puesto haría que el comprador lo pulsara para recibir un
«ya existe una solicitud activa» que no tenía forma de prever.

Una rechazada sí deja volver a reclamar mientras siga el plazo, igual que en el backend. La
lista que bloquea vive en `CustomerReturnRequest::BLOQUEAN_NUEVA` y la usan los dos sitios: si
divergieran, la pantalla ofrecería un botón que el backend rechaza —o escondería uno que sí
funciona, que es la misma clase de mentira—.

Las frases de estado se mudaron a `resources/js/utils/claims.ts`, compartidas con el portal. Las
dos pantallas pintan la MISMA fila, y dos redacciones distintas serían la plataforma diciéndole
dos cosas al mismo comprador sobre el mismo caso.

**Lo que el navegador vio y los tests no:** el botón salía con `color="subtle"`, que en el tema
del escaparate es transparente a propósito. Sobre el fondo oscuro se leía como texto muerto.
Pasó a `light`: sigue siendo secundario —no se invita a reclamar— pero con borde.

---

## Lo que este plan NO arregla

**Los pedidos de invitado ya existentes se quedan huérfanos**: nadie puede demostrar que son
suyos, así que ni se ven ni se confirman ni se reclaman. En desarrollo da igual. **Antes de que
haya compras reales, no.**

**Una reclamación sobre un pedido sin comisión registrada se resuelve cubriendo cero, en
silencio.** `ResolveReturnRequestUseCase` saca la tasa congelada de `platform_commissions`; si
no hay fila, la tasa es 0, el tope en bolívares es 0 y la cobertura sale 0. No lo provoca esta
fase y no se toca aquí: los pedidos reales SÍ registran comisión —`CreateStorefrontOrderPOSTController`
la crea— y solo se ve en pedidos sembrados a mano. Pero conviene que falle ruidosamente el día
que importe.
