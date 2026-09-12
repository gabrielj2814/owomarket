import { createTheme } from 'flowbite-react';

/**
 * El aspecto del escaparate y del marketplace central, escrito UNA vez.
 *
 * Tercero de los tres temas, junto a [`portalTheme`](./portalTheme.ts) y
 * [`tenantPanelTheme`](./tenantPanelTheme.ts). Los tres son distintos a propósito: son tres
 * productos con tres audiencias —el comprador en su cuenta, el comerciante en su panel, y
 * cualquiera navegando una tienda— y unificarlos haría que cada pantalla desentonara con sus
 * vecinas en dos de las tres zonas.
 *
 * Lo que distingue a éste: **tarjetas `rounded-2xl`, no `rounded-3xl`**. Es una superficie de
 * catálogo, con muchas tarjetas pequeñas en rejilla; la esquina más suave del portal las hace
 * parecer globos cuando hay veinte en pantalla.
 *
 * ## Esta es la cara pública
 *
 * Se copia el aspecto que el escaparate YA tenía, para que la migración no cambie nada de lo
 * que ven los compradores. Cualquier cambio de diseño va **aparte y después**, en su propio
 * commit, para que se pueda revisar —y revertir— sin arrastrar el refactor con él.
 *
 * **Solo las claves que se apartan del tema original de Flowbite.** `createTheme` fusiona con
 * lo que ya trae.
 */
export const storefrontTheme = createTheme({
    card: {
        root: {
            base: 'flex bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700',
            children: 'flex h-full flex-col justify-center gap-3 p-4',
        },
    },

    button: {
        base: 'group relative flex items-stretch justify-center rounded-xl p-0.5 text-center font-bold transition focus:outline-none focus:ring-2',
        color: {
            primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-300 shadow-md shadow-blue-500/20 dark:focus:ring-blue-800',
            subtle: 'bg-transparent text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white',
            /*
             * El amarillo de Binance, para el camino de pago en USDT. Va aquí y no suelto en el
             * checkout porque aparece en tres sitios --selector de método, resumen y
             * confirmación-- y tres amarillos distintos se notan.
             */
            binance: 'bg-yellow-500 text-gray-900 hover:bg-yellow-600 focus:ring-yellow-300 shadow-md shadow-yellow-500/20',
        },
        size: {
            xs: 'h-8 px-3 text-xs',
            sm: 'h-9 px-4 text-xs',
            md: 'h-10 px-5 text-xs',
            lg: 'h-12 px-6 text-sm',
        },
    },

    modal: {
        content: {
            inner: 'relative flex max-h-[90dvh] flex-col rounded-2xl bg-white shadow-2xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800',
        },
        header: {
            base: 'flex items-start justify-between rounded-t-2xl border-b p-5 dark:border-gray-700',
            title: 'text-base font-black text-gray-900 dark:text-white',
        },
        footer: { base: 'flex items-center justify-end gap-3 rounded-b-2xl border-t border-gray-100 p-5 dark:border-gray-700' },
    },

    textInput: {
        field: {
            input: {
                base: 'block w-full border disabled:cursor-not-allowed disabled:opacity-50 rounded-xl',
                sizes: { sm: 'px-3 py-2 text-xs', md: 'px-3 py-2 text-xs', lg: 'px-4 py-3 text-sm' },
                colors: {
                    gray: 'border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white',
                },
            },
        },
    },

    select: {
        field: {
            select: {
                sizes: { sm: 'px-3 py-2 text-xs', md: 'px-3 py-2 text-xs', lg: 'px-4 py-3 text-sm' },
                withAddon: { off: 'rounded-xl' },
                colors: {
                    gray: 'border-gray-200 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white',
                },
            },
        },
    },

    textarea: {
        base: 'block w-full rounded-xl border px-3 py-2 text-xs focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:opacity-50',
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
            base: 'flex h-fit items-center gap-1 font-black uppercase tracking-wide',
            size: { xs: 'px-2 py-0.5 text-[10px]', sm: 'px-2.5 py-1 text-xs' },
        },
        icon: { off: 'rounded-lg' },
    },

    alert: {
        base: 'flex flex-col gap-2 rounded-2xl border px-4 py-3 text-xs',
        color: {
            success: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300',
            info: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300',
            warning: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
            failure: 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300',
        },
    },
});

export default storefrontTheme;
