import Dashboard from "@/components/layouts/Dashboard";
import BrandServices from "@/Services/BrandServices";
import { Brand } from "@/types/models/Brand";
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
    HiBookmark,
    HiHome,
    HiRefresh,
    HiSearch,
    HiTrash,
} from "react-icons/hi";
import { LuTag } from "react-icons/lu";

interface BrandIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const BrandIndexPage: FC<BrandIndexPageProps> = ({ user_id, title, host, user_name }) => {
    const [brands, setBrands] = useState<Brand[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [search, setSearch] = useState<string>("");
    const [isActiveFilter, setIsActiveFilter] = useState<string>("");
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(10);

    const [deleteModalOpen, setDeleteModalOpen] = useState<boolean>(false);
    const [brandToDelete, setBrandToDelete] = useState<Brand | null>(null);
    const [actionLoading, setActionLoading] = useState<boolean>(false);

    const fetchBrands = async (page = currentPage) => {
        setLoading(true);
        try {
            const res = await BrandServices.filtrar(
                search.trim() !== "" ? search.trim() : null,
                isActiveFilter !== "" ? isActiveFilter === "true" : null,
                perPage,
                page
            );
            if (res?.data?.data && Array.isArray(res.data.data)) {
                setBrands(res.data.data);
                if (res.data.pagination) {
                    setCurrentPage(res.data.pagination.current_page);
                    setTotalPages(res.data.pagination.last_page);
                    setTotalItems(res.data.pagination.total);
                }
            }
        } catch (err) {
            console.error("Error al cargar marcas", err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchBrands(1);
    }, [isActiveFilter, perPage]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchBrands(1);
    };

    const confirmDelete = async () => {
        if (!brandToDelete) return;
        setActionLoading(true);
        try {
            const res = await BrandServices.delete(brandToDelete.id);
            if (res?.data?.code === 200) {
                setDeleteModalOpen(false);
                setBrandToDelete(null);
                fetchBrands(currentPage);
            }
        } catch (err) {
            console.error("Error eliminando marca", err);
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
                        Marcas
                    </BreadcrumbItem>
                </Breadcrumb>

                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiBookmark className="w-8 h-8 text-purple-600 dark:text-purple-400" />
                            Marcas y Fabricantes
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Gestiona las marcas comerciales de los productos en tu catálogo
                        </p>
                    </div>
                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <Button color="gray" onClick={() => fetchBrands(currentPage)} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-2 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                    </div>
                </div>

                <Card className="mb-6 shadow-sm border-gray-100 dark:border-gray-700">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <div className="sm:col-span-2">
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                Buscar por Nombre o Slug
                            </label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="Ej: Sony, Apple, Nike..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                Estado
                            </label>
                            <Select value={isActiveFilter} onChange={(e) => setIsActiveFilter(e.target.value)}>
                                <option value="">Todos los Estados</option>
                                <option value="true">Activas</option>
                                <option value="false">Inactivas</option>
                            </Select>
                        </div>
                    </form>
                </Card>

                <Card className="shadow-sm border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div className="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                        <Table hoverable>
                            <TableHead className="bg-gray-50 dark:bg-gray-700 text-xs uppercase tracking-wider">
                                <TableHeadCell>Marca</TableHeadCell>
                                <TableHeadCell>Slug</TableHeadCell>
                                <TableHeadCell>Posición</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-100 dark:divide-gray-700">
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-12">
                                            <Spinner size="xl" />
                                        </TableCell>
                                    </TableRow>
                                ) : brands.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-12">
                                            <LuTag className="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                            <p className="text-base font-semibold text-gray-700 dark:text-gray-300">
                                                No se encontraron marcas
                                            </p>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    brands.map((b) => (
                                        <TableRow key={b.id} className="bg-white dark:bg-gray-800">
                                            <TableCell className="font-semibold text-gray-900 dark:text-white">
                                                <div className="flex items-center gap-3">
                                                    {b.logo ? (
                                                        <img src={b.logo} alt={b.name} className="w-9 h-9 object-contain rounded-lg border p-0.5" />
                                                    ) : (
                                                        <div className="w-9 h-9 rounded-lg bg-purple-50 dark:bg-gray-700 flex items-center justify-center text-purple-500">
                                                            <LuTag className="w-5 h-5" />
                                                        </div>
                                                    )}
                                                    <span>{b.name}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-mono text-xs text-gray-500">
                                                /{b.slug}
                                            </TableCell>
                                            <TableCell>
                                                <Badge color="gray" size="sm">{b.position}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge color={b.is_active ? "success" : "failure"} size="sm">
                                                    {b.is_active ? "Activa" : "Inactiva"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <button
                                                    onClick={() => {
                                                        setBrandToDelete(b);
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
                                    fetchBrands(p);
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
                                ¿Eliminar Marca?
                            </h3>
                            <p className="mb-5 text-sm text-gray-500">
                                ¿Deseas eliminar la marca <strong>"{brandToDelete?.name}"</strong>?
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

export default BrandIndexPage;
