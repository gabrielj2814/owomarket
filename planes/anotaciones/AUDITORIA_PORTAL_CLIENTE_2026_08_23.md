# Auditoría del portal del cliente — `pages/customer/**`

> ## 📌 ESTADO — 23/08/2026
>
> **C1 ✅ cerrado · C2 ⬜ abierto.**
>
> Alcance: las 11 páginas de `resources/js/pages/customer/**` (2.801 líneas) y los 18
> controladores de `Src\CentralCustomer` que hay detrás.
>
> Era la única parte del frontend sin pasada propia: sólo la habían tocado de refilón G14
> (una página) y N35 (un barrido de `.catch(() => {})`).

---

**Fecha:** 23 de agosto de 2026
**Método:** lectura del código, y **prueba de comportamiento para lo que se afirma**. Los
dos hallazgos se demostraron ejecutando; los dos falsos positivos se descartaron igual.

---

## Resumen

El portal está en buen estado estructural. **17 de los 18 controladores derivan la identidad
del comprador desde la sesión** con el trait `ResolvesAuthenticatedCustomer`, y el que falta
es el de cupones, que no la necesita. La Fase 0.3-D llegó entera; aquí no hay un A3 sin
cerrar.

Lo que sí hay es una promesa comercial que el sistema no cumple, y un `alert()` que
sobrevivió porque A5 tenía otro alcance.

| # | Qué | Severidad | Demostrado |
| :--- | :--- | :--- | :--- |
| **C1** | ✅ El portal anuncia tres cupones que el checkout rechaza | 🟠 | ✅ los tres pasados por el validador real |
| **C2** | ⬜ Diez `alert()` en cinco páginas del portal | 🟡 | ✅ lectura |

---

## C1. 🟠 El portal anuncia tres cupones que el checkout rechaza

> **Estado:** ✅ CERRADO — 23/08/2026

**Dónde:** `ListCustomerAvailableCouponsUseCase`

Devolvía tres promociones escritas a mano, por delante de cualquier cupón real:

| Código | Lo que prometía |
| :--- | :--- |
| `OWOPASS10` | 10% OFF en tu primera compra con OwO Pass |
| `ENVIOGRATIS` | Envío Gratis Nacional (MRW / Zoom) |
| `CRYPTO5` | 5% Cashback en USDT con Binance Pay |

El portal las muestra bajo **Mis Cupones**, con su botón de copiar al portapapeles y el
texto «Aplícalo en el carrito de compras».

### Demostrado

Se pasó cada código anunciado por `ValidateCouponUseCase`, que es el que decide en el
checkout. **Los tres rechazados.**

`OWOPASS10` y `CRYPTO5` no existen en ninguna migración, seeder ni base de datos: sólo en esa
lista. `ENVIOGRATIS` existe únicamente en `TenantDemoDataSeeder`, o sea en tiendas que
corrieron datos de demostración.

### Por qué importa con usuarios reales

No es un fallo de pantalla: es una promesa comercial incumplida. El comprador copia el
código, llega al pago y se lo rechazan. **Y la queja le llega al comerciante**, por una
promoción que él nunca creó y de la que no puede hacerse cargo.

### Y otra vez un test que consagraba el fallo

`CustomerReturnsAndWishlistApiTest.php:127` afirmaba que el primer cupón fuese `OWOPASS10`.
Blindaba el cupón falso, igual que el test de A3 blindaba la enumeración de correos. Es el
tercer caso en dos días.

### ✅ Cómo se cerró

Fuera las tres. El endpoint devuelve sólo cupones reales de la base. Si la sección queda
vacía, eso es la verdad: hoy no hay promociones de plataforma.

Hacerlas reales se descartó porque no es un arreglo sino una decisión de negocio — los
cupones viven en la base de cada inquilino, así que habría que decidir **quién paga ese
descuento**, y eso toca monetización y comisiones.

Se añadió el estado vacío que la página no tenía: antes nunca llegaba vacía porque el
servidor siempre inventaba tres, y sin él se veía «Cupones Disponibles (0)» sobre un hueco
en blanco.

**Vigilado por:** el test reescrito, que ya no exige un código concreto sino que **todo
código anunciado se pueda canjear**. Si alguien vuelve a añadir una promoción de adorno,
falla.

---

## C2. 🟡 Diez `alert()` en cinco páginas del portal

> **Estado:** ⬜ ABIERTO

A5 arregló los `alert()` de `pages/auth/**`. Su alcance no incluía el portal, así que aquí
siguen intactos:

| Página | Cuántos |
| :--- | :--- |
| `CustomerAddressesPage` | 3 |
| `CustomerReturnsPage` | 3 |
| `CustomerReviewsPage` | 2 |
| `CustomerWishlistPage` | 1 |
| `CustomerCouponsPage` | 1 — ✅ ya arreglado al cerrar C1 |

Mismo razonamiento que A5: bloquea el hilo, no se puede estilar, no es accesible. Y aquí
pesa más que en los logins, porque **son las respuestas a acciones que el comprador acaba de
hacer** — guardar una dirección, pedir una devolución, publicar una reseña. Un `alert()` de
error deja al usuario sin saber si su solicitud de devolución se envió o no.

El patrón a copiar ya existe en este mismo directorio: `PortalLoadError` para fallos de
carga, y estado con mensaje en línea para el resto.

---

## Lo que se comprobó y está BIEN

| Qué | Resultado |
| :--- | :--- |
| **Identidad desde la sesión** | ✅ 17 de 18 controladores usan `ResolvesAuthenticatedCustomer`. Las páginas mandan `customer.id` en la URL y el servidor lo **ignora**: o exige que coincida con la sesión (perfil, direcciones) o lo sustituye por el de la sesión (listados) |
| **`X-Customer-Id` en cupones** | ✅ **Falso positivo.** El controlador leía `customer_id` y esa cabecera —las dos fuentes que el trait documenta como prohibidas— pero **el valor no se usaba en ninguna parte**. No era un agujero. Se quitó igualmente: se leía como uno, y era una invitación a hacerlo funcionar filtrando por él sin añadir la comprobación de propiedad |
| **Fuga de cupones de otras tiendas** | ✅ **Falso positivo.** El endpoint es público y vuelca todos los cupones activos, pero `routes/api.php` va en el grupo `api`, que **no** lleva `InitializeTenancyByDomain`: corre contra la base central, donde la tabla `coupons` no existe. Se persiguió hasta el final antes de descartarlo |
| **N35 (`.catch` vacíos)** | ✅ Cerrado de verdad. Cero `.catch(() => {})` en el portal. Las dos páginas sin `PortalLoadError` —perfil y soporte— son formularios y tienen su propio manejo de errores |

---

## Lo que salió de aquí y no era de aquí

Auditar el perfil destapó que **A4 se había cerrado incompleto**:
`UpdateCustomerProfilePUTController` seguía en `min:8` mientras registro y reset ya exigían
la política completa. La contraseña se podía cambiar a `aaaaaaaa` desde «Mi Perfil»,
saltándose la regla entera.

Está corregido y anotado en `AUDITORIA_AUTH_2026_08_22.md`. Se deja escrito aquí también
porque es la lección de la jornada: **el arreglo de A4 llegó a dos hermanos de tres**, que es
el mismo patrón que A4 venía a corregir.
