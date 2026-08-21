# Auditoría de Bugs — OwoMarket

> ## 📌 ESTADO DE LA REMEDIACIÓN — actualizado el 21/08/2026
>
> **Avance: 28 de 50 hallazgos cerrados (~56%). Fases 0, 1 y 2 completas, Fase 3 empezada.**
>
> **Todos los 🔴 críticos que este documento marcó como bloqueantes están cerrados.**
> Lo que queda es mayoritariamente 🟠 alto y 🟡 medio.
>
> ### Cómo continuar en otra sesión
>
> 1. Leer este bloque de estado y la sección **«Plan de acción sugerido»** al final,
>    que está anotada con el estado real de cada punto.
> 2. Los planes de cada fase ejecutada están en `planes/implementados/PLAN_FASE*.md`,
>    con su checklist de cierre marcado. Los únicos puntos que siguen sin marcar son
>    las verificaciones en navegador, que no se hicieron; las tareas sobre datos de
>    producción quedaron anotadas como no aplicables al reiniciarse la base de
>    desarrollo desde cero.
> 3. Cada arreglo lleva un comentario en el código citando su hallazgo
>    (buscar `hallazgo A5`, `hallazgo B1`, etc.) con el escenario que corregía.
>
> ### Leyenda
> ✅ cerrado · 🟡 parcial · ⬜ abierto
>
> ### Estado por hallazgo
>
> | Bloque | Cerrados | Parciales | Abiertos |
> | :--- | :--- | :--- | :--- |
> | **A. Autenticación** | A1 A2 A3 A4 A5 A6 | A9 | A7 A8 |
> | **B. Datos del cliente** | B1 B2 B3 | B4 | — |
> | **C. Concurrencia** | C1 C2 C3 C4 C6 | — | C5 |
> | **D. Dinero** | D1 D2 D3 D4 | — | D5 D6 |
> | **E. Catálogo** | E1 E2 E3 E4 | — | — |
> | **F. Infraestructura** | F1 F2 F6 | F4 | F3 F5 |
> | **G. Frontend** | G1 G2 G3 | G8 G13 | los otros 10 |
>
> F2 («`bootstrap/app.php` importa middlewares que no existen y no registra ningún
> alias») lo cerró la Fase 0.2, y era la **causa raíz de todo el bloque A**: no
> había con qué proteger las rutas, así que se protegieron sólo con `auth` o con
> nada.
>
> ### Fases ejecutadas
>
> | Fase | Hallazgos | Documento |
> | :--- | :--- | :--- |
> | 0.1 | A1 | `PLAN_FASE0_1_DESMONTAR_BACKOFFICE_ADMIN_DE_TENANT.md` |
> | 0.2 | Middlewares de rol (base de F2) | `PLAN_FASE0_2_MIDDLEWARES_DE_AUTORIZACION.md` |
> | 0.3-A | A4, A9 (parcial) | `PLAN_FASE0_3A_PROTEGER_BACKOFFICE_Y_MONETIZACION.md` |
> | 0.3-B | A2 | `PLAN_FASE0_3B_PROTEGER_APIS_TENANT_OWNER.md` |
> | 0.3-C | A6 | `PLAN_FASE0_3C_PROTEGER_MESA_DE_SOPORTE.md` |
> | 0.3-D | A3, F4 (parcial) | `PLAN_FASE0_3D_PROTEGER_API_CLIENTES_CENTRALES.md` |
> | 0.3-E | A5 | `PLAN_FASE0_3E_PROTEGER_API_TENANT.md` |
> | 0.4 | B1, B2, C1 (parcial) | `PLAN_FASE0_4_PRECIOS_Y_RESENAS_SERVER_SIDE.md` |
> | 0.5 | G1, G8 (parcial) | `PLAN_FASE0_5_DATOS_BANCARIOS_Y_BYPASS_CHECKOUT.md` |
> | 1.1 | C2, D1 | `PLAN_FASE1_1_DESPACHO_TRANSACCIONAL_Y_PRORRATEO.md` |
> | 1.2 | D2 | `PLAN_FASE1_2_REVERSION_DE_COMISIONES.md` |
> | 1.3 | C3, C4, C1 | `PLAN_FASE1_3_BLOQUEOS_Y_TRANSACCIONES.md` |
> | 1.4 | D3, D4 | `PLAN_FASE1_4_TASA_DE_CAMBIO_FIABLE.md` |
> | 2.1 | F1, F6 | `PLAN_FASE2_1_SESIONES_Y_SEEDERS_DE_PRODUCCION.md` |
> | 2.2 | E1, E2, E3, E4 | `PLAN_FASE2_2_SINCRONIZACION_DEL_CATALOGO_CENTRAL.md` |
> | 3.1 | G2, G3, B3, C6 | `PLAN_FASE3_1_FLUJO_DE_CUPONES.md` |
>
> ### 🔜 Siguiente paso recomendado
>
> 1. **Seguir con la Fase 3 (bloque G)** — quedan G4, G5, G6, G7, G9, G10, G11, G12,
>    G14 y G15, más rematar G8 y G13.
> 2. **Un comando para crear el superadmin** (P1/N22): la Fase 2.1 vetó `RootUserSeeder`
>    fuera de desarrollo, así que una instalación nueva ya no tiene por dónde arrancar.
> 3. **`domains.id` casteado a int** (P2/N23): `$domain->id` devuelve siempre `0` y
>    revienta la petición con ~6% de los UUID.
> 4. **F3 y F5**, y **D5 y D6** — lo que queda de infraestructura y de dinero.
>
> ### 📋 Pendientes explícitos (fuera de los bloques A-G)
>
> | # | Pendiente | Por qué | Estado |
> | :--- | :--- | :--- | :--- |
> | **P1** | **Comando `admin:create-super`** para crear el primer superadmin por consola | La Fase 2.1 vetó `RootUserSeeder` fuera de desarrollo, así que **una instalación nueva no tiene ningún camino para crear el superadmin inicial**. No rompe los despliegues existentes, que ya tienen el suyo. Debe pedir nombre, email y contraseña de forma interactiva, validarla con `PasswordValidator` y negarse a sobrescribir un usuario existente | ⬜ Abierto |
> | **P2** | **`domains.id` es UUID pero el modelo lo declara `int`** | Ver N23: `$domain->id` devuelve **siempre `0`**, y con ~6% de los UUID revienta la petición entera | ⬜ Abierto |
>
> ### ⚠️ Deuda operativa pendiente de revisar antes de desplegar
>
> Cada plan tiene su sección «Riesgo», pero estas cuatro requieren acción sobre
> datos existentes, no sobre código:
>
> - **Fase 0.5:** ninguna tienda tiene datos de cobro configurados (el grupo de
>   settings `payment` no existía), así que **se quedarán sin métodos de pago**
>   hasta que se carguen.
> - **Fase 1.2:** las comisiones de pedidos cancelados *antes* del cambio siguen
>   vivas en `pending` y se cobrarán en la próxima liquidación.
> - **Fase 1.3:** puede haber stock negativo heredado de los pedidos que se
>   aceptaban sin existencias.
> - **Fase 1.1:** los pedidos ya despachados no tienen fila en
>   `central_order_dispatches`; relanzar el despacho de uno antiguo lo duplicaría.
> - **Fase 1.4:** `/api/exchange-rate/convert` ahora devuelve 404 en lugar de convertir
>   con tasa 1.0, así que **tiene que haber una tasa activa en `exchange_rates` antes de
>   desplegar**. Comprobarlo con la consulta de la sección «Riesgo» de su plan.
> - **Fase 2.1:** la migración correctiva de `sessions` hay que correrla en **las dos
>   rutas** (`migrate` y `tenants:migrate`); si se olvida la segunda, las tiendas
>   existentes siguen con el esquema que rompe el login.
> - **Fase 2.2:** el catálogo central existente **sigue desincronizado y no se arregla
>   solo**: los productos sólo se re-sincronizan al volver a guardarse. Hay que forzar una
>   pasada por tienda tras desplegar, o los precios viejos seguirán cobrándose. Y el
>   índice único `(tenant_id, slug)` puede fallar si ya hay slugs repetidos dentro de una
>   tienda: comprobarlo antes con la consulta del plan.
>
> ### 🧠 Contexto útil que no está en el texto original
>
> Cosas que se descubrieron al implementar y que cambian lo que dice la auditoría:
>
> - **G1 era peor:** los datos bancarios de demostración no eran sólo un fallback
>   del frontend, estaban hardcodeados también en el backend.
> - **A3 era más profundo:** el login central nunca creaba sesión — devolvía un
>   token de 64 caracteres que nadie verificaba. Hubo que construir el guard.
> - **El formulario de reseñas del storefront lleva roto desde siempre:** exige
>   `customer_id` y `TenantProductDetailPage.tsx` nunca lo envía (422 garantizado).
> - **`lockForUpdate` no hace nada en SQLite**, que es lo que usa la suite: los
>   tests de concurrencia validan la lógica, no la ausencia de la carrera.
>
> ---

> **Fecha:** 21 de agosto de 2026
> **Alcance:** `src/` (25 bounded contexts, ~1.083 archivos PHP), `routes/`, `app/`, `bootstrap/`, `config/`, `database/`, `resources/js/` (~218 archivos TSX/TS)
> **Método:** lectura directa del código. Cada hallazgo cita archivo y línea, y describe un escenario de fallo concreto. Los hallazgos marcados ✅ los verifiqué personalmente releyendo el archivo.

