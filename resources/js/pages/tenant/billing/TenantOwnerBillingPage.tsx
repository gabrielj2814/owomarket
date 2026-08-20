import React from 'react';
import { Head } from '@inertiajs/react';
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
}

export const TenantOwnerBillingPage: React.FC<TenantOwnerBillingPageProps> = ({
    user_id,
    tenants,
    subscriptions,
    available_plans,
}) => {
    return (
        <Dashboard user_uuid={user_id}>
            <Head title="Suscripciones & Facturas B2B - OwOMarket" />

            <div className="p-4 sm:p-6 space-y-6">
                <TenantOwnerNavTabs userId={user_id} activeTab="billing" />

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

                                    <button
                                        onClick={() => alert(`Solicitud de cambio de plan para ${tenant.name} registrada. Un asesor te contactará.`)}
                                        className="w-full py-2 px-3 rounded-xl bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-600 dark:text-blue-400 text-xs font-bold transition flex items-center justify-center gap-1.5"
                                    >
                                        <HiOutlineSparkles className="w-3.5 h-3.5" />
                                        <span>Mejorar Plan (Upgrade)</span>
                                    </button>
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
