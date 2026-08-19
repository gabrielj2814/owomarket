# Plan: Conciliación, Liquidación y Facturación de Comisiones y Planes de Suscripción

## 📌 Objetivo
Asegurar el flujo financiero completo y la monetización efectiva de OwOMarket:
1. **Cobro de Comisiones por Ventas Propias (`collection`)**: Conciliar las comisiones que las tiendas deben a la plataforma por ventas en sus propias tiendas.
2. **Desembolso por Ventas en Marketplace Central (`payout`)**: Liquidar a las tiendas el dinero recaudado centralmente, descontando automáticamente la comisión de la plataforma.
3. **Métricas Financieras Centrales**: Proveer al SuperAdmin la visión de ingresos por comisiones, suscripciones activas y comisiones pendientes por cobrar o desembolsar.

---

## 🗄️ 1. Base de Datos Central (`connection = 'central'`)

- **`commission_settlements`**:
  - `id` (uuid)
  - `settlement_number`: `SET-YYYYMM-XXXX`
  - `tenant_id`: string (id del inquilino)
  - `type`: `collection` | `payout`
  - `total_orders_count`: int
  - `gross_sales_amount`: decimal
  - `commission_amount`: decimal
  - `net_amount`: decimal
  - `currency`: USD
  - `status`: `pending` | `settled` | `cancelled`
  - `payment_method`, `payment_reference`, `settled_at`, `notes`, `metadata`
- **`platform_commissions`**:
  - `settlement_id`: nullable uuid (vínculo con la liquidación)

---

## 🏛️ 2. Arquitectura Hexagonal DDD (`src/Monetization/`)

- `GenerateTenantCommissionSettlementUseCase`: Agrupa las comisiones pendientes de un tenant y genera el estado de cuenta / liquidación formal.
- `ConfirmAndSettleCommissionUseCase`: Valida el pago (Pago Móvil / Binance Pay) y marca las comisiones y la liquidación como liquidadas (`settled` / `collected`).
- `GetSuperAdminMonetizationMetricsUseCase`: Reporte consolidado de comisiones generadas, recaudadas, pendientes, suscripciones y facturación.
- `GetTenantSettlementHistoryUseCase`: Historial de liquidaciones para el panel del inquilino.

---

## 🧪 3. Pruebas y QA

- `CommissionSettlementAndBillingTest.php`:
  - Generación de liquidación de comisiones pendientes.
  - Validación de conciliación y cambio de estado a `collected` / `settled`.
  - Reporte de métricas consolidadas de monetización.
- `php artisan test` (100% pasando) y `npm run types` (0 errores).
