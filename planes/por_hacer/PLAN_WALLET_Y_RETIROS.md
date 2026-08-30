# Plan — Wallet de la tienda y retiros

> **Estado:** 🔵 En curso · Redactado el 30/08/2026 · **Fase 1 ✅ cerrada, 3 tests**
>
> Sale de una conversación de diseño sobre cómo debe fluir el dinero del marketplace
> central. Se descartaron dos modelos antes de llegar a éste; queda escrito por qué, porque
> el motivo del descarte es lo que sostiene el diseño.

---

## El modelo

El comprador paga **una sola vez a la plataforma**. Cada tienda ve su saldo en una wallet,
pide el retiro cuando quiere, y la plataforma le transfiere. **La comisión se descuenta en el
retiro.**

### Por qué éste y no los otros dos

**Modelo A — el que había:** la plataforma cobra y luego paga a la tienda. No es que
estuviera mal: es que **nunca funcionó**. La dirección `payout` de las liquidaciones no se
podía generar jamás porque nada ponía un pedido central en `paid` (hallazgo A, cerrado el
30/08/2026). El modelo estaba bien; le faltaba la pieza que lo activaba.

**Modelo B — pago directo a cada tienda:** el comprador transfiere a cada comercio y sube una
referencia por tienda. Se descartó por dos motivos, y el segundo es fatal:

1. Tres transferencias por carrito es fricción real en la conversión.
2. **La comisión pasaba a depender de que el deudor la declarara.** El comerciante ya tenía
   su dinero en el banco sin hacer nada; marcar el pedido como pagado sólo le añadía una
   factura de la plataforma. Sin retención no hay palanca.

**Este modelo resuelve las dos.** Un solo pago, y para sacar su dinero el comerciante pasa
por la plataforma: la comisión se retiene, no se cobra.

### Los dos canales

No hay un modelo único, y eso no es incoherencia — son dos negocios distintos:

| | Marketplace central | Escaparate de la tienda |
| :--- | :--- | :--- |
| Quién cobra | **La plataforma** | El comerciante, directo |
| Comisión | **Retenida en el retiro** | El comerciante la debe |
| Liquidación | `payout` | `collection` |
| Palanca si no paga | No hace falta | `suspended` |

Los dos tipos de `commission_settlements` no eran indecisión: **uno por canal.**

Y esto reduce el hallazgo **B** —«la comisión depende de un botón que al comerciante no le
conviene pulsar»—: desaparece del canal central y sobrevive sólo en el escaparate.

---

## Lo que ya está construido

Casi todo. `commission_settlements` **es** la solicitud de retiro:

```
type                enum('collection','payout')
gross_sales_amount / commission_amount / net_amount
payment_method / payment_reference / settled_at
status              enum('pending','settled','cancelled')
```

Un `payout` ya calcula `net_amount = ventas brutas − comisión`, que es exactamente «al
retirar se le cobra la comisión». `ConfirmAndSettleCommissionUseCase` ya cierra el pago con
su referencia. `ExchangeRate` ya tiene sincronización con el BCV, tasa activa, manuales,
histórico y conversión.

**La wallet no necesita tabla.** El saldo es la suma de las comisiones en `pending` sin
`settlement_id`, valoradas en bruto menos comisión. Es una vista.

---

## La decisión de moneda

**El saldo se guarda en bolívares, congelado a la tasa de la venta.**

La plataforma recibe X Bs del comprador y le debe exactamente esos X Bs al comerciante. La
posición queda cuadrada pase lo que pase con la tasa: **no se debe más de lo que se tiene.**
El comerciante asume la devaluación si tarda en retirar, lo que además le da un incentivo a
retirar pronto.

La alternativa —saldo en USD, convertido el día del retiro— pone el riesgo cambiario sobre la
plataforma: se reciben bolívares y se deben dólares. En Venezuela eso es una deuda que crece
sola mientras el dinero está en la cuenta.

**Consecuencia técnica:** hay que capturar la tasa **en el momento de la venta**. Es la
Fase 1, y es lo único que no se puede añadir después: cada venta que ocurra sin su tasa
capturada es una venta cuyo saldo ya no se puede congelar.

---

## Fases

### Fase 1 — La tasa en la comisión · ✅ HECHA (30/08/2026)

Una columna `exchange_rate` en `platform_commissions`, capturada al crear la comisión desde
`GetActiveExchangeRateUseCase`.

Va en `CalculateAndRecordOrderCommissionUseCase` porque es el punto por donde pasan **los dos
canales**: el checkout del escaparate y el despacho central. Una captura, no dos.

Los bolívares salen derivados y sumables en SQL, sin más columnas:

```sql
SUM((order_total - commission_amount) * exchange_rate)
```

**Nullable a propósito.** Si no hay tasa activa, la venta no puede tumbarse por eso: se
registra la comisión sin tasa y se deja rastro en el log. Una comisión sin tasa no es
retirable hasta que alguien la valore — eso lo resuelve la Fase 2, no ésta.

**Sin relleno de las filas existentes**, al revés que la migración de `central_order_id`.
Allí el dato correcto ya estaba escrito en `metadata` y sólo había que recuperarlo; aquí no
existe en ninguna parte, e inventar la tasa histórica de una venta pasada sería fabricar el
importe de una deuda.

**Vigilada por tres tests** en `tests/Feature/Monetization/CommissionExchangeRateTest.php`:
la tasa se congela; una venta no se cae si el BCV no ha sincronizado; y el saldo en bolívares
sale de la consulta derivada **conservando cada venta la tasa de su día** — con dos ventas a
tasas distintas, que es lo que distingue congelar de revalorizar.

### Fase 2 — La wallet

Vista de solo lectura en el backoffice de la tienda: saldo retirable en Bs, saldo retenido, y
el desglose por pedido. Y qué hacer con las comisiones sin tasa capturada.

### Fase 3 — Solicitar retiro

El comerciante dispara un `payout`; hoy sólo lo dispara el admin.
`GenerateTenantCommissionSettlementUseCase` ya calcula el neto. El admin transfiere y cierra
con `ConfirmAndSettleCommissionUseCase`.

### Fase 4 — Retención y banco

Sólo entra en el saldo retirable lo que llegó a `delivered` — la máquina de estados ya sabe
cuándo pasa, así que no hay que inventar plazos. Protege del reembolso posterior al retiro.
Y el banco obligatorio como ajuste de la tienda, validado al pedir el retiro.

### Fase 5 — Cablear `suspended`

Sigue sin aplicarse en ningún sitio: `TenantStatus::STATUS_SUSPENDED` se escribe y nadie lo
comprueba. Otra protección escrita y sin cablear. Ahora sólo hace falta para la mora del
canal escaparate.

---

## Lo que este plan no resuelve, y hay que mirar fuera del código

**El saldo de las wallets no es dinero de la plataforma**, es una deuda con los comerciantes.
Cuenta bancaria separada, y ese saldo nunca cuenta como ingreso. Es la disciplina que sostiene
el modelo, y no es algo que el código pueda garantizar.

**Custodiar fondos de terceros puede encuadrar a la plataforma como intermediario de pagos
ante SUDEBAN.** Hay que verificarlo con un contador antes de operar en serio. Queda escrito
aquí para que no se pierda.

**Las disputas se quedan a medias.** `resolve-dispute` marca un reembolso, pero si el dinero
ya salió en un retiro, devolverlo es perseguirlo. La Fase 4 lo mitiga reteniendo hasta
`delivered`; el caso del reembolso posterior sigue sin diseño.
