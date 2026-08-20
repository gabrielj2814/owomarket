# 📋 Plan de Trabajo: Corrección del Flujo SSO 1-Click y Redirección de Login en Inquilino

## 🎯 1. Problema Identificado
1. **Redirección a ruta 404 (`/login`) en caso de error o URL por defecto**:
   En el inquilino la ruta del login es `/auth/login`. Al redirigir a `/login`, Laravel responde con un 404 (Not Found).
2. **Redirección a ruta inexistente (`/dashboard`) en el consumo de SSO**:
   En [ConsumeTenantOwnerSsoTokenUseCase.php](file:///c:/laragon/www/owomarket/src/Tenant/Application/UseCase/ConsumeTenantOwnerSsoTokenUseCase.php), el `redirect_to` retornaba `/dashboard` en lugar de la URL del backoffice del inquilino: `/tenant/backoffice/{user_uuid}/dashboard`.
3. **Persistencia del usuario en `auth_users` del tenant**:
   Al iniciar sesión por SSO en el subdominio del inquilino, se debe asegurar que el registro en `auth_users` del tenant esté sincronizado para que `DashboardContext` cargue la información del usuario (`name`, `email`, `avatar`, `role`).

---

## 🛠️ 2. Solución Propuesta

### A. Casos de Uso y Controladores
1. **[ConsumeTenantOwnerSsoTokenUseCase.php](file:///c:/laragon/www/owomarket/src/Tenant/Application/UseCase/ConsumeTenantOwnerSsoTokenUseCase.php)**:
   - Configurar `redirect_to` con la ruta real: `/tenant/backoffice/{user_id}/dashboard`.
   - Sincronizar o crear el registro en la tabla `auth_users` del tenant para que el frontend cargue el perfil de inmediato.
2. **[ConsumeTenantOwnerSsoTokenGETController.php](file:///c:/laragon/www/owomarket/src/Tenant/Infrastructure/Http/Controller/ConsumeTenantOwnerSsoTokenGETController.php)**:
   - Cambiar los redireccionamientos de error de `/login` a `/auth/login`.

### B. Rutas de Convivencia (`routes/tenant.php` y `routes/web.php`)
1. Añadir redirección de cortesía `Route::get('/login', fn () => redirect('/auth/login'))` tanto en el inquilino como en central para evitar 404 si el usuario escribe `/login`.

---

## 🧪 3. Plan de Testing
- Actualizar `tests/Feature/Tenant/TenantOwnerCentralHubTest.php` para validar la redirección precisa a `/tenant/backoffice/{user_uuid}/dashboard`.
- 100% de tests pasando (`php artisan test` y `npm run test:unit`), 0 errores de TypeScript (`npm run types`).
- Commit con Conventional Commits y push a `origin/moduleProduct`.
