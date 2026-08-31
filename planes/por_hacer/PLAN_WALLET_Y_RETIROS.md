# Plan — Wallet de la tienda y retiros

> **Estado:** 🔵 En curso · Redactado el 30/08/2026 · **Fases 1 a 4 ✅ cerradas · queda la Fase 5**
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

**El saldo ES bolívares.** No es «dólares convertidos a bolívares».

El dólar es la unidad en la que se **pone el precio**: al comprador se le muestra el precio en
dólares y su equivalente en bolívares a la tasa del día, y **paga bolívares**. Nunca entra un
dólar a ninguna cuenta. Así que el saldo de la wallet es la suma de los bolívares que aportó
cada venta —su total en USD por la tasa a la que compró ese cliente— y **no hay nada que
convertir al leer**.

Esta redacción llegó después de una peor: «un saldo en dólares congelado a la tasa de la
venta». Es la misma aritmética, pero invitaba a la pregunta *«¿qué tasa usamos al retirar?»*,
que en este modelo no existe.

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

### Fase 2 — La wallet · ✅ HECHA (30/08/2026)

**La wallet ya existía**, y la Fase 3 también: `TenantOwnerWalletPage`,
`GetTenantOwnerWalletSummaryUseCase` y un `CreateTenantOwnerPayoutRequestUseCase` que ya
verifica propiedad, corre en transacción y bloquea con `lockForUpdate()` (hallazgos T1 y C3,
cerrados). Así que la Fase 2 no fue construir nada: fue arreglar lo que había.

#### 🔴 La fórmula del saldo contaba dinero que no existe

`TenantAvailableBalance::netEarnings()` sumaba `order_total` y `commission_amount` de **todas**
las comisiones de la tienda. Le faltaban los dos filtros, y **no es una consulta de pantalla:
es la que autoriza cuánto dinero real sale**.

- **Sin filtro de estado.** El enum es `awaiting_payment`, `pending`, `collected`, `waived`,
  `refunded`. Una venta cancelada o reembolsada seguía contando como saldo retirable, y un
  cobro sin confirmar también.
- **Sin filtro de canal.** Contaba las ventas del escaparate, donde el comprador transfirió
  directo al banco del comerciante. La plataforma no recibió ese dinero y aun así se lo
  ofrecía para retirar: pagar dos veces la misma venta.

Ahora `ventasDe()` acota a `central_order_id` no nulo, y los estados van por **lista blanca**,
`['pending', 'collected']`, no por lista negra: en dinero, un estado nuevo que nadie previó
tiene que quedarse fuera del saldo, no colarse dentro.

#### La copia de la fórmula, borrada

`GetTenantOwnerWalletSummaryUseCase` calculaba el saldo por su cuenta, con su propia resta y
una tasa BCV escrita a mano —`775.3356`— bajo un `TODO(Fase 1)`. El docblock del servicio
compartido ya lo había advertido:

> *«dos copias de una fórmula de saldo que divergen es como se pierde dinero, y este
> repositorio ya ha demostrado que las copias divergen»*

La advertencia estaba escrita y la copia seguía viva al lado. Ahora la pantalla y la
autorización del retiro leen el mismo número.

#### Lo demás

Los bolívares salen de las tasas congeladas de la Fase 1. Se añadieron dos apartados
visibles: **retenido** (ventas cuyo cobro la plataforma no ha confirmado) y **pendiente de
valorar** (sin tasa capturada). Excluirlas en silencio le haría desaparecer dinero al
comerciante sin explicación, que es el patrón de fallo que esta auditoría lleva cerrando
desde PR2.

Y se quitó `amount_ves` del historial de liquidaciones: se calculaba con la tasa fija, así que
era un número inventado.

**Un fallo que apareció de camino:** la pantalla enviaba
`tenant_id: wallet.settlements[0]?.tenant_id || 'tecs'` al pedir un retiro. Una tienda sin
liquidaciones previas —toda tienda nueva— mandaba el id literal `'tecs'`. El backend verifica
la propiedad, así que no colaba; simplemente **no se podía pedir el primer retiro nunca**. El
resumen devuelve ahora el `tenant_id`.

**Vigilada por siete tests**, y seis de ellos fallan sin el arreglo. El que importa no
comprueba una cifra en pantalla: comprueba que **un retiro pedido contra el saldo inflado se
rechaza**. Antes esas cuatro comisiones —reembolsada, cancelada, sin confirmar y del
escaparate— sumaban 368 USD retirables.

### Fase 3 — El retiro, en bolívares · ✅ HECHA (30/08/2026)

`CreateTenantOwnerPayoutRequestUseCase` ya existía y estaba bien construido. Lo que estaba mal
era la **unidad**, y estaba mal en los dos lados a la vez:

