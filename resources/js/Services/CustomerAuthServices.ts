import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

export interface CentralCustomerData {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    document_id?: string | null;
    avatar?: string | null;
    addresses?: Array<{
        id: string;
        label: string;
        address: string;
        city: string;
        state?: string | null;
        zip_code?: string | null;
        country: string;
        is_default: boolean;
    }>;
}

export interface RegisterCustomerPayload {
    name: string;
    email: string;
    password: string;
    phone?: string;
    document_id?: string;
}

export interface LoginCustomerPayload {
    email: string;
    password: string;
}

export interface SsoConsumeResult {
    customer: {
        id: string;
        central_uuid: string;
        name: string;
        email: string;
        phone?: string | null;
    };
    central_customer: CentralCustomerData;
    addresses: any[];
}

const axiosCentral = axios.create({
    baseURL: '/api/central/customer/',
    timeout: 15000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

const axiosTenantCustomer = axios.create({
    baseURL: '/api-tenant/customer/',
    timeout: 15000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export const isCentralDomain = (): boolean => {
    if (typeof window === 'undefined') return true;
    const hostname = window.location.hostname;
    const centralList = ['owomarket.local', 'owomarket.test', 'localhost', '127.0.0.1'];
    if (centralList.includes(hostname)) {
        return true;
    }
    const parts = hostname.split('.');
    if (parts.length <= 2) {
        return true;
    }
    return false;
};

export const CustomerAuthServices = {
    // 1. Registro en Base Central
    registerCentral: async (payload: RegisterCustomerPayload): Promise<Data<{ customer: CentralCustomerData; token: string }>> => {
        try {
            const res = await axiosCentral.post<Data<{ customer: CentralCustomerData; token: string }>>('register', payload);
            return res.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión con el servidor central',
                    data: null as any,
                }
            );
        }
    },

    // 2. Login en Base Central
    loginCentral: async (payload: LoginCustomerPayload): Promise<Data<{ customer: CentralCustomerData; token: string }>> => {
        try {
            const res = await axiosCentral.post<Data<{ customer: CentralCustomerData; token: string }>>('login', payload);
            return res.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error de conexión con el servidor central',
                    data: null as any,
                }
            );
        }
    },

    // 3. Generar token SSO desde la Central
    generateSsoToken: async (customerId: string, targetDomain?: string): Promise<Data<{ token: string; expires_at: string; target_domain?: string }>> => {
        try {
            const res = await axiosCentral.post<Data<{ token: string; expires_at: string; target_domain?: string }>>('sso/generate-token', {
                customer_id: customerId,
                target_domain: targetDomain,
            });
            return res.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al generar enlace SSO',
                    data: null as any,
                }
            );
        }
    },

    // 4. Consumir token SSO en el Tenant
    consumeSsoToken: async (token: string): Promise<Data<SsoConsumeResult>> => {
        try {
            const res = await axiosTenantCustomer.post<Data<SsoConsumeResult>>('sso/consume', { token });
            return res.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al validar token en la tienda',
                    data: null as any,
                }
            );
        }
    },

    // 5. Consultar sesión activa en el Tenant
    getTenantSession: async (): Promise<Data<{ authenticated: boolean; customer: any; central_customer_id?: string }>> => {
        try {
            const res = await axiosTenantCustomer.get<Data<{ authenticated: boolean; customer: any; central_customer_id?: string }>>('auth/session');
            return res.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar sesión',
                    data: { authenticated: false, customer: null },
                }
            );
        }
    },

    // 6. Cerrar sesión en el Tenant
    logoutTenant: async (): Promise<Data<null>> => {
        try {
            const res = await axiosTenantCustomer.post<Data<null>>('auth/logout');
            return res.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al cerrar sesión',
                    data: null,
                }
            );
        }
    },
};

export default CustomerAuthServices;
