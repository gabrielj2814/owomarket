import DeliveryConfirmationPanel from '@/components/ui/customer/DeliveryConfirmationPanel';
import StorefrontLayout, { StorefrontLayoutProps } from '@/components/layouts/StorefrontLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import StorefrontOrderServices, { StorefrontOrder } from '@/Services/StorefrontOrderServices';
import { Alert, Button, Card, Spinner } from 'flowbite-react';
import React, { useCallback, useEffect, useState } from 'react';
import {
    HiOutlineArchiveBox,
    HiOutlineArrowPath,
    HiOutlineCheckCircle,
    HiOutlineShieldCheck,
} from 'react-icons/hi2';

/**
 * «Mis pedidos» dentro del escaparate de una tienda (fase 1 de
 * `planes/por_hacer/PLAN_PEDIDOS_ESCAPARATE.md`).
 *
 * **Sin esta pantalla el subsistema 3 estaba roto en el escaparate y no lo notaba nadie.** El
 * comprador de una tienda no tenía dónde ver lo que había comprado, así que no podía confirmar
 * ninguna entrega: todas las ventas se liberaban por vencimiento del plazo.
 *
 * Tres estados distintos que la pantalla NO puede confundir:
 *
 * - **Sin sesión** — no ha entrado. Se le invita a entrar, no se le dice que no tiene nada.
 * - **Con sesión y sin pedidos** — no ha comprado aquí, o compró como invitado y no hay forma
 *   de demostrar que era él.
 * - **Con pedidos** — la lista, con su panel de confirmación donde toque.
 *
 * `can_confirm` viene resuelto del servidor. Es la misma regla que aplica al recibir la
 * confirmación, así que decidirlo aquí mostraría un botón que el backend rechaza.
 */
interface StorefrontMyOrdersPageProps {
    domain?: string;
    store_settings?: StorefrontLayoutProps['storeSettings'];
    categories?: StorefrontLayoutProps['categories'];
}

const ESTADO: Record<string, string> = {
    pending: 'Pendiente de pago',
    confirmed: 'Confirmado',
    processing: 'En preparación',
    shipped: 'Enviado',
    delivered: 'Entregado',
    cancelled: 'Cancelado',
    refunded: 'Reembolsado',
};

const fecha = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString('es-VE') : '');

