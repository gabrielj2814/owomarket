# Plan — Migrar el frontend a Flowbite

> **Estado:** ✅ TERMINADA el 12/09/2026 · Redactada el 11/09/2026
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

## ✅ Terminada

**81 de 83 páginas y 30 de 34 componentes** usan Flowbite. Lo que falta está fuera a
propósito, y cada fichero lleva escrito el porqué (ver más abajo).

Se empezó en 55/81. Las tres zonas quedaron con **su propio tema**, y son deliberadamente
distintos: son tres productos con tres audiencias.

| Zona | Tema | Dónde se aplica |
| :--- | :--- | :--- |
| Portal del cliente | `portalTheme` | `CustomerAccountLayout`, con `root` para cortar la herencia |
| Panel del comerciante | `tenantPanelTheme` | `TenantOwnerShell` |
| Escaparate y marketplace | `storefrontTheme` | `CentralLayout` y `StorefrontLayout` |

### Por qué el panel no cuelga su tema de `Dashboard`

Sería el sitio obvio, pero `Dashboard` lo comparten el panel del comerciante Y el backoffice
del administrador, que usa el aspecto por defecto. Colgarlo ahí habría repintado el backoffice
entero de rebote. Lo aplica `TenantOwnerShell`, que además recoge lo que esas pantallas ya
repetían: layout, `<Head>` y pestañas.

### Por qué el portal corta la herencia

`CustomerAccountLayout` se construye sobre `CentralLayout`, que aplica `storefrontTheme`. Sin
`root` en su `<ThemeProvider>` los dos temas se fusionarían y el portal acabaría con valores que
no le corresponden — **sin dar ningún error**.

---

## Lo que queda fuera a propósito

`reglas.md` §1.3 admite Tailwind puro para lo que Flowbite no cubra. Estos son esos casos, y
cada uno lleva la razón escrita en su propio fichero para que nadie los "arregle":

| Fichero | Por qué |
| :--- | :--- |
| `CurrencyPriceDisplay` | Primitiva tipográfica con su propia escala de tamaños, usada en las TRES zonas. Un componente tematizado se vería distinto en cada una |
| `TenantOwnerNavTabs` | Es navegación: cada pestaña es un `<Link>` de Inertia. El `Tabs` de Flowbite gestiona su propio estado y renderiza paneles |
| `StorefrontFooter` | Sus partes distintivas (métodos de pago, garantías, marca) no existen en el `Footer` de Flowbite, y sus columnas son `<ul><li><a>` planos — eso es HTML, no un componente reinventado |
| `OrderTrackingTimeline` | Sus botones SÍ se migraron; la línea de tiempo no tiene equivalente |
| `welcome.tsx`, `InicialPage.tsx` | Un título, una imagen y un párrafo |

También se conservan en Tailwind, dentro de pantallas ya migradas: los selectores de cantidad
(Flowbite no tiene stepper), las zonas de arrastrar ficheros con sus miniaturas, y los
selectores de método de pago —que son tarjetas grandes con icono y descripción, no botones—.

---

## Tres trampas que costó descubrir

**Colores que no existen.** En flowbite-react 0.12, `success` y `failure` valen en insignias
pero **no en botones**; `warning` tampoco. Un color inexistente deja el elemento **sin relleno,
sin error y sin que nadie lo reporte**. Los que hacían falta viven ahora en los temas.

**Un componente compartido no puede usar colores de un tema.** `DeliveryConfirmationPanel` se
pinta en el portal y en el escaparate; `OrderTrackingTimeline` y `TenantKycCard`, en dos sitios
cada uno. Todos usan colores de Flowbite (`blue`, `green`, `light`), porque un color propio de
un tema saldría sin relleno en la otra zona.

**`Card` no acepta `as`.** Un formulario va DENTRO del `Card`, no al revés: envolverlo por fuera
deja el borde de la tarjeta separado del área que el formulario ocupa.

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

## Y una del `<form>` en los modales

Un `<form>` va alrededor de `ModalBody` + `ModalFooter`, no dentro de cada uno: el botón de
enviar vive en el pie y necesita estar dentro del formulario para que `type="submit"` funcione.
