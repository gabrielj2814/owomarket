import Dashboard from '@/components/layouts/Dashboard';
import BillingServices from '@/Services/BillingServices';
import { FormDirectInvoice, FormInvoiceItemRow } from '@/types/FormDirectInvoice';
import { Invoice, InvoiceMetrics } from '@/types/models/Invoice';
import { Head, Link } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
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
    Textarea,
} from 'flowbite-react';
import { FC, useEffect, useState } from 'react';
import {
    HiCurrencyDollar,
    HiDocumentDownload,
    HiDocumentText,
    HiEye,
    HiHome,
    HiMail,
    HiPlus,
    HiRefresh,
    HiSearch,
    HiTrash,
    HiXCircle,
} from 'react-icons/hi';
import { LuFileCheck, LuReceipt, LuReceiptText } from 'react-icons/lu';

interface BillingIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const emptyItemRow: FormInvoiceItemRow = {
    description: '',
    quantity: 1,
    unit_price: 0,
    tax_rate: 19,
    discount_amount: 0,
    sku: '',
};

const initialInvoiceForm: FormDirectInvoice = {
    customer_name: '',
    customer_email: '',
    customer_tax_id: '',
    customer_address_line_1: '',
    customer_address_line_2: '',
    customer_city: '',
    customer_state: '',
    customer_postal_code: '',
    customer_country: '',
    items: [{ ...emptyItemRow }],
    payment_method: 'manual_transfer',
    payment_status: 'paid',
    status: 'issued',
    currency: 'USD',
    notes: '',
};

