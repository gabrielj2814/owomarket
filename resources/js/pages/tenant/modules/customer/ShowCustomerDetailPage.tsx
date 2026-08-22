import Dashboard from '@/components/layouts/Dashboard';
import CustomerServices from '@/Services/CustomerServices';
import { ErrorsFormCustomer } from '@/types/ErrorsFormCustomer';
import { FormCustomerAddress } from '@/types/FormCustomer';
import { Customer } from '@/types/models/Customer';
import { Head, Link } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Checkbox,
    Label,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Select,
    Spinner,
    TextInput,
} from 'flowbite-react';
import { FC, useEffect, useState } from 'react';
import { HiArrowLeft, HiCalendar, HiHome, HiIdentification, HiLocationMarker, HiMail, HiPhone, HiPlus, HiStar, HiTrash } from 'react-icons/hi';

interface ShowCustomerDetailPageProps {
    user_id: string;
    customer_id: string;
    title: string;
    host: string;
    user_name: string;
}

const emptyAddressForm: FormCustomerAddress = {
    first_name: '',
    last_name: '',
    address_line_1: '',
    address_line_2: '',
    city: '',
    state: '',
    postal_code: '',
    country: 'Chile',
    type: 'shipping',
    company: '',
    phone: '',
    is_default: false,
};

