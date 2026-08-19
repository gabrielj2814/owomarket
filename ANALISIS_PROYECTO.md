# 📊 Análisis Técnico Profundo del Proyecto: OwoMarket

> **Última actualización:** 2026-08-19  
> **Estado global:** 396 tests pasando (2,305 assertions) | TypeScript: 0 errores | Estilo: PSR-12 (Laravel Pint)

Este documento contiene la auditoría y análisis técnico exhaustivo del proyecto **OwoMarket**, detallando su propósito, arquitectura, stack tecnológico, estado de sus **23 contextos delimitados**, hallazgos resueltos, deuda técnica y roadmap arquitectónico.

---

## 📌 1. Resumen Ejecutivo y Propósito

**OwoMarket** es una plataforma de **Marketplace Multi-Tenant (Multi-inquilino)** diseñada bajo una arquitectura desacoplada que combina **Arquitectura Hexagonal (Puertos y Adaptadores)** y **Domain-Driven Design (DDD)** en el backend, junto con una interfaz reactiva SPA mediante **Laravel 12 + Inertia.js v2 + React 19 + TypeScript**.

El objetivo del sistema es permitir:
1. **Plataforma Central (Marketplace Central / SuperAdmin):** Registro de comerciantes, aprobación y suspensión de tiendas, administración de configuraciones globales, comisiones de monetización y catálogo central.
2. **Tiendas Aisladas (Tenants):** Cada comerciante cuenta con su propio subdominio/dominio y su **base de datos independiente** (gestionada con `stancl/tenancy`) para administrar catálogo, inventario, pedidos, envíos, facturación, pasarelas de pago y clientes.
3. **Storefront Público del Tenant:** Catálogo público de productos, filtros multicriterio, variantes, carrito de compras, cupones de descuento, pasarelas de pago y checkout seguro.

---

## 🛠️ 2. Stack Tecnológico y Componentes Principales

| Capa | Tecnologías Clave | Versión | Rol en el Proyecto |
| :--- | :--- | :--- | :--- |
| **Backend** | PHP / Laravel | `^12.0` (PHP 8.2+) | Framework web backend, CLI, middlewares y routing. |
| **Multi-Tenancy** | `stancl/tenancy` | `v3.9` | Aislamiento estricto de BD por tenant y resolución de subdominios wildcard. |
| **DTOs & Tipado** | `spatie/laravel-data` | `^4.18` | Tipado estricto entre peticiones HTTP y casos de uso. |
| **Frontend Bridge** | Inertia.js (`inertia-laravel` / `@inertiajs/react`) | `^2.0` | Conexión directa backend-frontend sin API REST redundante. |
| **Frontend UI** | React + TypeScript | `^19.0` / `^5.9` | SPA reactiva fuertemente tipada. |
| **Diseño / UI** | Tailwind CSS v4 + Flowbite React | `^4.0` / `^0.12` | Sistema de diseño utilitario moderno. |
| **Bundler** | Vite | `^7.0` | Compilador de assets y servidor HMR. |
| **Testing** | PestPHP / PHPUnit | `^4.1` | Suite de pruebas unitarias, integración y E2E. |
| **Infraestructura** | Docker Compose / Nginx / MySQL 8 / Redis | N/A | Contenedores para desarrollo y producción con soporte wildcard. |

---

## 🧩 3. Desglose de los 23 Contextos Delimitados en `src/`

La lógica de negocio reside exclusivamente en `src/`, organizada en 23 módulos clasificados por dominios funcionales:

