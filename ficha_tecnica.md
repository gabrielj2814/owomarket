# Ficha Técnica del Proyecto OwoMarket

Este documento sirve como **referencia técnica integral** de todas las librerías, dependencias, frameworks, herramientas y decisiones de arquitectura utilizadas en el proyecto **OwoMarket**. Cualquier asistente de IA o desarrollador debe consultar esta ficha técnica antes de realizar cambios o agregar nuevas funcionalidades.

---

## 📌 1. Información General y Arquitectura

* **Nombre del Proyecto:** OwoMarket
* **Descripción:** Marketplace Multi-Tenant (Multi-inquilino) que permite gestionar múltiples tiendas independientes con aislamiento de base de datos y administración centralizada.
* **Patrón de Arquitectura:** Arquitectura Hexagonal (Puertos y Adaptadores) + Domain-Driven Design (DDD) + MVC Híbrido con Inertia.js.
* **Estructura de Directorios:**
  * `src/`: Dominio puro, Casos de Uso, Contratos/Interfaces e Infraestructura Hexagonal.
  * `app/`: Modelos Eloquent base, Providers y lógica nativa de Laravel.
  * `resources/js/`: Componentes React (Inertia), vistas, páginas y estilos.

---

## 🛠️ 2. Requisitos de Entorno y Sistema

| Herramienta | Versión Recomendada / Requerida |
| :--- | :--- |
| **PHP** | `^8.2` (Probado en 8.3 / 8.5) |
| **Composer** | `^2.9` |
| **Node.js** | `^22.0` (LTS) |
| **NPM** | `^10.0` |
| **Base de Datos** | MySQL `8.0` (Soporta múltiples BDs para Tenancy) |
| **Contenedores** | Docker & Docker Compose (Opcional, Nginx + PHP-FPM + MySQL + Redis) |

---

## 🐘 3. Backend Stack (PHP / Laravel)

### Dependencias Principales (`require`)

| Librería / Paquete | Versión | Propósito / Uso en el Proyecto |
| :--- | :--- | :--- |
| **`laravel/framework`** | `^12.0` | Framework web backend principal (Laravel 12). |
| **`stancl/tenancy`** | `^3.9` | Core de Multi-Tenancy. Maneja aislamiento de base de datos por tenant, resolución de subdominios wildcard e identificación del inquilino activo. |
| **`inertiajs/inertia-laravel`** | `^2.0` | Adaptador backend de Inertia.js. Permite renderizar vistas de React directamente desde controladores sin construir una API REST tradicional. |
| **`spatie/laravel-data`** | `^4.18` | Data Transfer Objects (DTOs) fuertemente tipados. Se usa para validación estricta, mapeo de datos entre peticiones HTTP y la capa de Aplicación/Dominio. |
| **`laravel/sanctum`** | `^4.0` | Autenticación ligera mediante tokens/cookies para sesiones y APIs. |
| **`laravel/wayfinder`** | `^0.1.11` | Generación automática de tipos y helpers para compartir rutas de Laravel hacia el frontend TypeScript. |
| **`laravel/tinker`** | `^2.10.1` | REPL interactivo para consola de comandos Artisan. |

### Dependencias de Desarrollo (`require-dev`)

| Librería / Paquete | Versión | Propósito / Uso en el Proyecto |
| :--- | :--- | :--- |
| **`pestphp/pest`** | `^4.1` | Framework de testing moderno y sintaxis expresiva basado en PHPUnit. |
| **`pestphp/pest-plugin-laravel`** | `^4.0` | Plugin de Pest con helpers específicos para pruebas de integración y HTTP en Laravel. |
| **`laravel/pint`** | `^1.24` | Linter y formateador automático de código PHP (basado en PHP-CS-Fixer). |
| **`mockery/mockery`** | `^1.6` | Framework de objetos de prueba (mocks) para pruebas unitarias de contratos en la capa de Dominio. |
| **`fakerphp/faker`** | `^1.23` | Generador de datos falsos para Seeders y Factories de prueba. |
| **`laravel/pail`** | `^1.2.2` | Herramienta CLI para streaming de logs en tiempo real durante el desarrollo. |
| **`laravel/sail`** | `^1.41` | Entorno de desarrollo en contenedores Docker para Laravel. |
| **`nunomaduro/collision`** | `^8.6` | Formateador elegante de errores y trazas de pila en consola CLI. |

---

## ⚛️ 4. Frontend Stack (JavaScript / TypeScript / React)

### Dependencias Principales (`dependencies`)

