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
    HiCheckCircle,
    HiEye,
    HiEyeOff,
    HiFilter,
    HiHome,
    HiPencilAlt,
    HiRefresh,
    HiSearch,
    HiShoppingBag,
    HiStar,
    HiTag,
    HiXCircle,
} from "react-icons/hi";
import { LuPackageCheck, LuShieldCheck, LuSparkles } from "react-icons/lu";

interface CentralProduct {
    id: string;
    tenant_id: string;
    tenant_product_id: string;
    name: string;
    slug: string;
    description?: string;
    sku?: string;
    barcode?: string;
    price: number;
    compare_price?: number;
    cost_price?: number;
    quantity: number;
    is_visible: boolean;
    is_featured: boolean;
    category_name?: string;
    brand_name?: string;
    images?: string[];
    variants?: any[];
    metadata?: {
        custom_commission_rate?: number;
        moderation_history?: Array<{
            moderated_by: string;
            is_visible: boolean;
            is_featured: boolean;
            notes: string;
            timestamp: string;
        }>;
    };
    tenant?: {
        id: string;
        name: string;
    };
    created_at?: string;
}

interface Metrics {
    total_products: number;
    approved_products: number;
    pending_products: number;
    featured_products: number;
}

interface PaginationData {
    data: CentralProduct[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface AdminProductsModerationPageProps {
    title?: string;
    user_id: string;
    products_data: PaginationData;
    metrics: Metrics;
    tenants_list: Array<{ id: string; name: string }>;
    filters: {
        tenant_id: string;
        is_visible: string;
        is_featured: string;
        search: string;
    };
}

const AdminProductsModerationPage: FC<AdminProductsModerationPageProps> = ({
    title = "Moderación de Productos del Marketplace - OwOMarket",
    user_id,
    products_data: initialPagination,
    metrics: initialMetrics,
    tenants_list: initialTenants,
    filters: initialFilters,
}) => {
    const [products, setProducts] = useState<CentralProduct[]>(initialPagination.data || []);
    const [pagination, setPagination] = useState({
        current_page: initialPagination.current_page || 1,
        last_page: initialPagination.last_page || 1,
        total: initialPagination.total || 0,
        per_page: initialPagination.per_page || 15,
    });
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);

    const [search, setSearch] = useState(initialFilters.search || "");
    const [tenantFilter, setTenantFilter] = useState(initialFilters.tenant_id || "");
    const [visibleFilter, setVisibleFilter] = useState(initialFilters.is_visible || "");
    const [featuredFilter, setFeaturedFilter] = useState(initialFilters.is_featured || "");
    const [loading, setLoading] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal Moderación
    const [moderateModalOpen, setModerateModalOpen] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState<CentralProduct | null>(null);
    const [modData, setModData] = useState({
        is_visible: true,
        is_featured: false,
        commission_rate: "",
        notes: "",
    });
    const [submitting, setSubmitting] = useState(false);

    const fetchProducts = async (page = 1) => {
        setLoading(true);
        try {
            const params: any = { page };
            if (search.trim()) params.search = search.trim();
            if (tenantFilter) params.tenant_id = tenantFilter;
            if (visibleFilter !== "") params.is_visible = visibleFilter;
            if (featuredFilter !== "") params.is_featured = featuredFilter;

            const response = await axios.get("/admin/api/catalog/moderation-products", { params });
            if (response.data?.status === "success") {
                const resData = response.data.data;
                setProducts(resData.products.data);
                setPagination({
                    current_page: resData.products.current_page,
                    last_page: resData.products.last_page,
                    total: resData.products.total,
                    per_page: resData.products.per_page,
                });
                setMetrics(resData.metrics);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar productos para moderación." });
        } finally {
            setLoading(false);
        }
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchProducts(1);
    };

    const handleOpenModerate = (prod: CentralProduct) => {
        setSelectedProduct(prod);
        setModData({
            is_visible: prod.is_visible,
            is_featured: prod.is_featured,
            commission_rate: prod.metadata?.custom_commission_rate ? String(prod.metadata.custom_commission_rate) : "",
            notes: "",
        });
        setModerateModalOpen(true);
    };

    const handleModerateSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedProduct) return;

        setSubmitting(true);
        try {
            const payload: any = {
                is_visible: modData.is_visible,
                is_featured: modData.is_featured,
                moderation_notes: modData.notes.trim() || undefined,
            };
            if (modData.commission_rate !== "") {
                payload.commission_rate = Number(modData.commission_rate);
            }

            const response = await axios.post(`/admin/api/catalog/moderation-products/${selectedProduct.id}/moderate`, payload);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Producto "${selectedProduct.name}" moderado exitosamente.`,
                });
                setModerateModalOpen(false);
                fetchProducts(pagination.current_page);
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al moderar producto.",
            });
        } finally {
            setSubmitting(false);
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
                            <BreadcrumbItem>Marketplace Central</BreadcrumbItem>
                            <BreadcrumbItem>Moderación de Catálogo</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <LuShieldCheck className="text-emerald-600 w-8 h-8" />
                            Moderación y Calidad de Productos
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Auditoría de visibilidad, precios, fotos y productos destacados en el Marketplace Central.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button color="light" size="sm" onClick={() => fetchProducts(pagination.current_page)} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-1.5 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
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
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-l-4 border-blue-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Total Productos
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_products || 0}
                                </h3>
                                <p className="text-xs text-blue-600 font-medium mt-1">
                                    Sincronizados por tiendas
                                </p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-xl">
                                <HiShoppingBag className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Aprobados / Visibles
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.approved_products || 0}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    En marketplace público
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
                                    Pendientes / Ocultos
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.pending_products || 0}
                                </h3>
                                <p className="text-xs text-amber-600 font-medium mt-1">
                                    Ocultos para compradores
                                </p>
                            </div>
                            <div className="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 rounded-xl">
                                <HiEyeOff className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-purple-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Destacados Home
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.featured_products || 0}
                                </h3>
                                <p className="text-xs text-purple-600 font-medium mt-1">
                                    En carrusel principal
                                </p>
                            </div>
                            <div className="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 rounded-xl">
                                <HiStar className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* FILTROS Y TABLA */}
                <Card className="shadow-sm">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                        <div className="lg:col-span-2">
                            <TextInput
                                icon={HiSearch}
                                placeholder="Buscar por título, SKU, marca..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <div>
                            <Select
                                value={tenantFilter}
                                onChange={(e) => setTenantFilter(e.target.value)}
                            >
                                <option value="">Todas las Tiendas</option>
                                {initialTenants.map((t) => (
                                    <option key={t.id} value={t.id}>
                                        {t.name}
                                    </option>
                                ))}
                            </Select>
                        </div>
                        <div>
                            <Select
                                value={visibleFilter}
                                onChange={(e) => setVisibleFilter(e.target.value)}
                            >
                                <option value="">Estado Visibilidad</option>
                                <option value="1">Solo Visibles</option>
                                <option value="0">Solo Ocultos</option>
                            </Select>
                        </div>
                        <div>
                            <Button type="submit" color="blue" disabled={loading} className="w-full">
                                <HiFilter className="w-4 h-4 mr-1.5" />
                                Filtrar
                            </Button>
                        </div>
                    </form>

                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                <TableHeadCell>Foto</TableHeadCell>
                                <TableHeadCell>Producto / SKU</TableHeadCell>
                                <TableHeadCell>Tienda Inquilina</TableHeadCell>
                                <TableHeadCell>Categoría & Marca</TableHeadCell>
                                <TableHeadCell>Precio USD</TableHeadCell>
                                <TableHeadCell>Stock</TableHeadCell>
                                <TableHeadCell>Visibilidad</TableHeadCell>
                                <TableHeadCell className="text-right">Acción</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y text-xs">
                                {products.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-gray-400">
                                            No se encontraron productos para los filtros seleccionados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    products.map((p) => {
                                        const mainImg = p.images && p.images.length > 0 ? p.images[0] : null;
                                        return (
                                            <TableRow key={p.id}>
                                                <TableCell>
                                                    {mainImg ? (
                                                        <img src={mainImg} alt={p.name} className="w-10 h-10 object-cover rounded border bg-white" />
                                                    ) : (
                                                        <div className="w-10 h-10 rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400">
                                                            <HiShoppingBag className="w-5 h-5" />
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="space-y-0.5">
                                                        <p className="font-bold text-gray-900 dark:text-white max-w-xs truncate" title={p.name}>
                                                            {p.name}
                                                        </p>
                                                        <p className="text-[11px] font-mono text-gray-400">
                                                            SKU: {p.sku || "N/A"}
                                                        </p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-semibold text-blue-600">
                                                        {p.tenant?.name || p.tenant_id}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-gray-500">
                                                    <p>{p.category_name || "Sin categoría"}</p>
                                                    <p className="text-[10px] text-gray-400">{p.brand_name || "Sin marca"}</p>
                                                </TableCell>
                                                <TableCell className="font-bold text-emerald-600 text-sm">
                                                    ${parseFloat(String(p.price || 0)).toFixed(2)}
                                                </TableCell>
                                                <TableCell className="font-mono">
                                                    <Badge color={p.quantity > 0 ? "indigo" : "failure"} className="w-fit">
                                                        {p.quantity} uds
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex flex-col gap-1">
                                                        <Badge color={p.is_visible ? "success" : "warning"} className="w-fit text-[10px]">
                                                            {p.is_visible ? "Visible en Home" : "Oculto"}
                                                        </Badge>
                                                        {p.is_featured && (
                                                            <Badge color="purple" className="w-fit text-[10px]">
                                                                Destacado ⭐
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button size="xs" color="blue" onClick={() => handleOpenModerate(p)}>
                                                        <HiPencilAlt className="w-3.5 h-3.5 mr-1" />
                                                        Moderar
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {pagination.last_page > 1 && (
                        <div className="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Pagination
                                currentPage={pagination.current_page}
                                totalPages={pagination.last_page}
                                onPageChange={(page) => fetchProducts(page)}
                                showIcons
                                previousLabel="Anterior"
                                nextLabel="Siguiente"
                            />
                        </div>
                    )}
                </Card>

                {/* MODAL MODERACION DE PRODUCTO */}
                <Modal show={moderateModalOpen} onClose={() => setModerateModalOpen(false)} size="lg">
                    <ModalHeader>
                        Moderar Producto: {selectedProduct?.name}
                    </ModalHeader>
                    <form onSubmit={handleModerateSubmit}>
                        <ModalBody className="space-y-4">
                            <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-center justify-between text-xs">
                                <div>
                                    <p className="text-gray-500">Tienda:</p>
                                    <p className="font-bold text-gray-900 dark:text-white">
                                        {selectedProduct?.tenant?.name || selectedProduct?.tenant_id}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-gray-500">Precio Público:</p>
                                    <p className="font-bold text-emerald-600 text-sm">
                                        ${parseFloat(String(selectedProduct?.price || 0)).toFixed(2)} USD
                                    </p>
                                </div>
                                <div>
                                    <p className="text-gray-500">Stock:</p>
                                    <p className="font-bold text-gray-900 dark:text-white">{selectedProduct?.quantity} unidades</p>
                                </div>
                            </div>

                            <div className="space-y-3 pt-2">
                                <div className="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div>
                                        <p className="text-xs font-bold text-gray-900 dark:text-white">
                                            Aprobar Visibilidad en Marketplace Central
                                        </p>
                                        <p className="text-[11px] text-gray-500">
                                            Si se desactiva, el producto permanecerá en la tienda pero no aparecerá en OwOMarket.
                                        </p>
                                    </div>
                                    <ToggleSwitch
                                        checked={modData.is_visible}
                                        onChange={(checked) => setModData({ ...modData, is_visible: checked })}
                                    />
                                </div>

                                <div className="flex items-center justify-between p-3 border border-purple-200 dark:border-purple-800 bg-purple-50/40 dark:bg-purple-900/10 rounded-lg">
                                    <div>
                                        <p className="text-xs font-bold text-purple-900 dark:text-purple-200">
                                            Destacar en Carrusel / Home Central
                                        </p>
                                        <p className="text-[11px] text-purple-700 dark:text-purple-300">
                                            Mostrar en sección de ofertas y productos recomendados de la página principal.
                                        </p>
                                    </div>
                                    <ToggleSwitch
                                        checked={modData.is_featured}
                                        onChange={(checked) => setModData({ ...modData, is_featured: checked })}
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Comisión Personalizada para este Producto (% Opcional)
                                </label>
                                <TextInput
                                    type="number"
                                    step="0.01"
                                    placeholder="Ej: 8.5 (Sobrescribe la comisión del plan)"
                                    value={modData.commission_rate}
                                    onChange={(e) => setModData({ ...modData, commission_rate: e.target.value })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Notas de Moderación / Motivo de Decisión
                                </label>
                                <textarea
                                    className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    rows={2}
                                    placeholder="Ej: Aprobado para campaña de Navidad; Fotos con buena resolución."
                                    value={modData.notes}
                                    onChange={(e) => setModData({ ...modData, notes: e.target.value })}
                                />
                            </div>

                            {selectedProduct?.metadata?.moderation_history && selectedProduct.metadata.moderation_history.length > 0 && (
                                <div className="pt-2">
                                    <h4 className="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                                        Historial de Moderación
                                    </h4>
                                    <div className="space-y-1.5 max-h-36 overflow-y-auto">
                                        {selectedProduct.metadata.moderation_history.map((h, i) => (
                                            <div key={i} className="p-2 bg-gray-50 dark:bg-gray-800 rounded text-[11px] flex justify-between">
                                                <span>{h.notes}</span>
                                                <span className="text-gray-400 text-[10px]">
                                                    {new Date(h.timestamp).toLocaleDateString("es-VE")}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setModerateModalOpen(false)} disabled={submitting}>
                                Cancelar
                            </Button>
                            <Button color="blue" type="submit" disabled={submitting}>
                                {submitting ? <Spinner size="sm" className="mr-2" /> : <HiCheckCircle className="w-4 h-4 mr-2" />}
                                Guardar Moderación
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default AdminProductsModerationPage;
