# PLAN — Fase 0.3-A: Aplicar autorización al backoffice central y a la API de monetización

> **Origen:** hallazgos A1 (residual), A4 y A9 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md`
> **Severidad:** 🔴 Crítico
> **Tamaño:** 2 archivos de rutas modificados, 0 controladores tocados
> **Estado:** ✅ Implementado — pendiente de validación con tests
> **Depende de:** Fase 0.2 (los alias `super_admin` y `staff` deben existir)

---

## 1. Por qué esta fase se divide

La Fase 0.3 completa toca ~60 rutas repartidas en seis frentes con riesgos muy distintos. Se parte en sub-fases para poder validar cada una por separado:

| Sub-fase | Alcance | Toca casos de uso | Estado |
| :--- | :--- | :---: | :--- |
| **0.3-A** | Backoffice central + API de monetización | No | ✅ Este documento |
| 0.3-B | APIs del tenant owner + verificación de propiedad | Sí | ⬜ Pendiente |
| 0.3-C | Mesa de soporte central + suplantación de agentes | Sí | ⬜ Pendiente |
| 0.3-D | API de clientes centrales (requiere guard `central_customer`) | Sí + frontend | ⬜ Pendiente |
| 0.3-E | Grupo `api-tenant` (requiere decidir el mecanismo) | Por decidir | ⬜ Pendiente |

Esta sub-fase es la más segura de las cinco: son cambios **declarativos en archivos de rutas**, sin tocar un solo controlador, caso de uso ni modelo.

---

## 2. Contexto crítico descubierto

**`CreateAdminUseCase.php:52` crea todos los administradores con `type = UserType::SUPER_ADMIN`:**

```php
$type = UserType::make(UserType::SUPER_ADMIN);
```

Esto tiene dos consecuencias que hay que entender bien antes de leer el resto:

1. **No hay riesgo de dejar a nadie fuera.** Todo administrador existente pasa cualquier comprobación `staff:<permiso>` por el atajo de super administrador implementado en la Fase 0.2. El panel sigue funcionando igual para ellos.

2. **Los permisos granulares aún no restringen nada.** Los cuatro roles de Spatie (Agente de Soporte, Moderador de Catálogo, Gestor Financiero) no pueden asignarse a nadie de forma efectiva mientras todo admin nazca como `super_admin`. La estructura queda montada y correcta, pero **el RBAC granular no entra en vigor hasta que `CreateAdminUseCase` acepte un tipo de staff distinto** — trabajo de seguimiento, fuera del alcance de la Fase 0.

**Lo que sí se gana ahora mismo, y es el objetivo real de esta fase:** los usuarios que **no** son administradores —`tenant_owner`, `customer`, y cualquier cuenta creada por el registro público de comercios— quedan expulsados del backoffice central. Ese era exactamente el vector de escalada de privilegios del hallazgo A1: hasta hoy bastaba con estar autenticado, con cualquier rol, para llamar a `POST /admin/api/security/staff/{uuid}/roles` y auto-asignarse `super-admin`.

---

## 3. Archivo modificado 1: `src/Admin/Infrastructure/Http/Routes/web.php`

Las 58 rutas pasaron de `->middleware('auth')` individual a grupos `Route::middleware([...])->group()` por área funcional.

### Mapa de autorización aplicado

| Área | Rutas | Middleware |
| :--- | :---: | :--- |
| Dashboard del backoffice | 1 | `auth`, `staff` |
| Perfil propio del administrador | 5 | `auth` |
| Gestión de administradores | 8 | `auth`, `super_admin` |
| Finanzas y payouts | 4 | `auth`, `staff:manage_payouts` |
| Mesa central de soporte | 4 | `auth`, `staff:manage_support` |
| Directorio central de clientes | 4 | `auth`, `staff:manage_customers` |
| Monitor global de órdenes | 4 | `auth`, `staff:manage_orders` |
| Expediente 360° y gobernanza | 2 | `auth`, `staff:manage_tenants` |
| **Impersonación de tienda (SSO)** | 1 | `auth`, `super_admin` |
| Catálogo maestro (marcas y categorías) | 8 | `auth`, `staff:manage_catalog` |
| Moderación de productos | 3 | `auth`, `staff:manage_moderation` |
| CMS de banners | 4 | `auth`, `staff:manage_cms` |
| Planes de suscripción B2B | 4 | `auth`, `staff:manage_plans` |
| **Seguridad y roles RBAC** | 4 | `auth`, `super_admin` |
| Pista de auditoría | 2 | `auth`, `staff:view_audit_logs` |

### Tres decisiones que merecen explicación

**El perfil propio se queda solo con `auth`.** Las cinco rutas de `/backoffice/{user_uuid}/profile` operan sobre la cuenta del propio usuario. Añadirles un gate de rol impediría a un futuro agente de soporte cambiar su propia contraseña. El problema real de esas rutas es distinto: usan el `{user_uuid}` de la URL en lugar de `auth()->id()`, y el PIN no tiene límite de intentos (hallazgo A7). Eso se corrige aparte, con `throttle` y usando la identidad de sesión.

**La impersonación de tiendas sube a `super_admin`, separada del resto de gobernanza.** `POST /api/tenants/{id}/sso-token` emite un token que abre sesión como el propietario de una tienda: es la operación de mayor privilegio del panel. El expediente 360° y el cambio de estado de gobernanza se quedan en `staff:manage_tenants`, que es lectura y administración; la impersonación no.

**Los roles RBAC suben a `super_admin`.** Quien puede asignar roles puede concederse cualquier permiso, así que gatear `/api/security/*` con `staff:manage_staff_roles` sería circular: bastaría tener ese permiso para dárselo todo. Se reserva al super administrador.

### Verificación de equivalencia

Se comparó automáticamente el archivo original con el nuevo:

```
rutas (método, path):     IDÉNTICAS   (58)
nombres de ruta:          IDÉNTICOS   (15)
controladores referidos:  IDÉNTICOS   (58)
```

Ni una sola ruta, nombre o controlador cambió. La única diferencia es el middleware.

---

## 4. Archivo modificado 2: `src/Monetization/Infrastructure/Http/Routes/apiCentral.php`

Las seis rutas pasan a `['web', 'auth', 'super_admin']`:

```php
Route::middleware(['web', 'auth', 'super_admin'])->group(function () {
    Route::get('/plans', ListPlansGETController::class);
    Route::post('/custom-commission', UpdateTenantCustomCommissionPOSTController::class);
    Route::get('/metrics', GetSuperAdminMonetizationMetricsGETController::class);
    Route::get('/settlements', ListCommissionSettlementsGETController::class);
    Route::post('/settlements/generate', GenerateCommissionSettlementPOSTController::class);
    Route::post('/settlements/{id}/confirm', ConfirmCommissionSettlementPOSTController::class);
});
```

**Por qué `web` además de `auth`.** Estas rutas se montan desde `routes/api.php`, y el grupo `api` de Laravel **no arranca sesión**. Sin `web`, el middleware `auth` no tendría sesión sobre la que resolver la identidad y devolvería 401 siempre. Añadir `web` aporta `StartSession` — y de paso `VerifyCsrfToken`, es decir, protección CSRF sobre los POST, que es lo correcto para un endpoint que cambia tasas de comisión.

**Comprobado que no rompe la UI:** se buscó en todo `resources/js/` y **ninguna** llamada apunta a `/api/central/monetization/*`. El panel de planes del admin usa `/admin/api/plans/subscription-plans`, que es otra ruta. Estos seis endpoints no tienen consumidor todavía.

Cuando se construya la pantalla de monetización del superadmin, tendrá que enviar el token CSRF en los POST — el proyecto ya tiene el helper `resources/js/utils/getCSRFToken.ts` para eso.

---

## 5. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **A1** — escalada de privilegios vía backoffice | ✅ Cerrado (la Fase 0.1 quitó el acceso desde dominios de tenant; esta cierra el acceso de no-administradores en el dominio central) |
| **A4** — API de monetización anónima | ✅ Cerrado |
| **A9** — impersonación sin control de rol | 🟡 Parcial: ya exige `super_admin`, pero siguen pendientes la falta de auditoría y la URL rota (`/auth/sso` en vez de `/auth/sso-consume`) |

---

## 6. Tareas

- [x] Reescribir `src/Admin/Infrastructure/Http/Routes/web.php` con grupos de middleware por área
- [x] Proteger `src/Monetization/Infrastructure/Http/Routes/apiCentral.php`
- [x] Verificar sintaxis con `php -l`
- [x] Verificar que rutas, nombres y controladores son idénticos a los originales
- [ ] `php artisan route:clear && php artisan config:clear`
- [x] `php artisan route:list --path=admin` — confirmar los 58 endpoints y su columna de middleware
- [x] `php artisan test`
- [x] `npm run types`
- [x] `vendor/bin/pint src/Admin/Infrastructure/Http/Routes/ src/Monetization/Infrastructure/Http/Routes/`
- [x] Commit: `fix(admin,monetization): exigir rol de super admin y permisos RBAC en el backoffice central`
- [x] `git push origin <rama_actual>`
- [x] Mover este documento a `planes/implementados/`

---

## 7. Verificación manual

**Debe seguir funcionando** (sesión de `root@owomarket.local`, `type = super_admin`):
1. Entrar al backoffice y navegar por dashboard, payouts, soporte, clientes, órdenes, catálogo maestro, moderación, banners, planes, roles y audit logs. Todo debe cargar.
2. Aprobar o rechazar un payout, guardar una marca maestra, moderar un producto.

**Debe dejar de funcionar:**
3. Iniciar sesión con una cuenta de `tenant_owner` y visitar `/admin/backoffice/{uuid}/dashboard` → **403**.
4. Con esa misma sesión, `POST /admin/api/security/staff/{uuid}/roles` → **403** (antes: 200 y escalada a superadmin).
5. Sin sesión, `GET /api/central/monetization/metrics` → **401** (antes: 200 con la facturación global).

---

## 8. Riesgo

**Bajo, con una salvedad.** Ningún administrador existente pierde acceso, porque todos tienen `type = 'super_admin'` y el atajo de la Fase 0.2 los deja pasar antes de consultar la tabla de permisos.

La salvedad: si en algún punto existiera un usuario con `type = 'super_admin'` marcado como `is_active = false`, ahora recibirá 403 donde antes entraba. Es el comportamiento correcto, pero conviene tenerlo presente si alguien reporta que "de repente no puede entrar".

---

## 9. Trabajo de seguimiento que deja identificado

1. **`CreateAdminUseCase` hardcodea `SUPER_ADMIN`.** Mientras siga así, los cuatro roles de Spatie son decorativos. Debería aceptar el tipo (o el rol) como parámetro para que el RBAC granular tenga efecto real.

2. **Los roles y permisos se crean de forma perezosa.** `ListStaffRolesAndPermissionsUseCase::ensureDefaultPermissionsAndRolesExist()` solo se ejecuta cuando alguien abre la pantalla de roles. Si nadie la abre, la tabla `roles` está vacía. Convendría moverlo a un seeder propio (`RbacRolesSeeder`) o a una migración.

3. **`RootUserSeeder` no asigna el rol "Super Admin" de Spatie**, solo escribe `type = 'super_admin'`. Funciona por el atajo, pero deja la pista de auditoría de roles incompleta.

4. **Hallazgo A7 sigue abierto**: las rutas de perfil usan `{user_uuid}` de la URL en lugar de `auth()->id()`, y el PIN de 6 dígitos no tiene `throttle` ni límite de intentos.