| Librería / Paquete | Versión | Propósito / Uso en el Proyecto |
| :--- | :--- | :--- |
| **`react`** / **`react-dom`** | `^19.0.0` | Librería UI principal (React 19). |
| **`@inertiajs/react`** | `^2.0.0` | Adaptador frontend de Inertia.js para React 19 (manejo de navegación SPA, props de controladores y estado de página). |
| **`vite`** | `^7.0.4` | Bundler y servidor de desarrollo ultrarrápido para assets. |
| **`@vitejs/plugin-react`** | `^4.6.0` | Plugin de integración de React en Vite con Fast Refresh (HMR). |
| **`laravel-vite-plugin`** | `^2.0` | Plugin para integrar la compilación de Vite con las directivas de Laravel Blades/Inertia. |
| **`tailwindcss`** | `^4.0.0` | Framework CSS utilitario (Tailwind CSS v4). |
| **`@tailwindcss/vite`** | `^4.1.11` | Integración nativa de Tailwind v4 con Vite. |
| **`flowbite`** & **`flowbite-react`** | `^4.0.1` / `^0.12.10` | Biblioteca de componentes UI precreados (modales, tablas, botones, dropdowns) adaptados para React y Tailwind. |
| **`react-icons`** | `^5.5.0` | Colección unificada de iconos (FontAwesome, Feather, Material Icons, etc.). |
| **`clsx`** | `^2.1.1` | Construcción de cadenas de `className` condicionales. |
| **`tailwind-merge`** | `^3.0.1` | Combinación eficiente de clases CSS de Tailwind evitando conflictos de especificidad. |
| **`class-variance-authority`** | `^0.7.1` | Creación de componentes reutilizables con variantes de diseño tipadas (CVA). |
| **`dayjs`** | `^1.11.19` | Manipulación, parseo y formateo ligero de fechas y horas. |
| **`uuid`** | `^13.0.0` | Generación de UUIDs (Universally Unique Identifiers) en el cliente. |
| **`concurrently`** | `^9.0.1` | Ejecución paralela de servicios CLI (Artisan serve + Worker Queue + Vite). |

### Dependencias de Desarrollo y Linting (`devDependencies`)

| Librería / Paquete | Versión | Propósito / Uso en el Proyecto |
| :--- | :--- | :--- |
| **`typescript`** | `^5.9.3` | Tipado estático para la base de código frontend. |
| **`@types/*`** | Varias (`react`, `react-dom`, `node`, `uuid`, `react-icons`) | Definiciones de tipos de TypeScript para librerías JavaScript. |
| **`eslint`** | `^9.17.0` | Analizador estático de código JavaScript/TypeScript (`@eslint/js`, `eslint-plugin-react`, `eslint-plugin-react-hooks`, `typescript-eslint`). |
| **`prettier`** | `^3.4.2` | Formateador automático de código (`prettier-plugin-tailwindcss`, `prettier-plugin-organize-imports`, `eslint-config-prettier`). |
| **`@laravel/vite-plugin-wayfinder`** | `^0.1.3` | Plugin para sincronizar las rutas de Laravel con autocompletado en React via Wayfinder. |

---

## 📐 5. Reglas de Arquitectura y Buenas Prácticas

1. **Dominio Aislado (`src/Domain`)**:
   * El código dentro de `Domain` **no debe importar nada de Laravel** (ni Facades, ni Eloquent, ni helpers de Laravel). Debe ser PHP puro.
   * La interacción con servicios externos (UUIDs, Hashing) debe hacerse mediante **interfaces/contratos** inyectados.
2. **DTOs en la capa de Entrada**:
   * Las peticiones HTTP deben ser mapeadas mediante `spatie/laravel-data` o `FormRequest` para garantizar un tipado seguro en los Casos de Uso.
3. **Controladores Delgados**:
   * Los controladores en `src/.../Infrastructure/Http/Controller` **no contienen lógica de negocio**. Solo invocan un `UseCase` y devuelven una vista de Inertia o un JSON.
4. **Modelos Eloquent (`app/Models`)**:
   * Funcionan únicamente como **herramientas de persisitencia / Active Record**. La lógica de reglas de negocio reside en las entidades de `src/Domain`.

---

## ⚙️ 6. Scripts y Comandos Principales

* **Entorno de desarrollo unificado (Servidor + Cola + Vite):**
  ```bash
  composer dev
  ```
* **Compilación Frontend para producción:**
  ```bash
  npm run build
  ```
* **Ejecutar Pruebas (Pest):**
  ```bash
  php artisan test
  # o con script composer:
  composer test
  ```
* **Formatear Código:**
  * PHP: `vendor/bin/pint`
  * Frontend: `npm run format`
* **Verificación de Tipos y Linter Frontend:**
  ```bash
  npm run types
  npm run lint
  ```
