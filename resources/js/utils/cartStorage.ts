/**
 * Lectura defensiva del carrito guardado en `localStorage` (hallazgo G12).
 *
 * El código anterior era, en los dos contextos de carrito:
 *
 *     const saved = localStorage.getItem(storageKey);
 *     return saved ? JSON.parse(saved) : [];
 *
 * El `try/catch` de alrededor no ayudaba, porque `JSON.parse` **no lanza** con `"null"`,
 * `"{}"` ni con ítems de una versión anterior del carrito. El resultado era que
 * `items.reduce(...)` reventaba con «items.reduce is not a function», o el subtotal
 * quedaba en `NaN` y toda la tienda mostraba «$ NaN» sin forma de recuperarse salvo
 * limpiar el navegador a mano.
 *
 * Dos defensas: se valida después del parseo, ítem a ítem, y la clave va versionada, para
 * que un cambio de forma del carrito descarte lo viejo en vez de intentar interpretarlo.
 */

/** Súbelo cuando cambie la forma de `CartItem` o `CentralCartItem`. */
export const CART_STORAGE_VERSION = 'v2';

export function versionedCartKey(base: string): string {
    return `${base}_${CART_STORAGE_VERSION}`;
}

/**
 * Lee un array del almacenamiento local descartando lo que no encaje.
 * Ante cualquier duda devuelve `[]`: un carrito vacío es recuperable, uno corrupto no.
 */
export function readStoredArray<T>(key: string, isValid: (item: unknown) => item is T): T[] {
    if (typeof window === 'undefined') return [];

    try {
        const raw = localStorage.getItem(key);
        if (!raw) return [];

        const parsed: unknown = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];

        return parsed.filter(isValid);
    } catch {
        return [];
    }
}

/** Lee un objeto suelto (el cupón aplicado) con el mismo criterio. */
export function readStoredObject<T>(key: string, isValid: (value: unknown) => value is T): T | null {
    if (typeof window === 'undefined') return null;

    try {
        const raw = localStorage.getItem(key);
        if (!raw) return null;

        const parsed: unknown = JSON.parse(raw);

        return isValid(parsed) ? parsed : null;
    } catch {
        return null;
    }
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

/** Campos sin los que una línea de carrito no se puede ni mostrar ni pedir. */
function hasUsableCartFields(value: unknown): value is Record<string, unknown> {
    if (!isRecord(value)) return false;

    const { price, quantity } = value;

    return (
        typeof value.id === 'string' &&
        typeof price === 'number' &&
        Number.isFinite(price) &&
        price >= 0 &&
        typeof quantity === 'number' &&
        Number.isFinite(quantity) &&
        quantity > 0
    );
}

export function isStoredCartItem<T>(value: unknown): value is T {
    return hasUsableCartFields(value) && typeof value.productId === 'string';
}

export function isStoredCentralCartItem<T>(value: unknown): value is T {
    return (
        hasUsableCartFields(value) &&
        typeof value.product_id === 'string' &&
        typeof value.tenant_id === 'string'
    );
}

export function isStoredCoupon<T>(value: unknown): value is T {
    if (!isRecord(value)) return false;

    const { code, discountAmount } = value;

    return (
        typeof code === 'string' &&
        code.length > 0 &&
        typeof discountAmount === 'number' &&
        Number.isFinite(discountAmount)
    );
}