const ShowCustomerDetailPage: FC<ShowCustomerDetailPageProps> = ({ user_id, customer_id, title, host, user_name }) => {
    const [customer, setCustomer] = useState<Customer | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [loadingAction, setLoadingAction] = useState<boolean>(false);

    // Modal Add Address
    const [showAddAddressModal, setShowAddAddressModal] = useState<boolean>(false);
    const [addressForm, setAddressForm] = useState<FormCustomerAddress>(emptyAddressForm);
    const [addressErrors, setAddressErrors] = useState<ErrorsFormCustomer>({});

    // Notification toast
    const [alertMessage, setAlertMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const showAlert = (type: 'success' | 'error', text: string) => {
        setAlertMessage({ type, text });
        setTimeout(() => setAlertMessage(null), 5000);
    };

    const fetchCustomer = async () => {
        setLoading(true);
        const res = await CustomerServices.consultById(customer_id);
        if (res.data) {
            setCustomer(res.data);
        } else {
            showAlert('error', 'No se pudo cargar la información del cliente.');
        }
        setLoading(false);
    };

    useEffect(() => {
        if (customer_id) {
            fetchCustomer();
        }
    }, [customer_id]);

    const handleOpenAddAddress = () => {
        if (customer) {
            const names = customer.name.split(' ');
            setAddressForm({
                ...emptyAddressForm,
                first_name: names[0] || '',
                last_name: names.slice(1).join(' ') || '',
                phone: customer.phone || '',
                is_default: customer.addresses.length === 0,
            });
        }
        setAddressErrors({});
        setShowAddAddressModal(true);
    };

    const handleAddAddressSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setAddressErrors({});
        setLoadingAction(true);

        const res = await CustomerServices.addAddress(customer_id, addressForm);
        setLoadingAction(false);

        if (res.status === 'success') {
            showAlert('success', 'Dirección agregada exitosamente.');
            setShowAddAddressModal(false);
            if (res.data) {
                setCustomer(res.data);
            } else {
                fetchCustomer();
            }
        } else {
            showAlert('error', res.message || 'Error al agregar dirección.');
        }
    };

    const handleSetDefaultAddress = async (addressId: string) => {
        setLoadingAction(true);
        const res = await CustomerServices.setDefaultAddress(customer_id, addressId);
        setLoadingAction(false);

        if (res.status === 'success') {
            showAlert('success', 'Dirección predeterminada actualizada.');
            if (res.data) {
                setCustomer(res.data);
            } else {
                fetchCustomer();
            }
        } else {
            showAlert('error', res.message || 'Error al actualizar dirección.');
        }
    };

    const handleDeleteAddress = async (addressId: string) => {
        if (!confirm('¿Estás seguro de eliminar esta dirección?')) return;

        setLoadingAction(true);
        const res = await CustomerServices.deleteAddress(customer_id, addressId);
        setLoadingAction(false);

        if (res.status === 'success') {
            showAlert('success', 'Dirección eliminada correctamente.');
            if (res.data) {
                setCustomer(res.data);
            } else {
                fetchCustomer();
            }
        } else {
            showAlert('error', res.message || 'Error al eliminar dirección.');
        }
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .slice(0, 2)
            .join('')
            .toUpperCase();
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <div className="space-y-6 p-4 sm:p-6">
                {/* Alert Toast */}
                {alertMessage && (
                    <div
                        className={`flex items-center justify-between rounded-xl border p-4 text-sm shadow-lg transition-all duration-300 ${
                            alertMessage.type === 'success'
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                : 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-300'
                        }`}
                    >
                        <span className="font-medium">{alertMessage.text}</span>
                        <button onClick={() => setAlertMessage(null)} className="text-lg leading-none font-bold hover:opacity-75">
                            ×
                        </button>
                    </div>
                )}

                {/* Breadcrumb & Navigation */}
                <div className="flex items-center justify-between">
                    <Breadcrumb>
                        <BreadcrumbItem href="#" icon={HiHome}>
                            Inicio
                        </BreadcrumbItem>
                        <BreadcrumbItem href={`/customer/backoffice/${user_id}/module`}>Clientes</BreadcrumbItem>
                        <BreadcrumbItem>Ficha 360°</BreadcrumbItem>
                    </Breadcrumb>

                    <Link
                        href={`/customer/backoffice/${user_id}/module`}
                        className="inline-flex items-center text-xs font-semibold text-gray-600 transition-colors hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                    >
                        <HiArrowLeft className="mr-1.5 h-4 w-4" />
                        Volver al Directorio
                    </Link>
                </div>

                {loading || !customer ? (
                    <div className="py-24 text-center">
                        <Spinner size="xl" />
                        <p className="mt-3 text-sm text-gray-500">Cargando perfil del cliente...</p>
                    </div>
                ) : (
                    <>
                        {/* Profile Header Banner */}
                        <Card className="border-t-4 border-t-indigo-600 shadow-sm">
                            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                                <div className="flex items-center gap-4">
                                    <div className="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-xl font-black text-white shadow-md">
                                        {getInitials(customer.name)}
                                    </div>
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2.5">
                                            <h1 className="text-2xl font-black text-gray-900 dark:text-white">{customer.name}</h1>
                                            {customer.is_active ? (
                                                <Badge color="success" size="sm">
                                                    Activo
                                                </Badge>
                                            ) : (
                                                <Badge color="failure" size="sm">
                                                    Inactivo
                                                </Badge>
                                            )}
                                            {customer.accepts_marketing && (
                                                <Badge color="purple" size="sm">
                                                    📢 Marketing Habilitado
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="mt-1.5 flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                            <span className="flex items-center gap-1">
                                                <HiMail className="h-4 w-4 text-gray-400" />
                                                {customer.email}
                                            </span>
                                            {customer.phone && (
                                                <span className="flex items-center gap-1">
                                                    <HiPhone className="h-4 w-4 text-gray-400" />
                                                    {customer.phone}
                                                </span>
                                            )}
                                            <span className="flex items-center gap-1">
                                                <HiCalendar className="h-4 w-4 text-gray-400" />
                                                Registrado el: {customer.created_at ? new Date(customer.created_at).toLocaleDateString() : '-'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Card>

                        {/* Grid Content */}
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            {/* Left Column: Personal Information */}
                            <div className="space-y-6">
                                <Card className="shadow-sm">
                                    <h3 className="flex items-center gap-2 border-b pb-3 text-base font-bold text-gray-900 dark:border-gray-700 dark:text-white">
                                        <HiIdentification className="h-5 w-5 text-indigo-600" />
                                        Información Personal
                                    </h3>

                                    <div className="space-y-3.5 text-xs">
                                        <div>
                                            <span className="mb-0.5 block font-semibold tracking-wider text-gray-400 uppercase">Nombre Completo</span>
                                            <span className="text-sm font-medium text-gray-800 dark:text-gray-200">{customer.name}</span>
                                        </div>

                                        <div>
                                            <span className="mb-0.5 block font-semibold tracking-wider text-gray-400 uppercase">
                                                Correo Electrónico
                                            </span>
                                            <span className="font-medium text-gray-800 dark:text-gray-200">{customer.email}</span>
                                        </div>

                                        <div>
                                            <span className="mb-0.5 block font-semibold tracking-wider text-gray-400 uppercase">
                                                Teléfono de Contacto
                                            </span>
                                            <span className="font-medium text-gray-800 dark:text-gray-200">
                                                {customer.phone || 'No especificado'}
                                            </span>
                                        </div>

                                        <div>
                                            <span className="mb-0.5 block font-semibold tracking-wider text-gray-400 uppercase">
                                                Fecha de Nacimiento
                                            </span>
                                            <span className="font-medium text-gray-800 dark:text-gray-200">
                                                {customer.birth_date ? `🎂 ${customer.birth_date}` : 'No especificada'}
                                            </span>
                                        </div>

                                        <div>
                                            <span className="mb-0.5 block font-semibold tracking-wider text-gray-400 uppercase">Género</span>
                                            <span className="font-medium text-gray-800 capitalize dark:text-gray-200">
                                                {customer.gender === 'male'
                                                    ? 'Masculino'
                                                    : customer.gender === 'female'
                                                      ? 'Femenino'
                                                      : customer.gender === 'other'
                                                        ? 'Otro'
                                                        : 'No especificado'}
                                            </span>
                                        </div>

                                        <div>
                                            <span className="mb-0.5 block font-semibold tracking-wider text-gray-400 uppercase">
                                                Consentimiento de Marketing
                                            </span>
                                            <span className="font-medium text-gray-800 dark:text-gray-200">
                                                {customer.accepts_marketing
                                                    ? '✅ Suscrito a boletines y promociones'
                                                    : '❌ No suscrito a comunicaciones'}
                                            </span>
                                        </div>
                                    </div>
                                </Card>
                            </div>

                            {/* Right Column: Address Book Manager */}
                            <div className="space-y-6 lg:col-span-2">
                                <Card className="shadow-sm">
                                    <div className="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                                        <h3 className="flex items-center gap-2 text-base font-bold text-gray-900 dark:text-white">
                                            <HiLocationMarker className="h-5 w-5 text-indigo-600" />
                                            Libreta de Direcciones ({customer.addresses?.length || 0})
                                        </h3>
                                        <Button size="xs" color="purple" onClick={handleOpenAddAddress} className="font-medium shadow-sm">
                                            <HiPlus className="mr-1 h-3.5 w-3.5" />
                                            Agregar Dirección
                                        </Button>
                                    </div>

                                    {customer.addresses?.length === 0 ? (
                                        <div className="space-y-2 py-10 text-center text-gray-400">
                                            <HiHome className="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600" />
                                            <p className="text-sm">El cliente aún no tiene direcciones registradas.</p>
                                            <Button size="xs" color="light" onClick={handleOpenAddAddress} className="mx-auto">
                                                + Registrar primera dirección
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            {customer.addresses.map((addr) => (
                                                <div
                                                    key={addr.id}
                                                    className={`rounded-xl border p-4 transition-all ${
                                                        addr.is_default
                                                            ? 'border-indigo-300 bg-indigo-50/50 ring-1 ring-indigo-400/50 dark:border-indigo-800 dark:bg-indigo-950/20'
                                                            : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800'
                                                    }`}
                                                >
                                                    <div className="mb-2 flex items-start justify-between gap-2">
                                                        <div className="flex flex-wrap items-center gap-1.5">
                                                            {addr.is_default && (
                                                                <Badge color="indigo" size="xs">
                                                                    ⭐ Predeterminada
                                                                </Badge>
                                                            )}
                                                            <Badge
                                                                color={
                                                                    addr.type === 'shipping' ? 'info' : addr.type === 'billing' ? 'warning' : 'gray'
                                                                }
                                                                size="xs"
                                                            >
                                                                {addr.type === 'shipping'
                                                                    ? 'Envío'
                                                                    : addr.type === 'billing'
                                                                      ? 'Facturación'
                                                                      : addr.type === 'both'
                                                                        ? 'Envío y Factura'
                                                                        : 'General'}
                                                            </Badge>
                                                        </div>

                                                        <div className="flex items-center gap-1">
                                                            {!addr.is_default && (
                                                                <button
                                                                    onClick={() => handleSetDefaultAddress(addr.id)}
                                                                    className="rounded p-1 text-xs text-indigo-600 transition-colors hover:bg-indigo-100 dark:hover:bg-indigo-900/40"
                                                                    title="Establecer como Predeterminada"
                                                                    disabled={loadingAction}
                                                                >
                                                                    <HiStar className="h-4 w-4" />
                                                                </button>
                                                            )}
                                                            <button
                                                                onClick={() => handleDeleteAddress(addr.id)}
                                                                className="rounded p-1 text-xs text-rose-600 transition-colors hover:bg-rose-100 dark:hover:bg-rose-900/40"
                                                                title="Eliminar Dirección"
                                                                disabled={loadingAction}
                                                            >
                                                                <HiTrash className="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <h4 className="text-sm font-bold text-gray-900 dark:text-white">{addr.full_name}</h4>
                                                    {addr.company && (
                                                        <p className="text-xs font-medium text-gray-500 dark:text-gray-400">🏢 {addr.company}</p>
                                                    )}

                                                    <p className="mt-1 text-xs text-gray-700 dark:text-gray-300">
                                                        {addr.address_line_1}
                                                        {addr.address_line_2 ? `, ${addr.address_line_2}` : ''}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        {addr.city}, {addr.state} ({addr.postal_code}), {addr.country}
                                                    </p>

                                                    {addr.phone && (
                                                        <p className="mt-2 flex items-center gap-1 text-xs text-gray-400">
                                                            <HiPhone className="h-3.5 w-3.5" />
                                                            {addr.phone}
                                                        </p>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </Card>
                            </div>
                        </div>
                    </>
                )}

                {/* MODAL: Agregar Dirección */}
                <Modal show={showAddAddressModal} onClose={() => setShowAddAddressModal(false)} size="lg" popup={false}>
                    <ModalHeader>Agregar Nueva Dirección</ModalHeader>
                    <form onSubmit={handleAddAddressSubmit}>
                        <ModalBody className="max-h-[70vh] space-y-4 overflow-y-auto">
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <Label htmlFor="new-first-name">Nombre Destinatario *</Label>
                                    <TextInput
                                        id="new-first-name"
                                        value={addressForm.first_name}
                                        onChange={(e) => setAddressForm({ ...addressForm, first_name: e.target.value })}
                                        required
                                        sizing="sm"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="new-last-name">Apellido Destinatario *</Label>
                                    <TextInput
                                        id="new-last-name"
                                        value={addressForm.last_name}
                                        onChange={(e) => setAddressForm({ ...addressForm, last_name: e.target.value })}
                                        required
                                        sizing="sm"
                                    />
                                </div>

                                <div className="sm:col-span-2">
                                    <Label htmlFor="new-company">Empresa / Razón Social (Opcional)</Label>
                                    <TextInput
                                        id="new-company"
                                        value={addressForm.company || ''}
                                        onChange={(e) => setAddressForm({ ...addressForm, company: e.target.value })}
                                        sizing="sm"
                                    />
                                </div>

                                <div className="sm:col-span-2">
                                    <Label htmlFor="new-line1">Dirección (Calle y Número) *</Label>
                                    <TextInput
                                        id="new-line1"
                                        placeholder="Ej: Av. Apoquindo 4500"
                                        value={addressForm.address_line_1}
                                        onChange={(e) => setAddressForm({ ...addressForm, address_line_1: e.target.value })}
                                        required
                                        sizing="sm"
                                    />
                                </div>

                                <div className="sm:col-span-2">
                                    <Label htmlFor="new-line2">Depto / Oficina / Piso (Opcional)</Label>
                                    <TextInput
                                        id="new-line2"
                                        placeholder="Ej: Depto 102"
                                        value={addressForm.address_line_2 || ''}
                                        onChange={(e) => setAddressForm({ ...addressForm, address_line_2: e.target.value })}
                                        sizing="sm"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="new-city">Ciudad / Comuna *</Label>
                                    <TextInput
                                        id="new-city"
                                        value={addressForm.city}
                                        onChange={(e) => setAddressForm({ ...addressForm, city: e.target.value })}
                                        required
                                        sizing="sm"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="new-state">Región / Estado *</Label>
                                    <TextInput
                                        id="new-state"
                                        value={addressForm.state}
                                        onChange={(e) => setAddressForm({ ...addressForm, state: e.target.value })}
                                        required
                                        sizing="sm"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="new-zip">Código Postal *</Label>
                                    <TextInput
                                        id="new-zip"
                                        value={addressForm.postal_code}
                                        onChange={(e) => setAddressForm({ ...addressForm, postal_code: e.target.value })}
                                        required
                                        sizing="sm"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="new-country">País *</Label>
                                    <TextInput
                                        id="new-country"
                                        value={addressForm.country}
                                        onChange={(e) => setAddressForm({ ...addressForm, country: e.target.value })}
                                        required
                                        sizing="sm"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="new-type">Tipo de Dirección</Label>
                                    <Select
                                        id="new-type"
                                        value={addressForm.type}
                                        onChange={(e) => setAddressForm({ ...addressForm, type: e.target.value })}
                                        sizing="sm"
                                    >
                                        <option value="shipping">Dirección de Envío</option>
                                        <option value="billing">Dirección de Facturación</option>
                                        <option value="both">Envío y Facturación</option>
                                        <option value="other">Otra</option>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="new-phone">Teléfono de Entrega</Label>
                                    <TextInput
                                        id="new-phone"
                                        value={addressForm.phone || ''}
                                        onChange={(e) => setAddressForm({ ...addressForm, phone: e.target.value })}
                                        sizing="sm"
                                    />
                                </div>
                            </div>

                            <div className="border-t pt-2 dark:border-gray-700">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="new-default"
                                        checked={addressForm.is_default}
                                        onChange={(e) => setAddressForm({ ...addressForm, is_default: e.target.checked })}
                                    />
                                    <Label htmlFor="new-default" className="cursor-pointer">
                                        Establecer como dirección predeterminada
                                    </Label>
                                </div>
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button type="submit" color="purple" disabled={loadingAction} className="font-semibold">
                                {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                                Guardar Dirección
                            </Button>
                            <Button color="gray" onClick={() => setShowAddAddressModal(false)} disabled={loadingAction}>
                                Cancelar
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default ShowCustomerDetailPage;
