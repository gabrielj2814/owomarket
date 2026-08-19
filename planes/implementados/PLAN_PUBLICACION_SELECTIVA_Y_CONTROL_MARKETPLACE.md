# Plan: Publicación Selectiva y Control de Visibilidad en Marketplace Central y Tienda

## 🎯 Objetivo
Permitir que el inquilino decida de forma granular e independiente qué productos publicar en el **Marketplace Central OwOMarket** y cuáles mantener únicamente en su tienda propia (o solo para facturación interna), asegurando la sincronización en tiempo real del stock.

---

## ⚙️ Reglas de Negocio

1. **Creación de Producto por Defecto**:
   - Al crear un producto, `is_published_central = false` por defecto. El producto **no** se registra ni es visible en el Marketplace Central a menos que el inquilino active explícitamente la opción *"Publicar en Marketplace Central"*.
2. **Independencia de Canales de Venta**:
   - **Canal 1 (Tienda Propia / Storefront)**: Controlado por `is_visible`. Si es `false`, se oculta del storefront de la tienda pero se puede seguir facturando en el backoffice/POS.
   - **Canal 2 (Marketplace Central)**: Controlado por `is_published_central`. Si es `false`, no aparece en el Marketplace Central.
3. **Dar de Baja / Despublicar del Marketplace Central**:
   - Si el inquilino despublica un producto del Marketplace Central (`is_published_central = false`), se actualiza su estado en `central_products` a oculto (`is_visible = false`), pero el inquilino **puede seguir vendiéndolo y facturándolo en su tienda local**.
4. **Sincronización Bidireccional de Stock**:
   - Cada venta en la tienda local o en el Marketplace Central descuenta el inventario y mantiene el stock sincronizado con `central_products`.

---

## 🗄️ 1. Base de Datos y Modelos

### A. Base de Datos del Inquilino (Tenant)
- `database/migrations/tenant/2026_08_19_000006_add_marketplace_publication_to_products_table.php`:
  - `is_published_central` (boolean, default `false`).
  - `published_to_central_at` (timestamp, nullable).
- Actualización de modelo `Src\Product\Infrastructure\Eloquent\Models\Product.php`.

### B. Base de Datos Central
- Verificación / actualización de `central_products` (`is_visible`, `quantity`, `tenant_id`, `updated_at`).

---

## 🏛️ 2. Arquitectura Hexagonal DDD (`src/Product/`)

- **Domain & Application**:
  - Actualizar `Product` Entity con `isPublishedCentral`.
  - Actualizar `CreateProductData` y `UpdateProductData` DTOs.
  - `ToggleProductMarketplacePublicationUseCase.php`:
    - Alterna `is_published_central`.
    - Si pasa a `true`: Sincroniza/actualiza el producto en `central_products` con stock, precios, variantes e imágenes.
    - Si pasa a `false`: Oculta el producto en `central_products` (`is_visible = false`).
- **HTTP Controllers & Endpoints**:
  - `POST /api-tenant/product/{id}/toggle-marketplace` -> `ToggleProductMarketplacePublicationPOSTController.php`
  - Actualizar `CreateProductPOSTController.php` y `UpdateProductPOSTController.php` para respetar la bandera `is_published_central`.

---

## 🎨 3. Frontend Backoffice Tenant (React + TypeScript)

- **Formulario de Producto (`ProductModal.tsx` o creación/edición)**:
  - Toggle 1: 🏪 *"Visible en Storefront de la Tienda"* (`is_visible`).
  - Toggle 2: 🌐 *"Publicar en Marketplace Central OwOMarket"* (`is_published_central`) con tooltip explicativo.
- **Listado de Productos (`ProductListPage` / `DataTable`)**:
  - Columna/Badge de estado en Marketplace Central (🌐 *Publicado en Marketplace* / 🔒 *Solo Tienda Local*) con switch para alternar con un solo clic.

---

## 🧪 4. Plan de Verificación y Testing

1. **Test Automatizado (`tests/Feature/Product/ProductMarketplacePublicationTest.php`)**:
   - Crear producto sin publicar en central -> Verificar que no existe en `central_products` como activo.
   - Publicar en marketplace central -> Verificar creación/sincronización en `central_products`.
   - Despublicar del marketplace central -> Verificar que queda oculto en `central_products` pero sigue activo en la tienda del tenant para facturación.
   - Modificar stock en el tenant -> Verificar actualización de stock en `central_products`.
2. **Suite Global**:
   - `php artisan test` (100% pasando) y `npm run types` (0 errores).
3. **Git**:
   - Commit con Conventional Commits y push a `origin/moduleProduct`.
