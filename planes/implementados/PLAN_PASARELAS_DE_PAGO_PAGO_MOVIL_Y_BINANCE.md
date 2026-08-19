# Plan: Pasarelas de Pago de Prueba (Pago Móvil y Binance Pay)

## 📌 Objetivo
Permitir a los compradores realizar pagos en tiendas tenant utilizando los dos métodos de pago más demandados para pruebas y producción:
1. **Pago Móvil Interbancario (VES)**: Con cálculo de monto en Bolívares según tasa referencial, instrucciones claras del receptor (Banco, Teléfono, RIF/Cédula) y captura de la referencia bancaria.
2. **Binance Pay / USDT (Cripto)**: Con Binance Pay ID receptor, botón interactivo para copiar, visualización de código QR y captura del Binance ID / Transaction Hash de la transferencia.

---

## 🏛️ 1. Arquitectura Hexagonal DDD (`src/Payment/`)

- `src/Payment/Infrastructure/Adapters/PagoMovilPaymentGateway.php`:
  - Implementa `PaymentGatewayInterface`.
  - Manejo de `charge()` registrando los datos bancarios y referencia en `gateway_response`.
- `src/Payment/Infrastructure/Adapters/BinancePayPaymentGateway.php`:
  - Implementa `PaymentGatewayInterface`.
  - Manejo de `charge()` registrando el Hash de Binance, Pay ID y moneda (USDT).
- `PaymentServiceProvider.php`:
  - Registro de los identificadores `pago_movil` y `binance_pay` en el contenedor y `PaymentGatewayFactory`.

---

## 🛒 2. Flujo de Checkout y Controladores

- `ViewCheckoutTenantGETController.php`:
  - Proveer opciones dinámicas con datos preconfigurados de Pago Móvil y Binance Pay a la vista de Checkout.
- `CreateStorefrontOrderPOSTController.php`:
  - Recibir `payment_details` en el payload JSON.
  - Crear el registro en `payments` asociado a la orden con `payment_gateway = 'pago_movil'` o `'binance_pay'`, `status = 'pending'`, montos y datos del comprobante.

---

## 🎨 3. Frontend Storefront Flowbite React

- `TenantCheckoutPage.tsx`:
  - Componentes de formulario condicionales cuando se selecciona Pago Móvil o Binance Pay:
    - **Pago Móvil**: Tarjeta informativa + inputs (Banco Emisor, Teléfono, Referencia de 6 a 8 dígitos).
    - **Binance Pay**: Tarjeta informativa con Pay ID, botón Copiar, QR + inputs (Binance Pay ID / Tx Hash).
  - Validación antes del submit para asegurar que el comprador complete los datos requeridos del comprobante.
- `TenantOrderConfirmationPage.tsx`:
  - Tarjeta de confirmación mostrando el método utilizado y el número de comprobante o referencia bancaria registrada.

---

## 🧪 4. Pruebas y Validación

- `StorefrontPaymentGatewaysTest.php`: Tests automatizados para validar que las órdenes con Pago Móvil y Binance Pay se creen con sus respectivos registros de pago.
- `php artisan test` (100% pasando) y `npm run types` (0 errores).
- Commit y push a origin.
