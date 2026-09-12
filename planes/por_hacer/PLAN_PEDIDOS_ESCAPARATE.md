# Plan — Los pedidos del comprador en el escaparate

> **Estado:** 🟨 Fase 1 HECHA · Fase 2 pendiente · Redactado el 12/09/2026
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

## Fase 2 — Reclamar desde el escaparate ⬜

`CreateCustomerReturnRequestUseCase` está atado a `CentralOrder`: busca el pedido en la base
central. Un pedido de escaparate vive en la base del inquilino, así que **hoy no se puede
reclamar aunque tenga pantalla**.

Generalizarlo es un cambio en el corazón del subsistema 5 y merece ir solo, después de ver la
fase 1 funcionando.

---

## Lo que este plan NO arregla

**Los pedidos de invitado ya existentes se quedan huérfanos**: nadie puede demostrar que son
suyos, así que ni se ven ni se confirman ni se reclaman. En desarrollo da igual. **Antes de que
haya compras reales, no.**