const BillingIndexPage: FC<BillingIndexPageProps> = ({ user_id, title }) => {
    const [invoices, setInvoices] = useState<Invoice[]>([]);
    const [metrics, setMetrics] = useState<InvoiceMetrics>({
        total_billed: 0,
        total_issued: 0,
        total_paid: 0,
        total_cancelled: 0,
    });
    const [loading, setLoading] = useState<boolean>(true);
    const [search, setSearch] = useState<string>('');
    const [statusFilter, setStatusFilter] = useState<string>('');
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(15);

    // Modal Emisión Directa
    const [createModalOpen, setCreateModalOpen] = useState<boolean>(false);
    const [form, setForm] = useState<FormDirectInvoice>(initialInvoiceForm);
    const [formErrors, setFormErrors] = useState<Record<string, string[]>>({});
    const [submitting, setSubmitting] = useState<boolean>(false);

    // Modal Anulación
    const [cancelModalOpen, setCancelModalOpen] = useState<boolean>(false);
    const [invoiceToCancel, setInvoiceToCancel] = useState<Invoice | null>(null);
    const [cancelReason, setCancelReason] = useState<string>('');
    const [cancelling, setCancelling] = useState<boolean>(false);

    // Modal Reenvío Email
    const [resendModalOpen, setResendModalOpen] = useState<boolean>(false);
    const [invoiceToResend, setInvoiceToResend] = useState<Invoice | null>(null);
    const [resendEmail, setResendEmail] = useState<string>('');
    const [resending, setResending] = useState<boolean>(false);

    // Toast de notificación
    const [toastMessage, setToastMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);

    const showToast = (text: string, type: 'success' | 'error' = 'success') => {
        setToastMessage({ text, type });
        setTimeout(() => setToastMessage(null), 4000);
    };

    const fetchMetrics = async () => {
        try {
            const res = await BillingServices.getMetrics();
            if (res.data) {
                setMetrics(res.data);
            }
        } catch (error) {
            console.error('Error al cargar métricas:', error);
        }
    };

    const fetchInvoices = async (page = currentPage) => {
        setLoading(true);
        try {
            const res = await BillingServices.filterInvoices({
                search: search.trim() !== '' ? search.trim() : undefined,
                status: statusFilter !== '' ? statusFilter : undefined,
                page,
                per_page: perPage,
                sort_by: 'created_at',
                sort_direction: 'desc',
            });

            if (Array.isArray(res.data)) {
                setInvoices(res.data);
                if (res.pagination) {
                    setCurrentPage(res.pagination.current_page);
                    setTotalPages(res.pagination.last_page);
                    setTotalItems(res.pagination.total);
                }
            }
        } catch (error) {
            console.error('Error al consultar facturas:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchMetrics();
        fetchInvoices(1);
    }, [statusFilter, perPage]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchInvoices(1);
    };

    // Manejo de ítems en el formulario de emisión directa
    const handleAddItem = () => {
        setForm((prev) => ({
            ...prev,
            items: [...prev.items, { ...emptyItemRow }],
        }));
    };

    const handleRemoveItem = (index: number) => {
        if (form.items.length <= 1) return;
        setForm((prev) => ({
            ...prev,
            items: prev.items.filter((_, i) => i !== index),
        }));
    };

    const handleItemChange = (index: number, field: keyof FormInvoiceItemRow, value: any) => {
        setForm((prev) => {
            const newItems = [...prev.items];
            newItems[index] = { ...newItems[index], [field]: value };
            return { ...prev, items: newItems };
        });
    };

    // Cálculos en vivo del formulario
    const calculatedSubtotal = form.items.reduce((acc, item) => acc + (Number(item.quantity) * Number(item.unit_price) || 0), 0);
    const calculatedTax = form.items.reduce((acc, item) => {
        const lineSubtotal = Number(item.quantity) * Number(item.unit_price) || 0;
        const discount = Number(item.discount_amount) || 0;
        const taxable = Math.max(0, lineSubtotal - discount);
        return acc + taxable * ((Number(item.tax_rate) || 0) / 100);
    }, 0);
    const calculatedDiscount = form.items.reduce((acc, item) => acc + (Number(item.discount_amount) || 0), 0);
    const calculatedTotal = calculatedSubtotal - calculatedDiscount + calculatedTax;

    const handleCreateInvoice = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setFormErrors({});

        try {
            const res = await BillingServices.createDirectInvoice(form);
            if (res.data) {
                showToast(`Factura ${res.data.invoice_number} emitida exitosamente`);
                setCreateModalOpen(false);
                setForm(initialInvoiceForm);
                fetchMetrics();
                fetchInvoices(1);
            } else if (res.errors) {
                setFormErrors(res.errors as Record<string, string[]>);
            } else {
                showToast(res.message || 'Ocurrió un error al emitir la factura', 'error');
            }
        } catch (error: any) {
            showToast('Ocurrió un error al emitir la factura', 'error');
        } finally {
            setSubmitting(false);
        }
    };

    const handleCancelInvoice = async () => {
        if (!invoiceToCancel) return;
        setCancelling(true);
        try {
            const res = await BillingServices.cancelInvoice(invoiceToCancel.id, cancelReason);
            if (res.code === 200 || res.status === 'success') {
                showToast(`Factura ${invoiceToCancel.invoice_number} anulada`);
                setCancelModalOpen(false);
                setInvoiceToCancel(null);
                setCancelReason('');
                fetchMetrics();
                fetchInvoices(currentPage);
            }
        } catch (error) {
            showToast('No se pudo anular la factura', 'error');
        } finally {
            setCancelling(false);
        }
    };

    const handleResendMail = async () => {
        if (!invoiceToResend) return;
        setResending(true);
        try {
            const res = await BillingServices.resendEmail(invoiceToResend.id, resendEmail || undefined);
            if (res.code === 200 || res.status === 'success') {
                showToast(res.message || res.message || 'Factura enviada por correo');
                setResendModalOpen(false);
                setInvoiceToResend(null);
            }
        } catch (error) {
            showToast('Error al reenviar el correo', 'error');
        } finally {
            setResending(false);
        }
    };

    const handleDownloadPdf = async (invoice: Invoice) => {
        try {
            await BillingServices.downloadPdf(invoice.id, `${invoice.invoice_number}.pdf`);
            showToast(`Descargando ${invoice.invoice_number}.pdf`);
        } catch (error) {
            showToast('Error al descargar el PDF', 'error');
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'paid':
                return <Badge color="success">PAGADA</Badge>;
            case 'issued':
                return <Badge color="info">EMITIDA</Badge>;
            case 'cancelled':
                return <Badge color="failure">ANULADA</Badge>;
            default:
                return <Badge color="gray">{status.toUpperCase()}</Badge>;
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <div className="space-y-6 p-4">
                {/* Notificación Toast */}
                {toastMessage && (
                    <div
                        className={`mb-4 rounded-lg p-4 text-sm ${
                            toastMessage.type === 'success'
                                ? 'bg-green-50 text-green-800 dark:bg-gray-800 dark:text-green-400'
                                : 'bg-red-50 text-red-800 dark:bg-gray-800 dark:text-red-400'
                        }`}
                    >
                        {toastMessage.text}
                    </div>
                )}

                {/* Breadcrumb & Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <Breadcrumb aria-label="Breadcrumb">
                            <BreadcrumbItem href={`/tenant/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Inicio
                            </BreadcrumbItem>
                            <BreadcrumbItem>Facturación</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="mt-2 flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
                            <LuReceipt className="h-7 w-7 text-blue-600" />
                            Facturación y Comprobantes
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Emite comprobantes fiscales directos, consulta ventas y gestiona facturas.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button color="blue" onClick={() => setCreateModalOpen(true)}>
                            <HiPlus className="mr-2 h-4 w-4" />
                            Emitir Factura Manual
                        </Button>
                        <Link href={`/billing/backoffice/${user_id}/settings`}>
                            <Button color="light">
                                <LuReceiptText className="mr-2 h-4 w-4 text-gray-600" />
                                Datos Fiscales
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Métricas KPI Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="border-l-4 border-l-blue-600">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase">Total Facturado</p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white">
                                    ${metrics.total_billed.toLocaleString('es-CL', { minimumFractionDigits: 2 })}
                                </h3>
                            </div>
                            <div className="rounded-xl bg-blue-50 p-3 dark:bg-blue-900/30">
                                <HiCurrencyDollar className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-l-green-600">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase">Facturas Pagadas</p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white">{metrics.total_paid}</h3>
                            </div>
                            <div className="rounded-xl bg-green-50 p-3 dark:bg-green-900/30">
                                <LuFileCheck className="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-l-cyan-600">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase">Facturas Emitidas</p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white">{metrics.total_issued}</h3>
                            </div>
                            <div className="rounded-xl bg-cyan-50 p-3 dark:bg-cyan-900/30">
                                <HiDocumentText className="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-l-red-600">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase">Facturas Anuladas</p>
                                <h3 className="text-2xl font-bold text-gray-900 dark:text-white">{metrics.total_cancelled}</h3>
                            </div>
                            <div className="rounded-xl bg-red-50 p-3 dark:bg-red-900/30">
                                <HiXCircle className="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* Barra de Filtros */}
                <Card>
                    <form onSubmit={handleSearchSubmit} className="flex flex-col items-center justify-between gap-4 md:flex-row">
                        <div className="flex w-full flex-1 flex-col gap-3 sm:flex-row">
                            <div className="relative flex-1">
                                <TextInput
                                    icon={HiSearch}
                                    placeholder="Buscar por N° factura, cliente, email o RUT/RFC..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>

                            <Select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="w-full sm:w-44">
                                <option value="">Todos los estados</option>
                                <option value="issued">Emitidas</option>
                                <option value="paid">Pagadas</option>
                                <option value="cancelled">Anuladas</option>
                            </Select>
                        </div>

                        <div className="flex w-full items-center gap-2 md:w-auto">
                            <Button type="submit" color="blue" size="sm">
                                <HiSearch className="mr-1 h-4 w-4" />
                                Filtrar
                            </Button>
                            <Button
                                type="button"
                                color="light"
                                size="sm"
                                onClick={() => {
                                    setSearch('');
                                    setStatusFilter('');
                                    fetchInvoices(1);
                                }}
                            >
                                <HiRefresh className="h-4 w-4" />
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* Tabla de Facturas */}
                <Card>
                    {loading ? (
                        <div className="flex items-center justify-center py-16">
                            <Spinner size="xl" />
                        </div>
                    ) : invoices.length === 0 ? (
                        <div className="py-12 text-center">
                            <HiDocumentText className="mx-auto mb-3 h-12 w-12 text-gray-400" />
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">No se encontraron facturas</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                {search || statusFilter
                                    ? 'Prueba cambiando los términos de búsqueda o filtros.'
                                    : 'Comienza emitiendo tu primera factura directa.'}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table hoverable>
                                <TableHead>
                                    <TableHeadCell>Factura</TableHeadCell>
                                    <TableHeadCell>Cliente / Receptor</TableHeadCell>
                                    <TableHeadCell>Fecha Emisión</TableHeadCell>
                                    <TableHeadCell>Total</TableHeadCell>
                                    <TableHeadCell>Método</TableHeadCell>
                                    <TableHeadCell>Estado</TableHeadCell>
                                    <TableHeadCell className="text-right">Acciones</TableHeadCell>
                                </TableHead>
                                <TableBody className="divide-y">
                                    {invoices.map((inv) => (
                                        <TableRow key={inv.id} className="bg-white dark:border-gray-700 dark:bg-gray-800">
                                            <TableCell className="font-bold whitespace-nowrap text-blue-600 dark:text-blue-400">
                                                <Link href={`/billing/backoffice/${user_id}/invoice/${inv.id}`} className="hover:underline">
                                                    {inv.invoice_number}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-medium text-gray-900 dark:text-white">{inv.billing_customer_name}</div>
                                                <div className="text-xs text-gray-500">
                                                    {inv.billing_customer_email}{' '}
                                                    {inv.billing_customer_tax_id ? `• ${inv.billing_customer_tax_id}` : ''}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-sm whitespace-nowrap text-gray-600 dark:text-gray-300">
                                                {inv.issue_date}
                                            </TableCell>
                                            <TableCell className="font-bold whitespace-nowrap text-gray-900 dark:text-white">
                                                ${inv.total.toLocaleString('es-CL', { minimumFractionDigits: 2 })} {inv.currency}
                                            </TableCell>
                                            <TableCell className="text-xs text-gray-500 uppercase">{inv.payment_method}</TableCell>
                                            <TableCell>{getStatusBadge(inv.status)}</TableCell>
                                            <TableCell className="text-right whitespace-nowrap">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Link href={`/billing/backoffice/${user_id}/invoice/${inv.id}`}>
                                                        <Button size="xs" color="light" title="Ver detalle e imprimir">
                                                            <HiEye className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </Link>

                                                    <Button size="xs" color="light" title="Descargar PDF" onClick={() => handleDownloadPdf(inv)}>
                                                        <HiDocumentDownload className="h-3.5 w-3.5 text-blue-600" />
                                                    </Button>

                                                    <Button
                                                        size="xs"
                                                        color="light"
                                                        title="Reenviar por Email"
                                                        onClick={() => {
                                                            setInvoiceToResend(inv);
                                                            setResendEmail(inv.billing_customer_email);
                                                            setResendModalOpen(true);
                                                        }}
                                                    >
                                                        <HiMail className="h-3.5 w-3.5 text-gray-600" />
                                                    </Button>

                                                    {inv.status !== 'cancelled' && (
                                                        <Button
                                                            size="xs"
                                                            color="light"
                                                            title="Anular Factura"
                                                            onClick={() => {
                                                                setInvoiceToCancel(inv);
                                                                setCancelModalOpen(true);
                                                            }}
                                                        >
                                                            <HiTrash className="h-3.5 w-3.5 text-red-600" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {totalPages > 1 && (
                                <div className="flex items-center justify-between pt-4">
                                    <span className="text-sm text-gray-600 dark:text-gray-400">
                                        Mostrando {invoices.length} de {totalItems} facturas
                                    </span>
                                    <Pagination
                                        currentPage={currentPage}
                                        totalPages={totalPages}
                                        onPageChange={(page) => fetchInvoices(page)}
                                        showIcons
                                    />
                                </div>
                            )}
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal: Emitir Factura Manual / Mostrador */}
            <Modal show={createModalOpen} onClose={() => setCreateModalOpen(false)} size="4xl">
                <ModalHeader>Emitir Factura Manual / Directa</ModalHeader>
                <form onSubmit={handleCreateInvoice}>
                    <ModalBody className="max-h-[75vh] space-y-4 overflow-y-auto">
                        <div className="border-b pb-3 dark:border-gray-700">
                            <h4 className="mb-2 text-sm font-semibold text-gray-900 uppercase dark:text-white">1. Datos del Cliente / Receptor</h4>
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div>
                                    <Label htmlFor="customer_name">Razón Social / Nombre *</Label>
                                    <TextInput
                                        id="customer_name"
                                        required
                                        value={form.customer_name}
                                        onChange={(e) => setForm({ ...form, customer_name: e.target.value })}
                                        placeholder="Ej: Juan Perez"
                                    />
                                    {formErrors.customer_name && <span className="text-xs text-red-600">{formErrors.customer_name[0]}</span>}
                                </div>

                                <div>
                                    <Label htmlFor="customer_tax_id">RUT / RFC / NIF / RUC</Label>
                                    <TextInput
                                        id="customer_tax_id"
                                        value={form.customer_tax_id || ''}
                                        onChange={(e) => setForm({ ...form, customer_tax_id: e.target.value })}
                                        placeholder="Ej: 12345678-9"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="customer_email">Correo Electrónico *</Label>
                                    <TextInput
                                        id="customer_email"
                                        type="email"
                                        required
                                        value={form.customer_email}
                                        onChange={(e) => setForm({ ...form, customer_email: e.target.value })}
                                        placeholder="cliente@email.com"
                                    />
                                    {formErrors.customer_email && <span className="text-xs text-red-600">{formErrors.customer_email[0]}</span>}
                                </div>

                                <div className="md:col-span-2">
                                    <Label htmlFor="customer_address_line_1">Dirección Legal *</Label>
                                    <TextInput
                                        id="customer_address_line_1"
                                        required
                                        value={form.customer_address_line_1}
                                        onChange={(e) => setForm({ ...form, customer_address_line_1: e.target.value })}
                                        placeholder="Av. Providencia 1234"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="customer_city">Ciudad *</Label>
                                    <TextInput
                                        id="customer_city"
                                        required
                                        value={form.customer_city}
                                        onChange={(e) => setForm({ ...form, customer_city: e.target.value })}
                                        placeholder="Santiago"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="customer_state">Región / Estado *</Label>
                                    <TextInput
                                        id="customer_state"
                                        required
                                        value={form.customer_state}
                                        onChange={(e) => setForm({ ...form, customer_state: e.target.value })}
                                        placeholder="RM"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="customer_postal_code">Código Postal *</Label>
                                    <TextInput
                                        id="customer_postal_code"
                                        required
                                        value={form.customer_postal_code}
                                        onChange={(e) => setForm({ ...form, customer_postal_code: e.target.value })}
                                        placeholder="8320000"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="customer_country">País *</Label>
                                    <TextInput
                                        id="customer_country"
                                        required
                                        value={form.customer_country}
                                        onChange={(e) => setForm({ ...form, customer_country: e.target.value })}
                                        placeholder="Chile"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Conceptos / Ítems */}
                        <div className="border-b pb-3 dark:border-gray-700">
                            <div className="mb-2 flex items-center justify-between">
                                <h4 className="text-sm font-semibold text-gray-900 uppercase dark:text-white">2. Conceptos e Ítems a Facturar</h4>
                                <Button type="button" size="xs" color="light" onClick={handleAddItem}>
                                    <HiPlus className="mr-1 h-3.5 w-3.5" />
                                    Agregar Ítem
                                </Button>
                            </div>

                            <div className="space-y-3">
                                {form.items.map((item, idx) => (
                                    <div
                                        key={idx}
                                        className="flex flex-col items-start gap-2 rounded-lg bg-gray-50 p-3 md:flex-row md:items-center dark:bg-gray-700/40"
                                    >
                                        <div className="w-full flex-1">
                                            <Label className="text-xs">Descripción *</Label>
                                            <TextInput
                                                required
                                                sizing="sm"
                                                value={item.description}
                                                onChange={(e) => handleItemChange(idx, 'description', e.target.value)}
                                                placeholder="Ej: Producto o Servicio"
                                            />
                                        </div>

                                        <div className="w-20">
                                            <Label className="text-xs">Cant. *</Label>
                                            <TextInput
                                                type="number"
                                                min={1}
                                                required
                                                sizing="sm"
                                                value={item.quantity}
                                                onChange={(e) => handleItemChange(idx, 'quantity', parseInt(e.target.value) || 1)}
                                            />
                                        </div>

                                        <div className="w-28">
                                            <Label className="text-xs">P. Unit *</Label>
                                            <TextInput
                                                type="number"
                                                step="0.01"
                                                min={0}
                                                required
                                                sizing="sm"
                                                value={item.unit_price}
                                                onChange={(e) => handleItemChange(idx, 'unit_price', parseFloat(e.target.value) || 0)}
                                            />
                                        </div>

                                        <div className="w-24">
                                            <Label className="text-xs">IVA %</Label>
                                            <TextInput
                                                type="number"
                                                step="0.1"
                                                min={0}
                                                sizing="sm"
                                                value={item.tax_rate}
                                                onChange={(e) => handleItemChange(idx, 'tax_rate', parseFloat(e.target.value) || 0)}
                                            />
                                        </div>

                                        <div className="w-24">
                                            <Label className="text-xs">Desc. $</Label>
                                            <TextInput
                                                type="number"
                                                step="0.01"
                                                min={0}
                                                sizing="sm"
                                                value={item.discount_amount}
                                                onChange={(e) => handleItemChange(idx, 'discount_amount', parseFloat(e.target.value) || 0)}
                                            />
                                        </div>

                                        <div className="pt-5">
                                            <Button
                                                type="button"
                                                size="xs"
                                                color="failure"
                                                disabled={form.items.length <= 1}
                                                onClick={() => handleRemoveItem(idx)}
                                            >
                                                <HiTrash className="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Cuadro Resumen de Totales y Condiciones */}
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="notes">Notas / Condiciones de la Factura</Label>
                                <Textarea
                                    id="notes"
                                    rows={3}
                                    value={form.notes || ''}
                                    onChange={(e) => setForm({ ...form, notes: e.target.value })}
                                    placeholder="Instrucciones de pago o notas al cliente..."
                                />
                            </div>

                            <div className="space-y-2 rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-700/50">
                                <div className="flex justify-between text-gray-600 dark:text-gray-300">
                                    <span>Subtotal Neto:</span>
                                    <span>${calculatedSubtotal.toFixed(2)}</span>
                                </div>
                                {calculatedDiscount > 0 && (
                                    <div className="flex justify-between text-red-600">
                                        <span>Descuentos:</span>
                                        <span>-${calculatedDiscount.toFixed(2)}</span>
                                    </div>
                                )}
                                {calculatedTax > 0 && (
                                    <div className="flex justify-between text-gray-600 dark:text-gray-300">
                                        <span>Impuestos (IVA):</span>
                                        <span>${calculatedTax.toFixed(2)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between border-t pt-2 text-lg font-bold text-blue-600 dark:border-gray-600">
                                    <span>TOTAL:</span>
                                    <span>
                                        ${calculatedTotal.toFixed(2)} {form.currency}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </ModalBody>
                    <ModalFooter>
                        <Button color="blue" type="submit" disabled={submitting}>
                            {submitting ? <Spinner size="sm" className="mr-2" /> : <HiPlus className="mr-2 h-4 w-4" />}
                            Emitir Factura
                        </Button>
                        <Button color="gray" type="button" onClick={() => setCreateModalOpen(false)}>
                            Cancelar
                        </Button>
                    </ModalFooter>
                </form>
            </Modal>

            {/* Modal: Confirmación de Anulación */}
            <Modal show={cancelModalOpen} onClose={() => setCancelModalOpen(false)} size="md">
                <ModalHeader>Anular Factura {invoiceToCancel?.invoice_number}</ModalHeader>
                <ModalBody className="space-y-3">
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        ¿Estás seguro de que deseas anular esta factura? Esta acción quedará registrada en el historial.
                    </p>
                    <div>
                        <Label htmlFor="cancel_reason">Motivo de la anulación (opcional)</Label>
                        <TextInput
                            id="cancel_reason"
                            value={cancelReason}
                            onChange={(e) => setCancelReason(e.target.value)}
                            placeholder="Ej: Error en los datos del cliente / pedido devuelto"
                        />
                    </div>
                </ModalBody>
                <ModalFooter>
                    <Button color="failure" onClick={handleCancelInvoice} disabled={cancelling}>
                        {cancelling ? <Spinner size="sm" className="mr-2" /> : <HiTrash className="mr-2 h-4 w-4" />}
                        Confirmar Anulación
                    </Button>
                    <Button color="gray" onClick={() => setCancelModalOpen(false)}>
                        Cerrar
                    </Button>
                </ModalFooter>
            </Modal>

            {/* Modal: Reenviar Email */}
            <Modal show={resendModalOpen} onClose={() => setResendModalOpen(false)} size="md">
                <ModalHeader>Reenviar Factura por Correo</ModalHeader>
                <ModalBody className="space-y-3">
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        Se enviará la factura <strong>{invoiceToResend?.invoice_number}</strong> con su PDF adjunto.
                    </p>
                    <div>
                        <Label htmlFor="resend_email">Correo Electrónico de Destino</Label>
                        <TextInput id="resend_email" type="email" required value={resendEmail} onChange={(e) => setResendEmail(e.target.value)} />
                    </div>
                </ModalBody>
                <ModalFooter>
                    <Button color="blue" onClick={handleResendMail} disabled={resending}>
                        {resending ? <Spinner size="sm" className="mr-2" /> : <HiMail className="mr-2 h-4 w-4" />}
                        Enviar Correo
                    </Button>
                    <Button color="gray" onClick={() => setResendModalOpen(false)}>
                        Cancelar
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
};

export default BillingIndexPage;
