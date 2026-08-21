# PLAN — Fase 2.2: Sincronización del catálogo central (bloque E completo)

> **Origen:** hallazgos E1, E2, E3 y E4 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md` (puntos 10 y 11 del plan de acción)
> **Severidad:** 🔴 E1 y E2 críticos · 🟠 E3 y E4 altos
> **Tamaño:** 1 observador nuevo, 1 servicio nuevo, 2 migraciones, 8 archivos de código, 4 páginas de frontend, 2 archivos de test
> **Estado:** ✅ Implementado — 525 tests en verde (`php artisan test`)
> **Cierra el bloque E entero.**

El bloque completo se resume en una frase: **el catálogo central se quedaba congelado en el estado que tenía el día de la publicación.** Y desde la Fase 0.4 el checkout central toma los precios de `central_products`, así que eso dejó de ser cosmético: es dinero mal cobrado.

---

## 1. E1 y E2 — La sincronización dependía de que alguien se acordara

`SyncProductToCentralMarketplaceUseCase` ya existía. El problema es que **sólo lo invocaba `ToggleProductMarketplacePublicationUseCase`**, es decir, únicamente cuando el comerciante pulsaba «publicar en el marketplace». Ni al crear, ni al editar, ni al borrar, ni al ocultar, ni al vender.

**Escenarios que estaban vivos:**

- El comerciante sube el precio de $50 a $80 → el marketplace central sigue vendiendo a $50 indefinidamente.
- El comerciante borra un producto descatalogado → sigue apareciendo y siendo comprable; el checkout recibe un `product_id` que ya no existe.
- El comerciante oculta un producto → sigue publicado en el marketplace.
- Se vende una unidad → el stock central no baja, porque el descuento del checkout nunca pasaba por `updateStock()`, el único método que propagaba algo.

### 1.1 Solución: eventos de modelo

`ProductObserver` nuevo, registrado en `ProductServiceProvider::boot()`, que sincroniza en `saved`, `deleted` y `restored`. Los eventos de modelo son el único punto por el que pasan **todos** los caminos, así que ya no es posible añadir uno nuevo y olvidarse.

### 1.2 El detalle que lo hacía no funcionar: el query builder no dispara eventos

Colocar el observador no bastaba. Las escrituras hechas con el query builder (`Product::where(...)->update(...)`) **se saltan los eventos de Eloquent por completo**, y justo los tres caminos del hallazgo iban por ahí:

| Sitio | Antes | Ahora |
| :--- | :--- | :--- |
| `ProductRepository::delete()` | `where(...)->delete()` | `where(...)->first()?->delete()` |
| `ProductRepository::toggleVisibility()` | `where(...)->update(...)` | `where(...)->first()?->update(...)` |
| `ProductRepository::updateStock()` | `where(...)->update(...)` + copia a mano del stock central envuelta en un `catch` vacío | `where(...)->first()?->update(...)`, y sincroniza el observador |
| `StockReserver::release()` | `increment('quantity')` | lectura y `save()` sobre el modelo |

`StockReserver::reserve()` ya usaba `save()` desde la Fase 1.3, así que las ventas empezaron a propagarse solas.

Y `ToggleProductMarketplacePublicationUseCase` **deja de llamar a la sincronización a mano**: ahora la dispara el mismo `save()` que todos los demás caminos.

### 1.3 Ocultar en la tienda ahora sí oculta en el marketplace

La sincronización escribía `'is_visible' => true` fijo. Ahora manda la visibilidad del producto en la tienda, que es la otra mitad de E1.

### 1.4 Dos consecuencias que hubo que resolver por el camino

Hacer la sincronización automática rompía dos cosas que antes se sostenían por accidente:

1. **El comerciante podía deshacer una decisión del moderador.** Si `is_visible` se recalcula en cada guardado, bastaba con editar el producto para volver a publicar algo que el superadmin acababa de retirar. Se añade la columna `central_products.is_blocked_by_admin`, que fija `ModerateCentralProductUseCase` y que la sincronización respeta: **el comerciante controla su producto, el moderador puede vetarlo por encima.**
2. **Cada sincronización borraba el historial de moderación y la comisión personalizada.** El `metadata` central se sobrescribía con el del producto de la tienda. Ahora se fusiona, preservando `moderation_history` y `custom_commission_rate`. Este bug ya existía; simplemente pasaba desapercibido porque la sincronización casi nunca corría.

### 1.5 Qué pasa si el catálogo central no responde

El fallo **no** revierte la escritura de la tienda: abortar una venta porque el marketplace no responde sería peor que la desincronización que causa. Pero se registra como `error` con tienda, producto y motivo — no en silencio como hacía el `catch (\Throwable) {}` del antiguo `updateStock()`. Una fila desincronizada es dinero mal cobrado y tiene que dejar rastro.

---

## 2. E3 — Colisión de slugs entre tiendas

La búsqueda, repetida en dos controladores, ponía tres campos a competir en el mismo `OR` y **sin filtrar por tienda**:

```php
$q->where('slug', $slugOrId)->orWhere('id', $slugOrId)->orWhere('tenant_product_id', $slugOrId);
```

Como el slug se copia tal cual desde cada tienda, los duplicados entre tiendas son la norma. Si A y B publican `camisa-blanca`, al abrir el producto de B se mostraba la ficha, el precio y la tienda de **A**, y el «añadir al carrito» apuntaba al inquilino equivocado.

### Solución

`CentralProductResolver` nuevo, que ambos controladores comparten. Resuelve **por prioridad, no por competición**:

1. `id` del catálogo central — UUID, inequívoco.
2. `tenant_product_id` — UUID, inequívoco.
3. `slug` — ambiguo por naturaleza en una URL global sin tienda. Se conserva por compatibilidad con los enlaces ya publicados, pero desempatando siempre igual, de modo que la misma URL lleve **siempre** a la misma ficha en vez de depender del orden de la base de datos.

Y los enlaces del marketplace central (catálogo, portada, relacionados y carrito) pasan a usar el id en lugar del slug, que es lo que de verdad cierra el hallazgo. **Los enlaces del storefront de cada tienda no se tocan:** ahí `/product/{slug}` es otra ruta, la de la propia tienda, donde el slug sí es único.

Se añade además el índice único `(tenant_id, slug)`. No hace único el slug globalmente —dos tiendas *deben* poder vender `camisa-blanca`— sino que garantiza que, fijada la tienda, el slug identifica un único producto: la pareja resoluble que pedía la auditoría.

---

## 3. E4 — Editar un producto regeneraba los IDs de todas sus variantes

`update()` borraba **físicamente** todas las filas de `product_variants` y `product_images` y las recreaba con UUID nuevos, en cada edición.

**Escenario:** el comerciante corrige una errata en la descripción → todas las variantes cambian de id. Los `order_items.product_variant_id` de pedidos históricos quedan huérfanos, los carritos de los clientes apuntan a variantes inexistentes, el array `variants` ya sincronizado en `central_products` queda inconsistente, y las imágenes borradas dejan sus ficheros huérfanos en disco.

### Solución

`upsertVariants()` y `upsertImages()`: se actualiza lo que ya existía (por id), se crea sólo lo nuevo y se borra únicamente lo que el comerciante quitó de verdad. Al eliminar una imagen se borra también el fichero físico, para lo cual `ProductRepository` recibe ahora `ProductMediaStorageInterface` por constructor.

---

## 4. Archivos tocados

**Código:**
- `src/Product/Infrastructure/Eloquent/Observers/ProductObserver.php` (**nuevo**)
- `src/Marketplace/Application/Service/CentralProductResolver.php` (**nuevo**)
- `src/Product/Application/UseCase/SyncProductToCentralMarketplaceUseCase.php`
- `src/Product/Application/UseCase/ToggleProductMarketplacePublicationUseCase.php`
- `src/Product/Infrastructure/Eloquent/Repositories/ProductRepository.php`
- `src/Product/Infrastructure/Eloquent/Models/CentralProduct.php`
- `src/Marketplace/Application/Service/StockReserver.php`
- `src/Marketplace/Infrastructure/Http/Controller/GetCentralProductDetailAPIController.php`
- `src/Marketplace/Infrastructure/Http/Controller/ViewProductDetailCentralGETController.php`
- `src/Admin/Application/UseCase/ModerateCentralProductUseCase.php`
- `app/Providers/ProductServiceProvider.php`

**Migraciones (ambas nuevas):**
- `2026_08_21_120000_add_admin_block_to_central_products.php`
- `2026_08_21_130000_add_unique_tenant_slug_to_central_products.php`

**Frontend:** `CentralCatalogPage.tsx`, `centralHomePage.tsx`, `CentralProductDetailPage.tsx`, `CentralCartPage.tsx`

**Tests:**
- `tests/Feature/Product/CentralCatalogSyncTest.php` (**nuevo**, 11 casos)
- `tests/Integration/Product/ProductRepositoryTest.php` (+2 casos de E4)

Los casos de E3 construyen las filas centrales directamente en vez de a través de dos tiendas reales: la suite corre con `DatabaseTenancyBootstrapper` desactivado, así que todas las «tiendas» comparten la misma base SQLite y el `unique` de `products.slug` impediría el escenario. En producción cada tienda tiene su base y dos productos con el mismo slug son lo normal.

---

## 5. Checklist de cierre

- [x] `php artisan test` → 525 pasan (3.076 aserciones)
- [x] `./vendor/bin/pint` sobre los archivos tocados
- [x] `git add` + commit
- [x] `git push origin <rama_actual>`
- [x] Actualizar el bloque de estado de `AUDITORIA_BUGS_2026_08_21.md`
- [x] Mover este documento a `planes/implementados/`

---

## 6. Verificación manual

**Debe cambiar:**
1. Editar el precio de un producto publicado → el marketplace central muestra el precio nuevo **sin** volver a pulsar «publicar».
2. Ocultar o borrar el producto en la tienda → desaparece del marketplace.
3. Vender una unidad → el stock del marketplace baja.
4. Un producto retirado por el moderador **sigue retirado** después de que el comerciante lo edite.
5. Editar la descripción de un producto con variantes → las variantes conservan su id (comprobar contra `order_items` históricos).
6. Dos tiendas con el mismo slug → cada ficha abre la suya.

**Debe seguir funcionando:**
7. El botón de publicar/despublicar en el marketplace.
8. Los enlaces `/product/{slug}` ya publicados o guardados en marcadores.
9. El storefront de cada tienda, cuyos enlaces por slug no se han tocado.

---

## 7. Riesgo

**Medio-alto.** Es la fase que más superficie mueve de todas las hechas hasta ahora.

1. **El catálogo central existente está desincronizado y no se arregla solo.** Los productos sólo se re-sincronizan cuando se vuelvan a guardar. Conviene forzar una pasada tras desplegar, tienda por tienda:
   ```php
   Product::where('is_published_central', true)->each->touch();
   ```
   Sin eso, los precios viejos siguen ahí. **Es el punto más importante de este despliegue.**
2. **El índice único `(tenant_id, slug)` puede fallar al migrar** si algún catálogo central ya tiene slugs repetidos dentro de una misma tienda. Comprobar antes:
   ```sql
   SELECT tenant_id, slug, COUNT(*) FROM central_products
   GROUP BY tenant_id, slug HAVING COUNT(*) > 1;
   ```
3. **Ahora se escribe en la base central en cada guardado de producto.** Incluido dentro de la transacción del checkout, que se alarga. Al volumen actual no debería notarse, pero es lo primero que hay que mirar si el checkout se vuelve lento.
4. **`ProductRepository` cambió de firma:** recibe `ProductMediaStorageInterface`. Cualquier `new ProductRepository` fuera del contenedor revienta; se corrigieron los dos que había en los tests.
5. **Borrar una imagen ahora borra el fichero del disco.** Es lo que pedía la auditoría, pero es irreversible: si dos productos compartieran la misma ruta de imagen, borrar uno dejaría al otro sin fichero. No parece darse hoy —cada subida genera su propio nombre— pero conviene tenerlo presente.

---

## 8. Trabajo de seguimiento identificado

1. **No hay comando para re-sincronizar el catálogo.** El punto 1 del riesgo se resuelve hoy con un `tinker` a mano. Merece un `catalog:resync {--tenant=}` idempotente, que además serviría para reparar desincronizaciones futuras.
2. **La sincronización es síncrona.** Cada guardado de producto escribe en la base central en la misma petición. Lo natural es un job en cola con reintentos, que además resolvería el punto 1.5: hoy, si el marketplace no responde, la fila queda desincronizada y sólo queda el log.
3. **`ProductVariant` y `ProductImage` no propagan sus propios eventos.** Si algún día se editan variantes sin pasar por `ProductRepository::update()`, el catálogo central no se enteraría. Hoy no ocurre, pero es la misma clase de agujero que este plan cierra para `Product`.
4. **El slug sigue siendo ambiguo en `/product/{slug}`.** Los enlaces nuevos usan el id y la resolución es estable, pero un enlace antiguo a un slug compartido puede llevar a la tienda equivocada. Cerrarlo del todo pide una URL con tienda (`/{tienda}/product/{slug}`) y una redirección permanente desde la forma antigua.
5. **N12 sigue abierto:** el formulario de reseñas del storefront exige `customer_id` y `TenantProductDetailPage.tsx` nunca lo envía (422 garantizado).
