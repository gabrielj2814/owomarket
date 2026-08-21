# PLAN — Fase 0.5: Quitar los datos bancarios de demostración y el bypass del checkout

> **Origen:** hallazgos G1 y G8 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md` (punto 5 y último del plan de acción de Fase 0)
> **Severidad:** 🔴 Crítico — G1 es, según la auditoría, «el bug de frontend más urgente»
> **Tamaño:** 1 servicio nuevo, 1 value object ampliado, 1 controlador, 1 página de checkout, 1 archivo de tipos, 1 archivo de test nuevo
> **Estado:** ✅ Implementado — pendiente de validación con `php artisan test`
> **Depende de:** nada

---

## 1. G1 — El comprador transfería el dinero a una cuenta que no era la de la tienda

La auditoría lo describe como un problema de frontend: literales de demostración detrás de un `||` en `TenantCheckoutPage.tsx`. **Al implementarlo resultó ser peor: los mismos datos estaban hardcodeados también en el backend**, en `ViewCheckoutTenantGETController.php:80-110`:

```php
'document_id'       => 'J-50123456-0',
'bank_name'         => '0102 - Banco de Venezuela',
'binance_pay_id'    => '284759302',
'qr_code'           => 'https://api.qrserver.com/v1/create-qr-code/?...binancepay://pay?id=284759302',
'exchange_rate_ves' => 40.50,
```

Es decir, el fallback del frontend casi nunca llegaba a activarse: el servidor ya enviaba los datos falsos.

**Tres daños distintos en el mismo bloque:**

1. **Cuenta ajena.** El comprador elegía Pago Móvil, veía un RIF y un teléfono que no eran los de la tienda, y transfería allí. El dinero no llegaba al comercio.
2. **Tasa 19 veces menor.** `40.50` Bs/USD cuando la real ronda 775. Un pedido de $100 se mostraba como «Bs. 4.050» en vez de «Bs. 77.533».
3. **Fuga a un tercero.** El QR de Binance lo generaba `api.qrserver.com`, un servicio externo al que se le enviaba el identificador de cobro en la URL.

### 1.1 La causa de fondo

**No existía ningún sitio donde el comerciante configurara sus datos de cobro.** `StoreSettings` sólo guarda nombre, email, moneda, teléfono, dirección, redes sociales y SEO. La funcionalidad nunca se construyó, y por eso se rellenó con datos de demostración.

### 1.2 Solución

El almacén de settings del inquilino ya era **clave-valor genérico con grupos** (`tenant_settings`: `key`, `value`, `type`, `group`), así que sólo faltaba habilitar un grupo nuevo:

```php
// SettingGroup.php
public const PAYMENT = 'payment';
```

Y un servicio que construya la lista de métodos a partir de lo que el comercio tenga configurado:

**`Src\Payment\Application\Service\StorefrontPaymentMethodsProvider`**

| Método | Claves de settings requeridas | Si falta alguna |
| :--- | :--- | :--- |
| Pago Móvil | `pago_movil_bank_name`, `pago_movil_document_id`, `pago_movil_phone` | No se ofrece |
| Binance Pay | `binance_pay_id` | No se ofrece |
| Transferencia | `bank_transfer_instructions` | No se ofrece |
| Contra entrega | `cash_on_delivery_enabled` (`true`/`1`/`si`) | No se ofrece |

**La regla es que un método sin configurar NO se ofrece.** Es preferible que el comprador vea una opción menos a que envíe dinero a una cuenta equivocada. Esto es un cambio de comportamiento consciente: una tienda recién creada arranca sin métodos de pago hasta que su dueño los configure.

Opcionales, se envían sólo si existen: `pago_movil_holder_name` y `binance_qr_url`.

### 1.3 La tasa de cambio sale del módulo ExchangeRate

`exchange_rate_ves` ya no es un literal: se obtiene de `GetActiveExchangeRateUseCase`. Si no hay tasa activa **no se envía el campo**, y el frontend muestra «Tasa de cambio no disponible… transfiere el equivalente a $X según la tasa BCV del día» en lugar de un monto inventado.

Esto toca de refilón los hallazgos D3 y G13, que siguen abiertos en el resto de la aplicación: aquí sólo se corrige el panel de Pago Móvil del checkout del inquilino.

### 1.4 Frontend

- Eliminados todos los `|| 'literal'` de `TenantCheckoutPage.tsx` (6 puntos).
- El QR de Binance sólo se renderiza si la tienda configuró uno propio.
- **`StorefrontPaymentMethod` (en `types/models/Storefront.d.ts`) declara ahora los campos de cobro.** Esto es importante: el tipo sólo tenía `id`, `name`, `description` e `instructions`, así que el checkout accedía al resto con `(method as any).campo` y TypeScript **no podía avisar** de que siempre se caía al literal. El tipo incompleto es lo que hizo invisible el bug.

---

## 2. G8 — Botón que anulaba la puerta de autenticación

```tsx
{/* Optional dev/test bypass button */}
<Button ... onClick={() => { setIsAuthGateModalOpen(false); setCurrentStep(3); }}>
    Continuar como Invitado (Modo Pruebas)
