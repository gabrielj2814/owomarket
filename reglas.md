# Reglas y Estándares del Proyecto OwoMarket

Este documento establece las **reglas de desarrollo obligatorias** que todo desarrollador o asistente de Inteligencia Artificial debe consultar y obedecer estrictamente antes de realizar cualquier cambio en la base de código.

---

## 🎨 1. Reglas de Frontend (React + TypeScript + Flowbite)

1. **Uso Obligatorio de Servicios para Consumo de APIs**:
   * Toda petición HTTP (`GET`, `POST`, `PUT`, `DELETE`, etc.) enviada desde los componentes de React hacia el Backend debe realizarse a través de un **Servicio centralizado** ubicado en la carpeta `resources/js/Services/` (ej. `AdminServices.ts`, `TenantServices.ts`, `AuthServices.ts`).
   * **PROHIBIDO** realizar llamadas `fetch` o `axios` directas incrustadas dentro de los componentes o páginas de React.

2. **Tipado Estricto de Peticiones y Respuestas (TypeScript)**:
   * Cada método dentro de un servicio debe definir explícitamente el tipado del cuerpo de entrada (`Form*`) y la estructura de la respuesta (`Response*`).
   * Las interfaces de respuesta deben colocarse en `resources/js/types/Response/` (o `resources/js/types/`) y utilizar el envoltorio genérico `ApiResponse<T, E>`.

3. **Librería de Componentes**:
   * Usar componentes de **Flowbite React** (`Card`, `Button`, `Avatar`, `TextInput`, `FileInput`, `Label`, `Badge`, `Spinner`, `Modal`, `Breadcrumb`, etc.) para mantener coherencia visual y responsive.

4. **Diseño Responsivo y Scroll**:
   * Las vistas de administración que se renderizan dentro del layout `<Dashboard>` deben utilizar los contenedores con scroll vertical interno (`overflow-y-auto`) para garantizar que ningún formulario o botón se corte en dispositivos móviles ni en pantallas de escritorio.

---

## 🏛️ 2. Reglas de Backend (Arquitectura Hexagonal + DDD)

1. **Aislamiento Absoluto del Dominio (`src/{Context}/Domain`)**:
   * El código dentro de la capa de Dominio **NO DEBE** importar ni depender de clases de Laravel (ni Eloquent, ni Facades, ni helpers como `Illuminate\Support\Str`). Debe ser PHP puro.
   * La generación de UUIDs, hashing de contraseñas y otros servicios de infraestructura deben abstraerse mediante **Contratos/Interfaces** definidos en `Domain` o `Application/Contracts/`.

2. **Casos de Uso (Use Cases)**:
   * Toda la lógica de negocio vive en la capa de Aplicación dentro de `src/{Context}/Application/UseCase/`.
   * Los controladores HTTP (`src/{Context}/Infrastructure/Http/Controller/`) deben ser delgados: solo validan la entrada (`FormRequest` o DTO `spatie/laravel-data`), invocan el `UseCase` correspondiente y retornan la respuesta (`JsonResponse` o vista de Inertia).

3. **Modelos Eloquent e Infraestructura**:
   * Los modelos Eloquent (en `app/Models/` o `Infrastructure/Eloquent/Models/`) actúan exclusivamente como herramientas de persistencia (**Active Record**) y no deben contener lógica de negocio.
   * La interacción con bases de datos se realiza a través de la implementación del `RepositoryInterface` correspondiente.

4. **Inyección de Dependencias**:
   * Todos los contratos/interfaces de repositorios y servicios deben ser vinculados a su implementación concreta en su respectivo `ServiceProvider` (ej. `AdminServiceProvider.php`, `AppServiceProvider.php`).

5. **Uso Obligatorio del Helper `ApiResponse` en Respuestas JSON**:
   * Todos los controladores HTTP que devuelvan respuestas JSON deben utilizar obligatoriamente la clase `Src\Shared\Helper\ApiResponse` (`ApiResponse::success()`, `ApiResponse::error()`, `ApiResponse::Pagination()`) para garantizar una estructura estándar de respuesta `{ status, code, message, data, meta, errors }` compatible con el frontend TypeScript.

---

## 📌 3. Flujo de Trabajo antes de Implementar

1. **Consultar `reglas.md`**: Verificar si existe alguna regla predefinida aplicable a la tarea antes de escribir o modificar código.
2. **Consultar `ficha_tecnica.md`**: Revisar las versiones de dependencias y librerías disponibles.
3. **Planificar si la tarea lo requiere**: Crear el plan de implementación correspondiente cuando existan cambios arquitectónicos o nuevas funcionalidades complejas.

---

## 🔒 4. Reglas de Control de Versiones y Commits (Testing Obligatorio)

1. **Pruebas Obligatorias Previas al Commit:**
   * Cada vez que se implemente una funcionalidad, componente, caso de uso, repositorio o cambio de código, se debe ejecutar la suite de pruebas automatizadas correspondiente (`php artisan test` / `composer test` y `npm run types` si afecta al frontend).
   * **PROHIBIDO hacer commit si existe algún test fallido, error de compilación o error de tipado.**

2. **Creación de Commit tras Validación Exitosa:**
   * **SI Y SOLO SI** todas las pruebas pasan exitosamente al 100%, se debe proceder a crear un commit en Git para guardar los cambios de forma incremental.
   * Los mensajes de commit deben seguir el estándar de **Conventional Commits**:
     * `feat({modulo}): {descripción del cambio}`
     * `fix({modulo}): {descripción del fix}`
     * `test({modulo}): {descripción de las pruebas añadidas}`
     * `refactor({modulo}): {descripción de la refactorización}`

---

## 📁 5. Ubicación y Gestión de Documentos de Planificación

1. **Carpeta `planes/` Centralizada:**
   * Todos los planes maestros, desgloses de desarrollo por fases, especificaciones de módulos y hojas de ruta (`PLANIFICACION_*.md`, `PLAN_*.md`, `PUNTOS_CLAVE_*.md`) deben almacenarse, crearse y consultarse exclusivamente dentro del directorio `planes/` en la raíz del proyecto.
   * Cada vez que se complete una fase de desarrollo, se debe actualizar el archivo de planificación correspondiente en `planes/` marcando el checking `[x]` para registrar el avance de la implementación.


