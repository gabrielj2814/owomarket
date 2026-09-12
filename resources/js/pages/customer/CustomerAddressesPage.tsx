import PortalActionFeedback, { PortalFeedback } from '@/components/ui/customer/PortalActionFeedback';
import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import { Badge, Button, Card, Checkbox, Label, Modal, ModalBody, ModalFooter, ModalHeader, Textarea, TextInput } from 'flowbite-react';
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
    // Hallazgo C2: el resultado de cada accion, en linea en vez de un alert().
    const [feedback, setFeedback] = useState<PortalFeedback | null>(null);

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

        setFeedback(null);
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
            setFeedback({ type: 'error', text: err.response?.data?.message || 'No se pudo guardar la dirección.' });
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (addressId: string) => {
        if (!customer?.id || !confirm('¿Estás seguro de eliminar esta dirección?')) return;
        setFeedback(null);
        try {
            await CustomerPortalServices.deleteAddress(customer.id, addressId);
            loadAddresses();
        } catch (err: any) {
            setFeedback({ type: 'error', text: err.response?.data?.message || 'No se pudo eliminar la dirección.' });
        }
    };

    const handleSetDefault = async (addressId: string) => {
        if (!customer?.id) return;
        setFeedback(null);
        try {
            await CustomerPortalServices.setDefaultAddress(customer.id, addressId);
            loadAddresses();
        } catch (err: any) {
            setFeedback({ type: 'error', text: err.response?.data?.message || 'No se pudo cambiar la dirección predeterminada.' });
        }
    };

    return (
        <CustomerAccountLayout
            title="Libreta de Direcciones"
            description="Administra los lugares de entrega de tus compras para agilizar el proceso de compra."
        >
            {loadError && <PortalLoadError />}
            <PortalActionFeedback feedback={feedback} />

            <Head title="Mis Direcciones - OwOMarket" />

            <div className="flex items-center justify-between mb-6">
                <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <HiOutlineMapPin className="w-5 h-5 text-blue-600" />
                    Direcciones Guardadas ({addresses.length})
                </h3>
                <Button color="primary" size="sm" onClick={openCreateModal}>
                    <HiOutlinePlus className="mr-1.5 h-4 w-4" />
                    Nueva Dirección
                </Button>
            </div>

            {/* Address Cards Grid */}
            {addresses.length === 0 ? (
                <Card>
                    <div data-testid="direcciones-vacio" className="py-6 text-center">
                        <HiOutlineMapPin className="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-700" />
                        <h4 className="mb-1 text-base font-bold text-gray-900 dark:text-white">
                            No tienes direcciones registradas
                        </h4>
                        <p className="mb-6 text-xs text-gray-500 dark:text-gray-400">
                            Agrega tu primera dirección de entrega para recibir tus pedidos de forma rápida.
                        </p>
                        <Button color="primary" size="sm" className="mx-auto w-fit" onClick={openCreateModal}>
                            Agregar Dirección
                        </Button>
                    </div>
                </Card>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {addresses.map(addr => (
                        <Card
                            key={addr.id}
                            className={addr.is_default ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-md shadow-blue-500/10' : undefined}
                            theme={{ root: { children: 'flex h-full flex-col gap-3 p-5' } }}
                        >
                            <div className="flex items-center justify-between mb-3">
                                <Badge color="gray" size="xs">
                                    {addr.label}
                                </Badge>
                                {addr.is_default ? (
                                    <Badge color="blue" size="xs" icon={HiOutlineStar}>
                                        Predeterminada
                                    </Badge>
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
                        </Card>
                    ))}
                </div>
            )}

            {/* Create/Edit Address Modal */}
            <Modal show={showModal} onClose={() => setShowModal(false)} size="lg">
                <ModalHeader>{editingAddress ? 'Editar Dirección' : 'Nueva Dirección de Entrega'}</ModalHeader>
                <form onSubmit={handleSaveAddress}>
                    <ModalBody>
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="dir-etiqueta">Etiqueta (ej. Casa, Oficina, Apartamento)</Label>
                                <TextInput
                                    id="dir-etiqueta"
                                    value={label}
                                    onChange={(e) => setLabel(e.target.value)}
                                    required
                                />
                            </div>

                            <div>
                                <Label htmlFor="dir-direccion">Dirección Detallada (Calle, Edificio, Casa, Nro)</Label>
                                <Textarea
                                    id="dir-direccion"
                                    value={addressText}
                                    onChange={(e) => setAddressText(e.target.value)}
                                    required
                                    rows={2}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="dir-ciudad">Ciudad</Label>
                                    <TextInput
                                        id="dir-ciudad"
                                        value={city}
                                        onChange={(e) => setCity(e.target.value)}
                                        required
                                        placeholder="Caracas"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="dir-estado">Estado</Label>
                                    <TextInput
                                        id="dir-estado"
                                        value={state}
                                        onChange={(e) => setState(e.target.value)}
                                        placeholder="Miranda / Dtto Capital"
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2 pt-2">
                                <Checkbox
                                    id="isDefault"
                                    checked={isDefault}
                                    onChange={(e) => setIsDefault(e.target.checked)}
                                />
                                <Label htmlFor="isDefault" className="mb-0">
                                    Establecer como dirección predeterminada
                                </Label>
                            </div>
                        </div>
                    </ModalBody>
                    <ModalFooter>
                        <Button type="submit" color="primary" size="sm" disabled={saving}>
                            {saving ? 'Guardando...' : 'Guardar Dirección'}
                        </Button>
                        <Button type="button" color="subtle" size="sm" onClick={() => setShowModal(false)}>
                            Cancelar
                        </Button>
                    </ModalFooter>
                </form>
            </Modal>
        </CustomerAccountLayout>
    );
};

export default CustomerAddressesPage;
