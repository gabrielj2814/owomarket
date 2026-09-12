import { createTheme } from 'flowbite-react';

/**
 * El aspecto del portal del cliente, escrito UNA vez.
 *
 * ## Por qué existe
 *
 * `reglas.md` exige componentes de Flowbite, pero las diez páginas del portal estaban hechas a
 * mano con Tailwind: la misma tarjeta copiada en seis ficheros, el mismo modal en cuatro, el
 * mismo botón azul en nueve. El problema no era «no usa Flowbite» — era que **el portal no tenía
 * primitivas y su aspecto vivía repartido en diez sitios**, así que cambiarlo exigía acordarse
 * de los diez.
 *
 * Migrar sin este fichero habría recreado el mismo problema con otras etiquetas: cada `<Card>`
 * arrastrando su `className="rounded-3xl p-6 shadow-sm…"` idéntico al de al lado.
 *
 * ## Qué hay aquí y qué no
 *
 * **Solo las claves que se apartan del tema original de Flowbite.** `createTheme` fusiona con lo
 * que ya trae, así que repetir un valor que ya es el que queremos solo añade una línea que
 * mantener. Si un componente no aparece abajo, es que su aspecto por defecto ya servía.
 *
 * El lenguaje visual no lo invento: se copia del que el portal ya tenía —esquinas `rounded-3xl`,
 * borde tenue, azul de marca, texto pequeño y grueso— para que la migración **no se note**. Un
 * refactor que además cambia cómo se ve todo es imposible de revisar: no se sabe si un cambio de
 * aspecto fue intencionado o un descuido.
 *
 * ## Cómo se usa
 *
 * `CustomerAccountLayout` envuelve el portal en `<ThemeProvider theme={portalTheme}>`, así que
 * las páginas escriben `<Card>` y `<Button color="primary">` a secas. **Si una pantalla necesita
 * un `className` para verse como sus hermanas, el sitio de arreglarlo es este fichero.**
 */
export const portalTheme = createTheme({
    card: {
        root: {
            // Las tarjetas del portal son más redondas y de sombra más suave que las de
            // Flowbite, y su borde es semitransparente para que no compita con el contenido.
            base: 'flex bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-200/80 dark:border-gray-800/80',
            children: 'flex h-full flex-col justify-center gap-4 p-6',
        },
    },

    button: {
        base: 'group relative flex items-stretch justify-center rounded-xl p-0.5 text-center font-bold transition focus:outline-none focus:ring-2',
        color: {
            // `primary` es el azul del portal --el mismo de la navegación activa--, con su
            // sombra de color. Se añade como color propio en vez de pisar `default` para que
            // quien lea `color="primary"` sepa que está pidiendo el azul de marca.
            primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-300 shadow-md shadow-blue-500/20 dark:focus:ring-blue-800',
            // Secundario: el «Cancelar» de los modales, que no debe competir con la acción.
            subtle: 'bg-transparent text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200',
        },
        size: {
            xs: 'h-8 px-3 text-xs',
            sm: 'h-9 px-4 text-xs',
            md: 'h-10 px-5 text-xs',
        },
    },

    modal: {
        content: {
            // `rounded-3xl` para que el modal pertenezca al mismo mundo que las tarjetas, y
            // `bg-gray-900` en oscuro --Flowbite usa `gray-700`, que aquí se ve descolorido
            // sobre el fondo del portal--.
            inner: 'relative flex max-h-[90dvh] flex-col rounded-3xl bg-white shadow-2xl border border-gray-200 dark:border-gray-800 dark:bg-gray-900',
        },
        header: {
            base: 'flex items-start justify-between rounded-t-3xl border-b p-6 dark:border-gray-800',
            title: 'text-base font-black text-gray-900 dark:text-white',
        },
        footer: {
            base: 'flex items-center justify-end gap-3 rounded-b-3xl border-t border-gray-100 p-6 dark:border-gray-800',
        },
    },

    textInput: {
        field: {
            input: {
                base: 'block w-full border disabled:cursor-not-allowed disabled:opacity-50 rounded-xl',
                sizes: { sm: 'p-2 text-xs', md: 'px-4 py-2.5 text-xs', lg: 'p-4 text-sm' },
                colors: {
                    gray: 'border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white',
                },
            },
        },
    },

    select: {
        field: {
            select: {
                sizes: { sm: 'p-2 text-xs', md: 'px-4 py-2.5 text-xs', lg: 'p-4 text-sm' },
                withAddon: { off: 'rounded-xl' },
                colors: {
                    gray: 'border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white',
                },
            },
        },
    },

    textarea: {
        base: 'block w-full rounded-xl border px-4 py-2.5 text-xs focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50',
        colors: {
            gray: 'border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white',
        },
    },

    label: {
        root: {
            base: 'block text-xs font-bold mb-1',
            colors: { default: 'text-gray-700 dark:text-gray-300' },
        },
    },

    badge: {
        root: {
            // Las insignias de estado del portal son pastilla completa, diminutas y en
            // mayúsculas: es lo que las distingue de una etiqueta cualquiera.
            base: 'flex h-fit items-center gap-1 font-black uppercase tracking-wide',
            size: { xs: 'px-2.5 py-1 text-[10px]', sm: 'px-3 py-1 text-xs' },
        },
        icon: { off: 'rounded-full' },
    },
});

export default portalTheme;
