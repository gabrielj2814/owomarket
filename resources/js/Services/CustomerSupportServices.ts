import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

/**
 * Mesa de soporte del cliente (hallazgo G14).
 *
 * `CustomerSupportPage` llamaba a `axios` directamente desde el componente, violando la
 * regla 1 de frontend de `reglas.md`. Y no era sólo una cuestión de estilo: esas llamadas
 * iban **sin `X-CSRF-TOKEN`**, a diferencia del resto del proyecto, y sin manejar el caso
 * `status !== 'success'` — el usuario pulsaba «Enviar», no veía ningún aviso y reenviaba,
 * generando tickets duplicados.
 */
const axiosSupport = axios.create({
    baseURL: '/api/support/',
    timeout: 20000,
    headers: {
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

function fallback<T>(error: any, message: string): Data<T> {
    return (
        error?.response?.data || {
            status: 'error',
            code: error?.response?.status ?? 500,
            message,
            data: null as any,
        }
    );
}

const CustomerSupportServices = {
    createTicket: async (payload: FormData): Promise<Data<any>> => {
        try {
            const res = await axiosSupport.post<Data<any>>('tickets', payload);
            return res.data;
        } catch (error: any) {
            return fallback(error, 'No se pudo enviar el reporte.');
        }
    },

    addMessage: async (ticketId: string, payload: FormData): Promise<Data<any>> => {
        try {
            const res = await axiosSupport.post<Data<any>>(`tickets/${ticketId}/messages`, payload);
            return res.data;
        } catch (error: any) {
            return fallback(error, 'No se pudo enviar el mensaje.');
        }
    },

    getTicket: async (ticketId: string): Promise<Data<any>> => {
        try {
            // El `user_id` ya no viaja en la URL: desde la Fase 0.3-C el backend resuelve
            // la identidad desde la sesión, y pasarlo a mano era el vector de IDOR de A6.
            const res = await axiosSupport.get<Data<any>>(`tickets/${ticketId}`);
            return res.data;
        } catch (error: any) {
            return fallback(error, 'No se pudo cargar el ticket.');
        }
    },
};

export default CustomerSupportServices;
