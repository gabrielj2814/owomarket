# Auditoría del backend — módulos de `src/`

> ## 📌 ESTADO — 23/08/2026
>
> **Documento vivo.** Se va ampliando módulo a módulo. **No se está arreglando nada
> todavía**: aquí sólo se registra lo encontrado, con su estado.
>
> Alcance: los módulos de `src/` que nunca han tenido una pasada propia. Han recibido
> arreglos puntuales llegados desde otras auditorías —`StockReserver` por N14 y N36, los
> cupones por B3/C6, las pasarelas por G1— pero **nadie ha mirado el conjunto**.
>
> ### Leyenda
> 🔴 crítico · 🟠 alto · 🟡 medio · ✅ cerrado · ⬜ abierto
>
> ### Nomenclatura
> `PR` Product · `OR` Order · `SH` Shipment · `PY` Payment

---

## Progreso por módulo

| Módulo | Ruta | Tamaño | Estado |
| :--- | :--- | :--- | :--- |
| **Product** | `src/Product/` | 85 ficheros · 4.686 líneas | ✅ **Auditado** — 2 hallazgos |
| Order | `src/Order/` | 52 ficheros · 2.917 líneas | ⬜ Pendiente |
| Shipment | `src/Shipment/` | 36 ficheros · 1.730 líneas | ⬜ Pendiente |
| Payment | `src/Payment/` | 33 ficheros · 1.626 líneas | ⬜ Pendiente |

---

## Hallazgos abiertos

| # | Módulo | Qué | Severidad | Estado | Demostrado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **PR1** | Product | Borrado arbitrario de ficheros del disco público | 🔴 | ⬜ Abierto | ⚠️ Sólo lectura |
| **PR2** | Product | Actualizar el stock de un producto con variantes no hace nada | 🟠 | ⬜ Abierto | ⚠️ Sólo lectura |

> **Ninguno está probado ejecutando.** Los dos salen de leer el código. En este proyecto esa
> distinción ha importado varias veces en un solo día —una lectura llevó a un falso positivo
> de fijación de sesión, y un fixture incompleto casi archiva el hallazgo T7 como
> inexistente—, así que queda marcada hasta que se demuestren.

---

# Módulo Product

**Auditado el 23/08/2026.** Método: perímetro de rutas y guardas, después integridad de los
datos que el módulo escribe (stock, precios, ficheros).

## PR1. 🔴 Borrado arbitrario de ficheros del disco público

> **Estado:** ⬜ ABIERTO

**Dónde:** `DeleteProductImageDELETEController` → `DeleteProductImageUseCase` →
`LaravelProductMediaStorageService::deleteImage()`

El controlador toma la ruta **directamente del request, sin validarla**:

```php
$imagePath = (string) $request->input('image_path', $request->query('image_path', ''));

if (! empty($imagePath)) {
    $this->deleteUseCase->execute($imagePath);
}
```

Y el servicio la usa tal cual:

```php
if (Storage::disk('public')->exists($relativePath)) {
    Storage::disk('public')->delete($relativePath);
}
```

**No hay ninguna comprobación** de que ese fichero pertenezca a este producto, a esta tienda,
ni siquiera de que esté dentro del directorio de imágenes de producto.

### Qué se puede borrar

El disco `public` no es sólo de Product. Lo comparten:

| Qué | Dónde |
| :--- | :--- |
| Imágenes de producto **de todas las tiendas** | `LaravelProductMediaStorageService` |
| **Avatares de administradores** | `src/Admin/.../LaravelAvatarStorageService.php` |
| **PDFs de facturas** | `src/Billing/.../DomPdfInvoiceGeneratorService.php` |
| **Adjuntos de tickets de soporte** | `src/SupportTicket/.../UploadSupportAttachmentService.php` |

Las facturas y los adjuntos de soporte son **registros**, no adorno: son la prueba
documental de una transacción y de una reclamación.

### Quién puede hacerlo

Cualquiera con sesión de tienda y `manage_catalog` — el permiso más básico del catálogo, el
que tiene cualquier empleado que suba productos. No hace falta ser propietario.

