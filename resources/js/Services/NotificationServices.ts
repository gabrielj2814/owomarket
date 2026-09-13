import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

/**
 * El buzón de notificaciones.
 *
 * **La misma superficie para las tres audiencias, con dos URLs.** El personal —administradores y
 * comerciantes— comparte tabla y guard, así que comparte ruta; el comprador tiene la suya bajo
 * su propio guard. Quién pregunta lo decide siempre el servidor leyendo la sesión: ningún método
 * acepta un identificador de destinatario, porque aceptarlo dejaría leer el buzón de otro —y en
 * estos avisos hay números de pedido, productos comprados y motivos de reclamación—.
 */
const RUTAS = {
    staff: '/api',
    customer: '/api/central/customer',
} as const;

export type NotificationAudience = keyof typeof RUTAS;

/**
 * Lo que pinta un aviso. `type` decide el icono; el resto viene del `data` que guardó cada
 * notificación, así que un aviso nuevo no obliga a tocar la pantalla.
 */
export interface NotificationItem {
    id: string;
    read: boolean;
    created_at: string | null;
    type: string;
    title: string;
    body: string;
    url?: string;
    days_left?: number;
    [extra: string]: unknown;
}

export interface NotificationInbox {
    items: NotificationItem[];
    /** Se calcula en el servidor: el listado viene limitado, así que contar aquí daría un tope. */
    unread: number;
    /**
     * Si recibe también por correo los avisos **opcionales**. Los urgentes —reclamación abierta,
     * entrega declarada, identidad resuelta— salen por correo de todas formas: tienen un reloj o
     * dinero detrás, y la pantalla lo dice para que nadie crea que apagó algo que no apagó.
     */
    email_enabled: boolean;
}

interface Respuesta<T> {
    status: 'success' | 'error';
    code: number;
    message: string;
    data: T;
}

const cliente = (audiencia: NotificationAudience) =>
    axios.create({
        baseURL: RUTAS[audiencia],
        timeout: 15000,
        headers: { 'X-CSRF-TOKEN': getCSRFToken() },
    });

const NotificationServices = {
    buzon: async (audiencia: NotificationAudience) => {
        const res = await cliente(audiencia).get<Respuesta<NotificationInbox>>('/notifications');

        return res.data;
    },

    /** Activa o desactiva los correos opcionales. Los urgentes no se apagan. */
    cambiarCorreo: async (audiencia: NotificationAudience, activado: boolean) => {
        const res = await cliente(audiencia).post<Respuesta<{ email_enabled: boolean }>>('/notifications/email-preference', {
            email_enabled: activado,
        });

        return res.data;
    },

    /** Sin `id` marca el buzón entero. */
    marcarLeido: async (audiencia: NotificationAudience, id?: string) => {
        const res = await cliente(audiencia).post<Respuesta<{ marked: number }>>('/notifications/read', id ? { id } : {});

        return res.data;
    },
};

export default NotificationServices;
