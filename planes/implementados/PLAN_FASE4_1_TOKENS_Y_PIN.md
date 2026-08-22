# PLAN — Fase 4.1: Tokens SSO y PIN de administrador

> **Origen:** hallazgos A7, A8 y C5, más el pendiente P1 (`admin:create-super`)
> **Severidad:** 🟠 los tres hallazgos · P1 bloquea instalaciones nuevas
> **Tamaño:** 1 comando nuevo, 2 casos de uso, 2 controladores, 1 archivo de rutas, 2 archivos de test nuevos
> **Estado:** ✅ Implementado — 553 tests en verde

A7, A8 y C5 son la misma familia: **credenciales de un solo uso que no se comportaban como tales**. Y P1 va en el mismo lote porque también es identidad de administrador.

---

## 1. A7 — PIN de 6 dígitos fuerza-brutable y aplicable a cualquier administrador

Tres problemas encadenados:

1. **El destinatario era el `{user_uuid}` de la URL, no el usuario en sesión.** Un usuario autenticado de bajo privilegio podía disparar `/send-pin` contra el UUID de un admin y luego iterar los PIN posibles contra `/change-password`.
2. **Sin límite de tasa**, ni en la ruta ni global (`bootstrap/app.php` no invoca `throttleApi()`). Un millón de combinaciones se agota dentro de la ventana de 15 minutos.
3. **Un fallo no invalidaba nada ni incrementaba ningún contador**, así que se podía probar sobre el mismo PIN indefinidamente.

### Solución

- Los dos controladores usan `auth()->id()`: **sólo puedes pedir un PIN para ti y cambiar tu propia contraseña.**
- `->middleware('throttle:5,15')` en ambas rutas.
- A los **3 fallos el PIN se quema** y hay que pedir uno nuevo.

El contador vive en caché y no en una columna nueva: el PIN caduca a los 15 minutos, así que el contador tampoco tiene por qué sobrevivir más. Se llegó a escribir la migración con `pin_attempts` y se descartó — habría obligado a atravesar la entidad `Admin`, su `reconstitute` y el repositorio para algo que expira solo.

---

## 2. A8 — El token SSO no se ataba al destino

`ValidateAndConsumeSsoTokenUseCase` **recibía `$currentDomain` y no lo usaba nunca**, y el `target_domain` que se persiste al generar el token jamás se comparaba. El de tienda hacía lo propio: buscaba sólo por `token`, sin `where('tenant_id', tenant()->id)`, y encima forzaba `type = 'owner'`.

**Escenario:** el dueño de la tienda A pide un token legítimo para su tienda y abre `https://tiendaB.owomarket.com/auth/sso-consume?token=…`. El token es válido → se le crea un `User` con `type = 'owner'` en la base de la tienda B y queda logueado como propietario de una tienda ajena. **Rotura completa del aislamiento multi-tenant.**

### Solución

- Cliente central: se compara `target_domain` con el host actual usando `hash_equals`.
- Dueño de tienda: la consulta filtra por `tenant_id` de la tienda actual, y **se conserva el tipo real del usuario** en vez de forzar `owner`.

Las dos columnas ya existían en sus tablas desde el principio; sólo faltaba mirarlas.

---

## 3. C5 — Consumo de tokens sin atomicidad

Leer el token, comprobar `used_at` y escribirlo eran **tres sentencias sueltas**, sin transacción ni `UPDATE … WHERE used_at IS NULL`. Dos peticiones simultáneas con el mismo enlace pasaban ambas la comprobación. (El archivo del cliente central incluso importaba `DB` sin usarlo.)

### Solución

La comprobación y el consumo son **la misma sentencia**, y se mira el número de filas afectadas:

```php
$consumed = CentralCustomerSsoToken::query()
    ->where('token', $token)
    ->whereNull('used_at')
    ->where('expires_at', '>', now())
    ->update(['used_at' => now()]);

if ($consumed === 0) { throw new Exception('… inválido, ya utilizado o expirado', 410); }
```

Es el mismo patrón que `CouponRedeemer` (Fase 3.1) y `StockReserver` (Fase 1.3).

