import { ErrorsFormBillingProfile } from '@/types/ErrorsFormBillingProfile';
import { ErrorsFormDirectInvoice } from '@/types/ErrorsFormDirectInvoice';
import { FormBillingProfile } from '@/types/FormBillingProfile';
import { FilterInvoicesParams, FormDirectInvoice } from '@/types/FormDirectInvoice';
import { BillingProfile } from '@/types/models/BillingProfile';
import { Invoice, InvoiceMetrics } from '@/types/models/Invoice';
import { PaymentGateway } from '@/types/models/PaymentGateway';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosBilling = axios.create({
    baseURL: '/api-tenant/billing/',
    timeout: 20000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

const axiosPayment = axios.create({
    baseURL: '/api-tenant/payment/',
    timeout: 15000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

class BillingServices {
    /**
     * Obtiene las métricas agregadas de facturación para el dashboard.
     */
    async getMetrics(): Promise<ApiResponse<InvoiceMetrics>> {
        try {
            const response = await axiosBilling.get<ApiResponse<InvoiceMetrics>>('metrics');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar métricas de facturación',
                    data: null as any,
                }
            );
        }
    }

    /**
     * Filtra y lista facturas paginadas.
     */
    async filterInvoices(params: FilterInvoicesParams = {}): Promise<ApiResponse<Invoice[]>> {
        try {
            const response = await axiosBilling.post<ApiResponse<Invoice[]>>('invoices/filter', params);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar facturas',
                    data: [] as any,
                }
            );
        }
    }

    /**
     * Consulta una factura por su ID.
     */
    async getInvoice(id: string): Promise<ApiResponse<Invoice>> {
        try {
            const response = await axiosBilling.get<ApiResponse<Invoice>>(`invoices/${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar la factura',
                    data: null as any,
                }
            );
        }
    }

    /**
     * Emite una nueva factura directa / mostrador.
     */
    async createDirectInvoice(data: FormDirectInvoice): Promise<ApiResponse<Invoice, ErrorsFormDirectInvoice>> {
        try {
            const response = await axiosBilling.post<ApiResponse<Invoice, ErrorsFormDirectInvoice>>('invoices', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al emitir la factura',
                    data: null as any,
                }
            );
        }
    }

    /**
     * Anula una factura existente.
     */
    async cancelInvoice(id: string, reason?: string): Promise<ApiResponse<Invoice>> {
        try {
            const response = await axiosBilling.post<ApiResponse<Invoice>>(`invoices/${id}/cancel`, { reason });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al anular la factura',
                    data: null as any,
                }
            );
        }
    }

    /**
     * Reenvía la factura por correo electrónico al cliente.
     */
    async resendEmail(id: string, email?: string): Promise<ApiResponse<Invoice>> {
        try {
            const response = await axiosBilling.post<ApiResponse<Invoice>>(`invoices/${id}/resend-email`, { email });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al reenviar la factura por correo',
                    data: null as any,
                }
            );
        }
    }

    /**
     * Descarga el archivo PDF de la factura directamente en el navegador.
     */
    async downloadPdf(id: string, filename?: string): Promise<void> {
        const response = await axiosBilling.get(`invoices/${id}/pdf`, {
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', filename || `factura-${id}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    }

    /**
     * Obtiene el perfil fiscal actual de la tienda.
     */
    async getBillingProfile(): Promise<ApiResponse<BillingProfile>> {
        try {
            const response = await axiosBilling.get<ApiResponse<BillingProfile>>('profile');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar perfil fiscal',
                    data: null as any,
                }
            );
        }
    }

    /**
     * Actualiza o configura los datos fiscales de la tienda.
     */
    async updateBillingProfile(data: FormBillingProfile): Promise<ApiResponse<BillingProfile, ErrorsFormBillingProfile>> {
        try {
            const response = await axiosBilling.put<ApiResponse<BillingProfile, ErrorsFormBillingProfile>>('profile', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al actualizar perfil fiscal',
                    data: null as any,
                }
            );
        }
    }

    /**
     * Obtiene los métodos de pago disponibles en el tenant.
     */
    async getPaymentGateways(): Promise<ApiResponse<PaymentGateway[]>> {
        try {
            const response = await axiosPayment.get<ApiResponse<PaymentGateway[]>>('gateways');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar métodos de pago',
                    data: [] as any,
                }
            );
        }
    }
}

export default new BillingServices();
