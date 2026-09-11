# Plan de Ejecución de Migraciones y Seeders de Base de Datos

> **Estado:** ✅ Ejecutado el 11/09/2026. Este documento pasa de plan a **runbook**: describe
> lo que hay que hacer para reconstruir el entorno de desarrollo desde cero, y lo que se
> descubrió al hacerlo por primera vez.
>
> La versión anterior describía una arquitectura que ya no era la del código. Lo corregido va
> señalado.

## Objetivo

Reconstruir el ciclo completo de migraciones y sembrado en el entorno local de **Owomarket**,
dejando coherentes la base Central y las de los inquilinos.

---

## ⚠️ Lo que hay que saber antes de ejecutarlo

### `migrate:fresh` NO borra las bases de los inquilinos

Es lo que más sorprende y lo que hay que hacer a mano. `migrate:fresh` limpia las tablas de la
base **central**, pero cada tienda vive en su propia base (`tenant_{slug}_{uuid}_tenant`) y
esas quedan intactas.

Al resembrar, `TenantDomainSeeder` crea tiendas con **UUID nuevos**, es decir bases nuevas. Las
viejas se quedan huérfanas ocupando disco y confundiendo a cualquiera que mire el MySQL. Hay
que borrarlas en el mismo movimiento:

```bash
for db in $(docker compose exec -T db mysql -uroot -proot -e "SHOW DATABASES;" </dev/null 2>/dev/null | grep "^tenant_"); do
  docker compose exec -T db mysql -uroot -proot -e "DROP DATABASE \`$db\`;" </dev/null
done
```

El `</dev/null` no es adorno: sin él, `docker compose exec` se come la entrada estándar del
bucle y solo se borra la primera base.

### El error 1059 ya no hace falta vigilarlo a mano

MySQL limita los identificadores a 64 caracteres, y Laravel genera nombres de índice a partir
de la tabla y las columnas. Ha roto el proyecto **dos veces**:

| Índice | Longitud |
| :--- | :--- |
| `central_home_banners_position_type_is_active_order_position_index` | 67 |
| `order_delivery_confirmations_declared_delivered_at_released_at_index` | 68 |

Y las dos de la misma forma desagradable: como el DDL de MySQL **no es transaccional**, la
tabla se queda creada y la migración sin registrar, así que el siguiente intento choca con
«Table already exists» y esconde el error real.

Lo peor era que nada lo detectaba: **la suite corre sobre SQLite, que no tiene ese límite**. Una
migración podía pasar los 765 tests y reventar la primera vez que alguien la aplicaba de verdad.

Desde el 11/09/2026 lo cubre `tests/Feature/Seeders/MigrationIdentifierLengthTest.php`, que
recorre las migraciones y falla nombrando el fichero y el índice culpable. El arreglo siempre
es el mismo: `$table->index([...], 'idx_nombre_corto')`.

---

## Arquitectura de base de datos

### Central (`owomarket_db_central`)

- Framework y autenticación central (`users`, `cache`, `jobs`, `personal_access_tokens`).
- Tenancy (`tenants`, `domains`, `tenant_users`).
- Catálogo maestro y proyección del marketplace (`central_brands`, `central_products`).
  **Corrección:** la versión anterior citaba `central_categories`. Esa tabla **no existe**.
- Monetización y entregas (`platform_commissions`, `commission_settlements`,
  `order_delivery_confirmations`).
- Tasas BCV (`exchange_rates`), banners, permisos (`spatie/laravel-permission`).
- Clientes del dominio central (`central_customers`, `central_orders`,
  `customer_return_requests`…).

### Inquilinos (`tenant_{slug}_{uuid}_tenant`)

Catálogo local (`products`, `product_variants`, `product_images`), clientes y direcciones,
pedidos, envíos, pagos y facturación, cupones, carritos y reseñas, y la configuración de
tienda (`tenant_settings`, `tax_rates`, `shipping_zones`, `shipping_rates`).

---

## Los seeders, tal como son de verdad

**Corrección:** la versión anterior listaba los seeders en un orden que no es el del código y
omitía la pieza más importante — la guarda de entorno.

`DatabaseSeeder` hace dos cosas:

1. **`ProductionSeeder`** — datos maestros, reales y necesarios **también en producción**:
   `CentralMasterCatalogSeeder` (marcas centrales) y `ExchangeRateSeeder` (tasa BCV).
2. **Los de demostración, solo en `local` y `testing`:**
   `RootUserSeeder` → `TenantDomainSeeder` → `TenantDefaultUsersSeeder` →
   `TenantDemoDataSeeder` → `CentralCustomerDemoSeeder` → `CentralPaymentDemoSeeder`.

Esa guarda es el **hallazgo F6**: antes los seeders de demo no estaban condicionados, y un
`db:seed --force` en producción creaba el superadmin con contraseña conocida, ocho dueños de
tienda falsos y el catálogo de prueba.

---

## El plan de ejecución

### Fase 1 — Limpieza de caché

```bash
docker compose exec -T app php artisan config:clear
docker compose exec -T app php artisan cache:clear
```

### Fase 2 — Bases huérfanas, migración y sembrado

Primero el bucle de borrado de arriba, y después:

```bash
docker compose exec -T app php artisan migrate:fresh --seed --force
docker compose exec -T app php artisan tenants:migrate --force
```

El orden importa: `tenants:migrate` va **después**, cuando las tiendas ya existen.

### Fase 3 — Verificación

```bash
docker compose exec -T app php artisan migrate:status
docker compose exec -T app php artisan test
docker compose run --rm --entrypoint sh frontend -c "npx tsc --noEmit && npx vitest run"
```

Estado esperado tras un ciclo limpio:

| Tabla | Filas |
| :--- | :--- |
| `tenants` / `domains` / `users` | 9 |
| `central_brands` | 12 |
| `central_products` | **54** |
| `exchange_rates` | 1 |
| `central_customers` | 1 |
| Bases `tenant_*` | 9 |

---

## El problema que apareció al ejecutarlo: el marketplace nacía vacío

Después de un sembrado completo y sin un solo error, `central_products` quedaba en **0**. Nueve
tiendas con seis productos cada una, y el marketplace central vacío — es decir, **el checkout
central, que es el flujo por el que la plataforma cobra, no se podía ni probar**.

La causa: `TenantDemoDataSeeder` creaba los productos sin `is_published_central`. El
`ProductObserver` encola la sincronización en cada guardado, pero
`SyncProductToCentralMarketplaceUseCase` solo proyecta lo publicado, así que los 54 productos
se quedaban en sus tiendas. Ninguna de las dos piezas estaba rota: faltaba el dato que las une.

Corregido en el propio seeder. De paso, la garantía que la demo ya prometía en el texto de
`specifications` pasa a ser el dato estructurado `warranty_days` que el subsistema 4 usará para
calcular retenciones.

**Detalle operativo:** la sincronización es **asíncrona** (hallazgo N25). El seeder termina y
los 54 productos aparecen cuando Horizon vacía la cola, no al instante. Si al terminar el
sembrado `central_products` sale a 0, mira si el worker está vivo antes de buscar el fallo en
otro sitio.
