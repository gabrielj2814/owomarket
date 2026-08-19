import Dashboard from "@/components/layouts/Dashboard";
import CouponServices from "@/Services/CouponServices";
import { ErrorsFormCoupon } from "@/types/ErrorsFormCoupon";
import { FormCoupon } from "@/types/FormCoupon";
import { Coupon } from "@/types/models/Coupon";
import { Head } from "@inertiajs/react";
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
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
    ToggleSwitch,
} from "flowbite-react";
import { FC, useEffect, useState } from "react";
import {
    HiHome,
    HiPlus,
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

const initialFormCoupon: FormCoupon = {
    code: "",
    type: "percentage",
    value: 10,
    min_order_amount: null,
    usage_limit: null,
    usage_limit_per_customer: null,
    valid_from: new Date().toISOString().split("T")[0],
    valid_to: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
    is_active: true,
};

const CouponIndexPage: FC<CouponIndexPageProps> = ({ user_id, title, host, user_name }) => {
    const [coupons, setCoupons] = useState<Coupon[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [search, setSearch] = useState<string>("");
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(10);

    // Create Modal States
    const [createModalOpen, setCreateModalOpen] = useState<boolean>(false);
    const [formData, setFormData] = useState<FormCoupon>(initialFormCoupon);
    const [formErrors, setFormErrors] = useState<ErrorsFormCoupon>({});

    // Delete Modal States
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
            const items = Array.isArray(res?.data) ? res.data : (Array.isArray(res?.data?.data) ? res.data.data : []);
            setCoupons(items);

            const pagination = (res as any)?.pagination || (res as any)?.data?.pagination;
            if (pagination) {
                setCurrentPage(pagination.current_page);
                setTotalPages(pagination.last_page);
                setTotalItems(pagination.total);
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

    const handleOpenCreate = () => {
        setFormData(initialFormCoupon);
        setFormErrors({});
        setCreateModalOpen(true);
    };

    const handleCreateSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading(true);
        setFormErrors({});
        try {
            const payload: FormCoupon = {
                ...formData,
                code: formData.code.trim().toUpperCase(),
                value: Number(formData.value),
                min_order_amount: formData.min_order_amount ? Number(formData.min_order_amount) : null,
                usage_limit: formData.usage_limit ? Number(formData.usage_limit) : null,
                usage_limit_per_customer: formData.usage_limit_per_customer ? Number(formData.usage_limit_per_customer) : null,
            };

            const res = await CouponServices.create(payload);
            if ((res as any)?.code === 201 || (res as any)?.code === 200 || (res as any)?.status === "success" || res?.status === 201 || res?.status === 200) {
                setCreateModalOpen(false);
                fetchCoupons(1);
            } else if ((res as any)?.errors || (res as any)?.data?.errors) {
                setFormErrors((res as any)?.errors || (res as any)?.data?.errors);
            }
        } catch (err: any) {
            console.error("Error creando cupón", err);
        } finally {
            setActionLoading(false);
        }
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
                        <Button color="purple" onClick={handleOpenCreate}>
                            <HiPlus className="w-4 h-4 mr-2" />
                            Crear Cupón
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

                {/* Create Modal */}
                <Modal show={createModalOpen} onClose={() => setCreateModalOpen(false)} size="lg">
                    <ModalHeader>
                        <span className="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                            <HiTicket className="w-6 h-6 text-pink-600" />
                            Crear Nuevo Cupón de Descuento
                        </span>
                    </ModalHeader>
                    <ModalBody>
                        <form onSubmit={handleCreateSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="code">Código del Cupón *</Label>
                                    <TextInput
                                        id="code"
                                        placeholder="Ej: OWOVERANO2026"
                                        value={formData.code}
                                        onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                                        required
                                        color={formErrors.code ? "failure" : undefined}
                                    />
                                    {formErrors.code && (
                                        <span className="text-xs text-red-500 mt-1 block">
                                            {Array.isArray(formErrors.code) ? formErrors.code[0] : formErrors.code}
                                        </span>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="type">Tipo de Descuento *</Label>
                                    <Select
                                        id="type"
                                        value={formData.type}
                                        onChange={(e) => setFormData({ ...formData, type: e.target.value as any })}
                                    >
                                        <option value="percentage">Porcentaje (%)</option>
                                        <option value="fixed_amount">Monto Fijo ($)</option>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="value">
                                        {formData.type === "percentage" ? "Porcentaje de Descuento (%) *" : "Monto de Descuento ($) *"}
                                    </Label>
                                    <TextInput
                                        id="value"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value={formData.value}
                                        onChange={(e) => setFormData({ ...formData, value: Number(e.target.value) })}
                                        required
                                        color={formErrors.value ? "failure" : undefined}
                                    />
                                    {formErrors.value && (
                                        <span className="text-xs text-red-500 mt-1 block">
                                            {Array.isArray(formErrors.value) ? formErrors.value[0] : formErrors.value}
                                        </span>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="min_order_amount">Monto Mínimo de Compra ($)</Label>
                                    <TextInput
                                        id="min_order_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="Opcional (Ej: 20.00)"
                                        value={formData.min_order_amount ?? ""}
                                        onChange={(e) => setFormData({ ...formData, min_order_amount: e.target.value ? Number(e.target.value) : null })}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="valid_from">Fecha de Inicio *</Label>
                                    <TextInput
                                        id="valid_from"
                                        type="date"
                                        value={formData.valid_from}
                                        onChange={(e) => setFormData({ ...formData, valid_from: e.target.value })}
                                        required
                                        color={formErrors.valid_from ? "failure" : undefined}
                                    />
                                    {formErrors.valid_from && (
                                        <span className="text-xs text-red-500 mt-1 block">
                                            {Array.isArray(formErrors.valid_from) ? formErrors.valid_from[0] : formErrors.valid_from}
                                        </span>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="valid_to">Fecha de Expiración *</Label>
                                    <TextInput
                                        id="valid_to"
                                        type="date"
                                        value={formData.valid_to}
                                        onChange={(e) => setFormData({ ...formData, valid_to: e.target.value })}
                                        required
                                        color={formErrors.valid_to ? "failure" : undefined}
                                    />
                                    {formErrors.valid_to && (
                                        <span className="text-xs text-red-500 mt-1 block">
                                            {Array.isArray(formErrors.valid_to) ? formErrors.valid_to[0] : formErrors.valid_to}
                                        </span>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="usage_limit">Límite Total de Usos</Label>
                                    <TextInput
                                        id="usage_limit"
                                        type="number"
                                        min="1"
                                        placeholder="Vacío para ilimitado"
                                        value={formData.usage_limit ?? ""}
                                        onChange={(e) => setFormData({ ...formData, usage_limit: e.target.value ? Number(e.target.value) : null })}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="usage_limit_per_customer">Límite de Usos por Cliente</Label>
                                    <TextInput
                                        id="usage_limit_per_customer"
                                        type="number"
                                        min="1"
                                        placeholder="Vacío para ilimitado"
                                        value={formData.usage_limit_per_customer ?? ""}
                                        onChange={(e) => setFormData({ ...formData, usage_limit_per_customer: e.target.value ? Number(e.target.value) : null })}
                                    />
                                </div>
                            </div>

                            <div className="pt-2">
                                <ToggleSwitch
                                    checked={formData.is_active ?? true}
                                    label="Cupón Activo y Disponible"
                                    onChange={(checked) => setFormData({ ...formData, is_active: checked })}
                                />
                            </div>

                            <div className="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <Button color="gray" onClick={() => setCreateModalOpen(false)}>
                                    Cancelar
                                </Button>
                                <Button color="purple" type="submit" disabled={actionLoading}>
                                    {actionLoading ? <Spinner size="sm" className="mr-2" /> : null}
                                    Guardar Cupón
                                </Button>
                            </div>
                        </form>
                    </ModalBody>
                </Modal>

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
