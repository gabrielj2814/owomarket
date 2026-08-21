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
    HiCheck,
    HiCheckCircle,
    HiCreditCard,
    HiCurrencyDollar,
    HiHome,
    HiPencilAlt,
    HiPlus,
    HiRefresh,
    HiTrash,
    HiUsers,
} from "react-icons/hi";
import { LuCoins, LuSparkles } from "react-icons/lu";

interface SubscriptionPlan {
    id: string;
    name: string;
    slug: string;
    description?: string | null;
    price_monthly: number;
    price_yearly: number;
    commission_rate: number;
    max_products: number;
    features?: string[];
    is_active: boolean;
    subscriptions_count?: number;
    created_at?: string;
}

interface Metrics {
    total_plans: number;
    active_plans: number;
    active_subscriptions: number;
}

interface AdminSubscriptionPlansPageProps {
    title?: string;
    user_id: string;
    plans: SubscriptionPlan[];
    metrics: Metrics;
}

const AdminSubscriptionPlansPage: FC<AdminSubscriptionPlansPageProps> = ({
    title = "Planes de Suscripción y Tarifas B2B - OwOMarket",
    user_id,
    plans: initialPlans,
    metrics: initialMetrics,
}) => {
    const [plans, setPlans] = useState<SubscriptionPlan[]>(initialPlans || []);
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);

    const [loading, setLoading] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal Form
    const [formModalOpen, setFormModalOpen] = useState(false);
    const [editingPlan, setEditingPlan] = useState<SubscriptionPlan | null>(null);
    const [formData, setFormData] = useState({
        name: "",
        slug: "",
        description: "",
        price_monthly: 0,
        price_yearly: 0,
        commission_rate: 5,
        max_products: 100,
        features_text: "",
        is_active: true,
    });
    const [submitting, setSubmitting] = useState(false);

    // Modal Eliminar
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [planToDelete, setPlanToDelete] = useState<SubscriptionPlan | null>(null);
    const [deleting, setDeleting] = useState(false);

    const fetchPlans = async () => {
        setLoading(true);
        try {
            const response = await axios.get("/admin/api/plans/subscription-plans");
            if (response.data?.status === "success") {
                const resData = response.data.data;
                setPlans(resData.plans);
                setMetrics(resData.metrics);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar planes de suscripción." });
        } finally {
            setLoading(false);
        }
    };

    const handleOpenCreate = () => {
        setEditingPlan(null);
        setFormData({
            name: "",
            slug: "",
            description: "",
            price_monthly: 0,
            price_yearly: 0,
            commission_rate: 5,
            max_products: 100,
            features_text: "Dominio propio\nSoporte por WhatsApp\nPasarelas de Pago Binance y PagoMóvil\nFacturación BCV",
            is_active: true,
        });
        setFormModalOpen(true);
    };

    const handleOpenEdit = (plan: SubscriptionPlan) => {
        setEditingPlan(plan);
        setFormData({
            name: plan.name,
            slug: plan.slug,
            description: plan.description || "",
            price_monthly: plan.price_monthly,
            price_yearly: plan.price_yearly,
            commission_rate: plan.commission_rate,
            max_products: plan.max_products,
            features_text: Array.isArray(plan.features) ? plan.features.join("\n") : "",
            is_active: plan.is_active,
        });
        setFormModalOpen(true);
    };

    const handleFormSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);

        const featuresArray = formData.features_text
            .split("\n")
            .map((f) => f.trim())
            .filter((f) => f.length > 0);

        const payload = {
            id: editingPlan?.id,
            name: formData.name.trim(),
            slug: formData.slug.trim() || undefined,
            description: formData.description.trim() || null,
            price_monthly: Number(formData.price_monthly),
            price_yearly: Number(formData.price_yearly),
            commission_rate: Number(formData.commission_rate),
            max_products: Number(formData.max_products),
            features: featuresArray,
            is_active: formData.is_active,
        };

        try {
            const response = await axios.post("/admin/api/plans/subscription-plans", payload);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Plan "${payload.name}" guardado exitosamente.`,
                });
                setFormModalOpen(false);
                fetchPlans();
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al guardar plan de suscripción.",
            });
        } finally {
            setSubmitting(false);
        }
    };

    const handleDeleteSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!planToDelete) return;

        setDeleting(true);
        try {
            const response = await axios.delete(`/admin/api/plans/subscription-plans/${planToDelete.id}`);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Plan "${planToDelete.name}" eliminado exitosamente.`,
                });
                setDeleteModalOpen(false);
                fetchPlans();
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al eliminar plan de suscripción.",
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
                            <BreadcrumbItem>Facturación B2B</BreadcrumbItem>
                            <BreadcrumbItem>Planes de Suscripción</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiCreditCard className="text-blue-600 w-8 h-8" />
                            Planes de Suscripción y Tarifas B2B
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Esquema de monetización SaaS, comisiones por transacción y límites por tienda inquilina.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button color="light" size="sm" onClick={fetchPlans} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-1.5 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                        <Button color="blue" size="sm" onClick={handleOpenCreate}>
                            <HiPlus className="w-4 h-4 mr-1.5" />
                            Nuevo Plan
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
                                    Total Planes Creados
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_plans || 0}
                                </h3>
                                <p className="text-xs text-blue-600 font-medium mt-1">
                                    Esquemas tarifarios
                                </p>
                            </div>
                            <div className="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-xl">
                                <HiCreditCard className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Planes Activos
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.active_plans || 0}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    Disponibles para contratación
                                </p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-xl">
                                <HiCheckCircle className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-purple-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Tiendas Suscritas Activas
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.active_subscriptions || 0}
                                </h3>
                                <p className="text-xs text-purple-600 font-medium mt-1">
                                    Comercios con membresía activa
                                </p>
                            </div>
                            <div className="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 rounded-xl">
                                <HiUsers className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* TABLA DE PLANES */}
                <Card className="shadow-sm">
                    <div className="overflow-x-auto">
                        <Table hoverable>
                            <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                <TableHeadCell>Plan</TableHeadCell>
                                <TableHeadCell>Precio Mensual</TableHeadCell>
                                <TableHeadCell>Precio Anual</TableHeadCell>
                                <TableHeadCell>Comisión Marketplace</TableHeadCell>
                                <TableHeadCell>Límite Productos</TableHeadCell>
                                <TableHeadCell>Tiendas Activas</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y text-xs">
                                {plans.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-gray-400">
                                            No hay planes de suscripción configurados.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    plans.map((p) => (
                                        <TableRow key={p.id}>
                                            <TableCell>
                                                <div className="space-y-0.5">
                                                    <p className="font-bold text-gray-900 dark:text-white text-sm">{p.name}</p>
                                                    <p className="text-[11px] text-gray-400 font-mono">{p.slug}</p>
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-bold text-emerald-600 text-sm">
                                                ${parseFloat(String(p.price_monthly || 0)).toFixed(2)}/mes
                                            </TableCell>
                                            <TableCell className="font-medium text-gray-600 dark:text-gray-300">
                                                ${parseFloat(String(p.price_yearly || 0)).toFixed(2)}/año
                                            </TableCell>
                                            <TableCell className="font-bold text-blue-600">
                                                {p.commission_rate}% por venta
                                            </TableCell>
                                            <TableCell className="font-mono">
                                                {p.max_products} productos
                                            </TableCell>
                                            <TableCell>
                                                <Badge color="indigo" className="w-fit font-bold">
                                                    {p.subscriptions_count || 0} tiendas
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge color={p.is_active ? "success" : "failure"} className="w-fit">
                                                    {p.is_active ? "Activo" : "Inactivo"}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button size="xs" color="light" onClick={() => handleOpenEdit(p)}>
                                                        <HiPencilAlt className="w-4 h-4 text-blue-600" />
                                                    </Button>
                                                    <Button size="xs" color="failure" onClick={() => {
                                                        setPlanToDelete(p);
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

                {/* MODAL CREAR / EDITAR PLAN */}
                <Modal show={formModalOpen} onClose={() => setFormModalOpen(false)} size="lg">
                    <ModalHeader>
                        {editingPlan ? `Editar Plan: ${editingPlan.name}` : "Nuevo Plan de Suscripción"}
                    </ModalHeader>
                    <form onSubmit={handleFormSubmit}>
                        <ModalBody className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Nombre del Plan <span className="text-red-500">*</span>
                                    </label>
                                    <TextInput
                                        required
                                        placeholder="Ej: Emprendedor, Pro, Corporativo"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Slug URL (Opcional)
                                    </label>
                                    <TextInput
                                        placeholder="emprendedor, pro, enterprise..."
                                        value={formData.slug}
                                        onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Precio Mensual (USD) <span className="text-red-500">*</span>
                                    </label>
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.price_monthly}
                                        onChange={(e) => setFormData({ ...formData, price_monthly: Number(e.target.value) })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Precio Anual (USD)
                                    </label>
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        value={formData.price_yearly}
                                        onChange={(e) => setFormData({ ...formData, price_yearly: Number(e.target.value) })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Comisión Marketplace (%) <span className="text-red-500">*</span>
                                    </label>
                                    <TextInput
                                        type="number"
                                        step="0.01"
                                        required
                                        value={formData.commission_rate}
                                        onChange={(e) => setFormData({ ...formData, commission_rate: Number(e.target.value) })}
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Límite Máximo de Productos en Catálogo <span className="text-red-500">*</span>
                                </label>
                                <TextInput
                                    type="number"
                                    required
                                    value={formData.max_products}
                                    onChange={(e) => setFormData({ ...formData, max_products: Number(e.target.value) })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Descripción General
                                </label>
                                <TextInput
                                    placeholder="Ideal para comercios emergentes que inician en el e-commerce"
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Beneficios / Features del Plan (Una línea por beneficio)
                                </label>
                                <textarea
                                    className="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    rows={4}
                                    placeholder="Dominio Propio&#10;Soporte 24/7&#10;Integración con Binance Pay"
                                    value={formData.features_text}
                                    onChange={(e) => setFormData({ ...formData, features_text: e.target.value })}
                                />
                            </div>

                            <div className="flex items-center justify-between pt-2">
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">Plan Habilitado para Comercios</span>
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
                                Guardar Plan
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                {/* MODAL ELIMINAR PLAN */}
                <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} size="md">
                    <ModalHeader>Eliminar Plan de Suscripción</ModalHeader>
                    <form onSubmit={handleDeleteSubmit}>
                        <ModalBody className="space-y-3">
                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                ¿Estás seguro de eliminar el plan <strong>{planToDelete?.name}</strong>?
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

export default AdminSubscriptionPlansPage;