> Se usa el query builder **del modelo**, no `DB::table()`, para heredar su conexión: en producción la central, en tests la de la suite. El primer intento con `DB::table('central_customer_sso_tokens')` falló porque la tabla real se llama `central_sso_tokens`.

---

## 4. P1 — `admin:create-super`

La Fase 2.1 vetó `RootUserSeeder` fuera de local y testing, y con eso **una instalación nueva se quedaba sin ningún camino** para crear el primer superadministrador.

### Solución

Comando interactivo, con tres decisiones deliberadas:

- **La contraseña se pide en modo oculto y no se acepta por argumento.** Un `--password=` acabaría en el historial del shell y en los logs de despliegue.
- Se valida con `PasswordValidator`, el mismo del resto del sistema.
- **Se niega a sobrescribir un usuario existente.** Resetear la contraseña de otra persona desde la consola era justo lo que hacía mal el seeder con su `updateOrCreate`.

```bash
php artisan admin:create-super
```

---

## 5. Archivos tocados

- `src/Admin/Infrastructure/Console/Commands/CreateSuperAdminCommand.php` (**nuevo**)
- `src/Admin/Application/UseCase/ChangePasswordWithPinUseCase.php`
- `src/Admin/Infrastructure/Http/Controller/ChangePasswordWithPinPUTController.php`, `SendSecurityPinPOSTController.php`
- `src/Admin/Infrastructure/Http/Routes/web.php`
- `src/CentralCustomer/Application/UseCases/ValidateAndConsumeSsoTokenUseCase.php`
- `src/Tenant/Application/UseCase/ConsumeTenantOwnerSsoTokenUseCase.php`
- `app/Providers/AdminServiceProvider.php`
- `tests/Feature/CentralCustomer/SsoTokenSecurityTest.php` (**nuevo**, 4 casos)
- `tests/Feature/Admin/CreateSuperAdminCommandTest.php` (**nuevo**, 4 casos)

---

## 6. Checklist de cierre

- [x] `php artisan test` → 553 pasan (3.254 aserciones)
- [x] `./vendor/bin/pint` sobre los archivos tocados
- [x] `git add` + commit y push
- [x] Actualizar `AUDITORIA_BUGS_2026_08_21.md`
- [ ] Probar el cambio de contraseña con PIN en el navegador — ⚠️ pendiente

---

## 7. Riesgo

**Medio.**

1. **El PIN ya no sirve para cambiar la contraseña de otro usuario.** Si alguna pantalla del backoffice permitía a un superadmin resetear la contraseña de otro por esta vía, deja de funcionar. Era el hallazgo, pero conviene comprobar que no había un uso legítimo apoyado en ello.
2. **Los enlaces SSO ya emitidos dejan de valer fuera de su dominio.** Es la corrección de A8; si algún flujo dependía de redimir un token en otro dominio, se rompe a propósito.
3. **`throttle:5,15` cuenta por IP.** Varios administradores tras la misma IP corporativa comparten el cupo. Cinco intentos por cuarto de hora es holgado para un uso legítimo, pero es un cambio de comportamiento.
4. **El contador de intentos usa la caché.** Con `CACHE_STORE=array` (el de los tests) no persiste entre peticiones; en producción hace falta un store real. Con `file` —el valor por defecto— funciona.

---

## 8. Trabajo de seguimiento identificado

1. **A9 sigue parcial y es del mismo flujo:** la impersonación de tenant no escribe en `CentralAuditLog` y su URL apunta a `/auth/sso` cuando la ruta real es `/auth/sso-consume`, así que el botón de «acceso directo» del expediente 360° da 404.
2. **N18 sigue abierto:** esta fase pone `throttle` en dos rutas, pero **el resto de la aplicación sigue sin ningún límite de tasa**. El login, el registro y el consumo de SSO son los siguientes candidatos obvios.
3. **El comando no asigna roles de Spatie.** Crea el usuario con `type = 'super_admin'`, que es lo que comprueban los middlewares, pero si el RBAC llega a exigir un rol nominal habrá que ampliarlo.
