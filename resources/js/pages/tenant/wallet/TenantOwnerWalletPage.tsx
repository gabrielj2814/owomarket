import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import Dashboard from '@/components/layouts/Dashboard';
import TenantOwnerNavTabs from '@/components/tenant/TenantOwnerNavTabs';
import {
    HiOutlineCurrencyDollar,
    HiOutlineArrowDownTray,
    HiOutlineClock,
    HiOutlineCheckBadge,
    HiOutlineBuildingLibrary,
    HiOutlineQrCode,
    HiOutlineShieldCheck,
    HiOutlineArrowPath,
} from 'react-icons/hi2';
import axios from 'axios';

interface WalletData {
    gross_sales: number;
    total_commissions: number;
    available_balance: number;
    available_balance_ves: number;
    pending_payouts: number;
    settled_payouts: number;
    bcv_rate: number;
    tenants_count: number;
    settlements: Array<{
        id: string;
        settlement_number: string;
        tenant_id: string;
        type: string;
        amount_usd: number;
        amount_ves: number;
        status: string;
        payment_method: string;
        payment_reference: string | null;
        date: string;
    }>;
}

interface TenantOwnerWalletPageProps {
    title?: string;
    user_id: string;
    wallet: WalletData;
}

export const TenantOwnerWalletPage: React.FC<TenantOwnerWalletPageProps> = ({
    user_id,
    wallet: initialWallet,
}) => {
    const [wallet, setWallet] = useState<WalletData>(initialWallet);
    const [payoutModalOpen, setPayoutModalOpen] = useState<boolean>(false);
    const [payoutMethod, setPayoutMethod] = useState<'pago_movil' | 'binance_pay'>('pago_movil');
    const [amount, setAmount] = useState<string>('50');
    const [bankName, setBankName] = useState<string>('Banesco (0134)');
    const [phoneNumber, setPhoneNumber] = useState<string>('04121234567');
    const [documentId, setDocumentId] = useState<string>('V-24890123');
    const [binancePayId, setBinancePayId] = useState<string>('');
    const [loading, setLoading] = useState<boolean>(false);
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const handleRequestPayout = async (e: React.FormEvent) => {
        e.preventDefault();
        setMessage(null);
        setLoading(true);

        const numericAmount = parseFloat(amount);
        if (isNaN(numericAmount) || numericAmount <= 0) {
            setMessage({ type: 'error', text: 'Ingresa un monto válido mayor a 0.' });
            setLoading(false);
            return;
        }

        if (numericAmount > wallet.available_balance) {
            setMessage({ type: 'error', text: 'El monto solicitado supera el balance disponible.' });
            setLoading(false);
            return;
        }

        try {
            const paymentDetails = payoutMethod === 'pago_movil'
                ? { bank: bankName, phone: phoneNumber, document_id: documentId }
                : { binance_id: binancePayId, currency: 'USDT' };

            const response = await axios.post('/tenant/owner/api/payout-request', {
                user_id,
                tenant_id: wallet.settlements[0]?.tenant_id || 'tecs',
                amount: numericAmount,
                payment_method: payoutMethod === 'pago_movil' ? 'Pago Móvil (Bs. BCV)' : 'Binance Pay (USDT)',
                payment_details: paymentDetails,
                notes: 'Retiro solicitado desde el Tenant Owner Hub',
            });

            if (response.data?.status === 'success') {
                setMessage({ type: 'success', text: '¡Solicitud de retiro registrada exitosamente! Será procesada en breve.' });
                setPayoutModalOpen(false);
                // Refresh balance
                setWallet(prev => ({
                    ...prev,
                    available_balance: prev.available_balance - numericAmount,
                    pending_payouts: prev.pending_payouts + numericAmount,
                }));
            }
        } catch (error: any) {
            setMessage({
                type: 'error',
                text: error?.response?.data?.message || 'Error al procesar la solicitud de retiro.',
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title="Billetera Central & Liquidaciones - OwOMarket" />

            <div className="p-4 sm:p-6 space-y-6">
                <TenantOwnerNavTabs userId={user_id} activeTab="wallet" />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h1 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <HiOutlineCurrencyDollar className="w-7 h-7 text-emerald-600 dark:text-emerald-400" />
                            Billetera Central de Liquidaciones
                        </h1>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Consulta tus ingresos por ventas unificadas en el Marketplace Central y solicita retiros a tu banco o wallet.
                        </p>
                    </div>

                    <button
                        onClick={() => setPayoutModalOpen(true)}
                        disabled={wallet.available_balance <= 0}
                        className="px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2"
                    >
                        <HiOutlineArrowDownTray className="w-4 h-4" />
                        <span>Solicitar Retiro de Fondos</span>
                    </button>
                </div>

                {message && (
                    <div
                        className={`p-4 rounded-2xl text-xs font-semibold flex items-center gap-2 ${
                            message.type === 'success'
                                ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                                : 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800'
                        }`}
                    >
                        <span>{message.text}</span>
                    </div>
                )}

                {/* KPI Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {/* Disponible */}
                    <div className="p-6 rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white shadow-lg shadow-emerald-500/20 space-y-2">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-emerald-100">
                            Saldo Disponible
                        </span>
                        <div className="text-2xl sm:text-3xl font-black">
                            ${wallet.available_balance.toFixed(2)} USD
                        </div>
                        <div className="text-xs text-emerald-100 font-semibold">
                            ≈ Bs. {wallet.available_balance_ves.toLocaleString('es-VE', { minimumFractionDigits: 2 })} (Tasa BCV)
                        </div>
                    </div>

                    {/* Ventas Brutas */}
                    <div className="p-6 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-gray-400">
                            Ventas Centrales Brutas
                        </span>
                        <div className="text-2xl font-black text-gray-900 dark:text-white">
                            ${wallet.gross_sales.toFixed(2)} USD
                        </div>
                        <div className="text-xs text-gray-500">
                            Total procesado en Marketplace
                        </div>
                    </div>

                    {/* En Tránsito / Pendiente */}
                    <div className="p-6 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-amber-500">
                            Retiros en Proceso
                        </span>
                        <div className="text-2xl font-black text-gray-900 dark:text-white">
                            ${wallet.pending_payouts.toFixed(2)} USD
                        </div>
                        <div className="text-xs text-gray-500">
                            Pendiente de transferencia
                        </div>
                    </div>

                    {/* Liquidado Total */}
                    <div className="p-6 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm space-y-2">
                        <span className="text-[11px] font-bold uppercase tracking-wider text-blue-500">
                            Total Liquidado
                        </span>
                        <div className="text-2xl font-black text-gray-900 dark:text-white">
                            ${wallet.settled_payouts.toFixed(2)} USD
                        </div>
                        <div className="text-xs text-gray-500">
                            Transferido a tus cuentas
                        </div>
                    </div>
                </div>

                {/* Historial de Liquidaciones */}
                <div className="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                    <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <HiOutlineClock className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        Historial de Liquidaciones y Retiros ({wallet.settlements.length})
                    </h3>

                    {wallet.settlements.length === 0 ? (
                        <div className="text-center py-12 text-gray-400 text-xs">
                            No hay registros de liquidaciones aún.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="text-[11px] font-black uppercase tracking-wider text-gray-400 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                    <tr>
                                        <th className="py-3 px-4 rounded-l-xl">N° Referencia</th>
                                        <th className="py-3 px-4">Fecha</th>
                                        <th className="py-3 px-4">Método</th>
                                        <th className="py-3 px-4 text-right">Monto (USD)</th>
                                        <th className="py-3 px-4 text-right">Monto (VES)</th>
                                        <th className="py-3 px-4 rounded-r-xl text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                                    {wallet.settlements.map(item => (
                                        <tr key={item.id} className="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                                            <td className="py-4 px-4 font-bold text-gray-900 dark:text-white">
                                                {item.settlement_number}
                                            </td>
                                            <td className="py-4 px-4 text-gray-500">{item.date}</td>
                                            <td className="py-4 px-4 font-medium text-gray-700 dark:text-gray-300">
                                                {item.payment_method}
                                            </td>
                                            <td className="py-4 px-4 text-right font-black text-gray-900 dark:text-white">
                                                ${item.amount_usd.toFixed(2)}
                                            </td>
                                            <td className="py-4 px-4 text-right font-black text-emerald-600 dark:text-emerald-400">
                                                Bs. {item.amount_ves.toLocaleString('es-VE', { minimumFractionDigits: 2 })}
                                            </td>
                                            <td className="py-4 px-4 text-center">
                                                <span
                                                    className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ${
                                                        item.status === 'settled'
                                                            ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'
                                                            : item.status === 'pending'
                                                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                                            : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                                                    }`}
                                                >
                                                    {item.status === 'settled' ? 'Completado' : item.status === 'pending' ? 'En Proceso' : item.status}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal de Solicitud de Retiro */}
            {payoutModalOpen && (
                <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full p-6 space-y-6 shadow-2xl border border-gray-200 dark:border-gray-700">
                        <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                            <h3 className="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                                <HiOutlineArrowDownTray className="w-5 h-5 text-emerald-600" />
                                Solicitar Liquidación de Fondos
                            </h3>
                            <button
                                onClick={() => setPayoutModalOpen(false)}
                                className="text-gray-400 hover:text-gray-600 text-sm font-bold"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleRequestPayout} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Método de Recepción
                                </label>
                                <div className="grid grid-cols-2 gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setPayoutMethod('pago_movil')}
                                        className={`p-3 rounded-2xl border text-xs font-bold flex items-center gap-2 justify-center transition ${
                                            payoutMethod === 'pago_movil'
                                                ? 'border-blue-600 bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400'
                                                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300'
                                        }`}
                                    >
                                        <HiOutlineBuildingLibrary className="w-4 h-4" />
                                        Pago Móvil (Bs. BCV)
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => setPayoutMethod('binance_pay')}
                                        className={`p-3 rounded-2xl border text-xs font-bold flex items-center gap-2 justify-center transition ${
                                            payoutMethod === 'binance_pay'
                                                ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-950/60 text-yellow-600 dark:text-yellow-400'
                                                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300'
                                        }`}
                                    >
                                        <HiOutlineQrCode className="w-4 h-4" />
                                        Binance Pay (USDT)
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Monto a Retirar (USD)
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    max={wallet.available_balance}
                                    step="0.01"
                                    value={amount}
                                    onChange={e => setAmount(e.target.value)}
                                    className="w-full p-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-bold text-gray-900 dark:text-white"
                                    required
                                />
                                <span className="text-[11px] text-gray-400 mt-1 block">
                                    Máximo disponible: ${wallet.available_balance.toFixed(2)} USD
                                </span>
                            </div>

                            {payoutMethod === 'pago_movil' ? (
                                <>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                                Banco de Destino
                                            </label>
                                            <input
                                                type="text"
                                                value={bankName}
                                                onChange={e => setBankName(e.target.value)}
                                                className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs text-gray-900 dark:text-white"
                                                required
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                                Cédula / RIF
                                            </label>
                                            <input
                                                type="text"
                                                value={documentId}
                                                onChange={e => setDocumentId(e.target.value)}
                                                className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs text-gray-900 dark:text-white"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Teléfono Pago Móvil
                                        </label>
                                        <input
                                            type="text"
                                            value={phoneNumber}
                                            onChange={e => setPhoneNumber(e.target.value)}
                                            className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs text-gray-900 dark:text-white"
                                            required
                                        />
                                    </div>
                                </>
                            ) : (
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Binance Pay ID / Pay Email
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Ej: 123456789 o correo@binance.com"
                                        value={binancePayId}
                                        onChange={e => setBinancePayId(e.target.value)}
                                        className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs text-gray-900 dark:text-white"
                                        required
                                    />
                                </div>
                            )}

                            <div className="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setPayoutModalOpen(false)}
                                    className="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-500/20 transition flex items-center gap-2"
                                >
                                    {loading && <HiOutlineArrowPath className="w-4 h-4 animate-spin" />}
                                    <span>Confirmar Retiro</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </Dashboard>
    );
};

export default TenantOwnerWalletPage;
