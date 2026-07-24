# Guía de Mejoras y Buenas Prácticas: Arquitectura Hexagonal Multi-Tenant

¡Felicitaciones por implementar **Arquitectura Hexagonal (Puertos y Adaptadores)** y **DDD** en un entorno **Multi-Tenant**! Combinar ambos patrones en Laravel requiere rigor y disciplina, y la estructura actual del proyecto en `src/` demuestra una base sólida de diseño:

- Separación clara de responsabilidades en tres capas fundamentales (`Domain`, `Application`, `Infrastructure`).
- Inmutabilidad en los **Value Objects** mediante constructores privados y Factory Methods (`create`, `reconstitute`, `make`).
- Casos de uso orientados a una única tarea (`Single Responsibility Principle`).
- Desacoplamiento de librerías del framework mediante contratos e **Inyección de Dependencias** (ej. `UuidGenerator`, `PasswordHasher`).

A continuación se presenta un análisis detallado a nivel de código con recomendaciones específicas para elevar la calidad, mantenibilidad y escalabilidad del proyecto.

---

## 1. Reorganización del Shared Kernel (Dominio Compartido)

### ⚠️ Situación Actual
Actualmente, interfaces como `UuidGenerator`, `PasswordHasher`, `PasswordValidator` y Value Objects genéricos (`Uuid`, `CreatedAt`, `UpdatedAt`, `AvatarUrl`) están duplicados en casi todos los Bounded Contexts (`Authentication`, `User`, `Tenant`, `Product`, `Admin`).

### 💡 Recomendación
Mover los contratos y Value Objects puramente genéricos y agnósticos del dominio de negocio al **Shared Kernel** (`Src\Shared`):

```text
src/Shared/
├── Domain/
│   ├── Contracts/
│   │   ├── UuidGenerator.php
│   │   ├── PasswordHasher.php
│   │   └── PasswordValidator.php
│   ├── ValueObjects/
│   │   ├── Uuid.php
│   │   ├── StringValueObject.php
│   │   ├── IntValueObject.php
│   │   ├── CreatedAt.php
│   │   └── UpdatedAt.php
│   └── Exceptions/
│       ├── DomainException.php
│       └── InvalidUuidException.php
└── Infrastructure/
    └── Security/
        ├── LaravelUuidGenerator.php
        └── LaravelPasswordHasher.php
```

#### Ventajas:
- Elimina duplicación de código redundante.
- Mantiene las reglas del lenguaje omnipresente para tipos primitivos (`Uuid`, fechas) en un solo lugar.
- Permite que los contratos de seguridad compartidos se registren una sola vez en un `SharedServiceProvider` o `AppServiceProvider`.

---

## 2. Normalización de Convenciones de Nombres (Naming Conventions)

### ⚠️ Situación Actual
Existen ligeras inconsistencias ortográficas en las carpetas de algunos módulos:
- En la mayoría de contextos se usa `Domain/ValueObjects/`.
- En el contexto `Tenant` se utiliza `Domain/ValuesObjects/` (con `s` en `Values`).

### 💡 Recomendación
Estandarizar todas las carpetas a la forma singular/plural estándar:
- `Domain/ValueObjects/` para todos los contextos.

---

## 3. Implementación de Eventos de Dominio (Domain Events)

### ⚠️ Situación Actual
En aplicaciones Multi-Tenant, la comunicación entre la base de datos central y la base de datos del inquilino suele acoplar casos de uso o repositorios si se llaman directamente.

### 💡 Recomendación
Introducir **Eventos de Dominio** para desacoplar contextos mediante eventos asíncronos o sincrónicos:

1. **Definir la interfaz base de Evento de Dominio**:
   ```php
   namespace Src\Shared\Domain\Events;

   interface DomainEvent {
       public function occurredOn(): \DateTimeImmutable;
   }
   ```
2. **Publicar eventos al realizar mutaciones clave**:
   Por ejemplo, cuando se crea un Tenant (`TenantCreatedDomainEvent`), se publica el evento y un oyente (`Subscriber`/`Listener`) en la capa de Infraestructura ejecuta la creación del dominio y usuario por defecto en la base de datos del Tenant.

---

## 4. Manejo Global de Excepciones de Dominio en HTTP

### ⚠️ Situación Actual
Cuando un Value Object o Entidad lanza una excepción de dominio (ej. `InvalidUuidException` o `InvalidArgumentException`), si no se captura explícitamente en el controlador, Laravel devuelve un error 500.

### 💡 Recomendación
Registrar un captulador de excepciones de dominio en `bootstrap/app.php` o mediante un Middleware de Infraestructura:

```php
// En bootstrap/app.php (Laravel 12)
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (\Src\Shared\Domain\Exceptions\DomainException $e, Request $request) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 400);
    });
})
```

---

## 5. Aislamiento Multi-Tenant (Central DB vs Tenant DB)

### 💡 Buenas Práctica para Multi-Tenancy:
- **Central Context**: Modelos como `Tenant`, `Domain`, `CentralUser` deben residir explícitamente en la base de datos central.
- **Tenant Context**: Modelos como `Product`, `Order`, `Customer` deben ejecutarse dentro del scope de `stancl/tenancy`.
- **Regla en DDD**: Asegúrate de que los repositorios de Infraestructura sepan explícitamente en qué conexión/contexto deben operar, evitando fugas de información entre inquilinos.

---

## 6. Estrategia de Pruebas Unitarias y de Integración (Testing Strategy)

Para mantener la velocidad y confianza en el sistema:

1. **Pruebas Unitarias de Casos de Uso (`tests/Unit/Application/`)**:
   - Prueba los `UseCases` simulando (mocking) las interfaces de los repositorios. No requieras base de datos ni framework. Son instantáneas (milisegundos).
2. **Pruebas de Integración de Repositorios (`tests/Integration/Infrastructure/`)**:
   - Prueba que los repositorios de Eloquent guardan y leen correctamente de la base de datos real.

---

## Checklist de Próximos Pasos Sugeridos

- [ ] Unificar la carpeta `ValuesObjects` a `ValueObjects` en el módulo `Tenant`.
- [ ] Evaluar la centralización de `Uuid` y `UuidGenerator` en `Src/Shared/Domain`.
- [ ] Configurar el manejador global de `DomainException` en Laravel para retornar respuestas HTTP 400/422 estructuradas.
- [ ] Escribir tests unitarios para los Use Cases principales (`LoginWebUserUseCase`, `CreateTenantUseCase`, etc.).

---
*Este documento sirve como hoja de ruta arquitectónica para continuar evolucionando **OwoMarket** con los estándares más altos de desarrollo de software.*
