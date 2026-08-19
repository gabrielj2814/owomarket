# 📋 Plan Maestro: Módulo de Tasa de Cambio (BCV Scraping), Moneda Dual (USD / VES / Cripto) y Liquidación de Comisiones

> **Estado:** Pendiente de Aprobación  
> **Rama:** `moduleProduct` (o nueva rama a definir)  
> **Módulos Afectados:** `ExchangeRate` (Nuevo), `Admin`, `Marketplace`, `Payment`, `Monetization`, `Shared`  
> **Tipo:** `feature` (Nueva funcionalidad transversal de plataforma)

---

## 1. 🔍 Contexto y Justificación del Negocio

En el modelo de negocio de **OwoMarket**:
1. **Fijación de Precios en Dólares (USD):** Los comercios y tiendas (Tenants) fijan los precios de sus productos en **Dólares estadounidenses (USD)** como moneda de cuenta y referencia estable.
2. **Monedas y Métodos de Pago Aceptados por la Plataforma:**
   - **Bolívares (VES):** Pago Móvil, Transferencia Bancaria Nacional (Banesco, Mercantil, BDV, etc.).
   - **Criptomonedas Estables (USD-Pegged):** **USDT** (TRC-20, BEP-20, Polygon) y **USDC** (Polygon, Solana, ERC-20).
3. **Visualización de Precios Dual / Multi-Moneda al Comprador:**
   - En el Storefront y Marketplace Central, el cliente ve el precio base en **USD** (ej: `$ 25.00 USD`), la equivalencia en **Bolívares (VES)** calculada en tiempo real según la tasa oficial del **Banco Central de Venezuela (BCV)** vigente (ej: `Bs. 1,025.50 (Tasa BCV: 41.02)`), y la equivalencia en Cripto Estable (ej: `25.00 USDT`).
4. **Regla de Liquidación de Comisiones de la Plataforma:**
   - **Si el cliente pagó en Bolívares (VES):** La comisión porcentual de la plataforma se liquida y retiene en **Bolívares (VES)** sobre el monto total en VES.
   - **Si el cliente pagó en Cripto Estable (USDT / USDC):** La comisión porcentual de la plataforma se liquida y retiene en **Dólares / Cripto (USD / USDT / USDC)** sobre el monto total en USD.

---

## 2. 🏛️ Arquitectura Hexagonal y DDD del Nuevo Módulo: `ExchangeRate`

Se creará el nuevo Bounded Context en `src/ExchangeRate/`:

```text
src/ExchangeRate/
├── Domain/
│   ├── Entities/
│   │   └── ExchangeRate.php             # Entidad raíz: id, base_currency, target_currency, rate, source, rate_date, is_active, timestamps
│   ├── ValueObjects/
│   │   ├── RateAmount.php               # VO: valor decimal positivo con 4-6 decimales (ej. 41.0245)
│   │   ├── CurrencyCode.php             # VO: USD, VES, EUR, USDT, USDC
│   │   ├── RateSource.php               # VO: 'BCV_SCRAPING', 'MANUAL_ADMIN', 'API_FALLBACK'
│   │   └── RateDate.php                 # VO: Fecha valor oficial publicada por el BCV
│   └── Contracts/
│       ├── ExchangeRateRepositoryInterface.php # Persistencia y consulta de tasas
│       └── BcvScraperInterface.php             # Contrato para scraping/extracción del portal BCV
├── Application/
│   ├── DTOs/
│   │   ├── ExchangeRateResponseDTO.php
│   │   └── CreateManualRateDTO.php
│   └── UseCase/
│       ├── SyncBcvExchangeRateUseCase.php      # Ejecuta scraping, parsea, valida y guarda la tasa BCV activa
│       ├── GetActiveExchangeRateUseCase.php    # Obtiene la tasa activa actual (con caché)
│       ├── CreateManualExchangeRateUseCase.php # Permite al Admin ingresar o forzar una tasa manual
│       ├── ListExchangeRatesHistoryUseCase.php # Historial paginado con filtros de fecha y origen
│       └── ConvertCurrencyAmountUseCase.php    # Conversión de USD a VES / VES a USD aplicando tasa activa
└── Infrastructure/
    ├── Eloquent/
    │   ├── Models/ExchangeRate.php             # Modelo Eloquent central
    │   └── Repositories/EloquentExchangeRateRepository.php
    ├── Scrapers/
    │   └── BcvWebScraper.php                   # Scraper HTTP con fallback a regex/DOM crawler para bcv.org.ve
    ├── Console/
    │   └── Commands/SyncBcvExchangeRateCommand.php # php artisan exchange-rate:sync-bcv
    ├── Http/
    │   ├── Controller/
    │   │   ├── GetActiveRateGETController.php       # API pública/interna para Storefront y Tenants
    │   │   ├── AdminListRatesGETController.php      # Vista y listado en Backoffice Admin
    │   │   ├── AdminSyncBcvPOSTController.php       # Botón "Sincronizar BCV ahora" en Admin
    │   │   └── AdminCreateManualRatePOSTController.php # Formulario de tasa manual de contingencia
    │   └── Routes/
    │       ├── web.php                         # Rutas administrativas en /admin/exchange-rates
    │       └── api.php                         # Rutas API en /api/exchange-rate/current
    └── Providers/
        └── ExchangeRateServiceProvider.php     # Binding de repositorios, scraper y comandos
```

