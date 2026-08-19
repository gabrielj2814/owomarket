# Plan: Corrección del Flujo de Login de Cliente en Dominio Central (404 en SSO Consume) [IMPLEMENTADO AL 100%]

## 1. Causa Raíz del Problema
Cuando el usuario iniciaba sesión desde el **Dominio Central** (`http://owomarket.local`), `CustomerAuthContext.tsx` ejecutaba de manera incondicional el paso 3:
`POST /api-tenant/customer/sso/consume`.

La ruta `/api-tenant/*` es exclusiva del contexto de inquilinos (Tenants / subdominios como `tecs.owomarket.local`). En el dominio central `owomarket.local`, esa ruta no existe (retornaba HTTP 404 Not Found), provocando que el frontend atrapara el error e impidiera completar el inicio de sesión del cliente en la Central.

---

## 2. Solución Implementada

1. **Detección de Contexto Central vs Tenant:**
   - Implementado helper `isCentralDomain()` en `CustomerAuthServices.ts`.
2. **Flujo de Login Adaptativo en `CustomerAuthContext.tsx`:**
   - **En Dominio Central (`owomarket.local`):**
     - Autentica con `CustomerAuthServices.loginCentral(payload)`.
     - Establece `customer` y `centralCustomer`, almacena en `localStorage` (`owo_central_customer` y `owo_customer_addresses`).
     - Cierra el modal de autenticación y finaliza el inicio de sesión con éxito inmediato.
   - **En Dominio Tenant (Tienda Inquilina):**
     - Autentica con la Central.
     - Genera token SSO (`generateSsoToken`).
     - Consume token SSO en el tenant (`consumeSsoToken`) con fallback seguro.
3. **Persistencia y Restauración de Sesión en `refreshSession`:**
   - Restaura la sesión desde `localStorage` (`owo_central_customer`).
   - En tiendas tenant, sincroniza con `getTenantSession()`.
4. **Cierre de Sesión Limpio en `logout`:**
   - Limpia `localStorage` (`owo_central_customer` y `owo_customer_addresses`).
   - En tiendas tenant, invoca `CustomerAuthServices.logoutTenant()`.

---

## 3. Resultados de Verificación
- **TypeScript (`npm run types`):** 0 errores.
- **Frontend Tests (`npm run test:unit`):** 14/14 tests pasando al 100%.
