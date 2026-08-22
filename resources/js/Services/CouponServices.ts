import { ErrorsFormCoupon } from '@/types/ErrorsFormCoupon';
import { FormCoupon } from '@/types/FormCoupon';
import { Coupon } from '@/types/models/Coupon';
import { Data } from '@/types/ResponseApi';
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
        sortDirection: string = 'desc',
    ): Promise<Data<Coupon[]>> => {
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

            const response = await axiosCoupon.post<Data<Coupon[]>>('filter', body);
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

    consultById: async (id: string): Promise<Data<Coupon>> => {
        try {
            const response = await axiosCoupon.get<Data<Coupon>>(`${id}`);
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

    create: async (data: FormCoupon): Promise<Data<Coupon, ErrorsFormCoupon>> => {
        try {
            const response = await axiosCoupon.post<Data<Coupon, ErrorsFormCoupon>>('create', data);
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

    update: async (id: string, data: FormCoupon): Promise<Data<Coupon, ErrorsFormCoupon>> => {
        try {
            const response = await axiosCoupon.put<Data<Coupon, ErrorsFormCoupon>>(`${id}`, data);
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

    delete: async (id: string): Promise<Data<null>> => {
        try {
            const response = await axiosCoupon.delete<Data<null>>(`${id}`);
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

    /**
     * Hallazgo G2: devolvia `response.data` —el sobre del backend— pero estaba tipada como
     * `ApiResponse`, que en este proyecto describe la respuesta COMPLETA de axios. El
     * componente desenvolvia una capa de mas y el tipo lo tapaba, asi que la condicion
     * `apiData.code === 200` era falsa siempre y **ningun cupon se podia aplicar**.
     *
     * `Data<T>` es el sobre real: `{ status, code, message, data, meta }`.
     */
    validate: async (data: ValidateCouponPayload): Promise<Data<ValidateCouponResponse>> => {
        try {
            const response = await axiosCoupon.post<Data<ValidateCouponResponse>>('validate', data);
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
