import Dashboard from "@/components/layouts/Dashboard";
import BillingServices from "@/Services/BillingServices";
import { Invoice } from "@/types/models/Invoice";
import { Head, Link } from "@inertiajs/react";
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
} from "flowbite-react";
import { FC, useEffect, useState } from "react";
import {
    HiArrowLeft,
    HiDocumentDownload,
    HiHome,
    HiMail,
    HiPrinter,
} from "react-icons/hi";
import { LuReceipt } from "react-icons/lu";

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
            if (res?.data?.data) {
                setInvoice(res.data.data);
            }
        } catch (error) {
            console.error("Error al cargar factura:", error);
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
            showToast("PDF descargado con éxito");
        } catch (error) {
            showToast("Error al descargar PDF");
        } finally {
            setDownloading(false);
        }
    };

    const handleResendMail = async () => {
        if (!invoice) return;
        setResending(true);
        try {
            const res = await BillingServices.resendEmail(invoice.id);
            if ((res as any)?.code === 200 || (res as any)?.status === "success" || res?.data?.code === 200) {
                showToast((res as any)?.message || res?.data?.message || "Correo enviado");
            }
        } catch (error) {
            showToast("Error al enviar correo");
        } finally {
            setResending(false);
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case "paid":
                return <Badge color="success" size="sm">PAGADA</Badge>;
            case "issued":
                return <Badge color="info" size="sm">EMITIDA</Badge>;
            case "cancelled":
                return <Badge color="failure" size="sm">ANULADA</Badge>;
            default:
                return <Badge color="gray" size="sm">{status.toUpperCase()}</Badge>;
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <div className="p-4 space-y-6 max-w-5xl mx-auto">
                {/* Notificación Toast */}
                {toastMessage && (
                    <div className="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">
                        {toastMessage}
                    </div>
                )}

                {/* Toolbar Superior */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
                    <div>
                        <Breadcrumb aria-label="Breadcrumb">
                            <BreadcrumbItem href={`/tenant/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Inicio
                            </BreadcrumbItem>
                            <BreadcrumbItem href={`/billing/backoffice/${user_id}/module`}>
                                Facturación
                            </BreadcrumbItem>
                            <BreadcrumbItem>Detalle</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white mt-2 flex items-center gap-2">
                            <LuReceipt className="w-7 h-7 text-blue-600" />
                            Factura {invoice?.invoice_number || "..."}
                        </h1>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link href={`/billing/backoffice/${user_id}/module`}>
                            <Button color="light" size="sm">
                                <HiArrowLeft className="w-4 h-4 mr-1" />
                                Volver
                            </Button>
                        </Link>
                        <Button color="light" size="sm" onClick={handlePrint}>
                            <HiPrinter className="w-4 h-4 mr-1" />
                            Imprimir
                        </Button>
                        <Button color="blue" size="sm" onClick={handleDownloadPdf} disabled={downloading}>
                            {downloading ? <Spinner size="sm" className="mr-1" /> : <HiDocumentDownload className="w-4 h-4 mr-1" />}
                            Descargar PDF
                        </Button>
                        <Button color="light" size="sm" onClick={handleResendMail} disabled={resending}>
                            {resending ? <Spinner size="sm" className="mr-1" /> : <HiMail className="w-4 h-4 mr-1" />}
                            Enviar Email
                        </Button>
                    </div>
                </div>

                {loading || !invoice ? (
                    <div className="flex justify-center items-center py-20">
                        <Spinner size="xl" />
                    </div>
                ) : (
                    /* Tarjeta Documento Imprimible */
                    <Card className="bg-white border rounded-lg shadow-sm print:border-none print:shadow-none p-6 space-y-6">
                        {/* Cabecera del Documento */}
                        <div className="flex flex-col sm:flex-row justify-between items-start gap-4 border-b pb-6 dark:border-gray-700">
                            <div>
                                <h2 className="text-2xl font-black text-gray-900 dark:text-white">
                                    {invoice.issuer_snapshot.legal_name}
                                </h2>
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    RUT / NIF: <strong className="text-gray-900 dark:text-white">{invoice.issuer_snapshot.tax_id}</strong>
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    {invoice.issuer_snapshot.address?.address_line_1}, {invoice.issuer_snapshot.address?.city}
                                </p>
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    Email: {invoice.issuer_snapshot.billing_email}
                                </p>
                            </div>

                            <div className="text-left sm:text-right bg-blue-50 dark:bg-gray-800 p-4 rounded-lg">
                                <div className="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                    FACTURA DE VENTA
                                </div>
                                <div className="text-2xl font-extrabold text-gray-900 dark:text-white my-1">
                                    {invoice.invoice_number}
                                </div>
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
                        <div className="bg-gray-50 dark:bg-gray-800/60 p-4 rounded-lg">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">
                                FACTURADO A (CLIENTE / RECEPTOR)
                            </h3>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                                <div>
                                    <p className="font-semibold text-gray-900 dark:text-white">
                                        {invoice.billing_customer_name}
                                    </p>
                                    {invoice.billing_customer_tax_id && (
                                        <p className="text-gray-600 dark:text-gray-300">
                                            RUT/NIF: {invoice.billing_customer_tax_id}
                                        </p>
                                    )}
                                    <p className="text-gray-600 dark:text-gray-300">
                                        Email: {invoice.billing_customer_email}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-gray-600 dark:text-gray-300">
                                        {invoice.billing_customer_address?.address_line_1}
                                    </p>
                                    <p className="text-gray-600 dark:text-gray-300">
                                        {invoice.billing_customer_address?.city}, {invoice.billing_customer_address?.state} {invoice.billing_customer_address?.postal_code}
                                    </p>
                                    <p className="text-gray-600 dark:text-gray-300">
                                        {invoice.billing_customer_address?.country}
                                    </p>
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
                                                {item.sku && <span className="text-xs text-gray-400 block">SKU: {item.sku}</span>}
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
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t dark:border-gray-700">
                            <div>
                                {invoice.notes && (
                                    <div className="mb-4">
                                        <h4 className="text-xs font-bold uppercase text-gray-500 mb-1">Notas:</h4>
                                        <p className="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{invoice.notes}</p>
                                    </div>
                                )}
                                {invoice.issuer_snapshot.invoice_footer_notes && (
                                    <div>
                                        <h4 className="text-xs font-bold uppercase text-gray-500 mb-1">Condiciones Legales:</h4>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            {invoice.issuer_snapshot.invoice_footer_notes}
                                        </p>
                                    </div>
                                )}
                            </div>

                            <div className="space-y-2 text-sm bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
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
                                <div className="flex justify-between text-xl font-black text-blue-600 border-t pt-2 dark:border-gray-700">
                                    <span>TOTAL GENERAL:</span>
                                    <span>${invoice.total.toFixed(2)} {invoice.currency}</span>
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
