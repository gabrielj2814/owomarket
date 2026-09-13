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
   * **Flowbite en todas las vistas. Tailwind puro SOLO para lo que Flowbite no cubra**: un componente que la librería no trae, o un remate que ningún componente resuelve. Un `className` para ajustar un margen es normal; un `className` que **reconstruye** algo que Flowbite ya trae —una tarjeta, un modal, un botón— no lo es.
   * **El aspecto de una zona se declara en un tema, no en cada pantalla.** Ver `resources/js/theme/portalTheme.ts` y su `<ThemeProvider>` en `CustomerAccountLayout`: las páginas escriben `<Card>` y `<Button color="primary">` a secas y salen con el aspecto de su zona. Si una pantalla necesita un `className` para parecerse a sus hermanas, el sitio de arreglarlo es el tema. Sin esto, migrar a Flowbite solo cambia la etiqueta con la que se duplica el mismo estilo.
   * **Los colores válidos dependen del componente.** En `flowbite-react` 0.12 los BOTONES aceptan `red`, `green`, `blue`, `light`, `dark`… pero **no** `success` ni `failure` (que sí valen en las insignias). Un color inexistente deja el botón sin relleno, sin ningún error: se ve como texto plano y nadie lo reporta.
   * La migración terminó el 12/09/2026 (81/83 páginas). Lo que quedó fuera lo está a propósito, con la razón escrita en cada fichero.

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

5. **`final` sí, salvo en lo que los tests doblan**:
   * Los servicios y casos de uso se marcan `final` por defecto.
   * **Excepción declarada:** un colaborador que los tests sustituyen por un doble de Mockery no lleva `final`, porque Mockery necesita heredar de él. No es una violación de la convención: es parte de ella. Basta un comentario de una línea sobre la clase.
   * No se crea una interfaz con una sola implementación sólo para poder doblarla.

6. **Uso Obligatorio del Helper `ApiResponse` en Respuestas JSON**:
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
 
 1. **Dos ficheros de referencia, y hay que leer los dos antes de tocar nada:**
    * `planes/COMO_FUNCIONA.md` — **cómo funciona**: el negocio, el recorrido del dinero, las garantías, la arquitectura y las reglas que explican el código raro.
    * `planes/ESTADO_DEL_PROYECTO.md` — **qué está hecho**: lo que hace, lo que no hace todavía, las decisiones tomadas y no aplicadas, y los riesgos.

 2. **Los dos se actualizan en la MISMA entrega que el código, nunca después.**
    * ¿El cambio toca una regla, un recorrido del dinero o uno de los ajustes? → también a `COMO_FUNCIONA.md`.
    * ¿Es una pantalla nueva, un arreglo, algo que se termina o se descarta? → también a `ESTADO_DEL_PROYECTO.md`.
    * **Un documento de referencia desactualizado es peor que no tenerlo**: se lee con confianza y miente. Dejarlo «para el final» es no hacerlo.

 3. **Carpeta `planes/`, con tres subcarpetas:**
    * Todos los planes (`PLANIFICACION_*.md`, `PLAN_*.md`, `PUNTOS_CLAVE_*.md`, `ARQUITECTURA_*.md`) viven dentro de `planes/`, en tres subcarpetas:
      - `planes/por_hacer/`: planes en curso o aprobados.
      - `planes/futuros/`: planificaciones que todavía no se van a realizar.
      - `planes/anotaciones/`: auditorías, decisiones y referencias. **Esta carpeta no se borra**: los comentarios del código citan sus hallazgos por número («hallazgo N35», «hallazgo A3»), y sin ella esas referencias quedan huérfanas.
    * **Un plan terminado no se archiva: se absorbe y se borra.** Al completarlo, lo que se aprendió pasa a `ESTADO_DEL_PROYECTO.md` y el fichero se elimina. La carpeta `planes/implementados/` existió hasta el 13/09/2026 y llegó a acumular 60 planes que nadie leía y que el código no citaba; el historial de git los conserva si alguien los necesita.
    * **Antes de borrar un plan, comprueba quién lo cita.** Los comentarios del código enlazan ficheros de `planes/`, y borrar uno sin repuntar sus referencias deja enlaces a ninguna parte:
      ```bash
      grep -rn "NOMBRE_DEL_PLAN" src/ resources/ tests/
      ```

---

## 🌱 6. Reglas de Seeders y Datos de Demostración

1. **Generación Obligatoria de Seeders para Nuevos Módulos:**
   * Cada vez que se implemente una nueva funcionalidad o módulo (ej. Configuración de Tienda, Catálogo de Productos, Atributos y Variantes, Reseñas y Calificaciones, Cupones de Descuento, Pedidos, etc.), se debe crear o actualizar un **Seeder** en `database/seeders/` con datos de prueba realistas, consistentes y completos.
   * Esto garantiza que los entornos de desarrollo local y las pruebas manuales puedan visualizar de inmediato el funcionamiento tanto en el backoffice como en el storefront público del inquilino sin requerir carga manual previa.



