# 📋 Plan de Refactor: Shared Kernel, Providers, Controlador de Admin, `AuthUser`, `env()` y Documentación

> **Estado:** Pendiente de aprobación
> **Origen:** Sección "Fuera de Alcance" de `planes/implementados/PLAN_CORRECCION_UUIDGENERATOR_Y_MIDDLEWARE_TENANCY.md`
> **Audiencia de este documento:** una IA ejecutora sin contexto previo del proyecto. Todo lo que necesita saber está aquí; no debe asumir nada que no esté escrito explícitamente.
> **Regla no negociable (reglas.md §3):** ninguna fase se ejecuta sin planificación aprobada. Este documento ES la planificación. Aun así, la Fase 4.6 requiere una **decisión de negocio adicional** que debes exponer al usuario antes de tocar código — está marcada en rojo más abajo.
> **Regla no negociable (reglas.md §4):** prohibido commitear con tests fallando o con errores de tipado. Cada fase indica su propio bloque de validación y su propio commit. No agrupes fases en un solo commit.

---

## 0. Cómo usar este documento

Este plan cubre **6 frentes independientes** detectados en una auditoría previa. Están ordenados de **menor a mayor riesgo/dependencia**. Ejecuta las fases en el orden dado:

| Fase | Frente | Riesgo | Depende de |
| :-: | :--- | :--- | :--- |
| 1 | Centralizar bindings redundantes en `SharedServiceProvider` | Bajo | Ninguna |
| 2 | Extraer generación/validación de contraseñas de `CreateAdminPOSTController` | Bajo | Ninguna |
| 3 | Reemplazar `env()` fuera de `config/*.php` | Bajo (mecánico, muchos archivos pequeños) | Ninguna |
| 4 | Consolidar Value Objects duplicados en `Shared` | Medio (superficie grande) | Ninguna, pero bloquea la Fase 5 |
| 5 | Unificar entidad `AuthUser` duplicada | Medio-Alto | Fase 4 (parcialmente — ver 5.0) |
| 6 | Actualizar `ANALISIS_PROYECTO.md` | Ninguno | Ninguna (hazla al final, así documenta el estado post-refactor) |

Cada fase es **un commit independiente** (o varios, si la propia fase lo indica), con su propia validación completa antes de commitear, siguiendo `reglas.md §4`. No pases a la fase siguiente si la anterior no pasó validación.

Rama de trabajo sugerida: continuar en la rama activa del repo (verifica con `git branch --show-current`; en la última auditoría era `moduleProduct`). Push tras cada commit (`reglas.md §4.3`).

---

## FASE 1 — Centralizar bindings de `UuidGenerator`, `PasswordHasher`, `PasswordValidator`

### 1.1 Diagnóstico

Los contratos `Src\Shared\Domain\Contracts\UuidGenerator`, `PasswordHasher` y `PasswordValidator` se bindean de forma **textualmente idéntica** en 4 providers, más un binding parcial en un 5º:

| Provider | Bindings redundantes (líneas a eliminar) | Bindings específicos del módulo (NO TOCAR) |
| :--- | :--- | :--- |
| `app/Providers/AdminServiceProvider.php` | `UuidGenerator`, `PasswordHasher`, `PasswordValidator` + bloque comentado de código muerto (líneas ~24-30) | `AdminRepositoryInterface→AdminRepository`, `AvatarStorageInterface→LaravelAvatarStorageService`, `SecurityPinMailerInterface→LaravelSecurityPinMailerService` |
| `app/Providers/AppServiceProvider.php` | `UuidGenerator`, `PasswordHasher`, `PasswordValidator` | `UserServices→UserApiClient`, `AuthServices→AuthApiClient`; en `boot()`: `Sanctum::usePersonalAccessTokenModel`, lógica de `forceRootUrl` multi-tenant |
| `app/Providers/AuthServiceProvider.php` | `UuidGenerator`, `PasswordHasher`, `PasswordValidator` | `LoginWebRepositoryInterface`, `UserRepositoryInterface`, `AuthUserRepositoryInterface`, `PersonalAccessTokenRepositoryInterface` (con sus repos Eloquent) |
| `app/Providers/TenantServiceProvider.php` | `UuidGenerator`, `PasswordHasher`, `PasswordValidator` | `TenantRepositoryInterface`, `TenantOwnerRepositoryInterface`, `TenantUserRepositoryInterface` |
| `app/Providers/UserServiceProvider.php` | `UuidGenerator` (único binding que tiene) | Ninguno — el archivo queda vacío tras la limpieza |

Confirmado: **una sola implementación concreta por contrato en todo el proyecto** (`LaravelUuidGenerator`, `LaravelPasswordHasher`, `StrictPasswordValidator`, todas en `src/Shared/Infrastructure/Security/`). No hay ambigüedad — es seguro centralizar.

No existe hoy ningún `SharedServiceProvider`. Hay que crearlo.

**Nota de investigación abierta (no forma parte de esta fase, no la resuelvas aquí):** `app/Providers/TenancyServiceProvider.php` existe en el filesystem pero **no** aparece listado en `bootstrap/providers.php`. Es una inconsistencia preexistente ajena a este refactor. No la toques; si te llama la atención, repórtalo al usuario al terminar, no lo arregles por iniciativa propia.

### 1.2 Cambios

**1.2.1 — Crear `app/Providers/SharedServiceProvider.php`:**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Infrastructure\Security\LaravelPasswordHasher;
use Src\Shared\Infrastructure\Security\LaravelUuidGenerator;
use Src\Shared\Infrastructure\Security\StrictPasswordValidator;

/**
 * Centraliza los bindings de infraestructura compartida (Shared Kernel)
 * usados por múltiples módulos. Antes de este provider, estos 3 bindings
 * estaban duplicados textualmente en AdminServiceProvider, AppServiceProvider,
 * AuthServiceProvider, TenantServiceProvider y UserServiceProvider.
 */
