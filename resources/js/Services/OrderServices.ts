import { ErrorsFormOrder } from '@/types/ErrorsFormOrder';
import { FormOrder } from '@/types/FormOrder';
import { Order, OrderMetrics, OrderStatusType, PaymentStatusType } from '@/types/models/Order';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosOrder = axios.create({
    baseURL: '/api-tenant/order/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface PaginatedOrdersData {
    data: Order[];
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
}

export interface FilterOrdersParams {
    search?: string | null;
    customer_id?: string | null;
    status?: OrderStatusType | string | null;
    payment_status?: PaymentStatusType | string | null;
    start_date?: string | null;
    end_date?: string | null;
    per_page?: number;
    page?: number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}

const OrderServices = {
    filtrar: async (params: FilterOrdersParams = {}): Promise<ApiResponse<PaginatedOrdersData>> => {
        try {
            const body = {
                search: params.search ?? null,
                customer_id: params.customer_id ?? null,
                status: params.status ?? null,
                payment_status: params.payment_status ?? null,
                start_date: params.start_date ?? null,
                end_date: params.end_date ?? null,
                per_page: params.per_page ?? 15,
                page: params.page ?? 1,
                sort_by: params.sort_by ?? 'created_at',
                sort_direction: params.sort_direction ?? 'desc',
            };

            const response = await axiosOrder.post<ApiResponse<PaginatedOrdersData>>('filter', body);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión con el servidor',
                    data: {
                        data: [],
                        total: 0,
                        current_page: 1,
                        per_page: 15,
                        last_page: 1,
                    },
                }
            );
        }
    },

    getMetrics: async (): Promise<ApiResponse<OrderMetrics>> => {
        try {
            const response = await axiosOrder.get<ApiResponse<OrderMetrics>>('metrics');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar métricas de ventas',
                    data: {
                        total_orders: 0,
                        pending_orders: 0,
                        processing_orders: 0,
                        completed_orders: 0,
                        total_sales_amount: 0,
                        average_order_value: 0,
                    },
                }
            );
        }
    },

    consultById: async (id: string): Promise<ApiResponse<Order>> => {
        try {
            const response = await axiosOrder.get<ApiResponse<Order>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar la orden',
                    data: null as any,
                }
            );
        }
    },

    consultByOrderNumber: async (orderNumber: string): Promise<ApiResponse<Order>> => {
        try {
            const response = await axiosOrder.get<ApiResponse<Order>>(`number/${orderNumber}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar la orden por correlativo',
                    data: null as any,
                }
            );
        }
    },

    create: async (data: FormOrder): Promise<ApiResponse<Order, ErrorsFormOrder>> => {
        try {
            const response = await axiosOrder.post<ApiResponse<Order, ErrorsFormOrder>>('create', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al crear la orden de venta',
                    data: null as any,
                }
            );
        }
    },

    updateStatus: async (
        id: string,
        status: OrderStatusType | string,
        shippingMethod?: string | null,
        reason?: string | null
    ): Promise<ApiResponse<Order, ErrorsFormOrder>> => {
        try {
            const body = {
                status,
                shipping_method: shippingMethod ?? null,
                reason: reason ?? null,
            };
            const response = await axiosOrder.post<ApiResponse<Order, ErrorsFormOrder>>(`${id}/status`, body);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al actualizar el estado de la orden',
                    data: null as any,
                }
            );
        }
    },

    cancel: async (id: string, reason?: string | null): Promise<ApiResponse<Order>> => {
        try {
            const body = { reason: reason ?? null };
            const response = await axiosOrder.post<ApiResponse<Order>>(`${id}/cancel`, body);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al anular la orden',
                    data: null as any,
                }
            );
        }
    },

    updatePaymentStatus: async (
        id: string,
        paymentStatus: PaymentStatusType | string
    ): Promise<ApiResponse<Order, ErrorsFormOrder>> => {
        try {
            const body = { payment_status: paymentStatus };
            const response = await axiosOrder.post<ApiResponse<Order, ErrorsFormOrder>>(`${id}/payment-status`, body);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al actualizar el estado de pago',
                    data: null as any,
                }
            );
        }
    },
};

export default OrderServices;
