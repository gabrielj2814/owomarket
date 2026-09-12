import { describe, expect, it, vi } from 'vitest';

/**
 * La URL del KYC del comerciante.
 *
 * **El test que faltaba.** `TenantKycCard` pedía `/owner/api/kyc/{id}` con axios suelto, pero la
 * ruta real vive bajo el prefijo `tenant`: la petición devolvía 404 siempre, el componente lo
 * tragaba en su `catch` y se pintaba como `null`. La tarjeta no aparecía nunca y **ningún
 * comerciante podía enviar su identidad** — con el KYC exigido para retirar, el cobro quedaba
 * bloqueado desde ese extremo.
 *
 * Los tests del componente no lo veían porque doblaban axios, y un doble de axios responde a
 * cualquier URL. Éste mira la única cosa que aquellos no podían mirar: a dónde apunta de verdad.
 */
const create = vi.fn((_config: unknown) => ({ get: vi.fn(), post: vi.fn() }));

vi.mock('axios', () => ({
    default: { create: (config: unknown) => create(config) },
}));

vi.mock('@/utils/getCSRFToken', () => ({ default: () => 'token-de-prueba' }));

describe('Servicio de KYC del comerciante', () => {
    it('apunta a la ruta que existe de verdad, con su prefijo', async () => {
        await import('@/Services/TenantKycServices');

        expect(create).toHaveBeenCalledWith(expect.objectContaining({ baseURL: '/tenant/owner/api/kyc' }));
    });
});
