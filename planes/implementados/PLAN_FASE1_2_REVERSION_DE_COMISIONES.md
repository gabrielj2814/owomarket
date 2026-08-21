# PLAN — Fase 1.2: Revertir la comisión al cancelar o reembolsar un pedido

> **Origen:** hallazgo D2 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md` (punto 7 del plan de acción)
> **Severidad:** 🟠 Alto — la auditoría advierte: «esto va a generar disputas con tus comerciantes»
> **Tamaño:** 1 caso de uso nuevo, 2 casos de uso modificados, 1 archivo de test
> **Estado:** ✅ Implementado — pendiente de validación con `php artisan test`
> **Depende de:** nada (independiente de la Fase 1.1)

---

## 1. El problema

`CalculateAndRecordOrderCommissionUseCase` crea la comisión con `status = 'pending'` en el momento del despacho, **sin mirar el `payment_status`**. Y para `pago_movil`, `manual_transfer` y `cash_on_delivery` ese estado es siempre `pending`: son métodos donde el dinero se confirma después, o nunca.

Lo grave es que **no existía ninguna ruta que sacara la comisión de ese estado**. `CancelOrderUseCase` y `RefundOrderUseCase` sólo mutaban el agregado `Order` de la tienda; no tocaban la base de datos central.

**Escenario de la auditoría:** un cliente pide $1.000 con Pago Móvil y nunca paga. La tienda cancela. La comisión de $80 sigue en `pending` y `GenerateTenantCommissionSettlementUseCase` la incluye en la siguiente liquidación: **se le cobran $80 a la tienda por una venta que no existió.**

---

## 2. Solución

Nuevo caso de uso `Src\Monetization\Application\UseCases\ReverseOrderCommissionUseCase`, invocado desde `CancelOrderUseCase` y `RefundOrderUseCase`.

No hizo falta migración: la tabla `platform_commissions` ya definía los estados necesarios en su enum.

| Situación | Estado resultante | Razón |
| :--- | :--- | :--- |
| Pedido cancelado | `waived` | La venta nunca ocurrió; la plataforma renuncia a la comisión |
| Pedido reembolsado | `refunded` | La venta ocurrió y se deshizo |

Como `GenerateTenantCommissionSettlementUseCase` sólo recoge comisiones con `status = 'pending'` y `settlement_id` nulo, con esto dejan de entrar en las liquidaciones automáticamente.

### 2.1 El caso delicado: comisiones ya liquidadas

Si la comisión ya está `collected` o vinculada a una liquidación emitida, **no se puede "deshacer" sin más**: puede que al comerciante ya se le haya descontado el dinero.

La decisión tomada: se marca igual —para que no vuelva a liquidarse— **pero se deja señalada** en `metadata.reversal.requires_manual_adjustment = true`, junto con el estado y la liquidación previos, y se escribe un `warning` en el log. La corrección real es una nota de crédito en la siguiente liquidación, que hoy no existe como funcionalidad.

Es un compromiso consciente: es preferible dejar rastro explícito de una deuda pendiente que fingir que el problema no existe o bloquear la cancelación del pedido.

### 2.2 Un fallo al revertir no bloquea la cancelación

La comisión vive en la base central; el pedido, en la de la tienda. Si la central no está accesible desde ese contexto, **la cancelación del pedido sigue adelante** y el fallo se registra en el log. Impedir que un comerciante cancele un pedido porque una base de datos ajena no responde sería peor que el problema que se intenta resolver.

Esto deja una ventana conocida: una cancelación con la central caída deja la comisión viva. Ver seguimiento.

---

## 3. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **D2** — la comisión no se revierte al cancelar | ✅ Cerrado |
| **D2** — la comisión no se revierte al reembolsar | ✅ Cerrado |
| **D2** — comisión creada sin mirar el `payment_status` | 🟡 Parcial: se sigue creando en `pending` al despachar, pero ahora existe la ruta para anularla. Cobrar sólo tras confirmar el pago es un cambio de modelo mayor (ver seguimiento) |
| Reversión de comisiones ya liquidadas | 🟡 Se marca y se señala, pero la nota de crédito no existe como funcionalidad |

---

## 4. Tareas

- [x] Crear `ReverseOrderCommissionUseCase`
- [x] Invocarlo desde `CancelOrderUseCase` y `RefundOrderUseCase`
- [x] Marcar para ajuste manual las comisiones ya liquidadas
- [x] Tests de cancelación, reembolso y comisión ya liquidada
- [x] Añadir sesión de usuario de tienda al `beforeEach` del test de monetización (lo exige la Fase 0.3-E)
- [x] `php artisan test`
- [x] `vendor/bin/pint src/Monetization/ src/Order/`
- [ ] Revisar si hay comisiones huérfanas de pedidos ya cancelados (sección 6) — no aplica: base de datos de desarrollo reiniciada desde cero
- [x] Commit: `fix(monetization): revertir la comisión de la plataforma al cancelar o reembolsar un pedido`
- [x] `git push origin <rama_actual>`
- [x] Mover este documento a `planes/implementados/`

---

## 5. Verificación manual

**Debe seguir funcionando:**
1. Cancelar y reembolsar pedidos desde el backoffice de la tienda.
2. Generar una liquidación con comisiones legítimas.

**Debe cambiar:**
3. Cancelar un pedido con Pago Móvil sin pagar → su comisión pasa a `waived` y **no aparece** en la siguiente liquidación.
4. Reembolsar un pedido entregado → su comisión pasa a `refunded`.
5. Revertir una comisión ya liquidada → queda marcada con `requires_manual_adjustment` y aparece un `warning` en el log.

Consulta para encontrar reversiones que necesitan nota de crédito:

```sql
SELECT id, tenant_id, order_number, commission_amount, settlement_id
FROM platform_commissions
WHERE status IN ('waived','refunded')
  AND JSON_EXTRACT(metadata, '$.reversal.requires_manual_adjustment') = true;
