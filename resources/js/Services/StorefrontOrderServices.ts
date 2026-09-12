import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

/**
 * Los pedidos del comprador dentro del escaparate de una tienda (fase 1 del plan del escaparate).
 *
 * Gemelo de la parte de pedidos de `CustomerPortalServices`, con una diferencia: estas rutas
 * viven en el dominio de la tienda y la identidad sale de la sesión que dejó el SSO, no del
 * guard central. Por eso son URLs distintas y no un parámetro más.
 *
 * **Ningún método acepta un identificador de comprador.** Quién pregunta lo decide el servidor
 * leyendo su sesión: aceptarlo por parámetro dejaría que cualquiera pidiera los pedidos de otro.
 */
const axiosStorefront = axios.create({
    baseURL: '/api-tenant/storefront',
    timeout: 15000,
    headers: {
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface StorefrontOrderItem {
    id: string;
    product_id: string;
    product_name: string;
    quantity: number;
    price: number;
}

export interface StorefrontOrder {
    id: string;
    order_number: string;
    status: string;
    total: number;
    currency: string;
    created_at: string | null;
    items: StorefrontOrderItem[];
    /**
     * El expediente de entrega, si la tienda ya registró algo. `can_confirm` lo decide el
     * servidor: es la misma regla que aplica al recibir la confirmación, así que calcularla
     * aquí acabaría mostrando un botón que el backend rechaza.
     */
    delivery: {
        declared_delivered_at: string | null;
        confirmed_at: string | null;
        released_at: string | null;
        can_confirm: boolean;
    } | null;
}

interface StorefrontApiResponse<T = unknown> {
    status: 'success' | 'error';
    code: number;
    message: string;
    data: T;
}

const StorefrontOrderServices = {
    misPedidos: async () => {
        const response = await axiosStorefront.get<StorefrontApiResponse<StorefrontOrder[]>>('/my-orders');

        return response.data;
    },

    getDeliveryStatus: async (orderId: string) => {
        const response = await axiosStorefront.get(`/deliveries/${orderId}`);

        return response.data;
    },

    /** La evidencia es opcional: se pide, no se impone. */
    confirmDelivery: async (orderId: string, files: File[] = []) => {
        const form = new FormData();
        files.forEach((file) => form.append('evidence[]', file));

        const response = await axiosStorefront.post(`/deliveries/${orderId}/confirm`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        return response.data;
    },
};

export default StorefrontOrderServices;
