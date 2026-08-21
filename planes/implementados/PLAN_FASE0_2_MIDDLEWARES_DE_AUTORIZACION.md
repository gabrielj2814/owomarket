# PLAN — Fase 0.2: Crear los middlewares de autorización y registrar sus alias

> **Origen:** hallazgo F2 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md`
> **Severidad:** 🔴 Crítico (causa raíz de todo el Bloque A de la auditoría)
> **Tamaño:** 3 archivos nuevos + 1 modificado
> **Estado:** ✅ Implementado — pendiente de validación con tests
> **Depende de:** Fase 0.1 (`PLAN_FASE0_1_DESMONTAR_BACKOFFICE_ADMIN_DE_TENANT.md`)

---

## 1. Problema

`bootstrap/app.php` importaba dos clases que **no existen en disco**:

```php
use App\Http\Middleware\TenantAuthentication;   // ← no existe
use App\Http\Middleware\VerifyCsrfToken;        // ← no existe
```

`app/Http/Middleware/` solo contiene `CorsHeaders.php` y `HandleInertiaRequests.php`. Y `withMiddleware()` nunca llamaba a `$middleware->alias(...)`, por lo que **no existía ningún alias de middleware de autorización en toda la aplicación**.

Consecuencia directa: escribir `->middleware('admin')` o `->middleware('role:super_admin')` en cualquier ruta habría producido `Target class [admin] does not exist`. Por eso las 40+ rutas del backoffice central se protegieron únicamente con `auth`, que solo comprueba *"hay alguien logueado"*, no *"es superadministrador"*.

**Esta es la causa raíz de los 9 hallazgos de autorización del Bloque A.** Sin esta fase, la Fase 0.3 (aplicar `auth`+rol a las rutas desprotegidas) es imposible.

---

## 2. Contexto descubierto durante la implementación

Dos hallazgos que simplificaron el diseño:

1. **`spatie/laravel-permission ^8.3` ya está instalado** (`composer.json:20`) y `Src\User\...\User` usa el trait `HasRoles` con `guard_name = 'web'`.

2. **El RBAC ya está diseñado.** `ListStaffRolesAndPermissionsUseCase::ensureDefaultPermissionsAndRolesExist()` (líneas 74-110) crea de forma perezosa:

   | Permiso | Descripción |
   | :--- | :--- |
   | `manage_tenants` | Gestionar tiendas inquilinas y gobernanza |
   | `manage_orders` | Monitorear órdenes globales y resolver disputas |
   | `manage_customers` | Directorio central de clientes y bloqueos |
   | `manage_payouts` | Aprobación de liquidaciones y comprobantes |
   | `manage_support` | Atención de tickets en mesa central |
   | `manage_catalog` | Taxonomía de categorías y marcas maestras |
   | `manage_moderation` | Moderación de productos marketplace |
   | `manage_cms` | Banners y campañas en portada |
   | `manage_plans` | Planes de suscripción B2B |
   | `manage_staff_roles` | Gestión de roles RBAC y permisos |
   | `view_audit_logs` | Pista de auditoría de seguridad |

   Y cuatro roles: **Super Admin** (todos los permisos), **Agente de Soporte**, **Moderador de Catálogo**, **Gestor Financiero**.

No hubo que inventar un modelo de permisos: solo construir el middleware que lo consuma.

---

## 3. Decisión de ubicación

Los middlewares se crearon en **`src/Shared/Infrastructure/Http/Middleware/`**, no en `app/Http/Middleware/`.

**Razón:** el proyecto ya tiene ese precedente con `InternalServiceMiddleware.php`, y `reglas.md` (regla 2 de backend) establece que la infraestructura vive en `src/`. `app/Http/Middleware/` queda reservado para middleware puramente de framework (`CorsHeaders`, `HandleInertiaRequests`).

---

## 4. Archivos creados

### 4.1 `src/Shared/Infrastructure/Http/Middleware/EnsureUserIsSuperAdmin.php`

Alias: **`super_admin`**

Comprueba, en orden: autenticado (401 si no) → `is_active !== false` (403) → `type === 'super_admin'` (403).

```php
Route::post('/api/security/roles', Controller::class)->middleware(['auth', 'super_admin']);
```

### 4.2 `src/Shared/Infrastructure/Http/Middleware/EnsureUserHasStaffPermission.php`

Alias: **`staff`** — acepta parámetros con lógica **OR**.

```php
Route::get('/api/payouts', Controller::class)->middleware(['auth', 'staff:manage_payouts']);
Route::get('/api/orders', Controller::class)->middleware(['auth', 'staff:manage_orders,manage_customers']);
```

**Decisión de diseño crítica — el super administrador pasa siempre, sin consultar la tabla de permisos.** Sin ese atajo el sistema sería imposible de arrancar: los roles de Spatie se crean *de forma perezosa* la primera vez que alguien abre la pantalla de roles, así que un superadmin recién sembrado por `RootUserSeeder` no tiene ningún rol asignado y quedaría fuera de su propio panel — incluido el panel desde el que se asignan los roles.

**Manejo defensivo del contexto de tenant.** Las tablas de Spatie solo existen en la base de datos central (hallazgo F5 de la auditoría). Si una ruta con este middleware se alcanzara dentro del contexto de un inquilino, `$user->can()` lanzaría `Base table or view not found: 'roles'`. El middleware captura esa excepción, **deniega** el acceso (nunca lo concede) y registra un `warning` con el host y el permiso evaluado, porque llegar ahí significa que una ruta central está montada donde no debe.

### 4.3 `src/Shared/Infrastructure/Http/Middleware/EnsureUserIsTenantOwner.php`

Alias: **`tenant_owner`**

Acepta `tenant_owner` (identidad en la BD central), `owner` (identidad aprovisionada dentro de la BD del inquilino) y `super_admin`. El mismo propietario tiene un valor distinto en `type` según el contexto en el que inició sesión, de ahí que se acepten los dos.

> ⚠️ Comprueba el **rol**, no la **propiedad**. No verifica que el usuario sea dueño del `tenant_id` concreto que viaja en la petición. Esa comprobación va en los casos de uso, en la Fase 0.3 (hallazgos A2 y A9).

---

## 5. Archivo modificado: `bootstrap/app.php`

```diff
 use App\Http\Middleware\CorsHeaders;
 use App\Http\Middleware\HandleInertiaRequests;
