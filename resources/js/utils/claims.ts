/**
 * El vocabulario de una reclamación, en un solo sitio.
 *
 * Desde la fase 2 del escaparate hay **dos pantallas** que enseñan el estado de la misma
 * reclamación: `/account/returns` en el portal central y `/mis-pedidos` dentro de la tienda. La
 * fila que pintan es literalmente la misma —las reclamaciones de escaparate viven en la tabla
 * central— así que dos redacciones distintas del mismo estado no serían un matiz de estilo:
 * serían la plataforma diciéndole dos cosas al mismo comprador sobre el mismo caso.
 *
 * Aquí vive además una frase que ya costó un error: decir «la tienda aceptó tu reclamación»
 * cuando la tienda no contestó le atribuye una decisión que no tomó, y contradice la línea de
 * al lado que explica que venció el plazo. Por eso `explicacionDe` mira también `resolved_by`.
 */

/** Los motivos que se ofrecen al abrir una reclamación. */
export const MOTIVOS_DE_RECLAMACION = [
    'Producto dañado o roto',
    'Producto no coincide con la descripción',
    'Talla o variante incorrecta',
    'Defecto de fábrica',
    'Otro motivo',
] as const;

/**
 * La insignia dice el estado; esta frase dice qué significa.
 *
 * Depende de `resolved_by` y no solo del estado: **una aprobación por silencio no la aprobó la
 * tienda**.
 */
export const explicacionDe = (status: string, resolvedBy: string | null | undefined): string => {
    if (status === 'approved') {
        return resolvedBy === 'merchant'
            ? 'La tienda aceptó tu reclamación: se te devuelve el importe.'
            : 'Tu reclamación se aprobó: se te devuelve el importe.';
    }

    const fijas: Record<string, string> = {
        requested: 'La tienda todavía no ha respondido.',
        in_review: 'La tienda está revisando tu reclamación.',
        rejected: 'La tienda rechazó tu reclamación.',
        refunded: 'El importe ya se te devolvió.',
    };

    return fijas[status] ?? 'Tu reclamación está en curso.';
};

/**
 * Cómo se resolvió, en palabras del comprador.
 *
 * `resolved_by` vale `merchant` o `timeout`. Cualquier otra cosa cae en una frase neutra a
 * propósito: si algún día se guardara ahí un identificador interno, no debe acabar en la
 * pantalla de un cliente.
 */
export const comoSeResolvio = (resolvedBy: string | null | undefined): string | null => {
    if (!resolvedBy) return null;
    if (resolvedBy === 'timeout') {
        return 'La tienda no respondió dentro del plazo, así que se resolvió a tu favor.';
    }
    if (resolvedBy === 'merchant') return 'Respondió la tienda.';

    return 'La resolvió el equipo de OwOMarket.';
};

/**
 * El texto y el color de la insignia de estado.
 *
 * Los colores son los de Flowbite y NO los de ningún tema: esto se pinta en el portal y en el
 * escaparate, que tienen temas distintos, y un color propio de uno saldría sin relleno en el
 * otro sin dar ningún error.
 */
export const INSIGNIA_DE_RECLAMACION: Record<string, { texto: string; color: string }> = {
    approved: { texto: 'Aprobada', color: 'success' },
    refunded: { texto: 'Reembolsada', color: 'purple' },
    rejected: { texto: 'Rechazada', color: 'failure' },
    in_review: { texto: 'En revisión', color: 'blue' },
    requested: { texto: 'Solicitada', color: 'warning' },
};
