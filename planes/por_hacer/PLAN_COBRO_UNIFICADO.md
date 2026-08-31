# Plan — La plataforma cobra todas las ventas

> **Estado:** 🔵 En curso · Redactado el 30/08/2026 · **Fases 1 y 2 ✅ cerradas**
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

Hace falta decidir dos cosas antes de diseñarlo:

1. **Cómo ve el administrador los cobros pendientes de todas las tiendas.** Recorrer las bases
   de los inquilinos, como ya hace el despacho central, o proyectar los pagos pendientes a una
   tabla central.
2. **Si el comerciante conserva algún papel.** Marcar «pagado» deja de tener sentido para él,
   pero puede seguir siendo útil que reporte una referencia que el cliente le pasó por otro
   canal.

### Fase 4 — Retirar lo que sobra

`collection` deja de usarse. No se borra hasta que la Fase 3 esté funcionando: hasta entonces
sigue siendo el único camino por el que una comisión del escaparate podría cobrarse.

---

## Lo que NO cambia

La tasa congelada, la retención hasta `delivered`, la comisión por transferencia interbancaria
y el cálculo del saldo en bolívares siguen exactamente igual. Este cambio afecta a **de quién
es el dinero cuando entra**, no a cómo se cuenta después.

## Y una idea que quedó pendiente de la misma conversación

Retención como palanca comercial: el saldo se puede retirar pasado un tiempo desde el pedido, y
**quien paga suscripción retira desde el día uno**. Se apila con la retención hasta `delivered`
de la Fase 4b, que es una regla de riesgo y no comercial.

Falta decidir si ese plazo cuenta desde **la entrega** o desde **la fecha del pedido**. Desde la
entrega es coherente con la protección que ya existe; desde el pedido, una venta que tarde en
entregarse podría cumplir el plazo antes de haber llegado y las dos reglas se pisarían.