---

## 3. 🕸️ Estrategia de Scraping del BCV (`BcvWebScraper`)

1. **URL Objetivo:** `https://www.bcv.org.ve/`
2. **Estructura HTML Exacta del BCV:**
   ```html
   <div id="dolar" class="col-sm-12 col-xs-12 ">        
       <div class="field-content">
           <div class="row recuadrotsmc">
               <div class="col-sm-6 col-xs-6">
                   <img src="/sites/default/files/dollar-04_2.png" class="icono_bss_blanco1"> 		
                   <span> USD</span>
               </div>
               <div class="col-sm-6 col-xs-6 centrado textp">
                   <strong class="strong-tb">775,33560000</strong>
               </div>
           </div>  
       </div>
   </div>
   ```
3. **Mecanismo de Extracción y Limpieza:**
   - Cliente HTTP de Laravel (`Http::withOptions(['verify' => false, 'timeout' => 15])`) con headers de navegador (`User-Agent: Mozilla/5.0...`).
   - Selector primario (DOM / Regex):
     - Patrón Regex directo y tolerante: `/<div[^>]*id=["']dolar["'][^>]*>.*?<strong[^>]*>([\d\.,\s]+)<\/strong>/si`
     - Limpieza numérica:
       1. Eliminar espacios en blanco y etiquetas internas.
       2. Reemplazar la coma decimal venezolana `,` por punto decimal `.`: `str_replace(',', '.', $match)`.
       3. Validar con `is_numeric()` y convertir a flotante positivo (`floatval`).
   - Fecha Valor oficial: extracción del bloque de fecha en el portal o fallback a la fecha del día actual.
4. **Resiliencia y Fallback:**
   - Si la página del BCV está caída, no responde o cambia su estructura HTML, el scraper lanza una excepción controlada sin romper la plataforma, manteniendo la **última tasa activa** en base de datos.
   - Panel de control de SuperAdmin con botón de **Tasa Manual** para fijar la tasa de emergencia en caso de contingencia técnica.

---

## 4. 🗄️ Esquema de Base de Datos (Migración)

Tabla central: `exchange_rates` (en la base de datos central de OwoMarket):

```sql
CREATE TABLE exchange_rates (
    id CHAR(36) PRIMARY KEY,
    base_currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    target_currency VARCHAR(10) NOT NULL DEFAULT 'VES',
    rate DECIMAL(16, 6) NOT NULL,
    source ENUM('BCV_SCRAPING', 'MANUAL_ADMIN', 'API_FALLBACK') NOT NULL DEFAULT 'BCV_SCRAPING',
    rate_date DATE NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    metadata JSON NULL, -- Guarda HTML snippet, usuario admin que modificó, fecha valor original
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_currency_active (base_currency, target_currency, is_active),
    INDEX idx_rate_date (rate_date)
);
```

---

## 5. 💰 Visualización de Precios Multi-Moneda en Frontend

Se implementará un componente reutilizable: `resources/js/components/ui/CurrencyPriceDisplay.tsx`:

```tsx
<CurrencyPriceDisplay 
    priceUsd={25.00} 
    rateVes={41.02} 
    showCrypto={true} 
    layout="vertical" // o "inline"
/>
```

**Resultado visual:**
- **Principal:** `$ 25.00 USD` / `25.00 USDT`
- **Equivalente en Bolívares:** `Bs. 1,025.50` (badge discreto: `Tasa BCV: Bs. 41.02`)

---

