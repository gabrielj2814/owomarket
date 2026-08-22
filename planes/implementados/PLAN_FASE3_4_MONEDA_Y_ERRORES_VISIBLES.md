# PLAN — Fase 3.4: Moneda honesta y errores visibles (cierre del bloque G)

> **Origen:** hallazgos G9, G13, G14 y G15, más un hallazgo nuevo (N32) descubierto al abrirlos
> **Severidad:** 🟠 G9, G13, G14 · 🟡 G15 · 🔴 N32
> **Tamaño:** 2 servicios nuevos, 1 modelo nuevo, 1 seeder nuevo, 1 controlador, 8 archivos de frontend, 1 archivo de test nuevo
> **Estado:** ✅ Implementado — 545 tests en verde, `npm run types` sin errores
> **Cierra el bloque G.**

---

## 1. N32 (nuevo) — El checkout central tenía datos bancarios inventados

Al abrir G9 apareció esto en `CentralCheckoutPage.tsx`:

```tsx
<div><strong>Banco:</strong> Banesco (0134)</div>
<div><strong>C.I.:</strong> J-501234567</div>
<div><strong>Teléfono:</strong> 0412-9998877</div>
```

**Es G1 otra vez.** La Fase 0.5 sacó los datos de demostración del checkout **del inquilino**, pero el **central** se quedó con los suyos, y el controlador no pasaba ningún método de pago. En un pedido multi-tienda cobra la plataforma y luego liquida con cada comercio, así que el comprador estaba transfiriendo a una cuenta que no era de nadie.

### Solución

La tabla `central_settings` existía desde 2025 y **no la usaba nadie**. Sobre ella:

- `CentralSetting` (modelo) y `CentralPaymentMethodsProvider`, hermano del `StorefrontPaymentMethodsProvider` de la Fase 0.5, con **la misma regla: un método que no esté completamente configurado no se ofrece**.
- El controlador pasa `payment_methods` y la página los pinta desde las props. Si no hay, muestra un aviso de que ese método no está disponible — nunca datos inventados.
- La tasa en bolívares sale del módulo `ExchangeRate`, no de un literal.
- `CentralPaymentDemoSeeder` con datos de mentira, bajo la guarda de entorno de la Fase 2.1: en producción los carga el superadmin o el método no aparece.

---

## 2. G9 — Tasa BCV hardcodeada y carrera con la petición

```tsx
const [bcvRate, setBcvRate] = useState<number>(775.3356);
useEffect(() => { getActiveExchangeRate().then(...).catch(() => {}); }, []);
```

Nada bloqueaba el envío mientras la tasa no había cargado, y el `.catch` era silencioso: el comprador podía confirmar un pago en bolívares calculado con una tasa inventada.

### Solución

Arranca en `null` —no hay tasa hasta que el servidor la da— y el botón de confirmar espera. Usa la petición compartida de G13, así que esta página no añade una propia.

> **Lo que NO cierra esta fase:** el checkout central sigue **sin incluir envío ni impuestos**; el total mostrado es el subtotal puro. Eso no es un bug de código sino una funcionalidad que falta, y se anota como seguimiento en vez de darlo por cerrado.

---

## 3. G13 — Una petición por precio, y una tasa inventada rotulada «oficial BCV»

`CurrencyPriceDisplay` se usa en `ProductCard`, en los dos checkouts y en las dos fichas de producto. Cada instancia disparaba su propio GET: **un catálogo de 24 tarjetas lanzaba 24 peticiones simultáneas**. Y mientras respondían —o si fallaban, porque el `.catch` era silencioso— cada precio mostraba bolívares calculados con `775.3356` bajo el rótulo **«Tasa oficial BCV»**.

### Solución

- `getSharedActiveRate()` memoiza la promesa a nivel de módulo: las N instancias que monten a la vez comparten **una sola petición**.
- **Se elimina el valor por defecto.** Hasta que el servidor responde no se muestra ni el importe en bolívares ni la insignia. Es el criterio de la Fase 1.4 con `/convert`: preferible no mostrar nada que mostrar un número que no es.

---

## 4. G14 — `axios` directo en un componente

`CustomerSupportPage` llamaba a `axios` desde el propio componente, violando la regla 1 de frontend de `reglas.md`. No era sólo estilo: iba **sin `X-CSRF-TOKEN`**, y no manejaba el caso `status !== 'success'` — el usuario pulsaba «Enviar», no veía nada y reenviaba, generando **tickets duplicados**.

### Solución

`CustomerSupportServices` nuevo, con CSRF y respuesta tipada. Las tres llamadas pasan por él y las tres ramas de fallo muestran ahora un mensaje. De paso, el `user_id` deja de viajar en la URL: desde la Fase 0.3-C el backend resuelve la identidad desde la sesión, y pasarlo a mano era el vector de IDOR de A6.

