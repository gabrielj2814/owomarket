# Plan — Reembolsar cuando el dinero ya salió en un retiro

> **Estado:** ✅ Hueco 1 cerrado (no había nada que arreglar) · Hueco 2 cerrado por el fondo de garantía (11/09/2026)
>
> Redactado el 31/08/2026. **Reescrito el 10/09/2026 tras medir el comportamiento real:
> la mitad de este documento describía un fallo que no existe, y el arreglo que proponía
> habría introducido uno.** Lo que sigue está verificado con tests, no razonado sobre el
> código.

---

## El problema

La plataforma cobra todas las ventas. Si el comprador reclama y hay que devolverle su dinero,
la plataforma lo devuelve de su cuenta.

Mientras el importe siga en la wallet del comerciante, no hay problema: se descuenta de ahí.
**El caso difícil es cuando ese dinero ya se le pagó en un retiro.** Entonces la plataforma
paga dos veces —al comerciante y al comprador— y lo que le queda es reclamárselo al
comerciante.

## La retención lo hace poco frecuente, no imposible

Un importe no es retirable hasta que el pedido llega a `delivered` **y** pasa su ventana de
garantía (`central_payout_hold_days`, un día por defecto). Eso cubre el caso más común —el
paquete que no llegó— pero no el que aparece a las tres semanas.

---

## ⚠️ Antes de tocar nada: hay DOS mecanismos, y son distintos a propósito

Esto es lo que la versión anterior de este plan no vio, y es lo único que de verdad hay que
entender aquí. Una reversión se descuenta por dos vías separadas porque hay dos preguntas
distintas que responder, y **cada una ya está resuelta en su sitio**:

### La wallet es un saldo corriente

`ReverseOrderCommissionUseCase` pasa la comisión a `refunded` o `waived`. Ninguno de los dos
está en `ESTADOS_COBRADOS`, así que **la venta desaparece de `netEarnings()` por sí sola**.

Y si además se le había pagado en un retiro, ese retiro **sigue restándose** en `payouts()`.
Esa resta huérfana —un pago sin ganancia que lo respalde— **es la deuda**. No hay que
registrarla en ninguna parte: emerge de la aritmética.

### Las liquidaciones son documentos de un periodo cerrado

Una liquidación ya emitida no se reescribe. Por eso la corrección tiene que llegar como una
fila negativa en la siguiente, y para eso existe la nota de crédito del hallazgo N16:
`ReverseOrderCommissionUseCase::issueCreditNote()` emite una `PlatformCommission` con el
importe en negativo, `pending` y sin `settlement_id`, que
`GenerateTenantCommissionSettlementUseCase` recoge como cualquier otra.

### Por qué la nota de crédito NO debe entrar en la wallet

Nace sin `exchange_rate`, y `netEarnings()` exige `whereNotNull('exchange_rate')`. Parece un
descuido. **No lo es: es lo único que evita cobrar la devolución dos veces.**

La versión anterior de este plan proponía exactamente eso —darle tasa y `released_at` para que
la wallet la sumara— por creer que la wallet no se enteraba de las reversiones. Medido, con
una venta de 100 USD a tasa 50 (4.600 Bs de parte del comerciante):

| Escenario | Saldo real | Con la nota sumada a la wallet |
|---|---|---|
| Venta cobrada y retirable | 4.600 Bs | 4.600 Bs |
| Tras reembolsarla | **0 Bs** | 0 Bs |
| Retiro pagado, reembolso, y vuelve a vender 9.200 Bs | **4.600 Bs** ✅ | **0 Bs** ❌ |

La última fila es el escenario entero de este plan. La deuda ya se cuenta una vez; sumar la
nota la contaría dos, y le cobraría 4.600 Bs de más a un comerciante que no los debe.

**Está cerrado con tests:** `tests/Feature/Monetization/CreditNoteBalanceTest.php`. El que
vigila es «la deuda de un reembolso tras un retiro ya pagado se cuenta una sola vez» —
verificado que se pone rojo (`0.0 is identical to 4600.0`) si alguien reimplementa aquella
idea.

---

## Hueco 1 — CERRADO

> Decía: «la nota de crédito la ve la liquidación pero no la wallet, así que el saldo del
> comerciante no baja al revertir una venta ya liquidada».

**No existe.** El saldo sí baja, por la vía del cambio de estado descrita arriba.

De dónde salió el error: la lista blanca `ESTADOS_COBRADOS` entró el **30/08/2026** (commit
`d49f623`, Fase 2 del plan de wallet y retiros) y este plan se redactó el **31/08**, sobre el
comportamiento de la víspera. Antes de esa lista, `netEarnings()` sumaba todos los estados y
una venta `refunded` sí seguía contando como saldo. El plan describía correctamente un código
que ya había cambiado el día anterior.

**Lección, que es lo que vale de todo esto:** el plan razonaba sobre el código leyéndolo. Dos
sondas de veinte líneas contra la base de datos en memoria tumbaron la premisa en dos minutos.
Cuando un plan afirma que algo está roto, medirlo antes de arreglarlo cuesta menos que
arreglar lo que no lo estaba.

## Hueco 2 — CERRADO

> Si la tienda no vuelve a vender, la deuda nunca se compensa.

**Cerrado el 11/09/2026 por el fondo de garantía** (subsistema 4 de la decisión de garantías):
cada venta retiene un 10% durante 60 días, así que la deuda de un reembolso tiene con qué
pagarse sin depender de que la tienda vuelva a vender. El hueco deja de poder existir por
construcción en vez de tener que gestionarse.