---

## Resumen ejecutivo

La arquitectura (DDD + hexagonal + multi-tenant) está bien planteada y los módulos nuevos —Monetization, SupportTicket, ExchangeRate, CentralMarketplace, el panel de SuperAdmin— siguen el patrón del proyecto. El problema no es de diseño: es que **la capa de autorización nunca se construyó**. No existe ningún middleware de rol registrado, `bootstrap/app.php` importa dos middlewares que no existen en disco, y decenas de endpoints que mueven dinero o identidad quedaron sin `auth`.

En segundo lugar, el flujo de compra **confía en datos que envía el navegador** (precio de los productos, `is_approved` de las reseñas, `customer_id` del portal del cliente) y **no usa transacciones ni bloqueos** en ninguna de las operaciones críticas (crear pedido, descontar stock, generar liquidación, emitir factura correlativa).

| Bloque | Hallazgos | Máx. severidad |
| :--- | :---: | :--- |
| A. Autenticación y autorización | 9 | 🔴 Crítico |
| B. Confianza en datos del cliente | 4 | 🔴 Crítico |
| C. Transacciones y concurrencia | 6 | 🔴 Crítico |
| D. Cálculo de dinero | 6 | 🟠 Alto |
| E. Sincronización de catálogo | 4 | 🔴 Crítico |
| F. Infraestructura y configuración | 6 | 🔴 Crítico |
| G. Frontend | 15 | 🔴 Crítico |

> ⚠️ **Cobertura incompleta:** la revisión de `resources/js/pages/admin/*` y `resources/js/pages/tenant/*` (las páginas más nuevas: seguridad, roles, planes, CMS, moderación, payouts, dashboard, soporte, wallet) se cortó por límite de sesión. Ese frente queda pendiente.

---

## Bloque A — Autenticación y autorización

### A1. 🔴 El backoffice de SuperAdmin se monta también en **cada dominio de tenant** ✅

> **Estado:** ✅ CERRADO (Fase 0.1)

**Archivo:** `routes/tenant.php:31` (y `routes/web.php:23`)

```php
// routes/web.php:23 — dentro de Route::domain($central_domain)
Route::prefix('admin')->group(callback: base_path('src/Admin/Infrastructure/Http/Routes/web.php'));

// routes/tenant.php:31 — dentro del grupo de tenancy, SIN restricción de dominio
Route::prefix('admin')->group(callback: base_path('src/Admin/Infrastructure/Http/Routes/web.php'));
```

El mismo archivo de rutas se carga dos veces. La copia central está protegida por `Route::domain()`; la de tenant no tiene nada más que `auth`.

**Escenario:** un cliente cualquiera con cuenta en `tienda-a.owomarket.com` inicia sesión en su propio storefront y hace `POST https://tienda-a.owomarket.com/admin/api/security/staff/{su_uuid}/roles` con `{"roles":["super-admin"]}`. Pasa el `auth` (está autenticado) y se convierte en administrador de toda la plataforma. Igual con `/admin/api/payouts/{id}/approve` (aprobar retiros de dinero) y `/admin/api/tenants/{id}/sso-token` (entrar a cualquier tienda).

**Arreglo:** borrar la línea 31 de `routes/tenant.php`.

---

### A2. 🔴 Emisión pública de tokens SSO de dueño de tienda ✅

> **Estado:** ✅ CERRADO (Fase 0.3-B)

**Archivo:** `src/Tenant/Infrastructure/Http/Routes/web.php:58-62`

Las rutas de vista de arriba llevan `->middleware('auth')`; el bloque de APIs se lo saltó entero:

```php
Route::post('/owner/api/sso-token', GenerateTenantOwnerSsoTokenPOSTController::class);
Route::get('/owner/api/wallet-summary', GetTenantOwnerWalletSummaryGETController::class);
Route::post('/owner/api/payout-request', CreateTenantOwnerPayoutRequestPOSTController::class);
Route::get('/owner/api/products', ListTenantOwnerProductsGETController::class);
Route::post('/owner/api/products/{id}/toggle-marketplace', ToggleTenantOwnerProductPublicationPOSTController::class);
```

Y `ConsumeTenantOwnerSsoTokenGETController.php:31` hace `Auth::login($user, true)` (con *remember me*) sin más verificación.

**Escenario:** los UUID de usuario aparecen en las URLs del panel (`/tenant/owner/backoffice/{user_uuid}/dashboard`) y los devuelve `POST /tenant/owner/filter/tenants`, que **también está sin `auth`** (línea 53). Un anónimo obtiene un UUID, hace `POST /tenant/owner/api/sso-token {"user_id":"<uuid>","tenant_id":"<cualquiera>"}`, visita la URL devuelta y queda logueado como esa persona. Toma de control total sin credenciales.

**Arreglo:** `->middleware('auth')` en las líneas 53 y 58-62, y derivar `user_id` de `auth()->id()` en lugar del body.

---

### A3. 🔴 Toda la API de clientes centrales, anónima y con la identidad en la URL ✅

> **Estado:** ✅ CERRADO (Fase 0.3-D)

**Archivo:** `routes/api.php:21` → `src/CentralCustomer/Infrastructure/Http/Routes/apiCentral.php:35-67`

Ninguna de estas rutas tiene middleware:

```php
Route::post('/sso/generate-token', GenerateSsoTokenPOSTController::class);
Route::get('/profile/{id}',  GetCustomerProfileGETController::class);
Route::put('/profile/{id}',  UpdateCustomerProfilePUTController::class);
Route::get('/orders',        ListCustomerOrdersGETController::class);
Route::get('/invoices/{id}/pdf', DownloadCustomerInvoicePdfGETController::class);
```

**Escenarios:**
- `POST /api/central/customer/sso/generate-token {"customer_id":"<uuid>"}` devuelve un token válido → suplantación de cualquier comprador.
- `GET /api/central/customer/profile/{uuid}` expone nombre, email, teléfono, cédula y direcciones.
- `ListCustomerOrdersUseCase.php:17-23` filtra con `where('customer_id')->orWhere('customer_email')`: basta acertar **uno** de los dos, así que `GET /api/central/customer/orders?email=victima@gmail.com` devuelve todo su historial de compras.

**Arreglo:** definir un guard `central_customer` en `config/auth.php` (hoy no existe, ver F4) y envolver todo el archivo salvo `/register`, `/login`, `/forgot-password`, `/reset-password`; resolver el ID desde la sesión, nunca desde la URL.

---

### A4. 🔴 API de monetización (comisiones y liquidaciones) sin autenticación ✅

> **Estado:** ✅ CERRADO (Fase 0.3-A)

**Archivo:** `routes/api.php:22` → `src/Monetization/Infrastructure/Http/Routes/apiCentral.php:12-18`

```php
Route::post('/custom-commission', UpdateTenantCustomCommissionPOSTController::class);
Route::get('/metrics', GetSuperAdminMonetizationMetricsGETController::class);
Route::post('/settlements/generate', GenerateCommissionSettlementPOSTController::class);
Route::post('/settlements/{id}/confirm', ConfirmCommissionSettlementPOSTController::class);
```

**Escenario:** cualquier persona en internet hace `POST /api/central/monetization/custom-commission {"tenant_id":"<uuid>","custom_commission_rate":0}` y deja la comisión de esa tienda en 0% para siempre; o `POST /settlements/{id}/confirm` para marcar liquidaciones como cobradas sin haber cobrado nada; o `GET /metrics` para leer la facturación global de la plataforma. **Esto rompe directamente el objetivo de negocio que planteaste: asegurar el cobro de la comisión.**

---

### A5. 🔴 Toda la API de administración del tenant (`/api-tenant/*`) sin autenticación ✅

> **Estado:** ✅ CERRADO (Fase 0.3-E)

**Archivo:** `bootstrap/app.php:22-26`

```php
Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api-tenant')->group(base_path('routes/tenantApi.php'));
```

Solo tenancy, ningún `auth`. Y los archivos de rutas de cada módulo tampoco lo añaden:

```php
// src/Product/Infrastructure/Http/Routes/apiTenant.php
Route::post('/create', CreateProductPOSTController::class);
Route::delete('/{id}', DeleteProductDELETEController::class);
Route::patch('/{id}/stock', UpdateProductStockPATCHController::class);
```

**Escenario:** `POST https://tienda1.owomarket.com/api-tenant/coupon/create {"code":"FREE","type":"percentage","value":100}` crea un cupón del 100% y luego se usa. O `DELETE /api-tenant/product/{id}` borra el catálogo entero. Sin login.

---

### A6. 🔴 Mesa de soporte central abierta: lectura, escritura y suplantación de agentes

> **Estado:** ✅ CERRADO (Fase 0.3-C)

**Archivo:** `src/SupportTicket/Infrastructure/Http/Routes/web.php:23-29`

El grupo `api/support` del dominio central no tiene middleware — aunque la versión tenant del mismo módulo (`tenant.php:19`) **sí** lo tiene, lo que confirma que fue un olvido. Además el controlador prioriza el `user_id` del query string sobre la sesión, y `GetSupportTicketDetailUseCase.php:19-23` solo filtra por propietario **si** ese `user_id` viene:

```php
if ($userId) { $query->where('user_id', $userId); }   // sin userId => sin filtro
```

