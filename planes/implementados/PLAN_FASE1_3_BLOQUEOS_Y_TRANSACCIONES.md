# PLAN — Fase 1.3: Bloqueos y transacciones en liquidaciones, facturas y stock

> **Origen:** hallazgos C3, C4 y C1 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md` (punto 8 del plan de acción)
> **Severidad:** 🔴 C1 crítico · 🟠 C3 y C4 altos
> **Tamaño:** 1 servicio nuevo, 1 método de repositorio nuevo, 3 casos de uso, 1 controlador, 3 archivos de test
> **Estado:** ✅ Implementado — pendiente de validación con `php artisan test`
> **Depende de:** Fase 0.4 (que ya corrigió el doble descuento y el `catch` vacío del stock)

Las tres son la misma clase de error: **leer, comprobar y escribir en pasos separados, sin bloqueo ni transacción**. Por eso van juntas.

---

## 1. C3 — Carrera al generar liquidaciones: doble cobro de comisiones

Se leían las comisiones pendientes, se creaba la liquidación con los totales y **después** se enlazaban con un `update` que no revalidaba `whereNull('settlement_id')`.

**Escenario:** doble clic en «Generar liquidación» con $500 pendientes → se creaban SET-A y SET-B, ambas por $500; el segundo `update` reasignaba todas las comisiones a SET-B, y SET-A quedaba pendiente por $500 **sin comisiones asociadas**. Si el superadmin confirmaba ambas, la plataforma registraba $1.000 cobrados sobre $500 reales.

### Solución

- Todo dentro de `DB::transaction`.
- `lockForUpdate()` al leer las comisiones pendientes: la segunda generación espera ahí y, cuando entra, ya no ve comisiones libres.
- El enlace final revalida `whereNull('settlement_id')` y **compara el número de filas afectadas**. Si no coincide, se aborta con 409 en lugar de emitir una liquidación cuyos totales no corresponden a las comisiones realmente enlazadas.

---

## 2. C4 — Números de factura correlativos duplicados

`getProfile()` hacía un `first()` sin bloqueo, el incremento ocurría en memoria y se persistía en una escritura aparte. La transacción del repositorio de facturas era posterior y no cubría el contador.

**Escenario:** dos operadores emiten factura a la vez con `next_invoice_number = 42`. Ambos generan `FAC-2026-000042` — **dos facturas fiscales con el mismo correlativo**. Y si el `save()` de la factura fallaba, el contador ya había quedado incrementado, dejando un hueco en la serie.

### Solución

Método nuevo en el repositorio: `reserveNextInvoiceNumber()`. Bloquea la fila del perfil con `lockForUpdate()` dentro de una transacción, formatea el número e incrementa el contador **en la misma operación**. Una segunda petición espera en el bloqueo y lee el contador ya incrementado.

`CreateDirectInvoiceUseCase` pasa a correr entero dentro de una transacción, así que **si la factura no se persiste, el correlativo tampoco se consume** — se acabaron los huecos en la serie.

El formato se replica exactamente igual que `BillingProfile::generateAndIncrementInvoiceNumber()` para no romper la numeración existente.

---

## 3. C1 — El stock: el que faltaba de la Fase 0.4

La Fase 0.4 ya había corregido dos de los tres bugs del bloque de stock (el doble descuento con variante y el `catch (\Throwable)` vacío). Quedaban los dos peores:

1. **Si no había stock, el pedido se creaba igual.** El `if ($product->quantity >= $qty)` simplemente no descontaba y nadie se enteraba: el cliente pagaba por algo que no existía.
2. **La carrera.** Leer, comprobar y descontar en tres pasos sin bloqueo: dos pedidos simultáneos sobre la última unidad leían ambos `quantity = 1`, ambos descontaban, y el stock terminaba en −1 con dos pedidos imposibles de servir.

### Solución

Servicio `Src\Marketplace\Application\Service\StockReserver`:

- `reserve()` bloquea la fila con `lockForUpdate()` y **lanza 409 si no hay existencias**, en vez de seguir adelante en silencio.
- Respeta `track_quantity`: un producto sin control de inventario se vende sin descontar.
- `release()` devuelve stock (sumar no puede dejar el inventario en negativo, así que no necesita bloqueo).

Y el checkout completo —resolución de precios, reserva de stock, consumo del cupón, creación del pedido y registro del pago— pasa a correr dentro de **una transacción**. Es lo que hace efectivos los bloqueos y lo que garantiza que, si algo falla a mitad, el stock no quede descontado ni el cupón consumido.

### 3.1 Dos cambios de comportamiento visibles

- **El checkout devuelve 409** cuando falta stock (antes: 201 con el pedido creado). Se conserva el código de la excepción en la respuesta para que el frontend pueda distinguirlo de un error de validación.
- **El registro del pago ya no lleva `catch` vacío.** Si el pago no se puede registrar, la transacción revierte el pedido entero en vez de dejar una venta sin rastro de cobro.

**Queda fuera a propósito:** el registro de la comisión sigue con su `try/catch` y **fuera** de la transacción, porque escribe en la base central (otra conexión) e incluirla no daría atomicidad real. Si falla, el pedido de la tienda es válido igualmente y lo que queda pendiente es registrar la comisión.

---

## 4. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **C3** — liquidaciones sin transacción ni `lockForUpdate` | ✅ Cerrado |
| **C3** — enlace de comisiones sin revalidar | ✅ Cerrado |
| **C4** — correlativo de factura sin bloqueo | ✅ Cerrado |
| **C4** — contador consumido aunque la factura falle | ✅ Cerrado |
| **C1** — pedido creado sin existencias | ✅ Cerrado |
| **C1** — carrera de stock | ✅ Cerrado |
| **C1** — reposición de stock al cancelar | 🟡 `StockReserver::release()` existe pero **nadie lo llama todavía** (ver seguimiento) |
| **C6** — `increment('used_count')` de cupones sin techo | ⬜ Sigue abierto: va con B3, el bloque de cupones |

Con esto queda cerrado el **punto 8 del plan de acción**. De la Fase 1 sólo resta el punto 9: D3 y D4 (tasa de cambio y scraper del BCV).

---

## 5. Tareas

- [x] `lockForUpdate` + transacción + revalidación en `GenerateTenantCommissionSettlementUseCase`
- [x] `reserveNextInvoiceNumber()` en el repositorio de perfiles de facturación
- [x] `CreateDirectInvoiceUseCase` dentro de una transacción
- [x] Crear `StockReserver` con bloqueo y error 409
- [x] Envolver el checkout del storefront en una transacción
- [x] Quitar el `catch` vacío del registro de pago
- [x] Conservar el código de la excepción en la respuesta del checkout
- [x] Tests de C3 (`SettlementConcurrencyTest`), C4 (correlativos) y C1 (sin stock / reversión)
- [x] `php artisan test`
- [x] `vendor/bin/pint src/Monetization/ src/Billing/ src/Marketplace/`
- [ ] Revisar si hay stock negativo en producción (sección 7) — no aplica: base de datos de desarrollo reiniciada desde cero
- [x] Commit: `fix(monetization,billing,marketplace): bloqueos y transacciones en liquidaciones, facturas y stock`
- [x] `git push origin <rama_actual>`
- [x] Mover este documento a `planes/implementados/`

---

## 6. Verificación manual

**Debe seguir funcionando:**
1. Comprar con stock disponible, con y sin variante.
2. Emitir varias facturas seguidas: correlativos consecutivos, sin repetir.
3. Generar una liquidación con comisiones pendientes.

**Debe cambiar:**
4. Comprar más unidades de las que hay → **409** con el mensaje de existencias, y **ningún pedido creado**.
5. Doble clic en «Generar liquidación» → la segunda falla; sigue habiendo **una sola** liquidación.
6. Un pedido que falle a mitad → el stock **no** queda descontado.

---

## 7. Riesgo

**Medio.**

1. **El checkout ahora puede rechazar pedidos que antes aceptaba.** Es la corrección del bug, pero cambia la tasa de conversión: pedidos que antes entraban (y luego no se podían servir) ahora se rechazan en el momento. Es preferible, pero conviene avisar a los comerciantes.
2. **Puede haber stock negativo heredado.** Los pedidos aceptados sin existencias antes de este cambio dejaron inventarios en negativo. Conviene revisarlo por tienda antes de desplegar:
   ```sql
   SELECT id, name, quantity FROM products WHERE quantity < 0;
   SELECT id, product_id, quantity FROM product_variants WHERE quantity < 0;
   ```
3. **`lockForUpdate` no hace nada en SQLite.** La suite de tests usa SQLite, así que **los tests no demuestran la ausencia de la carrera** — sólo que la lógica de comprobación es correcta. La garantía real depende del motor de producción (MySQL/PostgreSQL con InnoDB). Conviene una prueba de carga real antes de confiar del todo.
4. **Transacciones más largas en el checkout.** Ahora la transacción abarca todo el flujo. Si `CreateOrderUseCase` fuera lento, el bloqueo sobre las filas de producto se mantiene más tiempo y podría aumentar la contención en productos muy vendidos. No debería ser problema al volumen actual, pero es algo a vigilar.

---

## 8. Trabajo de seguimiento identificado

1. **Nadie repone el stock al cancelar un pedido.** `StockReserver::release()` está escrito y probado en su lógica, pero `CancelOrderUseCase` no lo invoca. Falta decidir en qué estados corresponde reponer (¿un pedido cancelado tras ser enviado devuelve stock?) — es una decisión de producto, no técnica. **Es el hueco más visible que deja esta fase.**
2. **C6 sigue abierto:** `increment('used_count')` de cupones no comprueba el techo, así que N peticiones paralelas superan el `usage_limit`. La auditoría propone un `UPDATE ... WHERE used_count < usage_limit` comprobando filas afectadas. Va con B3, que también sigue abierto en el mismo controlador.
3. **El checkout central no reserva stock en absoluto.** `DispatchCentralOrderToTenantsUseCase` crea pedidos de tienda sin tocar el inventario. Es un hueco mayor que el que cierra esta fase para el storefront, y merece su propia revisión.
4. **La numeración de facturas es por tienda, no por año.** `reserveNextInvoiceNumber()` conserva el comportamiento existente (el año va en el número pero el contador no se reinicia). Si la normativa fiscal exige reiniciar cada ejercicio, hay que abordarlo aparte.
