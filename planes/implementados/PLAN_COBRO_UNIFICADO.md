# Plan — La plataforma cobra todas las ventas

> **Estado:** ✅ IMPLEMENTADO — 31/08/2026 · **Las tres fases cerradas**
>
> Decisión de negocio del 30/08/2026: **el dinero de todas las compras cae en la cuenta del
> marketplace**, también el de las que se hacen en el escaparate propio de cada tienda.
>
> Esto sustituye al modelo de dos canales que quedó escrito en
> `planes/implementados/PLAN_WALLET_Y_RETIROS.md`. Aquel documento no se borra: describe lo
> que se construyó y por qué, y este explica qué cambia.

---

## Lo que hace el código hoy

Hay dos proveedores de datos de cobro y muestran cuentas distintas:

| Checkout | Cuenta que ve el comprador | De dónde sale |
| :--- | :--- | :--- |
| Marketplace central | La de **la plataforma** | `central_settings` |
| Escaparate de la tienda | La de **esa tienda** | `tenant_settings` |

`StorefrontPaymentMethodsProvider` lee el banco, la cédula y el teléfono **del comerciante**.
Hoy, cuando alguien compra en `tienda.owomarket.com`, el Pago Móvil va a la cuenta del
comerciante.

## Lo que se simplifica

Un solo modelo en vez de dos:

| | Antes | Ahora |
| :--- | :--- | :--- |
| Quién cobra | La plataforma en el central, la tienda en su escaparate | **La plataforma, siempre** |
| Comisión | Retenida en el central, adeudada en el escaparate | **Retenida, siempre** |
| Liquidación | `payout` y `collection` | **Sólo `payout`** |
| Palanca de cobro | `suspended` para la mora | **No hace falta ninguna** |

Y dos pendientes se evaporan:

- **Hallazgo B / punto D** — «la comisión depende de un botón que al comerciante no le
  conviene pulsar». Deja de existir el botón: si cobra la plataforma, confirma la plataforma.
- **La regla de mora (punto A)** — sin deuda no hay morosos. El módulo de morosos y la
  suspensión automática se quedan sin objeto.

`suspended` sobrevive, pero para lo que sirve de verdad: fraude, incumplimiento, moderación.
Ya no es la palanca de cobro.

---

## Fases

### Fase 1 — El escaparate cobra a la cuenta de la plataforma · ✅ HECHA (30/08/2026)

Era lo único que, cada día que siguiera como estaba, **ponía dinero en la cuenta equivocada**.

`StorefrontPaymentMethodsProvider::paymentSettings()` era el único sitio que decidía de qué
tienda salen los datos, así que el cambio quedó contenido ahí: los **datos de la cuenta** vienen
de `central_settings`, y los **interruptores por tienda** —qué métodos ofrece cada escaparate—
se quedan como están. Una tienda puede seguir decidiendo que no acepta Binance; lo que no
decide es a qué cuenta entra el dinero.

#### No bastaba con mapear el Pago Móvil

Las claves de cuenta se **borran** del mapa del inquilino, no sólo se sobrescriben las que
tienen equivalente central. Si sólo se hubiera mapeado el Pago Móvil, una tienda con sus
propias `bank_transfer_instructions` habría seguido enseñándole su banco al comprador, y ahí el
dinero acaba en la cuenta equivocada **sin que nada lo avise**. Lo mismo con el `binance_qr_url`.

Eso obligó a añadir `central_bank_transfer_instructions` a los ajustes de la plataforma: sin
ella el método de transferencia habría desaparecido del escaparate en silencio.

El QR de Binance se borra y **no se sustituye**: lo generaba un tercero al que se le filtraba el
identificador de cobro, y ya estaba desaconsejado en el código.

#### El test que importa

*«Los datos bancarios del comerciante nunca llegan al comprador»*: la tienda tiene configurados
sus propios datos —los que usaba hasta hoy— y se comprueba que el comprador **no ve ni uno**,
mientras sí ve los de la plataforma. Los demás tests del fichero se reescribieron para hablar de
ajustes centrales, porque su premisa cambió.

### Fase 2 — Quitar el filtro de canal del saldo · ✅ HECHA (30/08/2026)

El filtro `whereNotNull('central_order_id')` se puso esta misma mañana, con este razonamiento:
*«en el escaparate el comerciante ya cobró directo en su banco, la plataforma no le debe nada»*.

