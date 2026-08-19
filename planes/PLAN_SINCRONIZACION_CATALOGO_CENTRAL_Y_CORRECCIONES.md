# 📋 Plan de Desarrollo: Corrección de Tablas, Creación de Cupones/Marcas y Catálogo Maestro Centralizado (Sync por UUID)
## OwoMarket - Arquitectura Hexagonal y DDD

Este documento define la planificación detallada, arquitectura y desglose por fases para resolver los problemas de lectura de datos en tablas de backoffice, incorporar modales de creación rápida y desarrollar el sistema de **Catálogo Maestro Centralizado (Sincronización de Categorías y Marcas con UUID)**.

---

## 🗺️ Arquitectura de Catálogo Maestro y Sincronización

```mermaid
flowchart TD
    subgraph CentralDB ["🏢 Base de Datos Central (owomarket_dev)"]
        CCAT["Categorías Globales Maestras\n(id: UUID, name, slug, icon, parent_id)"]
        CBRD["Marcas Globales Maestras\n(id: UUID, name, slug, logo, position)"]
    end

    subgraph TenantDB ["🏪 Base de Datos del Tenant (tenant_*)"]
        TCAT["Categorías del Tenant\n(id, central_uuid, name, slug, is_active)"]
        TBRD["Marcas del Tenant\n(id, central_uuid, name, slug, is_active)"]
        PROD["Productos del Tenant\n(category_id, brand_id)"]
    end

    CCAT -->|SyncCentralCategoriesUseCase\n(POST /api-tenant/category/sync-central)| TCAT
    CBRD -->|SyncCentralBrandsUseCase\n(POST /api-tenant/brand/sync-central)| TBRD
    TCAT --> PROD
    TBRD --> PROD
```

---

## 📌 Desglose por Fases de Desarrollo

### 🔹 Fase 1: Corrección Inmediata de Visualización en Tablas de Backoffice (`resources/js/`)
- [x] **`ProductIndexPage.tsx`**: Normalizar la lectura de datos para soportar tanto `res.data` como `res.data.data`, y cargar correctamente dependencias de categorías y marcas.
- [x] **`BrandIndexPage.tsx`**: Normalizar la lectura de `res.data` para poblar la tabla de marcas.
- [x] **`CouponIndexPage.tsx`**: Normalizar la lectura de `res.data` para poblar la tabla de cupones.
- [x] **`AttributeIndexPage.tsx`**: Normalizar la lectura de `res.data` para listar atributos.
- [x] **`TaxIndexPage.tsx`**: Normalizar la lectura de `res.data` para listar tasas de impuestos.
- [x] **`ShippingIndexPage.tsx`**: Normalizar la lectura de `res.data` para listar zonas y tarifas de envío.
- [x] **`CategoryIndexPage.tsx`**: Normalizar la lectura de `res.data` para listar categorías.

---

### 🔹 Fase 2: Botones y Modales de Creación Rápida
- [x] **Creación de Cupones (`CouponIndexPage.tsx`)**:
  - [x] Añadir botón **"Crear Cupón"** en la cabecera.
  - [x] Implementar modal interactivo con formulario: código, tipo (`percentage`/`fixed_amount`), valor, compra mínima, límite de usos, fechas y estado.
  - [x] Conectar con `CouponServices.create`.
- [x] **Creación de Marcas (`BrandIndexPage.tsx`)**:
  - [x] Añadir botón **"Crear Marca"** en la cabecera.
  - [x] Implementar modal interactivo para registro de marca local (nombre, slug, logo URL, posición, descripción, estado).
  - [x] Conectar con `BrandServices.create`.
- [x] **Creación de Categorías (`CategoryIndexPage.tsx`)**:
  - [x] Añadir botón **"Crear Categoría"** en la cabecera.
  - [x] Implementar modal interactivo con formulario Flowbite para registro de categoría local.
  - [x] Conectar con `CategoryServices.create`.

---

### 🔹 Fase 3: Catálogo Maestro Centralizado y Sincronización por UUID
- [x] **Migraciones de Base de Datos**:
  - [x] Migración tenant: agregar columna `central_uuid (nullable)` indexada a `categories` y `brands`.
  - [x] Migración central: crear tabla `central_brands` con id UUID en la base de datos central.
  - [x] Migración central: asegurar `parent_id` nullable en `tenant_categories`.
- [x] **Modelos Centrales y Conexión**:
  - [x] `CentralCategory.php` con conexión `central` y tabla `tenant_categories`.
  - [x] `CentralBrand.php` con conexión `central` y tabla `central_brands`.
- [x] **Servicios y Casos de Uso Backend (`src/`)**:
  - [x] `SyncCentralCategoriesUseCase`: Obtiene el árbol maestro de categorías de la central e inserta/actualiza (`upsert`) en la base de datos del tenant vinculando `central_uuid` y resolviendo jerarquía `parent_id`.
  - [x] `SyncCentralBrandsUseCase`: Obtiene las marcas maestras de la central e inserta/actualiza (`upsert`) en la base de datos del tenant vinculando `central_uuid`.
  - [x] Controladores API:
    - `POST /api-tenant/category/sync-central` -> `SyncCentralCategoriesPOSTController`
    - `POST /api-tenant/brand/sync-central` -> `SyncCentralBrandsPOSTController`
- [x] **Integración en Frontend**:
  - [x] Métodos `syncCentral()` en `CategoryServices.ts` y `BrandServices.ts`.
  - [x] Botón **"Sincronizar Catálogo Central"** con loader e indicadores de éxito en `CategoryIndexPage.tsx` y `BrandIndexPage.tsx`.
- [x] **Seeder Maestro Central**:
  - [x] `CentralMasterCatalogSeeder.php` con categorías jerárquicas y marcas comerciales reconocidas globales.
  - [x] Integración en `DatabaseSeeder.php` y `TenantDemoDataSeeder.php`.

---

### 🔹 Fase 4: Testing Integral y QA
- [x] Pruebas de integración de sincronización en `tests/Feature/Tenant/MasterCatalogSyncTest.php`.
- [x] Verificación de suite completa con `php artisan test` (**362 tests pasando, 1,915 aserciones** al 100%).
- [x] Verificación estática con `npm run types` (**0 errores de tipado TypeScript**).
