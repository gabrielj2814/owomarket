# 📋 Plan de Trabajo: Procesamiento Conexión Central-Tenant del Token SSO 1-Click

## 🎯 1. Diagnóstico del Comportamiento
Al hacer clic en el botón SSO 1-Click desde `owomarket.local`:
1. El navegador navega a `http://chivostore.owomarket.local/auth/sso-consume?token=...`.
2. El middleware de Tenancy inicializa la base de datos del inquilino `chivostore`.
3. Al intentar buscar el token `TenantOwnerSsoToken` o el `User` central dentro del contexto del inquilino:
   - La tabla `tenant_owner_sso_tokens` reside en la base de datos **Central**.
   - El usuario dueño de la tienda reside en la base de datos **Central**.
   - Al ocurrir un fallo en la consulta entre bases de datos, el controlador capturaba la excepción y redirigía a `/auth/login`.

---

## 🛠️ 2. Solución Propuesta

### A. Consulta Explícita a la Conexión Central
En [ConsumeTenantOwnerSsoTokenUseCase.php](file:///c:/laragon/www/owomarket/src/Tenant/Application/UseCase/ConsumeTenantOwnerSsoTokenUseCase.php):
1. Consultar el token `TenantOwnerSsoToken` y el usuario en la **conexión central** (`central_connection` / `mysql`).
2. Marcar el token como consumido (`used_at = now()`).
3. **Aprovisionar / Sincronizar el usuario en la base de datos local del Inquilino** (`users` y `auth_users`) con rol `owner`.
4. Iniciar la sesión del inquilino con `Auth::login($tenantUser, true)`.
5. Redirigir limpiamente a `/tenant/backoffice/{user_id}/dashboard`.

### B. Validación de Conexión en Modelo `TenantOwnerSsoToken`
Asegurar que [TenantOwnerSsoToken.php](file:///c:/laragon/www/owomarket/app/Models/TenantOwnerSsoToken.php) retorne dinámicamente la conexión central en entorno local/producción y la conexión por defecto en tests.

---

## 🧪 3. Plan de Testing
- Ejecutar `tests/Feature/Tenant/TenantOwnerCentralHubTest.php`.
- Ejecutar suite completa `php artisan test` (456+ tests) y `npm run test:unit`.
- Commit con Conventional Commits y push a `origin/moduleProduct`.
