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
    HiHome,
    HiPencilAlt,
    HiPhotograph,
    HiPlus,
    HiRefresh,
    HiTrash,
    HiViewBoards,
} from "react-icons/hi";
import { LuImagePlus, LuMegaphone } from "react-icons/lu";

interface HomeBanner {
    id: string;
    title: string;
    subtitle?: string | null;
    image_url: string;
    link_url?: string | null;
    badge_text?: string | null;
    position_type: "hero_slider" | "top_promo" | "featured_grid" | "footer_banner";
    order_position: number;
    is_active: boolean;
    start_date?: string | null;
    end_date?: string | null;
    created_at?: string;
}

interface Metrics {
    total_banners: number;
    active_banners: number;
    hero_sliders: number;
}

interface AdminHomeBannersPageProps {
    title?: string;
    user_id: string;
    banners: HomeBanner[];
    metrics: Metrics;
}

const AdminHomeBannersPage: FC<AdminHomeBannersPageProps> = ({
    title = "Gestor de Banners y Campañas Home - OwOMarket",
    user_id,
    banners: initialBanners,
    metrics: initialMetrics,
}) => {
    const [banners, setBanners] = useState<HomeBanner[]>(initialBanners || []);
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);

    const [loading, setLoading] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal Form
    const [formModalOpen, setFormModalOpen] = useState(false);
    const [editingBanner, setEditingBanner] = useState<HomeBanner | null>(null);
    const [formData, setFormData] = useState({
        title: "",
        subtitle: "",
        image_url: "",
        link_url: "",
        badge_text: "",
        position_type: "hero_slider",
        order_position: 0,
        is_active: true,
        start_date: "",
        end_date: "",
    });
    const [submitting, setSubmitting] = useState(false);

    // Modal Eliminar
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [bannerToDelete, setBannerToDelete] = useState<HomeBanner | null>(null);
    const [deleting, setDeleting] = useState(false);

    const fetchBanners = async () => {
        setLoading(true);
        try {
            const response = await axios.get("/admin/api/cms/home-banners");
            if (response.data?.status === "success") {
                const resData = response.data.data;
                setBanners(resData.banners);
                setMetrics(resData.metrics);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar banners de la home." });
        } finally {
            setLoading(false);
        }
    };

    const handleOpenCreate = () => {
        setEditingBanner(null);
        setFormData({
            title: "",
            subtitle: "",
            image_url: "",
            link_url: "",
            badge_text: "",
            position_type: "hero_slider",
            order_position: 0,
            is_active: true,
            start_date: "",
            end_date: "",
        });
        setFormModalOpen(true);
    };

    const handleOpenEdit = (banner: HomeBanner) => {
        setEditingBanner(banner);
        setFormData({
            title: banner.title,
            subtitle: banner.subtitle || "",
            image_url: banner.image_url,
            link_url: banner.link_url || "",
            badge_text: banner.badge_text || "",
            position_type: banner.position_type,
            order_position: banner.order_position || 0,
            is_active: banner.is_active,
            start_date: banner.start_date ? banner.start_date.substring(0, 10) : "",
            end_date: banner.end_date ? banner.end_date.substring(0, 10) : "",
        });
        setFormModalOpen(true);
    };

    const handleFormSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);

        const payload = {
            id: editingBanner?.id,
            title: formData.title.trim(),
            subtitle: formData.subtitle.trim() || null,
            image_url: formData.image_url.trim(),
            link_url: formData.link_url.trim() || null,
            badge_text: formData.badge_text.trim() || null,
            position_type: formData.position_type,
            order_position: Number(formData.order_position),
            is_active: formData.is_active,
            start_date: formData.start_date || null,
            end_date: formData.end_date || null,
        };

        try {
            const response = await axios.post("/admin/api/cms/home-banners", payload);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Banner "${payload.title}" guardado exitosamente.`,
                });
                setFormModalOpen(false);
                fetchBanners();
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al guardar banner.",
            });
        } finally {
            setSubmitting(false);
        }
    };

    const handleDeleteSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!bannerToDelete) return;

        setDeleting(true);
        try {
            const response = await axios.delete(`/admin/api/cms/home-banners/${bannerToDelete.id}`);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Banner "${bannerToDelete.title}" eliminado.`,
                });
                setDeleteModalOpen(false);
                fetchBanners();
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al eliminar banner.",
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
                            <BreadcrumbItem>Home CMS</BreadcrumbItem>
                            <BreadcrumbItem>Banners & Campañas</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <LuMegaphone className="text-rose-600 w-8 h-8" />
                            Gestor de Banners y Campañas Centrales
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Administración del slider principal, promociones y destacados de la portada de OwOMarket.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button color="light" size="sm" onClick={fetchBanners} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-1.5 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                        <Button color="blue" size="sm" onClick={handleOpenCreate}>
                            <HiPlus className="w-4 h-4 mr-1.5" />
                            Nuevo Banner
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
                    <Card className="border-l-4 border-rose-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Total Banners
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_banners || 0}
                                </h3>
                                <p className="text-xs text-rose-600 font-medium mt-1">
                                    Campañas configuradas
                                </p>
                            </div>
                            <div className="p-3 bg-rose-50 dark:bg-rose-900/30 text-rose-600 rounded-xl">
                                <HiPhotograph className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Banners Activos
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.active_banners || 0}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    Visibles en portada
                                </p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-xl">
                                <HiCheckCircle className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-indigo-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Hero Sliders
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.hero_sliders || 0}
                                </h3>
                                <p className="text-xs text-indigo-600 font-medium mt-1">
                                    Carrusel superior
                                </p>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-xl">
                                <HiViewBoards className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* TABLA DE BANNERS */}
                <Card className="shadow-sm">
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                <TableHeadCell>Imagen Preview</TableHeadCell>
                                <TableHeadCell>Título / Subtítulo</TableHeadCell>
                                <TableHeadCell>Ubicación</TableHeadCell>
                                <TableHeadCell>Enlace</TableHeadCell>
                                <TableHeadCell>Orden</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y text-xs">
                                {banners.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-8 text-gray-400">
                                            No hay banners configurados para la página principal.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    banners.map((b) => (
                                        <TableRow key={b.id}>
                                            <TableCell>
                                                <img
                                                    src={b.image_url}
                                                    alt={b.title}
                                                    className="w-24 h-12 object-cover rounded border bg-gray-100"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-0.5">
                                                    <div className="flex items-center gap-1.5">
                                                        <span className="font-bold text-gray-900 dark:text-white">{b.title}</span>
                                                        {b.badge_text && (
                                                            <Badge color="pink" className="text-[10px]">
                                                                {b.badge_text}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    {b.subtitle && (
                                                        <p className="text-[11px] text-gray-400 truncate max-w-xs">{b.subtitle}</p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    color={
                                                        b.position_type === "hero_slider" ? "purple" :
                                                        b.position_type === "top_promo" ? "indigo" : "info"
                                                    }
                                                    className="capitalize w-fit"
                                                >
                                                    {b.position_type.replace("_", " ")}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="font-mono text-blue-600 truncate max-w-[150px]">
                                                {b.link_url || "-"}
                                            </TableCell>
                                            <TableCell className="font-mono font-bold">
                                                #{b.order_position}
                                            </TableCell>
                                            <TableCell>
                                                <Badge color={b.is_active ? "success" : "failure"} className="w-fit">
                                                    {b.is_active ? "Activo" : "Inactivo"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button size="xs" color="light" onClick={() => handleOpenEdit(b)}>
                                                        <HiPencilAlt className="w-4 h-4 text-blue-600" />
                                                    </Button>
                                                    <Button size="xs" color="failure" onClick={() => {
                                                        setBannerToDelete(b);
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
                </Card>

                {/* MODAL CREAR / EDITAR BANNER */}
                <Modal show={formModalOpen} onClose={() => setFormModalOpen(false)} size="md">
                    <ModalHeader>
                        {editingBanner ? `Editar Banner: ${editingBanner.title}` : "Nuevo Banner de Home"}
                    </ModalHeader>
                    <form onSubmit={handleFormSubmit}>
                        <ModalBody className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Título Principal <span className="text-red-500">*</span>
                                </label>
                                <TextInput
                                    required
                                    placeholder="Ej: Gran Liquidación de Tecnología"
                                    value={formData.title}
                                    onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Subtítulo / Mensaje Secundario
                                </label>
                                <TextInput
                                    placeholder="Ej: Hasta 40% OFF en smartphones y accesorios"
                                    value={formData.subtitle}
                                    onChange={(e) => setFormData({ ...formData, subtitle: e.target.value })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    URL de la Imagen <span className="text-red-500">*</span>
                                </label>
                                <TextInput
                                    required
                                    placeholder="https://ejemplo.com/banner-portada.jpg"
                                    value={formData.image_url}
                                    onChange={(e) => setFormData({ ...formData, image_url: e.target.value })}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Ubicación en Portada
                                    </label>
                                    <Select
                                        value={formData.position_type}
                                        onChange={(e) => setFormData({ ...formData, position_type: e.target.value as any })}
                                    >
                                        <option value="hero_slider">Hero Slider Principal</option>
                                        <option value="top_promo">Barra Promocional Superior</option>
                                        <option value="featured_grid">Grid de Destacados</option>
                                        <option value="footer_banner">Banner Inferior / Footer</option>
                                    </Select>
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Orden / Posición
                                    </label>
                                    <TextInput
                                        type="number"
                                        value={formData.order_position}
                                        onChange={(e) => setFormData({ ...formData, order_position: Number(e.target.value) })}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Texto de Insignia / Badge
                                    </label>
                                    <TextInput
                                        placeholder="Ej: OFERTA FLASH, NUEVO"
                                        value={formData.badge_text}
                                        onChange={(e) => setFormData({ ...formData, badge_text: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Enlace de Destino (URL)
                                    </label>
                                    <TextInput
                                        placeholder="/catalog?category=electronica"
                                        value={formData.link_url}
                                        onChange={(e) => setFormData({ ...formData, link_url: e.target.value })}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center justify-between pt-2">
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">Banner Activo en Portada</span>
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
                                Guardar Banner
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                {/* MODAL ELIMINAR BANNER */}
                <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} size="md">
                    <ModalHeader>Eliminar Banner de Home</ModalHeader>
                    <form onSubmit={handleDeleteSubmit}>
                        <ModalBody className="space-y-3">
                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                ¿Estás seguro de eliminar el banner <strong>{bannerToDelete?.title}</strong>?
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

export default AdminHomeBannersPage;
