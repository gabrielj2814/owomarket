import Dashboard from '@/components/layouts/Dashboard';
import TaxServices from '@/Services/TaxServices';
import { TaxRate } from '@/types/models/TaxRate';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Modal,
    ModalBody,
    ModalHeader,
    Pagination,
    Spinner,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    TextInput,
} from 'flowbite-react';
import { FC, useEffect, useState } from 'react';
import { HiHome, HiReceiptTax, HiRefresh, HiSearch, HiTrash } from 'react-icons/hi';

interface TaxIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const TaxIndexPage: FC<TaxIndexPageProps> = ({ user_id, title, host, user_name }) => {
    const [taxRates, setTaxRates] = useState<TaxRate[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [search, setSearch] = useState<string>('');
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(10);

    const [deleteModalOpen, setDeleteModalOpen] = useState<boolean>(false);
    const [taxToDelete, setTaxToDelete] = useState<TaxRate | null>(null);
    const [actionLoading, setActionLoading] = useState<boolean>(false);

    const fetchTaxRates = async (page = currentPage) => {
        setLoading(true);
        try {
            const res = await TaxServices.filtrar(search.trim() !== '' ? search.trim() : null, null, null, null, perPage, page);
            const items = Array.isArray(res.data) ? res.data : [];
            setTaxRates(items);

            const pagination = res.pagination;
            if (pagination) {
                setCurrentPage(pagination.current_page);
                setTotalPages(pagination.last_page);
                setTotalItems(pagination.total);
            }
        } catch (err) {
            console.error('Error al cargar tasas de impuestos', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTaxRates(1);
    }, [perPage]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchTaxRates(1);
    };

    const confirmDelete = async () => {
        if (!taxToDelete) return;
        setActionLoading(true);
        try {
            const res = await TaxServices.delete(taxToDelete.id);
            if (res.code === 200 || res.status === 'success') {
                setDeleteModalOpen(false);
                setTaxToDelete(null);
                fetchTaxRates(currentPage);
            }
        } catch (err) {
            console.error('Error eliminando tasa de impuesto', err);
        } finally {
            setActionLoading(false);
        }
    };

    return (
        <>
            <Head>
                <title>{title}</title>
            </Head>
            <Dashboard user_uuid={user_id}>
                <Breadcrumb
                    aria-label="Navegación"
                    className="mb-5 hidden rounded-lg border border-gray-100 bg-gray-50 px-5 py-3 lg:block dark:border-gray-700 dark:bg-gray-800"
                >
                    <BreadcrumbItem icon={HiHome} href={`/tenant/backoffice/${user_id}/dashboard`}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem>Configuración</BreadcrumbItem>
                    <BreadcrumbItem>Impuestos</BreadcrumbItem>
                </Breadcrumb>

                <div className="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                            <HiReceiptTax className="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
                            Tasas de Impuestos (IVA / Tax Rates)
                        </h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Configura los porcentajes de impuestos aplicables en checkout según país o estado
                        </p>
                    </div>
                    <div className="flex w-full items-center gap-3 sm:w-auto">
                        <Button color="gray" onClick={() => fetchTaxRates(currentPage)} disabled={loading}>
                            <HiRefresh className={`mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                    </div>
                </div>

                <Card className="mb-6 border-gray-100 shadow-sm dark:border-gray-700">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2">
                        <div>
                            <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Buscar por Nombre</label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="Ej: IVA General, Tax Rate 16%..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>
                </Card>

                <Card className="overflow-hidden border-gray-100 shadow-sm dark:border-gray-700">
                    <div className="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                        <Table hoverable>
                            <TableHead className="bg-gray-50 text-xs tracking-wider uppercase dark:bg-gray-700">
                                <TableHeadCell>Nombre del Impuesto</TableHeadCell>
                                <TableHeadCell>Porcentaje</TableHeadCell>
                                <TableHeadCell>Prioridad</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-100 dark:divide-gray-700">
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-12 text-center">
                                            <Spinner size="xl" />
                                        </TableCell>
                                    </TableRow>
                                ) : taxRates.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-12 text-center">
                                            <HiReceiptTax className="mx-auto mb-2 h-12 w-12 text-gray-300 dark:text-gray-600" />
                                            <p className="text-base font-semibold text-gray-700 dark:text-gray-300">
                                                No se encontraron tasas de impuestos
                                            </p>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    taxRates.map((t) => (
                                        <TableRow key={t.id} className="bg-white dark:bg-gray-800">
                                            <TableCell className="font-semibold text-gray-900 dark:text-white">{t.name}</TableCell>
                                            <TableCell className="font-bold text-gray-900 dark:text-white">{t.rate}%</TableCell>
                                            <TableCell>
                                                <Badge color="gray" size="sm">
                                                    {t.priority}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge color={t.is_active ? 'success' : 'failure'} size="sm">
                                                    {t.is_active ? 'Activo' : 'Inactivo'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <button
                                                    onClick={() => {
                                                        setTaxToDelete(t);
                                                        setDeleteModalOpen(true);
                                                    }}
                                                    className="rounded-lg p-1.5 text-gray-500 hover:text-red-600"
                                                    title="Eliminar"
                                                >
                                                    <HiTrash className="h-4 w-4" />
                                                </button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {totalPages > 1 && (
                        <div className="mt-6 flex justify-center">
                            <Pagination
                                currentPage={currentPage}
                                totalPages={totalPages}
                                onPageChange={(p) => {
                                    setCurrentPage(p);
                                    fetchTaxRates(p);
                                }}
                                showIcons
                            />
                        </div>
                    )}
                </Card>

                {/* Delete Modal */}
                <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} size="md" popup>
                    <ModalHeader />
                    <ModalBody>
                        <div className="text-center">
                            <HiTrash className="mx-auto mb-4 h-14 w-14 text-red-500" />
                            <h3 className="mb-2 text-lg font-bold text-gray-900 dark:text-white">¿Eliminar Tasa de Impuesto?</h3>
                            <p className="mb-5 text-sm text-gray-500">
                                ¿Deseas eliminar la tasa <strong>"{taxToDelete?.name}"</strong>?
                            </p>
                            <div className="flex justify-center gap-3">
                                <Button color="gray" onClick={() => setDeleteModalOpen(false)}>
                                    Cancelar
                                </Button>
                                <Button color="failure" onClick={confirmDelete} disabled={actionLoading}>
                                    {actionLoading ? <Spinner size="sm" className="mr-2" /> : null}
                                    Sí, eliminar
                                </Button>
                            </div>
                        </div>
                    </ModalBody>
                </Modal>
            </Dashboard>
        </>
    );
};

export default TaxIndexPage;