class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UuidGenerator::class, LaravelUuidGenerator::class);
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
        $this->app->bind(PasswordValidator::class, StrictPasswordValidator::class);
    }

    public function boot(): void
    {
        //
    }
}
```

**1.2.2 — `bootstrap/providers.php`**: añadir `App\Providers\SharedServiceProvider::class` como **primera entrada** del array (antes que `AdminServiceProvider`), para dejar explícito que es infraestructura base de la que dependen los demás.

**1.2.3 — `app/Providers/AdminServiceProvider.php`**: eliminar el bloque comentado (código muerto de un binding alternativo de `PasswordHasher`) y las 3 líneas de binds redundantes. Quitar los imports que queden sin uso (`Hasher`, `PasswordHasher`, `PasswordValidator`, `UuidGenerator`, `LaravelPasswordHasher`, `LaravelUuidGenerator`, `StrictPasswordValidator`). Conservar intactos los 3 bindings específicos de Admin.

**1.2.4 — `app/Providers/AppServiceProvider.php`**: eliminar las 3 líneas de binds redundantes y sus imports. Conservar `UserServices→UserApiClient`, `AuthServices→AuthApiClient` y todo el `boot()`.

**1.2.5 — `app/Providers/AuthServiceProvider.php`**: eliminar las 3 líneas de binds redundantes y sus imports. Conservar los 4 bindings de repositorios.

**1.2.6 — `app/Providers/TenantServiceProvider.php`**: eliminar las 3 líneas de binds redundantes y sus imports. Conservar los 3 bindings de repositorios de Tenant.

**1.2.7 — `app/Providers/UserServiceProvider.php`**: este archivo, tras quitar su único binding, queda con `register()` y `boot()` completamente vacíos y sin aportar nada. **Elimínalo por completo** y quita su entrada `App\Providers\UserServiceProvider::class` de `bootstrap/providers.php`.

### 1.3 Validación

```bash
composer dump-autoload
php artisan config:clear
php artisan test
npm run types
```

Verificación funcional manual (o test dirigido si existe): cualquier flujo que cree un Admin/Tenant (usa `UuidGenerator`) o haga login (usa `PasswordHasher`/`PasswordValidator`) debe seguir funcionando exactamente igual, porque la implementación concreta bindeada no cambió, solo el lugar donde se registra.

### 1.4 Commit

```
refactor(providers): centralize UuidGenerator, PasswordHasher and PasswordValidator bindings into SharedServiceProvider
```

---

## FASE 2 — Extraer generación/validación de contraseñas de `CreateAdminPOSTController`

### 2.1 Diagnóstico

`src/Admin/Infrastructure/Http/Controller/CreateAdminPOSTController.php` viola la regla de controladores delgados (`reglas.md §2.2`). Contiene:

- Propiedad privada `$rules` con 4 regex (mayúscula, minúscula, número, carácter especial).
- Método privado `generarContrasena(int $length = 12): string` (genera password aleatoria cumpliendo las reglas, con recursión si falla la validación).
- Método público `validarContrasena(string $password): bool` (valida contra `$rules`).
- Método público `generarMultiplesContrasenas(int $count = 5, int $length = 12): array` — **código muerto**, no se invoca desde `index()` ni desde ningún otro sitio del controlador.
- Uso directo de `env('APP_ENV')` y `env('USER_PASSWORD_DEV')` (esto se resuelve en la Fase 3, no aquí — no toques esas líneas todavía en esta fase, solo la lógica de generación/validación).

**Hallazgo clave:** las 4 regex de `validarContrasena` son **idénticas letra por letra** a las de `src/Shared/Infrastructure/Security/StrictPasswordValidator.php` (que ya implementa `Src\Shared\Domain\Contracts\PasswordValidator` y ya está bindeado e inyectado dentro de `CreateAdminUseCase` vía el VO `Password::fromPlainText()`). Es decir: **la validación del controlador es 100% redundante** — `CreateAdminUseCase` ya rechaza contraseñas inválidas (lanza `InvalidArgumentException` desde dentro del dominio) antes de que el controlador necesite validar nada. `validarContrasena` puede eliminarse sin reemplazo.

No existe ningún contrato `PasswordGenerator` en el proyecto — hay que crearlo, siguiendo el mismo patrón que `PasswordHasher`/`PasswordValidator`.

**Antes de borrar nada:** ejecuta `grep -rn "validarContrasena\|generarMultiplesContrasenas" tests/` — si algún test llama a estos métodos públicos del controlador, avisa al usuario antes de proceder (no debería haber ninguno, pero verifica).

### 2.2 Cambios

**2.2.1 — Crear el contrato `src/Shared/Domain/Contracts/PasswordGenerator.php`:**

```php
<?php

namespace Src\Shared\Domain\Contracts;

interface PasswordGenerator
{
    public function generate(int $length = 12): string;
}
```

**2.2.2 — Crear la implementación `src/Shared/Infrastructure/Security/RandomPasswordGenerator.php`**, reutilizando `PasswordValidator` inyectado (evita duplicar las 4 regex):

```php
<?php

namespace Src\Shared\Infrastructure\Security;

use Src\Shared\Domain\Contracts\PasswordGenerator;
use Src\Shared\Domain\Contracts\PasswordValidator;

class RandomPasswordGenerator implements PasswordGenerator
{
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const NUMBERS = '0123456789';
    private const SPECIAL_CHARS = '!@#$%^&*()-_=+{};:,<.>';

    public function __construct(
        protected PasswordValidator $validator
    ) {}

    public function generate(int $length = 12): string
    {
        if ($length < 8 || $length > 72) {
            throw new \InvalidArgumentException('La longitud debe estar entre 8 y 72 caracteres.');
        }

        $allChars = self::UPPERCASE.self::LOWERCASE.self::NUMBERS.self::SPECIAL_CHARS;
        $allCharsLength = strlen($allChars);

        $password = '';
        $password .= self::UPPERCASE[random_int(0, strlen(self::UPPERCASE) - 1)];
        $password .= self::LOWERCASE[random_int(0, strlen(self::LOWERCASE) - 1)];
        $password .= self::NUMBERS[random_int(0, strlen(self::NUMBERS) - 1)];
        $password .= self::SPECIAL_CHARS[random_int(0, strlen(self::SPECIAL_CHARS) - 1)];

        for ($i = 0; $i < $length - 4; $i++) {
            $password .= $allChars[random_int(0, $allCharsLength - 1)];
        }

        $password = str_shuffle($password);

        try {
            $this->validator->validate($password);
        } catch (\InvalidArgumentException) {
            return $this->generate($length);
        }

        return $password;
    }
}
```

> Nota: `$this->validator->validate()` lanza excepción en vez de devolver `bool` (esa es la firma real del contrato `PasswordValidator`, confirmado en `src/Shared/Domain/Contracts/PasswordValidator.php`). El `try/catch` reemplaza el `if (!$this->validarContrasena(...))` original.

**2.2.3 — `app/Providers/AdminServiceProvider.php`**: añadir el binding (respetando lo que dejó la Fase 1 — este archivo a esta altura solo debe tener sus bindings específicos):

```php
use Src\Shared\Domain\Contracts\PasswordGenerator;
use Src\Shared\Infrastructure\Security\RandomPasswordGenerator;

