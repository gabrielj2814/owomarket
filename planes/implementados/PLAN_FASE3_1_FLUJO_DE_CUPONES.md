# PLAN — Fase 3.1: El flujo de cupones, de punta a punta

> **Origen:** hallazgos G2 y G3 (punto 13 del plan de acción) más B3 y C6, que son la mitad de backend del mismo flujo
> **Severidad:** 🔴 B3 crítico · 🔴 G2 crítico · 🟠 C6 y G3 altos
> **Tamaño:** 1 servicio nuevo, 1 controlador, 3 archivos de frontend, 1 tipo, 1 archivo de test nuevo
> **Estado:** ✅ Implementado — 532 tests en verde, `npm run types` sin errores

Arrancar la Fase 3 sólo por el frontend habría sido un error: **el arreglo de G2 hace que los cupones se apliquen por primera vez, y el checkout no los validaba.** Habría pasado de «ningún cupón funciona» a «todos los cupones funcionan, incluidos los caducados». Por eso esta fase se lleva también B3 y C6.

---

## 1. G2 — Ningún cupón se podía aplicar en la tienda

`CouponServices.validate` devuelve `response.data` —el **sobre** del backend, `{status, code, message, data}`— pero estaba tipada como `ApiResponse<T>`, que en este proyecto describe la respuesta **completa** de axios. El componente desenvolvía una capa de más:

```tsx
const apiData = res.data;                              // esto ya es la carga útil
if (apiData && apiData.code === 200 && apiData.data) { // .code y .data no existen aquí
```

La condición era `undefined` siempre, así que el usuario escribía un cupón perfectamente válido y leía «El cupón ingresado no es válido o ha expirado». **Ningún cupón funcionaba, y el tipo lo tapaba.**

### Solución

El tipo que describe el sobre ya existía: `Data<T>` en `types/ResponseApi.d.ts`. La firma pasa a `Promise<Data<ValidateCouponResponse>>` y el componente lee `res.code` y `res.data` directamente. Con eso TypeScript pasa a comprobar de verdad lo que antes se creía.

---

## 2. G3 — El descuento se recalculaba en el cliente

```tsx
if (coupon.type === 'percentage') return Math.round((subtotal * coupon.value) / 100);
```

El backend devolvía `discount_amount`, se guardaba en `AppliedCoupon.discountAmount` y **no se leía en ningún sitio** (0 usos en todo `resources/js`). Además ese `Math.round` redondea a unidades enteras: un 10% sobre $45,50 son $4,55 en el backend y $5,00 en pantalla.

### Solución

Manda el importe del backend. Y para el problema de fondo —«tras aplicar el cupón, cambiar cantidades re-escala el descuento solo, saltándose mínimos y topes que el backend sí valida»— se guarda contra qué subtotal se validó:

- `AppliedCoupon.validatedSubtotal` nuevo.
- Si el subtotal actual no coincide, el descuento **no se reescala: se descarta**, y el cupón se retira solo para que el comprador vea que tiene que volver a aplicarlo.

Reescalar en el cliente es precisamente lo que no se puede hacer: sólo el backend sabe si el nuevo subtotal sigue cumpliendo `min_order_amount`.

---

## 3. B3 — El checkout aplicaba cupones sin validar nada

El flujo real de compra **no usaba** `ValidateCouponUseCase` ni `Coupon::validateUsability()`. Sólo comprobaba `is_active`:

```php
$coupon = Coupon::where('code', $couponCode)->where('is_active', true)->first();
if ($coupon) {
    ...
    $coupon->increment('used_count');
}
```

Se ignoraban `valid_from`/`valid_to`, `usage_limit` y `min_order_amount`. Un cupón `NAVIDAD2025` con `valid_to = 2025-12-31` y su límite agotado seguía descontando en agosto de 2026, de forma ilimitada.

### Solución

El checkout pasa por `ValidateCouponUseCase` —la misma validación que ya usaba el endpoint `/validate` del carrito— sobre el **subtotal calculado en el servidor**, no el que envía el navegador (hallazgo B1, Fase 0.4).

**Un cupón inválido rechaza el pedido con 422 en vez de cobrarlo sin descuento en silencio.** El comprador pulsó «pagar» contando con ese precio: tiene que enterarse, no descubrirlo en el resumen. Es el mismo criterio que la Fase 1.3 aplicó al stock.

---

## 4. C6 — `increment()` sin condición permitía superar el límite

`increment()` es atómico a nivel de columna pero **no comprueba el techo**, y la validación previa ocurría en una sentencia aparte, así que N peticiones paralelas la pasaban todas.

### Solución

`CouponRedeemer` nuevo, hermano de `StockReserver`: la comprobación y el incremento son **la misma sentencia**, y se mira el número de filas afectadas.

```php
Coupon::where('code', $code)
    ->where('is_active', true)
    ->whereRaw('(usage_limit IS NULL OR used_count < usage_limit)')
    ->increment('used_count');
```

