import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import TenantServices from '@/Services/TenantServices';
import Dashboard from '@/components/layouts/Dashboard';
import TenantOwnerNavTabs from '@/components/tenant/TenantOwnerNavTabs';
import {
    HiOutlineCreditCard,
    HiOutlineCheck,
    HiOutlineSparkles,
    HiOutlineDocumentArrowDown,
    HiOutlineBuildingStorefront,
    HiOutlineReceiptPercent,
} from 'react-icons/hi2';

interface TenantOwnerBillingPageProps {
    title?: string;
    user_id: string;
    tenants: Array<{ id: string; name: string; slug: string }>;
    subscriptions: Array<{
        id: string;
        tenant_id: string;
        plan?: {
            id: string;
            name: string;
            price_monthly: number;
            commission_rate: number;
            max_products: number;
            features?: string[];
        };
        status: string;
        current_period_end?: string;
    }>;
    available_plans: Array<{
        id: string;
        name: string;
        slug: string;
        price_monthly: number;
        commission_rate: number;
        max_products: number;
        features?: string[];
    }>;
    // Hallazgo T3: solicitudes ya en curso, para no dejar pedir dos veces lo mismo.
    pending_plan_changes?: Array<{ id: string; tenant_id: string; requested_plan_id: string }>;
}

export const TenantOwnerBillingPage: React.FC<TenantOwnerBillingPageProps> = ({
    user_id,
    tenants,
    subscriptions,
    available_plans,
    pending_plan_changes = [],
}) => {
    /*
     * Hallazgo T3. Este boton era `onClick={() => alert('Solicitud de cambio de plan
     * registrada. Un asesor te contactara')}` y no mandaba absolutamente nada: no existia
     * endpoint ni tabla. El comerciante pulsaba, leia que su solicitud quedaba registrada, y
     * esperaba una llamada que nadie iba a hacer. Sin momento en el que enterarse.
     *
     * Ahora crea una solicitud de verdad, que un administrador resuelve desde su panel.
     */
    const [pendientes, setPendientes] = useState(pending_plan_changes);
    const [enviando, setEnviando] = useState<string | null>(null);
    // Que plan quiere cada tienda. El boton original decia «Mejorar Plan» sin decir a cual:
    // no habia nada que elegir, y por eso podia ser un alert() sin mas.
    const [planElegido, setPlanElegido] = useState<Record<string, string>>({});
    const [aviso, setAviso] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const tienePendiente = (tenantId: string) => pendientes.some(p => p.tenant_id === tenantId);

    const solicitarCambio = async (tenantId: string, planId: string) => {
        setAviso(null);
        setEnviando(tenantId);

        const respuesta = await TenantServices.solicitarCambioDePlan(tenantId, planId);
        setEnviando(null);

        const creada = respuesta?.data?.code === 201 || respuesta?.status === 201;

        if (!creada) {
            setAviso({
                type: 'error',
                text: respuesta?.response?.data?.message || 'No se pudo enviar la solicitud de cambio de plan.',
            });
            return;
        }

        setPendientes(prev => [...prev, { id: 'nueva', tenant_id: tenantId, requested_plan_id: planId }]);
        setAviso({
            type: 'success',
            text: 'Solicitud enviada. Te avisaremos cuando la revisemos.',
        });
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title="Suscripciones & Facturas B2B - OwOMarket" />

            <div className="p-4 sm:p-6 space-y-6">
                <TenantOwnerNavTabs userId={user_id} activeTab="billing" />

                {/* Hallazgo T3: el resultado de la solicitud, en linea. Antes era un alert()
                    que mentia sobre lo que habia pasado. */}
                {aviso && (
                    <div
                        role={aviso.type === 'error' ? 'alert' : 'status'}
                        className={`p-3 rounded-2xl text-xs font-bold border ${
                            aviso.type === 'error'
                                ? 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800'
                                : 'bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800'
                        }`}
                    >
                        {aviso.text}
                    </div>
                )}

                {/* Header */}
                <div className="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <h1 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <HiOutlineCreditCard className="w-7 h-7 text-indigo-600 dark:text-indigo-400" />
                        Suscripciones de Tiendas & Planes de Monetización
                    </h1>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Monitorea el plan asignado a cada una de tus tiendas, cuota de productos y esquema de comisiones por venta.
                    </p>
                </div>

                {/* Tiendas y Planes Asignados */}
                <div className="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                    <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <HiOutlineBuildingStorefront className="w-5 h-5 text-blue-600" />
                        Tus Tiendas Activas ({tenants.length})
                    </h3>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {tenants.map(tenant => {
                            const sub = subscriptions.find(s => s.tenant_id === tenant.id);
                            const plan = sub?.plan || {
                                name: 'Plan Inicial (Gratuito)',
                                price_monthly: 0,
                                commission_rate: 5.0,
                                max_products: 50,
                            };

                            return (
                                <div
                                    key={tenant.id}
                                    className="p-5 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-750/30 space-y-4 flex flex-col justify-between"
                                >
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <h4 className="font-bold text-sm text-gray-900 dark:text-white">
                                                {tenant.name}
                                            </h4>
                                            <span className="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300">
                                                Activa
                                            </span>
                                        </div>

                                        <div className="p-3 rounded-xl bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/80 space-y-1">
                                            <div className="text-xs font-bold text-blue-600 dark:text-blue-400">
                                                {plan.name}
                                            </div>
                                            <div className="text-[11px] text-gray-500">
                                                Comisión Marketplace: <strong>{plan.commission_rate}%</strong>
                                            </div>
                                            <div className="text-[11px] text-gray-500">
                                                Límite de Catálogo: <strong>Hasta {plan.max_products} productos</strong>
                                            </div>
                                        </div>
                                    </div>

                                    {tienePendiente(tenant.id) ? (
                                        <div className="w-full py-2 px-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 text-xs font-bold text-center border border-amber-200 dark:border-amber-800">
                                            Cambio de plan pendiente de revisión
                                        </div>
                                    ) : (
                                        <div className="space-y-2">
                                            <select
                                                value={planElegido[tenant.id] ?? ''}
                                                onChange={(e) => setPlanElegido(prev => ({ ...prev, [tenant.id]: e.target.value }))}
                                                className="w-full text-xs rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white py-2 px-3"
                                            >
                                                <option value="">Cambiar a otro plan…</option>
                                                {available_plans
                                                    .filter(p => p.id !== sub?.plan?.id)
                                                    .map(p => (
                                                        <option key={p.id} value={p.id}>
                                                            {p.name} — {p.commission_rate}% de comisión
                                                        </option>
                                                    ))}
                                            </select>

                                            <button
                                                onClick={() => solicitarCambio(tenant.id, planElegido[tenant.id])}
                                                disabled={!planElegido[tenant.id] || enviando === tenant.id}
                                                className="w-full py-2 px-3 rounded-xl bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-600 dark:text-blue-400 text-xs font-bold transition flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <HiOutlineSparkles className="w-3.5 h-3.5" />
                                                <span>{enviando === tenant.id ? 'Enviando…' : 'Solicitar cambio de plan'}</span>
                                            </button>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Tabla de Planes Disponibles */}
                <div className="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-6">
                    <div>
                        <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <HiOutlineReceiptPercent className="w-5 h-5 text-indigo-600" />
                            Comparativa de Planes de la Plataforma
                        </h3>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Puedes asignar planes diferentes a cada una de tus tiendas según su volumen de ventas.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {available_plans.length > 0 ? (
                            available_plans.map(plan => (
                                <div key={plan.id} className="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 space-y-4">
                                    <div>
                                        <h4 className="font-black text-base text-gray-900 dark:text-white">{plan.name}</h4>
                                        <div className="text-2xl font-black text-blue-600 mt-1">${plan.price_monthly.toFixed(2)} USD/mes</div>
                                    </div>
                                    <ul className="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                        <li className="flex items-center gap-1.5">
                                            <HiOutlineCheck className="w-4 h-4 text-emerald-500 shrink-0" />
                                            Comisión por venta: {plan.commission_rate}%
                                        </li>
                                        <li className="flex items-center gap-1.5">
                                            <HiOutlineCheck className="w-4 h-4 text-emerald-500 shrink-0" />
                                            Hasta {plan.max_products} productos
                                        </li>
                                    </ul>
                                </div>
                            ))
                        ) : (
                            <>
                                <div className="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 space-y-4">
                                    <h4 className="font-black text-base text-gray-900 dark:text-white">Plan Inicial</h4>
                                    <div className="text-2xl font-black text-blue-600">$0.00 USD/mes</div>
                                    <ul className="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                        <li className="flex items-center gap-1.5"><HiOutlineCheck className="w-4 h-4 text-emerald-500" /> Comisión: 5.0% por venta</li>
                                        <li className="flex items-center gap-1.5"><HiOutlineCheck className="w-4 h-4 text-emerald-500" /> Hasta 50 productos</li>
                                        <li className="flex items-center gap-1.5"><HiOutlineCheck className="w-4 h-4 text-emerald-500" /> Subdominio dedicado</li>
                                    </ul>
                                </div>
                                <div className="p-6 rounded-2xl border-2 border-blue-500 bg-blue-50/20 dark:bg-blue-950/30 space-y-4 shadow-lg shadow-blue-500/10">
                                    <div className="flex items-center justify-between">
                                        <h4 className="font-black text-base text-gray-900 dark:text-white">Plan Profesional</h4>
                                        <span className="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-blue-600 text-white">Popular</span>
                                    </div>
                                    <div className="text-2xl font-black text-blue-600">$19.99 USD/mes</div>
                                    <ul className="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                        <li className="flex items-center gap-1.5"><HiOutlineCheck className="w-4 h-4 text-emerald-500" /> Comisión reducida: 3.0%</li>
                                        <li className="flex items-center gap-1.5"><HiOutlineCheck className="w-4 h-4 text-emerald-500" /> Hasta 500 productos</li>
                                        <li className="flex items-center gap-1.5"><HiOutlineCheck className="w-4 h-4 text-emerald-500" /> Tienda Verificada</li>
                                    </ul>
                                </div>
                                <div className="p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 space-y-4">
                                    <h4 className="font-black text-base text-gray-900 dark:text-white">Plan Enterprise</h4>
                                    <div className="text-2xl font-black text-blue-600">$49.99 USD/mes</div>
                                    <ul className="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                        <li className="flex items-center gap-1.5"><HiOutlineCheck className="w-4 h-4 text-emerald-500" /> Comisión mínima: 1.5%</li>
                                        <li className="flex items-center gap-1.5"><HiOutlineCheck className="w-4 h-4 text-emerald-500" /> Catálogo Ilimitado</li>
                                        <li className="flex items-center gap-1.5"><HiOutlineCheck className="w-4 h-4 text-emerald-500" /> Dominio Personalizado</li>
                                    </ul>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </Dashboard>
    );
};

export default TenantOwnerBillingPage;
