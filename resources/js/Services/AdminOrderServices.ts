import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

/**
 * Monitor global de pedidos del superadmin.
 *
 * `AdminGlobalOrdersPage` hacía sus cuatro llamadas con `axios` incrustado, contra la regla 1.1
 * de `reglas.md`. Se sacaron aquí las cuatro juntas: mover sólo la nueva y dejar las otras tres
 * dentro habría cumplido la letra de la regla y empeorado el fichero.
 */
const axiosAdminOrders = axios.create({
    baseURL: '/admin/api/orders',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface FilterAdminOrdersParams {
    page?: number;
    per_page?: number;
    search?: string;
    tenant_id?: string;
    status?: string;
    payment_status?: string;
    date_from?: string;
    date_to?: string;
}

export interface ConfirmPaymentBody {
    reference?: string;
    notes?: string;
}

export interface ResolveDisputeBody {
    resolution_type: 'refund' | 'cancel';
    reason: string;
    notes?: string;
}

/**
 * La respuesta de estos endpoints la construye `Src\Shared\Helper\ApiResponse`, así que todas
 * traen la misma envoltura. Se tipa el sobre y no el contenido: la página ya define sus propias
 * formas para `GlobalOrder` y para el detalle, y duplicarlas aquí sería una segunda fuente de
 * verdad para el mismo dato.
 */
interface AdminApiResponse<T = unknown> {
    status: 'success' | 'error';
    code: number;
    message: string;
    data: T;
}

const AdminOrderServices = {
    listar: async <T>(params: FilterAdminOrdersParams = {}): Promise<AdminApiResponse<T>> => {
        const response = await axiosAdminOrders.get<AdminApiResponse<T>>('', { params });

        return response.data;
    },

    detalle: async <T>(orderId: string): Promise<AdminApiResponse<T>> => {
        const response = await axiosAdminOrders.get<AdminApiResponse<T>>(`/${orderId}/detail`);

        return response.data;
    },

    /** Hallazgo A: confirma que el dinero de un pedido central entró en la cuenta de la plataforma. */
    confirmarCobro: async <T>(orderId: string, body: ConfirmPaymentBody = {}): Promise<AdminApiResponse<T>> => {
        const response = await axiosAdminOrders.post<AdminApiResponse<T>>(`/${orderId}/confirm-payment`, body);

        return response.data;
    },

    resolverDisputa: async <T>(orderId: string, body: ResolveDisputeBody): Promise<AdminApiResponse<T>> => {
        const response = await axiosAdminOrders.post<AdminApiResponse<T>>(`/${orderId}/resolve-dispute`, body);

        return response.data;
    },
};

export default AdminOrderServices;
