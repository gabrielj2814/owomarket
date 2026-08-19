# Plan: Carrito Multi-Tienda y Checkout Unificado en Dominio Central

## 📌 Objetivo
Permitir que un comprador en el dominio central (`owomarket.com` / `owomarket.local`):
1. Arme su carrito agregando productos pertenecientes a **diferentes tiendas inquilinas**.
2. Complete un **Checkout Unificado** con una **Factura / Pedido Único** (`central_orders`), realizando un solo pago (Pago Móvil o Binance Pay).
3. El sistema **desglose y enrute automáticamente** la orden a cada una de las tiendas inquilinas en sus respectivas bases de datos (`orders`), asociando el cliente con SSO y generando el registro de comisión por venta para la plataforma.

---

## 🗄️ 1. Base de Datos Central (`connection = 'central'`)

- **`central_orders`**:
  - `id` (uuid)
  - `order_number`: `OWO-YYYYMMDD-XXXX`
  - `customer_id`: uuid de `central_customers` (o null para invitado)
  - `customer_name`, `customer_email`, `customer_phone`, `customer_document_id`
  - `shipping_address`: json
  - `payment_method`: `pago_movil`, `binance_pay`, etc.
  - `payment_details`: json con comprobante (banco emisor, teléfono, ref o hash)
  - `subtotal`, `shipping_amount`, `discount_amount`, `total`
  - `status`: `pending`, `paid`, `processing`, `completed`, `cancelled`
  - `payment_status`: `pending`, `paid`, `failed`
  - `metadata`: json
- **`central_order_items`**:
  - `id` (uuid)
  - `central_order_id` (uuid)
  - `tenant_id` (string)
  - `product_id`, `product_name`, `sku`, `price`, `quantity`, `total`
  - `tenant_order_id`: string (ID de la orden individual en la base del inquilino)
  - `commission_rate`, `commission_amount`

---

## 🏛️ 2. Arquitectura Hexagonal DDD (`src/CentralMarketplace/`)

- `CreateUnifiedCentralOrderUseCase`:
  - Recibe el payload del checkout unificado.
  - Genera el pedido maestro `central_orders` y sus ítems en `central_order_items`.
  - Dispara `DispatchCentralOrderToTenantsUseCase`.
- `DispatchCentralOrderToTenantsUseCase`:
  - Agrupa los ítems por tienda (`tenant_id`).
  - Para cada tienda:
    - Entra al contexto del inquilino (`tenancy()->initialize($tenant)`).
    - Crea o sincroniza el cliente con su `central_uuid`.
    - Genera la orden en el tenant con sus ítems específicos y metadata `{ source: 'central_marketplace', central_order_id: ... }`.
    - Inserta el pago en la tabla `payments` del tenant.
    - Actualiza el `tenant_order_id` en `central_order_items`.
    - Calcula y registra la comisión de la plataforma mediante `CalculateAndRecordOrderCommissionUseCase`.
    - Sale del contexto (`tenancy()->end()`).
- `GetCentralOrderConfirmationUseCase`:
  - Provee el detalle consolidado de la factura única para la pantalla de confirmación.

---

## 🧪 3. Pruebas y QA

- `MultiStoreCentralCheckoutTest.php`:
  - Compra de productos de 2 tiendas distintas en una sola orden central.
  - Validación del desglose en 2 órdenes independientes en los tenants.
  - Validación del registro de comisiones para cada tienda.
  - Validación de la factura consolidada única.
- `php artisan test` (100% pasando) y `npm run types` (0 errores).
