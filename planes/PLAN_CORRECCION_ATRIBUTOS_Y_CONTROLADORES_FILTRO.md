# 📋 Plan de Corrección: Módulo de Atributos y Normalización Global de Controladores de Filtrado

## 1. 🔍 Diagnóstico y Causa Raíz

En `Src\Attribute\Infrastructure\Http\Controller\FilterAttributesPOSTController.php`:
```php
isFilterable: $request->has('is_filterable') && $request->input('is_filterable') !== ''
    ? filter_var($request->input('is_filterable'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
    : null,
isVisible: $request->has('is_visible') && $request->input('is_visible') !== ''
    ? filter_var($request->input('is_visible'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
    : null,
```

### El Problema:
1. En PHP, `null !== ''` evalúa como `true`.
2. Cuando el frontend envía `is_filterable: null` o `is_visible: null`, la condición `$request->has('is_filterable') && $request->input('is_filterable') !== ''` evalúa como **`true`**.
3. `filter_var(null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)` evalúa como **`false`** (porque `filter_var` convierte `null` a cadena vacía `""`, la cual falla la validación booleana).
4. Como resultado, `$criteria->isFilterable` y `$criteria->isVisible` se asignaban como **`false`**.
5. En `AttributeRepository.php`:
   ```php
   if ($criteria->isFilterable !== null) {
       $query->where('is_filterable', $criteria->isFilterable); // where is_filterable = 0
   }
   if ($criteria->isVisible !== null) {
       $query->where('is_visible', $criteria->isVisible); // where is_visible = 0
   }
   ```
6. Los atributos configurados en `chivostore` (ej. Color, Talla) tienen `is_visible = 1` y `is_filterable = 1`, por lo que la consulta devolvía 0 registros (`data: []`).

### Controladores Afectados por el Mismo Patrón:
- [FilterAttributesPOSTController.php](file:///c:/laragon/www/owomarket/src/Attribute/Infrastructure/Http/Controller/FilterAttributesPOSTController.php) (`is_filterable`, `is_visible`)
- [FilterBrandsPOSTController.php](file:///c:/laragon/www/owomarket/src/Brand/Infrastructure/Http/Controller/FilterBrandsPOSTController.php) (`is_active`)
- [FilterCouponsPOSTController.php](file:///c:/laragon/www/owomarket/src/Coupon/Infrastructure/Http/Controller/FilterCouponsPOSTController.php) (`is_active`)
- [FilterTaxRatesPOSTController.php](file:///c:/laragon/www/owomarket/src/Tax/Infrastructure/Http/Controller/FilterTaxRatesPOSTController.php) (`is_active`)
- [FilterShippingZonesPOSTController.php](file:///c:/laragon/www/owomarket/src/Shipping/Infrastructure/Http/Controller/FilterShippingZonesPOSTController.php) (`is_active`)

---

## 2. 🛠️ Cambios Propuestos

### 🔹 Backend: Corrección de Controladores de Filtrado
Ajustar la comprobación para validar explícitamente `$request->input('campo') !== null`:
```php
isActive: $request->has('is_active') && $request->input('is_active') !== null && $request->input('is_active') !== ''
    ? (bool) $request->input('is_active')
    : null
```
Aplicar este saneamiento a:
- `FilterAttributesPOSTController.php`
- `FilterBrandsPOSTController.php`
- `FilterCouponsPOSTController.php`
- `FilterTaxRatesPOSTController.php`
- `FilterShippingZonesPOSTController.php`

### 🔹 Frontend: Modal y Botón "Crear Atributo" en `AttributeIndexPage.tsx`
- Añadir botón **"Crear Atributo"** en la cabecera.
- Implementar modal interactivo con soporte para:
  - Nombre del Atributo (ej: Talla, Color, Memoria RAM).
  - Slug autogenerable.
  - Tipo (`text`, `color`, `select`, `button`).
  - Gestión dinámica de valores del atributo (ej: agregar "Rojo (#ff0000)", "Azul (#0000ff)", "S", "M", "L", "XL").
  - Opciones booleanas: `is_filterable` y `is_visible`.
  - Conexión con `AttributeServices.create`.

### 🔹 Testing Automatizado
- Añadir tests en `AttributeRepositoryTest` / `AttributeApiTest` verificando filtros con valores `null`.

---

## 3. 🧪 Plan de Verificación
1. Ejecutar `php artisan test` (363+ tests pasando al 100%).
2. Ejecutar `npm run types` (0 errores de tipado).
3. Commit con Conventional Commits: `fix(filters): normalize boolean filter parsing across all tenant module controllers and add create attribute modal` y `git push origin moduleProduct`.
