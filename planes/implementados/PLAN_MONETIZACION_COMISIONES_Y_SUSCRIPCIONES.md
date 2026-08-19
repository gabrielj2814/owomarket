# Plan: Motor de Monetización con Comisiones 100% Configurables y Planes de Suscripción

## 📌 Niveles de Configuración de Comisiones (Jerarquía Inteligente)

Las comisiones de la plataforma son **100% configurables** y se calculan siguiendo una jerarquía clara:

1. **Nivel 1 (Tasa Personalizada por Tienda / Inquilino)**:
   - El administrador de OwOMarket puede configurar un `custom_commission_rate` individual para un inquilino específico (ej: `2.0%` por acuerdo especial).
2. **Nivel 2 (Tasa del Plan de Suscripción Activo)**:
   - Si no tiene tasa individual, se aplica la tasa asignada a su plan de suscripción actual (ej: Plan Gratuito: `8.0%`, Plan Pro: `3.5%`, Plan Enterprise: `1.0%`). Las tasas de cada plan también son editables por el administrador.
3. **Nivel 3 (Tasa Global por Defecto de la Plataforma)**:
   - Tasa base editable en `central_settings` o configuración global (por defecto `8.0%`).

---

## 🗄️ 1. Base de Datos Central (`connection = 'central'`)

- **`subscription_plans`**:
  - `id` (uuid)
  - `name`: Free / Emprendedor Pro / Enterprise
  - `slug`: `free`, `pro`, `enterprise`
  - `price_monthly`: 0.00 / 19.99 / 49.99
  - `price_yearly`: 0.00 / 199.99 / 499.99
  - `commission_rate`: Decimal configurable (ej. 8.00, 3.50, 1.00)
  - `features`: JSON con módulos permitidos.
  - `max_products`: Límite de catálogo.
  - `is_active`: boolean.
- **`tenant_subscriptions`**:
  - `id` (uuid), `tenant_id` (string), `plan_id` (uuid), `billing_cycle`, `status`, `starts_at`, `ends_at`, `renews_at`.
- **`platform_commissions`**:
  - `id` (uuid), `tenant_id` (string), `order_id` (string), `order_number` (string), `order_total` (decimal), `commission_rate` (tasa aplicada), `commission_amount` (monto calculado), `currency` (USD), `status` (pending / collected / waived), `created_at`.

---

## 🏛️ 2. Arquitectura Hexagonal DDD (`src/Monetization/`)

- `CalculateAndRecordOrderCommissionUseCase`: Resuelve la tasa aplicable por jerarquía (Tenant Custom -> Plan -> Global) y crea el registro inmutable de comisión.
- `GetTenantMonetizationSummaryUseCase`: Provee métricas del plan activo, porcentaje vigente, ventas totales y comisiones acumuladas.
- `UpdateTenantCustomCommissionUseCase`: Permite al SuperAdmin asignar o modificar la comisión de una tienda en particular.
- `ListSubscriptionPlansUseCase` y `SubscribeTenantToPlanUseCase`.

---

## 🛒 3. Integración en Flujo de Compra

- `CreateStorefrontOrderPOSTController.php` ejecuta automáticamente `CalculateAndRecordOrderCommissionUseCase` al procesar una venta.

---

## 🧪 4. Pruebas y QA

- `TenantMonetizationAndCommissionTest.php`: Tests de cálculo a los 3 niveles de jerarquía, suscripción a planes y resumen de métricas.
- `php artisan test` (100% pasando) y `npm run types` (0 errores).
- Commit y push a origin.
