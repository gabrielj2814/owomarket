# 📊 Análisis Técnico Profundo del Proyecto: OwoMarket

Este documento contiene una auditoría y análisis técnico exhaustivo del proyecto **OwoMarket**, detallando su propósito, arquitectura, stack tecnológico, estado de sus módulos, hallazgos críticos (bugs reales detectados en código), deuda técnica y un plan de acción priorizado.

---

## 📌 1. Resumen Ejecutivo y Propósito

**OwoMarket** es una plataforma de **Marketplace Multi-Tenant (Multi-inquilino)** diseñada bajo una arquitectura desacoplada que combina **Arquitectura Hexagonal (Puertos y Adaptadores)** y **Domain-Driven Design (DDD)** en el backend, junto con una interfaz reactiva SPA mediante **Laravel 12 + Inertia.js v2 + React 19**.

El objetivo del sistema es permitir:
1. **Plataforma Central (Marketplace Central / SuperAdmin):** Registro de comerciantes, aprobación y suspensión de tiendas, administración de configuraciones globales y catálogo central.
2. **Tiendas Aisladas (Tenants):** Cada comerciante cuenta con su propio subdominio/dominio y su **base de datos independiente** para gestionar productos, categorías, variantes, inventario, pedidos, pasarelas de pago y clientes.

---

## 🛠️ 2. Stack Tecnológico y Componentes Principales

| Capa | Tecnologías Clave | Versión | Rol en el Proyecto |
| :--- | :--- | :--- | :--- |
| **Backend** | PHP / Laravel | `^12.0` (PHP 8.2+) | Framework web backend, CLI, middlewares y routing. |
| **Multi-Tenancy** | `stancl/tenancy` | `v3.9` | Aislamiento de BD por tenant, resolución dinámica de subdominios wildcard. |
| **DTOs & Tipado** | `spatie/laravel-data` | `^4.18` | Tipado estricto entre peticiones HTTP y casos de uso. |
| **Frontend Bridge** | Inertia.js (`inertia-laravel` / `@inertiajs/react`) | `^2.0` | Conexión directa backend-frontend sin necesidad de API REST tradicional. |
| **Frontend UI** | React + TypeScript | `^19.0` / `^5.9` | SPA reactiva fuertemente tipada. |
| **Diseño / UI** | Tailwind CSS v4 + Flowbite React | `^4.0` / `^0.12` | Sistema de componentes y estilos utilitarios modernos. |
| **Bundler** | Vite | `^7.0` | Servidor de desarrollo HMR y compilación de assets. |
| **Testing** | PestPHP / PHPUnit | `^4.1` | Framework de pruebas unitarias y de integración. |
| **Infraestructura** | Docker Compose / Nginx / MySQL 8 / Redis | N/A | Contenedores para desarrollo y producción con soporte wildcard. |

---

## 🧩 3. Desglose de Módulos (Bounded Contexts) en `src/`

La lógica de negocio reside en `src/`, organizada en contextos delimitados (Bounded Contexts):

```text
src/
├── Admin/           # Gestión del SuperAdministrador, aprobación de tenants, métricas.
├── Authentication/  # Autenticación Web (Central y Tenant) y emisión de tokens Sanctum.
├── Marketplace/     # Portales públicos (Home central y Home de tenant).
├── Product/         # Catálogo, precios, SKUs, variantes y atributos de productos.
├── Shared/          # Kernel compartido (Value Objects primitivos, contratos, hashing, UUIDs).
├── Tenant/          # Ciclo de vida del tenant (registro, dominios, propietarios, estados).
├── User/            # Gestión de usuarios del sistema central.
└── bounded_context_example/ # Plantilla/scaffold base para nuevos módulos.
```

### Tabla de Estado de Módulos