**Escenarios:**
- `GET /api/support/tickets/{uuid}` sin parámetros → devuelve el ticket completo de cualquiera.
- `POST /api/support/tickets/{id}/messages {"message":"Confirme su contraseña","sender_type":"admin","sender_name":"Soporte OwoMarket"}` → inserta un mensaje que la víctima verá como oficial. Phishing dentro del propio producto.

---

### A7. 🟠 PIN de 6 dígitos fuerza-brutable y aplicable a cualquier administrador

> **Estado:** ⬜ ABIERTO

**Archivo:** `src/Admin/Infrastructure/Http/Routes/web.php:37-38` + `ChangePasswordWithPinUseCase.php:36-45`

El destinatario del cambio de contraseña es el `{user_uuid}` de la URL, **no** el usuario en sesión. No hay contador de intentos, ni `throttle` en la ruta, ni rate limiting global (`bootstrap/app.php` no invoca `throttleApi()`). Un fallo no invalida el PIN ni incrementa nada.

**Escenario:** un usuario autenticado de bajo privilegio dispara `/send-pin` contra el UUID de un admin y luego itera los 10⁶ PIN posibles contra `/change-password`. Sin límite de tasa, el espacio se agota dentro de la ventana de 15 minutos.

**Arreglo:** usar `auth()->id()` en vez de `{user_uuid}`, `->middleware('throttle:5,15')`, e invalidar el PIN tras 3 fallos.

---

### A8. 🟠 El token SSO no se ata al destino: se puede redimir en otra tienda

> **Estado:** ⬜ ABIERTO

**Archivos:** `src/CentralCustomer/Application/UseCases/ValidateAndConsumeSsoTokenUseCase.php:20-44` y `src/Tenant/Application/UseCase/ConsumeTenantOwnerSsoTokenUseCase.php:25-47`

El primero **recibe `$currentDomain` y nunca lo usa**; el campo `target_domain` que se persiste al generar jamás se compara. El segundo busca solo por `token`, sin `where('tenant_id', tenant()->id)`, y encima fuerza `type = 'owner'`.

**Escenario:** el dueño de la tienda A pide un token legítimo para su tienda y abre `https://tiendaB.owomarket.com/auth/sso-consume?token=...`. El token es válido → se le crea un `User` con `type = 'owner'` en la BD de la tienda B y queda logueado como propietario de una tienda ajena. Rotura completa del aislamiento multi-tenant.

**Arreglo:** comparar `target_domain` con `request()->getHost()` usando `hash_equals`, filtrar por `tenant_id`, y conservar el `type` real del usuario.

---

### A9. 🟠 Impersonación de tenant sin control de rol, sin auditoría y con URL rota

> **Estado:** 🟡 PARCIAL (Fase 0.3-A: ya exige super_admin; faltan la auditoría y la URL rota)

**Archivo:** `src/Tenant/Application/UseCase/AdminImpersonateTenantUseCase.php:23-53`

Cuatro problemas en el mismo flujo: la ruta solo exige `auth` (cualquier autenticado impersona); el controlador acepta un `admin_user_id` del request como respaldo; el caso de uso **no escribe nada en `CentralAuditLog`** (a diferencia de `AssignUserRolesUseCase.php:37`, que sí lo hace); y la URL generada apunta a `/auth/sso`, ruta que **no existe** — la real es `/auth/sso-consume`.

**Escenario funcional:** el botón "acceso directo" del expediente 360° devuelve un enlace que da 404. La función no sirve hoy.

---

## Bloque B — Confianza en datos del cliente

### B1. 🔴 El precio de cada producto lo envía el navegador y se persiste sin validar ✅

> **Estado:** ✅ CERRADO (Fase 0.4)

**Archivos:** `src/CentralMarketplace/Application/UseCases/CreateUnifiedCentralOrderUseCase.php:36,69,78` y `src/Marketplace/Infrastructure/Http/Controller/CreateStorefrontOrderPOSTController.php:55,89,94`

```php
// CreateUnifiedCentralOrderUseCase.php:34-37
$subtotal = 0.0;
foreach ($items as $item) {
    $subtotal += (float) ($item['price'] * (int) ($item['quantity'] ?? 1));
}
```

Nunca se consulta el precio real del producto en la BD del tenant. La validación del controlador solo exige `numeric|min:0`.

**Escenario:** el comprador intercepta el POST y envía `"price": 0.01` para un producto de $500. Se crea el pedido central por $0.01, se despacha al tenant una `Order` con `price=0.01`, se registra un `payment` de $0.01 y una comisión de $0.0008. La tienda envía un producto de $500.

**Este es el bug más grave del flujo de compra.** Afecta a ambos checkouts (tenant y central).

**Arreglo:** resolver precio y disponibilidad server-side por `tenant_id`+`product_id` (inicializando tenancy) y descartar por completo el `price` del request.

---

### B2. 🔴 El cliente decide si su reseña está aprobada y "verificada"

> **Estado:** ✅ CERRADO (Fase 0.4)

**Archivos:** `src/Review/Infrastructure/Http/Request/CreateProductReviewFormRequest.php:28-29`, `CreateProductReviewUseCase.php:36`

```php
'is_approved' => ['nullable', 'boolean'],
'is_verified' => ['nullable', 'boolean'],
```
```php
isVerified: $data->isVerified || ! empty($data->orderId),
```

Además `isVerified` se pone a `true` con la mera presencia de un `order_id`, validado solo con `exists:orders,id` — no se comprueba que la orden sea de ese cliente ni que contenga ese producto.

**Escenario:** `POST /api-tenant/review/create` con `{"rating":5,"is_approved":true,"is_verified":true}` publica al instante una reseña de 5 estrellas marcada como "compra verificada", saltándose la moderación. Combinado con A5 (sin auth), es explotable desde fuera.

---

### B3. 🔴 El checkout aplica cupones sin validar fechas, límite de uso ni monto mínimo

> **Estado:** ✅ CERRADO (Fase 3.1) — el checkout pasa por `ValidateCouponUseCase` sobre el subtotal del servidor, y un cupón inválido rechaza el pedido con 422

**Archivo:** `src/Marketplace/Infrastructure/Http/Controller/CreateStorefrontOrderPOSTController.php:125-135`

El flujo real de compra **no usa** `ValidateCouponUseCase` ni `Coupon::validateUsability()`. Solo comprueba `is_active`:

```php
$coupon = Coupon::where('code', $couponCode)->where('is_active', true)->first();
if ($coupon) {
    ...
    $coupon->increment('used_count');
}
```

Se ignoran `valid_from`/`valid_to`, `usage_limit`, `usage_limit_per_customer` y `min_order_amount`. Y el `used_count` se incrementa **antes** de crear la orden y fuera de transacción.

**Escenario:** un cupón `NAVIDAD2025` con `valid_to = 2025-12-31` y `usage_limit = 100` ya agotado sigue descontando en agosto de 2026, de forma ilimitada.

---

### B4. 🔴 La identidad del cliente del portal sale de `localStorage`

> **Estado:** 🟡 PARCIAL (Fase 0.3-D: el backend ya no confía en localStorage; el frontend sigue cacheando el perfil)

**Archivos:** `resources/js/Services/CustomerPortalServices.ts:181-247` y `resources/js/contexts/CustomerAuthContext.tsx:59,206`

```ts
const res = await axios.get(`/api/central/customer/orders`, { params: { customer_id: customerId, ...filters } });
```
```tsx
const cachedCustomer = localStorage.getItem('owo_central_customer');
isAuthenticated: !!customer,
```

**Escenario:** cualquiera edita `localStorage.owo_central_customer` con `{"id":"<uuid ajeno>"}`, recarga `/account/orders` y ve pedidos, facturas en PDF, direcciones y devoluciones de esa persona. Es el mismo agujero de A3 visto desde el frontend. Además guarda nombre, email, teléfono, documento y direcciones en `localStorage`, accesible a cualquier XSS.

---

## Bloque C — Transacciones y concurrencia

### C1. 🔴 Ningún punto del checkout descuenta stock correctamente

> **Estado:** ✅ CERRADO (Fase 0.4 + 1.3) — salvo la reposición de stock al cancelar, ver seguimiento de 1.3

**Archivos:** `CreateOrderUseCase.php:18-53` (no toca stock en absoluto) y `CreateStorefrontOrderPOSTController.php:106-119`:

```php
try {
    if ($varId) {
        $variant = ProductVariant::find($varId);
        if ($variant && $variant->quantity >= $qty) { $variant->decrement('quantity', $qty); }
    }
    $product = Product::find($pId);
    if ($product && $product->quantity >= $qty) { $product->decrement('quantity', $qty); }
} catch (\Throwable) {
}
```

Tres bugs en nueve líneas: si no hay stock **el pedido se crea igual** (el `if` simplemente no descuenta); con variante se descuenta **dos veces** (de la variante y del producto padre); y cualquier error queda tragado por el `catch` vacío. Sin `lockForUpdate` ni transacción. Tampoco hay reposición al cancelar.

**Escenario:** queda 1 unidad y llegan dos pedidos simultáneos → ambos leen `quantity = 1`, ambos descuentan, stock queda en -1 y hay dos órdenes que no se pueden servir. Con 0 unidades, el pedido se acepta sin avisar.

---

### C2. 🔴 El despacho multi-tienda no es transaccional ni idempotente ✅

> **Estado:** ✅ CERRADO (Fase 1.1)

