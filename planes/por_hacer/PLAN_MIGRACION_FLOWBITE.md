# Plan — Migrar el frontend a Flowbite

> **Estado:** 🟨 En curso · Redactado el 11/09/2026
>
> `reglas.md` §1.3 ya exige componentes de Flowbite React. Este documento no cambia la regla:
> recoge **dónde no se está cumpliendo** y cómo cerrarlo sin romper el aspecto de nada.
>
> Escrito para retomarlo sin contexto previo.

---

## La regla, afinada

**Flowbite en todas las vistas.** Tailwind puro solo para lo que Flowbite no cubre: un
componente que no existe en la librería, o un remate que ningún componente resuelve.

Un `className` suelto para ajustar un margen es normal. Un `className` que **reconstruye** un
componente que Flowbite ya trae —una tarjeta, un modal, un botón— no lo es.

---

## Lo que se descubrió al empezar

El portal del cliente no estaba «sin Flowbite»: estaba **sin primitivas**. La misma tarjeta
copiada en 6 ficheros, el mismo modal a mano en 4, el mismo botón azul en 9, el mismo estilo de
campo en 4.

Eso cambia cuál es el arreglo. Migrar fichero a fichero poniendo `<Card className="rounded-3xl
p-6 shadow-sm…">` habría recreado exactamente el mismo problema, solo que con otra etiqueta.

**La pieza que lo resuelve es [`resources/js/theme/portalTheme.ts`](../../resources/js/theme/portalTheme.ts):**
un tema de Flowbite con el lenguaje visual que el portal ya tenía, aplicado con `<ThemeProvider>`
desde `CustomerAccountLayout`. Las páginas escriben `<Card>` y `<Button color="primary">` a
secas y salen con el aspecto de siempre.

Dos consecuencias que conviene tener presentes:

1. **La migración no debe notarse.** Un refactor que además cambia cómo se ve todo es imposible
   de revisar: no se distingue un cambio intencionado de un descuido. Si al migrar una pantalla
   algo se ve distinto, el sitio de arreglarlo es el tema.
2. **El tema solo declara lo que se aparta del original.** `createTheme` fusiona; repetir un
   valor que ya es el que queremos solo añade una línea que mantener.

---

## ✅ Hecho

| Pantalla | Nota |
| :--- | :--- |
| `CustomerReturnsPage` | La primera. Sirvió de prueba del tema y además incorporó la vista del comprador del subsistema 5 |
| `CustomerAccountLayout` | Envuelve el portal en `<ThemeProvider theme={portalTheme}>` |

---

## ⬜ Portal del cliente — lo que queda

El tema ya está puesto, así que estas nueve son mecánicas: sustituir marcado por componentes y
**borrar** el `className` que el tema ya cubre.

| Pantalla | Líneas | Qué tiene dentro |
| :--- | ---: | :--- |
| `CustomerAddressesPage` | 335 | Modal a mano, formulario |
| `CustomerOrdersPage` | 257 | Tarjetas, insignias de estado |
| `CustomerReviewsPage` | 255 | Modal a mano, formulario |
| `CustomerOrderDetailPage` | 255 | Tarjetas, cronología |
| `CustomerDashboardPage` | 246 | Tarjetas de resumen |
| `CustomerProfilePage` | 231 | Formulario |
| `CustomerWishlistPage` | 167 | Tarjetas de producto |
| `CustomerInvoicesPage` | 130 | Tabla |
| `CustomerCouponsPage` | 129 | Tarjetas |
| `CustomerSupportPage` | 576 | Vive en `customer/support/`; la mayor del portal |

Y dos componentes que el portal usa: `DeliveryConfirmationPanel` (163).

---

## ⬜ Fuera del portal del cliente

Estas **no** están cubiertas por `portalTheme` y cada grupo necesita decidir su propio aspecto
antes de tocarlas. No conviene empezarlas sin esa decisión.

### Escaparate y marketplace central

Es la cara pública: cambiar cómo se ve es una decisión de producto, no de refactor.

| Pantalla | Líneas |
| :--- | ---: |
| `marketplace/checkout/CentralCheckoutPage` | 704 |
| `marketplace/landing/MerchantLandingPage` | 595 |
| `marketplace/home/centralHomePage` | 447 |
| `marketplace/product/CentralProductDetailPage` | 436 |
| `marketplace/catalog/CentralCatalogPage` | 360 |
| `marketplace/cart/CentralCartPage` | 287 |
| `marketplace/checkout/CentralOrderConfirmationPage` | 241 |

Con sus componentes: `StorefrontFooter` (222), `CentralCartDrawer` (188),
`OrderTrackingTimeline` (148), `StorefrontLayout` (96).

### Panel del comerciante

| Pantalla | Líneas |
| :--- | ---: |
| `tenant/support/TenantOwnerSupportPage` | 709 |
| `tenant/support/TenantStoreSupportPage` | 605 |
| `tenant/wallet/TenantOwnerWalletPage` | 552 |
| `tenant/catalog/TenantOwnerCentralCatalogPage` | 312 |
| `tenant/billing/TenantOwnerBillingPage` | 282 |

Con sus componentes: `TenantKycCard` (181), `TenantReputationCard` (116),
`ShipmentEvidenceCard` (113), `CurrencyPriceDisplay` (174), `TenantOwnerNavTabs` (84).

> `TenantReputationCard` es de 11/09/2026 y se escribió con Tailwind para no desentonar con la
> billetera que la rodea, que tampoco usa Flowbite. Se migra **con** esa pantalla, no antes:
> migrarla sola la dejaría siendo la única distinta.

### Autenticación y sueltas

`auth/ResetPasswordPage` (201), `auth/ForgotPasswordPage` (118), `InicialPage` (14),
`welcome.tsx` (31).

---

## Dónde se está

| | Con Flowbite | Total |
| :--- | ---: | ---: |
| Páginas | 55 | 81 |
| Componentes | 21 | 33 |

---

## Cómo migrar una pantalla, en orden

1. **Leerla y anotar su aspecto.** Si no coincide con el tema de su zona, lo que se corrige es
   el tema.
2. **Sustituir**: `<div className="rounded-3xl…">` → `<Card>`; el modal a mano → `<Modal>` con
   sus `ModalHeader/Body/Footer`; `<button>` → `<Button>`; `<select>`/`<textarea>`/`<input>` →
   `<Select>`/`<Textarea>`/`<TextInput>` con su `<Label htmlFor>`.
3. **Borrar el `className` que el tema ya pone.** Si no se borra, no se ha migrado: se ha
   duplicado.
4. **Su test de Vitest**, como exige `reglas.md` §1.5.
5. **Mirarla en el navegador.** Los tests no ven que un botón salió sin relleno.

---

## Dos trampas ya pisadas

**`color="success"` y `color="failure"` NO existen como color de botón** en flowbite-react
0.12.10 — solo `red`, `green`, `blue`, `light`, `dark`… Sí son válidos en las **insignias**, que
es de donde viene la confusión. Un color inexistente deja el botón **sin relleno**, así que la
acción primaria acaba pareciendo menos importante que «Cancelar», y nadie lo reporta.

Sigue presente en `AdminMasterBrandsPage`, `AdminMasterCategoriesPage` y `AdminHomeBannersPage`,
donde el botón de borrar se ve como texto plano.

**Un `<form>` alrededor de `ModalBody` + `ModalFooter`**, no dentro de cada uno: el botón de
enviar vive en el pie y necesita estar dentro del formulario para que `type="submit"` funcione.
