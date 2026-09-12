import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import CustomerPortalServices, { CustomerInvoiceData } from '@/Services/CustomerPortalServices';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import { Head } from '@inertiajs/react';
import {
    Badge,
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
import React, { useEffect, useState } from 'react';
import {
    HiOutlineCheckBadge,
    HiOutlineDocumentArrowDown,
    HiOutlineDocumentText,
} from 'react-icons/hi2';

/**
 * Facturas del comprador.
 *
 * El aspecto de la tabla lo pone `portalTheme`: Flowbite trae `text-sm` y `px-6 py-4`, que al
 * lado de las tarjetas del portal se ve enorme. Ajustarlo aquí con `className` habría dejado
 * esta tabla distinta de la siguiente que alguien escriba.
 */
export const CustomerInvoicesPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [invoices, setInvoices] = useState<CustomerInvoiceData[]>([]);
    const [loading, setLoading] = useState(true);
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);

    useEffect(() => {
        if (!customer?.id) return;
        setLoading(true);
        CustomerPortalServices.getInvoices(customer.id)
            .then((res) => {
                if (res.data) {
                    setInvoices(res.data);
                }
            })
            .catch(() => setLoadError(true))
            .finally(() => setLoading(false));
    }, [customer?.id]);

    return (
        <CustomerAccountLayout
            title="Mis Facturas Electrónicas PDF"
            description="Descarga comprobantes fiscales y facturas digitales con montos en USD y Bolívares a tasa oficial BCV."
        >
            {loadError && <PortalLoadError />}

            <Head title="Mis Facturas - OwOMarket" />

            <Card>
                <div className="mb-2 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h3 className="flex items-center gap-2 text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                        <HiOutlineDocumentText className="h-5 w-5 text-blue-600" />
                        Comprobantes Emitidos ({invoices.length})
                    </h3>
                    <p className="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Todas las facturas cumplen con la normativa del BCV y Ley de Impuesto a las Grandes
                        Transacciones Financieras.
                    </p>
                </div>

                {loading ? (
                    <div className="py-16 text-center">
                        <Spinner aria-label="Cargando facturas" />
                        <p className="mt-2 text-xs font-medium text-gray-400">Cargando facturas...</p>
                    </div>
                ) : invoices.length === 0 ? (
                    <div data-testid="facturas-vacio" className="py-12 text-center">
                        <HiOutlineDocumentText className="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-700" />
                        <h4 className="mb-1 text-base font-bold text-gray-900 dark:text-white">
                            No tienes facturas emitidas
                        </h4>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Cuando completes una compra en el marketplace, podrás descargar tu factura PDF aquí.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead>
                                <TableRow>
                                    <TableHeadCell>N° Factura</TableHeadCell>
                                    <TableHeadCell>Orden</TableHeadCell>
                                    <TableHeadCell>Fecha</TableHeadCell>
                                    <TableHeadCell className="text-right">Total (USD)</TableHeadCell>
                                    <TableHeadCell className="text-right">Total (VES BCV)</TableHeadCell>
                                    <TableHeadCell className="text-center">Estado</TableHeadCell>
                                    <TableHeadCell className="text-center">Acción</TableHeadCell>
                                </TableRow>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {invoices.map((inv) => (
                                    <TableRow key={inv.id}>
                                        <TableCell className="font-bold text-gray-900 dark:text-white">
                                            {inv.invoice_number}
                                        </TableCell>
                                        <TableCell className="font-medium text-gray-600 dark:text-gray-300">
                                            {inv.order_number}
                                        </TableCell>
                                        <TableCell className="text-gray-500">{inv.date}</TableCell>
                                        <TableCell className="text-right font-black text-gray-900 dark:text-white">
                                            ${inv.total_usd.toFixed(2)}
                                        </TableCell>
                                        <TableCell className="text-right font-black text-emerald-600 dark:text-emerald-400">
                                            Bs.{' '}
                                            {inv.total_ves.toLocaleString('es-VE', { minimumFractionDigits: 2 })}
                                        </TableCell>
                                        <TableCell>
                                            <Badge color="success" size="xs" icon={HiOutlineCheckBadge} className="mx-auto w-fit">
                                                Pagada
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Button
                                                as="a"
                                                href={inv.pdf_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                color="primary"
                                                size="xs"
                                                className="mx-auto w-fit"
                                            >
                                                <HiOutlineDocumentArrowDown className="mr-1 h-3.5 w-3.5" /> PDF
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </Card>
        </CustomerAccountLayout>
    );
};

export default CustomerInvoicesPage;
