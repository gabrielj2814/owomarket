import Dashboard from '@/components/layouts/Dashboard';
import BillingServices from '@/Services/BillingServices';
import { Invoice } from '@/types/models/Invoice';
import { Head, Link } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Spinner,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
} from 'flowbite-react';
import { FC, useEffect, useState } from 'react';
import { HiArrowLeft, HiDocumentDownload, HiHome, HiMail, HiPrinter } from 'react-icons/hi';
import { LuReceipt } from 'react-icons/lu';

interface ShowInvoiceDetailPageProps {
    user_id: string;
    invoice_id: string;
    title: string;
    host: string;
    user_name: string;
}

const ShowInvoiceDetailPage: FC<ShowInvoiceDetailPageProps> = ({ user_id, invoice_id, title }) => {
    const [invoice, setInvoice] = useState<Invoice | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [downloading, setDownloading] = useState<boolean>(false);
    const [resending, setResending] = useState<boolean>(false);
    const [toastMessage, setToastMessage] = useState<string | null>(null);

    const showToast = (text: string) => {
        setToastMessage(text);
        setTimeout(() => setToastMessage(null), 4000);
    };

    const fetchInvoice = async () => {
        setLoading(true);
        try {
            const res = await BillingServices.getInvoice(invoice_id);
            if (res.data) {
                setInvoice(res.data);
            }
        } catch (error) {
            console.error('Error al cargar factura:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (invoice_id) {
            fetchInvoice();
        }
    }, [invoice_id]);

    const handlePrint = () => {
        window.print();
    };

    const handleDownloadPdf = async () => {
        if (!invoice) return;
        setDownloading(true);
        try {
            await BillingServices.downloadPdf(invoice.id, `${invoice.invoice_number}.pdf`);
            showToast('PDF descargado con éxito');
        } catch (error) {
            showToast('Error al descargar PDF');
        } finally {
            setDownloading(false);
        }
    };

    const handleResendMail = async () => {
        if (!invoice) return;
        setResending(true);
        try {
            const res = await BillingServices.resendEmail(invoice.id);
            if (res.code === 200 || res.status === 'success') {
                showToast(res.message || res.message || 'Correo enviado');
            }
        } catch (error) {
            showToast('Error al enviar correo');
        } finally {
            setResending(false);
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'paid':
                return (
                    <Badge color="success" size="sm">
                        PAGADA
                    </Badge>
                );
            case 'issued':
                return (
                    <Badge color="info" size="sm">
                        EMITIDA
                    </Badge>
                );
            case 'cancelled':
                return (
                    <Badge color="failure" size="sm">
                        ANULADA
                    </Badge>
                );
            default:
                return (
                    <Badge color="gray" size="sm">
                        {status.toUpperCase()}
                    </Badge>
                );
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <div className="mx-auto max-w-5xl space-y-6 p-4">
                {/* Notificación Toast */}
                {toastMessage && (
                    <div className="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-gray-800 dark:text-green-400">{toastMessage}</div>
                )}

                {/* Toolbar Superior */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between print:hidden">
                    <div>
                        <Breadcrumb aria-label="Breadcrumb">
                            <BreadcrumbItem href={`/tenant/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Inicio
                            </BreadcrumbItem>
                            <BreadcrumbItem href={`/billing/backoffice/${user_id}/module`}>Facturación</BreadcrumbItem>
                            <BreadcrumbItem>Detalle</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="mt-2 flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-white">
                            <LuReceipt className="h-7 w-7 text-blue-600" />
                            Factura {invoice?.invoice_number || '...'}
                        </h1>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link href={`/billing/backoffice/${user_id}/module`}>
                            <Button color="light" size="sm">
                                <HiArrowLeft className="mr-1 h-4 w-4" />
                                Volver
                            </Button>
                        </Link>
                        <Button color="light" size="sm" onClick={handlePrint}>
                            <HiPrinter className="mr-1 h-4 w-4" />
                            Imprimir
                        </Button>
                        <Button color="blue" size="sm" onClick={handleDownloadPdf} disabled={downloading}>
                            {downloading ? <Spinner size="sm" className="mr-1" /> : <HiDocumentDownload className="mr-1 h-4 w-4" />}
                            Descargar PDF
                        </Button>
                        <Button color="light" size="sm" onClick={handleResendMail} disabled={resending}>
                            {resending ? <Spinner size="sm" className="mr-1" /> : <HiMail className="mr-1 h-4 w-4" />}
                            Enviar Email
                        </Button>
                    </div>
                </div>

                {loading || !invoice ? (
                    <div className="flex items-center justify-center py-20">
                        <Spinner size="xl" />
                    </div>
                ) : (
                    /* Tarjeta Documento Imprimible */
                    <Card className="space-y-6 rounded-lg border bg-white p-6 shadow-sm print:border-none print:shadow-none">
                        {/* Cabecera del Documento */}
                        <div className="flex flex-col items-start justify-between gap-4 border-b pb-6 sm:flex-row dark:border-gray-700">
                            <div>
                                <h2 className="text-2xl font-black text-gray-900 dark:text-white">{invoice.issuer_snapshot.legal_name}</h2>
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    RUT / NIF: <strong className="text-gray-900 dark:text-white">{invoice.issuer_snapshot.tax_id}</strong>
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    {invoice.issuer_snapshot.address?.address_line_1}, {invoice.issuer_snapshot.address?.city}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-300">Email: {invoice.issuer_snapshot.billing_email}</p>
                            </div>

                            <div className="rounded-lg bg-blue-50 p-4 text-left sm:text-right dark:bg-gray-800">
                                <div className="text-xs font-bold tracking-wider text-blue-600 uppercase dark:text-blue-400">FACTURA DE VENTA</div>
                                <div className="my-1 text-2xl font-extrabold text-gray-900 dark:text-white">{invoice.invoice_number}</div>
                                <div className="mb-2">{getStatusBadge(invoice.status)}</div>
                                <p className="text-xs text-gray-500">
                                    Fecha Emisión: <strong>{invoice.issue_date}</strong>
                                </p>
                                {invoice.due_date && (
                                    <p className="text-xs text-gray-500">
                                        Fecha Vencimiento: <strong>{invoice.due_date}</strong>
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Datos del Cliente / Receptor */}
                        <div className="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/60">
                            <h3 className="mb-2 text-xs font-bold tracking-wider text-gray-500 uppercase">FACTURADO A (CLIENTE / RECEPTOR)</h3>
                            <div className="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                <div>
                                    <p className="font-semibold text-gray-900 dark:text-white">{invoice.billing_customer_name}</p>
                                    {invoice.billing_customer_tax_id && (
                                        <p className="text-gray-600 dark:text-gray-300">RUT/NIF: {invoice.billing_customer_tax_id}</p>
                                    )}
                                    <p className="text-gray-600 dark:text-gray-300">Email: {invoice.billing_customer_email}</p>
                                </div>
                                <div>
                                    <p className="text-gray-600 dark:text-gray-300">{invoice.billing_customer_address?.address_line_1}</p>
                                    <p className="text-gray-600 dark:text-gray-300">
                                        {invoice.billing_customer_address?.city}, {invoice.billing_customer_address?.state}{' '}
                                        {invoice.billing_customer_address?.postal_code}
                                    </p>
                                    <p className="text-gray-600 dark:text-gray-300">{invoice.billing_customer_address?.country}</p>
                                </div>
                            </div>
                        </div>

                        {/* Tabla de Conceptos / Ítems */}
                        <div className="overflow-x-auto">
                            <Table hoverable>
                                <TableHead>
                                    <TableHeadCell>Descripción</TableHeadCell>
                                    <TableHeadCell className="text-center">Cant.</TableHeadCell>
                                    <TableHeadCell className="text-right">Precio Unit.</TableHeadCell>
                                    <TableHeadCell className="text-center">IVA %</TableHeadCell>
                                    <TableHeadCell className="text-right">Total</TableHeadCell>
                                </TableHead>
                                <TableBody className="divide-y">
                                    {invoice.items.map((item, index) => (
                                        <TableRow key={item.id || index}>
                                            <TableCell className="font-medium text-gray-900 dark:text-white">
                                                {item.description}
                                                {item.sku && <span className="block text-xs text-gray-400">SKU: {item.sku}</span>}
                                            </TableCell>
                                            <TableCell className="text-center">{item.quantity}</TableCell>
                                            <TableCell className="text-right">${item.unit_price.toFixed(2)}</TableCell>
                                            <TableCell className="text-center">{item.tax_rate}%</TableCell>
                                            <TableCell className="text-right font-semibold text-gray-900 dark:text-white">
                                                ${item.total.toFixed(2)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Resumen de Totales y Notas */}
                        <div className="grid grid-cols-1 gap-6 border-t pt-4 md:grid-cols-2 dark:border-gray-700">
                            <div>
                                {invoice.notes && (
                                    <div className="mb-4">
                                        <h4 className="mb-1 text-xs font-bold text-gray-500 uppercase">Notas:</h4>
                                        <p className="text-sm whitespace-pre-line text-gray-600 dark:text-gray-300">{invoice.notes}</p>
                                    </div>
                                )}
                                {invoice.issuer_snapshot.invoice_footer_notes && (
                                    <div>
                                        <h4 className="mb-1 text-xs font-bold text-gray-500 uppercase">Condiciones Legales:</h4>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">{invoice.issuer_snapshot.invoice_footer_notes}</p>
                                    </div>
                                )}
                            </div>

                            <div className="space-y-2 rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-800">
                                <div className="flex justify-between text-gray-600 dark:text-gray-300">
                                    <span>Subtotal Neto:</span>
                                    <span>${invoice.subtotal.toFixed(2)}</span>
                                </div>
                                {invoice.discount_amount > 0 && (
                                    <div className="flex justify-between text-red-600">
                                        <span>Descuento:</span>
                                        <span>-${invoice.discount_amount.toFixed(2)}</span>
                                    </div>
                                )}
                                <div className="flex justify-between text-gray-600 dark:text-gray-300">
                                    <span>Impuestos (IVA):</span>
                                    <span>${invoice.tax_amount.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between border-t pt-2 text-xl font-black text-blue-600 dark:border-gray-700">
                                    <span>TOTAL GENERAL:</span>
                                    <span>
                                        ${invoice.total.toFixed(2)} {invoice.currency}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </Card>
                )}
            </div>
        </Dashboard>
    );
};

export default ShowInvoiceDetailPage;