```

---

## 6. Riesgo

**Bajo en el código, con una salvedad de datos.**

**Las comisiones de pedidos cancelados ANTES de este cambio siguen vivas en `pending`.** Nadie las va a revertir retroactivamente, así que entrarán en la próxima liquidación y se cobrarán indebidamente. Antes de desplegar conviene revisarlas:

```sql
-- Comisiones pendientes cuyo pedido de tienda ya no debería cobrarse.
-- Hay que ejecutarlo por tienda, porque los pedidos viven en la BD del inquilino.
SELECT pc.id, pc.tenant_id, pc.order_id, pc.order_number, pc.commission_amount
FROM platform_commissions pc
WHERE pc.status = 'pending' AND pc.settlement_id IS NULL;
```

Y contrastar esos `order_id` con los pedidos en estado `cancelled` o `refunded` de cada tienda. Si aparecen, se pueden marcar a mano con el mismo caso de uso.

---

## 7. Trabajo de seguimiento identificado

1. **La comisión sigue naciendo en `pending` al despachar, no al cobrar.** El modelo correcto sería crearla sólo cuando el pago se confirma (o mantenerla en un estado `awaiting_payment` que las liquidaciones ignoren). Es un cambio de modelo mayor y afecta a las métricas de la plataforma, así que se deja fuera de esta fase — pero es la raíz de D2, y mientras no se aborde seguiremos dependiendo de que alguien cancele el pedido para que la comisión se anule.
2. **No existen notas de crédito.** Revertir una comisión ya liquidada sólo deja una marca. Hace falta que `GenerateTenantCommissionSettlementUseCase` sepa restar ajustes negativos de liquidaciones anteriores.
3. **Ventana con la base central caída:** si la reversión falla, la cancelación sigue adelante y la comisión queda viva. Un job de reconciliación periódico que compare pedidos cancelados contra comisiones pendientes cerraría el hueco.
4. **`PlatformCommission.order_id` guarda el id del pedido del inquilino, pero las relaciones Eloquent lo declaran contra `central_orders`** (menor listado en la auditoría): `$centralOrder->commissions` devuelve siempre una colección vacía. No afecta a esta fase —la reversión busca por `order_id` directamente— pero conviene arreglarlo.
