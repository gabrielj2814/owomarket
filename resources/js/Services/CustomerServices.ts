import { ErrorsFormCustomer } from '@/types/ErrorsFormCustomer';
import { FormCustomer, FormCustomerAddress } from '@/types/FormCustomer';
import { Customer, CustomerMetrics } from '@/types/models/Customer';
import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosCustomer = axios.create({
    baseURL: '/api-tenant/customer/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface PaginatedCustomersData {
    data: Customer[];
    pagination: {
        total: number;
        current_page: number;
        per_page: number;
        last_page: number;
    };
}

const CustomerServices = {
    filtrar: async (
        search: string | null = null,
        isActive: boolean | null = null,
        acceptsMarketing: boolean | null = null,
        gender: string | null = null,
        perPage: number = 15,
        page: number = 1,
        sortBy: string = 'created_at',
        sortDirection: string = 'desc',
    ): Promise<Data<PaginatedCustomersData>> => {
        try {
            const body = {
                search,
                is_active: isActive,
                accepts_marketing: acceptsMarketing,
                gender,
                per_page: perPage,
                page,
                sort_by: sortBy,
                sort_direction: sortDirection,
            };

            const response = await axiosCustomer.post<Data<PaginatedCustomersData>>('filter', body);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión con el servidor',
                    data: {
                        data: [],
                        pagination: { total: 0, current_page: 1, per_page: 15, last_page: 1 },
                    },
                }
            );
        }
    },

    getMetrics: async (): Promise<Data<CustomerMetrics>> => {
        try {
            const response = await axiosCustomer.get<Data<CustomerMetrics>>('metrics');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar métricas',
                    data: {
                        total_customers: 0,
                        active_customers: 0,
                        marketing_subscribers: 0,
                        new_this_month: 0,
                    },
                }
            );
        }
    },

    consultById: async (id: string): Promise<Data<Customer>> => {
        try {
            const response = await axiosCustomer.get<Data<Customer>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar el cliente',
                    data: null as any,
                }
            );
        }
    },

    create: async (data: FormCustomer): Promise<Data<Customer, ErrorsFormCustomer>> => {
        try {
            const response = await axiosCustomer.post<Data<Customer, ErrorsFormCustomer>>('create', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al crear el cliente',
                    data: null as any,
                }
            );
        }
    },

    update: async (id: string, data: Partial<FormCustomer>): Promise<Data<Customer, ErrorsFormCustomer>> => {
        try {
            const response = await axiosCustomer.put<Data<Customer, ErrorsFormCustomer>>(`${id}`, data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al actualizar el cliente',
                    data: null as any,
                }
            );
        }
    },

    delete: async (id: string): Promise<Data<null>> => {
        try {
            const response = await axiosCustomer.delete<Data<null>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al eliminar el cliente',
                    data: null,
                }
            );
        }
    },

    addAddress: async (customerId: string, address: FormCustomerAddress): Promise<Data<Customer, ErrorsFormCustomer>> => {
        try {
            const response = await axiosCustomer.post<Data<Customer, ErrorsFormCustomer>>(`${customerId}/address`, address);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al agregar dirección',
                    data: null as any,
                }
            );
        }
    },

    deleteAddress: async (customerId: string, addressId: string): Promise<Data<Customer>> => {
        try {
            const response = await axiosCustomer.delete<Data<Customer>>(`${customerId}/address/${addressId}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al eliminar dirección',
                    data: null as any,
                }
            );
        }
    },

    setDefaultAddress: async (customerId: string, addressId: string): Promise<Data<Customer>> => {
        try {
            const response = await axiosCustomer.post<Data<Customer>>(`${customerId}/address/${addressId}/default`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al establecer dirección predeterminada',
                    data: null as any,
                }
            );
        }
    },
};

export default CustomerServices;
