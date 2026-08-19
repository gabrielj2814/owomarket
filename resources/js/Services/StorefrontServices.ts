import { ApiResponse } from '@/types/ResponseApi';
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

const StorefrontServices = {
    createOrder: async (
        payload: CreateStorefrontOrderPayload
    ): Promise<ApiResponse<CreateStorefrontOrderResponse>> => {
        try {
            const response = await axiosStorefront.post<ApiResponse<CreateStorefrontOrderResponse>>(
                '/checkout/create-order',
                payload
            );
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
