# Auditoría de paneles — `pages/admin/**` y `pages/tenant/**`

> ## 📌 ESTADO — 22/08/2026
>
> **Primera pasada, centrada en AUTORIZACIÓN.** Es donde este proyecto ha fallado
> repetidamente, así que se empezó por ahí y no por el contenido de las páginas.
>
> **4 hallazgos abiertos: P0 🔴 · P1 🔴 · P2 🟠 · P3 🟠.**
> **Los cuatro están demostrados**, no deducidos: tres con tests que se ejecutaron y P2
> además contra la aplicación real corriendo en Laragon.
>
> **P0 permite tomar el control de cualquier tienda de la plataforma.** Es lo primero que
> hay que cerrar.
>
> **El árbol tiene 7 tests en rojo a propósito**, escritos como evidencia. Ninguno es una
> regresión: los siete documentan agujeros que ya existían.
>
> ### Leyenda
> 🔴 crítico · 🟠 alto · 🟡 medio · ✅ cerrado · ⬜ abierto
>
> ### De dónde viene
> Del bloque «Lo que queda por auditar» de
> [AUDITORIA_BUGS_2026_08_21.md](AUDITORIA_BUGS_2026_08_21.md), que dejaba sin revisar
> `resources/js/pages/admin/**` (21 páginas), `resources/js/pages/tenant/**` (24 páginas,
> ~24.000 líneas entre ambas) y la suite de tests.

---

**Fecha:** 22 de agosto de 2026
**Alcance de esta pasada:** las rutas que sirven esas páginas y quién puede llamarlas.
**Método:** lectura del código y verificación ejecutando tests. P2 se comprobó además
contra la aplicación real; el dato de desarrollo que se modificó para probarlo se restauró.

---

## Resumen ejecutivo

Las 45 páginas están razonablemente protegidas por rol —`staff:permiso` y `super_admin` en
casi todos los grupos—, resultado de las fases 0.3 y 4. **El problema no es que falte el
portero: es que hay dos puertas al mismo sitio y sólo se puso portero en una.**

Dos patrones distintos producen lo mismo:

1. **Rutas duplicadas** (P0). Las acciones de gobernanza de tiendas existen dos veces: una
   bajo `/admin/...` con `staff` o `super_admin`, y otra bajo `/tenant/admin/...` con sólo
   `auth`. La segunda esquiva la primera. Es la misma patología que el registro doble de
   `SupportTicket` cerrado el 22/08, pero aquí la consecuencia no es un nombre de ruta
   pisado, es un salto de privilegios.

2. **El arreglo llegó a la API y no a la página** (P1, P2). Los hallazgos A2 y A3
   corrigieron la verificación de identidad en los endpoints de API. Los controladores de
   página de Inertia, que sirven los mismos datos, se quedaron sin ella.

| # | Qué | Severidad | Demostrado |
| :--- | :--- | :--- | :--- |
| **P0** | Cualquiera con sesión central puede entrar como dueño de **cualquier tienda**, y suspenderla, aprobarla o rechazarla | 🔴 | ✅ test ejecutado |
| **P1** | Un propietario puede leer la billetera, facturación y panel de otro | 🔴 | ✅ test ejecutado |
| **P2** | El perfil de administrador se puede leer y **modificar** siendo otro | 🟠 | ✅ contra la app real |
| **P3** | Cuatro modelos siguen fijando la conexión `central` a mano | 🟠 | ✅ rompe los tests que lo tocan |

---

## P0. 🔴 Tomar el control de cualquier tienda de la plataforma

> **Estado:** ⬜ ABIERTO — **demostrado con test**