-use App\Http\Middleware\TenantAuthentication;
-use App\Http\Middleware\VerifyCsrfToken;
 use Illuminate\Foundation\Application;
...
+use Src\Shared\Infrastructure\Http\Middleware\EnsureUserHasStaffPermission;
+use Src\Shared\Infrastructure\Http\Middleware\EnsureUserIsSuperAdmin;
+use Src\Shared\Infrastructure\Http\Middleware\EnsureUserIsTenantOwner;
+use Src\Shared\Infrastructure\Http\Middleware\InternalServiceMiddleware;
```

```diff
         $middleware->web(append: [
             HandleInertiaRequests::class,
             AddLinkHeadersForPreloadedAssets::class,
             CorsHeaders::class
         ]);
+
+        $middleware->alias([
+            'super_admin'  => EnsureUserIsSuperAdmin::class,
+            'staff'        => EnsureUserHasStaffPermission::class,
+            'tenant_owner' => EnsureUserIsTenantOwner::class,
+            'internal'     => InternalServiceMiddleware::class,
+        ]);
     })
```

Se aprovechó para registrar también `internal` → `InternalServiceMiddleware`, que existía en `src/Shared/` pero no tenía alias, de modo que las rutas de API interna puedan protegerse con `->middleware('internal')` en la Fase 0.3.

---

## 6. Respuestas de error

Los tres middlewares distinguen el tipo de petición:

- **Peticiones JSON** (`$request->expectsJson()`): devuelven `ApiResponse::error()`, respetando la regla 5 de backend de `reglas.md` — estructura `{status, code, message, data, errors}` compatible con el tipado TypeScript del frontend.
- **Peticiones de navegación** (Inertia): `abort($code, $message)`, que renderiza la página de error de Laravel.

Códigos: **401** si no hay sesión, **403** si la hay pero el rol o el permiso no alcanzan, **403** si la cuenta está desactivada.

---

## 7. Tareas

- [x] Crear `EnsureUserIsSuperAdmin.php`
- [x] Crear `EnsureUserHasStaffPermission.php`
- [x] Crear `EnsureUserIsTenantOwner.php`
- [x] Registrar los cuatro alias en `bootstrap/app.php` y eliminar los dos `use` muertos
- [x] Verificar sintaxis con `php -l` en los cuatro archivos
- [ ] `composer dump-autoload`
- [ ] `php artisan config:clear && php artisan route:clear`
- [x] `php artisan test` — debe pasar al 100%
- [x] `npm run types` — 0 errores
- [x] `vendor/bin/pint` sobre los archivos nuevos
- [x] Commit: `feat(shared): añadir middlewares de autorización super_admin, staff y tenant_owner`
- [x] `git push origin <rama_actual>`
- [x] Mover este documento a `planes/implementados/`

---

## 8. Riesgo

**Ninguno para el comportamiento actual.** Esta fase es puramente aditiva:

- Los tres middlewares son clases nuevas que **ninguna ruta usa todavía**.
- El registro de alias no altera el pipeline de ninguna petición existente; solo hace que los nombres estén disponibles.
- Los dos `use` eliminados apuntaban a clases inexistentes, así que borrarlos no puede romper nada (de hecho elimina el riesgo de un error fatal de autoload si alguien hubiera intentado usarlos).

La aplicación se comporta exactamente igual después de esta fase que antes. El cambio de comportamiento llega en la Fase 0.3.

---

## 9. Verificación manual sugerida

Tras `composer dump-autoload`, comprobar en `php artisan tinker` que los alias resuelven:

```php
app(\Illuminate\Contracts\Http\Kernel::class)->getRouteMiddleware()['super_admin'];
// => "Src\Shared\Infrastructure\Http\Middleware\EnsureUserIsSuperAdmin"
```

O más simple: añadir temporalmente `->middleware('super_admin')` a una ruta de prueba y confirmar que no lanza `Target class [super_admin] does not exist`.

---

## 10. Prerrequisito que deja preparado para la Fase 0.3

Antes de aplicar `staff:<permiso>` a las rutas del backoffice hay que **asignar el rol "Super Admin" de Spatie a los usuarios con `type = 'super_admin'`**, o al menos confirmar que el atajo del apartado 4.2 los cubre (lo hace).

Conviene además decidir en la Fase 0.3:

1. Si `RootUserSeeder` debe asignar el rol de Spatie además de escribir `type = 'super_admin'`.
2. Si los permisos y roles por defecto deben crearse en una migración o un seeder propio, en lugar de perezosamente al abrir la pantalla de roles — hoy, si nadie abre esa pantalla, la tabla `roles` está vacía.

---

## 11. Qué NO resuelve esta fase

Las rutas siguen exactamente igual de desprotegidas que antes. Todos los hallazgos del Bloque A (A1 ya cerrado en la Fase 0.1, A2 a A9) siguen abiertos. Esta fase solo construye la herramienta; la Fase 0.3 la aplica:

- **A2** — APIs del tenant owner sin `auth` → `['auth', 'tenant_owner']` + verificación de propiedad en el caso de uso
- **A3** — API de clientes centrales anónima → requiere además crear el guard `central_customer` (hallazgo F4)
- **A4** — API de monetización anónima → `['auth', 'super_admin']`
- **A5** — grupo `api-tenant` sin `auth` → requiere decidir el mecanismo (Sanctum o sesión)
- **A6** — mesa de soporte central abierta → `['auth', 'staff:manage_support']`
- **A7** — PIN fuerza-brutable → `throttle` + usar `auth()->id()`
- **Backoffice central** — las 40+ rutas de `src/Admin/.../Routes/web.php` pasan de `auth` a `auth` + `staff:<permiso correspondiente>`