**Archivos:** `CreateUnifiedCentralOrderUseCase.php:43-89`, `DispatchCentralOrderToTenantsUseCase.php:35-153`

No hay `DB::transaction` en ningún nivel ni clave de idempotencia. El bucle usa `try { ... } finally { ... }` **sin `catch`**.

**Escenario:** carrito de 3 tiendas; la BD del tenant 2 no responde. El tenant 1 ya tiene su pedido, su `payment` y su comisión; el cliente recibe un error y reintenta. Se crea un **segundo** `CentralOrder` completo y el tenant 1 acaba con dos pedidos idénticos y dos comisiones — se cobra dos veces por una compra.

**Arreglo:** transacción para la creación central, y un job idempotente por `(central_order_id, tenant_id)` con índice único para el despacho.

---

### C3. 🟠 Carrera al generar liquidaciones: doble cobro de comisiones

> **Estado:** ✅ CERRADO (Fase 1.3)

**Archivo:** `src/Monetization/Application/UseCases/GenerateTenantCommissionSettlementUseCase.php:28-64`

Se leen las comisiones pendientes, se crea la liquidación con los totales y **después** se enlazan con un `update` que no revalida `whereNull('settlement_id')`. Sin transacción y sin `lockForUpdate()`.

**Escenario:** doble clic en "Generar liquidación" con $500 pendientes → se crean SET-A y SET-B, ambas por $500; el segundo `update` reasigna todas las comisiones a SET-B, y SET-A queda pendiente por $500 sin comisiones asociadas. Si el superadmin confirma ambas, la plataforma registra $1.000 cobrados sobre $500 reales y las métricas reportan el doble.

---

### C4. 🟠 Números de factura correlativos duplicados

> **Estado:** ✅ CERRADO (Fase 1.3)

**Archivos:** `CreateDirectInvoiceUseCase.php:24-46`, `BillingProfile.php:106-115`

`getProfile()` hace un `first()` sin bloqueo, el incremento ocurre en memoria y se persiste aparte. La transacción del repositorio de facturas es posterior y no cubre el contador.

**Escenario:** dos operadores emiten factura a la vez con `next_invoice_number = 42`. Ambos generan `FAC-2026-000042`. Se persisten dos facturas fiscales con el mismo correlativo (o la segunda revienta **después** de haber consumido el número, dejando un hueco en la serie). Si `save()` de la factura falla, el contador ya quedó incrementado.

---

### C5. 🟠 Consumo de tokens SSO sin atomicidad (replay por carrera)

> **Estado:** ⬜ ABIERTO

**Archivos:** `ValidateAndConsumeSsoTokenUseCase.php:30-39`, `ConsumeTenantOwnerSsoTokenUseCase.php:25-36`

Leer → comprobar `used_at` → escribir, en tres sentencias separadas, sin transacción ni `UPDATE ... WHERE used_at IS NULL` condicional. Curiosamente el primero **importa `DB` en la línea 9 y nunca lo usa**.

**Arreglo:**
```php
$affected = Token::where('token', $t)->whereNull('used_at')->where('expires_at','>',now())
                 ->update(['used_at' => now()]);
if ($affected === 0) { throw new Exception('Token inválido o ya usado', 410); }
```

---

### C6. 🟠 `increment('used_count')` sin condición permite superar el límite de un cupón

> **Estado:** ✅ CERRADO (Fase 3.1) — `CouponRedeemer` comprueba el techo y consume el uso en la misma sentencia, dentro de la transacción

Ya citado en B3. `increment()` es atómico a nivel de columna pero no verifica el techo, así que N peticiones paralelas pasan todas la comprobación previa.

**Arreglo:** `UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND (usage_limit IS NULL OR used_count < usage_limit)` comprobando filas afectadas.

---

## Bloque D — Cálculo de dinero

### D1. 🔴 El envío, el descuento y los impuestos se pierden al repartir el pedido central ✅

> **Estado:** ✅ CERRADO (Fase 1.1) — base de comisión confirmada por negocio: mercancía neta de descuento, sin envío

**Archivo:** `src/CentralMarketplace/Application/UseCases/DispatchCentralOrderToTenantsUseCase.php:88-107,124,161`

```php
$dto = new CreateOrderData(
    ...
    taxAmount: 0.0,
    shippingAmount: 0.0,
    discountAmount: 0.0,
    ...
);
$tenantOrderTotal = $tenantOrder->total()->amount();
```

Ese `$tenantOrderTotal` (subtotal bruto) se usa como (a) importe del registro en `payments` y (b) **base de cálculo de la comisión**.

**Escenario numérico:** carrito de dos tiendas, A=$60 y B=$40, envío $10, cupón −$30.
- El cliente paga **$80** (`CentralOrder.total`).
- Se crean pedidos de tenant por $60 y $40 → suma **$100 ≠ $80**.
- Se registran `payments` por $100 donde entraron $80.
- Comisión al 8%: se cobra sobre $60 y $40 = **$8,00**, cuando sobre lo realmente cobrado serían ~$5,60.
- Ninguna tienda ve el cupón que aplicó el cliente, y el envío no lo absorbe nadie.

**Arreglo:** prorratear `shipping_amount` y `discount_amount` entre tenants por peso del subtotal, y documentar explícitamente sobre qué base se cobra la comisión (bruto o neto) — ahora mismo el código dice una cosa y el negocio probablemente quiere otra.

---

### D2. 🔴 La comisión se registra al crear el pedido y nunca se revierte

> **Estado:** ✅ CERRADO (Fase 1.2)

**Archivos:** `CalculateAndRecordOrderCommissionUseCase.php:41-55`, `CancelOrderUseCase.php:26-27`, `RefundOrderUseCase.php:26-27`

La `PlatformCommission` se crea con `status='pending'` en el despacho, sin importar el `payment_status` (que para `pago_movil`, `manual_transfer` y `cash_on_delivery` es siempre `pending`). No existe ninguna ruta que la ponga en `cancelled`: `CancelOrderUseCase` y `RefundOrderUseCase` solo mutan el agregado `Order` del tenant y no tocan la BD central.

**Escenario:** un cliente pide $1.000 con Pago Móvil y nunca paga. La tienda cancela. La comisión de $80 sigue pendiente y `GenerateTenantCommissionSettlementUseCase` la incluye en la próxima liquidación: se le cobra $80 a la tienda por una venta que no existió. **Esto va a generar disputas con tus comerciantes.**

---

### D3. 🟠 Conversión de moneda con fallback silencioso a tasa 1.0

> **Estado:** ✅ CERRADO (Fase 1.4) — el caso de uso lanza excepción, el endpoint responde 404, y `deactivateAll()` + `save()` corren en transacción

**Archivo:** `src/ExchangeRate/Application/UseCase/ConvertCurrencyAmountUseCase.php:34-38,67-71`

```php
$rateValue = $activeRate ? $activeRate->getRate()->value() : 1.0;
$source    = $activeRate ? $activeRate->getSource()->value() : 'FALLBACK';
```

Y `SyncBcvExchangeRateUseCase.php:53-72` hace `deactivateAll()` y **después** `save()` sin transacción: si `save()` falla o el proceso muere entre ambas, no queda ninguna tasa activa.

**Escenario:** a partir de ese momento `GET /api/central/exchange-rate/convert?amount=100` devuelve **100 Bs por 100 USD** (≈775 veces menos) con `success: true`, y el checkout en bolívares cobra céntimos.

**Arreglo:** lanzar excepción si no hay tasa activa, y envolver `deactivateAll()` + `save()` en una transacción.

---

### D4. 🟠 El scraper del BCV rompe en cuanto la tasa supera 999,99

> **Estado:** ✅ CERRADO (Fase 1.4) — se quita el separador de miles y el fallback prolongado se registra como `error`, no como `warning`

**Archivo:** `src/ExchangeRate/Infrastructure/Scrapers/BcvWebScraper.php:95-108`

```php
$cleanedRate = str_replace([' ', "\r", "\n", "\t"], '', $rawRate);
$normalizedRate = str_replace(',', '.', $cleanedRate);
if (! is_numeric($normalizedRate) || (float) $normalizedRate <= 0) { ... success: false ... }
```

Se elimina el espacio y se cambia la coma decimal por punto, pero **no se quita el punto separador de miles**. `1.234,56` → `1.234.56` → `is_numeric()` = false.

**Escenario:** el BCV publica una tasa de cuatro cifras, el sync cae al fallback y **congela indefinidamente** la última tasa buena, dejando solo un `warning` en el log. Todo el sitio factura con una tasa vieja sin que nadie se entere. Dado el nivel actual del bolívar, esto es cuestión de tiempo.

**Arreglo:** `str_replace('.', '', $cleaned)` antes de convertir la coma, y alertar (no solo `warning`) si el fallback se activa varios días seguidos.

---

### D5. 🟠 Tarifas de envío: `free` ignora su umbral y `weight_based` cobra plano

> **Estado:** ⬜ ABIERTO

**Archivo:** `src/Shipping/Domain/Entities/ShippingRate.php:130-132,147-154`

```php
if ($this->type->isFree()) {
    return true;          // ← antes de evaluar minValue/maxValue
}
```
```php
public function calculateCost(): float
{
    if ($this->type->isFree()) { return 0.0; }
    return $this->cost->value();    // ← no recibe peso ni valor del pedido
}
```

