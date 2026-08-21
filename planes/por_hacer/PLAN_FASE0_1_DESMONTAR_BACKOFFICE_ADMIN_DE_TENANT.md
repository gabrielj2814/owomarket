# PLAN — Fase 0.1: Desmontar el backoffice de SuperAdmin de los dominios de tenant

> **Origen:** hallazgo A1 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md`
> **Severidad:** 🔴 Crítico — escalada de privilegios explotable por cualquier usuario autenticado
> **Tamaño:** 1 archivo, 1 línea
> **Estado:** ⬜ Pendiente de aprobación

---

## 1. Problema

El archivo de rutas del backoffice central se carga **dos veces**:

```php
// routes/web.php:23 — dentro de Route::domain($central_domain), correcto
Route::prefix('admin')->group(callback: base_path('src/Admin/Infrastructure/Http/Routes/web.php'));

// routes/tenant.php:31 — dentro del grupo de tenancy, SIN restricción de dominio
Route::prefix('admin')->group(callback: base_path('src/Admin/Infrastructure/Http/Routes/web.php'));
```

La primera copia está protegida por `Route::domain()`, así que solo responde en el dominio central. La segunda no tiene más barrera que el `->middleware('auth')` que cada ruta declara individualmente, y como **no existe ningún middleware de rol registrado** en `bootstrap/app.php`, ese `auth` solo comprueba "hay alguien logueado", no "es superadmin".

### Verificación realizada

Se leyeron las 113 líneas de `src/Admin/Infrastructure/Http/Routes/web.php`. Las 40+ rutas son exclusivamente del panel central:

- Gestión de administradores (`/backoffice/{uuid}/module`, crear/editar/eliminar admins)
- Finanzas y payouts (`/api/payouts/{id}/approve`, `/reject`)
- Mesa central de soporte
- Directorio central de clientes
- Monitor global de órdenes y disputas
- Expediente 360° de tenants y **emisión de tokens SSO de impersonación** (`/api/tenants/{id}/sso-token`)
- Catálogo maestro (marcas y categorías)
- Moderación de productos del marketplace
- CMS de banners de la home central
- Planes de suscripción B2B
- **Seguridad y roles RBAC** (`/api/security/staff/{userId}/roles`)
- Pista de auditoría

**Ninguna de ellas tiene sentido en el dominio de una tienda.** No hay dependencia funcional del lado tenant sobre este montaje.

### Escenario de explotación

1. Un usuario cualquiera se registra como cliente en `tienda-a.owomarket.com` e inicia sesión.
2. Hace `POST https://tienda-a.owomarket.com/admin/api/security/staff/{su_propio_uuid}/roles` con `{"roles":["super-admin"]}`.
3. `AssignUserRolesUseCase.php:19-31` ejecuta `$user->syncRoles($roles)` sin comprobar ningún permiso.
4. Es superadministrador de toda la plataforma.

Variantes con el mismo vector: aprobar retiros de dinero (`/api/payouts/{id}/approve`), generar tokens SSO para entrar en cualquier tienda (`/api/tenants/{id}/sso-token`), leer el directorio completo de clientes o la pista de auditoría.

---

## 2. Solución

Eliminar la línea 31 de `routes/tenant.php`. El backoffice central queda registrado únicamente dentro del grupo `Route::domain(central_domain)` de `routes/web.php:23`.

### Cambio

**Archivo:** `routes/tenant.php`

```diff
     Route::prefix('auth')->group(callback: base_path('src/Authentication/Infrastructure/Http/Routes/tenant.php'));
-    Route::prefix('admin')->group(callback: base_path('src/Admin/Infrastructure/Http/Routes/web.php'));
     Route::prefix('tenant')->group(callback: base_path('src/Tenant/Infrastructure/Http/Routes/tenant.php'));
```

---

## 3. Efecto secundario positivo: se arregla la colisión de nombres de ruta

Al registrarse el mismo archivo dos veces, cada `->name()` se registraba dos veces. Laravel no lanza excepción: sobrescribe silenciosamente el `nameList` y **gana el último registro**.

Con el doble montaje, `route('central.backoffice.web.admin.dashboard')` generaba una URL **sin dominio central**, por lo que desde una tienda los enlaces del panel apuntaban al host del tenant. Al eliminar el duplicado, todos los `route('central.backoffice.web.admin.*')` vuelven a resolver contra el dominio central.

---

## 4. Tareas

- [ ] Eliminar la línea 31 de `routes/tenant.php`
- [ ] Ejecutar `php artisan route:clear` y `php artisan config:clear`
- [ ] Verificar con `php artisan route:list --path=admin` que las rutas `admin/*` aparecen una sola vez y con el dominio central asignado
- [ ] Ejecutar `php artisan test` — debe pasar al 100%
- [ ] Ejecutar `npm run types` — 0 errores
- [ ] Commit: `fix(routes): eliminar montaje duplicado del backoffice admin en dominios de tenant`
- [ ] `git push origin <rama_actual>`
- [ ] Mover este documento a `planes/implementados/`

---

## 5. Verificación manual sugerida

1. **Debe seguir funcionando:** entrar al panel de superadmin en el dominio central (`http://localhost/admin/backoffice/{uuid}/dashboard`) y navegar por payouts, roles, moderación y audit logs.
2. **Debe dejar de responder:** `http://tienda1.localhost/admin/backoffice/{uuid}/dashboard` → 404.
3. **Debe seguir funcionando:** el storefront de la tienda y el backoffice del inquilino (`http://tienda1.localhost/product`, `/order`, `/customer`, etc.), que se registran en las líneas 32-46 de `routes/tenant.php` y no se tocan.

---

## 6. Riesgo

**Bajo.** El cambio solo retira rutas; no modifica controladores, casos de uso ni modelos. El único riesgo sería que alguna vista del tenant enlazara a una ruta `admin/*` — se verificó que no es el caso, ya que el `SidebarDashboardComponent` del panel central se renderiza únicamente en páginas servidas desde el dominio central.

---

## 7. Qué NO resuelve este plan

Este cambio cierra el vector de acceso desde dominios de tenant, pero **el `auth` a secas sigue sin comprobar rol**. En el dominio central, cualquier usuario autenticado (por ejemplo un `tenant_owner` que hizo login normal) todavía puede llamar a `POST /admin/api/security/staff/{uuid}/roles`.

Eso se resuelve en la **Fase 0.2** (crear los middlewares de rol que `bootstrap/app.php` importa pero que no existen en disco) y la **Fase 0.3** (aplicarlos a todas las rutas del backoffice).
