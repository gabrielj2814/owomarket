import { ErrorsFormCoupon } from '@/types/ErrorsFormCoupon';
import { FormCoupon } from '@/types/FormCoupon';
import { Coupon } from '@/types/models/Coupon';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosCoupon = axios.create({
    baseURL: '/api-tenant/coupon/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface ValidateCouponPayload {
    code: string;
    order_subtotal: number;
    current_date?: string;
}

export interface ValidateCouponResponse {
    is_valid: boolean;
    discount_amount: number;
    final_total: number;
    message: string;
    coupon?: Coupon | null;
}

const CouponServices = {
    filtrar: async (
        search: string | null = null,
        type: string | null = null,
        isActive: boolean | null = null,
        validDate: string | null = null,
        prePage: number = 10,
        page: number = 1,
        sortBy: string = 'created_at',
        sortDirection: string = 'desc'
    ): Promise<ApiResponse<Coupon[]>> => {
        try {
            const body = {
                search,
                type,
                is_active: isActive,
                valid_date: validDate,
                prePage,
                page,
                sortBy,
                sortDirection,
            };

            const response = await axiosCoupon.post<ApiResponse<Coupon[]>>('filter', body);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: [],
                }
            );
        }
    },

    consultById: async (id: string): Promise<ApiResponse<Coupon>> => {
        try {
            const response = await axiosCoupon.get<ApiResponse<Coupon>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null as any,
                }
            );
        }
    },

    create: async (data: FormCoupon): Promise<ApiResponse<Coupon, ErrorsFormCoupon>> => {
        try {
            const response = await axiosCoupon.post<ApiResponse<Coupon, ErrorsFormCoupon>>('create', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null as any,
                }
            );
        }
    },

    update: async (id: string, data: FormCoupon): Promise<ApiResponse<Coupon, ErrorsFormCoupon>> => {
        try {
            const response = await axiosCoupon.put<ApiResponse<Coupon, ErrorsFormCoupon>>(`${id}`, data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null as any,
                }
            );
        }
    },

    delete: async (id: string): Promise<ApiResponse<null>> => {
        try {
            const response = await axiosCoupon.delete<ApiResponse<null>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null,
                }
            );
        }
    },

    validate: async (data: ValidateCouponPayload): Promise<ApiResponse<ValidateCouponResponse>> => {
        try {
            const response = await axiosCoupon.post<ApiResponse<ValidateCouponResponse>>('validate', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión',
                    data: null as any,
                }
            );
        }
    },
};

export default CouponServices;
