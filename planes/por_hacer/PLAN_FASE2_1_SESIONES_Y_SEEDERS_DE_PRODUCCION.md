# PLAN — Fase 2.1: Sesiones en base de datos y seeders condicionados al entorno

> **Origen:** hallazgos F1 y F6 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md` (punto 12 del plan de acción, adelantado)
> **Severidad:** 🔴 F1 crítico · 🟠 F6 alto
> **Tamaño:** 2 migraciones corregidas, 2 migraciones nuevas, 7 seeders, 2 archivos de test nuevos
> **Estado:** ✅ Implementado — 512 tests en verde (`php artisan test`)

Los dos estaban fuera de las fases formales del plan, pero la propia auditoría recomendaba adelantarlos: **son las dos formas de romper producción que quedaban vivas**, y ninguna requiere tocar lógica de negocio.

---

## 1. F1 — `sessions.user_id` es NOT NULL con clave foránea

```php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    // $table->foreignId('user_id')->nullable()->index();   ← comentado
    $table->string('user_id');
    $table->foreign('user_id')->references('id')->on('users');
```

`DatabaseSessionHandler::addUserInformation()` escribe `user_id => auth()->id()`, que es **null para cualquier visitante anónimo**.

**Escenario:** con `SESSION_DRIVER=database` —que es justo lo que trae `.env.example`— la primera petición que persiste sesión (cargar `/auth/login` y generar el token CSRF) no llega a escribir la fila. Sin sesión no hay token CSRF, y sin token CSRF **nadie puede iniciar sesión**. Hoy funciona sólo porque el `.env` local usa `SESSION_DRIVER=file`.

### 1.1 El fallo era aún más silencioso de lo que decía la auditoría

La auditoría anticipaba un `SQLSTATE[23000] Column 'user_id' cannot be null`. Al escribir el test resultó que **no hay excepción**: `DatabaseSessionHandler::performInsert()` captura la `QueryException` y devuelve null. La sesión sencillamente no se guarda, sin un solo registro en el log. Un login roto sin ninguna pista de por qué.

Por eso el test se escribió **antes** del arreglo, para confirmar el modo de fallo real:

```
FAIL  a guest session can be persisted with the database driver
Expecting null not to be null
```

### 1.2 Solución

- **Las dos migraciones originales** (`database/migrations/` y `database/migrations/tenant/`) pasan a `$table->string('user_id')->nullable()->index()`, sin clave foránea. Las bases nuevas —y cada tenant que se cree a partir de ahora— nacen correctas.
- **Dos migraciones correctivas nuevas**, una por cada ruta de migración, para las bases que ya existen: eliminan la FK y hacen la columna nullable.

Las correctivas son defensivas: comprueban que la tabla exista, y consultan `Schema::getForeignKeys()` antes de intentar eliminar la clave foránea, así que no fallan sobre una base que ya esté bien. En SQLite salen de inmediato: ese motor no admite eliminar una FK sobre una tabla existente, y las bases SQLite del proyecto (la suite de tests) se crean siempre desde cero con la migración original ya corregida.

El `down()` está deliberadamente vacío, con el motivo escrito en el propio archivo: revertir devolvería la columna al estado que rompe el login, y además fallaría en cuanto exista una sola sesión anónima con `user_id` nulo.

---

## 2. F6 — Seeders de demostración sin condicionar al entorno

`DatabaseSeeder::run()` invocaba los siete seeders sin ninguna guarda, y `RootUserSeeder` hace `updateOrCreate` de `root@owomarket.local` con `USER_PASSWORD_DEV` (`Test_12345678` en el `.env.example`).

**Escenario:** un `php artisan db:seed --force` en producción crea el superadmin con contraseña conocida, ocho dueños de tienda de mentira y el catálogo de prueba. Y el `updateOrCreate` **resetea la contraseña del root si ya existía**.

### 2.1 Solución: separar datos maestros de datos de mentira

`ProductionSeeder` nuevo, con lo único que un despliegue real necesita:

| Seeder | Por qué es de producción |
| :--- | :--- |
| `CentralMasterCatalogSeeder` | Marcas y categorías maestras del marketplace. Datos reales, idempotentes. |
| `ExchangeRateSeeder` | Tasa USD → VES inicial. **Desde la Fase 1.4 su ausencia deja `/api/exchange-rate/convert` devolviendo 404**, así que dejó de ser opcional. |

`DatabaseSeeder` llama primero a `ProductionSeeder` y después, **sólo en `local` y `testing`**, a los cinco de demostración. Fuera de esos entornos avisa por consola y se detiene, en vez de fallar en silencio.

### 2.2 La guarda va también dentro de cada seeder

Condicionar sólo `DatabaseSeeder` no bastaba: los seeders de demo se pueden invocar sueltos con `db:seed --class=RootUserSeeder`, y el comando `tenants:seed-domains` de `routes/console.php` llama directamente a `TenantDomainSeeder`, saltándose la guarda del orquestador.

Trait `Database\Seeders\Concerns\RunsOnlyInDevelopment` con un único método, `shouldSkipOutsideDevelopment()`, que avisa y devuelve `true` fuera de `local`/`testing`. Lo usan los cinco seeders de demostración como primera línea de su `run()`.

---

## 3. Archivos tocados

**Migraciones:**
- `database/migrations/0001_01_01_000000_create_users_table.php` (corregida)
- `database/migrations/tenant/0001_01_01_000000_create_users_table.php` (corregida)
- `database/migrations/2026_08_21_110000_make_sessions_user_id_nullable.php` (**nueva**)
- `database/migrations/tenant/2026_08_19_000009_make_sessions_user_id_nullable.php` (**nueva**)

**Seeders:**
- `database/seeders/ProductionSeeder.php` (**nuevo**)
- `database/seeders/Concerns/RunsOnlyInDevelopment.php` (**nuevo**)
- `database/seeders/DatabaseSeeder.php` (reescrito)
- `RootUserSeeder`, `TenantDomainSeeder`, `TenantDefaultUsersSeeder`, `TenantDemoDataSeeder`, `CentralCustomerDemoSeeder` (guarda)

**Tests (ambos nuevos):**
- `tests/Feature/Session/DatabaseSessionDriverTest.php`
- `tests/Feature/Seeders/DemoSeedersEnvironmentGuardTest.php`

Los tests de la guarda invocan los seeders directamente con `app(...)->setContainer(app())->run()` en lugar de `$this->seed()`: en entorno `production`, `db:seed` pide confirmación interactiva y el test nunca llegaría al seeder.

---

## 4. Checklist de cierre

- [x] `php artisan test` → 512 pasan (3.042 aserciones)
- [x] `./vendor/bin/pint` sobre los archivos tocados
- [ ] `git add` + commit
- [ ] `git push origin <rama_actual>`
- [ ] Actualizar el bloque de estado de `AUDITORIA_BUGS_2026_08_21.md`
- [ ] Mover este documento a `planes/implementados/`

---

## 5. Verificación manual

**Debe seguir funcionando:**
1. `php artisan db:seed` en local → sigue creando root, tiendas demo y catálogo, igual que antes.
2. `php artisan tenants:seed-domains` en local → sigue sembrando los dominios de prueba.
3. El login con `SESSION_DRIVER=file` (lo que usa el `.env` actual).

**Debe cambiar:**
4. `SESSION_DRIVER=database` → **el login funciona**, que es lo que no ocurría.
5. `php artisan db:seed --force` con `APP_ENV=production` → sólo carga marcas, categorías y tasa de cambio; avisa de que omite los de demostración y **no crea `root@owomarket.local`**.
6. `php artisan db:seed --class=RootUserSeeder --force` en producción → no hace nada.

---

## 6. Riesgo

**Bajo,** pero con dos avisos operativos.

1. **Las migraciones correctivas hay que ejecutarlas en las dos rutas.** La central con `php artisan migrate`, y **la de tenants con `php artisan tenants:migrate`**. Si se olvida la segunda, las tiendas existentes siguen con el esquema roto y el problema sólo aparecerá cuando se cambie el driver de sesión.
2. **Antes de pasar a `SESSION_DRIVER=database` conviene vaciar la tabla.** Las sesiones que hubiera son de un esquema anterior:
   ```sql
   SELECT COUNT(*) FROM sessions;
   -- si procede: TRUNCATE TABLE sessions;
   ```
3. **`ProductionSeeder` es seguro de ejecutar en producción, pero no es automático.** Nadie lo invoca en el despliegue: hay que llamarlo a mano con `php artisan db:seed --class=ProductionSeeder --force`.
4. **`.env.example` sigue diciendo `SESSION_DRIVER=database`.** Ahora eso ya es correcto en vez de una trampa, pero conviene decidir explícitamente qué driver usa producción.

---

## 7. Trabajo de seguimiento identificado

1. **Producción se queda sin forma de crear el primer superadmin.** Era `RootUserSeeder`, y ahora está —correctamente— vetado fuera de desarrollo. No rompe los despliegues existentes, que ya tienen su superadmin, pero **una instalación nueva no tiene ningún camino para arrancar**. Hace falta un comando de artisan tipo `admin:create-super` que pida los datos por consola. Deliberadamente **no** se resolvió aquí con una variable de entorno: elegir cómo se bootstrapea la primera identidad de la plataforma es una decisión que merece tomarse a propósito, no de refilón en un arreglo de seeders.
2. **`RootUserSeeder` sigue reseteando la contraseña del root con `updateOrCreate`.** Con la guarda de entorno eso ya sólo afecta a local, donde además es el comportamiento que un desarrollador espera. Se deja como está, pero queda anotado.
3. **Hay un test intermitente en la suite.** `AdminPhaseTwoOperationsTest > super admin can view tenant 360 detail...` falló una vez de cada tres ejecuciones completas con *«The float-string "26e63005-…" is not representable as an int, cast occurred»*, y pasa siempre en aislamiento. Huele a un UUID que en algún punto se castea a int, y a estado que se filtra entre tests. **No lo provoca esta fase** (se reprodujo también antes de tocar nada), pero conviene perseguirlo: un test que falla una de cada tres veces acaba enseñando a ignorar la suite.
4. **Quedan F3 y F5 del bloque de infraestructura:** la cookie de sesión compartida por todos los subdominios (que interactúa con el diseño de SSO y merece una decisión explícita) y las tablas de permisos de Spatie ausentes en las bases de tenant, que hacen reventar cualquier `hasRole()` dentro de una tienda.
