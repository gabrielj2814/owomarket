import Dashboard from "@/components/layouts/Dashboard";
import CouponServices from "@/Services/CouponServices";
import { Coupon } from "@/types/models/Coupon";
import { Head } from "@inertiajs/react";
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
} from "flowbite-react";
import { FC, useEffect, useState } from "react";
import {
    HiHome,
    HiRefresh,
    HiSearch,
    HiTicket,
    HiTrash,
} from "react-icons/hi";

interface CouponIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const CouponIndexPage: FC<CouponIndexPageProps> = ({ user_id, title, host, user_name }) => {
    const [coupons, setCoupons] = useState<Coupon[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [search, setSearch] = useState<string>("");
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(10);

    const [deleteModalOpen, setDeleteModalOpen] = useState<boolean>(false);
    const [couponToDelete, setCouponToDelete] = useState<Coupon | null>(null);
    const [actionLoading, setActionLoading] = useState<boolean>(false);

    const fetchCoupons = async (page = currentPage) => {
        setLoading(true);
        try {
            const res = await CouponServices.filtrar(
                search.trim() !== "" ? search.trim() : null,
                null,
                null,
                null,
                perPage,
                page
            );
            if (res?.data?.data && Array.isArray(res.data.data)) {
                setCoupons(res.data.data);
                if (res.data.pagination) {
                    setCurrentPage(res.data.pagination.current_page);
                    setTotalPages(res.data.pagination.last_page);
                    setTotalItems(res.data.pagination.total);
                }
            }
        } catch (err) {
            console.error("Error al cargar cupones", err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCoupons(1);
    }, [perPage]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchCoupons(1);
    };

    const confirmDelete = async () => {
        if (!couponToDelete) return;
        setActionLoading(true);
        try {
            const res = await CouponServices.delete(couponToDelete.id);
            if (res?.data?.code === 200) {
                setDeleteModalOpen(false);
                setCouponToDelete(null);
                fetchCoupons(currentPage);
            }
        } catch (err) {
            console.error("Error eliminando cupón", err);
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
                <Breadcrumb aria-label="Navegación" className="hidden lg:block bg-gray-50 px-5 py-3 rounded-lg dark:bg-gray-800 mb-5 border border-gray-100 dark:border-gray-700">
                    <BreadcrumbItem icon={HiHome} href={`/tenant/backoffice/${user_id}/dashboard`}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem href={`/product/backoffice/${user_id}/module`}>
                        Catálogo
                    </BreadcrumbItem>
                    <BreadcrumbItem>
                        Cupones
                    </BreadcrumbItem>
                </Breadcrumb>

                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiTicket className="w-8 h-8 text-pink-600 dark:text-pink-400" />
                            Cupones y Descuentos
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Crea códigos promocionales de descuento fijo o porcentual para tus clientes
                        </p>
                    </div>
                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <Button color="gray" onClick={() => fetchCoupons(currentPage)} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-2 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                    </div>
                </div>

                <Card className="mb-6 shadow-sm border-gray-100 dark:border-gray-700">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                Buscar por Código
                            </label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="Ej: VERANO2026, BIENVENIDO..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>
                </Card>

                <Card className="shadow-sm border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div className="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                        <Table hoverable>
                            <TableHead className="bg-gray-50 dark:bg-gray-700 text-xs uppercase tracking-wider">
                                <TableHeadCell>Código</TableHeadCell>
                                <TableHeadCell>Tipo</TableHeadCell>
                                <TableHeadCell>Valor</TableHeadCell>
                                <TableHeadCell>Uso</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-100 dark:divide-gray-700">
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center py-12">
                                            <Spinner size="xl" />
                                        </TableCell>
                                    </TableRow>
                                ) : coupons.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center py-12">
                                            <HiTicket className="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                            <p className="text-base font-semibold text-gray-700 dark:text-gray-300">
                                                No se encontraron cupones
                                            </p>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    coupons.map((c) => (
                                        <TableRow key={c.id} className="bg-white dark:bg-gray-800">
                                            <TableCell className="font-mono font-bold text-gray-900 dark:text-white">
                                                {c.code}
                                            </TableCell>
                                            <TableCell>
                                                <Badge color="purple" size="sm">
                                                    {c.type === "percentage" ? "Porcentaje" : "Monto Fijo"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="font-bold text-gray-900 dark:text-white">
                                                {c.type === "percentage" ? `${c.value}%` : `$${c.value.toFixed(2)}`}
                                            </TableCell>
                                            <TableCell className="text-xs text-gray-500">
                                                {c.used_count} / {c.usage_limit ?? "∞"}
                                            </TableCell>
                                            <TableCell>
                                                <Badge color={c.is_active ? "success" : "failure"} size="sm">
                                                    {c.is_active ? "Activo" : "Inactivo"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <button
                                                    onClick={() => {
                                                        setCouponToDelete(c);
                                                        setDeleteModalOpen(true);
                                                    }}
                                                    className="p-1.5 text-gray-500 hover:text-red-600 rounded-lg"
                                                    title="Eliminar"
                                                >
                                                    <HiTrash className="w-4 h-4" />
                                                </button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {totalPages > 1 && (
                        <div className="flex justify-center mt-6">
                            <Pagination
                                currentPage={currentPage}
                                totalPages={totalPages}
                                onPageChange={(p) => {
                                    setCurrentPage(p);
                                    fetchCoupons(p);
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
                            <h3 className="mb-2 text-lg font-bold text-gray-900 dark:text-white">
                                ¿Eliminar Cupón?
                            </h3>
                            <p className="mb-5 text-sm text-gray-500">
                                ¿Deseas eliminar el cupón <strong>"{couponToDelete?.code}"</strong>?
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

export default CouponIndexPage;
