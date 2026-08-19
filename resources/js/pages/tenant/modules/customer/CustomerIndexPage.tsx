import Dashboard from "@/components/layouts/Dashboard";
import CustomerServices from "@/Services/CustomerServices";
import { ErrorsFormCustomer } from "@/types/ErrorsFormCustomer";
import { FormCustomer } from "@/types/FormCustomer";
import { Customer, CustomerMetrics } from "@/types/models/Customer";
import { Head, Link } from "@inertiajs/react";
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
    Pagination,
    Select,
    Spinner,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    TextInput,
} from "flowbite-react";
import { FC, useEffect, useState } from "react";
import {
    HiCalendar,
    HiCheckCircle,
    HiEye,
    HiHome,
    HiMail,
    HiPencil,
    HiPhone,
    HiPlus,
    HiRefresh,
    HiSearch,
    HiSpeakerphone,
    HiTrash,
    HiUsers,
    HiXCircle,
} from "react-icons/hi";

interface CustomerIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const emptyCustomerForm: FormCustomer = {
    name: "",
    email: "",
    phone: "",
    birth_date: "",
    gender: "",
    is_active: true,
    accepts_marketing: false,
    addresses: [
        {
            first_name: "",
            last_name: "",
            address_line_1: "",
            address_line_2: "",
            city: "",
            state: "",
            postal_code: "",
            country: "Chile",
            type: "shipping",
            phone: "",
            is_default: true,
        },
    ],
};