## 6. 📦 Adaptaciones Específicas en el Módulo `Product`

1. **Gestión de Precios en Base USD:**
   - Todo precio de producto se mantiene registrado y persistido en **USD** (`price`, `compare_price`, `cost_price`).
   - Se mantiene la integridad de los datos evitando mutar masivamente la base de datos de productos cada vez que la tasa de cambio fluctúa.
2. **DTOs y Respuestas de Catálogo/Storefront:**
   - Los DTOs de lectura para el frontend (`ProductResponseDTO`, `StorefrontProduct`) incorporan helpers computados:
     - `price_ves`: `$product->price * $bcvRate`
     - `compare_price_ves`: `$product->compare_price * $bcvRate`
     - `formatted_price_usd`: `$ 25.00 USD`
     - `formatted_price_ves`: `Bs. 1,025.50`
     - `crypto_symbol`: `USDT / USDC` (paridad 1:1)
3. **Formularios de Creación / Edición en Tenant Backoffice (`resources/js/pages/tenant/product/`):**
   - Inputs de precio claramente etiquetados en **USD ($)**.
   - Helper interactivo en tiempo real: *"Equivalente aproximado según tasa BCV activa (Bs. 41.02): Bs. X,XXX.XX"*.
4. **Vistas de Storefront y Detalle de Producto:**
   - Integración del componente reactivo `<CurrencyPriceDisplay />` en:
     - `ProductCard.tsx` (catálogo y grillas).
     - `ProductDetailPage.tsx` (vista individual con selector de variantes).
     - `MiniCartDrawer.tsx` y `CartPage.tsx` (subtotales y totales en USD y VES).

---

## 7. 🧾 Adaptaciones Específicas en el Módulo `Billing` (Facturación)

1. **Esquema de Facturas Dual (`invoices` & `Invoice.php`):**
   - Incorporar trazabilidad monetaria en la entidad `Invoice` y la tabla `invoices`:
     - `currency`: Moneda de la factura (`'USD'`, `'VES'`, `'USDT'`, `'USDC'`).
     - `exchange_rate`: Tasa oficial del BCV aplicada al momento exacto de la emisión de la factura (inmutable).
     - `subtotal_ves` / `total_ves`: Montos equivalentes en Bolívares.
     - `subtotal_usd` / `total_usd`: Montos equivalentes en Dólares.
     - `commission_amount`: Monto de la comisión retenida por la plataforma.
     - `commission_currency`: Moneda en la que se cobró la comisión (`'VES'` si fue en Bolívares, `'USD'`/`'USDT'` si fue en Cripto).
2. **Plantilla de Factura PDF (`resources/views/invoices/pdf.blade.php`):**
   - Actualizar el generador de PDF (DomPDF) para reflejar la normativa de facturación multi-moneda:
     - **Si la factura se emite por pago en Bolívares (VES):**
       - Precios unitarios y totales expresados en **Bs.**
       - Leyenda fiscal al pie: *"Operación en moneda nacional. Monto de referencia: $ XX.XX USD a la tasa oficial BCV de Bs. XX.XX/USD del DD/MM/AAAA"*.
     - **Si la factura se emite por pago en Cripto Estable (USDT / USDC) o USD:**
       - Totales expresados en **USD / USDT**.
       - Tabla resumen con la equivalencia en **Bs.** calculada a la tasa oficial BCV de la fecha de emisión.
3. **Generación Automática desde Órdenes (`InvoiceFromOrderService`):**
   - Al facturar una orden pagada, el servicio consulta la tasa BCV activa, congela ese valor histórico en el registro de la factura y calcula la comisión en la moneda de pago correspondiente.

---

## 8. 🛍️ Proceso de Compra (Checkout) y Pantalla de Detalle de Producto

### 8.1 — Pantallas de Detalle de Producto (`CentralProductDetailPage.tsx` y `TenantProductDetailPage.tsx`)
1. **Visualización de Precios Dual / Multi-Moneda:**
   - Precio destacado en **USD ($)** y su paridad **USDT**.
   - Equivalente en **Bolívares (VES)** calculado en tiempo real con la tasa oficial BCV activa:
     - Formato: `Bs. 1,025.50` acompañado del badge informativo `(Tasa BCV: Bs. 41.02)`.
2. **Interactividad con Variantes de Producto:**
   - Al seleccionar una variante (ej: talla, capacidad, color) que modifique el precio base en USD del SKU, el monto equivalente en Bolívares se recalcula de forma reactiva instantánea sin recargar la página.
