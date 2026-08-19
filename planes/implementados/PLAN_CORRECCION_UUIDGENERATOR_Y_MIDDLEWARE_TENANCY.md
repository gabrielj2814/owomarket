# 📋 Plan de Corrección: Inyección de `UuidGenerator` en Casos de Uso y Middleware `HandleInertiaTenancy`

> **Estado:** Implementado y validado al 100% ✅
> **Rama de trabajo:** `moduleProduct`
> **Módulos afectados:** `Admin`, `Tenant`, capa `app/Http/Middleware`
> **Tipo:** `fix` (corrección de bugs fatales en tiempo de ejecución)

---

## 1. 🔍 Diagnóstico y Causa Raíz

### Bug A — `TypeError` fatal por falta de `UuidGenerator` en los factory methods

Las entidades de dominio de `Admin` y `Tenant` fueron refactorizadas para respetar la **Regla de Aislamiento del Dominio** (`reglas.md` §2.1): en lugar de generar UUIDs con helpers de Laravel, reciben el contrato `Src\Shared\Domain\Contracts\UuidGenerator` como **primer parámetro obligatorio** de su método estático `create()`.

Los Casos de Uso que las invocan **nunca fueron sincronizados con esa nueva firma**. Firmas actuales de las entidades:

| Entidad | Firma de `create()` | Nº params |
| :--- | :--- | :---: |
| `Src\Admin\Domain\Entities\Admin` | `create(UuidGenerator, UserName, UserEmail, ?Password, ?EmailVerifiedAt, ?PinVerification, UserType, ?PhoneNumber, ?AvatarUrl, UserStatus, ?CreatedAt, ?UpdatedAt)` | 12 |
| `Src\Tenant\Domain\Entities\Tenant` | `create(UuidGenerator, TenantName, Slug, TenantStatus, Timezone, Currency, TenantRequest)` | 7 |
| `Src\Tenant\Domain\Entities\TenantOwner` | `create(UuidGenerator, UserName, UserEmail, ?Password, ?EmailVerifiedAt, ?PinVerification, UserType, ?PhoneNumber, ?AvatarUrl, UserStatus)` | 10 |
| `Src\Tenant\Domain\Entities\TenantUser` | `create(UuidGenerator, Uuid $tenantId, Uuid $userId, RoleTenantUser, ?array $permissions, ?CreatedAt)` | 6 |

#### Call sites defectuosos (5 confirmados)

| # | Archivo | Línea | Defecto exacto | Error en runtime |
| :-: | :--- | :-: | :--- | :--- |
| 1 | `src/Admin/Application/UseCase/CreateAdminUseCase.php` | 51 | No pasa el generador; `$name` (`UserName`) cae en el parámetro `$generator` | `TypeError: Argument #1 ($generator) must be of type UuidGenerator, UserName given` |
| 2 | `src/Tenant/Application/UseCase/CreateTenantUseCase.php` | 36 | No pasa el generador; `$name` (`TenantName`) cae en `$generator` | `TypeError` idéntico |
| 3 | `src/Tenant/Application/UseCase/CreateTenantOwnerUseCase.php` | 55 | No pasa el generador; **además** envía 11 argumentos a una firma de 10 (`$create_at`, `$update_at` sobran, la entidad ya los genera internamente en línea 102-103) | `TypeError` + argumentos silenciosamente descartados |
| 4 | `src/Tenant/Application/UseCase/CreateTenantUserUseCase.php` | 30 | Pasa `$uuid_tenant` (`Uuid`) como primer argumento en lugar del generador | `TypeError: Argument #1 ($generator) must be of type UuidGenerator, Uuid given` |
| 5 | `src/Tenant/Application/UseCase/AssignTenantToUserUseCase.php` | 33 | Usa **argumentos nombrados** y omite el parámetro obligatorio `generator:` | `ArgumentCountError: Argument #1 ($generator) not passed` |

#### Impacto funcional real

