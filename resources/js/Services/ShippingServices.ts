import { ErrorsFormShippingRate } from '@/types/ErrorsFormShippingRate';
import { ErrorsFormShippingZone } from '@/types/ErrorsFormShippingZone';
import { FormShippingRate } from '@/types/FormShippingRate';
import { FormShippingZone } from '@/types/FormShippingZone';
import { ShippingRate } from '@/types/models/ShippingRate';
import { ShippingZone } from '@/types/models/ShippingZone';
import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosShipping = axios.create({
    baseURL: '/api-tenant/shipping/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface CalculateShippingPayload {
    order_value: number;
    total_weight?: number;
    country?: string | null;
    state?: string | null;
    postal_code?: string | null;
}

export interface ShippingOption {
    zone_id: string;
    zone_name: string;
    rate_id: string;
    name: string;
    type: string;
    cost: number;
}

export interface CalculateShippingResponse {
    options: ShippingOption[];
    recommended_option?: ShippingOption | null;
}

const ShippingServices = {
    filtrarZonas: async (
        search: string | null = null,
        isActive: boolean | null = null,
        prePage: number = 10,
        page: number = 1,
        sortBy: string = 'priority',
        sortDirection: string = 'asc',
    ): Promise<Data<ShippingZone[]>> => {
        try {
            const body = {
                search,
                is_active: isActive,
                prePage,
                page,
                sortBy,
                sortDirection,
            };

            const response = await axiosShipping.post<Data<ShippingZone[]>>('zones/filter', body);
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

    consultarZonaPorId: async (id: string): Promise<Data<ShippingZone>> => {
        try {
            const response = await axiosShipping.get<Data<ShippingZone>>(`zones/${id}`);
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

    crearZona: async (data: FormShippingZone): Promise<Data<ShippingZone, ErrorsFormShippingZone>> => {
        try {
            const response = await axiosShipping.post<Data<ShippingZone, ErrorsFormShippingZone>>('zones/create', data);
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

    actualizarZona: async (id: string, data: FormShippingZone): Promise<Data<ShippingZone, ErrorsFormShippingZone>> => {
        try {
            const response = await axiosShipping.put<Data<ShippingZone, ErrorsFormShippingZone>>(`zones/${id}`, data);
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

    eliminarZona: async (id: string): Promise<Data<null>> => {
        try {
            const response = await axiosShipping.delete<Data<null>>(`zones/${id}`);
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

    crearTarifa: async (shippingZoneId: string, data: FormShippingRate): Promise<Data<ShippingRate, ErrorsFormShippingRate>> => {
        try {
            const response = await axiosShipping.post<Data<ShippingRate, ErrorsFormShippingRate>>(`zones/${shippingZoneId}/rates`, data);
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

    eliminarTarifa: async (rateId: string): Promise<Data<null>> => {
        try {
            const response = await axiosShipping.delete<Data<null>>(`rates/${rateId}`);
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

    calcularEnvio: async (data: CalculateShippingPayload): Promise<Data<CalculateShippingResponse>> => {
        try {
            const response = await axiosShipping.post<Data<CalculateShippingResponse>>('calculate', data);
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

export default ShippingServices;