// dentro de register():
$this->app->bind(PasswordGenerator::class, RandomPasswordGenerator::class);
```

**2.2.4 — `src/Admin/Infrastructure/Http/Controller/CreateAdminPOSTController.php`**: inyectar `PasswordGenerator` en el constructor y usarlo en vez del método privado. Eliminar `$rules`, `generarContrasena`, `validarContrasena`, `generarMultiplesContrasenas` por completo.

```php
public function __construct(
    protected CreateAdminUseCase $create_admin_use_case,
    protected PasswordGenerator $password_generator
) {}

// dentro de index(), reemplazar la rama else:
} else {
    $password = $this->password_generator->generate(12);
}
```

(La rama `if (env('APP_ENV') == 'local') { ... }` se deja intacta en esta fase — se corrige en la Fase 3.)

### 2.3 Validación

```bash
vendor/bin/pint --dirty
php artisan test
npm run types
```

Prueba unitaria nueva sugerida: `tests/Unit/Shared/RandomPasswordGeneratorTest.php` — generar 50 contraseñas con longitudes distintas (8, 12, 30, 72) y verificar con `RoleTenantUser`... no, verificar con una instancia real de `StrictPasswordValidator` que cada una pasa `validate()` sin excepción, y que `strlen($password) === $length`.

### 2.4 Commit

```
refactor(admin): extract password generation into shared PasswordGenerator, remove duplicated validation from CreateAdminPOSTController
```

---

## FASE 3 — Reemplazar `env()` fuera de `config/*.php`

### 3.1 Diagnóstico

21 ocurrencias de `env()` fuera de archivos de configuración, en `src/` y `database/seeders/` (cero en `app/` y `routes/`). Bajo `config:cache` en producción, todas devuelven `null`, rompiendo la lógica que dependa de ellas.

| Variable | Ocurrencias | Bridge en config existente | Acción |
| :--- | :-: | :--- | :--- |
| `APP_ENV` | 14 | Sí, `config('app.env')` (`config/app.php`) | Reemplazo directo 1:1 |
| `USER_PASSWORD_DEV` | 3 (1 controlador + 2 seeders) | No existe | Crear key en config + decisión de seguridad (ver 3.2.3) |
| `APP_CENTRAL_DOMAIN` | 2 (ambas en `TenantRepository.php`) | No existe, y además desacoplada de `config('tenancy.central_domains')` | Crear key en config |
| `DEFAULT_USER_TENANT_OWNER_PASSWORD_DEV` | 1 (seeder) | Sí, `config('app.default_passwords_tenant_owner')` ya existe en `config/app.php` | Reemplazo directo 1:1 |

### 3.2 Cambios

**3.2.1 — Las 14 ocurrencias de `APP_ENV` → `config('app.env')`** (o `App::environment('local')`, preferible por legibilidad cuando la comparación es contra `'local'`, que es el caso en las 14). Archivos y líneas exactas detectadas en la auditoría (vuelve a correr el grep antes de editar, por si el código cambió desde la auditoría):

```bash
grep -rn "env('APP_ENV')" src/ --include="*.php"
```

Archivos afectados (2 ocurrencias cada uno salvo el primero, que tiene 1 relevante a esta fase):
- `src/Admin/Infrastructure/Http/Controller/CreateAdminPOSTController.php` (línea ~43)
- `src/Admin/Infrastructure/Services/AuthApiClient.php` (líneas ~21, ~30)
- `src/Authentication/Infrastructure/Services/TenantApiCentralClient.php` (líneas ~19, ~29)
- `src/Authentication/Infrastructure/Services/UserApiCentralClient.php` (líneas ~22, ~32)
- `src/Authentication/Infrastructure/Services/UserApiTenantClient.php` (líneas ~22, ~32)
- `src/Product/Infrastructure/Http/Services/AuthTenantApiClient.php` (líneas ~22, ~31)
- `src/Tenant/Infrastructure/Http/Services/AuthCentralApiClient.php` (líneas ~22, ~31)
- `src/Tenant/Infrastructure/Http/Services/AuthTenantApiClient.php` (líneas ~22, ~31)

Patrón de reemplazo (verifica el patrón exacto en cada archivo antes de aplicar, puede haber pequeñas variaciones de espaciado):

```php
// antes
if (env('APP_ENV') == 'local') { ... }

// después
if (app()->environment('local')) { ... }
```

> Usa `app()->environment('local')` (helper de Laravel, no facade `App::`, para no añadir un `use` nuevo si el archivo no la tiene ya importada) en los servicios de `Infrastructure/`. En el controlador (2.2.4 ya lo dejó con esta rama intacta), aplica el mismo cambio ahí también.

**3.2.2 — Las 2 ocurrencias de `APP_CENTRAL_DOMAIN` en `src/Tenant/Infrastructure/Eloquent/Repositories/TenantRepository.php` (líneas ~141 y ~309):**

Primero añade la key a `config/app.php` (busca el bloque donde ya vive `default_passwords_tenant_owner` y agrega junto a él):

```php
'central_domain' => env('APP_CENTRAL_DOMAIN'),
```

Luego en `TenantRepository.php`:

```php
// antes
Slug::make($slug, env('APP_CENTRAL_DOMAIN'))

// después
Slug::make($slug, config('app.central_domain'))
```

**Nota de investigación abierta, no la resuelvas en esta fase:** `config('tenancy.central_domains')` ya existe como array hardcodeado (`'owomarket.local'`, `'127.0.0.1'`, `'localhost'`) y **no** está conectado con `APP_CENTRAL_DOMAIN`. Son dos fuentes de verdad distintas sobre "cuál es el dominio central". Esta fase solo migra el `env()` a `config()` sin resolver esa inconsistencia de fondo — repórtala al usuario al terminar como hallazgo adicional, no la arregles por iniciativa propia (podría tener implicancias en el `InitializeTenancyByDomain` de `stancl/tenancy`).

**3.2.3 — `USER_PASSWORD_DEV` (controlador + 2 seeders) — requiere decisión, no es un simple mover:**

Esta variable define una contraseña de desarrollo en texto plano (`Test_12345678` en `.env` actual), con fallback hardcodeado `'12345678'` en los seeders. Tener esta lógica en un controlador HTTP de producción es un riesgo de seguridad, no solo un problema de `env()`.

Acción recomendada (aplícala tal cual salvo que el usuario indique lo contrario al revisar este plan):

1. **Eliminar la rama de `CreateAdminPOSTController.php` por completo.** El controlador siempre debe generar la contraseña con `PasswordGenerator` (ya lo hace tras la Fase 2), sin importar el entorno. Si en desarrollo local se necesita una contraseña conocida, eso es responsabilidad de un **seeder**, no de un controlador HTTP que corre igual en local y en producción.

   ```php
   // CreateAdminPOSTController::index() — queda simplemente:
   $password = $this->password_generator->generate(12);
   ```

   Elimina también la variable `USER_PASSWORD_DEV` de `.env` y `.env.example` si tras esto ya no se usa en ningún otro sitio (verifica con `grep -rn "USER_PASSWORD_DEV" .env .env.example database/`).

2. **En los 2 seeders** (`database/seeders/RootUserSeeder.php` línea ~23, `database/seeders/TenantDefaultUsersSeeder.php` línea ~25): mover a `config()`, pero sin fallback débil hardcodeado — que falle explícitamente si falta la variable en local, en vez de crear silenciosamente una cuenta con `12345678`:

   ```php
   // en config/app.php, agregar:
   'dev_user_password' => env('USER_PASSWORD_DEV'),
   ```

   ```php
   // en cada seeder, antes:
   $password = env('USER_PASSWORD_DEV', '12345678');

   // después:
   $password = config('app.dev_user_password')
       ?? throw new \RuntimeException('USER_PASSWORD_DEV no está definida en .env; requerida para seeders de desarrollo.');
   ```

**3.2.4 — `DEFAULT_USER_TENANT_OWNER_PASSWORD_DEV` en `database/seeders/TenantDomainSeeder.php` (línea ~48):** el bridge ya existe (`config('app.default_passwords_tenant_owner')`), solo cambia el call site:

```php
// antes
env('DEFAULT_USER_TENANT_OWNER_PASSWORD_DEV', 'EndAdmin_12345678')

// después
config('app.default_passwords_tenant_owner')
```

(El default `'EndAdmin_12345678'` ya vive dentro de la definición de `config/app.php`, no hace falta repetirlo en el seeder.)

### 3.3 Validación

```bash
php artisan config:clear
php artisan config:cache && php artisan config:clear   # fuerza a probar que nada dependía del env() directo bajo caché
php artisan test
npm run types
```

Prueba manual dirigida: ejecutar `php artisan config:cache` y luego correr el seeder de admin/tenant en local — si `USER_PASSWORD_DEV` no está en `.env`, debe lanzar el `RuntimeException` explícito (comportamiento esperado, no un bug).

### 3.4 Commit

Dos commits separados (el segundo es más sensible por tocar seguridad):

```
refactor(config): replace direct env() calls with config() across services and repositories
```
```
fix(security): remove USER_PASSWORD_DEV fallback from admin controller and require explicit config in dev seeders
```

---

## FASE 4 — Consolidar Value Objects duplicados en `Shared`

### 4.0 Diagnóstico general

Se auditaron todos los Value Objects duplicados en `src/*/Domain/ValueObjects/` entre los módulos `Admin`, `Authentication`, `Product`, `Tenant`, `User` (y `Shared` como destino). Resultado, clasificado por riesgo de fusión:

**Grupo A — 100% idénticos entre todos los módulos que los tienen (fusión directa, sin decisiones):**

| VO | Módulos que lo tienen | ¿Existe ya en Shared? |
| :--- | :--- | :--- |
| `Uuid` | Admin, Authentication, Product, Tenant, User | **Sí** (ya existe, es el destino) |
| `UserEmail` | Admin, Authentication, Product, Tenant, User | No, crear |
| `UserStatus` | Admin, Authentication, Tenant, User | No, crear |
| `PhoneNumber` | Admin, Tenant, User | No, crear |

**Grupo B — casi idénticos, difieren SOLO en si la excepción de validación lleva o no un código de error (`400`/`500`) como segundo argumento (requiere estandarizar antes de fusionar, pero es una decisión de bajo impacto):**

| VO | Módulos que lo tienen | Detalle de la divergencia |
| :--- | :--- | :--- |
| `UserName` | Admin, Authentication, Product, Tenant, User | Admin/Authentication: sin código. Product/Tenant/User: con código `400`. |
| `AvatarUrl` | Admin, Authentication, Product, Tenant, User | Admin/Authentication/Product/Tenant: sin código. User: con código `400`. |
| `Password` | Admin, Authentication, Tenant, User | Admin/Authentication/Tenant: `'Hash inválido'` sin código. User: con código `500`. |
| `PinVerification` | Admin, Tenant, User | Admin/Tenant: sin código. User: con código `400` en 3 excepciones. |
| `EmailVerifiedAt` | Admin, Tenant | Admin: sin código. Tenant: con código `400`. (User no tiene este VO) |

**Decisión de estandarización para el Grupo B (aplícala tal cual, es de bajo impacto ya que ningún `catch` en el proyecto fue encontrado comprobando `$e->getCode()` sobre estas excepciones — verifícalo tú también antes de aplicar, con `grep -rn "getCode()" src/` acotado a los módulos afectados):** la versión canónica en `Shared` **incluye el código `400`** en todas las excepciones de validación (consistente con el resto de VOs del proyecto que ya usan `400` para errores de entrada de usuario). Si tu verificación de `getCode()` encuentra algún `catch` que dependa del código ausente, detente y repórtalo al usuario antes de continuar con ese VO específico.

**Grupo C — divergencia de reglas de negocio real, requiere decisión explícita antes de tocar nada (ver 4.6):**

| VO | Módulos que lo tienen | Por qué no es trivial |
| :--- | :--- | :--- |
| `UserType` | Admin, Authentication, Product, Tenant, User | Admin usa constante `TENANT_STAFF` + método `isEmployee()`/`tenantEmployee()` con jerarquía `SUPER_ADMIN=3, TENANT_OWNER=2, TENANT_STAFF=1`. Los otros 4 módulos usan `OWNER`+`STAFF` (dos constantes) con jerarquía `SUPER_ADMIN=4, TENANT_OWNER=3, OWNER=2, STAFF=1`. Son modelos de roles distintos, no solo un rename. |

**Grupo D — NO fusionar bajo ningún concepto (falsos positivos de nombre, o fusión insegura):**

| VO | Módulos que comparten el nombre | Por qué NO se fusiona |
| :--- | :--- | :--- |
| `Slug` | Product, Tenant | Conceptos distintos: slug simple de producto (`make(string $value)`, 1 arg) vs subdominio de tenant con validación DNS (`make(string $value, string $domain)`, 2 args, palabras reservadas, longitud DNS). Firmas incompatibles. |
| `PaymentStatus` | Order, Payment | `Order\PaymentStatus` es un `enum` nativo con estados `PENDING/PAID/FAILED/REFUNDED`. `Payment\PaymentStatus` es una clase con estados `PENDING/COMPLETED/FAILED/REFUNDED/CANCELLED`. APIs y catálogos de estado incompatibles. |
| `Currency` | Order, y ya existe en `Shared` | `Order\Currency` tiene constructor **público** `new Currency(?string $code = 'USD')` sin whitelist. `Shared\Currency` tiene constructor **privado** + `make()`, whitelist cerrada de ~20 monedas con metadatos. Fusionar rompería cualquier `new Currency(...)` en Order y podría rechazar códigos hoy válidos ahí. |

No toques nada del Grupo D en este plan. Si en el futuro se quiere unificar, es un plan aparte con análisis de todos los call sites de `Order`.

### 4.1 Procedimiento mecánico de migración (aplícalo VO por VO, del Grupo A primero, luego Grupo B)

Para cada Value Object `{VO}` que vayas a fusionar, sigue este procedimiento exacto, **uno a la vez**, con commit y validación entre cada uno (no batchees varios VOs en un commit — si algo rompe, necesitas poder aislar cuál fue):

**Paso 1 — Crear (o confirmar) la versión canónica en Shared.**
Si no existe en `src/Shared/Domain/ValueObjects/{VO}.php`, cópiala de cualquiera de los módulos que la tengan (son idénticas en el Grupo A; en el Grupo B, aplica la estandarización del código `400` decidida en 4.0), cambiando el namespace a `Src\Shared\Domain\ValueObjects`.

**Paso 2 — Verificar que ningún módulo tiene una referencia por nombre completamente calificado sin `use`.**
```bash
grep -rn "Src\\\\Admin\\\\Domain\\\\ValueObjects\\\\{VO}" src/ app/ tests/ --include="*.php"
grep -rn "Src\\\\Authentication\\\\Domain\\\\ValueObjects\\\\{VO}" src/ app/ tests/ --include="*.php"
grep -rn "Src\\\\Product\\\\Domain\\\\ValueObjects\\\\{VO}" src/ app/ tests/ --include="*.php"
grep -rn "Src\\\\Tenant\\\\Domain\\\\ValueObjects\\\\{VO}" src/ app/ tests/ --include="*.php"
grep -rn "Src\\\\User\\\\Domain\\\\ValueObjects\\\\{VO}" src/ app/ tests/ --include="*.php"
```
Guarda esta lista — es exactamente el conjunto de archivos que hay que tocar en el paso 3. Si aparecen usos con backslash simple sin escapar en el propio código fuente (`Src\Admin\...` tal cual, no como string), ajusta el patrón de búsqueda según corresponda al shell que uses.

**Paso 3 — Reemplazar el import módulo por módulo.**
Para cada módulo de la lista del paso 2:
```bash
grep -rl "use Src\\\\{Modulo}\\\\Domain\\\\ValueObjects\\\\{VO};" src/{Modulo} tests/ | \
  xargs sed -i "s/use Src\\\\{Modulo}\\\\Domain\\\\ValueObjects\\\\{VO};/use Src\\\\Shared\\\\Domain\\\\ValueObjects\\\\{VO};/"
