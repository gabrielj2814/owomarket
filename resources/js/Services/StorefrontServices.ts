import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosStorefront = axios.create({
    timeout: 15000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface CreateStorefrontOrderPayload {
    customer: {
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
    shipping_method: string;
    shipping_amount: number;
    payment_method: string;
    payment_details?: {
        bank_origin?: string;
        phone_origin?: string;
        reference_number?: string;
        binance_id?: string;
        transaction_hash?: string;
        [key: string]: any;
    };
    coupon_code?: string;
    items: Array<{
        product_id: string;
        product_name: string;
        sku: string;
        price: number;
        quantity: number;
        variant_id?: string;
        attributes?: Record<string, string>;
    }>;
}

export interface CreateStorefrontOrderResponse {
    order_id: string;
    order_number: string;
    total: number;
    redirect_url: string;
}

export interface RevalidatedCartLine {
    product_id: string;
    variant_id: string | null;
    available: boolean;
    reason: string | null;
    name?: string;
    sku?: string;
    price?: number;
    quantity?: number;
    available_stock?: number | null;
    price_changed?: boolean;
    previous_price?: number | null;
    quantity_reduced?: boolean;
}

export interface RevalidateCartResponse {
    lines: RevalidatedCartLine[];
    has_changes: boolean;
}

const StorefrontServices = {
    /**
     * Hallazgo G4: contrasta el carrito guardado en el navegador con lo que dice la base
     * de la tienda. El precio y el stock se congelaban el dia en que el comprador anadio
     * cada producto y no se revalidaban nunca.
     */
    revalidateCart: async (
        items: Array<{ product_id: string; variant_id?: string | null; quantity: number; price?: number }>,
    ): Promise<Data<RevalidateCartResponse>> => {
        try {
            const response = await axiosStorefront.post<Data<RevalidateCartResponse>>('/cart/revalidate', { items });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'No se pudo comprobar el carrito',
                    data: null as any,
                }
            );
        }
    },

    createOrder: async (payload: CreateStorefrontOrderPayload): Promise<Data<CreateStorefrontOrderResponse>> => {
        try {
            const response = await axiosStorefront.post<Data<CreateStorefrontOrderResponse>>('/checkout/create-order', payload);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al procesar la orden',
                    data: null as any,
                }
            );
        }
    },
};

export default StorefrontServices;
