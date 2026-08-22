# PLAN — Fase 4.2: Envío, impuestos, sesiones y permisos

> **Origen:** hallazgos D5, D6, F3 y F5, más el pendiente P2 (`domains.id`)
> **Severidad:** 🟠 los cuatro hallazgos · P2 provocaba 500 intermitentes
> **Tamaño:** 1 middleware nuevo, 1 modelo nuevo, 1 migración copiada, 4 archivos de código, 3 de configuración, 3 archivos de test
> **Estado:** ✅ Implementado — 564 tests en verde, tres ejecuciones seguidas
> **Cierra los bloques D y F.**

---

## 1. D5 — Tarifas de envío: `free` ignoraba su umbral y `weight_based` cobraba plano

```php
if ($this->type->isFree()) {
    return true;          // ← antes de evaluar minValue/maxValue
}
```

**Escenario:** «Envío gratis a partir de $100» se aplicaba a un pedido de $5 y, al ser la opción más barata, `CalculateShippingOptionsUseCase` la marcaba como recomendada → **todos los envíos salían gratis**.

Y `calculateCost()` no recibía ni el peso ni el valor del pedido, así que una tarifa «$3 por kg» cobraba **$3 por un pedido de 20 kg** en vez de $60.

### Solución

- Se elimina el atajo: una tarifa gratuita tiene exactamente los mismos umbrales que cualquier otra. Lo único que la distingue es que su coste es 0.
- `calculateCost(float $orderValue, float $totalWeight)`: para `weight_based` el coste configurado es el precio **por unidad de peso**; para el resto sigue siendo el importe plano.

---

## 2. D6 — Sin país se sumaban **todas** las tasas de impuesto activas

Cada filtro geográfico se aplicaba **sólo si el parámetro no era null**, así que una petición sin país no filtraba por país y devolvía todas las tasas activas — que el caso de uso suma. Un inquilino con «IVA Venezuela 16%» e «IVA España 21%» devolvía un **37%**.

### Solución

La regla simétrica: **una tasa con el campo geográfico fijado sólo aplica cuando ese campo coincide; una tasa con el campo vacío aplica siempre.** Si no sabemos el destino, sólo pueden aplicar las tasas sin destino.

> Que varias tasas se sumen **no** se toca: hay jurisdicciones donde el impuesto nacional, el estatal y el municipal se acumulan legítimamente. Lo que estaba mal era sumar tasas de países distintos, y eso se corrige acotando cuáles son aplicables, no cambiando la suma.

---

## 3. F3 — Cookie de sesión compartida por todos los subdominios

```php
'domain' => env('SESSION_DOMAIN', '.owomarket.local'),
```

Un comodín para todos los subdominios, con un único nombre de cookie para toda la aplicación. Y `StartSession` (del grupo `web`) corre **antes** de `InitializeTenancyByDomain`, así que el ID de sesión leído es el mismo en todos los dominios: un usuario autenticado en `tienda-a` navegaba a `tienda-b`, el navegador mandaba la misma cookie, y **la sesión se leía de una base de datos y se persistía en otra**.

### Solución

La auditoría pedía una decisión explícita entre cookie por inquilino y sesión compartida deliberada. **Se elige aislar**, y el motivo es que el SSO ya existe precisamente para cruzar de un dominio a otro: el flujo intercambia tokens por tienda y no depende en ningún punto de una cookie compartida. Compartirla no aportaba nada y rompía el aislamiento.

Dos medidas:

1. `SESSION_DOMAIN` se queda **sin valor por defecto** → cookie atada al host exacto.
2. `ScopeSessionCookieToHost`, **antepuesto a toda la pila** de middleware. Tiene que correr antes que `StartSession`, que es quien lee la cookie; después ya sería tarde. No depende de la tenancy —que aún no se ha inicializado— sino del host, que es el mismo discriminante que usa `InitializeTenancyByDomain`.

La segunda es redundante con la primera mientras nadie ponga un `SESSION_DOMAIN` comodín. Está para que, si alguien lo pone, las sesiones sigan sin pisarse.

---

## 4. F5 — Tablas de permisos ausentes en las bases de los inquilinos

`2026_08_21_000826_create_permission_tables.php` vivía sólo en `database/migrations/`. Pero `Src\User\...\User` usa `HasRoles` y no fija conexión, así que en un dominio de tienda resuelve contra la base del inquilino, donde `roles` y `permissions` no existían: cualquier `hasRole()` o `can()` reventaba con «Base table or view not found».

### Solución

La migración se copia a `database/migrations/tenant/`. Cada tienda tiene su propio espacio de roles, que es además lo que hará falta para cerrar N19.

---

## 5. P2 — `domains.id` casteado a int