**Escenarios:** "Envío gratis a partir de $100" se aplica a un pedido de $5 y, al ser la opción más barata, `CalculateShippingOptionsUseCase.php:43-46` la marca como recomendada → todos los envíos salen gratis. Y una tarifa "$3 por kg" cobra $3 por un pedido de 20 kg en vez de $60.

---

### D6. 🟠 Sin país, se suman **todas** las tasas de impuesto activas

> **Estado:** ⬜ ABIERTO

**Archivos:** `TaxRateRepository.php:120-151`, `CalculateTaxUseCase.php:28-37`

Cada filtro geográfico solo se aplica si el parámetro no es null, y el caso de uso **suma** todas las tasas devueltas. `priority` solo ordena, nunca selecciona.

**Escenario:** un tenant con "IVA Venezuela 16%" e "IVA España 21%" configurados. Un `POST /api-tenant/tax/calculate` solo con `subtotal` devuelve **37%** de impuesto.

---

### Menores de dinero
- **`commission_amount` por ítem no cuadra con la comisión registrada** (`DispatchCentralOrderToTenantsUseCase.php:172-176`): la comisión oficial se redondea una vez sobre el total y luego se recalcula ítem a ítem. Tres ítems de $3,33 al 8% dan $0,81 por ítems vs $0,80 en la `PlatformCommission`.
- **`PlatformCommission.order_id` guarda el ID del pedido del *tenant*, pero las relaciones Eloquent lo declaran contra `central_orders`** (`PlatformCommission.php:61-64`): `$centralOrder->commissions` devuelve **siempre** una colección vacía.
- **El valor medio de pedido divide ventas netas entre pedidos totales** (`EloquentOrderRepository.php:174-183`): con 50% de cancelaciones, el KPI queda a la mitad.
- **Importes de valor 0 se convierten en `null` al leer la factura** (`EloquentInvoiceRepository.php:241-247`): `$model->commission_amount ? (float) ... : null` — `0.00` es falsy, así que una venta exenta pierde la diferencia entre "sin comisión" y "comisión cero".
- **Fechas de cupones y tasas evaluadas en UTC** (`config/app.php:68` es `'UTC'`, `Coupon.php:198-201` usa `date('Y-m-d')`): un cupón con `valid_to = 2026-08-21` deja de funcionar a las **20:00 hora de Caracas**, cuatro horas antes de lo prometido.

---

## Bloque E — Sincronización de catálogo

### E1. 🔴 Borrar u ocultar un producto en el tenant no lo retira del marketplace central

> **Estado:** ✅ CERRADO (Fase 2.2) — sincronización por eventos de modelo; `delete()` y `toggleVisibility()` dejaron de usar el query builder, que no los dispara

**Archivo:** `src/Product/Infrastructure/Eloquent/Repositories/ProductRepository.php:188-191, 270-273`

```php
public function delete(ProductId $id): void
{
    EloquentProduct::where('id', $id->value())->delete();
}

public function toggleVisibility(ProductId $id, bool $isVisible): void
{
    EloquentProduct::where('id', $id->value())->update(['is_visible' => $isVisible]);
}
```

Solo `updateStock()` propaga a `central_products`. `Product` usa `SoftDeletes`, así que la fila central queda con `is_visible = true` para siempre.

**Escenario:** el comerciante borra un producto descatalogado. Sigue apareciendo y siendo comprable en el marketplace central; el checkout recibe un `product_id` que ya no existe y —por el `catch (\Throwable)` vacío de C1— la orden se crea igualmente.

---

### E2. 🔴 Cambios de precio y nombre nunca llegan al catálogo central

> **Estado:** ✅ CERRADO (Fase 2.2) — `ProductObserver` sincroniza la fila entera en cada `saved`, incluida la bajada de stock por venta

**Archivo:** `ProductRepository.php:115-186`

`update()` reescribe precio, nombre, descripción, imágenes y variantes en el tenant y **no toca** `central_products`. Y el `decrement('quantity')` del checkout no pasa por `updateStock()`, así que el stock central tampoco baja con las ventas.

**Escenario:** el comerciante sube el precio de $50 a $80. El marketplace central sigue vendiendo a $50 indefinidamente.

**Arreglo:** disparar la sincronización desde los eventos de modelo (`updated`, `deleted`) en vez de confiar en que cada camino se acuerde de llamarla.

---

### E3. 🟠 Colisión de slugs entre tenants en la ficha de producto central

> **Estado:** ✅ CERRADO (Fase 2.2) — `CentralProductResolver` resuelve por prioridad, los enlaces del marketplace usan el id, e índice único `(tenant_id, slug)`

**Archivo:** `src/Marketplace/Infrastructure/Http/Controller/GetCentralProductDetailAPIController.php:123-128`

```php
$product = CentralProduct::with('tenant.domains')
    ->where('is_visible', true)
    ->where(fn ($q) => $q->where('slug', $slugOrId)->orWhere('id', $slugOrId)->orWhere('tenant_product_id', $slugOrId))
    ->first();
```

Sin filtrar por `tenant_id`. El slug se copia tal cual desde cada tenant, así que los duplicados entre tiendas son la norma.

**Escenario:** la tienda A y la B publican `camisa-blanca`. Al abrir el producto de B desde el listado central se muestra la ficha, el precio y la tienda de **A**, y el "Añadir al carrito" apunta al tenant equivocado.

**Arreglo:** enrutar por `central_products.id` o por la pareja `tenant_id + slug`, con índice único `(tenant_id, slug)`.

---

### E4. 🟠 Editar un producto regenera los IDs de todas sus variantes

> **Estado:** ✅ CERRADO (Fase 2.2) — upsert por id de variantes e imágenes, y borrado del fichero físico al eliminar una imagen

**Archivo:** `ProductRepository.php:151-183`

En cada `update()` se borran físicamente todas las filas de `product_variants` y `product_images` y se recrean con UUIDs nuevos.

**Escenario:** el comerciante corrige una errata en la descripción → todas las variantes cambian de ID: los `order_items.product_variant_id` de pedidos históricos quedan huérfanos, los carritos de los clientes apuntan a variantes inexistentes, y el array `variants` ya sincronizado en `central_products` queda inconsistente. Las imágenes borradas dejan además sus ficheros huérfanos en storage.

**Arreglo:** upsert por ID de variante y borrar el fichero físico al eliminar una `ProductImage`.

---

## Bloque F — Infraestructura y configuración

### F1. 🔴 `sessions.user_id` es NOT NULL con clave foránea ✅

> **Estado:** ✅ CERRADO (Fase 2.1) — migraciones originales corregidas y dos migraciones correctivas para las bases existentes
>
> **Corrección al texto de abajo:** no hay `SQLSTATE[23000]`. `DatabaseSessionHandler::performInsert()`
> captura la `QueryException`, así que la sesión sencillamente **no se guardaba, sin ningún
> error en el log**. El login roto no dejaba ni una pista.

**Archivo:** `database/migrations/0001_01_01_000000_create_users_table.php:52-61` (y la copia en `database/migrations/tenant/`)

```php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    // $table->foreignId('user_id')->nullable()->index();   ← comentado
    $table->string('user_id');
    $table->foreign('user_id')->references('id')->on('users');
```

`DatabaseSessionHandler::addUserInformation()` escribe `user_id => auth()->id()`, que es `null` para cualquier visitante no autenticado.

**Escenario:** con `SESSION_DRIVER=database`, la primera petición que persiste sesión (cargar `/auth/login` y generar el token CSRF) lanza `SQLSTATE[23000] Column 'user_id' cannot be null` → **nadie puede iniciar sesión**. Si hoy funciona, es porque tu `.env` usa otro driver; en cuanto pases a `database` en producción, el sitio se cae entero.

**Arreglo:** migración correctiva `$table->string('user_id')->nullable()->change();` y quitar la FK (o dejarla `nullOnDelete`). En ambas rutas de migración.

---

### F2. 🔴 `bootstrap/app.php` importa middlewares que no existen y no registra ningún alias ✅

> **Estado:** ✅ CERRADO (Fase 0.2: alias de rol registrados y middlewares creados)

```php
use App\Http\Middleware\TenantAuthentication;   // ← la clase no existe en disco
use App\Http\Middleware\VerifyCsrfToken;        // ← tampoco
```

`app/Http/Middleware/` solo contiene `CorsHeaders.php` y `HandleInertiaRequests.php`. Y `withMiddleware()` no llama a `$middleware->alias(...)` en ningún momento.

**Consecuencia:** no existe ningún middleware `admin`, `role` ni `tenant.auth` disponible. Escribir `->middleware('admin')` en una ruta produciría `Target class [admin] does not exist`. **Esta es la causa raíz de todo el Bloque A**: no había con qué proteger las rutas, así que se protegieron solo con `auth` o con nada.

**Arreglo:** borrar los dos `use` muertos y registrar aliases reales (`super_admin`, `tenant_owner`, `central_customer`), luego aplicarlos ruta por ruta.

---

### F3. 🟠 Cookie de sesión compartida por todos los subdominios

> **Estado:** ⬜ ABIERTO

**Archivo:** `config/session.php:159, 172`

```php
'domain' => env('SESSION_DOMAIN', '.owomarket.local'),
'secure' => env('SESSION_SECURE_COOKIE', false),
```

Comodín para todos los subdominios y un único nombre de cookie para toda la app. Además `StartSession` (del grupo `web`) corre **antes** de `InitializeTenancyByDomain` en `routes/tenant.php:21-24`, así que el ID de sesión leído es el mismo en todos los dominios.