```text
src/
├── Identidad y Acceso
│   ├── Admin/             # SuperAdmin central, aprobación y gestión de tenants.
│   ├── Authentication/    # Autenticación Web/API (Central y Tenant), Sanctum tokens.
│   ├── User/              # Usuarios del sistema central.
│   ├── CentralCustomer/   # Clientes globales y compradores de la plataforma central.
│   └── Customer/          # Clientes aislados de cada tienda (Tenant).
├── Tenant y Plataforma
│   ├── Tenant/            # Ciclo de vida del tenant (registro, dominios, owners, estados).
│   ├── TenantSettings/    # Configuraciones parametrizables de la tienda tenant.
│   ├── CentralMarketplace/# Landing, directorio de tiendas y búsqueda central.
│   ├── Marketplace/       # Portales públicos y vitrina comercial.
│   ├── Monetization/      # Planes, suscripciones y comisiones de la plataforma.
│   └── Billing/           # Facturación, perfiles fiscales, emisión de PDFs y comprobantes.
├── Catálogo
│   ├── Product/           # Productos, variantes, SKUs, imágenes e inventario.
│   ├── Category/          # Jerarquía de categorías y árboles de navegación.
│   ├── Brand/             # Marcas comerciales asociadas a productos.
│   ├── Attribute/         # Atributos dinámicos y opciones de variantes (talla, color, etc.).
│   ├── Review/            # Calificaciones, reseñas moderadas y feedback de clientes.
│   └── Coupon/            # Cupones de descuento, reglas de validación y límites de uso.
├── Operación Comercial
│   ├── Order/             # Gestión de pedidos, ítems, transiciones de estado y métricas.
│   ├── Payment/           # Métodos de pago, transacciones y pasarelas del tenant.
│   ├── Shipment/          # Guías de envío, paqueterías y seguimiento de entregas.
│   ├── Shipping/          # Zonas y tarifas de envío configuradas por el tenant.
│   └── Tax/               # Reglas y tasas de impuestos aplicables.
└── Compartido
    └── Shared/            # Kernel compartido (Value Objects, contratos, hashing, UUIDs, DTOs).
```

### Tabla de Estado y Madurez de los Módulos

| Bounded Context | Archivos PHP | Casos de Uso | Capas Implementadas | Estado Operativo |
| :--- | :---: | :---: | :---: | :--- |
| **Tenant** | 85 | 23 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **Product** | 81 | 16 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **Billing** | 61 | 0 (Repos/Services) | Dominio, Infraestructura, Integración | ✅ Operativo (PDFs, Mailer, Repos) |
| **Admin** | 50 | 12 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **Order** | 50 | 0 (Repos/Services) | Dominio, Infraestructura, Integración | ✅ Operativo (Lifecycle & Sync) |
| **Customer** | 49 | 0 (Repos/Services) | Dominio, Infraestructura, Integración | ✅ Operativo |
| **Authentication** | 48 | 9 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **Attribute** | 42 | 8 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **Shipping** | 41 | 8 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **Review** | 40 | 0 (Repos/Services) | Dominio, Infraestructura, Integración | ✅ Operativo (Moderación & Storefront) |
| **Coupon** | 37 | 6 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **Shipment** | 36 | 0 (Repos/Services) | Dominio, Infraestructura, Integración | ✅ Operativo (Order Sync) |
| **Shared** | 35 | N/A (Shared Kernel) | Dominio, Infraestructura | ✅ Consolidado y Centralizado |
| **Category** | 33 | 7 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **Brand** | 33 | 7 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **TenantSettings** | 31 | 0 (Repos/Services) | Dominio, Infraestructura, UI | ✅ Operativo |
| **Tax** | 31 | 6 | Dominio, Aplicación, Infraestructura, UI | ✅ Producción / Operativo |
| **Payment** | 25 | 0 (Repos/Services) | Dominio, Infraestructura | ✅ Operativo |
| **Marketplace** | 21 | 0 (Controllers/UI) | Infraestructura, UI | ✅ Storefront y Navegación |
| **Monetization** | 21 | 0 (Models/Repos) | Dominio, Infraestructura | ⚠️ En evolución |
| **CentralCustomer** | 15 | 0 (Models/Repos) | Dominio, Infraestructura | ⚠️ En evolución |
| **User** | 13 | 1 | Dominio, Aplicación, Infraestructura | ✅ Operativo |
| **CentralMarketplace** | 6 | 0 (Controllers/UI) | Infraestructura, UI | ⚠️ En evolución |

---