| Módulo | Capa Dominio | Capa Aplicación | Capa Infraestructura / UI | Estado General |
| :--- | :---: | :---: | :---: | :--- |
| **Authentication** | ✅ Completo | ✅ 9 Casos de Uso | ✅ Vistas React / Controladores | **Operativo con detalles de bindings** |
| **Tenant** | ⚠️ Falta sincronizar llamadas `create()` | ⚠️ 23 Casos de Uso (algunos con TypeError) | ✅ UI Admin / Controladores | **Avanzado, requiere corregir `UuidGenerator`** |
| **Admin** | ⚠️ Falta sincronizar `create()` | ⚠️ 12 Casos de Uso | ✅ Dashboard / Vistas React | **Avanzado, requiere limpiar controlador** |
| **Product** | ⚠️ VOs de usuario en módulo de producto | ⚠️ 5 Casos de Uso | ✅ Vistas básicas de producto | **En desarrollo** |
| **Marketplace** | ❌ Vacío | ❌ Vacío | ⚠️ 2 Controladores básicos | **Esqueleto inicial** |
| **User** | ✅ Básico | ✅ 1 Caso de Uso | ✅ Modelos y rutas | **Básico** |

---

## 🚨 4. Hallazgos Críticos y Bugs Detectados en el Código

Durante la auditoría del código se identificaron los siguientes **errores que causan fallos en tiempo de ejecución**:

### 1. Incompatibilidad de firmas en `Entity::create()` (TypeError Fatal)
En varias entidades de dominio se requiere la inyección de `UuidGenerator $generator` como primer parámetro en el método estático `create()`. Sin embargo, múltiples casos de uso llaman al método `create()` sin suministrar el generador o pasando otro argumento:
- **`Admin`**: En `src/Admin/Application/UseCase/CreateAdminUseCase.php` (Línea 54), se invoca `Admin::create($name, $email, ...)` mientras que `src/Admin/Domain/Entities/Admin.php` (Línea 63) exige `Admin::create(UuidGenerator $generator, UserName $name, ...)`.
- **`Tenant`**: En `src/Tenant/Application/UseCase/CreateTenantUseCase.php` (Línea 41), se invoca `Tenant::create($name, $slug, ...)` mientras que `src/Tenant/Domain/Entities/Tenant.php` (Línea 62) exige `Tenant::create(UuidGenerator $generator, TenantName $name, ...)`.
- **`TenantOwner`**: En `src/Tenant/Application/UseCase/CreateTenantOwnerUseCase.php` (Línea 65), no se pasa el `UuidGenerator`.
- **`TenantUser`**: En `src/Tenant/Application/UseCase/CreateTenantUserUseCase.php` (Línea 34), se pasa `$uuid_tenant` como primer argumento en lugar de `$generator`.
- **`Product`**: En `src/Product/Application/UseCase/CreateProductUseCase.php` (Línea 43), se usan argumentos nombrados omitiendo el parámetro obligatorio `generator:`.

> **Impacto:** Cualquier llamada a estos Casos de Uso lanzará un error fatal: `TypeError: Argument #1 ($generator) must be of type UuidGenerator`.

---

### 2. Código Muerto en Middleware de Tenancy
En `app/Http/Middleware/HandleInertiaTenancy.php` (Línea 19):
```php
public function handle(Request $request, Closure $next): Response
{
    return $next($request); // <-- Retorno prematuro
    // Todo este bloque que inyecta los datos del tenant a Inertia es inalcanzable:
    if (tenancy()->initialized) {
        ...
    }
}
```
El middleware nunca comparte los datos del inquilino con las vistas de Inertia porque retorna inmediatamente en la primera línea.

---

### 3. Propiedades Dinámicas en Entidad de Dominio (PHP 8.2+ Deprecation/Notice)
En `src/Tenant/Domain/Entities/Tenant.php` (Línea 112):
```php
public function setDomain(?Domain $domain){
    $this->domain = $domain; // $domain no está declarado (la propiedad declarada es $domains)
}
```
En PHP 8.2 y versiones posteriores, asignar propiedades dinámicas no declaradas genera advertencias de obsolescencia (`Creation of dynamic property is deprecated`).

---

### 4. Uso directo de `env()` fuera de archivos de configuración
En varios controladores y servicios (ej. `CreateAdminPOSTController.php`, `AuthTenantApiClient.php`, `TenantRepository.php`):
- Se usa `env("APP_ENV")` o `env("USER_PASSWORD_DEV")`.
- En Laravel, cuando se ejecuta `php artisan config:cache` para producción, **todas las llamadas directas a `env()` retornan `null`**, lo que romperá la lógica de negocio y las llamadas internas. Debe usarse siempre `config('app.env')`.

---

## 🔍 5. Deuda Técnica e Inconsistencias Arquitectónicas