```
Si algún archivo usa el nombre completamente calificado sin `use` (detectado en el paso 2), edítalo a mano con `Edit`, no con `sed`, para evitar reemplazos parciales incorrectos.

**Paso 4 — Verificación de que no queda ninguna referencia a la clase del módulo.**
Repite exactamente los 5 comandos grep del paso 2. Todos deben devolver **cero resultados** antes de continuar. Si alguno devuelve algo, no borres el archivo del módulo todavía — revisa qué quedó sin actualizar.

**Paso 5 — Borrar el archivo duplicado del módulo.**
Solo cuando el paso 4 dio cero resultados en todos los módulos relevantes:
```bash
rm src/{Modulo}/Domain/ValueObjects/{VO}.php
```
(Si el borrado falla con "Operation not permitted" por vivir en una carpeta de usuario protegida, usa la herramienta de permiso de borrado disponible en tu entorno antes de reintentar — no reportes el borrado como imposible sin haberlo intentado.)

**Paso 6 — Validar y commitear ese VO específico.**
```bash
vendor/bin/pint --dirty
php artisan test
npm run types
```
Commit atómico por VO:
```
refactor({modulo-o-shared}): consolidate {VO} value object into Shared Kernel, remove duplicates from Admin/Authentication/Product/Tenant/User
```

### 4.2 Orden recomendado de ejecución dentro de la Fase 4

1. `Uuid` (ya existe en Shared, es el más simple — hazlo primero para validar que el procedimiento mecánico funciona en este repo antes de aplicarlo a los demás).
2. `UserEmail`
3. `UserStatus`
4. `PhoneNumber`
5. `UserName` (aplicar estandarización de código `400` del Grupo B)
6. `AvatarUrl` (ídem)
7. `Password` (ídem)
8. `PinVerification` (ídem)
9. `EmailVerifiedAt` (ídem)
10. `UserType` — **ver 4.6, no lo hagas sin la decisión explícita**

### 4.3 Casos especiales a vigilar durante la migración

- **`AvatarUrl`** extiende `Src\Shared\Domain\ValueObjects\StringValueObject` en todos los módulos — esa clase base YA vive en Shared, no hay que tocarla, solo la clase concreta `AvatarUrl`.
- **`Password`** tiene un bloque de código **comentado** (configuración de hashing antigua, ~20 líneas) en la versión de `Authentication` que no existe igual en las demás — es dead code sin efecto, cópialo o descártalo, no afecta el comportamiento; documenta en el commit cuál decidiste.
- Antes de borrar cada VO del módulo `Admin` en particular, ten en cuenta que la Fase 2 y Fase 5 (más abajo) también tocan archivos de `Admin` — si ejecutas las fases en el orden de este documento (1→2→3→4→5), no debería haber conflicto, pero si cambias el orden, revisa que no estés editando el mismo archivo en dos fases a la vez sin haber commiteado la primera.

### 4.4 Validación global al cerrar la Fase 4 (Grupos A y B completos)

Además de la validación por VO del paso 6, al terminar TODOS los VOs de los Grupos A y B:
```bash
find src/*/Domain/ValueObjects -name "Uuid.php" -o -name "UserEmail.php" -o -name "UserStatus.php" \
  -o -name "PhoneNumber.php" -o -name "UserName.php" -o -name "AvatarUrl.php" -o -name "Password.php" \
  -o -name "PinVerification.php" -o -name "EmailVerifiedAt.php" | grep -v "src/Shared/"
```
Este comando debe devolver **cero resultados** (ningún módulo debe conservar copias propias de estos 9 VOs). Si algo aparece, esa fusión quedó incompleta.

### 4.5 NO migres en esta fase (recordatorio)

`Slug` (Product/Tenant), `PaymentStatus` (Order/Payment), `Currency` (Order/Shared) — Grupo D, ver 4.0. Tampoco toques `UserType` sin pasar por 4.6.

### 4.6 🔴 DECISIÓN DE NEGOCIO REQUERIDA — `UserType`

**No implementes esta sub-fase sin aprobación explícita del usuario.** A diferencia de todo lo demás en este plan, aquí hay una divergencia real de reglas de negocio (jerarquía de privilegios y catálogo de roles distintos entre Admin y el resto), y una fusión mecánica podría cambiar comportamiento de autorización en producción.

Preséntale al usuario estas opciones (usa la herramienta de pregunta al usuario si tu entorno la tiene disponible; si no, detente y pide confirmación por texto antes de tocar código):

- **Opción A (recomendada) — Modelo unificado por superconjunto:** crear `Src\Shared\Domain\ValueObjects\UserType` con las constantes de ambos modelos (`SUPER_ADMIN`, `TENANT_OWNER`, `OWNER`, `STAFF`, `CUSTOMER`, y el equivalente de `TENANT_STAFF` de Admin mapeado a `STAFF` si son conceptualmente el mismo rol — **verifica con el usuario si `TENANT_STAFF` de Admin y `STAFF` de los otros módulos representan el mismo rol de negocio antes de fusionarlos como el mismo valor**), con jerarquía `SUPER_ADMIN=4, TENANT_OWNER=3, OWNER=2, STAFF=1` (el modelo "más rico" de 4 niveles), y mantener los métodos `isEmployee()`/`tenantEmployee()` de Admin como alias de los conceptos equivalentes en el modelo unificado para no romper call sites existentes en Admin.
- **Opción B — No fusionar `UserType`, dejarlo fuera de alcance también:** mover `Uuid`, `UserEmail`, `UserStatus`, `PhoneNumber`, `UserName`, `AvatarUrl`, `Password`, `PinVerification`, `EmailVerifiedAt` a Shared (Fase 4 completa salvo esto), pero dejar `UserType` duplicado por ahora, documentado como deuda técnica conocida y pendiente de una decisión de producto sobre el modelo de roles.

Si el usuario elige Opción A, sigue el mismo procedimiento mecánico de 4.1, pero con un paso adicional antes del Paso 3: revisar CADA call site que use `UserType::TENANT_STAFF`, `->isEmployee()` o `->tenantEmployee()` en el módulo `Admin` (con `grep -rn "TENANT_STAFF\|isEmployee\|tenantEmployee" src/Admin/`) y confirmar manualmente que el mapeo a `STAFF` no cambia ninguna decisión de autorización visible (por ejemplo, un middleware o gate que compare `hasHigherOrEqualPrivilegesThan()` contra un umbral específico). Si encuentras algún punto donde el cambio de jerarquía (`3 niveles` → `4 niveles`) alteraría el resultado de una comparación existente, detente y repórtalo — no lo decidas unilateralmente.

Si el usuario elige Opción B, simplemente omite este VO y sigue con la Fase 5 usando la ruta 5B (ver abajo), no la 5A.

---

## FASE 5 — Unificar entidad `AuthUser` duplicada

### 5.0 Prerrequisito

Esta fase asume que la **Fase 4 (Grupos A y B como mínimo)** ya está hecha, porque `AuthUser` depende de `Uuid`, `UserName`, `UserEmail`, `AvatarUrl` y `UserType`. Si en 4.6 el usuario eligió la **Opción B** (no fusionar `UserType`), ejecuta la **ruta 5B** de esta fase en vez de la 5A — la fusión completa de `AuthUser` no es segura sin un `UserType` unificado, porque las 4 copias delegan `isSuperAdmin()`/`isTenantOwner()`/`isCustomer()` al `UserType` de su propio módulo.

### 5.1 Diagnóstico

`AuthUser` existe como entidad de dominio en 4 módulos: `Admin`, `Authentication`, `Product`, `Tenant`, más un modelo Eloquent (`src/Authentication/Infrastructure/Eloquent/Models/AuthUser.php`) y su repositorio, ambos solo en `Authentication`.

- `Admin`, `Product`, `Tenant`: las 3 son **byte-idénticas** entre sí (112 líneas, solo cambia namespace). Su `create()` (que recibe `Uuid $user_id` ya construido, sin `id` propio) **nunca se invoca** en ninguno de los 3 módulos — es código muerto, solo usan `reconstitute()`.
- `Authentication`: 126 líneas, con propiedad `id` propia extra, `create(UuidGenerator $generator, ...)` que sí genera un id nuevo, y `reconstitute()` con un parámetro `?Uuid $id` adicional al inicio. Es la única que persiste de verdad (tabla `auth_users`, misma estructura en BD central y en cada BD de tenant vía `stancl/tenancy`).

**Arquitectura de fondo (correcta, no se toca):** Admin, Product y Tenant no acceden a la tabla `auth_users` ni comparten conexión de BD — consultan al usuario autenticado vía **HTTP interno real** contra endpoints de `Authentication` (`GET /api/auth/interna/user/{uuid}` en central, `GET /api-tenant/auth/interna/user/{uuid}` en tenant), protegidos con middleware y secreto compartido. Esto es intencional (anti-corruption layer entre contextos), no accidental. Lo que sí es accidental es que el DTO de respuesta (`AuthUser`) esté copiado 3 veces en vez de compartido.

**Discrepancia a resolver como parte de esta fase:** el endpoint `CurrentUserGETController::index()` en `Authentication` devuelve hoy `user_id/user_name/user_email/user_type/user_avatar`, **sin** el campo `id` de fila. Si la entidad unificada exige `id` en su constructor, los 3 módulos consumidores no podrán reconstituirla desde esa respuesta HTTP. La solución (ya contemplada en el diseño de abajo) es que `id` sea **nullable** en `reconstitute()`, y los 3 módulos consumidores pasen `null` explícitamente — no hace falta tocar el endpoint HTTP.

### 5.2 Ruta 5A — Fusión completa (requiere `UserType` unificado de la Fase 4.6-A)

**5.2.1 — Crear `src/Shared/Domain/Entities/AuthUser.php`**, basada en la versión de `Authentication` (la más completa), usando los VOs ya unificados en Shared:

```php
<?php

namespace Src\Shared\Domain\Entities;

use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\AvatarUrl;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\UserName;
use Src\Shared\Domain\ValueObjects\UserType;
use Src\Shared\Domain\ValueObjects\Uuid;

class AuthUser
{
    private ?Uuid $id;
    private Uuid $user_id;
    private UserName $name;
    private UserEmail $email;
    private UserType $type;
    private ?AvatarUrl $avatar;

    private function __construct(
        ?Uuid $id,
        Uuid $user_id,
        UserName $name,
        UserEmail $email,
        UserType $type,
        ?AvatarUrl $avatar,
    ) {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->name = $name;
        $this->email = $email;
        $this->type = $type;
        $this->avatar = $avatar;
    }

    public static function create(
        UuidGenerator $generator,
        Uuid $user_id,
        UserName $name,
        UserEmail $email,
        UserType $type,
        ?AvatarUrl $avatar,
    ): self {
        return new self(Uuid::generate($generator), $user_id, $name, $email, $type, $avatar);
    }

    public static function reconstitute(
        ?Uuid $id,
        Uuid $user_id,
        UserName $name,
        UserEmail $email,
        UserType $type,
        ?AvatarUrl $avatar,
    ): self {
        return new self($id, $user_id, $name, $email, $type, $avatar);
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUserId(): Uuid
    {
        return $this->user_id;
    }

    public function getName(): UserName
    {
        return $this->name;
    }

    public function getEmail(): UserEmail
    {
        return $this->email;
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function getAvatar(): ?AvatarUrl
    {
        return $this->avatar;
    }

    public function isSuperAdmin(): bool
    {
        return $this->type->isSuperAdmin();
    }

    public function isTenantOwner(): bool
    {
        return $this->type->isTenantOwner();
    }

    public function isCustomer(): bool
    {
        return $this->type->isCustomer();
    }
}
```

**5.2.2 — `Authentication`**: reemplazar `use Src\Authentication\Domain\Entities\AuthUser;` por `use Src\Shared\Domain\Entities\AuthUser;` en los 9 archivos consumidores (repositorio, casos de uso, modelo si aplica). Borrar `src/Authentication/Domain/Entities/AuthUser.php`.

**5.2.3 — `Admin`, `Product`, `Tenant`**: en cada uno de los 3 módulos:
- Reemplazar el import de su `AuthUser` local por `use Src\Shared\Domain\Entities\AuthUser;`.
- En cada `ConsultAuthUserApiByUuid(UseCase)` de estos 3 módulos, el call site que hace `AuthUser::reconstitute(...)` con 5 argumentos ahora debe pasar 6, con `id: null` como primero (la respuesta HTTP no trae `id`):
  ```php
  // antes (5 args)
  AuthUser::reconstitute($uuid, $name, $email, $type, $avatar);

  // después (6 args, id explícito en null)
  AuthUser::reconstitute(null, $uuid, $name, $email, $type, $avatar);
  ```
- Borrar `src/{Modulo}/Domain/Entities/AuthUser.php` en los 3 módulos.
- Sigue el mismo procedimiento de verificación de referencias del paso 4.1 (grep de namespace completo) antes de borrar cada archivo.

**5.2.4 — Verificación cruzada:** después de fusionar, `grep -rn "AuthUser::create(" src/Admin/ src/Product/ src/Tenant/` debe seguir devolviendo cero resultados (confirma que el código muerto no resucitó por error de copy-paste).

### 5.3 Ruta 5B — Limpieza mínima sin fusión (si 4.6 resultó en Opción B, o si el usuario prefiere no fusionar `AuthUser` todavía)

No muevas nada a Shared. Limítate a quitar el código muerto de las 3 copias idénticas (`Admin`, `Product`, `Tenant`):

- En cada una de las 3 clases, **elimina el método estático `create()`** (confirmado sin uso real en los 3 módulos) y su import de `UuidGenerator` si lo tuviera. Deja solo `reconstitute()` y los getters, ya que es lo único que estos 3 módulos usan en la práctica — esto documenta honestamente que son DTOs de solo lectura, no entidades con ciclo de vida de creación.
- No toques `Authentication\Domain\Entities\AuthUser` (esa sí crea de verdad).
- Esto reduce la duplicación de *comportamiento innecesario* sin resolver la duplicación de *definición de clase*, que queda pendiente para cuando se resuelva `UserType`.

### 5.4 Validación (ambas rutas)

```bash
php artisan test
npm run types
```

Prueba manual dirigida: cargar el dashboard de Admin, Product (una vista de producto) y Tenant que dependen de `ConsultAuthUserApiByUuid(UseCase)` — deben seguir mostrando nombre/email/avatar del usuario autenticado sin error.

### 5.5 Commit

Ruta 5A:
```
refactor(shared): unify AuthUser entity into Shared Kernel, remove duplicates from Admin, Product, Tenant and Authentication
```

Ruta 5B:
```
chore(cleanup): remove dead create() factory from unused AuthUser copies in Admin, Product and Tenant
```

---

## FASE 6 — Actualizar `ANALISIS_PROYECTO.md`

### 6.1 Diagnóstico

`ANALISIS_PROYECTO.md` (raíz del repo) describe el proyecto con **6 módulos** (`Admin`, `Authentication`, `Marketplace`, `Product`, `Shared`, `Tenant`, `User`, más un `bounded_context_example` de plantilla). El `src/` real, a fecha de esta auditoría, tiene **23 módulos**:

```
Admin, Attribute, Authentication, Billing, Brand, Category, CentralCustomer,
CentralMarketplace, Coupon, Customer, Marketplace, Monetization, Order,
Payment, Product, Review, Shared, Shipment, Shipping, Tax, Tenant,
TenantSettings, User
```

Con tamaño aproximado (nº de archivos `.php`, como proxy de madurez relativa — no es una medición de calidad, solo de volumen):

| Módulo | Archivos PHP | Módulo | Archivos PHP |
| :--- | -: | :--- | -: |
| Tenant | 96 | Shipment | 36 |
| Product | 87 | Category | 33 |
| Billing | 61 | Brand | 33 |
| Admin | 61 | TenantSettings | 31 |
| Authentication | 56 | Tax | 31 |
| Order | 50 | Payment | 25 |
| Customer | 49 | User | 23 |
| Attribute | 42 | Shared | 23 |
| Shipping | 41 | Monetization | 21 |
| Review | 40 | Marketplace | 21 |
| Coupon | 37 | CentralCustomer | 15 |
| | | CentralMarketplace | 6 |

Además, la sección de "Hallazgos Críticos y Bugs Detectados" del documento describe el bug de `UuidGenerator` y el bug del middleware `HandleInertiaTenancy` como pendientes — **ambos ya fueron corregidos** (ver `planes/implementados/PLAN_CORRECCION_UUIDGENERATOR_Y_MIDDLEWARE_TENANCY.md`) y deben marcarse como resueltos, no eliminarse del historial (para que quede constancia de que existieron y se corrigieron).

### 6.2 Cambios

Reescribe `ANALISIS_PROYECTO.md` (no lo borres, edítalo) con estas actualizaciones:

1. **Sección "Desglose de Módulos"**: reemplazar el árbol de 6 módulos por el listado completo de 23, agrupados por dominio funcional para que sea legible (sugerencia de agrupación, ajústala si tienes mejor criterio):
   - *Identidad y acceso:* `Admin`, `Authentication`, `User`, `CentralCustomer`, `Customer`
   - *Tenant y plataforma:* `Tenant`, `TenantSettings`, `CentralMarketplace`, `Marketplace`, `Monetization`, `Billing`
   - *Catálogo:* `Product`, `Category`, `Brand`, `Attribute`, `Review`, `Coupon`
   - *Operación comercial:* `Order`, `Payment`, `Shipment`, `Shipping`, `Tax`
   - *Compartido:* `Shared`
2. **Tabla de "Estado de Módulos"**: la tabla actual solo cubre 6 módulos con estimaciones de casos de uso ya desactualizadas (menciona commits de git recientes con features completas de pasarelas de pago, SSO, comisiones, carrito multitienda — ver `git log --oneline -20` para contexto reciente). Reconstruye esta tabla desde cero para los 23 módulos; para estimar el estado de cada uno, revisa si tiene las 3 capas (`Domain`, `Application`, `Infrastructure`) con `find src/{Modulo} -maxdepth 1 -type d` y cuenta casos de uso con `find src/{Modulo}/Application/UseCase -name "*.php" | wc -l` si la carpeta existe.
3. **Sección "Hallazgos Críticos"**: mover los 2 bugs de `UuidGenerator` y `HandleInertiaTenancy` a una nueva subsección "✅ Resueltos" con referencia al plan que los corrigió, en vez de dejarlos como pendientes.
4. **Sección "Deuda Técnica"**: actualizar según el resultado real de este plan (Fases 1-5) — marcar como resuelto lo que se haya completado, y dejar como pendiente explícito lo que se haya diferido (p. ej. `UserType` si el usuario eligió la Opción B en 4.6, o el Grupo D de VOs que nunca se fusiona).
5. Añadir una línea de fecha de última actualización al inicio del documento (`> Última actualización: {fecha de hoy}`) para que quede claro que ya no es el informe original.

### 6.3 Validación

No aplica suite de tests (es solo documentación). Sí verifica que los números que escribas en la tabla de módulos coincidan con una ejecución real de los comandos `find`/`grep` indicados arriba, no con las cifras de este plan (pueden haber cambiado si pasó tiempo entre esta auditoría y la ejecución).

### 6.4 Commit

```
docs(architecture): update ANALISIS_PROYECTO.md to reflect current 23-module structure and resolved critical bugs
```

---

## 7. Checklist Maestro de Ejecución

- [ ] **Fase 1** — `SharedServiceProvider` creado; 4 providers limpiados de binds redundantes; `UserServiceProvider` eliminado; tests + types OK; commit hecho.
- [ ] **Fase 2** — `PasswordGenerator` (contrato + `RandomPasswordGenerator`) creado; binding en `AdminServiceProvider`; controlador limpio de `$rules`/`generarContrasena`/`validarContrasena`/`generarMultiplesContrasenas`; tests + types OK; commit hecho.
- [ ] **Fase 3** — 14 usos de `APP_ENV` migrados; `APP_CENTRAL_DOMAIN` migrado con nueva key en config; `USER_PASSWORD_DEV` eliminado del controlador y endurecido en 2 seeders; `DEFAULT_USER_TENANT_OWNER_PASSWORD_DEV` migrado en 1 seeder; tests + types OK; 2 commits hechos.
- [ ] **Fase 4** — Grupo A (`Uuid`, `UserEmail`, `UserStatus`, `PhoneNumber`) fusionado. Grupo B (`UserName`, `AvatarUrl`, `Password`, `PinVerification`, `EmailVerifiedAt`) fusionado con estandarización de código `400`. Grupo D confirmado como NO tocado. `UserType` (4.6) resuelto según decisión del usuario (Opción A o B) — decisión documentada. Un commit por VO fusionado.
- [ ] **Fase 5** — Ruta 5A o 5B ejecutada según lo decidido en 4.6; discrepancia de `id` nullable en `reconstitute()` resuelta si aplica 5A; tests + types OK; commit hecho.
- [ ] **Fase 6** — `ANALISIS_PROYECTO.md` reescrito con los 23 módulos reales, bugs marcados como resueltos, deuda técnica actualizada; commit hecho.
- [ ] Mover este plan de `planes/por_hacer/` a `planes/implementados/` solo cuando TODAS las fases (incluyendo la decisión de 4.6, sea cual sea) estén cerradas.
- [ ] Reportar al usuario, al finalizar, los 2 hallazgos que este plan deliberadamente no resuelve: (a) `TenancyServiceProvider` huérfano de `bootstrap/providers.php` (Fase 1), (b) desacople entre `config('app.central_domain')` y `config('tenancy.central_domains')` (Fase 3).
