# 📋 Plan: Sincronización Idempotente y Actualización Inteligente con Base de Datos Central

## 1. 🎯 Objetivo
Garantizar que los procesos de sincronización con la base de datos central (`SyncCentralCategoriesUseCase`, `SyncCentralBrandsUseCase` y futuros módulos) sean **completamente idempotentes**:
1. **Detección de Existencia Multicriterio**:
   - Si el registro ya existe en el tenant (por `central_uuid`, `slug` o coincidencia de `name` insensible a mayúsculas), **NO se crea un nuevo registro** duplicado.
   - Se vincula el `central_uuid` y se **actualizan** los datos maestros (`name`, `slug`, `description`, `image`/`logo`, `icon`, `is_active`, `position`).
2. **Creación Limpia**:
   - Si el registro no existe bajo ningún criterio, se crea un registro nuevo vinculando su `central_uuid`.
3. **Métricas Detalladas en Respuesta**:
   - Devolver conteo exacto de: `created_count` (nuevos), `updated_count` (actualizados existentes), `unchanged_count` (sin cambios necesarios), y `synced_count` (total procesado).
4. **Mensaje Descriptivo al Usuario**:
   - Informar en la UI y API cuántos elementos se crearon, cuántos se actualizaron y si el catálogo ya estaba al día.

---

## 2. 🛠️ Componentes a Modificar

### 🔹 1. `Src\Category\Application\UseCase\SyncCentralCategoriesUseCase.php`
- Búsqueda en 3 niveles: `central_uuid` -> `slug` -> `LOWER(TRIM(name))`.
- Comparación de cambios para contabilizar `updated` vs `unchanged`.
- Actualización de `parent_id` en segunda fase preservando la jerarquía.

### 🔹 2. `Src\Brand\Application\UseCase\SyncCentralBrandsUseCase.php`
- Búsqueda en 3 niveles: `central_uuid` -> `slug` -> `LOWER(TRIM(name))`.
- Comparación de cambios para contabilizar `updated` vs `unchanged`.

### 🔹 3. Controladores API
- `SyncCentralCategoriesPOSTController.php`
- `SyncCentralBrandsPOSTController.php`
- Mensajes dinámicos contextualizados (`"Catálogo sincronizado: X creadas, Y actualizadas"`).

### 🔹 4. Testing Automatizado
- `tests/Feature/Tenant/MasterCatalogSyncTest.php`:
  - Test 1: Creación de nuevos registros cuando no existen.
  - Test 2: Actualización de datos cuando ya existen por slug o nombre previo sin crear duplicados.
  - Test 3: Sincronización repetida donde no se generan duplicados (idempotencia).

---

## 3. 🧪 Plan de Verificación
1. Ejecutar `php artisan test --filter=MasterCatalogSyncTest`.
2. Ejecutar la suite completa `php artisan test` (363+ tests al 100%).
3. Ejecutar `npm run types` (0 errores de tipado).
4. Commit con Conventional Commits: `feat(catalog-sync): implement robust 3-tier idempotent matching and detailed update metrics for central synchronization` y `git push origin moduleProduct`.