### A. Duplicación de Código vs. Shared Kernel
Actualmente existen Value Objects idénticos duplicados en varios contextos:
- `Uuid.php`, `AvatarUrl.php`, `UserName.php`, `UserEmail.php` existen en `Authentication`, `Admin`, `Tenant`, `Product` y `Shared`.
- Específicamente, en `src/Product/Domain/ValueObjects` existen `UserEmail`, `UserName` y `UserType`, lo cual acopla conceptos de identidad de usuario dentro del catálogo de productos.
- **Solución:** Mover los Value Objects genéricos (`Uuid`, `CreatedAt`, `UpdatedAt`, `AvatarUrl`) a `src/Shared/Domain/ValueObjects` y consumirlos desde allí.

### B. Lógica de Negocio en Controladores HTTP
En `src/Admin/Infrastructure/Http/Controller/CreateAdminPOSTController.php` (Líneas 87-180), existen métodos privados para generar contraseñas y validar expresiones regulares (`generarContrasena`, `validarContrasena`).
- Esto viola el principio de que los controladores deben ser delgados y delega la responsabilidad a la capa de Aplicación/Dominio (donde ya existe `StrictPasswordValidator.php`).

### C. Comunicación entre Módulos (Simulación de Microservicios)
En `src/Product/Application/UseCase/ConsultAuthUserApiByUuidUseCase.php`, el módulo de productos hace una petición HTTP a una ruta interna (`/api-tenant/auth/interna/user/{uuid}`) para consultar datos del usuario.
- Al estar en un monolito modular, realizar llamadas HTTP internas añade sobrecarga de red y latencia innecesaria. Es preferible comunicarse mediante **interfaces de repositorio compartidas**, **servicios de dominio**, o **Eventos de Dominio**.

### D. Redundancia en Service Providers
Los bindings para `UuidGenerator`, `PasswordHasher` y `PasswordValidator` se repiten textualmente en `AdminServiceProvider.php`, `TenantServiceProvider.php` y `AuthServiceProvider.php`.
- Deben centralizarse una sola vez en un `SharedServiceProvider` o en `AppServiceProvider`.

### E. Cobertura de Pruebas (Testing Gap)
- Actualmente sólo existe un archivo de prueba funcional (`tests/Unit/UuidValueObjectTest.php`).
- No existen tests unitarios para los Casos de Uso, ni tests de integración para las migraciones y aislamiento de bases de datos de Tenancy.

---

## 🚀 6. Plan de Acción y Recomendaciones Priorizadas

### Fase 1: Corrección de Errores Inmediatos (Bugs Críticos)
1. **Sincronizar `create()` en Casos de Uso:** Inyectar `UuidGenerator` en los constructores de los casos de uso (`CreateAdminUseCase`, `CreateTenantUseCase`, `CreateTenantOwnerUseCase`, `CreateTenantUserUseCase`, `CreateProductUseCase`) y pasarlo a `Entity::create($this->generator, ...)`.
2. **Corregir Middleware de Tenancy:** Eliminar el `return $next($request);` prematuro en `HandleInertiaTenancy.php`.
3. **Reemplazar llamadas `env()`:** Sustituir `env(...)` en controladores y repositorios por llamadas a `config(...)`.
4. **Corregir propiedad `$domain`:** Declarar adecuadamente la propiedad en `Tenant.php`.

### Fase 2: Refactorización y Limpieza de Dominio
1. **Centralizar Value Objects genéricos:** Migrar `Uuid`, `AvatarUrl`, `Email`, etc., hacia `src/Shared/Domain/ValueObjects/`.
2. **Limpiar bindings en Providers:** Crear un `SharedServiceProvider` que registre una sola vez `UuidGenerator`, `PasswordHasher` y `PasswordValidator`.
3. **Extraer lógica de contraseñas de los controladores:** Reutilizar `StrictPasswordValidator` y los servicios de dominio correspondientes.
4. **Limpiar módulo Product:** Eliminar los Value Objects de usuario dentro del catálogo de productos.

### Fase 3: Suite de Pruebas Automatizadas (PestPHP)
1. Escribir tests unitarios para los casos de uso principales (`CreateTenantUseCase`, `CreateAdminUseCase`, `LoginWebUserUseCase`).
2. Implementar pruebas de integración para verificar el aislamiento de base de datos multi-tenant de `stancl/tenancy`.
