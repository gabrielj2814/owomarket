import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

/**
 * Verificación de identidad, lado comerciante (subsistema 1).
 *
 * ## Por qué existe este fichero
 *
 * `TenantKycCard` llamaba a `/owner/api/kyc/...` con axios suelto. La ruta real vive bajo el
 * prefijo `tenant`, así que **esa petición devolvía 404 siempre**; el componente lo tragaba en
 * un `catch` y se pintaba a sí mismo como `null`.
 *
 * El efecto: la tarjeta no aparecía nunca y **ningún comerciante podía enviar su identidad**.
 * Con el KYC exigido para retirar, eso dejaba el cobro bloqueado desde el otro extremo — dar al
 * administrador una pantalla para verificar no sirve de nada si nadie puede enviar nada que
 * verificar.
 *
 * Centralizar la URL aquí, como manda `reglas.md`, es lo que impide que vuelva a pasar: un
 * prefijo equivocado deja de estar escondido dentro de un componente.
 */
const axiosKyc = axios.create({
    baseURL: '/tenant/owner/api/kyc',
    timeout: 10000,
    headers: {
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export type TenantKycStatus = 'missing' | 'pending' | 'verified' | 'rejected';

export interface TenantKycState {
    status: TenantKycStatus;
    legal_name?: string | null;
    phone?: string | null;
    address?: string | null;
    has_document: boolean;
    rejection_reason?: string | null;
}

interface TenantApiResponse<T = unknown> {
    status: 'success' | 'error';
    code: number;
    message: string;
    data: T;
}

const TenantKycServices = {
    estado: async (tenantId: string) => {
        const response = await axiosKyc.get<TenantApiResponse<TenantKycState>>(`/${tenantId}`);

        return response.data;
    },

    /**
     * Va como `multipart/form-data` porque admite la foto del documento. El número de cédula
     * sube, pero no vuelve: el backend no lo devuelve nunca.
     */
    enviar: async (tenantId: string, datos: FormData) => {
        const response = await axiosKyc.post<TenantApiResponse<unknown>>(`/${tenantId}`, datos, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        return response.data;
    },
};

export default TenantKycServices;