**Era correcto mientras cada canal cobraba por su lado.** Desde que la plataforma cobra todas
las ventas se invierte: si recibe ese dinero, se lo debe, y el filtro le escondía al comerciante
saldo que es suyo.

Cayó en dos sitios —`TenantAvailableBalance::ventasDe()` y la consulta del resumen de wallet— y
el razonamiento viejo se conserva escrito al lado del nuevo, porque explica por qué estuvo bien
y qué cambió.

**Los demás filtros siguen igual**: estado cobrable, tasa capturada, y entrega con su plazo de
garantía cumplido.

#### Dos tests cambiaron de significado

- *«Una venta del escaparate no entra en la wallet»* pasa a decir lo contrario, con el motivo
  del cambio escrito dentro: no es que estuviera mal, es que su premisa dejó de ser cierta.
- El test del retiro contra saldo inflado tenía cuatro casos y ahora tiene tres. El del
  escaparate dejó de valer como caso: hoy **sí** es saldo del comerciante, y dejarlo habría
  hecho que el test pasara por el motivo equivocado.

### Fase 3 — Confirmar el cobro de una venta del escaparate

Aquí está el trabajo de verdad, y es un hueco nuevo que abre este modelo.

Hoy el pago de un pedido de tienda lo confirma **el comerciante**, desde su backoffice. Bajo
este modelo el dinero entra en la cuenta de la plataforma, así que **sólo la plataforma puede
confirmar que llegó**: el comerciante no tiene acceso a ese extracto bancario.

Y el administrador **hoy no puede ver esos pedidos**: el monitor global lista pedidos
centrales, y los del escaparate viven en la base de cada tienda.

#### Las dos decisiones, tomadas el 31/08/2026

**1. La lista sale de `platform_commissions`.** Ni tabla nueva ni recorrer las bases de los
inquilinos: **la proyección ya existía**. Esa tabla es central, se escribe en cada venta de los
dos canales, y una comisión en `awaiting_payment` **es** un cobro pendiente. Sólo le faltaba la
referencia que puso el comprador, que ahora viaja en su `metadata` desde el checkout.

Recorrer inquilinos no escala más allá de unas pocas tiendas, y una tabla nueva es una copia
más que mantener — este proyecto ya tiene dos cicatrices de copias que divergieron.

**2. El comerciante reporta, la plataforma confirma.** Puede adjuntar la referencia que el
comprador le pasó por otro canal, y queda como **pista**. Confirmar el cobro sigue siendo de
quien ve el extracto.

#### ⚠️ El agujero que abrieron las fases 1 y 2

`POST /api-tenant/order/{id}/payment-status` sigue vivo, y con él **el comerciante puede marcar
sus propios pedidos como pagados**, lo que activa la comisión y vuelve el importe retirable.

Ayer estaba bien: él tenía el dinero. Hoy el dinero está en la cuenta de la plataforma, así que
eso es **autocertificarse un cobro para desbloquear un retiro contra la cuenta ajena**.

**Por eso esta fase se hizo en este orden**: primero el camino del administrador, después
cerrar el del comerciante. Al revés, nadie habría podido confirmar nada y las comisiones se
habrían quedado atascadas en `awaiting_payment`.

#### Fase 3a — El camino del administrador · ✅ HECHA (31/08/2026)

- La referencia del comprador viaja del checkout a la comisión.
- `GET /admin/api/storefront-payments` — los cobros pendientes de todas las tiendas, con el
  importe **en bolívares**, que es lo que hay que buscar en el extracto: el comprador pagó
  bolívares, no dólares.
- `POST /admin/api/storefront-payments/{id}/confirm` — marca el pedido de la tienda como
  pagado por la entidad, pone su fila de `payments` en `completed` y activa la comisión.

Cinco tests. Confirmar dos veces se rechaza: mentiría sobre cuándo entró el dinero, y esa fecha
es la que sostiene cualquier reclamación posterior.

#### Fase 3b — El agujero, cerrado · ✅ HECHA (31/08/2026)

`UpdateOrderPaymentStatusUseCase` **rechaza `paid`** con el motivo escrito, y no sacándolo del
`in:` del FormRequest, que daría un 422 genérico: lo que el comerciante necesita saber es que
existe otro camino, no que el valor «no es válido».

