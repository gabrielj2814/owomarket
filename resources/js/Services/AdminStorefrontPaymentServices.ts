import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

/**
 * Cobros del escaparate pendientes de confirmar.
 *
 * Desde que la plataforma cobra todas las ventas, el comerciante ya no puede decir si el dinero
 * llegó: no ve ese extracto bancario. El que cobra es el que confirma.
 */
const axiosStorefrontPayments = axios.create({
    baseURL: '/admin/api/storefront-payments',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface PendingPayment {
    id: string;
    tenant_id: string;
    tenant_name: string | null;
    order_id: string;
    order_number: string;
    order_total: number;
    commission_amount: number;
    currency: string;
    exchange_rate: number | null;
    /** Lo que el comprador pagó de verdad. Es lo que hay que buscar en el extracto. */
    total_ves: number | null;
    payment_gateway: string | null;
    /** La referencia que puso el comprador en el checkout. */
    payment_reference: string | null;
    /** La que reportó el comerciante, si el comprador se la pasó por otro canal. Es una pista. */
    reported_reference: string | null;
    source: string | null;
    created_at: string | null;
}

export interface PendingPaymentsPage {
    data: PendingPayment[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface AdminApiResponse<T = unknown> {
    status: 'success' | 'error';
    code: number;
    message: string;
    data: T;
}

const AdminStorefrontPaymentServices = {
    listar: async (params: { tenant_id?: string; search?: string; page?: number; per_page?: number } = {}) => {
        const response = await axiosStorefrontPayments.get<
            AdminApiResponse<{ payments: PendingPaymentsPage; metrics: { pending_count: number; pending_ves: number } }>
        >('', { params });

        return response.data;
    },

    confirmar: async (id: string, body: { reference?: string; notes?: string } = {}) => {
        const response = await axiosStorefrontPayments.post<AdminApiResponse<unknown>>(`/${id}/confirm`, body);

        return response.data;
    },
};

export default AdminStorefrontPaymentServices;
