import Dashboard from "@/components/layouts/Dashboard";
import AttributeServices from "@/Services/AttributeServices";
import { ProductAttribute } from "@/types/models/ProductAttribute";
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
    HiAdjustments,
    HiHome,
    HiRefresh,
    HiSearch,
    HiTrash,
} from "react-icons/hi";
import { LuLayers } from "react-icons/lu";

interface AttributeIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const AttributeIndexPage: FC<AttributeIndexPageProps> = ({ user_id, title, host, user_name }) => {
    const [attributes, setAttributes] = useState<ProductAttribute[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [search, setSearch] = useState<string>("");
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(10);

    const [deleteModalOpen, setDeleteModalOpen] = useState<boolean>(false);
    const [attributeToDelete, setAttributeToDelete] = useState<ProductAttribute | null>(null);
    const [actionLoading, setActionLoading] = useState<boolean>(false);

    const fetchAttributes = async (page = currentPage) => {
        setLoading(true);
        try {
            const res = await AttributeServices.filtrar(
                search.trim() !== "" ? search.trim() : null,
                null,
                null,
                null,
                perPage,
                page
            );
            const items = Array.isArray(res?.data) ? res.data : (Array.isArray(res?.data?.data) ? res.data.data : []);
            setAttributes(items);

            const pagination = (res as any)?.pagination || (res as any)?.data?.pagination;
            if (pagination) {
                setCurrentPage(pagination.current_page);
                setTotalPages(pagination.last_page);
                setTotalItems(pagination.total);
            }
        } catch (err) {
            console.error("Error al cargar atributos", err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchAttributes(1);
    }, [perPage]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchAttributes(1);
    };

    const confirmDelete = async () => {
        if (!attributeToDelete) return;
        setActionLoading(true);
        try {
            const res = await AttributeServices.delete(attributeToDelete.id);
            if (res?.data?.code === 200) {
                setDeleteModalOpen(false);
                setAttributeToDelete(null);
                fetchAttributes(currentPage);
            }
        } catch (err) {
            console.error("Error eliminando atributo", err);
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
                        Atributos
                    </BreadcrumbItem>
                </Breadcrumb>

                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiAdjustments className="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
                            Atributos y Variantes
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Define características como Color, Talla, Material o Capacidad
                        </p>
                    </div>
                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <Button color="gray" onClick={() => fetchAttributes(currentPage)} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-2 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                    </div>
                </div>

                <Card className="mb-6 shadow-sm border-gray-100 dark:border-gray-700">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                Buscar por Nombre o Slug
                            </label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="Ej: Talla, Color, Memoria..."
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
                                <TableHeadCell>Atributo</TableHeadCell>
                                <TableHeadCell>Slug</TableHeadCell>
                                <TableHeadCell>Tipo</TableHeadCell>
                                <TableHeadCell>Valores Configurados</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-100 dark:divide-gray-700">
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-12">
                                            <Spinner size="xl" />
                                        </TableCell>
                                    </TableRow>
                                ) : attributes.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-12">
                                            <LuLayers className="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                            <p className="text-base font-semibold text-gray-700 dark:text-gray-300">
                                                No se encontraron atributos
                                            </p>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    attributes.map((a) => (
                                        <TableRow key={a.id} className="bg-white dark:bg-gray-800">
                                            <TableCell className="font-semibold text-gray-900 dark:text-white">
                                                {a.name}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs text-gray-500">
                                                /{a.slug}
                                            </TableCell>
                                            <TableCell>
                                                <Badge color="info" size="sm">
                                                    {a.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {a.values && a.values.length > 0 ? (
                                                        a.values.map((val) => (
                                                            <Badge key={val.id} color="gray" size="xs">
                                                                {val.value}
                                                            </Badge>
                                                        ))
                                                    ) : (
                                                        <span className="text-xs text-gray-400">Sin valores</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <button
                                                    onClick={() => {
                                                        setAttributeToDelete(a);
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
                                    fetchAttributes(p);
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
                                ¿Eliminar Atributo?
                            </h3>
                            <p className="mb-5 text-sm text-gray-500">
                                ¿Deseas eliminar el atributo <strong>"{attributeToDelete?.name}"</strong> y todos sus valores?
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

export default AttributeIndexPage;