Con eso desaparece el único punto donde el comerciante promovía una comisión. Los dos que
quedan —`ConfirmStorefrontPaymentUseCase` y `ConfirmCentralOrderPaymentUseCase`— son los dos de
la plataforma.

**Lo que le queda al comerciante:** `POST /api-tenant/order/{id}/report-payment`. El comprador a
veces le pasa la referencia por WhatsApp en vez de por el checkout; él lo sabe y la plataforma
sólo ve un depósito sin dueño. Reportarla hace que esa información llegue a quien tiene que
cuadrarla.

Se guarda **en la comisión y no en la fila de `payments` de la tienda**, porque el que tiene que
leerla es el administrador y él consulta la base central. Y **aparte** de la que puso el
comprador: si no coinciden, eso es justo lo que hay que poder ver.

Un test comprueba lo importante: **reportar no confirma nada**. Si reportar cobrara, el
comerciante tendría otra vez la llave del dinero por otro camino.

**Tres tests existentes afirmaban la regla vieja** y se reescribieron para afirmar la nueva. No
se rompieron: dejaron de ser ciertos.

#### Fase 3c — La pantalla · ✅ HECHA (31/08/2026)

`AdminStorefrontPaymentsPage`, con su entrada en el menú lateral y en el móvil. Sin ella los
endpoints de la Fase 3a no los llamaba nadie —una protección escrita y sin cablear, el patrón
que esta auditoría lleva cerrando— y, como el comerciante ya no puede marcar sus pedidos como
pagados, no había forma de destrabar una comisión desde la interfaz.

**Lo que enseña, y por qué:**

- **El importe en bolívares primero**, con los dólares debajo como referencia. Es lo que hay que
  buscar en el extracto: el comprador pagó bolívares.
- **Las dos referencias por separado.** La del checkout y, etiquetada aparte, la que reportó la
  tienda. Si no coinciden, eso es exactamente lo que hay que ver antes de confirmar.
- **Un aviso en el modal** de que sólo se confirma con el ingreso ya visto, y de que no hay
  vuelta atrás.
- **«Sin tasa registrada»** en rojo cuando la comisión no capturó tasa: ese importe no se puede
  cotejar en bolívares, y ocultarlo lo dejaría en un limbo silencioso.

**Un test comprueba que la pantalla responde y trae los datos**, no sólo que la ruta existe.

#### Un tropiezo de camino

El test daba 500 y el log apuntaba a un fichero borrado en PY1 — pero eran **rutas de Windows**:
el log lo estaba contaminando el PHP del host, igual que pasó con el worker de Horizon al
depurar el entorno de la suite. El error real era otro: la página nueva no estaba en el
manifiesto de Vite. **Un `npm run build` hacía falta de todas formas** para que funcione en el
navegador.

### Fase 4 — Retirar lo que sobra

`collection` deja de usarse. No se borra hasta que la Fase 3 esté funcionando: hasta entonces
sigue siendo el único camino por el que una comisión del escaparate podría cobrarse.

---

## Lo que NO cambia

La tasa congelada, la retención hasta `delivered`, la comisión por transferencia interbancaria
y el cálculo del saldo en bolívares siguen exactamente igual. Este cambio afecta a **de quién
es el dinero cuando entra**, no a cómo se cuenta después.

## Una idea que se descartó por ahora

Se planteó usar la retención como **palanca comercial**: que quien pague suscripción pueda
retirar desde el día uno, saltándose la ventana de garantía.

**Descartada el 31/08/2026.** Queda anotada por si vuelve, con el motivo por el que era
delicada: convertir una **regla de riesgo** en un **producto de pago** significa vender el
derecho a cobrar antes de que el comprador pueda reclamar. El día que a un cliente con plan de
pago haya que devolverle una venta ya retirada, esa excepción la habrá vendido la propia
plataforma.

Si se retoma, la ventana de garantía por plan es una excepción a
`central_payout_hold_days` en `TenantAvailableBalance::diasDeRetencion()`, y lo que se abre con
ella es exactamente el escenario de
[`planes/por_hacer/PLAN_REEMBOLSO_TRAS_RETIRO.md`](../por_hacer/PLAN_REEMBOLSO_TRAS_RETIRO.md).