**Dónde:** [`src/Tenant/Infrastructure/Http/Routes/web.php:20-42`](../../src/Tenant/Infrastructure/Http/Routes/web.php#L20)

Todo el módulo de gobernanza de tiendas está declarado **dos veces**. Comparación real,
sacada de `route:list`:

| Acción | Ruta protegida | Duplicado |
| :--- | :--- | :--- |
| Emitir token SSO de una tienda | `POST /admin/api/tenants/{id}/sso-token` → **`super_admin`** | `POST /tenant/admin/api/tenants/{id}/sso-token` → **sólo `auth`** |
| Datos 360 de una tienda | `GET /admin/api/tenants/{id}/360-data` → **`staff:manage_tenants`** | `GET /tenant/admin/api/tenants/{id}/360-data` → **sólo `auth`** |
| Cambiar estado de gobernanza | `PATCH /admin/api/tenants/{id}/governance-status` → **`staff`** | `PATCH /tenant/admin/api/tenants/{id}/governance-status` → **sólo `auth`** |
| Suspender / activar / desactivar | — | `PATCH /tenant/backoffice/{id}/{suspended\|active\|inactive}` → **sólo `auth`** |
| Aprobar / rechazar una solicitud | — | `PATCH /tenant/backoffice/{id}/{approved\|rejected}` → **sólo `auth`** |

**Que la versión protegida exija `super_admin` demuestra que la intención era cerrarla.**
El duplicado la deja abierta.

### La cadena completa

Ninguna capa por debajo compensa la falta de rol:

1. `AdminGenerateTenantSsoTokenPOSTController:22` toma `auth()->user()` — sólo para saber
   *quién* pide, nunca para comprobar *si puede*.
2. `AdminImpersonateTenantUseCase` verifica que la tienda y el usuario existan
   (líneas 29 y 34) y **no comprueba ningún rol**.
3. `ConsumeTenantOwnerSsoTokenGETController:31` hace `Auth::login($user, true)` — sesión
   real, con «recuérdame».

### Demostrado

El test `un propietario no puede emitir un token SSO para la tienda de otro` devuelve
**200** con un enlace listo para abrir:

```json
{
  "sso_url": "http://tienda-beto-sso.owomarket.local/auth/sso-consume?token=kEHE5zWUqmcg…",
  "expires_at": "2026-08-22T20:35:24+00:00",
  "domain": "tienda-beto-sso.owomarket.local"
}
```

Ana —propietaria de su propia tienda, con sesión legítima en el hub central— abre esa URL
y queda dentro del backoffice de Beto **como Beto**: catálogo, pedidos, clientes,
facturación. Y el test hermano confirma que la ruta `/admin/...` **sí** la rechaza.

El test `un propietario no puede suspender la tienda de otro` también devuelve 200.

### Arreglo propuesto

Borrar los duplicados de `src/Tenant/.../web.php` y dejar sólo las versiones de
`src/Admin/.../web.php`, que ya están protegidas. Las cinco rutas `PATCH
/tenant/backoffice/{id}/…` no tienen gemela protegida: necesitan
`staff:manage_tenants` —o `super_admin` para aprobar y rechazar— antes de moverse.

Conviene además un test que recorra `route:list` y falle si alguna ruta de gobernanza queda
sin middleware de rol. Es lo que habría detectado esto sin que nadie lo buscara.

---

## P1. 🔴 Un propietario puede leer el dinero de otro

> **Estado:** ⬜ ABIERTO — **demostrado con test**

**Dónde:**
[`src/Tenant/.../web.php:48-51`](../../src/Tenant/Infrastructure/Http/Routes/web.php#L48) y
[`ViewTenantOwnerWalletGETController.php:19-21`](../../src/Tenant/Infrastructure/Http/Controller/ViewTenantOwnerWalletGETController.php#L19).

Las cuatro páginas del propietario llevan **sólo `auth`**, y el controlador toma el uuid de
la URL sin compararlo con la sesión:

```php
public function __invoke(Request $request, string $user_uuid): Response
{
    $summary = $this->walletSummaryUseCase->execute($user_uuid);   // ← el de la URL
```

**Escenario:** Ana inicia sesión con su cuenta y cambia el uuid de la barra de direcciones
por el de Beto.

**Demostrado.** Devuelve **200** con el dinero de Beto:

```
ventas_brutas    => 1000.0
comisiones       => 100.0
saldo_disponible => 900.0
```

Más el historial de liquidaciones con su `payment_reference`
([`GetTenantOwnerWalletSummaryUseCase.php:60-84`](../../src/Tenant/Application/UseCase/GetTenantOwnerWalletSummaryUseCase.php#L60)).
Lo mismo con `/billing` y `/dashboard`.

### Por qué se escapó

Esto ya se arregló, en el otro lado. El propio comentario del bloque de APIs lo dice:

> *«Ahora exigen sesión de propietario, y cada controlador deriva la identidad de
> `auth()->id()`»* — hallazgo A2

`/owner/api/wallet-summary` resuelve con `auth()->id()`. El portal del cliente tiene su
`denyIfNotOwnProfile()` del A3. **La corrección alcanzó la capa de API y se saltó los
controladores de página**, que sirven los mismos datos por otro camino.

---

## P2. 🟠 El perfil de administrador se puede leer y modificar siendo otro

> **Estado:** ⬜ ABIERTO — **demostrado contra la aplicación real**

**Dónde:** [`src/Admin/Infrastructure/Http/Routes/web.php:53-69`](../../src/Admin/Infrastructure/Http/Routes/web.php#L53)

El bloque se titula *«Perfil propio del administrador»*, pero nada obliga a que sea el
propio:

| Ruta | ¿Comprueba de quién es? |
| :--- | :--- |
| `GET /admin/backoffice/{user_uuid}/profile` | ❌ No |
| `PUT /admin/backoffice/{user_uuid}/profile` | ❌ No |
| `POST /admin/backoffice/{user_uuid}/profile/avatar` | ❌ No |
| `PUT …/profile/change-password` | ✅ **Sí** — usa `auth()->id()` (hallazgo A7) |

La última la arregló A7. **Las otras tres del mismo bloque se quedaron atrás.**

### Demostrado contra la aplicación real

Sesión iniciada en el hub central como `chivostore.owner@owomarket.local`, un
`tenant_owner` corriente:

```
GET /admin/backoffice/{otro-uuid}/profile   →  HTTP 200
{
  "name": "Nazuna Nanakusa",
  "email": "tecno-isekaic.owner@owomarket.local",
  "type": "tenant_owner",
  "is_active": true,
  "has_pin": false
}
```

Y la escritura:

```
nombre ANTES  : Nazuna Nanakusa
PUT ajeno     : HTTP 200
nombre DESPUÉS: CAMBIADO POR OTRO USUARIO
```

> El dato de desarrollo se restauró inmediatamente a `Nazuna Nanakusa` con `phone = NULL`.

No es una toma de control —la contraseña sí está protegida— pero sí lectura de datos
personales y alteración de la identidad de otro usuario.

---

## P3. 🟠 Cuatro modelos siguen fijando la conexión `central` a mano

> **Estado:** ⬜ ABIERTO — demostrado (es lo que hace intestables varias rutas)

Es la **tarea 4 de la auditoría anterior, que quedó incompleta**. Aquélla alineó
`config/tenancy.php` y el `getConnectionName()` de `Tenant`, pero no vio estos cuatro, que
usan la forma más fuerte —la propiedad `$connection`— y por eso no aparecieron en la
búsqueda que se hizo entonces:

```
src/Admin/Infrastructure/Eloquent/Models/User.php:20            protected $connection = 'central';
src/Tenant/Infrastructure/Eloquent/Models/TenantSetting.php:12  protected $connection = 'central';
src/Tenant/Infrastructure/Eloquent/Models/TenantUser.php:12     protected $connection = 'central';
src/Tenant/.../Repositories/TenantRepository.php:238            DB::connection('central')
```

**Dos consecuencias, y la segunda es la que importa:**

1. En producción pueden volver a leer de una base distinta que sus 22 hermanos, que es
   justo lo que la tarea 4 pretendía cerrar. Hoy coinciden sólo porque `DB_DATABASE` y
   `CENTRAL_DB_DATABASE` apuntan al mismo sitio.

2. **En tests van a MySQL y revientan**, porque `phpunit.xml` mapea la conexión central a
   sqlite y estos cuatro la ignoran. Ése es el motivo real de que **ninguna prueba cubra
   las rutas de perfil de administrador**: no es que nadie se acordara, es que no se podía.

El segundo punto es el patrón que conviene retener: **un modelo que no se puede instanciar
en el entorno de pruebas se queda sin tests, y lo que no tiene tests es donde se esconden
los hallazgos.** P2 estuvo dos auditorías escondido detrás de esto.

---

## La suite de tests: por qué no vio nada de esto

> Tercer punto del bloque «Lo que queda por auditar». El documento anterior hablaba de
> «396 tests»; **hoy son 636**.

Existe un test que cubre **exactamente** las cuatro rutas de P1
([`TenantOwnerCentralHubTest.php:212-224`](../../tests/Feature/Tenant/TenantOwnerCentralHubTest.php#L212)):

```php
$this->actingAs($user)->get("/tenant/owner/backoffice/{$user->id}/dashboard")->assertStatus(200);
$this->actingAs($user)->get("/tenant/owner/backoffice/{$user->id}/wallet")->assertStatus(200);
$this->actingAs($user)->get("/tenant/owner/backoffice/{$user->id}/catalog")->assertStatus(200);
$this->actingAs($user)->get("/tenant/owner/backoffice/{$user->id}/billing")->assertStatus(200);
```

Usa siempre `{$user->id}` — **el uuid propio**. Nunca prueba el ajeno. Pasa en verde con el
agujero abierto, y su existencia da la falsa sensación de que esas rutas están cubiertas.

No es un descuido de ese archivo. Es la forma de toda la suite:

| Qué se afirma | Cuántas veces |
| :--- | ---: |
| Que algo **funciona** (`assertStatus(200)`, `assertOk`, `assertSuccessful`, 201) | **364** |
| Que algo se **rechaza por rol** (`assertForbidden`, 403) | **13** |
| Que algo se rechaza por falta de sesión (401) | 18 |
| **Archivos que prueban algún rechazo por rol** | **7 de 152** |

Doce a uno. La suite comprueba que el usuario correcto puede entrar; casi nunca que el
incorrecto no puede. Y los bugs de autorización sólo viven en la segunda mitad.

### Qué hacer con esto

No es «añadir más tests». Son dos reglas concretas:

1. **Toda ruta que reciba un identificador por la URL necesita un test con el identificador
   de OTRO.** Dos líneas por ruta, y es lo único que habría detectado P1, P2, A2 y A3 antes
   de llegar a producción.

2. **Un test que recorra `route:list` y falle si una ruta que muta estado queda sin
   middleware de rol.** Es lo que habría detectado P0 sin que nadie lo buscara — y también
   el registro duplicado de `SupportTicket` que se cerró ese mismo día.

---

## Estado del árbol al cerrar esta pasada

```
637 pasan · 7 fallan
```

Los siete en rojo son deliberados y documentan los agujeros de arriba:

| Archivo | Rojos | Qué documentan |
| :--- | ---: | :--- |
| `tests/Feature/Tenant/TenantOwnerCentralHubTest.php` | 3 | P1 — billetera, facturación y panel ajenos devuelven 200 |
| `tests/Feature/Tenant/TenantOwnerCentralHubTest.php` | 2 | P0 — token SSO ajeno y suspensión ajena devuelven 200 |
| `tests/Feature/Admin/AdminProfileOwnershipTest.php` | 2 | P2 — hoy fallan por P3, no por el agujero; el agujero está probado contra la app real |

Hay además un test nuevo **en verde**: `la ruta protegida de SSO sí exige super_admin`. Es
el contraste que demuestra que P0 es un duplicado mal cerrado y no una función que falte.

Se pondrán verdes al cerrar los hallazgos. Ninguno es una regresión.

---

## Lo que queda por auditar

Esta pasada miró **quién puede llamar a qué**. Sin revisar:

- **El contenido de las 45 páginas** (~24.000 líneas): cálculos hechos en el cliente,
  estados de error y de carga, y si alguna consume formas de respuesta que dejaron de
  existir al unificar la paginación (N37).
- **Los casos de uso detrás de los paneles de admin**: moderación de catálogo, planes de
  suscripción y payouts mueven dinero o permisos y no se han leído.
- **El resto de rutas con sólo `auth`**: esta pasada siguió las de gobernanza y perfil.
  `route:list` muestra otras con identificador en la URL que no se han recorrido una a una.
