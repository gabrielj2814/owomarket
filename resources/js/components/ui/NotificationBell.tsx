import NotificationServices, { NotificationAudience, NotificationItem } from '@/Services/NotificationServices';
import { Badge, Button, Dropdown, Spinner } from 'flowbite-react';
import React, { useCallback, useEffect, useState } from 'react';
import { HiOutlineBell, HiOutlineCheckCircle, HiOutlineScale, HiOutlineTruck } from 'react-icons/hi2';

/**
 * La campana del buzón.
 *
 * Sustituye a una campana que **era decoración**: estaba dentro de la etiqueta del desplegable
 * del avatar, así que pulsarla abría el menú de perfil. Prometía un buzón que no existía.
 *
 * ## Dos decisiones sobre el ruido
 *
 * **El contador solo aparece si hay algo.** Un «0» permanente entrena a no mirar, y esta campana
 * lleva avisos con un reloj corriendo detrás.
 *
 * **Abrirla no marca nada como leído.** Es tentador —deja el número limpio— pero borraría el
 * rastro de lo que aún no se ha atendido: el comerciante abriría por curiosidad y perdería la
 * única señal de que tiene una reclamación esperando. Se marca al pulsar el aviso, que es cuando
 * de verdad lo ha visto.
 */
interface NotificationBellProps {
    /** Qué buzón: el del personal o el del comprador. Decide la ruta, no lo que se ve. */
    audience: NotificationAudience;
}

const ICONO: Record<string, typeof HiOutlineBell> = {
    'claim.opened': HiOutlineScale,
    'delivery.declared': HiOutlineTruck,
};

const cuando = (iso: string | null) => {
    if (!iso) return '';
    const minutos = Math.round((Date.now() - new Date(iso).getTime()) / 60000);
    if (minutos < 1) return 'ahora';
    if (minutos < 60) return `hace ${minutos} min`;
    const horas = Math.round(minutos / 60);
    if (horas < 24) return `hace ${horas} h`;

    return new Date(iso).toLocaleDateString('es-VE');
};

export default function NotificationBell({ audience }: NotificationBellProps) {
    const [items, setItems] = useState<NotificationItem[]>([]);
    const [unread, setUnread] = useState(0);
    const [cargando, setCargando] = useState(true);
    const [error, setError] = useState(false);

    const cargar = useCallback(async () => {
        setCargando(true);
        setError(false);
        try {
            const res = await NotificationServices.buzon(audience);
            setItems(res.data?.items ?? []);
            setUnread(res.data?.unread ?? 0);
        } catch {
            /*
             * Un fallo de red deja el buzón vacío y sin contador, que se lee igual que «no
             * tienes nada». Es el hallazgo N35 de este repositorio, y aquí importa más: lo que
             * se confunde con silencio es un aviso con un plazo corriendo.
             */
            setError(true);
        } finally {
            setCargando(false);
        }
    }, [audience]);

    useEffect(() => {
        void cargar();
    }, [cargar]);

    const abrir = async (item: NotificationItem) => {
        if (!item.read) {
            // Se marca al pulsar, no al abrir la campana: es cuando de verdad lo ha visto.
            try {
                await NotificationServices.marcarLeido(audience, item.id);
                setUnread((n) => Math.max(0, n - 1));
            } catch {
                // Si no se pudo marcar, el aviso sigue sin leer. Es el fallo inofensivo.
            }
        }

        if (item.url) window.location.href = item.url;
    };

    const marcarTodo = async () => {
        try {
            await NotificationServices.marcarLeido(audience);
            await cargar();
        } catch {
            setError(true);
        }
    };

    return (
        <Dropdown
            arrowIcon={false}
            inline
            label={
                <span className="relative inline-flex" aria-label={unread > 0 ? `${unread} notificaciones sin leer` : 'Notificaciones'}>
                    <HiOutlineBell className="h-9 w-9 cursor-pointer rounded-lg p-2 text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" />
                    {/* Solo si hay algo: un «0» permanente entrena a no mirar. */}
                    {unread > 0 && (
                        <span
                            data-testid="campana-contador"
                            className="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white"
                        >
                            {unread > 9 ? '9+' : unread}
                        </span>
                    )}
                </span>
            }
        >
            <div className="w-80 max-w-[90vw]">
                <div className="flex items-center justify-between border-b border-gray-100 px-4 py-2 dark:border-gray-700">
                    <span className="text-sm font-bold text-gray-900 dark:text-white">Notificaciones</span>
                    {unread > 0 && (
                        <Button color="light" size="xs" onClick={marcarTodo}>
                            Marcar todo como leído
                        </Button>
                    )}
                </div>

                {cargando ? (
                    <div className="px-4 py-8 text-center">
                        <Spinner aria-label="Cargando notificaciones" size="sm" />
                    </div>
                ) : error ? (
                    <div data-testid="campana-error" className="px-4 py-6 text-center text-xs text-gray-500">
                        No pudimos cargar tus notificaciones.{' '}
                        <button type="button" onClick={() => void cargar()} className="font-bold underline">
                            Reintentar
                        </button>
                    </div>
                ) : items.length === 0 ? (
                    <p data-testid="campana-vacia" className="px-4 py-8 text-center text-xs text-gray-500">
                        Aquí aparecerá lo que necesite tu atención.
                    </p>
                ) : (
                    <ul className="max-h-96 divide-y divide-gray-100 overflow-y-auto dark:divide-gray-700">
                        {items.map((item) => {
                            const Icono = ICONO[item.type] ?? HiOutlineCheckCircle;

                            return (
                                <li key={item.id}>
                                    <button
                                        type="button"
                                        data-testid={`aviso-${item.id}`}
                                        onClick={() => void abrir(item)}
                                        className={`flex w-full gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 ${
                                            item.read ? '' : 'bg-blue-50/60 dark:bg-blue-950/30'
                                        }`}
                                    >
                                        <Icono className="mt-0.5 h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" />
                                        <span className="min-w-0">
                                            <span className="block text-xs font-bold text-gray-900 dark:text-white">
                                                {item.title}
                                            </span>
                                            <span className="mt-0.5 block text-xs text-gray-600 dark:text-gray-400">
                                                {item.body}
                                            </span>
                                            <span className="mt-1 flex items-center gap-2">
                                                <span className="text-[10px] text-gray-400">{cuando(item.created_at)}</span>
                                                {/*
                                                  * La cuenta atrás se enseña porque es la razón
                                                  * de que este aviso exista: pasado el plazo, la
                                                  * reclamación se resuelve sola en contra.
                                                  */}
                                                {typeof item.days_left === 'number' && !item.read && (
                                                    <Badge color={item.days_left <= 1 ? 'failure' : 'warning'}>
                                                        {item.days_left === 0
                                                            ? 'Vence hoy'
                                                            : `${item.days_left} ${item.days_left === 1 ? 'día' : 'días'}`}
                                                    </Badge>
                                                )}
                                            </span>
                                        </span>
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </Dropdown>
    );
}