Estos Casos de Uso están inyectados en controladores HTTP activos, por lo que hay **endpoints de producción caídos**:

- `src/Admin/Infrastructure/Http/Controller/CreateAdminPOSTController.php` → **creación de SuperAdmin imposible** (call site 1).
- `src/Tenant/Infrastructure/Http/Controller/CreateTenantPOSTController.php` → **creación de tienda imposible** (call sites 2 y 4).
- `src/Tenant/Infrastructure/Http/Controller/CreateAccountTenantPOSTController.php` → **registro de comerciante (alta de cuenta + tienda) imposible** (call sites 2, 3 y 5).

Es decir: el flujo completo de onboarding de comerciantes está roto de extremo a extremo, no solo los dos casos documentados originalmente en `ANALISIS_PROYECTO.md`.

#### Por qué la corrección es segura

El contrato está **unificado y ya vinculado** en el contenedor:

- Contrato único: `src/Shared/Domain/Contracts/UuidGenerator.php`
- Implementación: `src/Shared/Infrastructure/Security/LaravelUuidGenerator.php`
- Binding registrado en `AppServiceProvider`, `TenantServiceProvider`, `AdminServiceProvider`, `AuthServiceProvider` y `UserServiceProvider` (redundante, pero funcional).

Los 5 Casos de Uso se resuelven por autowiring del contenedor (se inyectan en los constructores de los controladores), por lo que **basta añadir el contrato al constructor** para que Laravel lo resuelva. No hay que modificar ningún controlador, provider ni entidad de dominio.

> **Referencia de patrón correcto ya existente:** `src/Authentication/Infrastructure/Eloquent/Repositories/AuthUserRepository.php:30-31` ya inyecta y pasa `$this->generator` correctamente. Se replicará ese mismo patrón.

---

### Bug B — Middleware `HandleInertiaTenancy` con `return` prematuro y código muerto

`app/Http/Middleware/HandleInertiaTenancy.php` (registrado en el stack `web` en `bootstrap/app.php:39`):

```php
public function handle(Request $request, Closure $next): Response
{
    return $next($request);          // ← línea 19: retorno prematuro

    // Todo lo siguiente es código inalcanzable:
    if (tenancy()->initialized) {
        Inertia::share([ 'tenant' => [...], 'current_domain' => ... ]);
        if (app()->environment('local')) {
            Inertia::setRootView('tenant-app');
        }
    }
    return $next($request);
}
```

Dos hallazgos adicionales que condicionan la solución:

1. **El código muerto no es simplemente "inactivo": está roto.** Invoca `Inertia::setRootView('tenant-app')`, pero en `resources/views/` solo existen `app.blade.php` y `invoices/`. Eliminar el `return` prematuro sin más provocaría `InvalidArgumentException: View [tenant-app] not found` en **todas** las peticiones de tenant en entorno local — es decir, el "arreglo" ingenuo rompe el sitio.
2. **Nada consume esos props hoy.** Se verificó que ningún componente de `resources/js/` lee `current_domain` ni el prop compartido `tenant` (los controladores pasan los datos del tenant explícitamente en cada página). Por tanto restaurar el share no rompe nada, pero tampoco arregla nada visible: es una corrección de deuda técnica, no un cambio de comportamiento de la UI.

Adicionalmente, compartir datos con `Inertia::share()` desde un middleware propio duplica la responsabilidad de `HandleInertiaRequests::share()`, que es el punto idiomático de Inertia y ya existe en el proyecto (`app/Http/Middleware/HandleInertiaRequests.php:38`).

**Decisión aprobada:** consolidar el share en `HandleInertiaRequests` y eliminar el middleware redundante.

---

## 2. 🛠️ Cambios Propuestos

### 🔹 Bug A — 5 Casos de Uso (`src/`)

Patrón uniforme en los 5 archivos: añadir `protected UuidGenerator $generator` al constructor + `use Src\Shared\Domain\Contracts\UuidGenerator;` + pasar `$this->generator` como primer argumento.

