# Plan — Confirmación del cobro de un pedido central

> **Estado:** ⬜ Por hacer · Redactado el 30/08/2026
>
> Sale de cerrar OR1 en `planes/anotaciones/AUDITORIA_BACKEND.md`. Allí quedó anotado que
> «en pago móvil, transferencia y contra entrega el `payment_status` no refleja la realidad».
> Al trazar el flujo del dinero entero resultaron ser **tres** hallazgos, no uno.
>
> Este plan cubre **A y C**. **B** va en una segunda entrega, con su propio plan.

---

## Los tres hallazgos

| # | Qué | Severidad | En este plan |
| :--- | :--- | :--- | :--- |
| **A** | Confirmar el cobro de un pedido central **no existe**: la columna se lee en 6 sitios y no la escribe nadie | 🔴 | ✅ Sí |
| **B** | La comisión sólo se vuelve cobrable si el comerciante pulsa un botón que le genera deuda, y nada muestra el atraso | 🟠 | ❌ Segunda entrega |
| **C** | Resolver una disputa **no anula ninguna comisión**: el `where` usa la clave equivocada | 🔴 | ✅ Sí |

---

## A. Confirmar el cobro de un pedido central no existe

### La evidencia

Un pedido central nace así, en `CreateUnifiedCentralOrderUseCase:119`:

```php
'payment_status' => 'pending',
```

Se buscaron **todas** las asignaciones a `payment_status` del repositorio. A partir de ese
`create`, sólo lo escriben dos líneas, las dos en `ResolveCentralOrderDisputeUseCase`:
`'refunded'` y `'cancelled'`.

**Ningún camino del código lo pone en `'paid'`.** No hay endpoint, no hay acción de admin
—la única ruta admin sobre pedidos centrales es `resolve-dispute`—, no hay webhook. Los
únicos `'paid'` del repositorio están en `CentralCustomerDemoSeeder` y en fixtures de tests,
escritos a mano.

### Lo que rompe

| Dónde | Qué pasa hoy |
| :--- | :--- |
| `GetAdminDashboardMetricsUseCase:46` | El GMV es `where('payment_status','paid')->sum()` → **siempre 0** |
| `ListCentralOrdersForAdminUseCase:82,87` | Igual, y el contador de pedidos pagados también |
| `GetCustomerOrderTrackingUseCase:59` | El cliente ve *«En espera de conciliación del pago»* **para siempre** |
| `DispatchCentralOrderToTenantsUseCase:237` | La fila de `payments` nace `pending` y nunca pasa a `completed` |
| `DispatchCentralOrderToTenantsUseCase:428` | La comisión nace siempre en `awaiting_payment` |

### Y lo que lo convierte en 🔴

`GenerateTenantCommissionSettlementUseCase` acepta dos tipos de liquidación:

- `collection` — la tienda le paga a la plataforma su comisión (ventas del escaparate).
- `payout` — **la plataforma le paga a la tienda** la venta menos comisión (ventas centrales).

Las dos leen exactamente lo mismo:

```php
->where('status', 'pending')->whereNull('settlement_id')
```

Así que una comisión atascada en `awaiting_payment` no bloquea sólo el cobro de la
plataforma: **bloquea el pago a la tienda**. En el marketplace central el comprador le paga a
la plataforma, y hoy no existe forma de que ese dinero llegue nunca al comerciante.

Deja de ser un problema de métricas.

---

## C. Resolver una disputa no anula ninguna comisión

```php
// ResolveCentralOrderDisputeUseCase:52
PlatformCommission::where('order_id', $order->id)->update(['status' => 'cancelled']);
```

`$order->id` es el id del pedido **central**. Las comisiones se guardan con
`order_id = tenantOrderId`, y el central va en su propia columna `central_order_id` —
exactamente lo que separó el «Hallazgo Auditoría #1», cuyo comentario sigue escrito en
`CalculateAndRecordOrderCommissionUseCase`:

> *«`$orderId` es el pedido de la TIENDA. El del pedido central va aparte, porque son
> identificadores de bases distintas y meterlos en la misma columna era lo que dejaba
> `$centralOrder->commissions` siempre vacío.»*

Ese `where` no casa con ninguna fila. **Se reembolsa al comprador y la plataforma se queda la
comisión.** Es el fallo que D2 cerró para los pedidos de tienda, vivo en el camino central.

Y va dentro de un `catch (\Throwable)` con el cuerpo `// Silently handle`, así que aunque
fallara tampoco avisaría.

---

## Diseño

### A1 — `ConfirmCentralOrderPaymentUseCase`

Nuevo caso de uso en `src/Admin/Application/UseCase/`, junto a `ResolveCentralOrderDisputeUseCase`.

