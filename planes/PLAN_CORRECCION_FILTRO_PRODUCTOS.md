# 📋 Plan de Corrección: Filtrado de Productos en Backoffice de Tenant

## 1. 🔍 Diagnóstico y Causa Raíz

En el controlador `Src\Product\Infrastructure\Http\Controller\FilterProductsPOSTController.php`:
```php
isVisible: $request->has('is_visible') ? (bool) $request->input('is_visible') : null,
isFeatured: $request->has('is_featured') ? (bool) $request->input('is_featured') : null,
isDigital: $request->has('is_digital') ? (bool) $request->input('is_digital') : null,
inStock: $request->has('in_stock') ? (bool) $request->input('in_stock') : null,
```

### El Problema:
1. Cuando la página [ProductIndexPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/product/ProductIndexPage.tsx) carga inicialmente o el usuario tiene seleccionado "Todos los Estados" (`""`), envía los parámetros como `null`:
   ```json
   {
     "search": null,
     "category_id": null,
     "brand_id": null,
     "is_visible": null,
     "in_stock": null,
     "page": 1,
     "per_page": 10,
     "sort_by": "created_at",
     "sort_direction": "desc"
   }
   ```
2. En Laravel, `$request->has('is_visible')` retorna `true` porque la clave `'is_visible'` sí viene en el cuerpo de la petición (aunque su valor sea `null`).
3. El casteo booleano `(bool) $request->input('is_visible')` evalúa `(bool) null` como **`false`**.
4. Como resultado, `$criteria->isVisible` se asignaba como `false` en vez de `null`.
5. En `ProductRepository.php`:
   ```php
   if ($criteria->isVisible !== null) {
       $query->where('is_visible', $criteria->isVisible); // Ejecutaba where is_visible = 0
   }
   ```
6. Dado que los 6 productos registrados en `chivostore` tienen `is_visible = 1` (activos/visibles), la consulta devolvía `data: []` (0 productos).
7. Lo mismo ocurría con `in_stock`, `is_featured` e `is_digital`.

---

## 2. 🛠️ Cambios Propuestos

### 🔹 Backend
- Modificar [FilterProductsPOSTController.php](file:///c:/laragon/www/owomarket/src/Product/Infrastructure/Http/Controller/FilterProductsPOSTController.php) para validar estrictamente que el valor esté presente y no sea `null` ni cadena vacía:
  ```php
  isVisible: $request->has('is_visible') && $request->input('is_visible') !== null && $request->input('is_visible') !== '' ? (bool) $request->input('is_visible') : null,
  isFeatured: $request->has('is_featured') && $request->input('is_featured') !== null && $request->input('is_featured') !== '' ? (bool) $request->input('is_featured') : null,
  isDigital: $request->has('is_digital') && $request->input('is_digital') !== null && $request->input('is_digital') !== '' ? (bool) $request->input('is_digital') : null,
  inStock: $request->has('in_stock') && $request->input('in_stock') !== null && $request->input('in_stock') !== '' ? (bool) $request->input('in_stock') : null,
  ```

### 🔹 Testing Automatizado
- Actualizar [ProductApiTest.php](file:///c:/laragon/www/owomarket/tests/Feature/Tenant/ProductApiTest.php) para agregar un test que envíe explícitamente `is_visible: null`, `in_stock: null`, etc., garantizando que se listen todos los productos sin restricciones booleanas no deseadas.

---

## 3. 🧪 Plan de Verificación
1. Ejecutar `php artisan test --filter=ProductApiTest` para validar los endpoints de producto.
2. Ejecutar la suite completa `php artisan test` (362+ tests) y `npm run types` (0 errores).
3. Realizar commit con Conventional Commits: `fix(product): fix boolean filter parsing when receiving null in FilterProductsPOSTController` y `git push origin moduleProduct`.