Lo que sigue abierto no es este hueco sino su contabilidad fina: **que un saldo negativo se
pueda ver**. `requestable()` y `settleable()` terminan en `max(0.0, …)`, así que una tienda que
debe 4.600 Bs y una con saldo cero se ven idénticas. El fondo lo hace raro; no lo hace
imposible.

Lo que decía antes de cerrarse:

Una deuda se absorbe contra ventas futuras. Un comerciante que cierra, o que simplemente deja
de vender, arrastra un saldo negativo que no tiene contra qué restarse, y la plataforma se
queda sin forma de cobrarlo.

Hoy ese negativo es además **invisible**: `requestable()` y `settleable()` terminan en
`max(0.0, …)`, así que un comerciante que debe 4.600 Bs y uno con saldo cero se ven idénticos
en pantalla. Es seguro —bloquea el retiro— pero nadie puede reclamar lo que no puede ver.

Esto ya no es código: es qué hacer con un comerciante que debe dinero.

### Las cuatro preguntas, ya respondidas (10/09/2026)

Se decidieron en [`planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`](../anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md),
que es donde vive el razonamiento completo. Resumen:

| # | Pregunta | Respuesta |
| :--- | :--- | :--- |
| 1 | Quién asume la pérdida | **La tienda primero**, con un fondo retenido de sus propias ventas. Si no alcanza, **la plataforma cubre hasta un tope por pedido** y la tienda queda con deuda |
| 2 | Si un negativo bloquea algo | **Sí.** Topa su nivel de reputación en medio —lo que le encarece la retención y le ralentiza los cobros—, más registro público, suspensión y bloqueo de identidad para reabrir |
| 3 | Tope de tiempo para reclamar | Deja de ser arbitrario: **es el plazo de garantía del producto**, que pasa a ser un atributo de catálogo |
| 4 | Ventana de garantía más larga | **Se sustituye por el fondo.** En vez de retener el 100% durante más tiempo, se retiene un porcentaje —variable según el nivel de la tienda— durante el plazo de garantía |

### Y con eso, este hueco cambia de naturaleza

Deja de ser un problema abierto y pasa a ser **una consecuencia de dimensionar bien una reserva**.
La deuda irrecuperable no se elimina —la plataforma sí pone dinero cuando la tienda falla— pero
queda acotada por el tope y encarecida para quien la genera.

**Lo que queda no es un plan de cobro a morosos: son números.** Qué porcentaje se retiene en cada
nivel de reputación, durante cuánto tiempo, cuál es el tope por pedido y dónde salta la alarma
mensual. Es el subsistema 4 de los cinco que salen de aquella decisión, y hay que hacerlo antes
que el sistema de reclamaciones — sin fondo, una reclamación no tiene con qué pagarse.

### El mecanismo de este plan es el que cobra la deuda

Vale la pena notarlo: **la deuda que deja una tienda tras una cobertura de la plataforma se salda
por el mismo camino que describe este documento** — el saldo negativo absorbiéndose contra ventas
futuras, verificado en
[`CreditNoteBalanceTest.php`](../../tests/Feature/Monetization/CreditNoteBalanceTest.php). La
tienda no tiene que reunir una suma y entregarla: vendiendo, paga. Por eso el sistema de
reputación no la expulsa, sino que le encarece seguir.

### Lo que sigue haciendo falta igual

**Que el negativo se pueda ver.** Aunque el fondo lo haga raro, mientras `requestable()` y
`settleable()` terminen en `max(0.0, …)` un comerciante que debe 4.600 Bs y uno con saldo cero se
ven idénticos. Bloquea el retiro, que es lo importante, pero nadie puede reclamar lo que no puede
ver.

---

## Anotación aparte: el `max(0.0, …)` de la liquidación

Hallazgo del 10/09/2026, **no bloqueante y sin impacto en dinero hoy**, anotado para que no
haya que volver a rastrearlo.

[`GenerateTenantCommissionSettlementUseCase`](../../src/Monetization/Application/UseCases/GenerateTenantCommissionSettlementUseCase.php):

```php
$netAmount = $type === 'collection' ? $commissionAmount : max(0.0, $grossSalesAmount - $commissionAmount);
```

En el camino `payout`, si las comisiones pendientes suman negativo, se emite una liquidación
por **0** y acto seguido se le estampa `settlement_id` a la nota de crédito: queda consumida
sin haber compensado nada.

**Por qué no urge:** ese camino crea liquidaciones en `USD`, y `TenantAvailableBalance::payouts()`
solo cuenta `VES`. No toca el saldo de nadie. Los retiros reales van por
`CreateTenantOwnerPayoutRequestUseCase`, que no enlaza comisiones. El camino `collection` —el
que sí cobra comisiones al comerciante— no lleva ese recorte y funciona bien.

Queda como inconsistencia latente del camino en USD. Si algún día se unifica la moneda de las
liquidaciones, hay que resolverlo antes.

---

## Lo que NO hay que hacer

**Sumar la nota de crédito a la wallet.** Está explicado arriba con los números. Hay un test
que lo impide.

**Alargar la ventana de garantía indefinidamente** para no tener que resolver el hueco 2. El
comerciante necesita cobrar; una plataforma que retiene un mes su dinero pierde comerciantes
más rápido de lo que ahorra en reembolsos.

**Inventar una tabla de deudas.** La deuda ya está representada dos veces y bien: como resta
huérfana en la wallet y como nota de crédito en las liquidaciones. Una tercera
representación divergiría, y este proyecto ya tiene tres cicatrices de copias que divergieron.