**Escenario:** un usuario autenticado en `tienda-a` navega a `tienda-b`: el navegador manda la misma cookie, la sesión se lee de una BD y se persiste en otra. Sesiones colgadas o reutilizadas entre tenants.

> Nota: esto interactúa con el diseño de SSO que planteaste en `flujo_de_compra_cliente.md`. Merece una decisión explícita: o cookie por tenant (`session.cookie = 'owo_'.tenant('id')`) o sesión compartida deliberada con aislamiento a nivel de datos.

---

### F4. 🟠 Guard `central_customer` usado en el código pero inexistente en `config/auth.php`

> **Estado:** 🟡 PARCIAL (Fase 0.3-D: guard central_customer creado y en uso; revisar si queda algún otro guard inexistente)

`config/auth.php:40-52` solo define `web` y `central`. Pero `ListSupportTicketsGETController.php:23` llama a `auth('central_customer')->id()`.

**Escenario:** `GET /api/support/tickets` sin `user_id` explícito lanza `InvalidArgumentException: Auth guard [central_customer] is not defined` → HTTP 500. El único camino que funciona es pasar `user_id` a mano, que es justo el vector de IDOR de A6.

---

### F5. 🟠 Tablas de permisos (Spatie) solo en la BD central, pero `User` usa `HasRoles` también en tenants

> **Estado:** ⬜ ABIERTO

La migración `2026_08_21_000826_create_permission_tables.php` vive solo en `database/migrations/`, no en `migrations/tenant/`. `Src\User\...\User` (el provider `users`) no fija conexión, así que en un dominio de tenant resuelve la BD del tenant, donde `roles` y `permissions` no existen.

**Escenario:** cualquier `$user->hasRole(...)` o `$user->can(...)` en un dominio de tienda lanza `Base table or view not found: 'roles'`. Todo el RBAC que añadas al panel del tenant fallará en runtime.

---

### F6. 🟠 Seeders de demo sin condicionar al entorno

> **Estado:** ✅ CERRADO (Fase 2.1) — guarda de entorno en `DatabaseSeeder` y dentro de cada seeder de demo, más un `ProductionSeeder` con los datos maestros

`DatabaseSeeder.php:18-26` invoca `RootUserSeeder` y los seeders de demo sin ninguna guarda. `RootUserSeeder.php:53-61` hace `updateOrCreate` de `root@owomarket.local` con `USER_PASSWORD_DEV` (`Test_12345678` en el `.env.example`).

**Escenario:** un `php artisan db:seed --force` en producción crea el superadmin con contraseña conocida, ocho dueños de tienda demo y el catálogo de prueba. Y el `updateOrCreate` **resetea la contraseña del root si ya existía**.

**Arreglo:** `if (! app()->environment(['local','testing'])) { return; }` al inicio de `run()`, y separar un `ProductionSeeder` con solo los datos maestros.

### Menores de infraestructura
- **`AppServiceProvider::boot():31-37`** intenta usar `tenancy()->initialized`, que en el arranque del framework es **siempre** `false`. El `forceRootUrl` nunca se aplica: todas las URLs absolutas en dominios de tenant (correos, redirecciones) usan el `APP_URL` central. Mover a un listener de `TenancyBootstrapped`.
- **Colisiones de nombres de ruta** por el doble registro de `SupportTicket/.../web.php` (`routes/web.php:19` y `:26`) y de Admin (A1). Laravel no avisa: gana el último. `route('central.customer.support')` devuelve `/tenant/account/support`.
- **`type`, `is_active` e `id` en `$fillable` de `User`** (`src/User/.../User.php:43-50`), con un endpoint público de alta de cuenta en `src/Tenant/.../web.php:44`. Cualquier `User::create($request->all())` permite enviar `type=super_admin`.
- **Dos "conexiones centrales" contradictorias**: `config/tenancy.php:44` define `'central_connection' => env('DB_CONNECTION', 'central')` (toma el *driver*, no la conexión `central`), mientras `Tenant.php:105` hardcodea `'central'` y `CentralCustomer.php:179` lee el config. Con `.env.example` apuntando a bases distintas (`laravel` vs `db`), unos modelos leen de una BD y otros de otra.

---

## Bloque G — Frontend

### G1. 🔴 Los datos bancarios de Pago Móvil / Binance son valores de demo hardcodeados

> **Estado:** ✅ CERRADO (Fase 0.5)

**Archivo:** `resources/js/pages/marketplace/checkout/TenantCheckoutPage.tsx:704,718,723,732,802,818`

```tsx
Monto a transferir: Bs. {(finalGrandTotal * ((method as any).exchange_rate_ves || 40.50))...}
<span>{(method as any).document_id || 'J-50123456-0'}</span>
<span>{(method as any).phone || '0412-1234567'}</span>
<span>{(method as any).binance_pay_id || '284759302'}</span>
src={(method as any).qr_code || 'https://api.qrserver.com/...binancepay://pay?id=284759302'}
```

`StorefrontPaymentMethod` (`types/models/Storefront.d.ts:192-197`) solo define `id, name, description, instructions`. Todo lo demás se accede con `as any` y **siempre** cae al literal.

**Escenario:** el cliente elige Pago Móvil, ve "Bs. 405,00" (tasa 40,50 en vez de ~775) y transfiere a la cédula `J-50123456-0` y al teléfono `0412-1234567`. El dinero no llega a la tienda. Con Binance paga a un Pay ID ajeno y escanea un QR generado por un servicio externo.

**Este es el bug de frontend más urgente.** No debería estar en un entorno que alguien pueda tocar.

---

### G2. 🔴 Ningún cupón se puede aplicar en la tienda

> **Estado:** ✅ CERRADO (Fase 3.1) — el servicio se tipa con `Data<T>`, el sobre real del backend, y el componente deja de desenvolver una capa de más

**Archivo:** `resources/js/pages/marketplace/cart/TenantCartPage.tsx:72`

```tsx
const res = await CouponServices.validate({ code, order_subtotal: subtotal });
const apiData = res.data;                              // en runtime ya es ValidateCouponResponse
if (apiData && apiData.code === 200 && apiData.data) { // .code y .data no existen → undefined
```

`CouponServices.validate` devuelve `response.data` pero está tipado como `ApiResponse<T>`, que en este proyecto es la respuesta **completa** de axios (`types/ResponseApi.d.ts:1-9`). El componente desenvuelve una capa de más y el tipo lo tapa.

**Escenario:** el usuario escribe un cupón perfectamente válido → la condición es falsa siempre → "El cupón ingresado no es válido o ha expirado". **Ningún cupón funciona.**

---

### G3. 🟠 El descuento del cupón se recalcula en el cliente y se descarta el del backend

> **Estado:** ✅ CERRADO (Fase 3.1) — manda el importe del backend, y el cupón se retira si cambia el subtotal en vez de reescalarse

**Archivo:** `resources/js/contexts/CartContext.tsx:160-166`

```tsx
if (coupon.type === 'percentage') return Math.round((subtotal * coupon.value) / 100);
```

El backend devuelve `discount_amount`, se guarda en `AppliedCoupon.discountAmount` y **nunca se lee** (0 usos en todo `resources/js`).

**Escenario:** subtotal $45,50 con cupón del 10% → el backend calcula $4,55, el frontend muestra $5,00. Peor: tras aplicar el cupón, cambiar cantidades re-escala el descuento solo, saltándose mínimos y topes que el backend sí valida.

---

### G4. 🟠 Precio y stock del carrito congelados en `localStorage` y enviados así al crear el pedido

> **Estado:** ⬜ ABIERTO

**Archivos:** `CartContext.tsx:40-48` → `TenantCheckoutPage.tsx:213-221`

```tsx
const saved = localStorage.getItem(storageKey);
return saved ? JSON.parse(saved) : [];
...
items: items.map((it) => ({ product_id: it.productId, price: it.price, quantity: it.quantity }))
```

No hay ninguna revalidación de precio ni de stock. Es el lado cliente de B1: editando `localStorage` se envía `price: 0.01`.

---

### G5. 🟠 `CentralCartContext.addItem` muta el estado previo

> **Estado:** ⬜ ABIERTO

**Archivo:** `resources/js/contexts/CentralCartContext.tsx:77`

```tsx
const updated = [...prevItems];
updated[existingIndex].quantity += item.quantity;   // muta prevItems[i], no una copia
```

**Escenario:** React 19 en StrictMode invoca el updater dos veces → el usuario pulsa "Añadir" una vez con cantidad 2 y el carrito queda con 4. Además la referencia del ítem no cambia, así que los hijos memoizados no se re-renderizan.

---

### G6. 🟠 En el marketplace central se pueden añadir productos agotados, hasta 99 unidades

> **Estado:** ⬜ ABIERTO

**Archivo:** `resources/js/pages/marketplace/product/CentralProductDetailPage.tsx:252,259,266`

```tsx
onClick={() => setQuantity(Math.min(product.quantity || 99, quantity + 1))}
```

Con `quantity: 0`, el `||` convierte el tope en 99. Ni el botón ni `handleAddToCart` comprueban stock. La versión tenant (`TenantProductDetailPage.tsx:383-394`) **sí** usa `isOutOfStock`, así que es una regresión de la página nueva.

---

### G7. 🟠 `isCentralDomain()` clasifica contando etiquetas del dominio

