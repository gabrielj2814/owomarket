import { createTheme } from 'flowbite-react';

/**
 * El aspecto del panel del comerciante, escrito UNA vez.
 *
 * Hermano de [`portalTheme`](./portalTheme.ts) y **deliberadamente distinto**: el panel del
 * comerciante usa `gray-800` como superficie en modo oscuro donde el portal usa `gray-900`, y
 * sus bordes son opacos en vez de semitransparentes. No es un descuido histórico que haya que
 * unificar — son dos productos con dos audiencias, y mezclarlos haría que cada pantalla se
 * viera distinta de sus vecinas en una de las dos zonas.
 *
 * Se copia el aspecto que estas pantallas YA tenían, para que la migración no se note. Un
 * refactor que además cambia cómo se ve todo es imposible de revisar: no se sabe si un cambio
 * de aspecto fue intencionado o un descuido.
 *
 * ## Por qué no se cuelga de `Dashboard`
 *
 * Sería el sitio obvio, pero `Dashboard` lo comparten el panel del comerciante Y el backoffice
 * del administrador, que usa el aspecto por defecto de Flowbite. Colgarlo ahí repintaría el
 * backoffice entero de rebote.
 *
 * Lo aplica `TenantOwnerShell`, que es lo que estas pantallas tenían en común de todas formas:
 * el layout, las pestañas y ahora el tema.
 *
 * **Solo las claves que se apartan del tema original de Flowbite.** `createTheme` fusiona con
 * lo que ya trae, así que repetir un valor que ya es el que queremos solo añade una línea que
 * mantener.
 */
export const tenantPanelTheme = createTheme({
    card: {
        root: {
            base: 'flex bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-200 dark:border-gray-700',
            children: 'flex h-full flex-col justify-center gap-4 p-6',
        },
    },

    button: {
        base: 'group relative flex items-stretch justify-center rounded-xl p-0.5 text-center font-bold transition focus:outline-none focus:ring-2',
        color: {
            primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-300 shadow-md shadow-blue-500/20 dark:focus:ring-blue-800',
            subtle: 'bg-transparent text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white',
            /*
             * Ámbar suave, para lo que retira o pausa algo sin destruirlo --quitar un producto
             * del escaparate central, por ejemplo--.
             *
             * Se llama `warning` y se define AQUÍ porque en flowbite-react 0.12 ese nombre
             * **no existe como color de botón**: solo vale en las insignias. Un color
             * inexistente deja el botón sin relleno, sin error y sin que nadie lo reporte.
             */
            warning: 'bg-amber-100 text-amber-800 hover:bg-amber-200 focus:ring-amber-200 shadow-sm dark:bg-amber-950/80 dark:text-amber-300 dark:hover:bg-amber-900',
        },
        size: {
            xs: 'h-8 px-3 text-xs',
            sm: 'h-9 px-4 text-xs',
            md: 'h-10 px-5 text-xs',
        },
    },

    modal: {
        content: {
            inner: 'relative flex max-h-[90dvh] flex-col rounded-3xl bg-white shadow-2xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800',
        },
        header: {
            base: 'flex items-start justify-between rounded-t-3xl border-b p-6 dark:border-gray-700',
            title: 'text-base font-black text-gray-900 dark:text-white',
        },
        footer: {
            base: 'flex items-center justify-end gap-3 rounded-b-3xl border-t border-gray-100 p-6 dark:border-gray-700',
        },
    },

    textInput: {
        field: {
            input: {
                base: 'block w-full border disabled:cursor-not-allowed disabled:opacity-50 rounded-xl',
                sizes: { sm: 'p-2 text-xs', md: 'p-2.5 text-xs', lg: 'p-4 text-sm' },
                colors: {
                    gray: 'border-gray-300 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white',
                },
            },
        },
    },

    select: {
        field: {
            select: {
                sizes: { sm: 'p-2 text-xs', md: 'p-2.5 text-xs', lg: 'p-4 text-sm' },
                withAddon: { off: 'rounded-xl' },
                colors: {
                    gray: 'border-gray-300 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white',
                },
            },
        },
    },

    textarea: {
        base: 'block w-full rounded-xl border p-2.5 text-xs focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50',
        colors: {
            gray: 'border-gray-300 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white',
        },
    },

    label: {
        root: {
            base: 'block text-xs font-bold mb-1',
            colors: { default: 'text-gray-700 dark:text-gray-300' },
        },
    },

    alert: {
        /*
         * Los tres tonos del nivel de reputacion. Se definen aqui y no en el componente porque
         * el color NO es decorativo: codifica el nivel, y con el la retencion que se aplica a
         * cada venta. Los de Flowbite (`info` es cian, `warning` amarillo) no son los que el
         * panel venia usando, y cambiarlos haria que el comerciante viera otro color para el
         * mismo estado sin que nada hubiera cambiado.
         */
        base: 'flex flex-col gap-2 rounded-2xl border px-4 py-3 text-xs',
        color: {
            success: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300',
            info: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300',
            warning: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
            failure: 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300',
        },
    },

    table: {
        root: { base: 'w-full text-left text-xs text-gray-500 dark:text-gray-400' },
        head: {
            base: 'group/head text-[11px] font-black uppercase tracking-wider text-gray-400',
            cell: {
                base: 'bg-gray-50 px-4 py-3 group-first/head:first:rounded-l-xl group-first/head:last:rounded-r-xl dark:bg-gray-900/50',
            },
        },
        body: { cell: { base: 'px-4 py-4' } },
        row: { base: 'group/row', hovered: 'hover:bg-gray-50 dark:hover:bg-gray-700/30' },
    },

    badge: {
        root: {
            base: 'flex h-fit items-center gap-1 font-black uppercase tracking-wide',
            size: { xs: 'px-2.5 py-1 text-[10px]', sm: 'px-3 py-1 text-xs' },
        },
        icon: { off: 'rounded-full' },
    },
});

export default tenantPanelTheme;
