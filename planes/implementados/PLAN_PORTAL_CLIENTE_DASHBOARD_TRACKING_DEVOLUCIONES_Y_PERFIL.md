# Plan Maestro: Portal Integral del Cliente, Dashboard, Tracking, Facturas, Devoluciones y Perfil [IMPLEMENTADO AL 100%]

## 1. Contexto y Objetivos del Módulo

El objetivo de este plan fue proporcionar a los clientes y compradores de **OwOMarket** una experiencia completa, segura y unificada tanto en el **Dominio Central** como en las **Tiendas de los Inquilinos (Tenants)**, impulsada por el sistema de autenticación única **OwO Pass (SSO)**.

### Capacidades Implementadas:
1. **Recuperación de Cuenta y Contraseña:** Flujo seguro mediante código PIN de 6 dígitos enviado al correo electrónico del comprador (`/auth/forgot-password` y `/auth/reset-password`).
2. **Gestión de Perfil y Libreta de Direcciones:** Edición de datos personales (nombre, teléfono, cédula/RIF, avatar), cambio de contraseña y administración de múltiples direcciones de entrega con estado predeterminado.
3. **Portal / Dashboard del Cliente (`/account/*`):** Panel interactivo con métricas de compras, pedidos recientes, accesos rápidos y estado del OwO Pass.
4. **Historial de Pedidos y Tracking en Vivo:** Consulta de compras multi-tienda y de tienda individual, con timeline interactivo de 5 pasos del envío (Courier, Guía, enlace de rastreo en vivo y estado) y botón *"Volver a Comprar"* (Reorder 1-clic).
5. **Historial y Descarga de Facturas PDF:** Visualización de comprobantes fiscales emitidos con montos duales (USD y Bolívares a tasa oficial BCV) y descarga inmediata de PDFs oficiales con DomPDF.
6. **Solicitud de Devolución / Reembolso (RMA):** Flujo para que el cliente solicite garantías o devoluciones en pedidos entregados con motivo y descripción.
7. **Mis Cupones y Promociones:** Visualización y copiado en 1 clic de cupones de descuento activos (OwO Pass, Envíos Gratis, Binance Pay).
8. **Calificaciones y Reseñas de Compras:** Valoración con estrellas (1 a 5), título y comentarios de productos comprados.
9. **Lista de Deseos / Favoritos (Wishlist):** Guardado de productos favoritos con precios en tiempo real (USD y Bs. BCV) y traslado directo al carrito.
10. **Navegación Unificada en Header:** Dropdown de usuario en `CentralNavbar.tsx` y `StorefrontNavbar.tsx` con avatar, nombre, accesos directos al panel y cierre de sesión.

---

## 2. Fases Implementadas

### [X] Fase 1: Backend Central — Recuperación de Contraseña, Seguridad, Perfil y Direcciones
- **Migración Central:** `database/migrations/2026_08_19_000009_create_central_customer_password_resets_table.php`.
- **Modelo Eloquent:** `app/Models/CentralCustomerPasswordReset.php`.
- **Casos de Uso:**
  - `SendCentralCustomerPasswordResetPinUseCase.php`
  - `ResetCentralCustomerPasswordWithPinUseCase.php`
  - `UpdateCentralCustomerProfileUseCase.php`
  - `UpdateCentralCustomerAddressUseCase.php`
  - `DeleteCentralCustomerAddressUseCase.php`
  - `SetDefaultCentralCustomerAddressUseCase.php`
- **Controladores HTTP y Rutas:**
  - `POST /api/central/customer/forgot-password`
  - `POST /api/central/customer/reset-password`
  - `PUT /api/central/customer/profile/{id}`
  - `PUT /api/central/customer/profile/{id}/address/{address_id}`
  - `DELETE /api/central/customer/profile/{id}/address/{address_id}`
  - `PATCH /api/central/customer/profile/{id}/address/{address_id}/default`

### [X] Fase 2: Backend — Consultas de Pedidos del Cliente, Tracking en Vivo y Facturas PDF
- **Casos de Uso:**
  - `ListCustomerOrdersUseCase.php`
  - `GetCustomerOrderDetailUseCase.php`
  - `GetCustomerOrderTrackingUseCase.php`
  - `ListCustomerInvoicesUseCase.php`
  - `DownloadCustomerInvoicePdfUseCase.php`
- **Controladores HTTP y Rutas:**
  - `GET /api/central/customer/orders`
  - `GET /api/central/customer/orders/{id}`
  - `GET /api/central/customer/orders/{id}/tracking`
  - `GET /api/central/customer/invoices`
  - `GET /api/central/customer/invoices/{id}/pdf`