3. **Disponibilidad por Stock:**
   - Se mantiene el control de existencias tanto en el botón de compra directa como en el de adición al carrito.

### 8.2 — Proceso de Compra en el Storefront del Tenant (`TenantCheckoutPage.tsx`)
1. **Resumen de Totales Duales:**
   - La columna de resumen de orden (Subtotal, Costo de Envío, Impuestos, Descuento por Cupón y Total Final) muestra simultáneamente el monto en **USD** y el monto convertido en **Bolívares (VES)** a la tasa BCV activa.
2. **Selección de Método de Pago y Moneda de Pago:**
   - **Opción A: Pago en Bolívares (VES):**
     - Métodos disponibles: **Pago Móvil** y **Transferencia Bancaria Nacional** (Banesco, Mercantil, BDV, etc.).
     - La interfaz despliega los datos bancarios del comercio (Banco, RIF/Cédula, Teléfono, Cuenta) y el monto exacto a pagar en **Bs.**
     - El cliente ingresa el número de referencia bancaria y la fecha del pago.
   - **Opción B: Pago en Criptomonedas Estables (USDT / USDC):**
     - Métodos disponibles: **USDT** (TRC-20, BEP-20, Polygon) y **USDC** (Polygon, Solana, ERC-20).
     - La interfaz despliega la dirección de billetera (Wallet Address) / código QR del comercio y el monto exacto a transferir en **USDT / USDC** (paridad 1:1 con el total en USD).
     - El cliente ingresa el Hash de la Transacción (TxID).
3. **Congelamiento de la Tasa en la Orden (`Order`):**
   - Al crear el pedido, se guarda la tasa BCV vigente en el campo `exchange_rate` de la orden, garantizando que el monto exigido al cliente no cambie retrospectivamente.

### 8.3 — Proceso de Compra en el Dominio Central (`CentralCheckoutPage.tsx`)
1. **Carrito y Checkout Multi-Tienda:**
   - Permite al comprador pagar pedidos que contengan productos de una o múltiples tiendas del Marketplace Central.
   - Agrupa los subtotales por tienda, calculando el total global en USD y su equivalente en Bolívares a la tasa BCV oficial.
2. **Enrutamiento y Liquidación por Tienda:**
   - El pago se valida y se registran las órdenes correspondientes para cada tenant involucrado, aplicando la tasa de cambio y calculando la comisión en la moneda de pago elegida.

### 8.4 — Pantalla de Confirmación de Pedido (`TenantOrderConfirmationPage.tsx` y `CentralOrderConfirmationPage.tsx`)
- Muestra el comprobante de la orden con:
  - Total pagado en la moneda seleccionada (VES o USDT/USDC).
  - Tasa oficial BCV congelada para la transacción.
  - Datos de transferencia/wallet del comercio.
  - Botón de descarga de la Factura / Comprobante en PDF.

---

## 9. 🧮 Lógica de Liquidación de Comisiones de Plataforma

En la capa de `Monetization` / `Order` / `Payment`:
1. Al confirmarse un pago de orden:
   - **Caso A (Pago en Bolívares - Pago Móvil / Transferencia):**
     - Total de la orden: `Bs. 1,025.50`
     - Comisión de plataforma (ej. 5%): `Bs. 51.275`
     - Moneda de comisión: `VES`
     - Monto neto para el Tenant: `Bs. 974.225`
   - **Caso B (Pago en Cripto Estable - USDT / USDC):**
     - Total de la orden: `$ 25.00 USD` (25.00 USDT)
     - Comisión de plataforma (ej. 5%): `$ 1.25 USD` (1.25 USDT)
     - Moneda de comisión: `USDT` (o `USDC`)
     - Monto neto para el Tenant: `$ 23.75 USDT`

---

## 10. 🖥️ Interfaz de Usuario para SuperAdmin

Vista: `resources/js/pages/admin/exchangeRate/ExchangeRateManagementPage.tsx`
1. **Banner Superior:**
   - Tarjeta con la **Tasa BCV Activa** destacada en grande (`Bs. 41.02`).
   - Origen (`BCV Oficial` o `Manual`).
   - Fecha de última actualización y hora de la próxima sincronización automática.
2. **Acciones Directas:**
   - Botón *"Sincronizar BCV Ahora"* (invoca `POST /admin/exchange-rate/sync-bcv` con spinner y toast reactivo).
   - Botón *"Fijar Tasa Manualmente"* (abre modal para ingresar un valor de contingencia).
