# Plan — La plataforma cobra todas las ventas

> **Estado:** ⬜ Por hacer · Redactado el 30/08/2026
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

### Fase 1 — El escaparate cobra a la cuenta de la plataforma ⬅️ **lo urgente**

Es lo único que, cada día que siga como está, **pone dinero en la cuenta equivocada**. Todo lo
demás es procesar dinero que ya tienes.

`StorefrontPaymentMethodsProvider::paymentSettings()` es el único sitio que decide de qué
tienda salen los datos, así que el cambio está contenido ahí: los **datos de la cuenta** pasan
a venir de `central_settings`, y los **interruptores por tienda** —qué métodos ofrece cada
escaparate— se quedan como están. Una tienda puede seguir decidiendo que no acepta Binance;
lo que no decide es a qué cuenta entra el dinero.

### Fase 2 — Quitar el filtro de canal del saldo

En la Fase 2 del plan de wallet se puso esto:

```php
->whereNotNull('central_order_id')   // sólo el canal central
```

con este razonamiento: *«en el escaparate el comerciante ya cobró directo en su banco, la
plataforma no le debe nada»*. **Bajo este modelo es falso.** Si la plataforma cobra esas
ventas, se las debe, y ese filtro le esconde al comerciante saldo que es suyo.

Cae el filtro y cae el test que lo defiende. Los demás filtros del saldo —estado, tasa
capturada, entrega— siguen valiendo igual.

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