export default function StorefrontMyOrdersPage({
    domain = 'localhost',
    store_settings,
    categories = [],
}: StorefrontMyOrdersPageProps) {
    const { customer, openAuthModal } = useCustomerAuth();
    const [orders, setOrders] = useState<StorefrontOrder[]>([]);
    const [loading, setLoading] = useState(true);
    // `null` mientras no se sabe; `true` cuando el servidor ha dicho 401. Distinguirlo de «no
    // tienes pedidos» es la mitad del trabajo de esta pantalla.
    const [needsLogin, setNeedsLogin] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const cargar = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const res = await StorefrontOrderServices.misPedidos();
            setOrders(res.data ?? []);
            setNeedsLogin(false);
        } catch (e: unknown) {
            const status = (e as { response?: { status?: number } }).response?.status;
            if (status === 401) {
                setNeedsLogin(true);
                setOrders([]);
            } else {
                setError('No pudimos cargar tus pedidos. Vuelve a intentarlo.');
            }
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        void cargar();
    }, [cargar, customer?.id]);

    const storeName = store_settings?.store_name || 'esta tienda';

    return (
        <StorefrontLayout
            title="Mis pedidos"
            domain={domain}
            storeSettings={store_settings}
            categories={categories}
        >
            <div className="mx-auto max-w-4xl px-4 py-10 sm:px-6">
                <header className="mb-8">
                    <h1 className="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Mis pedidos</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Lo que has comprado en {storeName}, y dónde confirmar que te llegó.
                    </p>
                </header>

                {error && (
                    <Alert data-testid="pedidos-error" color="failure" role="alert" className="mb-6">
                        {error}
                    </Alert>
                )}

                {loading ? (
                    <div className="py-16 text-center">
                        <Spinner aria-label="Cargando tus pedidos" />
                        <p className="mt-2 text-sm text-gray-400">Cargando tus pedidos…</p>
                    </div>
                ) : needsLogin ? (
                    <Card data-testid="pedidos-sin-sesion" theme={{ root: { children: 'flex h-full flex-col gap-0 p-10 text-center' } }}>
                        <HiOutlineShieldCheck className="mx-auto mb-3 h-12 w-12 text-blue-600" />
                        <h2 className="mb-1 text-base font-bold text-gray-900 dark:text-white">
                            Entra para ver tus pedidos
                        </h2>
                        <p className="mx-auto mb-6 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                            Con tu OwO Pass puedes seguir tus compras en {storeName} y confirmar cuando te lleguen.
                        </p>
                        <Button color="primary" size="md" className="mx-auto w-fit" onClick={() => openAuthModal('login')}>
                            Entrar con OwO Pass
                        </Button>
                    </Card>
                ) : error ? (
                    /*
                      * Un fallo de red NO puede caer en el vacio de abajo. Es el hallazgo N35
                      * de este repositorio, repetido aqui: «no pudimos cargar» y «no has
                      * comprado nada» se ven igual, y el comprador concluye que sus pedidos
                      * desaparecieron. El aviso de arriba ya lo explica; aqui solo hay que no
                      * contradecirlo.
                      */
                    null
                ) : orders.length === 0 ? (
                    <Card data-testid="pedidos-vacio" theme={{ root: { children: 'flex h-full flex-col gap-0 p-10 text-center' } }}>
                        <HiOutlineArchiveBox className="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-700" />
                        <h2 className="mb-1 text-base font-bold text-gray-900 dark:text-white">
                            Todavía no hay pedidos aquí
                        </h2>
                        <p className="mx-auto max-w-md text-sm text-gray-500 dark:text-gray-400">
                            Aparecerán en cuanto compres en {storeName} con la sesión iniciada. Si compraste como
                            invitado, esos pedidos no se pueden mostrar: no hay forma de comprobar que son tuyos.
                        </p>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        <div className="flex justify-end">
                            <Button color="subtle" size="xs" onClick={() => void cargar()}>
                                <HiOutlineArrowPath className="mr-1.5 h-4 w-4" />
                                Actualizar
                            </Button>
                        </div>

                        {orders.map((order) => (
                            <Card
                                key={order.id}
                                data-testid={`pedido-${order.id}`}
                                theme={{ root: { children: 'flex h-full flex-col gap-0 p-6' } }}
                            >
                                <div className="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-100 pb-3 dark:border-gray-800">
                                    <div>
                                        <span className="font-bold text-gray-900 dark:text-white">
                                            {order.order_number}
                                        </span>
                                        <span className="ml-2 text-xs text-gray-400">{fecha(order.created_at)}</span>
                                    </div>
                                    <div className="text-right">
                                        <div className="font-black text-gray-900 dark:text-white">
                                            {order.total.toFixed(2)} {order.currency}
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            {ESTADO[order.status] ?? order.status}
                                        </div>
                                    </div>
                                </div>

                                <ul className="py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {order.items.map((item) => (
                                        <li key={item.id} className="flex justify-between gap-4 py-0.5">
                                            <span>
                                                {item.quantity} × {item.product_name}
                                            </span>
                                            <span className="shrink-0 tabular-nums">{item.price.toFixed(2)}</span>
                                        </li>
                                    ))}
                                </ul>

                                {order.delivery?.released_at && !order.delivery.can_confirm && (
                                    <p
                                        data-testid={`recibido-${order.id}`}
                                        className="flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400"
                                    >
                                        <HiOutlineCheckCircle className="h-4 w-4" />
                                        Entrega cerrada
                                    </p>
                                )}

                                {/*
                                  * El panel es el MISMO del portal central; lo unico que cambia
                                  * es de donde sale la identidad del comprador, y eso viaja en
                                  * el servicio que se le pasa. Copiarlo para cambiar dos URLs
                                  * habria dejado dos versiones que se separan en cuanto alguien
                                  * toque una.
                                  */}
                                {order.delivery?.can_confirm && (
                                    <DeliveryConfirmationPanel
                                        orderId={order.id}
                                        storeName={store_settings?.store_name}
                                        api={StorefrontOrderServices}
                                        onConfirmed={() => void cargar()}
                                    />
                                )}
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </StorefrontLayout>
    );
}