| | Antes | Ahora |
| :--- | :--- | :--- |
| Saldo que calcula el servicio | USD | **Bs** |
| Saldo que mostraba la pantalla | Bs | Bs |
| Importe que pide el comerciante | USD | **Bs** |
| `commission_settlements.currency` | `'USD'` | `'VES'` |

**El comerciante veía bolívares y escribía dólares en el mismo formulario.** Con un saldo de
23.000 Bs, el campo aceptaba hasta 276 — y rechazaba cualquier retiro real.

`netEarnings()` devuelve ahora bolívares, y los retiros que se restan del saldo se filtran por
`currency = 'VES'`: sin ese filtro, una liquidación vieja en dólares se restaría como si fueran
bolívares y descuadraría el saldo por el factor entero de la tasa.

`gross_sales` y `total_commissions` siguen en USD **a propósito**: son la unidad en la que el
comerciante puso sus precios y le sirven de referencia. Pero el dinero es bolívares, y la
pantalla ya no los mezcla — el historial de liquidaciones muestra la moneda de cada fila,
porque ahí conviven retiros en Bs con liquidaciones de comisión del escaparate en USD.

**Cuatro tests más**, y uno de ellos cubre el descuadre de unidades: que un retiro viejo en
dólares no se reste como si fueran bolívares.

**Y un test de la Fase 2 que estaba verde por el motivo equivocado:** asignaba la propiedad de
la tienda con `tenants.user_id`, que no es como se resuelve —va por `tenant_users`—, así que la
solicitud fallaba con un 403 de permisos y **nunca llegaba a mirar el saldo**. Ahora comprueba
el código 422 y el mensaje, no sólo que algo lanzó.

### Fase 4a — La tasa en el pedido · ✅ HECHA (30/08/2026)

Apareció al cerrar la Fase 3. La tasa vivía sólo en la comisión: bastaba para calcular la
wallet del comerciante, pero **no decía nada del comprador**.

**Corrección de cómo se anotó primero:** se escribió que el cliente «ve un importe distinto del
que pagó si vuelve dos días después». Es falso — las pantallas de pedidos del cliente muestran
dólares, no bolívares, así que no hay deriva visible. Lo que hay es un **hueco de registro**:
el comprador paga bolívares en el checkout y esa cifra no quedaba en ninguna parte. Existía
sólo en su extracto bancario.

Columna `exchange_rate` en `orders` y en `central_orders`, escrita al crear. En el storefront
va por `DB::table('orders')->update()`, el mismo camino y el mismo motivo que `coupon_code`:
atravesar DTO, entidad y repositorio para un dato que el dominio del pedido no usa.

**Lo importante es la herencia.** El pedido de cada tienda **copia la tasa del pedido central**
en vez de capturar la suya. El comprador hizo **un** pago a **una** tasa; si cada tienda cogiera
la del momento en que corrió el job —que puede ser horas después, o un reintento al día
siguiente— la suma de los bolívares de las tiendas no cuadraría con lo que pagó el cliente.

#### Dos errores propios, del mismo tipo

**1. Un `catch (Throwable)` sin importar la clase.** Resolvía al namespace del controlador, así
que no capturaba nada y el checkout entero devolvía 400 cuando no había tasa activa. Lo delató
la suite, no la lectura.

**2. El `try` envolvía también la escritura.** Un fallo de esquema quedaba tragado y el pedido
se guardaba sin tasa, en silencio — el mismo `catch` vacío que se quitó en el hallazgo C, tres
horas después de quitarlo. Ahora el `try` cubre **sólo** la consulta de la tasa: sin tasa activa
el pedido sigue siendo válido, pero un fallo de escritura tiene que verse.

Cuatro tests que construyen el esquema del inquilino a mano necesitaron la migración aditiva,
igual que en su día con `coupon_code`. La comprobación real vive en
`StorefrontCheckoutPaymentsTest`, que ejecuta el checkout completo: el pedido creado guarda la
tasa activa.

#### La herencia sí tiene test, y encontró que el arreglo no funcionaba

Se quedó sin cubrir porque el único fichero que ejercita `DispatchCentralOrderToTenantsUseCase`
—`MultiStoreCentralCheckoutTest`— estaba en rojo por el entorno de la suite. En cuanto se
arregló eso (ver `planes/anotaciones/ENTORNO_DE_TESTS.md`) se escribió el test, y **falló a la
primera**:

`exchange_rate` no estaba en el `$fillable` de `CentralOrder`, así que el `create()` la
**descartaba en silencio** y la columna se quedaba a null. La captura en el pedido central
nunca llegó a funcionar. El test del storefront no lo cazaba porque ese camino escribe con
`DB::table()`, que no pasa por la asignación masiva.

