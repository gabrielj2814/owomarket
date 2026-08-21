# 📐 Plan de Reubicación de Modelos Eloquent a sus Bounded Contexts (DDD / Clean Architecture) [IMPLEMENTADO ✅]

## 🎯 1. Objetivo y Diagnóstico
Actualmente todos los **34 modelos de dominio** han sido reubicados y organizados dentro de sus respectivos **Bounded Contexts** de OwOMarket en `src/<ContextName>/Infrastructure/Eloquent/Models/<ModelName>.php`.

---

## 🗺️ 2. Matriz de Reubicación por Bounded Contexts

| # | Modelo | Bounded Context (`src/`) | Namespace Canónico en `src/` | Estado |
|---|:---|:---|:---|:---|
| 1 | `CentralAuditLog` | `Admin` | `Src\Admin\Infrastructure\Eloquent\Models\CentralAuditLog` | ✅ Migrado |
| 2 | `CentralHomeBanner` | `Admin` | `Src\Admin\Infrastructure\Eloquent\Models\CentralHomeBanner` | ✅ Migrado |
| 3 | `CentralBrand` | `Brand` | `Src\Brand\Infrastructure\Eloquent\Models\CentralBrand` | ✅ Migrado |
| 4 | `CentralCategory` | `Category` | `Src\Category\Infrastructure\Eloquent\Models\CentralCategory` | ✅ Migrado |
| 5 | `CentralProduct` | `Product` | `Src\Product\Infrastructure\Eloquent\Models\CentralProduct` | ✅ Migrado |
| 6 | `CentralCustomer` | `CentralCustomer` | `Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer` | ✅ Migrado |
| 7 | `CentralCustomerAddress` | `CentralCustomer` | `Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerAddress` | ✅ Migrado |
| 8 | `CentralCustomerPasswordReset` | `CentralCustomer` | `Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerPasswordReset` | ✅ Migrado |
| 9 | `CentralCustomerSsoToken` | `CentralCustomer` | `Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerSsoToken` | ✅ Migrado |
| 10 | `CentralCustomerWishlist` | `CentralCustomer` | `Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerWishlist` | ✅ Migrado |
| 11 | `CustomerReturnRequest` | `CentralCustomer` | `Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest` | ✅ Migrado |
| 12 | `Cart` | `CentralCustomer` | `Src\CentralCustomer\Infrastructure\Eloquent\Models\Cart` | ✅ Migrado |
| 13 | `CentralOrder` | `Order` | `Src\Order\Infrastructure\Eloquent\Models\CentralOrder` | ✅ Migrado |
| 14 | `CentralOrderItem` | `Order` | `Src\Order\Infrastructure\Eloquent\Models\CentralOrderItem` | ✅ Migrado |
| 15 | `Payment` | `Payment` | `Src\Payment\Infrastructure\Eloquent\Models\Payment` | ✅ Migrado |
| 16 | `Wishlist` | `Customer` | `Src\Customer\Infrastructure\Eloquent\Models\Wishlist` | ✅ Migrado |
| 17 | `WishlistItem` | `Customer` | `Src\Customer\Infrastructure\Eloquent\Models\WishlistItem` | ✅ Migrado |
| 18 | `SupportTicket` | `SupportTicket` | `Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicket` | ✅ Migrado |
| 19 | `SupportTicketMessage` | `SupportTicket` | `Src\SupportTicket\Infrastructure\Eloquent\Models\SupportTicketMessage` | ✅ Migrado |
| 20 | `CommissionSettlement` | `Monetization` | `Src\Monetization\Infrastructure\Eloquent\Models\CommissionSettlement` | ✅ Migrado |
| 21 | `PlatformCommission` | `Monetization` | `Src\Monetization\Infrastructure\Eloquent\Models\PlatformCommission` | ✅ Migrado |
| 22 | `SubscriptionPlan` | `Monetization` | `Src\Monetization\Infrastructure\Eloquent\Models\SubscriptionPlan` | ✅ Migrado |
| 23 | `TenantSubscription` | `Monetization` | `Src\Monetization\Infrastructure\Eloquent\Models\TenantSubscription` | ✅ Migrado |
| 24 | `TenantOwnerSsoToken` | `Tenant` | `Src\Tenant\Infrastructure\Eloquent\Models\TenantOwnerSsoToken` | ✅ Migrado |
| 25 | `User` | `User` | `Src\User\Infrastructure\Eloquent\Models\User` | ✅ Unificado con Spatie RBAC y UUIDs |
| 26 | `AuthUser` | `Authentication` | `Src\Authentication\Infrastructure\Eloquent\Models\AuthUser` | ✅ Integrado |
| 27 | `Customer` | `Customer` | `Src\Customer\Infrastructure\Eloquent\Models\Customer` | ✅ Integrado |
| 28 | `Address` | `Customer` | `Src\Customer\Infrastructure\Eloquent\Models\Address` | ✅ Integrado |
| 29 | `Order` | `Order` | `Src\Order\Infrastructure\Eloquent\Models\Order` | ✅ Integrado |
| 30 | `OrderItem` | `Order` | `Src\Order\Infrastructure\Eloquent\Models\OrderItem` | ✅ Integrado |
| 31 | `Shipment` | `Shipment` | `Src\Shipment\Infrastructure\Eloquent\Models\Shipment` | ✅ Integrado |
| 32 | `Tenant` | `Tenant` | `Src\Tenant\Infrastructure\Eloquent\Models\Tenant` | ✅ Integrado |
| 33 | `TenantUser` | `Tenant` | `Src\Tenant\Infrastructure\Eloquent\Models\TenantUser` | ✅ Integrado |
| 34 | `TenantSetting` | `TenantSettings` | `Src\TenantSettings\Infrastructure\Eloquent\Models\TenantSetting` | ✅ Integrado |

---

## 🛠️ 3. Resultados de Verificación
- **Backend Tests**: 469/469 tests pasando (2,792 assertions) con 100% de éxito (`php artisan test`).
- **Frontend Types**: 0 errores de TypeScript (`npm run types`).
- **Frontend Unit Tests**: 16/16 tests pasando (`npm run test:unit`).
- **Bundle Production**: `npm run build` compilado con éxito.