3. **Tabla Histórica de Tasas:**
   - Columnas: Fecha, Tasa (USD -> VES), Origen, Estado (Activa / Histórica), Fecha de Registro, Acciones.

---

## 11. ⏰ Automatización y Tareas Programadas (Scheduler)

En `routes/console.php` (Laravel 12):
```php
use Illuminate\Support\Facades\Schedule;

// Sincronización automática de la tasa BCV en horarios bancarios venezolanos (Lunes a Viernes)
Schedule::command('exchange-rate:sync-bcv')
    ->weekdays()
    ->at('09:00')
    ->at('13:00')
    ->at('17:30')
    ->timezone('America/Caracas')
    ->withoutOverlapping();
```

---

## 12. 🧪 Plan de Testing Integral (Backend & Frontend)

### Backend (PHPUnit / Pest):
1. `tests/Unit/ExchangeRate/Domain/RateAmountTest.php`: Valida montos positivos, decimales y rechazo de tasas <= 0.
2. `tests/Unit/ExchangeRate/Application/ConvertCurrencyAmountUseCaseTest.php`: Valida conversiones matemáticas exactas USD -> VES y VES -> USD.
3. `tests/Unit/ExchangeRate/Infrastructure/BcvWebScraperTest.php`: Valida parseo de HTML con fixtures de respuesta real del BCV y manejo de errores cuando el HTML es inválido o el servidor no responde.
4. `tests/Feature/Admin/ExchangeRateAdminApiTest.php`: Valida endpoints de consulta, sincronización y registro manual de tasas por parte del SuperAdmin.

### Frontend (Vitest + Playwright):
1. `tests/Frontend/Components/CurrencyPriceDisplay.test.tsx` (Vitest):
   - Valida el formateo de precios en USD, conversión en VES y etiqueta de tasa BCV.
2. `tests/Frontend/Components/ExchangeRateActiveCard.test.tsx` (Vitest):
   - Valida el renderizado del valor activo, badge de origen y estado de carga.
3. `tests/Frontend/E2E/admin-exchange-rate-management.spec.ts` (Playwright):
   - Valida que el SuperAdmin acceda al módulo de tasas, presione "Sincronizar", vea el toast de éxito y la tasa se actualice en la tabla.

### Seeder:
- `ExchangeRateSeeder.php`: Crea una tasa inicial de ejemplo para desarrollo y tests (`41.0245 VES/USD`).

---

## 10. 🚀 Fases de Implementación Propuestas

| Fase | Tareas | Entregables |
| :---: | :--- | :--- |
| **Fase 1** | **Dominio & Infraestructura de Base:**<br>• Migración `create_exchange_rates_table`.<br>• Entidad, VOs y Contratos en `src/ExchangeRate/Domain/`.<br>• Modelo Eloquent y Repositorio. | Migración, Entidad, Repositorio, Seeder. |
| **Fase 2** | **Scraper del BCV & Casos de Uso:**<br>• Implementación de `BcvWebScraper`.<br>• Casos de uso `SyncBcvExchangeRateUseCase`, `GetActiveExchangeRateUseCase`, `ConvertCurrencyAmountUseCase`.<br>• Comando `php artisan exchange-rate:sync-bcv`. | Scraper, UseCases, Artisan Command, Tests Unitarios. |
| **Fase 3** | **Controladores, Rutas y APIs:**<br>• Controladores de Admin (`AdminSyncBcvPOSTController`, `AdminListRatesGETController`, etc.).<br>• Endpoint de consulta de tasa activa `/api/exchange-rate/current`.<br>• Registro en `ExchangeRateServiceProvider.php`. | Rutas, Controladores, Tests Feature. |
| **Fase 4** | **Interfaz SuperAdmin & Componentes UI:**<br>• Vista `ExchangeRateManagementPage.tsx` en Backoffice.<br>• Componente `CurrencyPriceDisplay.tsx` para Storefront y Catálogo.<br>• Integración de menú de navegación en Admin Sidebar. | Vistas React, Componentes, Tests Vitest. |
| **Fase 5** | **Integración de Liquidación de Comisiones:**<br>• Lógica de cálculo de comisión en moneda de origen (VES o USDT/USDC).<br>• Pruebas de integración E2E con Playwright. | Lógica de comisiones, Tests E2E Playwright. |