Además, los `URL.createObjectURL` de las vistas previas **nunca se revocaban**: se liberan al quitar el fichero y al desmontar.

---

## 5. G15 — Otros defectos verificados

| Defecto | Solución |
| :--- | :--- |
| `CustomerOrdersPage` reconstruía el carrito con `tenant_name: item.tenant_id` y `slug: item.product_id` | El backend enriquece los items con `tenant_name` y `product_slug`. El frontend no tenía nada mejor con que rellenarlos: el arreglo tenía que empezar por el servidor |
| `CustomerAccountLayout` ignoraba `loading` | Muestra un estado de carga en vez de «Inicia sesión» a alguien que sí la tiene |
| `CentralProductDetailPage` se quedaba en «Cargando producto…» para siempre | `finally` en vez de apagar el flag sólo en la rama de éxito, y una pantalla de «no pudimos cargarlo» |
| Los `.catch(() => {})` de `pages/customer` | ⬜ **No cerrado.** Son 9 sitios y cada uno necesita su propio estado de error; se deja anotado |

La búsqueda de slugs va en **una sola consulta** para todos los items de la página: hacerla ítem a ítem habría metido un N+1 sobre la base central en cada carga del historial — de hecho lo introduje y lo corregí antes de cerrar.

---

## 6. Archivos tocados

**Backend:**
- `src/Payment/Infrastructure/Eloquent/Models/CentralSetting.php` (**nuevo**)
- `src/Payment/Application/Service/CentralPaymentMethodsProvider.php` (**nuevo**)
- `src/Marketplace/Infrastructure/Http/Controller/ViewCheckoutCentralGETController.php`
- `src/CentralCustomer/Application/UseCases/ListCustomerOrdersUseCase.php`
- `database/seeders/CentralPaymentDemoSeeder.php` (**nuevo**) y `DatabaseSeeder.php`

**Frontend:**
- `resources/js/Services/CustomerSupportServices.ts` (**nuevo**)
- `resources/js/Services/ExchangeRateServices.ts`, `CustomerPortalServices.ts`
- `resources/js/components/ui/CurrencyPriceDisplay.tsx`
- `resources/js/components/layouts/CustomerAccountLayout.tsx`
- `resources/js/pages/marketplace/checkout/CentralCheckoutPage.tsx`
- `resources/js/pages/marketplace/product/CentralProductDetailPage.tsx`
- `resources/js/pages/customer/CustomerOrdersPage.tsx`
- `resources/js/pages/customer/support/CustomerSupportPage.tsx`

**Tests:** `tests/Feature/Marketplace/CentralCheckoutPaymentMethodsTest.php` (**nuevo**, 5 casos)

---

## 7. Checklist de cierre

- [x] `php artisan test` → 545 pasan (3.225 aserciones)
- [x] `npm run types` → 0 errores
- [x] `./vendor/bin/pint` sobre los archivos tocados
- [x] `git add` + commit
- [x] `git push origin <rama_actual>`
- [x] Actualizar el bloque de estado de `AUDITORIA_BUGS_2026_08_21.md`
- [ ] Probar el checkout central y el soporte en el navegador — ⚠️ pendiente

---

## 8. Riesgo

**Medio.**

1. **El checkout central se queda sin métodos de pago hasta que se configuren.** Es deliberado —la alternativa era seguir enviando dinero a una cuenta inventada— pero significa que, tras desplegar, **el checkout central no acepta pagos** hasta cargar `central_settings`. En desarrollo lo siembra `CentralPaymentDemoSeeder`.
2. **Los precios en bolívares desaparecen si no hay tasa activa.** Antes se mostraba `775.3356` como si fuera oficial. Refuerza el requisito de la Fase 1.4: tiene que haber una tasa activa.
3. **La tasa compartida se memoiza por carga de página.** Si alguien deja una pestaña abierta un día entero, seguirá con la tasa de cuando la abrió. Antes pasaba lo mismo, sólo que 24 veces.

---

## 9. Trabajo de seguimiento identificado

1. **No hay pantalla para configurar los datos de cobro de la plataforma.** `CentralPaymentMethodsProvider` lee `central_settings`, pero el superadmin no tiene dónde escribirlos: hoy sólo los pone un seeder o un `INSERT` a mano. Es el equivalente central de lo que la Fase 0.5 construyó para las tiendas.
2. **El checkout central sigue sin envío ni impuestos.** El total mostrado es el subtotal puro, así que el importe que el comprador transfiere no coincidirá con el total real en cuanto se añada el envío.
3. **Los `.catch(() => {})` de `pages/customer`** siguen haciendo que un error de red sea indistinguible de «no tienes pedidos». Son 9 sitios.
4. **`central_settings` no tiene módulo propio.** El modelo vive en `Payment` porque es el único que la usa; si aparecen más ajustes centrales merecerá su propio bounded context.