**Perímetro:** `auth` + `super_admin`, en `src/Admin/Infrastructure/Http/Routes/web.php` al
lado de `resolve-dispute`. El dinero de un pedido central entra en la cuenta de la
plataforma, y esos datos de cobro ya viven bajo `super_admin` por N33.

**Pasos, en orden:**

1. **Guarda.** El pedido central debe estar en `payment_status = 'pending'`. Ya pagado,
   reembolsado o cancelado se rechaza con el motivo. Nada de aceptar y no hacer nada: es el
   fallo de PR2, OR1 y SH1, tres veces ya en este proyecto.
2. `payment_status = 'paid'`, y quién/cuándo en `metadata`, con el mismo patrón que
   `dispute_resolution` para que los dos rastros se lean igual.
3. Por cada `CentralOrderDispatch` con `status = 'dispatched'` y `tenant_order_id` no nulo,
   entrando en la tenancy de esa tienda **con el mismo `try/finally` que usa el despacho**
   (`tenancy()->initialize()` … `finally { if (tenancy()->initialized) tenancy()->end(); }`):
   - marcar el pedido de tienda como pagado **a través de la entidad**,
     `Order::markPaymentPaid()`, respetando las guardas de OR1;
   - poner su fila de `payments` en `completed` con `paid_at`.
4. Promover las comisiones con `ActivateOrderCommissionUseCase`, que es el único punto que
   las hace cobrables (N15). Con eso se desbloquea el `payout` a la tienda.

**Ojo con el paso 4 y la tenancy:** `PlatformCommission` es un modelo de la base central.
La promoción tiene que ejecutarse **fuera** del `tenancy()->initialize()`, o la conexión
apunta a la base equivocada.

### A2 — Los dos órdenes de llegada convergen

Si el pago se confirma **antes** de que corra `DispatchCentralOrderJob`, el paso 3 no tiene
nada que hacer, y no hace falta código extra: el despacho ya lee

```php
paid: ($centralOrder->payment_status ?? 'pending') === 'paid',   // :428
'status' => $centralOrder->payment_status === 'paid' ? 'completed' : 'pending',   // :237
```

y crea la comisión directamente cobrable y la fila de `payments` en `completed`. El plan sólo
tiene que cubrir bien el caso «ya despachado». **Esto se fija con un test**, no se asume.

### A3 — Frontend

Acción «Confirmar pago» en el modal de `AdminGlobalOrdersPage.tsx`, visible sólo con
`payment_status === 'pending'`.

**Debe mostrar `payment_details`** —la referencia o el hash que envió el comprador— junto al
botón. El admin concilia esa referencia contra su banco **antes** de pulsar; sin ese dato a
la vista, el botón es una casilla que se marca a ciegas.

Servicio centralizado en `resources/js/Services/`, tipado, según `reglas.md` §1.

### C1 — La disputa usa la reversión que ya existe

Sustituir el `update` a pelo por `ReverseOrderCommissionUseCase`, que ya hace la reversión
bien —contempla `awaiting_payment`, `pending` y `collected`, y marca para ajuste manual lo ya
liquidado— y se llama con el `order_id` de la tienda, que es lo que las comisiones guardan.

Se recorren los `CentralOrderDispatch` del pedido, igual que en A1 paso 3.

Y se quita el `catch (\Throwable) { // Silently handle }`: si no se puede revertir una
comisión de un pedido reembolsado, eso tiene que verse.

---

## Tests

Feature, sobre el harness de marketplace central que ya existe:

1. Confirmar el cobro de un pedido central despachado → central `paid`, pedido de tienda
   `paid`, comisión en `pending`, fila de `payments` en `completed`.
2. **Y con eso se puede generar el `payout`** — es la consecuencia que hace 🔴 al hallazgo, así
   que se comprueba, no se da por hecha.
3. Confirmar **antes** del despacho, y despachar después: mismo estado final que en 1. Fija
   que los dos órdenes convergen.
4. Guardas: ya pagado, reembolsado, y sin `super_admin`.
5. **C:** reembolsar por disputa un pedido central despachado deja la comisión en `refunded`
   y no en `pending`. Este test falla hoy.

---

## Lo que este plan NO hace

- **B**, la visibilidad del atraso de comisiones en `awaiting_payment` para las ventas del
  escaparate. Segunda entrega.
- Conciliación automática contra la tabla `payments`. La confirmación es un acto humano y
  deliberado, y así se queda hasta que alguien pida lo contrario.
- Tocar la regla de N15. Sigue intacta: la comisión nace devengada y no cobrable. Lo que se
  añade es **quién puede confirmar el cobro** cuando el dinero entra en la plataforma.
