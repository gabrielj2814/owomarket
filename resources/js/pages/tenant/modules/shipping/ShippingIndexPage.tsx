import Dashboard from "@/components/layouts/Dashboard";
import ShippingServices from "@/Services/ShippingServices";
import { ShippingZone } from "@/types/models/ShippingZone";
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
    HiTrash,
    HiTruck,
} from "react-icons/hi";

interface ShippingIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const ShippingIndexPage: FC<ShippingIndexPageProps> = ({ user_id, title, host, user_name }) => {
    const [zones, setZones] = useState<ShippingZone[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [search, setSearch] = useState<string>("");
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(10);

    const [deleteModalOpen, setDeleteModalOpen] = useState<boolean>(false);
    const [zoneToDelete, setZoneToDelete] = useState<ShippingZone | null>(null);
    const [actionLoading, setActionLoading] = useState<boolean>(false);

    const fetchZones = async (page = currentPage) => {
        setLoading(true);
        try {
            const res = await ShippingServices.filtrarZonas(
                search.trim() !== "" ? search.trim() : null,
                null,
                perPage,
                page
            );
            if (res?.data?.data && Array.isArray(res.data.data)) {
                setZones(res.data.data);
                if (res.data.pagination) {
                    setCurrentPage(res.data.pagination.current_page);
                    setTotalPages(res.data.pagination.last_page);
                    setTotalItems(res.data.pagination.total);
                }
            }
        } catch (err) {
            console.error("Error al cargar zonas de envío", err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchZones(1);
    }, [perPage]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchZones(1);
    };

    const confirmDelete = async () => {
        if (!zoneToDelete) return;
        setActionLoading(true);
        try {
            const res = await ShippingServices.eliminarZona(zoneToDelete.id);
            if (res?.data?.code === 200) {
                setDeleteModalOpen(false);
                setZoneToDelete(null);
                fetchZones(currentPage);
            }
        } catch (err) {
            console.error("Error eliminando zona de envío", err);
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
                    <BreadcrumbItem>
                        Configuración
                    </BreadcrumbItem>
                    <BreadcrumbItem>
                        Envíos
                    </BreadcrumbItem>
                </Breadcrumb>

                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiTruck className="w-8 h-8 text-cyan-600 dark:text-cyan-400" />
                            Zonas y Tarifas de Envío
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Configura regiones de despacho, costos fijos, envíos gratis o tarifas por peso
                        </p>
                    </div>
                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <Button color="gray" onClick={() => fetchZones(currentPage)} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-2 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                    </div>
                </div>

                <Card className="mb-6 shadow-sm border-gray-100 dark:border-gray-700">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
                                Buscar por Nombre de Zona
                            </label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="Ej: Nacional, Local, Internacional..."
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
                                <TableHeadCell>Zona de Envío</TableHeadCell>
                                <TableHeadCell>Países</TableHeadCell>
                                <TableHeadCell>Tarifas Configuradas</TableHeadCell>
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
                                ) : zones.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-12">
                                            <HiTruck className="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                            <p className="text-base font-semibold text-gray-700 dark:text-gray-300">
                                                No se encontraron zonas de envío
                                            </p>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    zones.map((z) => (
                                        <TableRow key={z.id} className="bg-white dark:bg-gray-800">
                                            <TableCell className="font-semibold text-gray-900 dark:text-white">
                                                {z.name}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {z.countries && z.countries.length > 0 ? (
                                                        z.countries.map((c, i) => (
                                                            <Badge key={i} color="gray" size="xs">
                                                                {c}
                                                            </Badge>
                                                        ))
                                                    ) : (
                                                        <span className="text-xs text-gray-400">Todos los países</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1.5">
                                                    {z.rates && z.rates.length > 0 ? (
                                                        z.rates.map((r) => (
                                                            <Badge key={r.id} color="info" size="xs">
                                                                {r.name}: ${r.cost.toFixed(2)}
                                                            </Badge>
                                                        ))
                                                    ) : (
                                                        <span className="text-xs text-gray-400">Sin tarifas</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge color={z.is_active ? "success" : "failure"} size="sm">
                                                    {z.is_active ? "Activa" : "Inactiva"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <button
                                                    onClick={() => {
                                                        setZoneToDelete(z);
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
                                    fetchZones(p);
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
                                ¿Eliminar Zona de Envío?
                            </h3>
                            <p className="mb-5 text-sm text-gray-500">
                                ¿Deseas eliminar la zona <strong>"{zoneToDelete?.name}"</strong> y sus tarifas?
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

export default ShippingIndexPage;
