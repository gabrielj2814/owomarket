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

5. **Testing Obligatorio de Componentes e Interfaces (Vitest + Playwright)**:
   * Cada vez que se desarrolle una nueva interfaz, vista, modal, componente de UI o flujo de usuario, se deben crear sus pruebas unitarias/de componente correspondientes en `tests/Frontend/Components/` o `tests/Frontend/Unit/` utilizando **Vitest** y **React Testing Library**.
   * Para flujos de navegación completos y transiciones críticas (checkout, autenticación, carrito), se deben crear sus pruebas End-to-End en `tests/Frontend/E2E/` utilizando **Playwright**.
   * Todo commit que incluya cambios en el frontend debe pasar `npm run test:unit` y `npm run types` con 0 errores.

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
3. **Planificación Obligatoria y Aprobación Previa**: Antes de realizar cualquier cambio o desarrollo, se debe presentar una planificación detallada con las tareas, componentes afectados y pasos de ejecución. **No se iniciará ningún trabajo ni modificación de código sin la aprobación previa y explícita del usuario**.

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

3. **Push Automático a Origin en la Rama Activa:**
   * Inmediatamente después de crear cada commit en Git (tras haber superado las validaciones y pruebas), se debe ejecutar `git push origin <rama_actual>` para mantener el repositorio remoto actualizado y respaldado en todo momento.

---

## 📁 5. Ubicación y Gestión de Documentos de Planificación
 
 1. **Carpeta `planes/` Centralizada y Estructura en 4 Carpetas:**
    * Todos los planes maestros, desgloses de desarrollo por fases, especificaciones de módulos, notas y hojas de ruta (`PLANIFICACION_*.md`, `PLAN_*.md`, `PUNTOS_CLAVE_*.md`, `ARQUITECTURA_*.md`) deben almacenarse exclusivamente dentro del directorio `planes/` en la raíz del proyecto, organizados en 4 subcarpetas obligatorias:
      - `planes/implementados/`: Planes de trabajo finalizados y 100% implementados.
      - `planes/por_hacer/`: Planes de trabajo en curso o aprobados que, al completarse al 100%, se moverán a la carpeta `planes/implementados/`.
      - `planes/futuros/`: Planificaciones y requerimientos a futuro que todavía no se van a realizar.
      - `planes/anotaciones/`: Documentos generales, notas técnicas, diagramas de arquitectura y especificaciones de referencia global.
    * Cada vez que se complete una fase o plan de desarrollo, se debe actualizar el archivo de planificación correspondiente marcando el checking `[x]` y trasladarlo a `planes/implementados/`.

---

## 🌱 6. Reglas de Seeders y Datos de Demostración

1. **Generación Obligatoria de Seeders para Nuevos Módulos:**
   * Cada vez que se implemente una nueva funcionalidad o módulo (ej. Configuración de Tienda, Catálogo de Productos, Atributos y Variantes, Reseñas y Calificaciones, Cupones de Descuento, Pedidos, etc.), se debe crear o actualizar un **Seeder** en `database/seeders/` con datos de prueba realistas, consistentes y completos.
   * Esto garantiza que los entornos de desarrollo local y las pruebas manuales puedan visualizar de inmediato el funcionamiento tanto en el backoffice como en el storefront público del inquilino sin requerir carga manual previa.



