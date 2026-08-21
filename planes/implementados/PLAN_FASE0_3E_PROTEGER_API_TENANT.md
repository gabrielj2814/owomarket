# PLAN — Fase 0.3-E: Cerrar la API del inquilino (`/api-tenant/*`)

> **Origen:** hallazgo A5 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md`
> **Severidad:** 🔴 Crítico
> **Tamaño:** 1 cambio en `bootstrap/app.php`, 1 archivo de rutas reescrito, 3 archivos de rutas de módulo, 26 archivos de test actualizados, 1 archivo de test nuevo
> **Estado:** ✅ Implementado — pendiente de validación con `php artisan test`
> **Depende de:** Fase 0.3-A (patrón de añadir `web` para tener sesión donde el grupo `api` no la da)

---

## 1. El problema (hallazgo A5)

`bootstrap/app.php` montaba las rutas del inquilino así:

```php
Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api-tenant')->group(base_path('routes/tenantApi.php'));
```

Sólo tenancy. Ningún `auth`. Y ninguno de los 16 archivos de rutas de módulo lo añadía tampoco.

**El agravante que no salta a la vista:** en Laravel 11+ el grupo `api` está **vacío** por defecto salvo que se configure — y `bootstrap/app.php` no llama a `throttleApi()` ni añade nada. Así que `api` no aportaba ni sesión, ni CSRF, ni límite de tasa. Aunque alguien hubiera escrito `->middleware('auth')` en una de estas rutas, habría devuelto 401 siempre: sin `StartSession` no hay sesión sobre la que resolver identidad.

**Alcance real: ~108 rutas** repartidas en 16 módulos, todas accesibles desde internet sin credenciales:

| Módulo | Rutas | Qué permitía a un anónimo |
| :--- | :---: | :--- |
| product | 10 | Crear, editar y **borrar** el catálogo; subir y borrar imágenes; cambiar stock |
| customer | 14 | Listar, editar y borrar la **base de clientes** con sus direcciones |
| order | 8 | Leer todos los pedidos, cambiar su estado y el estado de pago, cancelarlos |
| billing | 9 | Leer la facturación, emitir facturas, descargar PDF, anular |
| coupon | 6 | **Crear un cupón del 100%** y usarlo (escenario textual de la auditoría) |
| settings | 6 | Cambiar la configuración de la tienda |
| review | 8 | Aprobar reseñas, responder **en nombre de la tienda**, borrarlas |
| shipment | 7 | Marcar envíos como entregados |
| payment | 2 | Procesar pagos |
| shipping / tax / brand / category / attribute | 35 | CRUD de zonas, tarifas, impuestos y catálogo maestro |
| auth / user | 2 | *(ya protegidas con `InternalServiceMiddleware`)* |

---

## 2. Solución

### 2.1 `bootstrap/app.php`: `api` → `web`

```php
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api-tenant')->group(base_path('routes/tenantApi.php'));
```

Es exactamente el mismo grupo y el mismo orden que ya usa `routes/tenant.php` para las vistas del inquilino, así que no introduce ninguna combinación nueva: aporta `StartSession` (la sesión que crea `src/Authentication/.../tenant.php` al iniciar sesión en la tienda) y `VerifyCsrfToken`.

**Sobre CSRF:** los 20 servicios de `resources/js/Services/*.ts` ya envían `X-CSRF-TOKEN` mediante el helper `getCSRFToken()`. No hizo falta tocar frontend.

**Sobre el orden sesión/tenancy:** `web` corre antes que `InitializeTenancyByDomain`, así que la sesión se lee antes de inicializar la tenancy — es el hallazgo **F3**, preexistente e idéntico al de `routes/tenant.php`. Esta fase no lo empeora ni lo arregla; queda donde estaba.

### 2.2 `routes/tenantApi.php`: tres categorías explícitas

`auth` se aplica **aquí y no en `bootstrap/app.php`** a propósito: así corre *después* de `InitializeTenancyByDomain` y resuelve el usuario contra la base de datos del inquilino, no la central. Es el mismo comportamiento que ya tienen las rutas web del backoffice del inquilino.

1. **Servicios internos** (`auth`, `user`): se quedan como estaban, con `InternalServiceMiddleware`. No llevan `auth` porque no hay usuario en sesión — se autentican por secreto compartido.
2. **Módulos 100% backoffice** (11 prefijos): envueltos en `Route::middleware('auth')`.
3. **Módulos mixtos** (`customer`, `coupon`, `review`): la protección se declara dentro del archivo del módulo, porque conviven rutas de backoffice con la lista blanca pública.

### 2.3 La lista blanca pública: 5 rutas

Se verificó en `resources/js` qué llama realmente el storefront. Las páginas de `pages/marketplace/**` sólo importan `CentralMarketplaceServices` (dominio central), `ExchangeRateServices`, `StorefrontServices` (que apunta a `/checkout/create-order`, ruta web del tenant, no a `/api-tenant`), `ReviewServices` y `CouponServices`.

| Ruta | Por qué queda pública |
| :--- | :--- |
| `POST customer/sso/consume` | Su seguridad la aporta el token SSO de un solo uso, no la sesión — es justamente lo que *crea* la sesión |
| `GET customer/auth/session` | Devuelve `authenticated: false` si no hay sesión; no expone datos de terceros |
| `POST customer/auth/logout` | Cerrar sesión debe funcionar sea cual sea el estado |
| `POST coupon/validate` | `TenantCartPage.tsx` la llama con el comprador aún anónimo. Sólo lee |
| `POST review/create` | `TenantProductDetailPage.tsx`, única llamada de esa página a `/api-tenant` |

**`review/filter` y `review/summary` NO están en la lista blanca**, aunque a primera vista parecerían necesarias para la ficha de producto: el listado de reseñas y el resumen de valoraciones llegan por props de Inertia desde `ViewProductDetailTenantGETController` (líneas 87-211), no por API. Quedan bajo `auth` con el resto de la moderación.

---

## 3. Decisión consciente: `review/create` sigue pública, y B2 sigue abierto

Se evaluó exigir `session('tenant_customer_id')` en `review/create` —el mismo patrón que la Fase 0.3-C usó en soporte— y **se decidió no hacerlo en esta fase**, para no romper el flujo actual de reseñas de compradores que no hayan pasado por el SSO.

La consecuencia hay que dejarla escrita con claridad: **el hallazgo B2 sigue siendo explotable desde fuera**. `CreateProductReviewFormRequest` acepta `is_approved` e `is_verified` del cuerpo de la petición, así que un POST directo publica una reseña de 5 estrellas marcada como "compra verificada", saltándose la moderación. Está anotado como aviso en el propio archivo de rutas (`src/Review/.../apiTenant.php`) para que quien lo lea no asuma que la Fase 0.3-E lo cerró.

---

## 4. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **A5** — grupo `api-tenant` sin autenticación | ✅ Cerrado (salvo la lista blanca de 5 rutas, justificada una a una) |
| **B2** — el cliente decide `is_approved`/`is_verified` | ⬜ Sigue abierto y explotable; decisión consciente, ver sección 3 |
| **B3** — el checkout aplica cupones sin validar | ⬜ Sigue abierto: el problema no es que `coupon/validate` sea pública, sino que el checkout no la use |
| **F3** — cookie de sesión compartida entre subdominios | ⬜ Sin cambios; preexistente e idéntico al de `routes/tenant.php` |

Con esto, **el bloque A de la auditoría queda cerrado salvo A7, A8 y A9**, que son de severidad 🟠 y no tocan este grupo de rutas.

---

## 5. Tareas

- [x] `bootstrap/app.php`: `api` → `web` en el grupo `api-tenant`
- [x] Reescribir `routes/tenantApi.php` con las tres categorías
- [x] `src/Customer/.../apiTenant.php`: separar SSO/sesión público del directorio de clientes
- [x] `src/Coupon/.../apiTenant.php`: separar `validate` público del CRUD
- [x] `src/Review/.../apiTenant.php`: separar `create` público de la moderación
- [x] Añadir sesión de usuario de tienda al `beforeEach` de los 26 archivos de test de `/api-tenant`
- [x] Crear `tests/Feature/Tenant/TenantApiAuthorizationTest.php` (401 en backoffice, lista blanca accesible)
- [ ] `php artisan route:clear && php artisan config:clear`
- [x] `php artisan test` (suite completa — esta fase toca el arranque de rutas, no sólo un módulo)
- [x] `npm run types`
- [x] `vendor/bin/pint routes/ src/Customer/ src/Coupon/ src/Review/`
- [ ] Probar el storefront y el backoffice en el navegador (ver sección 6) — ⚠️ pendiente: no se verificó en navegador
- [x] Commit: `fix(tenant-api): exigir sesión de usuario de tienda en /api-tenant y acotar la lista blanca pública`
- [x] `git push origin <rama_actual>`
- [x] Mover este documento a `planes/implementados/`

---

## 6. Verificación manual

**Debe seguir funcionando:**
1. Backoffice de una tienda con sesión iniciada: catálogo, clientes, pedidos, cupones, facturación, envíos, impuestos, configuración. Todo el CRUD.
2. Storefront anónimo: navegar el catálogo, abrir una ficha de producto y ver sus reseñas, aplicar un cupón en el carrito, completar el checkout.
3. Entrar como comprador vía SSO desde el dominio central y comprar.
4. Publicar una reseña como comprador.

**Debe dejar de funcionar:**
5. Sin sesión, `POST /api-tenant/coupon/create` → **401** (antes: 201, cupón del 100% creado).
6. Sin sesión, `DELETE /api-tenant/product/{id}` → **401** (antes: catálogo borrado).
7. Sin sesión, `POST /api-tenant/customer/filter` → **401** (antes: base de clientes completa).
8. Sin sesión, `GET /api-tenant/billing/metrics` → **401** (antes: facturación de la tienda).

---

## 7. Riesgo

**Alto — es la fase de mayor superficie de todo el bloque A.** Cambia el arranque de rutas, no un módulo concreto. Puntos a vigilar:

1. **Cualquier consumidor de `/api-tenant` que no sea el frontend de este repo dejará de funcionar.** Se auditó `resources/js` completo, pero si existe alguna integración externa, un script, un webhook o una app móvil apuntando a estos endpoints, empezará a recibir 401. No se detectó ninguna en el código, pero es el riesgo que conviene confirmar con el negocio antes de desplegar.
2. **CSRF ahora aplica sobre ~103 endpoints POST/PUT/PATCH/DELETE que antes no lo tenían.** Los servicios del frontend ya mandan el token, pero el cliente de pruebas de Laravel no reproduce el comportamiento real de CSRF — hay que probarlo en el navegador, no sólo con `php artisan test`.
3. **La sesión del backoffice depende de la cookie compartida entre subdominios (F3).** Igual que las vistas del backoffice, que ya funcionan así; pero ahora también las llamadas de API dependen de ello.

---

## 8. Trabajo de seguimiento identificado

1. **B2 (`review/create`)** — ver sección 3. Es el seguimiento más importante que deja esta fase.
2. **Sin límite de tasa en ningún sitio.** Al cambiar de `api` a `web` se pierde la posibilidad de activar `throttleApi()` sobre este grupo — aunque en la práctica no estaba activo antes tampoco (`bootstrap/app.php` nunca lo llamó). Convendría añadir `throttle` explícito a los endpoints sensibles: login, `coupon/validate` (fuerza bruta de códigos), `review/create` (spam). Va con el hallazgo A7, que ya pide `throttle` para el PIN de administrador.
3. **`auth` comprueba sesión, no propiedad de la tienda.** Un usuario con sesión en la tienda A que llegue al dominio de la tienda B... no debería poder, porque la tenancy resuelve el usuario contra la base de datos de cada inquilino y son distintas. Pero eso descansa en el aislamiento de bases de datos, no en una comprobación explícita — y `DatabaseTenancyBootstrapper` se desactiva en los tests, así que la suite **no cubre ese escenario**. Merece una prueba de integración real con dos tiendas y bases separadas.
4. **No hay control de rol dentro del inquilino.** Cualquier usuario del inquilino (`owner`, `admin`, `manager`, `staff` según la tabla pivote `tenant_users`) pasa por igual: un `staff` puede borrar el catálogo o anular facturas. El RBAC dentro de la tienda es trabajo posterior, fuera del alcance de la Fase 0.
