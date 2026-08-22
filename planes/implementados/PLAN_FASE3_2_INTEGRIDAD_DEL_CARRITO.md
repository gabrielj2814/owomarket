# PLAN — Fase 3.2: Integridad del carrito

> **Origen:** hallazgos G4, G5, G6, G11 y G12 (bloque G, punto 13 del plan de acción)
> **Severidad:** 🟠 G4, G5, G6 · 🟡 G11, G12
> **Tamaño:** 1 servicio nuevo, 1 controlador nuevo, 1 utilidad de frontend nueva, 6 archivos de frontend, 1 archivo de test nuevo
> **Estado:** ✅ Implementado — 538 tests en verde, `npm run types` sin errores

Los cinco son la misma clase de error: **el carrito guarda en el navegador cosas que sólo el servidor sabe**, y nadie las vuelve a comprobar.

---

## 1. G4 — Precio y stock congelados en `localStorage`

El carrito se rehidrata desde `localStorage` con el precio y la cantidad del día en que el comprador añadió cada producto, y **no había ninguna revalidación**. Desde la Fase 0.4 el servidor resuelve los precios por su cuenta e ignora los del navegador, así que el comprador descubría la diferencia al pagar: veía $50 y se le cobraban $80.

### Solución

Endpoint nuevo `POST /cart/revalidate` que contrasta el carrito con la base de la tienda, apoyado en `StorefrontItemPriceResolver` — el mismo que ya usa el checkout, así que **no hay dos fuentes de verdad**.

El servicio no decide nada: dice qué ha cambiado. Por línea devuelve el precio y el stock reales, y marca `price_changed`, `quantity_reduced` o `available: false`.

Las líneas que ya no se pueden servir **se marcan en lugar de hacer fallar la petición entera**: el comprador tiene que poder ver cuál es y quitarla, no encontrarse un carrito que no carga.

En el frontend, `CartContext.revalidate()` corrige el carrito y acumula un aviso por cada cambio, que `TenantCartPage` muestra al abrir la página. **Corregir el precio en silencio sería tan malo como no corregirlo**: si sube, el comprador tiene que enterarse antes de pagar.

Se revalida al montar, no en cada cambio: revalidar en cada clic en «+» dispararía una petición por pulsación, y el ajuste de cantidad ya lo acota `maxStock`.

---

## 2. G5 — `CentralCartContext.addItem` mutaba el estado previo

```tsx
const updated = [...prevItems];
updated[existingIndex].quantity += item.quantity;   // muta prevItems[i], no una copia
```

La copia del array es superficial: los objetos de dentro son los mismos. Con React 19 en StrictMode el updater se invoca dos veces, así que **añadir 2 unidades dejaba 4 en el carrito**. Y como la referencia del ítem no cambiaba, los hijos memoizados no se re-renderizaban.

### Solución

`map` que devuelve un objeto nuevo para la línea afectada.

---

## 3. G6 — Se podían añadir productos agotados, hasta 99 unidades

```tsx
onClick={() => setQuantity(Math.min(product.quantity || 99, quantity + 1))}
```

Con `quantity: 0`, el `||` convertía el tope en **99**. Ni el botón ni `handleAddToCart` comprobaban el stock. La ficha del storefront de tienda **sí** lo hacía: era una regresión de la página nueva del marketplace central.

### Solución

`availableStock` / `isOutOfStock` / `maxQuantity` derivados del stock real, con guardas en los cuatro botones y en `handleAddToCart`/`handleBuyNow`. Y la etiqueta deja de mentir: donde ponía «✓ Stock Disponible: 0» ahora pone «Agotado».

---

## 4. G11 — Pedido creado y carrito vaciado con redirección no validada

El checkout central navegaba a `res.data.redirect_url` sin comprobar que existiera, con el carrito ya borrado: si faltaba, el comprador acababa en `/undefined`, sin carrito y sin número de pedido.

El del inquilino ya comprobaba la URL, pero le quedaba el caso peor: **si la respuesta era de éxito pero faltaba la URL, se mostraba «Error al procesar la orden. Intenta de nuevo» con la orden ya creada** → el cliente reintentaba y pagaba dos veces.

### Solución

Los dos checkouts distinguen tres casos, no dos: éxito con URL, **éxito sin URL** y error. En el segundo se vacía el carrito, se da el número de pedido y se dice explícitamente que **no vuelva a pagar**.

---

## 5. G12 — Lectura de `localStorage` sin validar

```tsx
const saved = localStorage.getItem(storageKey);
return saved ? JSON.parse(saved) : [];
```

