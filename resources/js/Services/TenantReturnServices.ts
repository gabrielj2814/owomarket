import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

/**
 * Reclamaciones de una tienda, lado comerciante (subsistema 5).
 *
 * Sin esta superficie el reloj `returns:auto-resolve` aprobaba **todo** por silencio: resuelve a
 * favor del comprador lo que la tienda no contesta en plazo, y no había dónde contestar.
 */
const axiosReturns = axios.create({
    baseURL: '/tenant/owner/api/returns',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export type TenantReturnStatus = 'requested' | 'in_review' | 'approved' | 'rejected';

export interface TenantReturn {
    id: string;
    order_number: string;
    product_name: string;
    amount: number;
    reason: string;
    description: string | null;
    photos: string[];
    status: TenantReturnStatus;
    is_open: boolean;
    /** `merchant` si la respondió la tienda, `timeout` si la resolvió el reloj por silencio. */
    resolved_by: string | null;
    resolution_notes: string | null;
    /**
     * Días que le quedan a la tienda antes de que se resuelva sola a favor del comprador.
     * Lo calcula el backend: el plazo sale de un ajuste central que el navegador no ve, así
     * que una cuenta atrás inventada aquí prometería días que el comando no respeta.
     * `null` en las ya resueltas.
     */
    days_left: number | null;
    deadline_at: string | null;
    created_at: string | null;
}

interface TenantApiResponse<T = unknown> {
    status: 'success' | 'error';
    code: number;
    message: string;
    data: T;
}

const TenantReturnServices = {
    listar: async (tenantId: string) => {
        const response = await axiosReturns.get<TenantApiResponse<TenantReturn[]>>(`/${tenantId}`);

        return response.data;
    },

    /**
     * Aprobar **revierte la venta**: el importe sale del saldo del comerciante. Rechazar exige
     * motivo (el backend devuelve 422 sin él).
     */
    resolver: async (returnId: string, body: { approved: boolean; notes?: string }) => {
        const response = await axiosReturns.post<TenantApiResponse<{ status: string; resolved_at: string | null }>>(
            `/${returnId}/resolve`,
            body,
        );

        return response.data;
    },
};

export default TenantReturnServices;