La ruta está correctamente autenticada (`auth` + `throttle:api` + `tenant_can:manage_catalog`);
el problema no es el perímetro, es que **dentro no se comprueba de quién es el fichero**.

### Dos agravantes

1. **Responde siempre éxito**, borre o no borre algo. Quien lo use a mano no recibe señal
   de error, y quien audite los registros tampoco verá un rastro de intentos fallidos.
2. **Ningún `validate()`**: ni `required`, ni formato, ni longitud.

### Por dónde iría el arreglo

Resolver la imagen por su **id** en la base del inquilino en vez de aceptar una ruta, y
borrar el fichero que esa fila dice. La tenancy ya aísla la base por dominio, así que un id
no puede apuntar fuera de la tienda. Si hay que seguir aceptando rutas, comprobar que
empiecen por el prefijo de imágenes de producto **y** que exista una fila de
`product_images` que las reclame.

### Cómo demostrarlo

Autenticarse como usuario de tienda con `manage_catalog` y llamar al endpoint con la ruta de
un avatar o de una factura. Es la comprobación que falta antes de darlo por bueno.

---

## PR2. 🟠 Actualizar el stock de un producto con variantes no hace nada

> **Estado:** ⬜ ABIERTO

**Dónde:** `UpdateProductStockPATCHController` → `UpdateProductStockUseCase` →
`ProductRepository::updateStock()`

```php
EloquentProduct::where('id', $id->value())->first()?->update(['quantity' => max(0, $quantity)]);
```

Escribe **sólo** en `products.quantity`. Nunca toca `product_variants`.

### Por qué eso es un problema

En un producto con variantes, el `quantity` del padre **no lo mantiene ni lo lee nadie**. Lo
dice el propio código, anotado en el hallazgo N36:

> *«el `quantity` del padre no sirve en un producto con variantes: `StockReserver` sólo
> descuenta de la variante»*

Y la ficha de producto muestra el de la variante cuando la hay:

```tsx
const stockDeclarado = varianteActiva ? varianteActiva.quantity : Number(product.quantity);
```

Así que el comerciante entra en su lista de productos, corrige el stock de una camiseta con
tallas, ve que se guarda correctamente — y **ni lo que se vende ni lo que se muestra cambia**.
El endpoint lo llama `ProductIndexPage.tsx`, la lista del backoffice de la tienda.

### Consecuencia práctica

Un producto agotado sigue agotado después de reponerlo, y el comerciante no tiene forma de
saber por qué. Es un fallo silencioso en el inventario: no da error, simplemente no surte
efecto.

### Por dónde iría el arreglo

Que el endpoint acepte un `variant_id` opcional y escriba donde toca, o que rechace la
operación sobre un producto con variantes indicando que el stock se gestiona por variante.
Lo que no puede es aceptar el cambio y no aplicarlo.

---

## Lo que se comprobó y está BIEN

No hace falta volver a mirarlo:

| Qué | Resultado |
| :--- | :--- |
| **Perímetro de rutas** | ✅ Las 8 rutas de `api-tenant/product/*` llevan `Authenticate` + `throttle:api` + `tenant_can:manage_catalog` |
| **Aislamiento entre tiendas** | ✅ La tenancy resuelve la base por dominio, así que un `{id}` no puede apuntar al producto de otra tienda |
| **Concurrencia de stock en venta** | ✅ `StockReserver` usa `lockForUpdate()` dentro de transacción, y toca la variante cuando se le pasa (N14, N36) |
| **Validación de creación** | ✅ `sku`, `price`, `quantity`, `compare_price`, `cost_price`, `min/max_quantity` con tipos y `min:0` |
| **Validación de stock** | ✅ `required|integer|min:0`, con mensajes propios |
| **Subida de imágenes** | ✅ `file`, `image`, `mimes:jpeg,png,jpg,webp`, `max:5120` |
| **Sincronización con el catálogo central** | ✅ `ProductObserver` cubre `saved`, `deleted` y `restored` — la preocupación de E1 está atendida |
| **Identidad de variantes al editar** | ✅ E4 cerrado: se actualiza lo existente en vez de borrar y recrear con uuid nuevos |
