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
    HiFolder,
    HiHome,
    HiPencilAlt,
    HiPlus,
    HiRefresh,
    HiTrash,
    HiViewGrid,
} from "react-icons/hi";
import { LuFolderTree, LuLayers } from "react-icons/lu";

interface MasterCategory {
    id: string;
    name: string;
    slug: string;
    parent_id?: string | null;
    description?: string | null;
    icon?: string | null;
    image?: string | null;
    position: number;
    is_active: boolean;
    children?: MasterCategory[];
}

interface Metrics {
    total_categories: number;
    active_categories: number;
    root_categories: number;
}

interface AdminMasterCategoriesPageProps {
    title?: string;
    user_id: string;
    categories_tree: MasterCategory[];
    categories_flat: MasterCategory[];
    metrics: Metrics;
}

const AdminMasterCategoriesPage: FC<AdminMasterCategoriesPageProps> = ({
    title = "Catálogo Maestro de Categorías - OwOMarket",
    user_id,
    categories_tree: initialTree,
    categories_flat: initialFlat,
    metrics: initialMetrics,
}) => {
    const [tree, setTree] = useState<MasterCategory[]>(initialTree || []);
    const [flatCategories, setFlatCategories] = useState<MasterCategory[]>(initialFlat || []);
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);

    const [loading, setLoading] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal Form
    const [formModalOpen, setFormModalOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState<MasterCategory | null>(null);
    const [formData, setFormData] = useState({
        name: "",
        slug: "",
        parent_id: "",
        description: "",
        icon: "",
        image: "",
        position: 0,
        is_active: true,
    });
    const [submitting, setSubmitting] = useState(false);

    // Modal Eliminar
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [categoryToDelete, setCategoryToDelete] = useState<MasterCategory | null>(null);
    const [deleting, setDeleting] = useState(false);

    const fetchCategories = async () => {
        setLoading(true);
        try {
            const response = await axios.get("/admin/api/catalog/master-categories");
            if (response.data?.status === "success") {
                const resData = response.data.data;
                setTree(resData.tree);
                setFlatCategories(resData.categories);
                setMetrics(resData.metrics);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar categorías maestras." });
        } finally {
            setLoading(false);
        }
    };

    const handleOpenCreate = (parentId: string = "") => {
        setEditingCategory(null);
        setFormData({
            name: "",
            slug: "",
            parent_id: parentId,
            description: "",
            icon: "",
            image: "",
            position: 0,
            is_active: true,
        });
        setFormModalOpen(true);
    };

    const handleOpenEdit = (category: MasterCategory) => {
        setEditingCategory(category);
        setFormData({
            name: category.name,
            slug: category.slug,
            parent_id: category.parent_id || "",
            description: category.description || "",
            icon: category.icon || "",
            image: category.image || "",
            position: category.position || 0,
            is_active: category.is_active,
        });
        setFormModalOpen(true);
    };

    const handleFormSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);

        const payload = {
            id: editingCategory?.id,
            name: formData.name.trim(),
            slug: formData.slug.trim() || undefined,
            parent_id: formData.parent_id || null,
            description: formData.description.trim() || null,
            icon: formData.icon.trim() || null,
            image: formData.image.trim() || null,
            position: Number(formData.position),
            is_active: formData.is_active,
        };

        try {
            const response = await axios.post("/admin/api/catalog/master-categories", payload);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Categoría "${payload.name}" guardada exitosamente.`,
                });
                setFormModalOpen(false);
                fetchCategories();
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al guardar categoría maestra.",
            });
        } finally {
            setSubmitting(false);
        }
    };

    const handleDeleteSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!categoryToDelete) return;

        setDeleting(true);
        try {
            const response = await axios.delete(`/admin/api/catalog/master-categories/${categoryToDelete.id}`);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Categoría "${categoryToDelete.name}" eliminada.`,
                });
                setDeleteModalOpen(false);
                fetchCategories();
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al eliminar categoría maestra.",
            });
        } finally {
            setDeleting(false);
        }
    };

    // Render jerárquico recursivo
    const renderCategoryRows = (cats: MasterCategory[], depth = 0): React.ReactNode => {
        return cats.map((c) => (
            <React.Fragment key={c.id}>
                <TableRow className={depth > 0 ? "bg-gray-50/50 dark:bg-gray-800/30" : ""}>
                    <TableCell className="font-medium text-gray-900 dark:text-white">
                        <div className="flex items-center gap-2" style={{ paddingLeft: `${depth * 24}px` }}>
                            {depth > 0 && <span className="text-gray-400 font-mono">└─</span>}
                            {c.image ? (
                                <img src={c.image} alt={c.name} className="w-7 h-7 object-cover rounded border bg-white" />
                            ) : (
                                <div className="w-7 h-7 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 flex items-center justify-center text-xs font-bold">
                                    <HiFolder className="w-4 h-4" />
                                </div>
                            )}
                            <span className="font-bold">{c.name}</span>
                            {c.children && c.children.length > 0 && (
                                <Badge color="indigo" className="text-[10px]">
                                    {c.children.length} sub
                                </Badge>
                            )}
                        </div>
                    </TableCell>
                    <TableCell className="font-mono text-gray-500">{c.slug}</TableCell>
                    <TableCell className="font-mono font-bold">#{c.position}</TableCell>
                    <TableCell>
                        <Badge color={c.is_active ? "success" : "failure"} className="w-fit">
                            {c.is_active ? "Activa" : "Inactiva"}
                        </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-2">
                            <Button size="xs" color="light" title="Añadir Subcategoría" onClick={() => handleOpenCreate(c.id)}>
                                <HiPlus className="w-3.5 h-3.5 text-green-600" />
                            </Button>
                            <Button size="xs" color="light" onClick={() => handleOpenEdit(c)}>
                                <HiPencilAlt className="w-3.5 h-3.5 text-blue-600" />
                            </Button>
                            <Button size="xs" color="failure" onClick={() => {
                                setCategoryToDelete(c);
                                setDeleteModalOpen(true);
                            }}>
                                <HiTrash className="w-3.5 h-3.5" />
                            </Button>
                        </div>
                    </TableCell>
                </TableRow>
                {c.children && c.children.length > 0 && renderCategoryRows(c.children, depth + 1)}
            </React.Fragment>
        ));
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
                            <BreadcrumbItem>Categorías</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <LuFolderTree className="text-blue-600 w-8 h-8" />
                            Árbol Maestro de Categorías
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Estructura taxonómica jerárquica para clasificación global de productos en el Marketplace.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button color="light" size="sm" onClick={fetchCategories} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-1.5 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                        <Button color="blue" size="sm" onClick={() => handleOpenCreate()}>
                            <HiPlus className="w-4 h-4 mr-1.5" />
                            Nueva Categoría Raíz
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
                    <Card className="border-l-4 border-blue-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Total Categorías
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_categories || 0}
                                </h3>
                                <p className="text-xs text-blue-600 font-medium mt-1">
                                    En todo el ecosistema
                                </p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-xl">
                                <HiViewGrid className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Categorías Activas
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.active_categories || 0}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    Disponibles en filtros de búsqueda
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
                                    Categorías Raíz (Principales)
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.root_categories || 0}
                                </h3>
                                <p className="text-xs text-indigo-600 font-medium mt-1">
                                    Primer nivel de navegación
                                </p>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-xl">
                                <LuLayers className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* TABLA ÁRBOL */}
                <Card className="shadow-sm">
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                <TableHeadCell>Categoría / Jerarquía</TableHeadCell>
                                <TableHeadCell>Slug</TableHeadCell>
                                <TableHeadCell>Posición</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y text-xs">
                                {tree.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-8 text-gray-400">
                                            No hay categorías creadas en el catálogo maestro.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    renderCategoryRows(tree)
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </Card>

                {/* MODAL CREAR / EDITAR CATEGORIA */}
                <Modal show={formModalOpen} onClose={() => setFormModalOpen(false)} size="md">
                    <ModalHeader>
                        {editingCategory ? `Editar: ${editingCategory.name}` : "Nueva Categoría Maestra"}
                    </ModalHeader>
                    <form onSubmit={handleFormSubmit}>
                        <ModalBody className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nombre de la Categoría <span className="text-red-500">*</span>
                                </label>
                                <TextInput
                                    required
                                    placeholder="Ej: Electrónica, Ropa, Alimentos..."
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Categoría Padre (Para crear subcategoría)
                                </label>
                                <Select
                                    value={formData.parent_id}
                                    onChange={(e) => setFormData({ ...formData, parent_id: e.target.value })}
                                >
                                    <option value="">-- Es Categoría Raíz (Sin Padre) --</option>
                                    {flatCategories
                                        .filter((fc) => fc.id !== editingCategory?.id)
                                        .map((fc) => (
                                            <option key={fc.id} value={fc.id}>
                                                {fc.name}
                                            </option>
                                        ))}
                                </Select>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Slug URL (Opcional)
                                </label>
                                <TextInput
                                    placeholder="electronica, ropa..."
                                    value={formData.slug}
                                    onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Icono (Nombre o URL)
                                    </label>
                                    <TextInput
                                        placeholder="HiChip, LuShirt..."
                                        value={formData.icon}
                                        onChange={(e) => setFormData({ ...formData, icon: e.target.value })}
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
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    URL de la Imagen Banner / Miniatura
                                </label>
                                <TextInput
                                    placeholder="https://ejemplo.com/categoria.jpg"
                                    value={formData.image}
                                    onChange={(e) => setFormData({ ...formData, image: e.target.value })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Descripción
                                </label>
                                <textarea
                                    className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    rows={2}
                                    placeholder="Breve reseña sobre la categoría..."
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                />
                            </div>

                            <div className="flex items-center justify-between pt-2">
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">Categoría Activa</span>
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
                                Guardar Categoría
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                {/* MODAL ELIMINAR CATEGORIA */}
                <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} size="md">
                    <ModalHeader>Eliminar Categoría Maestra</ModalHeader>
                    <form onSubmit={handleDeleteSubmit}>
                        <ModalBody className="space-y-3">
                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                ¿Estás seguro de eliminar la categoría <strong>{categoryToDelete?.name}</strong>?
                            </p>
                            <p className="text-xs text-amber-600">
                                Las subcategorías asociadas pasarán automáticamente a ser categorías raíz.
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

export default AdminMasterCategoriesPage;