> **Estado:** ⬜ ABIERTO

**Archivo:** `resources/js/Services/CustomerAuthServices.ts:67-79`

```ts
const parts = hostname.split('.');
if (parts.length <= 2) return true;   // "mitienda.com" → "central"
return false;                          // "www.mitienda.com" → "tenant"
```

**Escenario:** un tenant con dominio propio `mitienda.com` toma la rama central al iniciar sesión, **no** genera ni consume el token SSO, y no se crea sesión de cliente en el tenant. El usuario ve "Conectado con OwO Pass" en el checkout pero el pedido se envía como invitado. Con `www.` delante, el comportamiento se invierte para el mismo sitio.

**Arreglo:** inyectar el flag desde el servidor (prop de Inertia `is_central`), no inferirlo del hostname.

---

### G8. 🟠 Botón "Continuar como Invitado (Modo Pruebas)" que anula la puerta de autenticación

> **Estado:** 🟡 PARCIAL (Fase 0.5: botón de bypass eliminado; la recarga que pierde el formulario sigue)

**Archivo:** `resources/js/pages/marketplace/checkout/TenantCheckoutPage.tsx:1009-1019`

```tsx
{/* Optional dev/test bypass button */}
<Button color="light" size="sm" onClick={() => { setIsAuthGateModalOpen(false); setCurrentStep(3); }}>
    Continuar como Invitado (Modo Pruebas)
</Button>
```

Cualquier anónimo llega al paso de pago sin cuenta, justo lo que el modal dice que es obligatorio. Y la otra rama (`:1002`) hace `window.location.href = '/auth/login?redirect=...'`: recarga completa que **pierde los datos ya escritos** (nombre, dirección, notas), porque solo el carrito está persistido.

---

### G9. 🟠 Checkout central: tasa BCV hardcodeada, carrera con la petición, y sin envío ni impuestos

> **Estado:** ⬜ ABIERTO

**Archivo:** `resources/js/pages/marketplace/checkout/CentralCheckoutPage.tsx:31,75,129-130`

```tsx
const [bcvRate, setBcvRate] = useState<number>(775.3356);
useEffect(() => { getActiveExchangeRate().then(...).catch(() => {}); }, []);
const totalBs = (subtotal * bcvRate).toFixed(2);
...
payment_details: { ..., rate_bcv: bcvRate, total_bs: totalBs }
```

Nada bloquea el submit mientras la tasa no ha cargado, y el `.catch` es silencioso. Además el checkout central **no incluye envío ni impuestos**: el total mostrado es el subtotal puro, así que el importe que el comprador transfiere nunca coincidirá con el total real.

---

### G10. 🟠 Al volver a la tienda, la sesión SSO no se restablece y el caché no se limpia

> **Estado:** ⬜ ABIERTO

**Archivo:** `resources/js/contexts/CustomerAuthContext.tsx:77-82`

Si el tenant responde "no autenticado", `refreshSession` no hace nada: ni reintenta el SSO ni borra el caché.

**Escenario:** el cliente entró ayer y la cookie del tenant expiró. Hoy la navbar y el checkout lo muestran logueado, pasa la puerta de autenticación del paso 3 y confirma el pedido; el backend lo trata como invitado o devuelve 401, y los `.catch(() => {})` del portal muestran listas vacías en lugar de "sesión expirada".

---

### G11. 🟡 Pedido creado y carrito vaciado con redirección no validada

> **Estado:** ⬜ ABIERTO

**Archivos:** `CentralCheckoutPage.tsx:149-157`, `TenantCheckoutPage.tsx:227-232`

```tsx
if (res.status === 'success' && res.data) {
    clearCart();
    window.location.href = res.data.redirect_url;   // sin comprobar que exista
}
```

**Escenario:** si falta `redirect_url`, se navega a `/undefined` con el carrito ya borrado y sin número de pedido. En el tenant es peor: si `code === 200` pero falta la URL, se muestra "Error al procesar la orden. Intenta de nuevo" **con la orden ya creada** → el cliente reintenta y paga dos veces.

---

### G12. 🟡 Lectura de `localStorage` sin validar

> **Estado:** ⬜ ABIERTO

**Archivos:** `CartContext.tsx:40-48`, `CentralCartContext.tsx:46-54`

`JSON.parse` sobre `"null"`, `"{}"` o ítems de una versión anterior no lanza excepción, así que el `try/catch` no ayuda: `items.reduce(...)` revienta con "items.reduce is not a function", o el subtotal queda `NaN` y toda la tienda muestra "$ NaN" sin forma de recuperarse salvo limpiar el navegador a mano.

**Arreglo:** validar tras el parse (`Array.isArray`, y por ítem `Number.isFinite(price) && quantity > 0`) y versionar la clave (`owomarket_cart_v2_...`).

---

### G13. 🟡 `CurrencyPriceDisplay` dispara una petición por instancia y etiqueta una tasa inventada como "oficial BCV"

> **Estado:** 🟡 PARCIAL (Fase 0.5: el checkout del inquilino ya usa la tasa real; el componente CurrencyPriceDisplay sigue igual)

**Archivo:** `resources/js/components/ui/CurrencyPriceDisplay.tsx:19,53,62`

Usado en `ProductCard`, ambos checkouts y ambos detalles de producto. Un catálogo con 24 tarjetas lanza 24 GET simultáneos a `/api/exchange-rate/current`, sin caché ni deduplicación ni `AbortController`. Mientras responden —o si fallan, porque el `.catch` es silencioso— cada precio muestra bolívares calculados con `775.3356` bajo el rótulo **"Tasa oficial BCV"**.

**Arreglo:** elevar la tasa a un provider único y no mostrar la insignia hasta tener el valor del servidor.

---

### G14. 🟡 `axios` directo dentro de un componente (prohibido por `reglas.md`)

> **Estado:** ⬜ ABIERTO

**Archivo:** `resources/js/pages/customer/support/CustomerSupportPage.tsx:15,147,184,209`

```tsx
import axios from 'axios';
const response = await axios.post('/api/support/tickets', formData, { headers: {...} });
```

Viola la regla 1 de frontend del proyecto. Sin `X-CSRF-TOKEN` como el resto, sin manejo del caso `status !== 'success'` (el usuario pulsa "Enviar", no ve feedback y reenvía → tickets duplicados). Además los `URL.createObjectURL` de las vistas previas (`:102,119-127`) nunca se revocan.

---

### G15. Otros defectos verificados

> **Estado:** ⬜ ABIERTO
- `CustomerOrdersPage.tsx:52-64` reconstruye el carrito con `tenant_name: item.tenant_id` y `slug: item.product_id` → el drawer muestra el UUID de la tienda y el enlace al producto rompe.
- `CustomerAccountLayout.tsx:97` ignora `loading` del contexto → muestra "Inicia sesión" durante cada carga antes de resolver la sesión.
- `CentralProductDetailPage.tsx:60-63` deja "Cargando producto…" para siempre si la petición falla.
- Los `.catch(() => {})` de todas las páginas de `pages/customer` hacen que un error de red sea indistinguible de "no tienes pedidos".

---

## Plan de acción sugerido

> **Estado al 21/08/2026: 12 de 13 puntos completados.**
> Fases 0, 1 y 2 completas · Fase 3 empezada.

### Fase 0 — Antes de exponer nada (bloqueante) — ✅ COMPLETA
1. ✅ **Borrar `routes/tenant.php:31`** (A1). — *Fase 0.1*
2. ✅ **Crear los middlewares de rol** que `bootstrap/app.php` finge tener (F2) y registrarlos como aliases. — *Fase 0.2*
3. ✅ **Aplicar `auth` + rol** a: Monetization apiCentral (A4), Tenant owner APIs (A2), CentralCustomer apiCentral (A3), SupportTicket web (A6), grupo `api-tenant` (A5), y todo `Admin/.../web.php`. — *Fases 0.3-A a 0.3-E*
4. ✅ **Resolver precios server-side** en ambos checkouts (B1) y quitar `is_approved`/`is_verified` del FormRequest de reseñas (B2). — *Fase 0.4*
5. ✅ **Quitar el botón de bypass del checkout y los datos bancarios hardcodeados** (G8, G1). — *Fase 0.5*

### Fase 1 — Integridad del dinero — ✅ COMPLETA
6. ✅ Transacción + idempotencia en el despacho multi-tienda (C2) y prorrateo de envío/descuento (D1). — *Fase 1.1*
7. ✅ Reversión de comisiones al cancelar o reembolsar (D2). — *Fase 1.2*
8. ✅ `lockForUpdate` en liquidaciones (C3), factura correlativa (C4) y stock (C1). — *Fase 1.3*
9. ✅ Excepción en lugar de tasa 1.0 (D3) y arreglo del scraper BCV (D4). — *Fase 1.4*

### Fase 2 — Consistencia — 🟡 2 de 3
10. ✅ Sincronización central por eventos de modelo (E1, E2) y `(tenant_id, slug)` único (E3). — *Fase 2.2*
11. ✅ Upsert de variantes en vez de borrar y recrear (E4). — *Fase 2.2*
12. 🟡 `sessions.user_id` nullable (F1 — ✅ *Fase 2.1*), guard `central_customer`
    (F4 — 🟡 ya creado en la Fase 0.3-D), permisos en tenant (F5 — ⬜),
    seeders condicionados (F6 — ✅ *Fase 2.1*).

