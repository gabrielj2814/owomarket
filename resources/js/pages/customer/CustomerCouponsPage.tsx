import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CustomerPortalServices, { CustomerCouponData } from '@/Services/CustomerPortalServices';
import {
    HiOutlineTicket,
    HiOutlineClipboardDocumentCheck,
    HiOutlineSparkles,
    HiOutlineClock,
} from 'react-icons/hi2';

export const CustomerCouponsPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [coupons, setCoupons] = useState<CustomerCouponData[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        CustomerPortalServices.getCoupons(customer?.id)
            .then(res => {
                if (res?.data) {
                    setCoupons(res.data);
                }
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [customer?.id]);

    const copyCode = (code: string) => {
        navigator.clipboard.writeText(code);
        alert(`¡Cupón ${code} copiado al portapapeles! Aplícalo en el carrito de compras.`);
    };

    return (
        <CustomerAccountLayout
            title="Mis Cupones & Descuentos"
            description="Aprovecha los cupones activos y beneficios exclusivos para compras en OwOMarket."
        >
            <Head title="Mis Cupones - OwOMarket" />

            <div className="flex items-center justify-between mb-6">
                <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <HiOutlineTicket className="w-5 h-5 text-emerald-600" />
                    Cupones Disponibles ({coupons.length})
                </h3>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {coupons.map(coupon => (
                    <div
                        key={coupon.id}
                        className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80 relative overflow-hidden flex flex-col justify-between"
                    >
                        {/* Discount Banner */}
                        <div>
                            <div className="flex items-center justify-between mb-3">
                                <span className="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 rounded-full text-xs font-black">
                                    <HiOutlineSparkles className="w-3.5 h-3.5" /> {coupon.badge}
                                </span>
                                <span className="text-[11px] font-semibold text-gray-400 flex items-center gap-1">
                                    <HiOutlineClock className="w-3.5 h-3.5" /> Vence: {coupon.valid_until}
                                </span>
                            </div>

                            <h4 className="text-sm font-black text-gray-900 dark:text-white mb-1">
                                {coupon.title}
                            </h4>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">
                                {coupon.description}
                            </p>
                        </div>

                        {/* Coupon Code Box */}
                        <div className="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-dashed border-gray-300 dark:border-gray-700">
                            <div>
                                <span className="text-[10px] uppercase font-bold text-gray-400 block">Código:</span>
                                <span className="font-mono text-sm font-black text-blue-600 dark:text-blue-400 tracking-wider">
                                    {coupon.code}
                                </span>
                            </div>

                            <button
                                onClick={() => copyCode(coupon.code)}
                                className="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-1.5 transition"
                            >
                                <HiOutlineClipboardDocumentCheck className="w-4 h-4" />
                                Copiar
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </CustomerAccountLayout>
    );
};

export default CustomerCouponsPage;