**A.1 — `src/Admin/Application/UseCase/CreateAdminUseCase.php`**

```php
use Src\Shared\Domain\Contracts\UuidGenerator;   // ← nuevo import

public function __construct(
    protected AdminRepositoryInterface $admin_repository,
    protected PasswordValidator $validator,
    protected PasswordHasher $hasher,
    protected UuidGenerator $generator            // ← nuevo
) {}

// línea ~51
$admin = Admin::create(
    $this->generator,                             // ← nuevo primer argumento
    $name, $email, $password, null, null,
    $type, $phone, $avatar, $state,
    $create_at, $update_at
);
```

**A.2 — `src/Tenant/Application/UseCase/CreateTenantUseCase.php`**

```php
use Src\Shared\Domain\Contracts\UuidGenerator;

public function __construct(
    protected TenantRepositoryInterface $tenantRepository,
    protected UuidGenerator $generator            // ← nuevo
) {}

// línea ~36
$tenant = Tenant::create(
    $this->generator,                             // ← nuevo
    $name, $slug, $status, $timezone, $currency, $request,
);
```

> **Mejora adicional incluida:** hoy el UUID del tenant se genera **antes** de comprobar si el slug ya está en uso (línea 45). Se reordenará para validar el slug primero y construir la entidad después — evita generar identidad de dominio para un agregado que va a ser descartado por excepción.

**A.3 — `src/Tenant/Application/UseCase/CreateTenantOwnerUseCase.php`**

```php
use Src\Shared\Domain\Contracts\UuidGenerator;

public function __construct(
    protected TenantOwnerRepositoryInterface $repository,
    protected PasswordValidator $validator,
    protected PasswordHasher $hasher,
    protected UuidGenerator $generator            // ← nuevo
) {}

// línea ~55 — se añade el generador y se eliminan $create_at / $update_at
$tenantOwner = TenantOwner::create(
    $this->generator,                             // ← nuevo
    $name, $email, $password, $emailVerifiedAt, $pin,
    $type, $phone, $avatar, $status
);                                                // ← $create_at y $update_at eliminados
```

También se eliminan las variables locales `$create_at` / `$update_at` (líneas 49-50) y sus imports `CreatedAt` / `UpdatedAt` si quedan sin uso, ya que `TenantOwner::create()` los establece internamente.

**A.4 — `src/Tenant/Application/UseCase/CreateTenantUserUseCase.php`**

```php
use Src\Shared\Domain\Contracts\UuidGenerator;

public function __construct(
    protected TenantUserRepositoryInterface $tenant_user_repository,
    protected UuidGenerator $generator            // ← nuevo
) {}

// línea ~30
$tenantUser = TenantUser::create(
    $this->generator,                             // ← nuevo
    $uuid_tenant, $uuid_owner, $role, $permisos, $create_at,
);
```

**A.5 — `src/Tenant/Application/UseCase/AssignTenantToUserUseCase.php`**

```php
use Src\Shared\Domain\Contracts\UuidGenerator;

public function __construct(
    protected TenantUserRepositoryInterface $tenantUserRepository,
    protected UuidGenerator $generator            // ← nuevo
) {}

// línea ~33 — se mantiene el estilo de argumentos nombrados
$tenantUser = TenantUser::create(
    generator: $this->generator,                  // ← nuevo
    tenantId: $tenantIdVO,
    userId: $userIdVO,
    role: $roleVO,
    permissions: $permissionsVO,
    createdAt: $create_at,
);
```

### 🔹 Bug B — Middleware (`app/`)

**B.1 — `app/Http/Middleware/HandleInertiaRequests.php`**: añadir los datos del tenant al `share()` existente, condicionados al contexto de tenancy:

```php
public function share(Request $request): array
{
    [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'quote' => ['message' => trim($message), 'author' => trim($author)],
        'auth' => ['user' => $request->user()],
        'tenant' => tenancy()->initialized ? [
            'id'   => tenant()->id,
            'name' => tenant()->name,
        ] : null,
        'current_domain' => $request->getHost(),
    ];
}
```