El `try/catch` no ayudaba: `JSON.parse` **no lanza** con `"null"`, `"{}"` ni con ítems de una versión anterior. El resultado era `items.reduce is not a function`, o un subtotal `NaN` que dejaba toda la tienda mostrando «$ NaN» sin forma de recuperarse salvo limpiar el navegador a mano.

### Solución

`utils/cartStorage.ts` nuevo, compartido por los dos contextos: valida después del parseo, ítem a ítem (`Number.isFinite(price)`, `quantity > 0`, identificadores presentes) y descarta lo que no encaje. Ante la duda devuelve `[]`: **un carrito vacío es recuperable, uno corrupto no**.

Y la clave va versionada (`owomarket_cart_v2_…`), así que un cambio futuro de la forma del carrito descarta lo viejo en vez de intentar interpretarlo.

---

## 6. Archivos tocados

**Backend:**
- `src/Marketplace/Application/Service/StorefrontCartRevalidator.php` (**nuevo**)
- `src/Marketplace/Infrastructure/Http/Controller/RevalidateStorefrontCartPOSTController.php` (**nuevo**)
- `src/Marketplace/Infrastructure/Http/Routes/tenant.php`

**Frontend:**
- `resources/js/utils/cartStorage.ts` (**nuevo**)
- `resources/js/Services/StorefrontServices.ts`
- `resources/js/contexts/CartContext.tsx`
- `resources/js/contexts/CentralCartContext.tsx`
- `resources/js/pages/marketplace/cart/TenantCartPage.tsx`
- `resources/js/pages/marketplace/product/CentralProductDetailPage.tsx`
- `resources/js/pages/marketplace/checkout/TenantCheckoutPage.tsx`
- `resources/js/pages/marketplace/checkout/CentralCheckoutPage.tsx`

**Tests:**
- `tests/Feature/Marketplace/StorefrontCartRevalidationTest.php` (**nuevo**, 6 casos)

---

## 7. Checklist de cierre

- [x] `php artisan test` → 538 pasan (3.127 aserciones)
- [x] `npm run types` → 0 errores
- [x] `./vendor/bin/pint` sobre los archivos tocados
- [x] `git add` + commit
- [x] `git push origin <rama_actual>`
- [x] Actualizar el bloque de estado de `AUDITORIA_BUGS_2026_08_21.md`
- [ ] Probar el carrito en el navegador — ⚠️ pendiente

---

## 8. Verificación manual

**Debe cambiar:**
1. Añadir algo al carrito, cambiar el precio en el backoffice y volver al carrito → aviso del cambio y precio corregido.
2. Dejar el stock por debajo de lo que hay en el carrito → la cantidad se recorta y se avisa.
3. Ocultar o borrar el producto → la línea se retira con su motivo.
4. Ficha de producto agotado en el marketplace central → «Agotado», botones deshabilitados.
5. Añadir dos veces el mismo producto al carrito central → suma la cantidad correcta, no el doble.
6. `localStorage.setItem('owomarket_cart_v2_...', '"null"')` → el carrito arranca vacío en vez de romperse.

**Debe seguir funcionando:**
7. Comprar con el carrito al día, con y sin cupón.

---

## 9. Riesgo

**Medio-bajo.**

1. **Los carritos guardados hoy se pierden.** La clave pasa a `_v2`, así que todo el mundo empieza con el carrito vacío una vez. Es el precio de poder descartar formas antiguas sin adivinar; en desarrollo no importa.
2. **Una petición más al abrir el carrito.** Es una consulta por línea contra la base de la tienda. Con carritos normales no se nota, pero el endpoint acepta hasta 100 líneas.
3. **`/cart/revalidate` es público**, como el resto del storefront. No expone nada que la ficha de producto no devuelva ya, pero **no tiene límite de tasa** — como ningún otro endpoint del proyecto (N18).
4. **El carrito puede corregir el precio hacia arriba.** Es lo correcto, pero cambia lo que el comprador ve; conviene que el aviso se lea bien.

---

## 10. Trabajo de seguimiento identificado

1. **El checkout no revalida al enviar.** Se revalida al abrir el carrito, pero entre eso y el pago puede pasar tiempo. El servidor resuelve los precios de todos modos (Fase 0.4), así que no se cobra mal — pero el comprador puede pagar viendo un total viejo. Conviene revalidar también al entrar al checkout.
2. **El carrito central no se revalida.** Esta fase cubre el storefront de tienda; `CentralCartContext` sigue con precios congelados. Necesita su propio endpoint contra `central_products`.
3. **G15 sigue abierto** y toca el carrito: `CustomerOrdersPage` reconstruye el carrito con `tenant_name: item.tenant_id` y `slug: item.product_id`.
