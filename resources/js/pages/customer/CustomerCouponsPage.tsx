import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import CustomerPortalServices, { CustomerCouponData } from '@/Services/CustomerPortalServices';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import { Head } from '@inertiajs/react';
import { Badge, Button, Card } from 'flowbite-react';
import React, { useEffect, useState } from 'react';
import {
    HiOutlineClipboardDocumentCheck,
    HiOutlineClock,
    HiOutlineSparkles,
    HiOutlineTicket,
} from 'react-icons/hi2';

/**
 * Cupones del comprador.
 *
 * El aspecto lo pone `portalTheme` desde `CustomerAccountLayout`: aquí se escribe `<Card>` y
 * `<Button color="primary">` a secas. Si algo necesitara un `className` para parecerse a sus
 * hermanas, el sitio de arreglarlo es el tema.
 */
export const CustomerCouponsPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [coupons, setCoupons] = useState<CustomerCouponData[]>([]);
    const [loading, setLoading] = useState(true);
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);

    useEffect(() => {
        CustomerPortalServices.getCoupons()
            .then((res) => {
                if (res?.data) {
                    setCoupons(res.data);
                }
            })
            .catch(() => setLoadError(true))
            .finally(() => setLoading(false));
    }, [customer?.id]);

    // Hallazgo C2: era un alert(), que bloquea el hilo y hay que descartar a mano para
    // seguir. Un aviso que se desvanece encaja mejor en «he copiado algo».
    const [copiado, setCopiado] = useState<string | null>(null);

    const copyCode = (code: string) => {
        void navigator.clipboard.writeText(code);
        setCopiado(code);
        setTimeout(() => setCopiado((prev) => (prev === code ? null : prev)), 2500);
    };

    return (
        <CustomerAccountLayout
            title="Mis Cupones & Descuentos"
            description="Aprovecha los cupones activos y beneficios exclusivos para compras en OwOMarket."
        >
            {loadError && <PortalLoadError />}

            <Head title="Mis Cupones - OwOMarket" />

            <div className="mb-6 flex items-center justify-between">
                <h3 className="flex items-center gap-2 text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                    <HiOutlineTicket className="h-5 w-5 text-emerald-600" />
                    Cupones Disponibles ({coupons.length})
                </h3>
            </div>

            {copiado && (
                <div
                    role="status"
                    className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300"
                >
                    Cupón {copiado} copiado. Aplícalo en el carrito de compras.
                </div>
            )}

            {/*
              * Hallazgo C1: hasta ahora esta lista nunca llegaba vacía porque el servidor
              * devolvía tres promociones inventadas que el checkout rechazaba. Al quitarlas
              * puede quedar vacía de verdad, y sin este bloque se veía «Cupones Disponibles
              * (0)» sobre un hueco en blanco, que parece una página rota.
              */}
            {!loading && !loadError && coupons.length === 0 && (
                <Card>
                    <div className="py-2 text-center">
                        <HiOutlineTicket className="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-700" />
                        <p className="mb-1 text-sm font-bold text-gray-900 dark:text-white">
                            No hay cupones disponibles
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Cuando haya promociones activas aparecerán aquí.
                        </p>
                    </div>
                </Card>
            )}

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {coupons.map((coupon) => (
                    <Card
                        key={coupon.id}
                        /*
                          * El tema centra el contenido de una tarjeta; estas van en rejilla y
                          * necesitan que el código quede pegado abajo para que dos cupones de
                          * distinto largo no descoloquen sus botones.
                          */
                        theme={{ root: { children: 'flex h-full flex-col justify-between gap-4 p-6' } }}
                    >
                        <div>
                            <div className="mb-3 flex items-center justify-between">
                                <Badge color="success" size="xs" icon={HiOutlineSparkles}>
                                    {coupon.badge}
                                </Badge>
                                <span className="flex items-center gap-1 text-[11px] font-semibold text-gray-400">
                                    <HiOutlineClock className="h-3.5 w-3.5" /> Vence: {coupon.valid_until}
                                </span>
                            </div>

                            <h4 className="mb-1 text-sm font-black text-gray-900 dark:text-white">{coupon.title}</h4>
                            <p className="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                                {coupon.description}
                            </p>
                        </div>

                        <div className="flex items-center justify-between rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/60">
                            <div>
                                <span className="block text-[10px] font-bold uppercase text-gray-400">Código:</span>
                                <span className="font-mono text-sm font-black tracking-wider text-blue-600 dark:text-blue-400">
                                    {coupon.code}
                                </span>
                            </div>

                            <Button color="primary" size="xs" onClick={() => copyCode(coupon.code)}>
                                <HiOutlineClipboardDocumentCheck className="mr-1.5 h-4 w-4" />
                                Copiar
                            </Button>
                        </div>
                    </Card>
                ))}
            </div>
        </CustomerAccountLayout>
    );
};

export default CustomerCouponsPage;
