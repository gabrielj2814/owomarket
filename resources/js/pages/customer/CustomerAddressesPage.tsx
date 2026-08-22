import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CustomerPortalServices, { CustomerAddressData } from '@/Services/CustomerPortalServices';
import {
    HiOutlineMapPin,
    HiOutlinePlus,
    HiOutlinePencilSquare,
    HiOutlineTrash,
    HiOutlineCheck,
    HiOutlineStar,
} from 'react-icons/hi2';

export const CustomerAddressesPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [addresses, setAddresses] = useState<CustomerAddressData[]>([]);
    const [loading, setLoading] = useState(true);
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);

    // Modal state
    const [showModal, setShowModal] = useState(false);
    const [editingAddress, setEditingAddress] = useState<CustomerAddressData | null>(null);

    // Form fields
    const [label, setLabel] = useState('Casa');
    const [addressText, setAddressText] = useState('');
    const [city, setCity] = useState('');
    const [state, setState] = useState('');
    const [zipCode, setZipCode] = useState('');
    const [isDefault, setIsDefault] = useState(false);

    const [saving, setSaving] = useState(false);

    const loadAddresses = () => {
        if (!customer?.id) return;
        CustomerPortalServices.getProfile(customer.id)
            .then(res => {
                if (res.data?.customer?.addresses) {
                    setAddresses(res.data.customer.addresses);
                }
            })
            .catch(() => setLoadError(true))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadAddresses();
    }, [customer?.id]);

    const openCreateModal = () => {
        setEditingAddress(null);
        setLabel('Casa');
        setAddressText('');
        setCity('');
        setState('');
        setZipCode('');
        setIsDefault(addresses.length === 0);
        setShowModal(true);
    };

    const openEditModal = (addr: CustomerAddressData) => {
        setEditingAddress(addr);
        setLabel(addr.label);
        setAddressText(addr.address);
        setCity(addr.city);
        setState(addr.state || '');
        setZipCode(addr.zip_code || '');
        setIsDefault(addr.is_default);
        setShowModal(true);
    };

    const handleSaveAddress = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!customer?.id) return;

        setSaving(true);
        try {
            if (editingAddress) {
                await CustomerPortalServices.updateAddress(customer.id, editingAddress.id, {
                    label,
                    address: addressText,
                    city,
                    state,
                    zip_code: zipCode,
                    country: 'VE',
                    is_default: isDefault,
                });
            } else {
                await CustomerPortalServices.addAddress(customer.id, {
                    label,
                    address: addressText,
                    city,
                    state,
                    zip_code: zipCode,
                    country: 'VE',
                    is_default: isDefault,
                });
            }
            setShowModal(false);
            loadAddresses();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Error al guardar la dirección.');
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (addressId: string) => {
        if (!customer?.id || !confirm('¿Estás seguro de eliminar esta dirección?')) return;
        try {
            await CustomerPortalServices.deleteAddress(customer.id, addressId);
            loadAddresses();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Error al eliminar dirección.');
        }
    };

    const handleSetDefault = async (addressId: string) => {
        if (!customer?.id) return;
        try {
            await CustomerPortalServices.setDefaultAddress(customer.id, addressId);
            loadAddresses();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Error al actualizar dirección predeterminada.');
        }
    };

    return (
        <CustomerAccountLayout
            title="Libreta de Direcciones"
            description="Administra los lugares de entrega de tus compras para agilizar el proceso de compra."
        >
            {loadError && <PortalLoadError />}

            <Head title="Mis Direcciones - OwOMarket" />

            <div className="flex items-center justify-between mb-6">
                <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <HiOutlineMapPin className="w-5 h-5 text-blue-600" />
                    Direcciones Guardadas ({addresses.length})
                </h3>
                <button
                    onClick={openCreateModal}
                    className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 flex items-center gap-1.5 transition"
                >
                    <HiOutlinePlus className="w-4 h-4" />
                    Nueva Dirección
                </button>
            </div>

            {/* Address Cards Grid */}
            {addresses.length === 0 ? (
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-12 text-center border border-gray-200/80 dark:border-gray-800/80">
                    <HiOutlineMapPin className="w-12 h-12 text-gray-300 dark:text-gray-700 mx-auto mb-3" />
                    <h4 className="text-base font-bold text-gray-900 dark:text-white mb-1">
                        No tienes direcciones registradas
                    </h4>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-6">
                        Agrega tu primera dirección de entrega para recibir tus pedidos de forma rápida.
                    </p>
                    <button
                        onClick={openCreateModal}
                        className="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition"
                    >
                        Agregar Dirección
                    </button>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {addresses.map(addr => (
                        <div
                            key={addr.id}
                            className={`p-5 rounded-3xl bg-white dark:bg-gray-900 border transition ${
                                addr.is_default
                                    ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-md shadow-blue-500/10'
                                    : 'border-gray-200/80 dark:border-gray-800/80'
                            }`}
                        >
                            <div className="flex items-center justify-between mb-3">
                                <span className="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-full text-xs font-bold">
                                    {addr.label}
                                </span>
                                {addr.is_default ? (
                                    <span className="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 bg-blue-50 dark:bg-blue-950/50 px-2 py-0.5 rounded-full">
                                        <HiOutlineStar className="w-3.5 h-3.5" /> Predeterminada
                                    </span>
                                ) : (
                                    <button
                                        onClick={() => handleSetDefault(addr.id)}
                                        className="text-[11px] font-semibold text-gray-400 hover:text-blue-600 transition"
                                    >
                                        Marcar como principal
                                    </button>
                                )}
                            </div>

                            <p className="text-xs font-semibold text-gray-900 dark:text-white leading-relaxed mb-1">
                                {addr.address}
                            </p>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                {addr.city} {addr.state ? `, ${addr.state}` : ''} {addr.zip_code ? `(${addr.zip_code})` : ''} - Venezuela
                            </p>

                            <div className="flex items-center justify-end gap-2 border-t border-gray-100 dark:border-gray-800 pt-3">
                                <button
                                    onClick={() => openEditModal(addr)}
                                    className="p-1.5 text-gray-500 hover:text-blue-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                    title="Editar"
                                >
                                    <HiOutlinePencilSquare className="w-4 h-4" />
                                </button>
                                <button
                                    onClick={() => handleDelete(addr.id)}
                                    className="p-1.5 text-gray-500 hover:text-red-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                    title="Eliminar"
                                >
                                    <HiOutlineTrash className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Create/Edit Address Modal */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-800 animate-in fade-in zoom-in-95">
                        <h3 className="text-base font-black text-gray-900 dark:text-white mb-4">
                            {editingAddress ? 'Editar Dirección' : 'Nueva Dirección de Entrega'}
                        </h3>

                        <form onSubmit={handleSaveAddress} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Etiqueta (ej. Casa, Oficina, Apartamento)
                                </label>
                                <input
                                    type="text"
                                    value={label}
                                    onChange={e => setLabel(e.target.value)}
                                    required
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Dirección Detallada (Calle, Edificio, Casa, Nro)
                                </label>
                                <textarea
                                    value={addressText}
                                    onChange={e => setAddressText(e.target.value)}
                                    required
                                    rows={2}
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Ciudad
                                    </label>
                                    <input
                                        type="text"
                                        value={city}
                                        onChange={e => setCity(e.target.value)}
                                        required
                                        placeholder="Caracas"
                                        className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Estado
                                    </label>
                                    <input
                                        type="text"
                                        value={state}
                                        onChange={e => setState(e.target.value)}
                                        placeholder="Miranda / Dtto Capital"
                                        className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2 pt-2">
                                <input
                                    type="checkbox"
                                    id="isDefault"
                                    checked={isDefault}
                                    onChange={e => setIsDefault(e.target.checked)}
                                    className="rounded text-blue-600 focus:ring-blue-500"
                                />
                                <label htmlFor="isDefault" className="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    Establecer como dirección predeterminada
                                </label>
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
                                    disabled={saving}
                                    className="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition disabled:opacity-50"
                                >
                                    {saving ? 'Guardando...' : 'Guardar Dirección'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </CustomerAccountLayout>
    );
};

export default CustomerAddressesPage;
