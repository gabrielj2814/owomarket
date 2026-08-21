# PLAN — Fase 1.4: Tasa de cambio fiable (fallback silencioso y scraper del BCV)

> **Origen:** hallazgos D3 y D4 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md` (punto 9 del plan de acción)
> **Severidad:** 🟠 ambos altos
> **Tamaño:** 3 archivos de código, 4 archivos de test (1 nuevo)
> **Estado:** ✅ Implementado — 508 tests en verde (`php artisan test`)
> **Cierra la Fase 1.**

Los dos hallazgos son la misma clase de error visto desde dos lados: **el sistema factura con una tasa equivocada y no avisa a nadie**. D3 es la tasa 1.0 que se usa cuando no hay ninguna registrada; D4 es la tasa vieja que se congela cuando el scraper deja de entender lo que publica el BCV. Por eso van juntos.

---

## 1. D3 — Conversión de moneda con fallback silencioso a tasa 1.0

`ConvertCurrencyAmountUseCase` resolvía la ausencia de tasa activa con un ternario:

```php
$rateValue = $activeRate ? $activeRate->getRate()->value() : 1.0;
$source    = $activeRate ? $activeRate->getSource()->value() : 'FALLBACK';
```

Y el endpoint seguía respondiendo `200` con `success: true`. Nada en la respuesta distinguía una conversión buena de una inventada salvo el literal `FALLBACK` en un campo que nadie mira.

**Escenario:** `SyncBcvExchangeRateUseCase` desactivaba todas las tasas y fallaba al guardar la nueva. A partir de ese momento `GET /api/exchange-rate/convert?amount=100` devolvía **100 Bs por 100 USD** (≈775 veces menos) con `success: true`, y el checkout en bolívares cobraba céntimos.

### Solución

- El caso de uso lanza excepción si no hay tasa activa, igual que ya hacía `GetActiveExchangeRateUseCase`. Se acabó el ternario: la lógica de "exigir tasa" está en un único método privado, `requireActiveRate()`, que usan ambas conversiones.
- `ConvertCurrencyGETController` captura la excepción y responde **404** con `success: false`, replicando exactamente el patrón de `GetActiveRateGETController`.

**Es preferible un checkout caído a uno que cobra mal.** Ese es el criterio de todo este punto.

---

## 2. D3 (segunda mitad) — La ventana sin ninguna tasa activa

`SyncBcvExchangeRateUseCase` hacía `deactivateAll()` y **después** `save()`, en dos escrituras sueltas. Si el `save()` fallaba o el proceso moría entre ambas, el sistema quedaba **sin ninguna tasa activa** — que es justo el estado que disparaba el fallback de 1.0 del punto anterior.

### Solución

Las dos escrituras pasan a correr dentro de un `DB::transaction`. O quedan las dos, o no queda ninguna: nunca el hueco intermedio. Es el mismo patrón que ya usan `CreateDirectInvoiceUseCase` y `GenerateTenantCommissionSettlementUseCase` desde la Fase 1.3.

Nótese que las dos correcciones de D3 se refuerzan: la transacción cierra la vía principal por la que se llegaba al estado sin tasa, y la excepción garantiza que, si aun así se llega por otro camino, se note de inmediato en vez de facturar mal en silencio.

---

## 3. D4 — El scraper rompe en cuanto la tasa supera 999,99

`BcvWebScraper::parseHtml()` limpiaba espacios y cambiaba la coma decimal por punto, pero **no quitaba el punto separador de miles**:

```php
$cleanedRate = str_replace([' ', "\r", "\n", "\t"], '', $rawRate);
$normalizedRate = str_replace(',', '.', $cleanedRate);   // "1.234,56" → "1.234.56"
```

`is_numeric('1.234.56')` es `false`, así que la extracción se daba por fallida.

**Escenario:** el BCV publica una tasa de cuatro cifras. El sync cae al fallback y **congela indefinidamente** la última tasa buena, dejando sólo un `warning` en el log. Todo el sitio factura con una tasa vieja sin que nadie se entere. Dado el nivel actual del bolívar, era cuestión de tiempo.

### Solución

Método privado `normalizeRate()` que interpreta el formato es-VE tal y como lo publica el BCV:

| Entrada | Interpretación | Resultado |
| :--- | :--- | :--- |
| `775,33560000` | coma decimal (formato habitual hoy) | `775.3356` |
| `1.234,56780000` | punto de miles + coma decimal | `1234.5678` |
| `1.234` | agrupación de miles sin decimales | `1234.0` |

El tercer caso es el único ambiguo (`1.234` podría leerse como un punto decimal), y se resuelve con el patrón `^\d{1,3}(\.\d{3})+$`: sólo se trata como agrupación de miles cuando los grupos son exactamente de tres dígitos. Un valor con punto que no encaje en ese patrón se sigue tratando como decimal.

Devuelve `null` en lugar de un float cuando el valor no es una cotización válida y positiva, así que la comprobación de error del llamador queda en una sola línea.

---

## 4. D4 (segunda mitad) — El fallback tenía que gritar, no susurrar

La auditoría pedía «alertar, no sólo `warning`, si el fallback se activa varios días seguidos». El caso de uso ahora mira la antigüedad de la tasa que está reutilizando:

- Menos de 3 días → `warning`, como antes. Un fallo de red puntual no es un incidente.
- **3 días o más** → `error`, con el número de días en el mensaje y en el contexto: *«la tasa activa lleva N días sin actualizarse. Todo el sitio está facturando con una tasa desactualizada»*.

El umbral vive en la constante `STALE_RATE_ALERT_DAYS` del caso de uso. Tres días cubre el fin de semana largo sin generar ruido: el `Schedule` de `routes/console.php` sólo corre en días laborables.

El contexto del log incluye ahora `rate_date` y `days_stale`, que antes no estaban y son justo lo que hace falta para montar una alerta encima.

---

## 5. Archivos tocados

**Código:**
- `src/ExchangeRate/Application/UseCase/ConvertCurrencyAmountUseCase.php`
- `src/ExchangeRate/Application/UseCase/SyncBcvExchangeRateUseCase.php`
- `src/ExchangeRate/Infrastructure/Http/Controller/ConvertCurrencyGETController.php`
- `src/ExchangeRate/Infrastructure/Scrapers/BcvWebScraper.php`

**Tests:**
- `tests/Unit/ExchangeRate/Application/ConvertCurrencyAmountUseCaseTest.php` (+1)
- `tests/Unit/ExchangeRate/Application/SyncBcvExchangeRateUseCaseTest.php` (+2)
- `tests/Unit/ExchangeRate/Infrastructure/BcvWebScraperTest.php` (+3)
- `tests/Feature/ExchangeRate/ExchangeRatePublicApiTest.php` (+1)
- `tests/Feature/ExchangeRate/SyncBcvExchangeRateTransactionTest.php` (**nuevo**)

El test de transacción es de tipo Feature a propósito: con el repositorio mockeado no hay nada que revertir. Usa el repositorio real para desactivar y un doble que revienta al persistir, y comprueba que la tasa anterior **sigue activa** después del fallo.

`SyncBcvExchangeRateUseCaseTest` pasa a llevar `uses(Tests\TestCase::class)` porque el caso de uso ahora toca la fachada `DB`. Es el patrón que ya usan otros tests unitarios del proyecto (`tests/Unit/Billing/...`, `tests/Unit/CentralCustomer/...`).

---

## 6. Checklist de cierre

- [x] `php artisan test` → 508 pasan (3.036 aserciones)
- [x] `./vendor/bin/pint` sobre los archivos tocados
- [x] `git add` + commit
- [x] `git push origin <rama_actual>`
- [x] Actualizar el bloque de estado de `AUDITORIA_BUGS_2026_08_21.md`
- [x] Mover este documento a `planes/implementados/`

---

## 7. Verificación manual

**Debe seguir funcionando:**
1. `GET /api/exchange-rate/convert?amount=25&from=USD&to=VES` con tasa activa → 200 con la conversión.
2. `php artisan exchange-rate:sync-bcv` contra el portal real → tasa actualizada.
3. El panel de Pago Móvil del checkout del inquilino (Fase 0.5) sigue mostrando la tasa real.

**Debe cambiar:**
4. Sin ninguna tasa activa en `exchange_rates` → `/convert` responde **404**, no 200 con tasa 1.0.
5. Con la tasa activa fechada hace más de 3 días y el BCV caído → el log del sync registra un **`error`**, no un `warning`.

---

## 8. Riesgo

**Bajo.**

1. **`/api/exchange-rate/convert` puede devolver 404 donde antes devolvía 200.** El endpoint **no tiene ningún consumidor en el frontend**: `convertCurrency()` está exportado en `resources/js/Services/ExchangeRateServices.ts` pero no lo llama ninguna página ni componente (`CurrencyPriceDisplay` usa `/current`, que ya devolvía 404 en ese caso). Si aparece un consumidor externo, tiene que manejar el 404.
2. **Es imprescindible que haya una tasa activa en producción antes de desplegar.** Antes, una base sin tasa daba números malos; ahora da 404. Conviene comprobarlo:
   ```sql
   SELECT id, rate, rate_date, source FROM exchange_rates
   WHERE base_currency = 'USD' AND target_currency = 'VES' AND is_active = 1;
   ```
   Si no devuelve nada, registrar una tasa manual desde el panel antes de subir.
3. **El caso `1.234` sigue siendo una conjetura sobre el formato del BCV.** Se interpreta como 1.234 bolívares porque el BCV agrupa miles con punto, pero es el único caso que el HTML real no ha ejercitado todavía. Cuando la tasa cruce las cuatro cifras conviene mirar el `raw_snippet` que el sync guarda en `metadata` y confirmarlo.

---

## 9. Trabajo de seguimiento identificado

1. **El `error` del log no llega a nadie.** Escala la severidad, que es lo que pedía la auditoría, pero no hay notificación por correo ni integración con un servicio de alertas. Montar la alerta encima de `days_stale` es un paso aparte y depende de qué canal quiera usarse.
2. **Hay un `ExchangeRateServiceProvider` duplicado y muerto** en `src/ExchangeRate/Infrastructure/Providers/`. No está en `bootstrap/providers.php` (el registrado es `App\Providers\ExchangeRateServiceProvider`) y además le faltan los `use` de `BcvScraperInterface` y `BcvWebScraper`, así que sus `::class` resuelven a FQCN inexistentes. Hoy es inofensivo porque nadie lo carga, pero si alguien lo registra por error el binding del scraper se rompe en silencio. Conviene borrarlo.
3. **D5 y D6 siguen abiertos** en el bloque de dinero: tarifas de envío (`free` ignora su umbral, `weight_based` cobra plano) y la suma de todas las tasas de impuesto activas cuando no se indica país. No estaban en el punto 9 del plan, pero son los dos hallazgos 🟠 que quedan del bloque D.
4. **El checkout central sigue sin incluir envío ni impuestos** (G9), y su tasa BCV está hardcodeada en el componente con `775.3356`. Esta fase arregla el backend; la pantalla sigue calculando por su cuenta.
