# Plan de Ejecución de Migraciones y Seeders de Base de Datos

## Descripción y Contexto
El objetivo es ejecutar el ciclo completo de migraciones y sembrado de datos (seeders) en el entorno local/desarrollo de **Owomarket**, garantizando la correcta creación y sincronización de las bases de datos Central y de los Tenants (multi-inquilino).

## Diagnóstico y Corrección de Error MySQL 1059
Se detectó un error de longitud de identificador en MySQL:
- **Causa**: MySQL / MariaDB limita los nombres de índices e identificadores a 64 caracteres. La migración `2026_08_20_000001_create_central_home_banners_table.php` definía `$table->index(['position_type', 'is_active', 'order_position'])`, lo que generaba automáticamente el nombre `central_home_banners_position_type_is_active_order_position_index` (67 caracteres > 64).
- **Corrección aplicada**: Se especificó un nombre explícito y abreviado para el índice:
  `$table->index(['position_type', 'is_active', 'order_position'], 'idx_banners_pos_active_ord');` (28 caracteres).

## Arquitectura de Base de Datos del Proyecto
1. **Base de Datos Central (`owomarket_dev`)**:
   - Tablas base del framework y autenticación central (`users`, `cache`, `jobs`, `personal_access_tokens`).
   - Tablas de Tenancy (`tenants`, `domains`, `tenant_users`).
   - Catálogo maestro central (`central_categories`, `central_brands`, `tenant_categories`, etc.).
   - Auditoría, monetización, tasas de cambio BCV (`exchange_rates`), banners y permisos (`spatie/laravel-permission`).
   - Clientes del dominio central (`central_customers`, `central_customer_wishlists`, `customer_return_requests`, etc.).

2. **Bases de Datos de Tenants (`tenant_{slug}_{id}_tenant`)**:
   - Categorías, marcas y productos sincronizados/locales (`products`, `product_variants`, `product_images`, etc.).
   - Clientes locales y direcciones (`customers`, `addresses`).
   - Pedidos, envíos, pagos y facturación (`orders`, `order_items`, `shipments`, `payments`, `invoices`, `invoice_items`).
   - Cupones, carritos, listas de deseos y reseñas (`coupons`, `carts`, `wishlists`, `product_reviews`).
   - Configuración de tienda, zonas y tarifas de envío (`tenant_settings`, `tax_rates`, `shipping_zones`, `shipping_rates`).

## Plan de Ejecución

### Fase 1: Limpieza de Caché de Configuración
Asegurar que las variables de entorno (`.env`) y configuraciones de conexiones se carguen de manera fresca:
```bash
php artisan config:clear
php artisan cache:clear
```

### Fase 2: Ejecución de Migraciones y Seeders
- **Ejecución Recomendada**:
  1. `php artisan migrate:fresh --seed`
     - Limpia y migra la base de datos central.
     - Ejecuta `DatabaseSeeder`:
       - `RootUserSeeder`: Superadmin `root@owomarket.local`.
       - `CentralMasterCatalogSeeder`: Categorías y marcas centrales.
       - `ExchangeRateSeeder`: Tasa BCV.
       - `TenantDomainSeeder`: Comercios de prueba y usuarios de tienda.
       - `TenantDefaultUsersSeeder`: Propietarios de tiendas.
       - `TenantDemoDataSeeder`: Sincronización de catálogo, productos con variantes, imágenes, clientes locales, pedidos y facturas.
       - `CentralCustomerDemoSeeder`: Clientes de prueba del dominio central.
  2. `php artisan tenants:migrate` para asegurar que las bases de datos de todos los inquilinos tengan todas sus migraciones al día.

### Fase 3: Validación y Verificación
- Comprobar el estado de las migraciones:
  `php artisan migrate:status`
- Ejecutar la suite de pruebas automatizadas de backend:
  `php artisan test`
- Validar tipos en frontend:
  `npm run types`