## 🚨 4. Historial de Hallazgos y Correcciones

### ✅ Resueltos

1. **Inyección de `UuidGenerator` en Casos de Uso y Factorías de Entidades:**
   - Se inyectó `UuidGenerator` como contrato obligatorio en todos los casos de uso (`CreateAdminUseCase`, `CreateTenantUseCase`, `CreateTenantOwnerUseCase`, `CreateTenantUserUseCase`, `AssignTenantToUserUseCase`, etc.).
   - Se corrigieron los tests unitarios eliminando errores de tipo `TypeError`.

2. **Unificación de Middleware de Tenancy:**
   - Se eliminó el middleware huérfano `HandleInertiaTenancy.php` y se unificó la inyección de props del inquilino (`tenant`, `current_domain`) dentro de `HandleInertiaRequests.php`.

3. **Centralización de Service Providers en `SharedServiceProvider`:**
   - Se creó `SharedServiceProvider` para registrar de forma única `UuidGenerator`, `PasswordHasher`, `PasswordValidator` y `PasswordGenerator`.
   - Se limpiaron los bindings redundantes en `AdminServiceProvider`, `AppServiceProvider`, `AuthServiceProvider` y `TenantServiceProvider`, y se eliminó el archivo vacío `UserServiceProvider`.

4. **Extracción del Generador de Contraseñas (`RandomPasswordGenerator`):**
   - Se desacopló la lógica de generación y validación de contraseñas de `CreateAdminPOSTController`, delegándola al contrato `PasswordGenerator` y la implementación `RandomPasswordGenerator`.

5. **Erradicación de `env()` fuera de `config/`:**
   - Se sustituyeron todas las llamadas directas a `env('APP_ENV')` por `app()->environment('local')` en los clientes HTTP internos.
   - Se definieron `config('app.central_domain')` y `config('app.dev_user_password')`, protegiendo la ejecución cuando se utiliza `php artisan config:cache`.

6. **Consolidación de Value Objects en `Shared Kernel`:**
   - Se migraron y unificaron en `src/Shared/Domain/ValueObjects/`:
     - `Uuid`, `UserEmail`, `UserStatus`, `PhoneNumber`, `UserName`, `AvatarUrl`, `Password`, `PinVerification`, `EmailVerifiedAt`, `UserType`.
   - Se estandarizó el código HTTP `400` en excepciones de validación.

7. **Unificación de la Entidad `AuthUser`:**
   - Se consolidó la entidad `AuthUser` canónica en `src/Shared/Domain/Entities/AuthUser.php` con soporte para `id` nullable en respuestas de API interna, eliminando las 3 definiciones duplicadas de `Admin`, `Product` y `Tenant`.

---

## 🔍 5. Deuda Técnica y Oportunidades Futuras

1. **Formalización de Casos de Uso en Módulos de Operación Comercial:**
   - Módulos como `Order`, `Billing`, `Shipment`, `Payment` y `Review` operan actualmente con repositorios e integraciones directas. Conforme el negocio crezca, se pueden encapsular en UseCases específicos (e.g., `PlaceOrderUseCase`, `GenerateInvoiceUseCase`).
2. **Event-Driven Architecture para Monolito Modular:**
   - Reemplazar progresivamente las llamadas HTTP internas (`/api/auth/interna/...`) por eventos de dominio o consultas directas a contratos de servicios en memoria para reducir latencia y evitar serialización JSON innecesaria.
3. **Consistencia de Configuración de Dominios Centrales:**
   - Evaluar la sincronización formal entre `config('app.central_domain')` y `config('tenancy.central_domains')` en un único punto de verdad.

---

## 🚀 6. Conclusión y Estado de Calidad

El proyecto **OwoMarket** cuenta con una base arquitectónica sólida, limpia y fuertemente tipada. Todas las pruebas automatizadas (396 tests, 2,305 assertions) pasan al 100%, TypeScript no presenta errores en el build y el backend sigue estrictamente las directrices de Domain-Driven Design y Arquitectura Hexagonal.
