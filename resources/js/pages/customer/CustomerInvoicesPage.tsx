import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CustomerPortalServices, { CustomerInvoiceData } from '@/Services/CustomerPortalServices';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';
import {
    HiOutlineDocumentText,
    HiOutlineDocumentArrowDown,
    HiOutlineArrowPath,
    HiOutlineCheckBadge,
} from 'react-icons/hi2';

export const CustomerInvoicesPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [invoices, setInvoices] = useState<CustomerInvoiceData[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!customer?.id) return;
        setLoading(true);
        CustomerPortalServices.getInvoices(customer.id)
            .then(res => {
                if (res.data) {
                    setInvoices(res.data);
                }
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [customer?.id]);

    return (
        <CustomerAccountLayout
            title="Mis Facturas Electrónicas PDF"
            description="Descarga comprobantes fiscales y facturas digitales con montos en USD y Bolívares a tasa oficial BCV."
        >
            <Head title="Mis Facturas - OwOMarket" />

            <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80">
                <div className="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <HiOutlineDocumentText className="w-5 h-5 text-blue-600" />
                            Comprobantes Emitidos ({invoices.length})
                        </h3>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Todas las facturas cumplen con la normativa del BCV y Ley de Impuesto a las Grandes Transacciones Financieras.
                        </p>
                    </div>
                </div>

                {loading ? (
                    <div className="text-center py-16 text-gray-400">
                        <HiOutlineArrowPath className="w-8 h-8 mx-auto mb-2 animate-spin text-blue-600" />
                        <p className="text-xs font-medium">Cargando facturas...</p>
                    </div>
                ) : invoices.length === 0 ? (
                    <div className="text-center py-12 text-gray-400">
                        <HiOutlineDocumentText className="w-12 h-12 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                        <h4 className="text-base font-bold text-gray-900 dark:text-white mb-1">
                            No tienes facturas emitidas
                        </h4>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Cuando completes una compra en el marketplace, podrás descargar tu factura PDF aquí.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="text-[11px] font-black uppercase tracking-wider text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                                <tr>
                                    <th className="py-3 px-4 rounded-l-xl">N° Factura</th>
                                    <th className="py-3 px-4">Orden</th>
                                    <th className="py-3 px-4">Fecha</th>
                                    <th className="py-3 px-4 text-right">Total (USD)</th>
                                    <th className="py-3 px-4 text-right">Total (VES BCV)</th>
                                    <th className="py-3 px-4 text-center">Estado</th>
                                    <th className="py-3 px-4 rounded-r-xl text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {invoices.map(inv => (
                                    <tr key={inv.id} className="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                        <td className="py-4 px-4 font-bold text-gray-900 dark:text-white">
                                            {inv.invoice_number}
                                        </td>
                                        <td className="py-4 px-4 font-medium text-gray-600 dark:text-gray-300">
                                            {inv.order_number}
                                        </td>
                                        <td className="py-4 px-4 text-gray-500">
                                            {inv.date}
                                        </td>
                                        <td className="py-4 px-4 text-right font-black text-gray-900 dark:text-white">
                                            ${inv.total_usd.toFixed(2)}
                                        </td>
                                        <td className="py-4 px-4 text-right font-black text-emerald-600 dark:text-emerald-400">
                                            Bs. {inv.total_ves.toLocaleString('es-VE', { minimumFractionDigits: 2 })}
                                        </td>
                                        <td className="py-4 px-4 text-center">
                                            <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300">
                                                <HiOutlineCheckBadge className="w-3 h-3" /> Pagada
                                            </span>
                                        </td>
                                        <td className="py-4 px-4 text-center">
                                            <a
                                                href={inv.pdf_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-[11px] shadow-sm shadow-blue-500/20 transition"
                                            >
                                                <HiOutlineDocumentArrowDown className="w-3.5 h-3.5" /> PDF
                                            </a>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </CustomerAccountLayout>
    );
};

export default CustomerInvoicesPage;
