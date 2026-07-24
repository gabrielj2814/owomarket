# Plan de Implementación: Reorganización del Shared Kernel (`Src\Shared`)

Este plan define los pasos para refactorizar y centralizar los elementos reutilizables de dominio e infraestructura en el **Shared Kernel** (`Src\Shared`), eliminando la duplicación de código en `Authentication`, `User`, `Tenant`, `Product` y `Admin`.

---

## Estrategia de Refactorización

1. **Creación de la Estructura Centralizada en `Src\Shared`**:
   - **`Src\Shared\Domain\Contracts`**: Interfaces de `UuidGenerator`, `PasswordHasher` y `PasswordValidator`.
   - **`Src\Shared\Domain\ValueObjects`**: Reorganización de `Uuid`, `StringValueObject`, `IntValueObject`, `BoolValueObject`, `FloatValueObject`, `CreatedAt`, `UpdatedAt`, `SoftDeleteAt` (corrigiendo además el nombre de carpeta `ValuesObjects` -> `ValueObjects`).
   - **`Src\Shared\Domain\Exceptions`**: `DomainException` e `InvalidUuidException`.
   - **`Src\Shared\Infrastructure\Security`**: Implementaciones reutilizables `LaravelUuidGenerator`, `LaravelPasswordHasher` y `StrictPasswordValidator`.

2. **Binding Global en Service Provider**:
   - Registrar la vinculación del contenedor de servicios para los contratos compartidos en `AppServiceProvider` (o un `SharedServiceProvider` exclusivo).

3. **Migración de Importaciones y Limpieza**:
   - Actualizar los namespaces e imports en todas las entidades, repositorios, casos de uso y Value Objects de los contextos `Authentication`, `User`, `Tenant`, `Product` y `Admin` para que consuman el **Shared Kernel**.
   - Eliminar interfaces y archivos duplicados en los dominios específicos que hayan sido centralizados.

4. **Pruebas y Verificación**:
   - Actualizar y ejecutar la suite de pruebas unitarias (`php artisan test`) para verificar 0 regresiones.

---

## Cambios Propuestos

### Componente: Shared Kernel (`Src\Shared`)

#### [NEW] [UuidGenerator.php](file:///c:/laragon/www/owomarket/src/Shared/Domain/Contracts/UuidGenerator.php)
- Interfaz del contrato `Src\Shared\Domain\Contracts\UuidGenerator`.

#### [NEW] [PasswordHasher.php](file:///c:/laragon/www/owomarket/src/Shared/Domain/Contracts/PasswordHasher.php)
- Interfaz del contrato `Src\Shared\Domain\Contracts\PasswordHasher`.

#### [NEW] [PasswordValidator.php](file:///c:/laragon/www/owomarket/src/Shared/Domain/Contracts/PasswordValidator.php)
- Interfaz del contrato `Src\Shared\Domain\Contracts\PasswordValidator`.

#### [NEW] [Uuid.php](file:///c:/laragon/www/owomarket/src/Shared/Domain/ValueObjects/Uuid.php)
- Value Object `Uuid` compartido en el Shared Kernel.

#### [NEW] [InvalidUuidException.php](file:///c:/laragon/www/owomarket/src/Shared/Domain/Exceptions/InvalidUuidException.php)
- Excepción de dominio para UUID inválido.

#### [NEW] [LaravelUuidGenerator.php](file:///c:/laragon/www/owomarket/src/Shared/Infrastructure/Security/LaravelUuidGenerator.php)
- Implementación de infraestructura para `UuidGenerator`.

#### [NEW] [LaravelPasswordHasher.php](file:///c:/laragon/www/owomarket/src/Shared/Infrastructure/Security/LaravelPasswordHasher.php)
- Implementación de infraestructura para `PasswordHasher`.

---

### Componente: Service Providers & Contextos (`Authentication`, `User`, `Tenant`, `Product`, `Admin`)

#### [MODIFY] [AppServiceProvider.php](file:///c:/laragon/www/owomarket/app/Providers/AppServiceProvider.php)
- Registrar los bindings compartidos para `UuidGenerator`, `PasswordHasher` y `PasswordValidator`.

#### [MODIFY] Archivos de Dominio, Infraestructura y Aplicación en los 5 Contextos
- Actualizar `use` statements para apuntar al Shared Kernel en `Src\Shared\Domain\...`.
- Eliminar las interfaces/clases duplicadas en cada dominio.

---

## Plan de Verificación

### Pruebas Automatizadas
- Ejecutar `php artisan test` para asegurar que las 9 pruebas (y nuevas pruebas unitarias del Shared Kernel) pasen al 100%.

### Análisis Estático / Linting
- Ejecutar `php -l` en todos los archivos modificados para verificar la integridad sintáctica.
