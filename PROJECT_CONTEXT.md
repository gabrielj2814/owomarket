# Contexto del Proyecto OwoMarket (Project Context)

Este documento está diseñado para proporcionar contexto crítico tanto a desarrolladores humanos como a asistentes de Inteligencia Artificial que trabajen en este repositorio.

## 1. Visión General del Proyecto
**OwoMarket** es una plataforma de **Marketplace Multi-Tenant** (Multi-inquilino) que permite la creación y gestión de múltiples tiendas dentro de un ecosistema centralizado.

## 2. Stack Tecnológico (Tech Stack)
*   **Backend:** Laravel 12 (PHP ^8.2)
*   **Frontend:** React + TypeScript (integrado vía Inertia.js v2.0)
*   **UI/Estilos:** Tailwind CSS y componentes de Flowbite (`.flowbite-react`)
*   **Bundler:** Vite
*   **Base de Datos:** Relacional (Típicamente MySQL/PostgreSQL soportado por Laravel)

## 3. Paquetes Críticos y Herramientas Core
Cualquier modificación o adición al proyecto debe tener en cuenta el uso de los siguientes paquetes base:
*   **Multi-Tenancy:** `stancl/tenancy` (v3.9). Maneja el aislamiento de las tiendas.
*   **Data Transfer Objects (DTOs):** `spatie/laravel-data` (v4.18). Se utiliza para el tipado estricto entre las peticiones HTTP y los casos de uso.
*   **Frontend Bridge:** `inertiajs/inertia-laravel`.

## 4. Arquitectura y Estructura (Arquitectura Hexagonal / DDD)
El proyecto se aleja del estándar MVC tradicional de Laravel para la lógica de negocio compleja, adoptando una **Arquitectura Hexagonal (Puertos y Adaptadores)** combinada con principios de **Domain-Driven Design (DDD)**.

Toda la lógica core del negocio vive en la carpeta `src/`. Gran parte de sus componentes (Casos de Uso, Controladores, Interfaces y Repositorios) están fuertemente tipados y documentados mediante PHPDoc para facilitar el autocompletado y comprensión del código.

### Estructura de un Módulo (Bounded Context) en `src/`
Ejemplo con el módulo `Product` (`src/Product/`):
*   **`Domain/`**:
    *   Contiene **Entities** (Entidades de dominio puro), **ValueObjects** (UUID, Email, Precio) y **Exceptions**.
    *   *Regla de Oro:* El código en `Domain` **NO DEBE** tener dependencias de Laravel (ni Eloquent, ni Facades). Debe ser PHP puro.
*   **`Application/`**:
    *   Contiene **UseCases** (Casos de uso como `CreateProductUseCase`) y **Contracts** (Interfaces como `ProductRepositoryInterface`).
    *   *Regla de Oro:* Los UseCases orquestan el flujo. Llaman a las entidades de dominio y utilizan los contratos para persistir la información.
*   **`Infrastructure/`**:
    *   Contiene **Http** (Controllers, FormRequests) y **Eloquent** (Modelos de BD y Repositorios reales que implementan los Contracts).
    *   *Regla de Oro:* Los Controladores no deben contener lógica de negocio; solo validan el Request (vía FormRequest/DTO), llaman a un UseCase y devuelven una respuesta (JSON o renderizado de Inertia).

### Modelos Base de Laravel
Los modelos tradicionales de base de datos (Eloquent) se encuentran en `app/Models/` (ej. `Tenant.php`, `Product.php`, `User.php`, `Order.php`). Estos actúan únicamente como herramientas de infraestructura (Active Record) para las consultas a la BD y **no** deben estar saturados de lógica de negocio (esa lógica pertenece a `src/.../Domain`).

## 5. Reglas y Convenciones de Código para IA y Desarrolladores
1.  **Aislamiento de Lógica:** NUNCA escribas lógica de negocio, cálculos o reglas complejas directamente en un Controlador en `src/.../Infrastructure/Http/Controller`. Usa siempre un Caso de Uso (`UseCase`).
2.  **Inyección de Dependencias:** Utiliza el contenedor de servicios de Laravel para inyectar Repositorios y Casos de Uso. Evita el uso del helper `app()` o instanciar clases directamente con `new` cuando sea posible inyectarlas.
3.  **Validación de Datos:** Toda entrada HTTP debe ser validada mediante un `FormRequest` antes de llegar al controlador, o mapeada a un DTO (`spatie/laravel-data`) para asegurar el tipado fuerte hacia la capa de Aplicación.
4.  **Value Objects:** Usa Value Objects para validar primitivas. Por ejemplo, si una función requiere un UUID, pásale una instancia de `UuidValueObject`, no un `string`.
5.  **Multi-Tenancy:** Ten siempre presente el contexto de ejecución. Si estás consultando/modificando datos de una tienda, asegúrate de estar operando bajo el Tenant correcto o utilizar las relaciones adecuadas.

## 6. Comandos Útiles y Entorno Local
*   **Docker:** El proyecto cuenta con un entorno Docker propio (sin Laravel Sail). Usa `docker-compose up -d --build` para levantar toda la infraestructura (PHP, Node, Nginx, MySQL, phpMyAdmin).
*   **Comandos en Docker:** `docker-compose exec app php artisan ...`
*   **Testeo:** `docker-compose exec app php artisan test` (PestPHP)
*   **Desarrollo tradicional (sin Docker):** `npm run dev` y `php artisan serve`