Es el patrón de siempre —aceptar la operación y no aplicarla—, cometido al implementar el plan
que lo persigue. Y la lección de todo el documento otra vez: **anotar «esto no tiene test» no
es lo mismo que tenerlo.**

El test fija además lo que importa de verdad: la tasa cambia entre el pedido y la comprobación,
y el pedido de cada tienda **sigue teniendo la del pedido central**.

### Fase 4b — Retención hasta la entrega · ✅ HECHA (30/08/2026)

Sólo entra en el saldo retirable lo que llegó a `delivered`. La máquina de estados ya sabe
cuándo pasa, así que no hubo que inventar plazos. Protege del reembolso posterior al retiro: si
la plataforma paga antes de que la mercancía llegue y el comprador reclama después, el dinero
ya salió y recuperarlo es perseguirlo.

**Una columna, `released_at`, y no una consulta.** La comisión vive en la base central y el
estado del pedido en la de cada tienda. Preguntarlo al calcular el saldo obligaría a entrar en
la base de cada inquilino en cada consulta de wallet. Se anota cuando ocurre: una escritura por
pedido en vez de una lectura por consulta.

`ReleaseOrderCommissionUseCase` responde a una pregunta distinta de `Activate`, y hacen falta
las dos:

| Caso de uso | Pregunta | Lo dispara |
| :--- | :--- | :--- |
| `Activate` | ¿Entró el dinero? | Confirmar el cobro (N15, hallazgo A) |
| `Release` | ¿Llegó la mercancía? | El pedido pasa a `delivered` |

**Dos puntos lo disparan**, los dos en la capa de aplicación y **después** del guardado, nunca
dentro de la transacción del inquilino — escribe en la base central, y acoplar la entrega a esa
escritura es la lección de N25:

- `DeliverOrderUseCase`, donde la entrega es explícita.
- `MarkShipmentAsDeliveredUseCase`, que **pregunta por el estado real del pedido** en vez de
  darlo por hecho: la guarda de SH1 decide si el envío entregado lleva el pedido a `delivered`,
  y un pedido que ya lo estaba por otro envío no vuelve a liberarse.

La wallet distingue ahora **los dos motivos de retención** — esperando cobro y esperando
entrega — porque al comerciante le importa cuál es, y en el mismo saco sólo generan preguntas.

**Cuatro tests nuevos**, más el ajuste de los que ya había. Uno comprueba que liberar dos veces
**no mueve la fecha de la primera**: esa fecha es el rastro de cuándo el dinero pasó a ser
reclamable, y un segundo envío entregado del mismo pedido no puede reescribirla.

### Fase 4c — La comisión por transferir a otro banco · ✅ HECHA (30/08/2026)

**El banco dejó de ser obligatorio.** En vez de imponer uno —que habría costado comerciantes
que no quisieran abrir cuenta allí— cada tienda cobra donde quiera, y **quien elige la vía cara
la paga**: si el retiro exige una transferencia interbancaria, su coste se descuenta del
importe.

#### La trampa que apareció al diseñarlo

`TenantAvailableBalance` restaba los retiros por `net_amount`. Daba igual mientras
`net == gross`, y con la comisión dejan de serlo:

| | Bs |
| :--- | ---: |
| El comerciante pide | 4.600 |
| Comisión interbancaria | −100 |
| **Recibe** | **4.500** |
| **Sale de su wallet** | **4.600** |

Restando `net_amount` le quedarían **100 Bs de saldo fantasma después de cada retiro**, y
repetible: dinero que se crea solo. El saldo resta ahora `gross_sales_amount`, que es lo que de
verdad salió. Hay un test dedicado a esa cifra; si alguien vuelve a `net_amount`, falla.

#### Lo demás

Los tres importes de un retiro pasan a significar cosas distintas —`gross_sales_amount` lo que
sale de la wallet, `transfer_fee` lo que se queda la plataforma, `net_amount` lo que recibe el
comerciante— y por eso la comisión necesita columna propia en vez de reutilizar
`commission_amount`, que ya significa otra cosa.

**No hay ajuste nuevo en la tienda.** El formulario de retiro ya pide el banco de destino, así
que guardar además una preferencia sería una segunda fuente de verdad para el mismo dato. Del
lado de la plataforma sólo hizo falta una clave, `central_interbank_transfer_fee`: el banco
propio ya estaba en `central_pago_movil_bank_name`.

**El comerciante ve el descuento antes de confirmar.** Un retiro que llega mermado sin aviso es
una reclamación.

**Los bancos se comparan por nombre normalizado**, y queda marcado con su techo: «Banesco»,
«BANESCO» y « banesco » son el mismo, pero «Banco Banesco» no lo sería. Si eso empieza a dar
problemas la salida es una lista de bancos con código; hoy no hay evidencia de que haga falta.

**Cinco tests**, incluido el del saldo fantasma y el de las mayúsculas.

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