**B.2 — `bootstrap/app.php`**: eliminar `HandleInertiaTenancy::class` del array `$middleware->web(append: [...])` (línea 39) y su `use` (línea 5).

**B.3 — `app/Http/Middleware/HandleInertiaTenancy.php`**: eliminar el archivo (queda sin referencias).

> Se **descarta deliberadamente** la lógica de `Inertia::setRootView('tenant-app')`: apunta a una vista Blade inexistente y el proyecto usa una única raíz `app.blade.php` para central y tenant. Si en el futuro se requiere una raíz distinta por tenant, se planificará aparte junto con la creación de la vista.

### 🔹 Tipado Frontend (`resources/js/types/`)

Al pasar `tenant` y `current_domain` a props compartidos de Inertia, se declararán en la interfaz de props compartidas de TypeScript para que `npm run types` los reconozca (`SharedData` / `PageProps` en `resources/js/types/`). Se localizará la interfaz existente y se extenderá — sin crear tipos duplicados.

---

## 3. 🧪 Pruebas a Implementar (Pest)

Actualmente **no existe ni un test** que cubra estos 5 Casos de Uso ni el middleware, razón por la cual el bug pasó desapercibido. Se añaden pruebas unitarias con `UuidGenerator` mockeado (Mockery), siguiendo la organización de `tests/Unit/{Modulo}/`:

| Archivo nuevo | Cobertura |
| :--- | :--- |
| `tests/Unit/Admin/CreateAdminUseCaseTest.php` | Ejecuta el caso de uso con generador y repositorio mockeados; asegura que retorna un `Admin` con UUID válido y que se llama al repositorio una vez. |
| `tests/Unit/Tenant/CreateTenantUseCaseTest.php` | Caso feliz + excepción `Slug already in use` (código 400) cuando el slug ya existe, verificando que **no** se invoca `save()`. |
| `tests/Unit/Tenant/CreateTenantOwnerUseCaseTest.php` | Verifica creación del owner con `UserType::TENANT_OWNER`, estado activo y `createdAt` generado por la entidad. |
| `tests/Unit/Tenant/CreateTenantUserUseCaseTest.php` | Verifica que `tenantId` y `userId` se asignan en el **orden correcto** (regresión directa del bug: hoy están desplazados). |
| `tests/Unit/Tenant/AssignTenantToUserUseCaseTest.php` | Verifica el rol asignado y la construcción con argumentos nombrados. |

Cada test asertará explícitamente que **no se lanza `TypeError` ni `ArgumentCountError`**, que es la regresión que se está blindando.

---

## 4. ✅ Validación (obligatoria antes del commit — `reglas.md` §4)

```bash
vendor/bin/pint --dirty          # formateo PHP de los archivos tocados
php artisan test                 # suite completa Pest: 0 fallos
npm run types                    # tsc --noEmit: 0 errores
```

**Verificación funcional manual complementaria** (no automatizable en esta fase, sin cobertura HTTP de tenancy):

1. `POST` al endpoint de creación de admin → responde `ApiResponse::success` con el UUID generado.
2. `POST` al registro de comerciante (`CreateAccountTenantPOSTController`) → crea owner + tenant + relación `tenant_user` sin `TypeError`.
3. Cargar una página de tenant y confirmar en el payload de Inertia que `tenant` y `current_domain` llegan poblados, y que la raíz sigue siendo `app.blade.php` (no hay error de vista).

**PROHIBIDO hacer commit si algo de lo anterior falla** (`reglas.md` §4.1).

---

## 5. 📦 Estrategia de Commits (Conventional Commits + push)

Commits incrementales, cada uno validado antes de crearse:

1. `fix(admin): inject UuidGenerator into CreateAdminUseCase to prevent TypeError on admin creation`
2. `fix(tenant): inject UuidGenerator into tenant, owner and tenant-user use cases`
3. `fix(tenancy): move tenant Inertia share into HandleInertiaRequests and remove dead HandleInertiaTenancy middleware`
4. `test(admin,tenant): add unit coverage for uuid generator injection in creation use cases`

Tras **cada** commit: `git push origin moduleProduct` (`reglas.md` §4.3).

---

## 6. ⚠️ Riesgos y Consideraciones

| Riesgo | Nivel | Mitigación |
| :--- | :---: | :--- |
| Romper la raíz Blade al reactivar el código muerto | **Alto si no se atiende** | Se descarta explícitamente `setRootView('tenant-app')`; la vista no existe. Ya contemplado en B.3. |
| El nuevo prop compartido `tenant` rompe el tipado del frontend | Bajo | Se extiende la interfaz de props compartidas y se valida con `npm run types`. |
| `tenancy()` / `tenant()` no disponibles en contexto central dentro de `share()` | Bajo | El acceso está guardado por `tenancy()->initialized`, que devuelve `false` en dominio central. |
| Cambio de firma en constructores rompe instanciaciones manuales con `new` | Bajo | Se verificó que los 5 Casos de Uso se resuelven solo por autowiring; la única instanciación con `new` está comentada (`CreateAdminPOSTController.php:51`). Se eliminará ese comentario obsoleto. |
| Reordenar la validación de slug en `CreateTenantUseCase` altera el comportamiento | Muy bajo | El resultado observable es idéntico (misma excepción, mismo código 400); solo cambia el momento de generar el UUID. Cubierto por test. |

---

## 7. 📊 Fuera de Alcance (planes futuros)

Detectado durante el diagnóstico, **no** se aborda aquí para mantener el commit acotado:

- Duplicación de Value Objects (`Uuid`, `UserEmail`, `UserName`, `AvatarUrl`) entre `Admin`, `Tenant`, `Product` y `Authentication` en lugar de consumirlos desde `Shared`.
- Binding redundante de `UuidGenerator` / `PasswordHasher` / `PasswordValidator` repetido en 5 providers → centralizar en un único `SharedServiceProvider`.
- Lógica de generación/validación de contraseñas dentro de `CreateAdminPOSTController` (viola `reglas.md` §2.2, controladores delgados).
- Entidades `AuthUser` duplicadas en `Admin`, `Authentication`, `Product` y `Tenant` con firmas divergentes.
- Uso directo de `env()` fuera de archivos de configuración (`config:cache` lo devuelve `null` en producción).
- `ANALISIS_PROYECTO.md` desactualizado: describe 6 módulos cuando `src/` ya contiene 23.

---

## 8. ☑️ Checklist de Ejecución

- [x] A.1 `CreateAdminUseCase` — inyectar y pasar generador
- [x] A.2 `CreateTenantUseCase` — inyectar, pasar generador y reordenar validación de slug
- [x] A.3 `CreateTenantOwnerUseCase` — inyectar, pasar generador y eliminar args sobrantes
- [x] A.4 `CreateTenantUserUseCase` — inyectar y pasar generador
- [x] A.5 `AssignTenantToUserUseCase` — añadir `generator:` a los args nombrados
- [x] B.1 `HandleInertiaRequests::share()` — añadir `tenant` y `current_domain`
- [x] B.2 `bootstrap/app.php` — desregistrar `HandleInertiaTenancy`
- [x] B.3 Eliminar `app/Http/Middleware/HandleInertiaTenancy.php`
- [x] Tipado de props compartidas en `resources/js/types/`
- [x] 5 tests unitarios nuevos (Admin + Tenant)
- [x] `vendor/bin/pint --dirty` sin cambios pendientes
- [x] `php artisan test` → 0 fallos (398 tests pasando al 100%)
- [x] `npm run types` → 0 errores
- [x] Commits + `git push origin moduleProduct`
- [x] Mover este plan a `planes/implementados/`