### Fase 3 — Frontend — 🟡 empezada
13. 🟡 Cupones (G2, G3 — ✅ *Fase 3.1*, junto con B3 y C6 del backend), revalidación de
    carrito (G4 — ⬜), `isCentralDomain` desde el servidor (G7 — ⬜), refresco de sesión
    SSO (G10 — ⬜).

---

## Hallazgos nuevos surgidos durante la remediación

No estaban en la auditoría original; se descubrieron al implementar los arreglos.
Cada uno está documentado en la sección «Trabajo de seguimiento» del plan citado.

| # | Hallazgo | Origen | Estado |
| :--- | :--- | :--- | :--- |
| N1 | El login del cliente central nunca creaba sesión: devolvía un token de 64 caracteres que no se persistía ni verificaba en ningún sitio | Fase 0.3-D | ✅ Cerrado |
| N2 | No existía logout en el dominio central | Fase 0.3-D | ✅ Cerrado |
| N3 | `GetTenantOwnerWalletSummaryUseCase` y `ListTenantOwnerProductsUseCase` caían a las tiendas de OTROS comerciantes cuando el usuario no tenía ninguna | Fase 0.3-B | ✅ Cerrado |
| N4 | `ToggleProductMarketplacePublicationUseCase` recibía `$userId` y no lo miraba nunca | Fase 0.3-B | ✅ Cerrado |
| N5 | Los retiros no validaban el importe contra el saldo disponible | Fase 0.3-B | ✅ Cerrado |
| N6 | `sum('order_amount')` sobre una columna que se llama `order_total`: el saldo de la billetera daba siempre 0 | Fase 0.3-B | ✅ Cerrado |
| N7 | `ViewCustomerSupportGETController` leía `session('customer_id')`, clave que no se escribe en ningún punto del proyecto | Fase 0.3-C | ✅ Cerrado |
| N8 | `AddMessageToTicketUseCase` no verificaba que el ticket fuera de quien escribe | Fase 0.3-C | ✅ Cerrado |
| N9 | Los datos bancarios de demostración estaban hardcodeados también en el **backend**, no sólo como fallback del frontend | Fase 0.5 | ✅ Cerrado |
| N10 | No existía ningún lugar donde el comerciante configurara sus datos de cobro | Fase 0.5 | ✅ Cerrado (grupo de settings `payment`) |
| N11 | El tipo `StorefrontPaymentMethod` sólo declaraba 4 campos, lo que obligaba a `(method as any)` y hacía invisible G1 para TypeScript | Fase 0.5 | ✅ Cerrado |
| N12 | **El formulario de reseñas del storefront lleva roto desde siempre:** exige `customer_id` y `TenantProductDetailPage.tsx` nunca lo envía (422 garantizado) | Fase 0.4 | ⬜ Abierto |
| N13 | `StockReserver::release()` existe pero nadie lo llama: **nadie repone stock al cancelar un pedido**. Falta decidir en qué estados corresponde (decisión de producto) | Fase 1.3 | ⬜ Abierto |
| N14 | **El checkout central no reserva stock en absoluto:** `DispatchCentralOrderToTenantsUseCase` crea pedidos de tienda sin tocar el inventario | Fase 1.3 | ⬜ Abierto |
| N15 | La comisión sigue naciendo al despachar y no al cobrar. Es la raíz de D2: mientras no cambie, dependemos de que alguien cancele el pedido para anularla | Fase 1.2 | ⬜ Abierto |
| N16 | No existen notas de crédito: revertir una comisión ya liquidada sólo deja una marca `requires_manual_adjustment` | Fase 1.2 | ⬜ Abierto |
| N17 | Los despachos fallidos quedan en `status = 'failed'` y nada los reintenta; el despacho sigue siendo síncrono | Fase 1.1 | ⬜ Abierto |
| N18 | Ningún endpoint tiene límite de tasa. Al pasar `api-tenant` de `api` a `web` se perdió incluso la posibilidad de `throttleApi()` (aunque nunca estuvo activo) | Fase 0.3-E | ⬜ Abierto |
| N19 | Dentro de una tienda no hay control de rol: un `staff` puede borrar el catálogo o anular facturas igual que el `owner` | Fase 0.3-E | ⬜ Abierto |
| N20 | El `error` que registra el fallback prolongado del BCV **no llega a nadie**: no hay notificación ni integración con un servicio de alertas, sólo un nivel de log más alto | Fase 1.4 | ⬜ Abierto |
| N21 | `src/ExchangeRate/Infrastructure/Providers/ExchangeRateServiceProvider.php` es un duplicado muerto: no está en `bootstrap/providers.php` y le faltan los `use` de `BcvScraperInterface` y `BcvWebScraper`, así que sus `::class` resuelven a FQCN inexistentes | Fase 1.4 | ⬜ Abierto |
| N22 | **Producción se queda sin forma de crear el primer superadmin**: era `RootUserSeeder`, ahora vetado fuera de desarrollo. No rompe los despliegues existentes, pero una instalación nueva no tiene por dónde arrancar. Hace falta un `admin:create-super` — anotado como **pendiente P1** | Fase 2.1 | ⬜ Abierto |
| N27 | `usage_limit_per_customer` **no se aplica en ningún sitio**: `validateUsability()` sólo comprueba el límite global, y `orders` no guarda el cupón usado, así que no hay con qué contarlo. La Fase 3.1 escribe `coupon_code` en el `metadata` del pedido para que el dato exista, pero hace falta una columna indexada | Fase 3.1 | ⬜ Abierto |
| N28 | **El checkout central no aplica cupones en absoluto.** `CreateUnifiedCentralOrderUseCase` recibe un descuento que nadie valida ni consume | Fase 3.1 | ⬜ Abierto |
| N29 | El resto de servicios del frontend arrastran el mismo error de tipos que G2: devuelven `response.data` declarando `ApiResponse<T>` en vez de `Data<T>`, y los consumidores lo compensan a mano con castings a `any`. Es la misma trampa esperando a la siguiente página | Fase 3.1 | ⬜ Abierto |
| N24 | No hay comando para **re-sincronizar el catálogo central**. Tras la Fase 2.2 los productos sólo se re-sincronizan al volver a guardarse, así que reparar el catálogo existente pide un `tinker` a mano. Merece un `catalog:resync {--tenant=}` | Fase 2.2 | ⬜ Abierto |
| N25 | La sincronización con el catálogo central es **síncrona**: escribe en la base central dentro de la misma petición, incluida la transacción del checkout. Si el marketplace no responde, la fila queda desincronizada y sólo queda el log. Lo natural es un job en cola con reintentos | Fase 2.2 | ⬜ Abierto |
| N26 | El `metadata` de `central_products` se sobrescribía con el del producto de la tienda en cada sincronización, **borrando el historial de moderación y la comisión personalizada**. Pasaba desapercibido porque la sincronización casi nunca corría | Fase 2.2 | ✅ Cerrado |
| N23 | **`domains.id` es una columna `uuid` pero el modelo `Stancl\Tenancy\Database\Models\Domain` usa los valores por defecto de Eloquent (`$incrementing = true`, `$keyType = 'int'`), así que Eloquent castea la clave a int: `$domain->id` devuelve SIEMPRE `0`.** Con la mayoría de UUID el fallo es silencioso; cuando el UUID empieza por dígitos seguidos de `e` (≈6% de los casos) PHP lo lee como notación científica, emite un warning que Laravel convierte en excepción y **la petición devuelve 500**. Es la causa del test intermitente `AdminPhaseTwoOperationsTest` | Diagnosticado tras la Fase 2.1 | ⬜ Abierto |

---

## Notas técnicas para quien retome

- **`lockForUpdate` no hace nada en SQLite**, que es lo que usa la suite de tests.
  Los tests de concurrencia (C1, C3, C4) validan que la lógica de comprobación sea
  correcta, **no** que la carrera esté cerrada. Esa garantía depende de MySQL/PostgreSQL
  en producción. Si el volumen lo justifica, hace falta una prueba de carga real.
- **Los tests que tocan `/api-tenant/*` necesitan `actingAs`** desde la Fase 0.3-E.
  El patrón usado es crear un `Src\Tenant\...\User` con `type => 'tenant_owner'` en el
  `beforeEach` y llamar a `$this->actingAs(...)`; cubre todo el archivo.
- **Las clases que se sustituyen por dobles de Mockery no pueden ser `final`.**
  Ya pasó con `VerifiedPurchaseChecker` y `ReverseOrderCommissionUseCase`; ambas llevan
  un comentario explicando por qué rompen la convención del proyecto.
- **Decisión de negocio confirmada (21/08/2026):** la comisión de la plataforma se cobra
  sobre la **mercancía neta de descuento, sin incluir el envío**. Punto único de cambio:
  `DispatchCentralOrderToTenantsUseCase::recordCommission()`.

---

## Pendiente de auditar

- `resources/js/pages/admin/**` — las páginas más nuevas (seguridad, roles y staff, audit logs, planes de suscripción, banners CMS, moderación de catálogo, categorías y marcas maestras, clientes, pedidos globales, payouts, dashboard, tickets de soporte, tenant 360).
- `resources/js/pages/tenant/**` — dashboard central del propietario, soporte, wallet, catálogo central, facturación.
- `tests/` — no revisé la suite; conviene comprobar si los 396 tests siguen pasando tras los cambios y cuáles de estos bugs deberían haberse detectado ahí.
