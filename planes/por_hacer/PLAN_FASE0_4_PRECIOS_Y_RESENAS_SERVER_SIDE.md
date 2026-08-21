# PLAN — Fase 0.4: Dejar de confiar en el navegador para precios y reseñas

> **Origen:** hallazgos B1 y B2 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md` (punto 4 del plan de acción de Fase 0)
> **Severidad:** 🔴 Crítico — B1 es, según la propia auditoría, «el bug más grave del flujo de compra»
> **Tamaño:** 3 servicios nuevos, 2 checkouts, 1 FormRequest, 1 DTO, 1 caso de uso, 6 archivos de test
> **Estado:** ✅ Implementado — pendiente de validación con `php artisan test`
> **Depende de:** Fase 0.3-E (la lista blanca de `/api-tenant` deja `review/create` pública; esta fase cierra lo que eso dejaba abierto)

---

## 1. B1 — El precio de cada producto lo ponía el navegador

Los dos checkouts sumaban el precio que venía en el cuerpo de la petición, sin consultar nunca el real:

```php
// CreateUnifiedCentralOrderUseCase.php:34-37  (marketplace central)
$subtotal = 0.0;
foreach ($items as $item) {
    $subtotal += (float) ($item['price'] * (int) ($item['quantity'] ?? 1));
}
```

```php
// CreateStorefrontOrderPOSTController.php:89,94  (tienda del inquilino)
$price = (float) $item['price'];
$calculatedSubtotal += ($price * $qty);
```

La única validación era `numeric|min:0`.

**Escenario:** el comprador intercepta el POST del checkout y envía `"price": 0.01` para un producto de $500. Se crea el pedido por $0,01, se registra un `payment` de $0,01 y una comisión de $0,0008 — y la tienda despacha un producto de $500.

### 1.1 Solución

Dos servicios que resuelven la línea contra la base de datos y descartan por completo el `price` del request:

| Servicio | Fuente de verdad | Usado por |
| :--- | :--- | :--- |
| `Src\Marketplace\Application\Service\StorefrontItemPriceResolver` | `products` / `product_variants` del inquilino (la tenancy ya está inicializada en el checkout del storefront) | `CreateStorefrontOrderPOSTController` |
| `Src\CentralMarketplace\Application\Service\CentralItemPriceResolver` | `central_products` | `CreateUnifiedCentralOrderUseCase` |

**Por qué `central_products` y no la base de cada inquilino:** es el precio que el comprador vio en el listado del marketplace, y evita inicializar la tenancy una vez por línea dentro del checkout. La contra conocida es que ese catálogo puede quedar desactualizado (hallazgos E1 y E2, que siguen abiertos), pero eso es un problema de sincronización, no de confianza en el cliente.

Además de precio, ambos resolvers devuelven **nombre y SKU** desde la base: el navegador también podía renombrar la línea del pedido a su antojo.

### 1.2 Dos bugs adicionales que se cerraron de paso

- **Variante ajena.** `StorefrontItemPriceResolver` comprueba que la variante pertenezca al producto de la línea. Sin esa comprobación se podría pedir un producto caro con el precio de la variante barata de otro producto.
- **Producto oculto o inexistente.** Ambos resolvers rechazan con 422 en lugar de crear el pedido igual. Antes, con el `catch (\Throwable)` vacío del descuento de stock, un `product_id` inexistente pasaba sin queja (era el remate del hallazgo E1).

### 1.3 Lo que se corrigió del descuento de stock — y lo que NO

El bloque de descuento de stock del storefront tenía tres bugs en nueve líneas (hallazgo C1). Se corrigieron dos:

- **Doble descuento:** con variante se restaba la misma unidad de la variante **y** del producto padre. Ahora, si la línea trae variante, sólo se descuenta de la variante.
- **`catch (\Throwable)` vacío** que se tragaba cualquier error: eliminado.

**Sigue abierto, y es Fase 1:** no hay transacción ni `lockForUpdate`, así que dos pedidos simultáneos sobre la última unidad pueden seguir dejando el stock en negativo. Está marcado con un comentario en el propio controlador.

---

## 2. B2 — El cliente decidía si su reseña estaba aprobada y "verificada"

```php
// CreateProductReviewFormRequest.php:28-29
'is_approved' => ['nullable', 'boolean'],
'is_verified' => ['nullable', 'boolean'],
```
```php
// CreateProductReviewUseCase.php:36
isVerified: $data->isVerified || ! empty($data->orderId),
```

Un `POST` con `{"rating":5,"is_approved":true,"is_verified":true}` publicaba al instante una reseña de 5 estrellas marcada como "compra verificada", saltándose la moderación. Y `order_id` sólo se validaba con `exists:orders,id`: el id de **cualquier** pedido de **cualquier** cliente concedía la insignia.

### 2.1 Solución

1. `is_approved` e `is_verified` salen del `FormRequest`. `CreateReviewData::fromArray()` los fija en `false` aunque vengan en el array.
2. La reseña **nace siempre pendiente de moderación**. Aprobar es potestad del comerciante, vía `ModerateReviewPOSTController` (que ya exige sesión desde la Fase 0.3-E).
3. La insignia de compra verificada la concede el servidor con `Src\Review\Application\Service\VerifiedPurchaseChecker`, que exige que el pedido **exista, sea de quien reseña y contenga el producto reseñado**.

**No rompe el frontend:** el formulario de reseñas del storefront (`TenantProductDetailPage.tsx:144-151`) nunca envió esos dos campos, y su propio mensaje de éxito dice «tu reseña ha sido enviada para moderación y aprobación» — el comportamiento que ahora sí se cumple.

---

## 3. Hallazgo nuevo encontrado en el camino

**El formulario de reseñas del storefront está roto hoy, y siempre lo estuvo.** `CreateProductReviewFormRequest` exige `customer_id` (`required|exists:customers,id`), pero `TenantProductDetailPage.tsx:144-151` envía `product_id`, `rating`, `title`, `comment`, `author_name` y `email` — **nunca `customer_id`**. Toda reseña enviada desde la ficha de producto recibe 422.

Esto no lo causa esta fase; es preexistente. Pero es relevante por dos motivos:

1. Explica por qué nadie notó que las reseñas del storefront no funcionaban.
2. Cambia el cálculo de la decisión que se tomó en la Fase 0.3-E de dejar `review/create` pública "para no romper el flujo actual": **no hay flujo actual que romper**. Exigir `session('tenant_customer_id')` en esa ruta —que además resolvería el `customer_id` que hoy falta— es ahora un cambio de bajo riesgo, no de riesgo medio.

Queda anotado en `src/Review/.../apiTenant.php` y propuesto como seguimiento.

---

## 4. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **B1** — precio manipulable en el checkout central | ✅ Cerrado |
| **B1** — precio manipulable en el checkout del storefront | ✅ Cerrado |
| **B2** — `is_approved`/`is_verified` desde el request | ✅ Cerrado |
| **B2** — `is_verified` con el pedido de otro | ✅ Cerrado |
| **C1** — doble descuento de stock con variante | ✅ Cerrado |
| **C1** — `catch` vacío que ocultaba errores de stock | ✅ Cerrado |
| **C1** — falta de transacción y `lockForUpdate` | ⬜ Fase 1 |
| **E1/E2** — `central_products` desactualizado | ⬜ Fase 2 (ahora con más peso: es la fuente de precios del checkout central) |
| Identidad del autor de la reseña (`customer_id` del body) | ⬜ Seguimiento, ver sección 3 |

Con esto queda cerrado el **punto 4 del plan de acción de Fase 0**. Sólo resta el punto 5: G1 y G8 (datos bancarios hardcodeados y botón de bypass del checkout).

---

## 5. Tareas

- [x] Crear `StorefrontItemPriceResolver` y `CentralItemPriceResolver`
- [x] Resolver precio, nombre y SKU server-side en ambos checkouts
- [x] Quitar `price`, `product_name` y `sku` de la validación del checkout del storefront
- [x] Corregir el doble descuento de stock y el `catch` vacío
- [x] Quitar `is_approved`/`is_verified` del FormRequest y del DTO de reseñas
- [x] Crear `VerifiedPurchaseChecker` y usarlo en `CreateProductReviewUseCase`
- [x] Actualizar tests unitarios de reseñas y añadir casos de B2
- [x] Actualizar tests de checkout (central y storefront) y añadir casos negativos de B1
- [ ] `php artisan test`
- [ ] `npm run types`
- [ ] `vendor/bin/pint src/Marketplace/ src/CentralMarketplace/ src/Review/`
- [ ] Probar ambos checkouts en el navegador
- [ ] Commit: `fix(checkout,review): resolver precios server-side y quitar is_approved/is_verified del request`
- [ ] `git push origin <rama_actual>`
- [ ] Mover este documento a `planes/implementados/`

---

## 6. Verificación manual

**Debe seguir funcionando:**
1. Comprar en una tienda con y sin variante; el total coincide con los precios del catálogo.
2. Comprar en el marketplace central con productos de dos tiendas distintas.
3. Moderar una reseña desde el backoffice y verla aparecer en la ficha pública.

**Debe dejar de funcionar:**
4. Interceptar el POST del checkout y enviar `"price": 0.01` → el pedido se crea **por el precio real**.
5. Enviar `"product_name": "otro"` → la línea del pedido conserva el nombre del catálogo.
6. Comprar un producto oculto o inexistente → **422**, sin pedido creado.
7. `POST /api-tenant/review/create` con `"is_approved": true` → la reseña se crea **pendiente**.
8. Reseñar pasando el `order_id` de otro cliente → la reseña se crea **sin** la insignia de compra verificada.

---

## 7. Riesgo

**Medio.** Los cambios son acotados, pero tocan la ruta del dinero. Puntos a vigilar:

1. **El checkout central ahora depende de `central_products`.** Si un producto está a la venta en la tienda pero no sincronizado al catálogo central, deja de poder comprarse desde el marketplace (422 en vez de un pedido con precio inventado). Es el comportamiento correcto, pero conviene revisar la integridad del catálogo central antes de desplegar:
   ```sql
   SELECT COUNT(*) FROM central_products WHERE is_visible = 1;
   ```
   Si el número es sospechosamente bajo frente a los productos publicados, hay que correr la sincronización primero.
2. **Cualquier discrepancia de precio entre el catálogo central y la tienda pasa a ser visible.** Antes quedaba enmascarada porque el precio lo ponía el navegador; ahora el comprador paga lo que dice `central_products`. Es exactamente por qué E1/E2 suben de prioridad.
3. **Las reseñas dejan de auto-aprobarse.** Si alguna tienda daba por hecho que las reseñas se publicaban solas, ahora se acumularán en la cola de moderación. Es lo correcto y lo que el propio mensaje del frontend promete, pero conviene avisar a los comerciantes.

---

## 8. Trabajo de seguimiento identificado

1. **Exigir sesión de comprador en `review/create`** y derivar de ahí el `customer_id`, que hoy llega en el cuerpo y además falta en el formulario del storefront (sección 3). Bajo riesgo, cierra el último resquicio de B2.
2. **`ProductReview` permite reseñar sin haber comprado.** `order_id` es opcional: sin él la reseña se crea igual, sólo que sin insignia. Si el negocio quiere reseñas exclusivas de compradores, hay que exigirlo — es decisión de producto, no de seguridad.
3. **B3 sigue abierto:** el checkout del storefront aplica cupones comprobando sólo `is_active`, ignorando fechas, límite de uso y monto mínimo, e incrementa `used_count` antes de crear la orden y fuera de transacción. No se tocó en esta fase para no mezclar B3 con B1/B2, pero está en el mismo controlador y debería ir pronto.
4. **El nombre del producto en `CentralOrderItem` queda congelado del catálogo central**, que puede estar desactualizado (E2). Para el histórico de pedidos es lo deseable —el nombre en el momento de la compra—, pero conviene tenerlo presente al arreglar E2.
