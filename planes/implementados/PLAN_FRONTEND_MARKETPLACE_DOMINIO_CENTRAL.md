# Plan: Frontend del Marketplace de Dominio Central OwOMarket

## 🎯 Objetivo
Diseñar y construir la interfaz completa, moderna y responsiva del **Marketplace Central OwOMarket** (`owomarket.local`), permitiendo a los compradores descubrir productos de múltiples tiendas inquilinas, armar un carrito unificado, realizar checkout con Pago Móvil / Binance Pay y recibir una factura única consolidada.

---

## 🎨 1. Estructura Visual y Componentes Frontend (React + Tailwind + Flowbite)

### A. Contexto Global de Carrito Central
- `resources/js/contexts/CentralCartContext.tsx`:
  - Manejo del carrito multi-tienda con agrupación de productos por `tenant_id`.
  - Métodos: `addItem`, `removeItem`, `updateQuantity`, `clearCart`, `getItemsByStore`, `getSubtotal`, `getTotalItems`.
  - Persistencia en `localStorage` (`owomarket_central_cart`).

### B. Layout Central
- `resources/js/components/layouts/CentralLayout.tsx`:
  - **Navbar Superior**:
    - Logo OwOMarket con badge de Marketplace.
    - Buscador global con selector rápido de categoría.
    - Enlaces de navegación: *Inicio*, *Explorar Catálogo*, *Tiendas Oficiales*, *Vender en OwOMarket*.
    - Botón de Carrito con indicador flotante de items y Drawer lateral.
    - Botón de Perfil con integración al modal de autenticación **OwO Pass SSO** (`CustomerAuthModal`).
  - **Drawer de Carrito Multi-Tienda**:
    - Vista rápida de artículos agrupados por tienda, subtotal por tienda y botón a checkout.
  - **Footer Central**:
    - Enlaces institucionales, métodos de pago aceptados (Pago Móvil & Binance Pay), y acceso para nuevos inquilinos.

### C. Páginas del Marketplace Central
1. **🏠 Portada Central (`/`)**:
   - `resources/js/Pages/marketplace/home/centralHomePage.tsx`:
     - **Hero Section**: Banner interactivo, buscador inteligente y accesos directos.
     - **Tiendas Destacadas**: Carrusel/Grid de tiendas verificadas con logo, banner y enlace.
     - **Novedades y Productos Destacados**: Grid de productos sincronizados con badge de vendedor ("Vendido por: *ChivoStore*").
     - **Sección de Beneficios**: OwO Pass, Pago Móvil / Binance Pay, Carrito Multi-Tienda y Compra Protegida.
2. **🔍 Catálogo y Búsqueda Global (`/explore` o `/marketplace`)**:
   - `resources/js/Pages/marketplace/catalog/CentralCatalogPage.tsx`:
     - Filtros laterales: Tienda/Vendedor, Categoría, Marca, Rango de Precios, Disponibilidad.
     - Ordenamiento: Más recientes, Menor precio, Mayor precio, Más vendidos.
     - Grid interactivo con paginación.
3. **📦 Detalle de Producto Central (`/product/{slug}` o `/marketplace/product/{id}`)**:
   - `resources/js/Pages/marketplace/product/CentralProductDetailPage.tsx`:
     - Galería de imágenes, selector de cantidad y variantes.
     - **Tarjeta de la Tienda Vendedora**: Nombre, logo, enlace al subdominio del tenant y botón para ver más productos de esa tienda.
     - Botones *"Agregar al Carrito Multi-Tienda"* y *"Comprar Ahora"*.
4. **🛒 Carrito Multi-Tienda Consolidado (`/cart`)**:
   - `resources/js/Pages/marketplace/cart/CentralCartPage.tsx`:
     - Desglose ordenado por tienda vendedora.
     - Controles de cantidad y eliminación.
     - Resumen consolidado con botón directo a Checkout.
5. **💳 Checkout Unificado Central (`/checkout`)**:
   - `resources/js/Pages/marketplace/checkout/CentralCheckoutPage.tsx`:
     - Integración con **OwO Pass** (identificación del cliente o invitado con autocompletado).
     - Formulario de dirección de envío.
     - Selección de Pasarela:
       - **Pago Móvil**: Datos bancarios oficiales de OwOMarket, cálculo automático en Bs. (VES) y campo de referencia bancaria.
       - **Binance Pay**: QR dinámico, Binance Pay ID y campo de hash de transacción USDT.
     - Conexión directa a `POST /api/central/marketplace/checkout/create-order`.
6. **🧾 Confirmación de Pedido Maestro (`/central/order/{id}/confirmation`)**:
   - `resources/js/Pages/marketplace/checkout/CentralOrderConfirmationPage.tsx`:
     - Factura única consolidada (`OWO-YYYYMMDD-XXXX`).
     - Desglose de paquetes por tienda vendedora y estado del pago.

---

## 🏛️ 2. Backend & Controladores HTTP (Laravel + Inertia)

- **Controladores de Vistas Inertia (`src/Marketplace/Infrastructure/Http/Controller/`)**:
  - `ViewCentralHomePageGETController.php` -> renderiza `marketplace/home/centralHomePage`.
  - `ViewCentralCatalogGETController.php` -> renderiza `marketplace/catalog/CentralCatalogPage`.
  - `ViewCentralProductDetailGETController.php` -> renderiza `marketplace/product/CentralProductDetailPage`.
  - `ViewCentralCartGETController.php` -> renderiza `marketplace/cart/CentralCartPage`.
  - `ViewCentralCheckoutGETController.php` -> renderiza `marketplace/checkout/CentralCheckoutPage`.
  - `ViewCentralOrderConfirmationGETController.php` -> renderiza `marketplace/checkout/CentralOrderConfirmationPage`.
- **Endpoints API Centrales**:
  - `GET /api/central/marketplace/home-data` -> Retorna tiendas destacadas, productos destacados y categorías.
  - `GET /api/central/marketplace/products` -> Búsqueda y filtrado paginado de `central_products`.
  - `GET /api/central/marketplace/product/{slug}` -> Detalle completo de producto con datos del vendedor.
- **Rutas**:
  - Actualizar `src/Marketplace/Infrastructure/Http/Routes/web.php` y `src/Marketplace/Infrastructure/Http/Routes/apiCentral.php`.

---

## 🧪 3. Plan de Verificación y Testing

1. **Testing de Backend (Feature & Integration)**:
   - Suite `CentralMarketplaceStorefrontTest.php` verificando:
     - Carga de la página principal y catálogo con productos de múltiples tiendas.
     - Búsqueda por término, categoría y filtro por tienda.
     - Consulta de detalle de producto con tienda asociada.
2. **Testing de Frontend**:
   - Validación completa con `npm run types` (0 errores de TypeScript).
   - Verificación de la navegación completa desde la Home -> Catálogo -> Carrito -> Checkout -> Factura Única.
3. **Validación Global**:
   - `php artisan test` (100% pasando).
4. **Git**:
   - Mover plan completado a `planes/implementados/` al finalizar.
   - Commit con Conventional Commits y push a `origin/moduleProduct`.
