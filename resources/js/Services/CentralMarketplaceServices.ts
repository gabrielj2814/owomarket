import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosCentral = axios.create({
    timeout: 20000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface CentralCheckoutItemPayload {
    tenant_id: string;
    product_id: string;
    product_name: string;
    sku?: string;
    price: number;
    quantity: number;
    attributes?: Record<string, string>;
}

export interface CreateCentralOrderPayload {
    customer: {
        id?: string;
        central_uuid?: string;
        name: string;
        email: string;
        phone?: string;
        document_id?: string;
    };
    shipping_address: {
        address: string;
        city: string;
        state?: string;
        zip?: string;
        notes?: string;
    };
    payment_method: 'pago_movil' | 'binance_pay' | 'bank_transfer' | string;
    payment_details?: {
        bank_origin?: string;
        phone_origin?: string;
        reference_number?: string;
        binance_id?: string;
        transaction_hash?: string;
        crypto_currency?: string;
        [key: string]: any;
    };
    shipping_amount?: number;
    discount_amount?: number;
    coupon_code?: string;
    currency?: string;
    items: CentralCheckoutItemPayload[];
}

export interface CentralOrderConfirmationResponse {
    id: string;
    order_number: string;
    status: string;
    payment_status: string;
    payment_method: string;
    payment_details?: any;
    subtotal: number;
    shipping_amount: number;
    discount_amount: number;
    total: number;
    currency: string;
    created_at: string;
    customer: {
        name: string;
        email: string;
        phone?: string;
    };
    shipping_address?: any;
    stores_count: number;
    stores_breakdown: Array<{
        tenant_id: string;
        store_name: string;
        store_domain?: string | null;
        subtotal: number;
        items_count: number;
        items: Array<{
            id: string;
            product_id: string;
            product_name: string;
            sku?: string;
            price: number;
            quantity: number;
            total: number;
            tenant_order_id?: string | null;
            commission_amount?: number;
        }>;
    }>;
}

const CentralMarketplaceServices = {
    createUnifiedOrder: async (
        payload: CreateCentralOrderPayload
    ): Promise<Data<{ order_id: string; order_number: string; total: number; redirect_url: string }>> => {
        try {
            const response = await axiosCentral.post<
                Data<{ order_id: string; order_number: string; total: number; redirect_url: string }>
            >('/api/central/marketplace/checkout/create-order', payload);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al procesar el pedido unificado multi-tienda',
                    data: null as any,
                }
            );
        }
    },

    getOrderConfirmation: async (
        orderIdOrNumber: string
    ): Promise<Data<CentralOrderConfirmationResponse>> => {
        try {
            const response = await axiosCentral.get<Data<CentralOrderConfirmationResponse>>(
                `/api/central/marketplace/order/${orderIdOrNumber}/confirmation`
            );
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al recuperar la confirmación de la orden',
                    data: null as any,
                }
            );
        }
    },
};

export default CentralMarketplaceServices;
