# PLAN — Fase 3.3: La sesión del comprador entre dominios

> **Origen:** hallazgos G7, G10 y la mitad pendiente de G8 (punto 13 del plan de acción)
> **Severidad:** 🟠 los tres
> **Tamaño:** 1 middleware, 1 hook nuevo, 2 archivos de frontend, 1 archivo de test nuevo
> **Estado:** ✅ Implementado — 540 tests en verde, `npm run types` sin errores

Los tres describen el mismo síntoma desde ángulos distintos: **el comprador cree que tiene sesión en la tienda y no la tiene.** G7 es equivocarse de dominio, G10 es no darse cuenta de que la sesión caducó, y G8 es perder el formulario al ir a recuperarla.

---

## 1. G7 — `isCentralDomain()` clasificaba contando puntos

```ts
const parts = hostname.split('.');
if (parts.length <= 2) return true;   // "mitienda.com" → "central"
return false;                          // "www.mitienda.com" → "tenant"
```

**Escenario:** una tienda con dominio propio, `mitienda.com`, tomaba la rama central al iniciar sesión: **no** generaba ni consumía el token SSO, así que no se creaba sesión de cliente en la tienda. El usuario veía «Conectado con OwO Pass» en el checkout, pero el pedido se enviaba como invitado. Con `www.` delante, el comportamiento se invertía para el mismo sitio.

### Solución

La bandera la decide el servidor, que es quien inicializa la tenancy por dominio y por tanto el único que lo sabe:

```php
'is_central' => ! tenancy()->initialized,
```

En el frontend, `useIsCentralDomain()` lee la prop compartida de Inertia. La heurística del hostname **se conserva sólo como respaldo** para una página servida fuera de Inertia, pero ya no decide nada en el flujo real.

El test cubre justo el caso que rompía: un dominio de dos etiquetas que **es** una tienda.

---

## 2. G10 — La sesión caducada no se detectaba ni se limpiaba

```tsx
if (!isCentralDomain()) {
    const res = await CustomerAuthServices.getTenantSession();
    if (res && res.code === 200 && res.data?.authenticated && res.data?.customer) {
        setCustomer(res.data.customer);
    }
}
```

Si la tienda respondía «no autenticado», esta rama **no hacía nada**: ni reintentaba el SSO ni borraba el caché de `localStorage`.

**Escenario:** el cliente entró ayer y la cookie de la tienda expiró. Hoy la navbar y el checkout lo muestran logueado, pasa la puerta de autenticación del paso 3 y confirma el pedido; el backend lo trata como invitado o devuelve 401, y los `.catch(() => {})` del portal muestran listas vacías en lugar de «sesión expirada».

### Solución

La rama pasa a tener las dos salidas que le faltaban:

1. **Reintentar el SSO en silencio** (`tryRestoreTenantSession()`) si el cliente sigue con sesión en el dominio central. Es exactamente lo que ya hacía `login()` tras autenticar, así que el camino está probado.
2. **Limpiar todo** (`clearCachedSession()`) si eso tampoco funciona: estado y `localStorage` quedan como los de un visitante anónimo.

Lo que no puede volver a pasar es la tercera opción de antes: dejar al usuario creyendo que sigue dentro.

---

## 3. G8 — La recarga que perdía el formulario

La Fase 0.5 quitó el botón «Continuar como Invitado (Modo Pruebas)» que anulaba la puerta de autenticación. Quedaba la otra mitad: el botón de iniciar sesión hacía

```tsx
window.location.href = `/auth/login?redirect=${encodeURIComponent(window.location.pathname)}`;
```

una recarga completa que **perdía los datos ya escritos** —nombre, dirección, notas—, porque sólo el carrito está persistido.

### Solución

`StorefrontLayout` ya monta `CustomerAuthModal`, gobernado por `openAuthModal()` del contexto. El botón lo abre en la propia página: el formulario sigue donde estaba y el comprador vuelve al paso 3 con lo que había escrito.

---

## 4. Archivos tocados

- `app/Http/Middleware/HandleInertiaRequests.php` — prop compartida `is_central`
- `resources/js/types/index.d.ts`
- `resources/js/hooks/useIsCentralDomain.ts` (**nuevo**)
- `resources/js/contexts/CustomerAuthContext.tsx` — G7 y G10
- `resources/js/pages/marketplace/checkout/TenantCheckoutPage.tsx` — G8
- `tests/Feature/Marketplace/CentralDomainFlagTest.php` (**nuevo**, 2 casos)

---

## 5. Checklist de cierre

- [x] `php artisan test` → 540 pasan (3.149 aserciones)
- [x] `npm run types` → 0 errores
- [x] `./vendor/bin/pint` sobre los archivos tocados
- [x] `git add` + commit
- [x] `git push origin <rama_actual>`
- [x] Actualizar el bloque de estado de `AUDITORIA_BUGS_2026_08_21.md`
- [ ] Probar el flujo de sesión en el navegador — ⚠️ pendiente

---

## 6. Verificación manual

**Debe cambiar:**
1. Iniciar sesión desde una tienda con dominio propio de dos etiquetas → se hace el SSO y el pedido **no** sale como invitado.
2. Borrar la cookie de sesión de la tienda y recargar → o se restablece sola vía SSO, o la navbar pasa a mostrar «Iniciar sesión». Nunca se queda mostrando al usuario dentro.
3. Rellenar medio checkout sin sesión y pulsar «Iniciar Sesión» → se abre el modal **sin perder lo escrito**.

**Debe seguir funcionando:**
4. El login y el registro desde el dominio central.
5. El SSO al iniciar sesión desde un subdominio de tienda.

---

## 7. Riesgo

**Medio.**

1. **`is_central` depende de que la tenancy se inicialice por dominio.** Es así en todo el proyecto, pero si algún día se añade otra forma de identificar al inquilino (por cabecera, por ruta) la prop dejaría de ser fiable en ese camino.
2. **`tryRestoreTenantSession()` hace dos peticiones más** al cargar una página de tienda con sesión caducada. Sólo ocurre cuando la cookie no vale, así que no es el camino normal.
3. **Ahora se puede cerrar sesión sola.** Si el SSO falla por un problema pasajero de red, el caché se limpia y el usuario ve que se le cerró la sesión. Es preferible a mostrarle una sesión que no existe, pero es un cambio visible.
4. **La heurística del hostname sigue viva** como respaldo en `CustomerAuthServices.isCentralDomain()`. Ya no la usa el flujo de sesión, pero conviene no reintroducirla.

---

## 8. Trabajo de seguimiento identificado

1. **El perfil del cliente sigue cacheado en `localStorage`** (B4, parcial): nombre, email, teléfono, documento y direcciones quedan accesibles a cualquier XSS. El backend ya no confía en ese caché desde la Fase 0.3-D, así que es un problema de exposición, no de autorización.
2. **A8 sigue abierto y es del mismo flujo:** el token SSO no se ata al destino, así que se puede redimir en otra tienda. Esta fase hace que el SSO se use *más*, lo que sube la prioridad de cerrarlo.
3. **Los `.catch(() => {})` del portal del cliente** siguen haciendo que un error de red sea indistinguible de «no tienes pedidos» (G15).
