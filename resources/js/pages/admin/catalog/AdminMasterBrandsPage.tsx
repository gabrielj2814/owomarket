import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import axios from "axios";
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
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
    ToggleSwitch,
} from "flowbite-react";
import React, { FC, useState } from "react";
import {
    HiBookmark,
    HiCheckCircle,
    HiHome,
    HiPencilAlt,
    HiPlus,
    HiRefresh,
    HiSearch,
    HiTrash,
} from "react-icons/hi";
import { LuCheck, LuSparkles, LuTag } from "react-icons/lu";

interface MasterBrand {
    id: string;
    name: string;
    slug: string;
    logo?: string | null;
    description?: string | null;
    is_active: boolean;
    position: number;
    created_at?: string;
}

interface Metrics {
    total_brands: number;
    active_brands: number;
    inactive_brands: number;
}

interface PaginationData {
    data: MasterBrand[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface AdminMasterBrandsPageProps {
    title?: string;
    user_id: string;
    brands_data: PaginationData;
    metrics: Metrics;
    filters: {
        search: string;
        is_active: string;
    };
}

const AdminMasterBrandsPage: FC<AdminMasterBrandsPageProps> = ({
    title = "Catálogo Maestro de Marcas - OwOMarket",
    user_id,
    brands_data: initialPagination,
    metrics: initialMetrics,
    filters: initialFilters,
}) => {
    const [brands, setBrands] = useState<MasterBrand[]>(initialPagination.data || []);
    const [pagination, setPagination] = useState({
        current_page: initialPagination.current_page || 1,
        last_page: initialPagination.last_page || 1,
        total: initialPagination.total || 0,
        per_page: initialPagination.per_page || 15,
    });
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);

    const [search, setSearch] = useState(initialFilters.search || "");
    const [statusFilter, setStatusFilter] = useState(initialFilters.is_active || "");
    const [loading, setLoading] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal Form (Crear / Editar)
    const [formModalOpen, setFormModalOpen] = useState(false);
    const [editingBrand, setEditingBrand] = useState<MasterBrand | null>(null);
    const [formData, setFormData] = useState({
        name: "",
        slug: "",
        logo: "",
        description: "",
        position: 0,
        is_active: true,
    });
    const [submitting, setSubmitting] = useState(false);

    // Modal Eliminar
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [brandToDelete, setBrandToDelete] = useState<MasterBrand | null>(null);
    const [deleting, setDeleting] = useState(false);

    const fetchBrands = async (page = 1) => {
        setLoading(true);
        try {
            const params: any = { page };
            if (search.trim()) params.search = search.trim();
            if (statusFilter !== "") params.is_active = statusFilter;

            const response = await axios.get("/admin/api/catalog/master-brands", { params });
            if (response.data?.status === "success") {
                const resData = response.data.data;
                setBrands(resData.brands.data);
                setPagination({
                    current_page: resData.brands.current_page,
                    last_page: resData.brands.last_page,
                    total: resData.brands.total,
                    per_page: resData.brands.per_page,
                });
                setMetrics(resData.metrics);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar marcas maestras." });
        } finally {
            setLoading(false);
        }
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchBrands(1);
    };

    const handleOpenCreate = () => {
        setEditingBrand(null);
        setFormData({
            name: "",
            slug: "",
            logo: "",
            description: "",
            position: 0,
            is_active: true,
        });
        setFormModalOpen(true);
    };

    const handleOpenEdit = (brand: MasterBrand) => {
        setEditingBrand(brand);
        setFormData({
            name: brand.name,
            slug: brand.slug,
            logo: brand.logo || "",
            description: brand.description || "",
            position: brand.position || 0,
            is_active: brand.is_active,
        });
        setFormModalOpen(true);
    };

    const handleFormSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);

        const payload = {
            id: editingBrand?.id,
            name: formData.name.trim(),
            slug: formData.slug.trim() || undefined,
            logo: formData.logo.trim() || null,
            description: formData.description.trim() || null,
            position: Number(formData.position),
            is_active: formData.is_active,
        };

        try {
            const response = await axios.post("/admin/api/catalog/master-brands", payload);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Marca "${payload.name}" guardada exitosamente.`,
                });
                setFormModalOpen(false);
                fetchBrands(pagination.current_page);
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al guardar marca maestra.",
            });
        } finally {
            setSubmitting(false);
        }
    };

    const handleDeleteSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!brandToDelete) return;

        setDeleting(true);
        try {
            const response = await axios.delete(`/admin/api/catalog/master-brands/${brandToDelete.id}`);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Marca "${brandToDelete.name}" eliminada del catálogo maestro.`,
                });
                setDeleteModalOpen(false);
                fetchBrands(pagination.current_page);
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al eliminar marca maestra.",
            });
        } finally {
            setDeleting(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />
            <div className="p-4 sm:p-6 space-y-6 max-w-7xl mx-auto">
                {/* Header & Breadcrumbs */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Breadcrumb className="mb-2">
                            <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Panel Global
                            </BreadcrumbItem>
                            <BreadcrumbItem>Catálogo Maestro</BreadcrumbItem>
                            <BreadcrumbItem>Marcas</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiBookmark className="text-indigo-600 w-8 h-8" />
                            Catálogo Maestro de Marcas
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Taxonomía oficial sincronizada por las tiendas de la red mediante `sync-central`.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button color="light" size="sm" onClick={() => fetchBrands(pagination.current_page)} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-1.5 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                        <Button color="blue" size="sm" onClick={handleOpenCreate}>
                            <HiPlus className="w-4 h-4 mr-1.5" />
                            Nueva Marca
                        </Button>
                    </div>
                </div>

                {/* Toast */}
                {toast && (
                    <div
                        className={`p-4 rounded-lg flex items-center justify-between text-sm ${
                            toast.type === "success"
                                ? "bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300 border border-green-200 dark:border-green-800"
                                : "bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800"
                        }`}
                    >
                        <span>{toast.text}</span>
                        <button onClick={() => setToast(null)} className="font-bold text-lg leading-none ml-4">
                            &times;
                        </button>
                    </div>
                )}

                {/* KPI CARDS */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Card className="border-l-4 border-indigo-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Total Marcas Maestras
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_brands || 0}
                                </h3>
                                <p className="text-xs text-indigo-600 font-medium mt-1">
                                    Disponibles para sincronización
                                </p>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-xl">
                                <HiBookmark className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Marcas Activas
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.active_brands || 0}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    Visibles en catálogos
                                </p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-xl">
                                <HiCheckCircle className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-amber-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Inactivas / Archivadas
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.inactive_brands || 0}
                                </h3>
                                <p className="text-xs text-amber-600 font-medium mt-1">
                                    Ocultas para tiendas
                                </p>
                            </div>
                            <div className="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 rounded-xl">
                                <LuTag className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* FILTROS Y TABLA */}
                <Card className="shadow-sm">
                    <form onSubmit={handleSearchSubmit} className="flex flex-col md:flex-row items-center gap-3 mb-4">
                        <div className="relative flex-1 w-full">
                            <TextInput
                                icon={HiSearch}
                                placeholder="Buscar por nombre o slug..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <div className="w-full md:w-48">
                            <Select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                            >
                                <option value="">Todos los Estados</option>
                                <option value="1">Solo Activas</option>
                                <option value="0">Solo Inactivas</option>
                            </Select>
                        </div>
                        <Button type="submit" color="blue" disabled={loading} className="w-full md:w-auto">
                            Filtrar
                        </Button>
                    </form>

                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                <TableHeadCell>Logo</TableHeadCell>
                                <TableHeadCell>Nombre</TableHeadCell>
                                <TableHeadCell>Slug</TableHeadCell>
                                <TableHeadCell>Posición</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y text-xs">
                                {brands.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center py-8 text-gray-400">
                                            No se encontraron marcas registradas en el catálogo maestro.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    brands.map((b) => (
                                        <TableRow key={b.id}>
                                            <TableCell>
                                                {b.logo ? (
                                                    <img src={b.logo} alt={b.name} className="w-8 h-8 object-contain rounded border p-0.5 bg-white" />
                                                ) : (
                                                    <div className="w-8 h-8 rounded bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-xs">
                                                        {b.name.substring(0, 2).toUpperCase()}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="font-bold text-gray-900 dark:text-white">
                                                {b.name}
                                                {b.description && (
                                                    <p className="text-[11px] text-gray-400 font-normal truncate max-w-xs">{b.description}</p>
                                                )}
                                            </TableCell>
                                            <TableCell className="font-mono text-gray-500">
                                                {b.slug}
                                            </TableCell>
                                            <TableCell className="font-mono font-bold">
                                                #{b.position}
                                            </TableCell>
                                            <TableCell>
                                                <Badge color={b.is_active ? "success" : "failure"} className="w-fit">
                                                    {b.is_active ? "Activa" : "Inactiva"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button size="xs" color="light" onClick={() => handleOpenEdit(b)}>
                                                        <HiPencilAlt className="w-4 h-4 text-blue-600" />
                                                    </Button>
                                                    <Button size="xs" color="failure" onClick={() => {
                                                        setBrandToDelete(b);
                                                        setDeleteModalOpen(true);
                                                    }}>
                                                        <HiTrash className="w-4 h-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {pagination.last_page > 1 && (
                        <div className="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Pagination
                                currentPage={pagination.current_page}
                                totalPages={pagination.last_page}
                                onPageChange={(page) => fetchBrands(page)}
                                showIcons
                                previousLabel="Anterior"
                                nextLabel="Siguiente"
                            />
                        </div>
                    )}
                </Card>

                {/* MODAL CREAR / EDITAR MARCA */}
                <Modal show={formModalOpen} onClose={() => setFormModalOpen(false)} size="md">
                    <ModalHeader>
                        {editingBrand ? `Editar Marca: ${editingBrand.name}` : "Nueva Marca Maestra"}
                    </ModalHeader>
                    <form onSubmit={handleFormSubmit}>
                        <ModalBody className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nombre de la Marca <span className="text-red-500">*</span>
                                </label>
                                <TextInput
                                    required
                                    placeholder="Ej: Samsung, Sony, Nike..."
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Slug URL (Opcional)
                                </label>
                                <TextInput
                                    placeholder="samsung, sony..."
                                    value={formData.slug}
                                    onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    URL del Logo
                                </label>
                                <TextInput
                                    placeholder="https://ejemplo.com/logo.png"
                                    value={formData.logo}
                                    onChange={(e) => setFormData({ ...formData, logo: e.target.value })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Posición / Orden
                                </label>
                                <TextInput
                                    type="number"
                                    value={formData.position}
                                    onChange={(e) => setFormData({ ...formData, position: Number(e.target.value) })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Descripción
                                </label>
                                <textarea
                                    className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    rows={2}
                                    placeholder="Breve reseña sobre la marca..."
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                />
                            </div>

                            <div className="flex items-center justify-between pt-2">
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">Marca Activa para Sincronización</span>
                                <ToggleSwitch
                                    checked={formData.is_active}
                                    onChange={(checked) => setFormData({ ...formData, is_active: checked })}
                                />
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setFormModalOpen(false)} disabled={submitting}>
                                Cancelar
                            </Button>
                            <Button color="blue" type="submit" disabled={submitting}>
                                {submitting ? <Spinner size="sm" className="mr-2" /> : <HiCheckCircle className="w-4 h-4 mr-2" />}
                                Guardar Marca
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                {/* MODAL ELIMINAR MARCA */}
                <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} size="md">
                    <ModalHeader>Eliminar Marca Maestra</ModalHeader>
                    <form onSubmit={handleDeleteSubmit}>
                        <ModalBody className="space-y-3">
                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                ¿Estás seguro de eliminar la marca <strong>{brandToDelete?.name}</strong> del catálogo maestro?
                            </p>
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setDeleteModalOpen(false)} disabled={deleting}>
                                Cancelar
                            </Button>
                            <Button color="failure" type="submit" disabled={deleting}>
                                {deleting ? <Spinner size="sm" className="mr-2" /> : <HiTrash className="w-4 h-4 mr-2" />}
                                Confirmar Eliminación
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default AdminMasterBrandsPage;