Diagnosticado tras la Fase 2.1 y arreglado aquí porque **estaba haciendo intermitente la suite** y estorbaba la verificación de todo lo demás.

`domains.id` es un `uuid`, pero el modelo de Stancl usa los valores por defecto de Eloquent (`$incrementing = true`, `$keyType = 'int'`), así que **`$domain->id` devolvía siempre `0`**. Con la mayoría de UUID el fallo era silencioso; cuando el UUID empieza por dígitos seguidos de `e` (~6%) PHP lo lee como notación científica y la petición devuelve 500.

Además, con la clave a 0, `$domain->save()` sobre un dominio existente generaba `WHERE id = 0`: no afectaba a ninguna fila y no avisaba.

### Solución

Modelo `Src\Tenant\...\Domain` que extiende el de Stancl con `$keyType = 'string'` e `$incrementing = false`, registrado en `config/tenancy.php` (`domain_model`, que existe justo para esto).

**Verificación:** la suite completa pasó **tres veces seguidas** tras el cambio. Antes fallaba aproximadamente una de cada tres.

---

## 6. Archivos tocados

- `src/Shipping/Domain/Entities/ShippingRate.php`, `src/Shipping/Application/UseCase/CalculateShippingOptionsUseCase.php`
- `src/Tax/Infrastructure/Eloquent/Repositories/TaxRateRepository.php`, `src/Tax/Application/UseCase/CalculateTaxUseCase.php`
- `app/Http/Middleware/ScopeSessionCookieToHost.php` (**nuevo**), `bootstrap/app.php`, `config/session.php`
- `database/migrations/tenant/2026_08_19_000010_create_permission_tables.php` (**nueva**)
- `src/Tenant/Infrastructure/Eloquent/Models/Domain.php` (**nuevo**), `config/tenancy.php`
- `tests/Unit/Shipping/ShippingRateTest.php` (**nuevo**, 5 casos)
- `tests/Feature/Tenant/DomainKeyTest.php` (**nuevo**, 3 casos)
- `tests/Integration/Tax/TaxRepositoryTest.php` (+3 casos)

---

## 7. Checklist de cierre

- [x] `php artisan test` → 564 pasan (3.271 aserciones), tres ejecuciones seguidas
- [x] `npm run types` → 0 errores
- [x] `./vendor/bin/pint` sobre los archivos tocados
- [x] `git add` + commit y push
- [x] Actualizar `AUDITORIA_BUGS_2026_08_21.md`
- [ ] Probar sesiones entre dominios en el navegador — ⚠️ pendiente

---

## 8. Riesgo

**Medio-alto**, sobre todo por F3.

1. **Todas las sesiones abiertas se invalidan.** La cookie cambia de nombre y de ámbito, así que todo el mundo tiene que volver a iniciar sesión una vez. En desarrollo no importa; en producción es un cierre de sesión masivo.
2. **Si algo dependía de compartir la sesión entre subdominios, deja de funcionar.** El SSO no depende de ello, pero conviene probar el paso de dominio central → tienda en el navegador antes de dar F3 por bueno.
3. **Los envíos gratuitos con umbral dejan de ser gratuitos por debajo de él.** Es la corrección de D5, pero cambia lo que se cobra: conviene revisar las tarifas configuradas antes de desplegar.
4. **Las tarifas por peso pasan a multiplicar.** Una tarifa configurada como «$3» pensando que era plana pero con tipo `weight_based` ahora cobra $3 × kg. Revisar los tipos configurados.
5. **Las tasas de impuesto atadas a un país dejan de aplicar cuando no se envía país.** Si algún checkout no manda el país, el impuesto pasa a ser 0 en vez de la suma de todo. Es lo correcto, pero es un cambio de importe.
6. **`tenants:migrate` hay que ejecutarlo** para que las tiendas existentes reciban las tablas de permisos.

---

## 9. Trabajo de seguimiento identificado

1. **`priority` en las tasas de impuesto sigue ordenando, no seleccionando.** Con el filtro corregido ya no se mezclan países, pero si un día hacen falta reglas excluyentes dentro de un mismo país (una tasa que anule a otra), `priority` es el campo natural y hoy no hace eso.
2. **N19 sigue abierto**, y ahora tiene con qué resolverse: las tablas de permisos ya existen en cada tienda, así que se puede dar a `staff` y `owner` permisos distintos dentro del inquilino.
3. **A9 sigue parcial:** la impersonación no escribe en `CentralAuditLog` y su URL apunta a `/auth/sso` en vez de `/auth/sso-consume`.
4. **El checkout no envía el país al calcular impuestos.** Con D6 corregido, eso ahora significa impuesto 0. Conviene revisar qué le llega a `CalculateTaxUseCase` desde el flujo de compra real.