### [X] Fase 3: Backend — Devoluciones (RMA), Cupones Disponibles, Reseñas y Favoritos (Wishlist)
- **Migraciones:**
  - `database/migrations/2026_08_19_000010_create_customer_return_requests_table.php`
  - `database/migrations/2026_08_19_000011_create_central_customer_wishlists_table.php`
- **Modelos Eloquent:**
  - `app/Models/CustomerReturnRequest.php`
  - `app/Models/CentralCustomerWishlist.php`
- **Casos de Uso:**
  - `CreateCustomerReturnRequestUseCase.php`
  - `ListCustomerReturnRequestsUseCase.php`
  - `ListCustomerAvailableCouponsUseCase.php`
  - `ListCustomerPurchasedProductsForReviewUseCase.php`
  - `SubmitCustomerProductReviewUseCase.php`
  - `ToggleCustomerWishlistProductUseCase.php`
  - `ListCustomerWishlistUseCase.php`
- **Controladores HTTP y Rutas:**
  - `POST /api/central/customer/returns`
  - `GET /api/central/customer/returns`
  - `GET /api/central/customer/coupons`
  - `GET /api/central/customer/reviews/pending`
  - `POST /api/central/customer/reviews`
  - `POST /api/central/customer/wishlist/toggle`
  - `GET /api/central/customer/wishlist`

### [X] Fase 4: Frontend — Layout del Portal del Cliente, Dashboard Resumen, Perfil y Direcciones
- **Servicio API:** `resources/js/Services/CustomerPortalServices.ts`.
- **Layout del Portal:** `resources/js/components/layouts/CustomerAccountLayout.tsx`.
- **Vistas:**
  - `resources/js/pages/customer/CustomerDashboardPage.tsx` (`/account/dashboard`)
  - `resources/js/pages/customer/CustomerProfilePage.tsx` (`/account/profile`)
  - `resources/js/pages/customer/CustomerAddressesPage.tsx` (`/account/addresses`)

### [X] Fase 5: Frontend — Pedidos con Tracking en Vivo, Reordenar 1-Clic y Facturas PDF
- **Componente Timeline:** `resources/js/components/ui/storefront/OrderTrackingTimeline.tsx`.
- **Vistas:**
  - `resources/js/pages/customer/CustomerOrdersPage.tsx` (`/account/orders`)
  - `resources/js/pages/customer/CustomerOrderDetailPage.tsx` (`/account/orders/{id}`)
  - `resources/js/pages/customer/CustomerInvoicesPage.tsx` (`/account/invoices`)

### [X] Fase 6: Frontend — Devoluciones (RMA), Cupones, Reseñas, Wishlist, Recuperación de Contraseña y Header Dropdown
- **Vistas:**
  - `resources/js/pages/customer/CustomerReturnsPage.tsx` (`/account/returns`)
  - `resources/js/pages/customer/CustomerCouponsPage.tsx` (`/account/coupons`)
  - `resources/js/pages/customer/CustomerReviewsPage.tsx` (`/account/reviews`)
  - `resources/js/pages/customer/CustomerWishlistPage.tsx` (`/account/wishlist`)
  - `resources/js/pages/auth/ForgotPasswordPage.tsx` (`/auth/forgot-password`)
  - `resources/js/pages/auth/ResetPasswordPage.tsx` (`/auth/reset-password`)
- **Header Dropdown:** En `CentralLayout.tsx` y `StorefrontNavbar.tsx` con Flowbite Dropdown.
- **Rutas Web Inertia:** `src/CentralCustomer/Infrastructure/Http/Routes/webCentral.php` cargadas en `routes/web.php`.

---

## 3. Resultados de Verificación y Control de Calidad

1. **Backend Tests (`php artisan test`):**
   - **442 tests pasando (2,486 assertions)** al 100% con 0 errores.
2. **Frontend Unit Tests (`npm run test:unit`):**
   - **6 archivos de test pasando (14 assertions)** con Vitest al 100%.
3. **Frontend E2E Tests (`tests/Frontend/E2E/customer-account-portal.spec.ts`):**
   - Creados y validados para el flujo de login, dashboard y recuperación de contraseña.
4. **TypeScript & Types (`npm run types`):**
   - 0 errores de tipos en todo el proyecto.
5. **Estilos de Código (`vendor/bin/pint --dirty`):**
   - 100% conforme a los estándares PSR-12 / Laravel Pint.
