# Plan — Reembolsar cuando el dinero ya salió en un retiro

> **Estado:** ⬜ Por hacer · Redactado el 31/08/2026
>
> Es el **punto B** que quedó abierto al cerrar el plan de wallet y retiros. No se implementa
> ahora; este documento existe para que lo que ya se sabe no haya que volver a averiguarlo.

---

## El problema

La plataforma cobra todas las ventas. Si el comprador reclama y hay que devolverle su dinero,
la plataforma lo devuelve de su cuenta.

Mientras el importe siga en la wallet del comerciante, no hay problema: se descuenta de ahí.
**El caso difícil es cuando ese dinero ya se le pagó en un retiro.** Entonces la plataforma
paga dos veces —al comerciante y al comprador— y lo que le queda es reclamárselo al
comerciante.

## Lo que ya está construido, y que hay que entender antes de tocar nada

### La retención lo hace poco frecuente, no imposible

Un importe no es retirable hasta que el pedido llega a `delivered` **y** pasa su ventana de
garantía (`central_payout_hold_days`, un día por defecto). Eso cubre el caso más común —el
paquete que no llegó— pero no el que aparece a las tres semanas.

### La nota de crédito ya existe (hallazgo N16)

`ReverseOrderCommissionUseCase` no se limita a marcar la comisión: cuando ya estaba liquidada,
**emite otra con el importe en negativo**, `pending` y sin `settlement_id`.

```php
'order_total'       => -1 * $original->order_total,
'commission_amount' => -1 * $original->commission_amount,
```

`GenerateTenantCommissionSettlementUseCase` la recoge como cualquier otra, así que el neto de
la siguiente liquidación **sale corregido solo**. Para un `payout` eso es exactamente lo que
hace falta: la fila negativa resta `gross − comisión`, que es la parte del comerciante.

**No hace falta inventar un mecanismo de devolución: hay que terminar el que existe.**

---

## Los dos huecos, verificados

### 1. La nota de crédito la ve la liquidación pero **no la wallet**

Se emite sin `exchange_rate` y sin `released_at`. Y `TenantAvailableBalance` exige los dos:

```php
->whereNotNull('exchange_rate')
->where('released_at', '<=', $limite)
```

mientras que `GenerateTenantCommissionSettlementUseCase` sólo mira `status` y `settlement_id`.

**Resultado: dos caminos y dos respuestas.** Hoy, al revertir una venta ya liquidada, el saldo
que el comerciante ve en su wallet **no baja**, pero su siguiente liquidación sí saldrá
recortada. Puede pedir un retiro contra un saldo que ya no le corresponde.

Es el mismo patrón que la fórmula duplicada del saldo que se corrigió el 30/08/2026: dos
consultas que responden a la misma pregunta y divergen.

**Arreglo probable:** que la nota de crédito herede `exchange_rate` de la comisión original y
nazca con `released_at` puesto —una deuda no espera a entregarse ni a cumplir garantía—. Hay
que comprobar si el signo negativo se comporta bien en la suma en bolívares.

### 2. Si la tienda no vuelve a vender, la nota nunca se compensa

Una nota de crédito se absorbe contra ventas futuras. Un comerciante que cierra, o que
simplemente deja de vender, se queda con un saldo negativo que **no tiene contra qué restarse**,
y la plataforma sin forma de cobrarlo.

Esto ya no es código: es qué hacer con un comerciante que debe dinero. Hay que decidirlo antes
de diseñar nada.

---

## Lo que hay que decidir

1. **Quién asume la pérdida cuando no se puede recuperar.** ¿La plataforma la da por perdida a
   partir de cierto importe, o se reclama siempre?
2. **Si un saldo negativo bloquea algo.** `suspended` ya está cableado desde la Fase 5 y podría
   servir; pero suspender a un comerciante que además dejó de vender no recupera nada.
3. **Si hay un tope de tiempo para reclamar.** Un reembolso a los seis meses de una venta ya
   pagada es un caso distinto de uno a los diez días.
4. **Si la ventana de garantía debería ser más larga** que un día para ciertas categorías. Es la
   palanca más barata: cada día de retención es un día menos de exposición.

## Lo que NO hay que hacer

**Alargar la ventana de garantía indefinidamente** para no tener que resolver esto. El
comerciante necesita cobrar; una plataforma que retiene un mes su dinero pierde comerciantes
más rápido de lo que ahorra en reembolsos.

**Inventar una tabla de deudas.** La nota de crédito ya es exactamente eso: una comisión al
revés. Una segunda representación de lo mismo divergiría, y este proyecto ya tiene tres
cicatrices de copias que divergieron.

---

## Por dónde empezar el día que se retome

El hueco 1 es **pequeño, verificable y no depende de ninguna decisión de negocio**: que la nota
de crédito lleve tasa y fecha de liberación, y un test que compruebe que el saldo de la wallet y
el de la liquidación dicen lo mismo después de revertir una venta ya liquidada.

El hueco 2 no se empieza hasta que las cuatro preguntas de arriba tengan respuesta.
