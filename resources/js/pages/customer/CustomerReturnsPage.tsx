import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CustomerPortalServices, {
    CustomerReturnRequestData,
    CustomerOrderData,
} from '@/Services/CustomerPortalServices';
import {
    HiOutlineArrowPathRoundedSquare,
    HiOutlinePlus,
    HiOutlineClock,
    HiOutlineCheckCircle,
    HiOutlineXCircle,
    HiOutlineBuildingStorefront,
} from 'react-icons/hi2';

export const CustomerReturnsPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [returns, setReturns] = useState<CustomerReturnRequestData[]>([]);
    const [orders, setOrders] = useState<CustomerOrderData[]>([]);
    const [loading, setLoading] = useState(true);

    // Modal state
    const [showModal, setShowModal] = useState(false);
    const [selectedOrderId, setSelectedOrderId] = useState('');
    const [selectedProductId, setSelectedProductId] = useState('');
    const [reason, setReason] = useState('damaged');
    const [description, setDescription] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const loadData = () => {
        if (!customer?.id) return;
        setLoading(true);
        Promise.all([
            CustomerPortalServices.getReturns(customer.id),
            CustomerPortalServices.getOrders(customer.id, { status: 'completed' }),
        ])
            .then(([returnsRes, ordersRes]) => {
                if (returnsRes?.data) setReturns(returnsRes.data);
                if (ordersRes?.data) setOrders(ordersRes.data);
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadData();
    }, [customer?.id]);

    const handleCreateReturn = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!customer?.id || !selectedOrderId || !selectedProductId) {
            alert('Por favor selecciona una orden y el producto a devolver.');
            return;
        }

        setSubmitting(true);
        try {
            await CustomerPortalServices.createReturn({
                customer_id: customer.id,
                order_id: selectedOrderId,
                product_id: selectedProductId,
                reason,
                description,
            });
            setShowModal(false);
            setDescription('');
            loadData();
            alert('Solicitud de devolución enviada con éxito.');
        } catch (err: any) {
            alert(err.response?.data?.message || 'Error al enviar solicitud de devolución.');
        } finally {
            setSubmitting(false);
        }
    };

    const selectedOrder = orders.find(o => o.id === selectedOrderId);

    const statusBadge = (status: string) => {
        switch (status) {
            case 'approved':
                return <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-300 rounded-full text-[10px] font-black uppercase"><HiOutlineCheckCircle className="w-3.5 h-3.5" /> Aprobada</span>;
            case 'rejected':
                return <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300 rounded-full text-[10px] font-black uppercase"><HiOutlineXCircle className="w-3.5 h-3.5" /> Rechazada</span>;
            case 'refunded':
                return <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 rounded-full text-[10px] font-black uppercase"><HiOutlineCheckCircle className="w-3.5 h-3.5" /> Reembolsada</span>;
            case 'in_review':
                return <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 rounded-full text-[10px] font-black uppercase"><HiOutlineClock className="w-3.5 h-3.5" /> En Revisión</span>;
            default:
                return <span className="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 rounded-full text-[10px] font-black uppercase"><HiOutlineClock className="w-3.5 h-3.5" /> Solicitada</span>;
        }
    };

    return (
        <CustomerAccountLayout
            title="Devoluciones & Garantías (RMA)"
            description="Gestiona reclamos, cambios por garantía y solicitudes de reembolso de tus compras."
        >
            <Head title="Devoluciones - OwOMarket" />

            <div className="flex items-center justify-between mb-6">
                <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <HiOutlineArrowPathRoundedSquare className="w-5 h-5 text-blue-600" />
                    Mis Reclamos ({returns.length})
                </h3>
                <button
                    onClick={() => setShowModal(true)}
                    className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-1.5 transition"
                >
                    <HiOutlinePlus className="w-4 h-4" />
                    Nueva Devolución
                </button>
            </div>

            {returns.length === 0 ? (
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center border border-gray-200/80 dark:border-gray-800/80">
                    <HiOutlineArrowPathRoundedSquare className="w-12 h-12 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                    <h4 className="text-base font-bold text-gray-900 dark:text-white mb-1">
                        No tienes devoluciones en curso
                    </h4>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-6">
                        Si tuviste algún inconveniente con un producto recibido, puedes iniciar un reclamo aquí.
                    </p>
                    <button
                        onClick={() => setShowModal(true)}
                        className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition"
                    >
                        Solicitar Devolución
                    </button>
                </div>
            ) : (
                <div className="space-y-4">
                    {returns.map(ret => (
                        <div
                            key={ret.id}
                            className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80"
                        >
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 gap-2">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-xs font-bold text-gray-900 dark:text-white">
                                            Orden: {ret.order_number}
                                        </span>
                                        {statusBadge(ret.status)}
                                    </div>
                                    <span className="text-[11px] text-gray-400">
                                        Solicitado el: {ret.created_at ? ret.created_at.substring(0, 10) : 'Reciente'} • Tienda: {ret.tenant_id}
                                    </span>
                                </div>
                            </div>

                            <div className="py-3">
                                <h4 className="text-xs font-bold text-gray-900 dark:text-white mb-1">
                                    Producto: {ret.product_name}
                                </h4>
                                <p className="text-xs text-gray-600 dark:text-gray-400">
                                    <strong>Motivo:</strong> {ret.reason}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">
                                    "{ret.description}"
                                </p>

                                {ret.admin_notes && (
                                    <div className="mt-3 p-3 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 text-xs text-blue-800 dark:text-blue-300">
                                        <strong>Respuesta del Soporte:</strong> {ret.admin_notes}
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Create Return Modal */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-800">
                        <h3 className="text-base font-black text-gray-900 dark:text-white mb-4">
                            Solicitar Devolución de Producto
                        </h3>

                        <form onSubmit={handleCreateReturn} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Selecciona el Pedido
                                </label>
                                <select
                                    value={selectedOrderId}
                                    onChange={e => {
                                        setSelectedOrderId(e.target.value);
                                        setSelectedProductId('');
                                    }}
                                    required
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                >
                                    <option value="">Selecciona una orden entregada...</option>
                                    {orders.map(o => (
                                        <option key={o.id} value={o.id}>
                                            {o.order_number} (${o.total.toFixed(2)})
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {selectedOrder && selectedOrder.items && (
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Producto a Devolver
                                    </label>
                                    <select
                                        value={selectedProductId}
                                        onChange={e => setSelectedProductId(e.target.value)}
                                        required
                                        className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                    >
                                        <option value="">Selecciona un producto del pedido...</option>
                                        {selectedOrder.items.map(item => (
                                            <option key={item.id} value={item.product_id}>
                                                {item.product_name} (${item.price.toFixed(2)})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Motivo del Reclamo
                                </label>
                                <select
                                    value={reason}
                                    onChange={e => setReason(e.target.value)}
                                    required
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                >
                                    <option value="Producto dañado o roto">Producto dañado o roto</option>
                                    <option value="Producto no coincide con la descripción">Producto no coincide con la descripción</option>
                                    <option value="Talla o variante incorrecta">Talla o variante incorrecta</option>
                                    <option value="Defecto de fábrica">Defecto de fábrica</option>
                                    <option value="Otro motivo">Otro motivo</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Explicación Detallada
                                </label>
                                <textarea
                                    value={description}
                                    onChange={e => setDescription(e.target.value)}
                                    required
                                    rows={3}
                                    placeholder="Describe qué ocurrió con el producto y qué solución solicitas (cambio o reembolso)..."
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <div className="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 rounded-xl hover:bg-gray-100 transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={submitting}
                                    className="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition disabled:opacity-50"
                                >
                                    {submitting ? 'Enviando...' : 'Enviar Reclamo'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </CustomerAccountLayout>
    );
};

export default CustomerReturnsPage;