</Button>
```

Cualquier anónimo llegaba al paso de pago sin cuenta, justo lo que el modal declara obligatorio dos líneas más arriba.

**Solución:** eliminado. En su lugar queda un botón secundario «Volver al carrito» que sólo cierra el modal, sin saltar de paso.

**No se abordó** la otra mitad del hallazgo: el botón de iniciar sesión sigue haciendo `window.location.href`, una recarga completa que pierde los datos ya escritos (nombre, dirección, notas), porque sólo el carrito está persistido. Es un problema de UX, no de seguridad; queda como seguimiento.

---

## 3. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **G1** — datos bancarios de demostración (frontend) | ✅ Cerrado |
| **G1** — datos bancarios de demostración (backend, no documentado en la auditoría) | ✅ Cerrado |
| **G1** — QR generado por un tercero | ✅ Cerrado |
| **G1** — tasa 40,50 hardcodeada en el checkout del inquilino | ✅ Cerrado |
| **G8** — botón de bypass de autenticación | ✅ Cerrado |
| **G8** — recarga que pierde los datos del formulario | ⬜ Seguimiento (UX) |
| **D3 / G13** — tasa hardcodeada en el resto de la app | ⬜ Fase 1 / Fase 3 |

**Con esto queda cerrada la Fase 0 completa** del plan de acción de la auditoría (los cinco puntos «antes de exponer nada»).

---

## 4. Tareas

- [x] Añadir el grupo `payment` a `SettingGroup`
- [x] Crear `StorefrontPaymentMethodsProvider`
- [x] Sustituir la lista hardcodeada de `ViewCheckoutTenantGETController`
- [x] Conectar la tasa al módulo `ExchangeRate`
- [x] Quitar los 6 literales de reserva de `TenantCheckoutPage.tsx`
- [x] Completar el tipo `StorefrontPaymentMethod`
- [x] Eliminar el botón de bypass (G8)
- [x] Crear `tests/Feature/Marketplace/StorefrontCheckoutPaymentMethodsTest.php`
- [ ] `php artisan test`
- [ ] `npm run types`
- [ ] `vendor/bin/pint src/Payment/ src/Marketplace/ src/TenantSettings/`
- [ ] **Configurar los datos de cobro de las tiendas existentes antes de desplegar** (ver sección 6)
- [ ] Commit: `fix(checkout): eliminar datos bancarios de demostración y el bypass de autenticación`
- [ ] `git push origin <rama_actual>`
- [ ] Mover este documento a `planes/implementados/`

---

## 5. Verificación manual

**Debe seguir funcionando:**
1. Una tienda con sus datos de cobro configurados ofrece Pago Móvil y Binance Pay con **sus** datos, y el monto en bolívares usa la tasa BCV real.
2. Completar una compra con cada método configurado.

**Debe dejar de funcionar:**
3. Abrir el checkout de una tienda sin configurar → **no aparece ningún método de pago** (antes: aparecían todos, con datos ajenos).
4. Buscar `J-50123456-0`, `284759302`, `0412-1234567` o `api.qrserver.com` en el HTML del checkout → **sin resultados**.
5. En el modal de autenticación, ya no existe «Continuar como Invitado (Modo Pruebas)»; no hay forma de llegar al paso 3 sin sesión.

---

## 6. Riesgo

**Alto en lo operativo, aunque el cambio de código sea acotado.**

**Las tiendas existentes se quedarán sin métodos de pago** en cuanto se despliegue, porque ninguna tiene el grupo `payment` configurado (no existía). Antes de desplegar hay que cargar los datos reales de cada comercio. Hoy se puede hacer por API, que ya existe:

```
POST /api-tenant/settings/item
{ "key": "pago_movil_bank_name",   "value": "0134 - Banesco", "group": "payment" }
{ "key": "pago_movil_document_id", "value": "J-XXXXXXXX-X",   "group": "payment" }
{ "key": "pago_movil_phone",       "value": "0414-XXXXXXX",   "group": "payment" }
{ "key": "binance_pay_id",         "value": "XXXXXXXXX",      "group": "payment" }
{ "key": "cash_on_delivery_enabled", "value": "true",         "group": "payment" }
```

Esto es deliberado y preferible a la alternativa: seguir cobrando a una cuenta falsa un día más. Pero **es un cambio que hay que coordinar con los comerciantes**, no desplegar en silencio.

Comprobación previa al despliegue, por tienda:

```sql
SELECT `key`, `value` FROM tenant_settings WHERE `group` = 'payment';
```

---

## 7. Trabajo de seguimiento identificado

1. **Falta la pantalla de configuración de cobros en el backoffice del inquilino.** Hoy sólo se puede configurar por API. Es la pieza que cierra G1 del todo desde el punto de vista del producto, y debería ser lo siguiente que se construya del módulo de settings.
2. **Los datos de cobro se guardan en claro en `tenant_settings`.** Un RIF y un teléfono no son secretos, pero conviene decidir si el Binance Pay ID u otros identificadores futuros (claves de API de pasarelas) merecen cifrado en reposo. La columna `type` del almacén permitiría marcar `encrypted`.
3. **El checkout central (`CentralCheckoutPage.tsx`) sigue con su propia tasa hardcodeada** (`useState<number>(775.3356)`, hallazgo G9) y sin envío ni impuestos. No se tocó aquí porque es otro checkout, pero tiene el mismo tipo de problema.
4. **G8 (UX):** el botón de iniciar sesión recarga la página y pierde los datos del formulario ya escritos.