const CustomerIndexPage: FC<CustomerIndexPageProps> = ({
    user_id,
    title,
    host,
    user_name,
}) => {
    // Data States
    const [customers, setCustomers] = useState<Customer[]>([]);
    const [metrics, setMetrics] = useState<CustomerMetrics>({
        total_customers: 0,
        active_customers: 0,
        marketing_subscribers: 0,
        new_this_month: 0,
    });
    const [loading, setLoading] = useState<boolean>(true);
    const [loadingAction, setLoadingAction] = useState<boolean>(false);

    // Filters & Pagination
    const [search, setSearch] = useState<string>("");
    const [statusFilter, setStatusFilter] = useState<string>("all");
    const [marketingFilter, setMarketingFilter] = useState<string>("all");
    const [genderFilter, setGenderFilter] = useState<string>("all");
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(15);

    // Modal States
    const [showCreateModal, setShowCreateModal] = useState<boolean>(false);
    const [showEditModal, setShowEditModal] = useState<boolean>(false);
    const [showDeleteModal, setShowDeleteModal] = useState<boolean>(false);
    const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null);

    // Form States
    const [formData, setFormData] = useState<FormCustomer>(emptyCustomerForm);
    const [formErrors, setFormErrors] = useState<ErrorsFormCustomer>({});
    const [includeAddress, setIncludeAddress] = useState<boolean>(false);

    // Notification toast
    const [alertMessage, setAlertMessage] = useState<{ type: "success" | "error"; text: string } | null>(null);

    const showAlert = (type: "success" | "error", text: string) => {
        setAlertMessage({ type, text });
        setTimeout(() => setAlertMessage(null), 5000);
    };

    const fetchMetrics = async () => {
        const res = await CustomerServices.getMetrics();
        if (res?.data?.data) {
            setMetrics(res.data.data);
        }
    };

    const fetchCustomers = async () => {
        setLoading(true);
        const isActiveParam = statusFilter === "all" ? null : statusFilter === "active";
        const acceptsMarketingParam = marketingFilter === "all" ? null : marketingFilter === "subscribed";
        const genderParam = genderFilter === "all" ? null : genderFilter;

        const res = await CustomerServices.filtrar(
            search || null,
            isActiveParam,
            acceptsMarketingParam,
            genderParam,
            perPage,
            currentPage
        );

        if (res?.data?.data) {
            setCustomers(res.data.data.data || []);
            setTotalPages(res.data.data.pagination?.last_page || 1);
            setTotalItems(res.data.data.pagination?.total || 0);
        }
        setLoading(false);
    };

    useEffect(() => {
        fetchMetrics();
    }, []);

    useEffect(() => {
        fetchCustomers();
    }, [search, statusFilter, marketingFilter, genderFilter, currentPage, perPage]);

    // Handle Create Customer
    const handleOpenCreateModal = () => {
        setFormData(emptyCustomerForm);
        setFormErrors({});
        setIncludeAddress(false);
        setShowCreateModal(true);
    };

    const handleCreateSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setFormErrors({});
        setLoadingAction(true);

        const payload: FormCustomer = {
            ...formData,
            addresses: includeAddress ? formData.addresses : [],
        };

        const res = await CustomerServices.create(payload);
        setLoadingAction(false);

        if (res?.data?.status === "success" || res?.status === 201) {
            showAlert("success", "Cliente registrado exitosamente.");
            setShowCreateModal(false);
            fetchCustomers();
            fetchMetrics();
        } else {
            if (res?.data?.errors) {
                setFormErrors(res.data.errors as ErrorsFormCustomer);
            }
            showAlert("error", res?.data?.message || "Error al registrar el cliente.");
        }
    };

    // Handle Edit Customer
    const handleOpenEditModal = (customer: Customer) => {
        setSelectedCustomer(customer);
        setFormData({
            name: customer.name,
            email: customer.email,
            phone: customer.phone || "",
            birth_date: customer.birth_date || "",
            gender: customer.gender || "",
            is_active: customer.is_active,
            accepts_marketing: customer.accepts_marketing,
        });
        setFormErrors({});
        setShowEditModal(true);
    };

    const handleEditSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedCustomer) return;

        setFormErrors({});
        setLoadingAction(true);

        const res = await CustomerServices.update(selectedCustomer.id, formData);
        setLoadingAction(false);

        if (res?.data?.status === "success" || res?.status === 200) {
            showAlert("success", "Cliente actualizado exitosamente.");
            setShowEditModal(false);
            fetchCustomers();
            fetchMetrics();
        } else {
            if (res?.data?.errors) {
                setFormErrors(res.data.errors as ErrorsFormCustomer);
            }
            showAlert("error", res?.data?.message || "Error al actualizar el cliente.");
        }
    };

    // Handle Delete Customer
    const handleOpenDeleteModal = (customer: Customer) => {
        setSelectedCustomer(customer);
        setShowDeleteModal(true);
    };

    const handleDeleteConfirm = async () => {
        if (!selectedCustomer) return;

        setLoadingAction(true);
        const res = await CustomerServices.delete(selectedCustomer.id);
        setLoadingAction(false);

        if (res?.data?.status === "success" || res?.status === 200) {
            showAlert("success", "Cliente eliminado correctamente.");
            setShowDeleteModal(false);
            fetchCustomers();
            fetchMetrics();
        } else {
            showAlert("error", res?.data?.message || "Error al eliminar el cliente.");
        }
    };

    const getInitials = (name: string) => {
        return name
            .split(" ")
            .map((n) => n[0])
            .slice(0, 2)
            .join("")
            .toUpperCase();
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <div className="p-4 sm:p-6 space-y-6">
                {/* Alert Toast Notification */}
                {alertMessage && (
                    <div
                        className={`p-4 rounded-xl shadow-lg border flex items-center justify-between text-sm transition-all duration-300 ${
                            alertMessage.type === "success"
                                ? "bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800"
                                : "bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950 dark:text-rose-300 dark:border-rose-800"
                        }`}
                    >
                        <span className="font-medium">{alertMessage.text}</span>
                        <button
                            onClick={() => setAlertMessage(null)}
                            className="font-bold text-lg leading-none hover:opacity-75"
                        >
                            ×
                        </button>
                    </div>
                )}

                {/* Header & Breadcrumb */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Breadcrumb className="mb-2">
                            <BreadcrumbItem href="#" icon={HiHome}>
                                Inicio
                            </BreadcrumbItem>
                            <BreadcrumbItem>Clientes</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                            Directorio de Clientes
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Gestiona tu base de clientes, historial de compras, libretas de direcciones y marketing.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button
                            color="light"
                            onClick={() => {
                                fetchMetrics();
                                fetchCustomers();
                            }}
                            className="shadow-sm"
                        >
                            <HiRefresh className="mr-2 h-4 w-4" />
                            Actualizar
                        </Button>
                        <Button
                            color="purple"
                            onClick={handleOpenCreateModal}
                            className="shadow-md font-semibold"
                        >
                            <HiPlus className="mr-2 h-4 w-4" />
                            Nuevo Cliente
                        </Button>
                    </div>
                </div>

                {/* KPI Metrics */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="shadow-sm border-l-4 border-l-blue-500 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Total Clientes
                                </p>
                                <h3 className="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                    {metrics.total_customers}
                                </h3>
                                <p className="text-xs text-gray-400 mt-1">Registrados en la base</p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-2xl">
                                <HiUsers className="w-6 h-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="shadow-sm border-l-4 border-l-emerald-500 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Clientes Activos
                                </p>
                                <h3 className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                                    {metrics.active_customers}
                                </h3>
                                <p className="text-xs text-emerald-600/70 mt-1">Habilitados para compras</p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                                <HiCheckCircle className="w-6 h-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="shadow-sm border-l-4 border-l-purple-500 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Suscritos a Marketing
                                </p>
                                <h3 className="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">
                                    {metrics.marketing_subscribers}
                                </h3>
                                <p className="text-xs text-purple-500 mt-1">Aceptan promociones</p>
                            </div>
                            <div className="p-3 bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 rounded-2xl">
                                <HiSpeakerphone className="w-6 h-6" />
                            </div>
                        </div>
                    </Card>

                    <Card className="shadow-sm border-l-4 border-l-amber-500 hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Nuevos Este Mes
                                </p>
                                <h3 className="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">
                                    {metrics.new_this_month}
                                </h3>
                                <p className="text-xs text-amber-500 mt-1">Registros recientes</p>
                            </div>
                            <div className="p-3 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-2xl">
                                <HiCalendar className="w-6 h-6" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Filter and Search Bar */}
                <Card className="shadow-sm">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <div className="lg:col-span-2">
                            <TextInput
                                id="search-customer"
                                icon={HiSearch}
                                placeholder="Buscar por nombre, correo o teléfono..."
                                value={search}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setCurrentPage(1);
                                }}
                                sizing="sm"
                            />
                        </div>

                        <div>
                            <Select
                                id="status-filter"
                                value={statusFilter}
                                onChange={(e) => {
                                    setStatusFilter(e.target.value);
                                    setCurrentPage(1);
                                }}
                                sizing="sm"
                            >
                                <option value="all">Estado: Todos</option>
                                <option value="active">Solo Activos</option>
                                <option value="inactive">Solo Inactivos</option>
                            </Select>
                        </div>

                        <div>
                            <Select
                                id="marketing-filter"
                                value={marketingFilter}
                                onChange={(e) => {
                                    setMarketingFilter(e.target.value);
                                    setCurrentPage(1);
                                }}
                                sizing="sm"
                            >
                                <option value="all">Marketing: Todos</option>
                                <option value="subscribed">Suscritos</option>
                                <option value="unsubscribed">No Suscritos</option>
                            </Select>
                        </div>

                        <div>
                            <Select
                                id="gender-filter"
                                value={genderFilter}
                                onChange={(e) => {
                                    setGenderFilter(e.target.value);
                                    setCurrentPage(1);
                                }}
                                sizing="sm"
                            >
                                <option value="all">Género: Todos</option>
                                <option value="male">Masculino</option>
                                <option value="female">Femenino</option>
                                <option value="other">Otro</option>
                            </Select>
                        </div>
                    </div>
                </Card>

                {/* Customers Table */}
                <Card className="shadow-sm overflow-hidden p-0">
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-50 dark:bg-gray-800 text-xs uppercase font-bold text-gray-500 dark:text-gray-400">
                                <TableHeadCell>Cliente</TableHeadCell>
                                <TableHeadCell>Contacto</TableHeadCell>
                                <TableHeadCell>Direcciones</TableHeadCell>
                                <TableHeadCell>Marketing</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell>Registro</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-12">
                                            <Spinner size="xl" />
                                            <p className="text-sm text-gray-500 mt-2">Cargando directorio de clientes...</p>
                                        </TableCell>
                                    </TableRow>
                                ) : customers.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-12">
                                            <div className="max-w-xs mx-auto text-center space-y-3">
                                                <div className="p-4 bg-gray-100 dark:bg-gray-800 rounded-full inline-block text-gray-400">
                                                    <HiUsers className="w-8 h-8" />
                                                </div>
                                                <h4 className="text-base font-semibold text-gray-700 dark:text-gray-200">
                                                    No se encontraron clientes
                                                </h4>
                                                <p className="text-xs text-gray-400">
                                                    Prueba con otros términos de búsqueda o registra un nuevo cliente.
                                                </p>
                                                <Button
                                                    size="xs"
                                                    color="purple"
                                                    onClick={handleOpenCreateModal}
                                                    className="mx-auto font-medium"
                                                >
                                                    <HiPlus className="mr-1 h-3.5 w-3.5" />
                                                    Registrar Cliente
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    customers.map((customer) => (
                                        <TableRow
                                            key={customer.id}
                                            className="bg-white dark:bg-gray-800 hover:bg-gray-50/70 dark:hover:bg-gray-750 transition-colors"
                                        >
                                            <TableCell className="font-medium text-gray-900 dark:text-white">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-gradient-to-tr from-purple-600 to-indigo-500 text-white flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0">
                                                        {getInitials(customer.name)}
                                                    </div>
                                                    <div>
                                                        <Link
                                                            href={`/customer/backoffice/${user_id}/show/${customer.id}`}
                                                            className="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
                                                        >
                                                            {customer.name}
                                                        </Link>
                                                        <div className="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                                                            <HiMail className="w-3.5 h-3.5" />
                                                            {customer.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div className="text-xs text-gray-600 dark:text-gray-300">
                                                    {customer.phone ? (
                                                        <span className="flex items-center gap-1">
                                                            <HiPhone className="w-3.5 h-3.5 text-gray-400" />
                                                            {customer.phone}
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-400 italic">Sin teléfono</span>
                                                    )}
                                                </div>
                                                {customer.birth_date && (
                                                    <div className="text-xs text-gray-400 mt-1">
                                                        🎂 {customer.birth_date}
                                                    </div>
                                                )}
                                            </TableCell>

                                            <TableCell>
                                                <Badge color="gray" className="inline-flex">
                                                    {customer.addresses?.length || 0} registrada(s)
                                                </Badge>
                                            </TableCell>

                                            <TableCell>
                                                {customer.accepts_marketing ? (
                                                    <Badge color="success" size="sm">
                                                        Suscrito
                                                    </Badge>
                                                ) : (
                                                    <Badge color="gray" size="sm">
                                                        No suscrito
                                                    </Badge>
                                                )}
                                            </TableCell>

                                            <TableCell>
                                                {customer.is_active ? (
                                                    <Badge color="success" size="sm">
                                                        Activo
                                                    </Badge>
                                                ) : (
                                                    <Badge color="failure" size="sm">
                                                        Inactivo
                                                    </Badge>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-xs text-gray-500 dark:text-gray-400">
                                                {customer.created_at
                                                    ? new Date(customer.created_at).toLocaleDateString()
                                                    : "-"}
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <Link
                                                        href={`/customer/backoffice/${user_id}/show/${customer.id}`}
                                                        className="p-1.5 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                                        title="Ver Ficha 360°"
                                                    >
                                                        <HiEye className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => handleOpenEditModal(customer)}
                                                        className="p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors"
                                                        title="Editar Datos"
                                                    >
                                                        <HiPencil className="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleOpenDeleteModal(customer)}
                                                        className="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-lg transition-colors"
                                                        title="Eliminar Cliente"
                                                    >
                                                        <HiTrash className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {!loading && totalPages > 1 && (
                        <div className="p-4 border-t border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <span className="text-xs text-gray-500 dark:text-gray-400">
                                Mostrando página {currentPage} de {totalPages} ({totalItems} clientes en total)
                            </span>
                            <Pagination
                                currentPage={currentPage}
                                totalPages={totalPages}
                                onPageChange={(page) => setCurrentPage(page)}
                                previousLabel="Anterior"
                                nextLabel="Siguiente"
                                showIcons
                            />
                        </div>
                    )}
                </Card>

                {/* MODAL: Crear Nuevo Cliente */}
                <Modal
                    show={showCreateModal}
                    onClose={() => setShowCreateModal(false)}
                    size="2xl"
                    popup={false}
                >
                    <ModalHeader>Registrar Nuevo Cliente</ModalHeader>
                    <form onSubmit={handleCreateSubmit}>
                        <ModalBody className="space-y-4 max-h-[75vh] overflow-y-auto">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="sm:col-span-2">
                                    <Label htmlFor="create-name">Nombre Completo *</Label>
                                    <TextInput
                                        id="create-name"
                                        placeholder="Ej: Carolina Morales"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        required
                                        sizing="sm"
                                        color={formErrors.name ? "failure" : undefined}
                                    />
                                    {formErrors.name && (
                                        <p className="text-xs text-rose-500 mt-1">{formErrors.name}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="create-email">Correo Electrónico *</Label>
                                    <TextInput
                                        id="create-email"
                                        type="email"
                                        placeholder="carolina@empresa.cl"
                                        value={formData.email}
                                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                        required
                                        sizing="sm"
                                        color={formErrors.email ? "failure" : undefined}
                                    />
                                    {formErrors.email && (
                                        <p className="text-xs text-rose-500 mt-1">{formErrors.email}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="create-phone">Teléfono de Contacto</Label>
                                    <TextInput
                                        id="create-phone"
                                        placeholder="+56912345678"
                                        value={formData.phone}
                                        onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                        sizing="sm"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="create-birth-date">Fecha de Nacimiento</Label>
                                    <TextInput
                                        id="create-birth-date"
                                        type="date"
                                        value={formData.birth_date}
                                        onChange={(e) => setFormData({ ...formData, birth_date: e.target.value })}
                                        sizing="sm"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="create-gender">Género</Label>
                                    <Select
                                        id="create-gender"
                                        value={formData.gender}
                                        onChange={(e) => setFormData({ ...formData, gender: e.target.value })}
                                        sizing="sm"
                                    >
                                        <option value="">No especificado</option>
                                        <option value="male">Masculino</option>
                                        <option value="female">Femenino</option>
                                        <option value="other">Otro</option>
                                    </Select>
                                </div>
                            </div>

                            <div className="flex flex-col sm:flex-row gap-4 pt-2 border-t dark:border-gray-700">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="create-active"
                                        checked={formData.is_active}
                                        onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                    />
                                    <Label htmlFor="create-active" className="cursor-pointer">
                                        Cliente Activo
                                    </Label>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="create-marketing"
                                        checked={formData.accepts_marketing}
                                        onChange={(e) =>
                                            setFormData({ ...formData, accepts_marketing: e.target.checked })
                                        }
                                    />
                                    <Label htmlFor="create-marketing" className="cursor-pointer">
                                        Acepta Comunicaciones de Marketing
                                    </Label>
                                </div>
                            </div>

                            {/* Optional Initial Address */}
                            <div className="pt-3 border-t dark:border-gray-700 space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="include-address"
                                            checked={includeAddress}
                                            onChange={(e) => setIncludeAddress(e.target.checked)}
                                        />
                                        <Label htmlFor="include-address" className="cursor-pointer font-bold text-sm">
                                            Agregar Dirección Inicial
                                        </Label>
                                    </div>
                                </div>

                                {includeAddress && formData.addresses && formData.addresses[0] && (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700">
                                        <div>
                                            <Label htmlFor="addr-first-name">Nombre Destinatario</Label>
                                            <TextInput
                                                id="addr-first-name"
                                                placeholder="Nombre"
                                                value={formData.addresses[0].first_name}
                                                onChange={(e) => {
                                                    const addrs = [...(formData.addresses || [])];
                                                    addrs[0].first_name = e.target.value;
                                                    setFormData({ ...formData, addresses: addrs });
                                                }}
                                                sizing="sm"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="addr-last-name">Apellido Destinatario</Label>
                                            <TextInput
                                                id="addr-last-name"
                                                placeholder="Apellido"
                                                value={formData.addresses[0].last_name}
                                                onChange={(e) => {
                                                    const addrs = [...(formData.addresses || [])];
                                                    addrs[0].last_name = e.target.value;
                                                    setFormData({ ...formData, addresses: addrs });
                                                }}
                                                sizing="sm"
                                            />
                                        </div>

                                        <div className="sm:col-span-2">
                                            <Label htmlFor="addr-line1">Dirección (Calle y Número)</Label>
                                            <TextInput
                                                id="addr-line1"
                                                placeholder="Av. Providencia 1234"
                                                value={formData.addresses[0].address_line_1}
                                                onChange={(e) => {
                                                    const addrs = [...(formData.addresses || [])];
                                                    addrs[0].address_line_1 = e.target.value;
                                                    setFormData({ ...formData, addresses: addrs });
                                                }}
                                                sizing="sm"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="addr-city">Ciudad / Comuna</Label>
                                            <TextInput
                                                id="addr-city"
                                                placeholder="Santiago"
                                                value={formData.addresses[0].city}
                                                onChange={(e) => {
                                                    const addrs = [...(formData.addresses || [])];
                                                    addrs[0].city = e.target.value;
                                                    setFormData({ ...formData, addresses: addrs });
                                                }}
                                                sizing="sm"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="addr-state">Región / Estado</Label>
                                            <TextInput
                                                id="addr-state"
                                                placeholder="Región Metropolitana"
                                                value={formData.addresses[0].state}
                                                onChange={(e) => {
                                                    const addrs = [...(formData.addresses || [])];
                                                    addrs[0].state = e.target.value;
                                                    setFormData({ ...formData, addresses: addrs });
                                                }}
                                                sizing="sm"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="addr-zip">Código Postal</Label>
                                            <TextInput
                                                id="addr-zip"
                                                placeholder="7500000"
                                                value={formData.addresses[0].postal_code}
                                                onChange={(e) => {
                                                    const addrs = [...(formData.addresses || [])];
                                                    addrs[0].postal_code = e.target.value;
                                                    setFormData({ ...formData, addresses: addrs });
                                                }}
                                                sizing="sm"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="addr-country">País</Label>
                                            <TextInput
                                                id="addr-country"
                                                value={formData.addresses[0].country}
                                                onChange={(e) => {
                                                    const addrs = [...(formData.addresses || [])];
                                                    addrs[0].country = e.target.value;
                                                    setFormData({ ...formData, addresses: addrs });
                                                }}
                                                sizing="sm"
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button
                                type="submit"
                                color="purple"
                                disabled={loadingAction}
                                className="font-semibold"
                            >
                                {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                                Guardar Cliente
                            </Button>
                            <Button
                                color="gray"
                                onClick={() => setShowCreateModal(false)}
                                disabled={loadingAction}
                            >
                                Cancelar
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                {/* MODAL: Editar Cliente */}
                <Modal
                    show={showEditModal}
                    onClose={() => setShowEditModal(false)}
                    size="lg"
                    popup={false}
                >
                    <ModalHeader>Editar Datos del Cliente</ModalHeader>
                    <form onSubmit={handleEditSubmit}>
                        <ModalBody className="space-y-4">
                            <div>
                                <Label htmlFor="edit-name">Nombre Completo *</Label>
                                <TextInput
                                    id="edit-name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    required
                                    sizing="sm"
                                    color={formErrors.name ? "failure" : undefined}
                                />
                                {formErrors.name && (
                                    <p className="text-xs text-rose-500 mt-1">{formErrors.name}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="edit-email">Correo Electrónico *</Label>
                                <TextInput
                                    id="edit-email"
                                    type="email"
                                    value={formData.email}
                                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                    required
                                    sizing="sm"
                                    color={formErrors.email ? "failure" : undefined}
                                />
                                {formErrors.email && (
                                    <p className="text-xs text-rose-500 mt-1">{formErrors.email}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="edit-phone">Teléfono</Label>
                                    <TextInput
                                        id="edit-phone"
                                        value={formData.phone}
                                        onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                        sizing="sm"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="edit-gender">Género</Label>
                                    <Select
                                        id="edit-gender"
                                        value={formData.gender}
                                        onChange={(e) => setFormData({ ...formData, gender: e.target.value })}
                                        sizing="sm"
                                    >
                                        <option value="">No especificado</option>
                                        <option value="male">Masculino</option>
                                        <option value="female">Femenino</option>
                                        <option value="other">Otro</option>
                                    </Select>
                                </div>
                            </div>

                            <div className="flex items-center gap-4 pt-2 border-t dark:border-gray-700">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="edit-active"
                                        checked={formData.is_active}
                                        onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                                    />
                                    <Label htmlFor="edit-active" className="cursor-pointer">
                                        Activo
                                    </Label>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="edit-marketing"
                                        checked={formData.accepts_marketing}
                                        onChange={(e) =>
                                            setFormData({ ...formData, accepts_marketing: e.target.checked })
                                        }
                                    />
                                    <Label htmlFor="edit-marketing" className="cursor-pointer">
                                        Marketing Permitido
                                    </Label>
                                </div>
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button
                                type="submit"
                                color="purple"
                                disabled={loadingAction}
                                className="font-semibold"
                            >
                                {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                                Actualizar Cliente
                            </Button>
                            <Button
                                color="gray"
                                onClick={() => setShowEditModal(false)}
                                disabled={loadingAction}
                            >
                                Cancelar
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                {/* MODAL: Eliminar Cliente */}
                <Modal
                    show={showDeleteModal}
                    onClose={() => setShowDeleteModal(false)}
                    size="md"
                    popup
                >
                    <ModalHeader />
                    <ModalBody>
                        <div className="text-center space-y-3">
                            <HiXCircle className="mx-auto mb-4 h-14 w-14 text-rose-500" />
                            <h3 className="mb-2 text-lg font-semibold text-gray-800 dark:text-gray-200">
                                ¿Eliminar este cliente?
                            </h3>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Se eliminará a{" "}
                                <span className="font-bold text-gray-700 dark:text-gray-300">
                                    {selectedCustomer?.name}
                                </span>{" "}
                                del directorio. Esta acción puede deshacerse recuperando el registro.
                            </p>
                        </div>
                    </ModalBody>
                    <ModalFooter className="justify-center">
                        <Button
                            color="failure"
                            onClick={handleDeleteConfirm}
                            disabled={loadingAction}
                        >
                            {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                            Sí, Eliminar
                        </Button>
                        <Button
                            color="gray"
                            onClick={() => setShowDeleteModal(false)}
                            disabled={loadingAction}
                        >
                            Cancelar
                        </Button>
                    </ModalFooter>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default CustomerIndexPage;