Si devuelve 0, otro comprador agotó el cupón en el hueco entre validar y canjear, y el pedido se rechaza con 409. Corre dentro de la transacción del checkout, así que **un pedido que falle no deja el cupón gastado** — algo que el código anterior sí hacía, porque incrementaba antes de crear la orden y fuera de transacción.

---

## 5. Archivos tocados

**Backend:**
- `src/Marketplace/Application/Service/CouponRedeemer.php` (**nuevo**)
- `src/Marketplace/Infrastructure/Http/Controller/CreateStorefrontOrderPOSTController.php`
- `src/Product/Application/UseCase/SyncProductToCentralMarketplaceUseCase.php` — las relaciones se cargan sólo si el producto está publicado; el observador de la Fase 2.2 corre en cada guardado, incluido el descuento de stock del checkout

**Frontend:**
- `resources/js/Services/CouponServices.ts`
- `resources/js/pages/marketplace/cart/TenantCartPage.tsx`
- `resources/js/contexts/CartContext.tsx`
- `resources/js/types/models/Cart.d.ts`

**Tests:**
- `tests/Feature/Marketplace/StorefrontCheckoutCouponTest.php` (**nuevo**, 6 casos)

> El diff de `CreateStorefrontOrderPOSTController.php` es más grande de lo que sugiere el cambio: Pint reindentó el cuerpo del `DB::transaction`, que estaba mal indentado desde la Fase 1.3. Ignorando espacios, el cambio real son unas 30 líneas.

---

## 6. Checklist de cierre

- [x] `php artisan test` → 532 pasan (3.106 aserciones)
- [x] `npm run types` → 0 errores
- [x] `./vendor/bin/pint` sobre los archivos tocados
- [x] `git add` + commit
- [x] `git push origin <rama_actual>`
- [x] Actualizar el bloque de estado de `AUDITORIA_BUGS_2026_08_21.md`
- [ ] Probar el flujo completo en el navegador — ⚠️ pendiente
- [x] Mover este documento a `planes/implementados/`

---

## 7. Verificación manual

**Debe cambiar:**
1. Aplicar un cupón válido en el carrito → **se aplica**, que es lo que no ocurría.
2. El descuento mostrado coincide al céntimo con el del backend (probar un 10% sobre un subtotal con decimales, p. ej. $45,50 → $4,55, no $5,00).
3. Aplicar un cupón y luego cambiar cantidades → el cupón se retira y hay que volver a aplicarlo.
4. Pagar con un cupón caducado o agotado → **422** con el motivo, y no se crea el pedido.
5. Pagar por debajo del monto mínimo del cupón → 422 con el mínimo exigido.

**Debe seguir funcionando:**
6. Pagar sin cupón.
7. El panel de cupones del backoffice de la tienda.

---

## 8. Riesgo

**Medio.**

1. **El checkout puede rechazar pedidos que antes aceptaba.** Es la corrección del bug, pero cambia el comportamiento: un comprador con un cupón caducado guardado en el carrito ahora recibe un 422 en vez de pagar el precio completo. Es lo correcto, pero conviene saberlo.
2. **Los `used_count` actuales están inflados.** El código anterior incrementaba antes de crear la orden y fuera de transacción, así que cada pedido fallido dejaba un uso consumido de más. Con la base de desarrollo reiniciada esto deja de importar; en un despliegue real habría que revisarlo.
3. **El carrito retira el cupón al cambiar cantidades.** Es deliberado, pero un comprador que ajuste el carrito varias veces tendrá que reaplicar el código cada vez. Si resulta molesto, la solución no es reescalar en el cliente sino revalidar contra el backend automáticamente.

---

## 9. Trabajo de seguimiento identificado

1. **`usage_limit_per_customer` sigue sin aplicarse.** `validateUsability()` comprueba el límite global pero no el de por cliente, y la tabla `orders` no guarda el cupón usado, así que hoy no hay con qué contarlo. El checkout ya escribe `coupon_code` en el `metadata` del pedido para que el dato exista, pero contar sobre JSON es frágil: hace falta una columna `coupon_code` indexada en `orders`.
2. **El checkout central no aplica cupones en absoluto.** Esta fase arregla el del storefront de cada tienda. `CreateUnifiedCentralOrderUseCase` recibe un descuento pero nadie lo valida ni lo consume.
3. **El resto de servicios del frontend siguen mal tipados igual que `validate`.** `CouponServices.filtrar`, `consultById` y compañía devuelven `response.data` declarando `ApiResponse<T>`. Los consumidores lo compensan a mano (`CouponIndexPage` hace `res?.data ?? res?.data?.data` y castea a `any`). Es la misma trampa de G2 esperando a la siguiente página: merece una pasada que cambie todos a `Data<T>`.
4. **Quedan del bloque G:** G4 (precio y stock congelados en `localStorage`), G5, G6, G7, G9, G10, G11, G12, G13, G14 y G15.
