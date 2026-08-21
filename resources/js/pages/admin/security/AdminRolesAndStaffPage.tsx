import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import axios from "axios";
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Checkbox,
    Label,
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
    TabItem,
    Tabs,
    TextInput,
} from "flowbite-react";
import React, { FC, useState } from "react";
import {
    HiCheck,
    HiCheckCircle,
    HiHome,
    HiKey,
    HiPencilAlt,
    HiPlus,
    HiRefresh,
    HiShieldCheck,
    HiUserAdd,
    HiUserGroup,
    HiUsers,
} from "react-icons/hi";
import { LuKeyRound, LuShieldAlert, LuUserCheck } from "react-icons/lu";

interface RoleItem {
    id: number;
    name: string;
    guard_name: string;
    permissions: string[];
    users_count: number;
    created_at?: string;
}

interface PermissionItem {
    id: number;
    name: string;
    guard_name: string;
}

interface StaffUser {
    id: string;
    name: string;
    email: string;
    type: string;
    is_active: boolean;
    roles: string[];
    direct_permissions: string[];
    created_at?: string;
}

interface Metrics {
    total_roles: number;
    total_permissions: number;
    total_staff: number;
}

interface AdminRolesAndStaffPageProps {
    title?: string;
    user_id: string;
    roles: RoleItem[];
    permissions: PermissionItem[];
    staff_users: StaffUser[];
    metrics: Metrics;
}

const AdminRolesAndStaffPage: FC<AdminRolesAndStaffPageProps> = ({
    title = "Roles, Permisos RBAC & Staff - OwOMarket",
    user_id,
    roles: initialRoles,
    permissions: initialPermissions,
    staff_users: initialStaff,
    metrics: initialMetrics,
}) => {
    const [roles, setRoles] = useState<RoleItem[]>(initialRoles || []);
    const [permissions, setPermissions] = useState<PermissionItem[]>(initialPermissions || []);
    const [staffUsers, setStaffUsers] = useState<StaffUser[]>(initialStaff || []);
    const [metrics, setMetrics] = useState<Metrics>(initialMetrics);

    const [loading, setLoading] = useState(false);
    const [toast, setToast] = useState<{ type: "success" | "error"; text: string } | null>(null);

    // Modal Crear / Editar Rol
    const [roleModalOpen, setRoleModalOpen] = useState(false);
    const [editingRole, setEditingRole] = useState<RoleItem | null>(null);
    const [roleName, setRoleName] = useState("");
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);
    const [submittingRole, setSubmittingRole] = useState(false);

    // Modal Asignar Rol a Usuario Staff
    const [assignModalOpen, setAssignModalOpen] = useState(false);
    const [selectedUser, setSelectedUser] = useState<StaffUser | null>(null);
    const [assignedRoles, setAssignedRoles] = useState<string[]>([]);
    const [submittingAssign, setSubmittingAssign] = useState(false);

    const fetchRolesAndStaff = async () => {
        setLoading(true);
        try {
            const response = await axios.get("/admin/api/security/roles");
            if (response.data?.status === "success") {
                const resData = response.data.data;
                setRoles(resData.roles);
                setPermissions(resData.permissions);
                setStaffUsers(resData.staff_users);
                setMetrics(resData.metrics);
            }
        } catch (e) {
            setToast({ type: "error", text: "Error al cargar roles y permisos." });
        } finally {
            setLoading(false);
        }
    };

    const handleOpenCreateRole = () => {
        setEditingRole(null);
        setRoleName("");
        setSelectedPermissions([]);
        setRoleModalOpen(true);
    };

    const handleOpenEditRole = (role: RoleItem) => {
        setEditingRole(role);
        setRoleName(role.name);
        setSelectedPermissions(role.permissions || []);
        setRoleModalOpen(true);
    };

    const handleTogglePermission = (permName: string) => {
        if (selectedPermissions.includes(permName)) {
            setSelectedPermissions(selectedPermissions.filter((p) => p !== permName));
        } else {
            setSelectedPermissions([...selectedPermissions, permName]);
        }
    };

    const handleRoleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmittingRole(true);

        const payload = {
            id: editingRole?.id,
            name: roleName.trim(),
            permissions: selectedPermissions,
        };

        try {
            const response = await axios.post("/admin/api/security/roles", payload);
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Rol "${payload.name}" guardado exitosamente con ${selectedPermissions.length} permisos.`,
                });
                setRoleModalOpen(false);
                fetchRolesAndStaff();
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al guardar rol.",
            });
        } finally {
            setSubmittingRole(false);
        }
    };

    const handleOpenAssignModal = (user: StaffUser) => {
        setSelectedUser(user);
        setAssignedRoles(user.roles || []);
        setAssignModalOpen(true);
    };

    const handleToggleAssignRole = (roleName: string) => {
        if (assignedRoles.includes(roleName)) {
            setAssignedRoles(assignedRoles.filter((r) => r !== roleName));
        } else {
            setAssignedRoles([...assignedRoles, roleName]);
        }
    };

    const handleAssignSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedUser) return;

        setSubmittingAssign(true);
        try {
            const response = await axios.post(`/admin/api/security/staff/${selectedUser.id}/roles`, {
                roles: assignedRoles,
            });
            if (response.data?.status === "success") {
                setToast({
                    type: "success",
                    text: `Roles de "${selectedUser.name}" actualizados exitosamente.`,
                });
                setAssignModalOpen(false);
                fetchRolesAndStaff();
            }
        } catch (error: any) {
            setToast({
                type: "error",
                text: error.response?.data?.message || "Error al asignar roles al usuario.",
            });
        } finally {
            setSubmittingAssign(false);
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
                            <BreadcrumbItem>Seguridad & Staff</BreadcrumbItem>
                            <BreadcrumbItem>Roles y Permisos (RBAC)</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <HiShieldCheck className="text-indigo-600 w-8 h-8" />
                            Control de Acceso Basado en Roles (RBAC)
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Gestión granular de privilegios y asignación de roles para el equipo de administración central.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button color="light" size="sm" onClick={fetchRolesAndStaff} disabled={loading}>
                            <HiRefresh className={`w-4 h-4 mr-1.5 ${loading ? "animate-spin" : ""}`} />
                            Actualizar
                        </Button>
                        <Button color="blue" size="sm" onClick={handleOpenCreateRole}>
                            <HiPlus className="w-4 h-4 mr-1.5" />
                            Nuevo Rol
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
                                    Total Roles Definidos
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_roles || 0}
                                </h3>
                                <p className="text-xs text-indigo-600 font-medium mt-1">
                                    Perfiles de acceso
                                </p>
                            </div>
                            <div className="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 rounded-xl">
                                <HiShieldCheck className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-purple-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Permisos Granulares
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_permissions || 0}
                                </h3>
                                <p className="text-xs text-purple-600 font-medium mt-1">
                                    Capacidades del sistema
                                </p>
                            </div>
                            <div className="p-3 bg-purple-50 dark:bg-purple-900/30 text-purple-600 rounded-xl">
                                <LuKeyRound className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>

                    <Card className="border-l-4 border-emerald-500 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Usuarios de Staff
                                </p>
                                <h3 className="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">
                                    {metrics?.total_staff || 0}
                                </h3>
                                <p className="text-xs text-emerald-600 font-medium mt-1">
                                    Cuentas de administradores
                                </p>
                            </div>
                            <div className="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-xl">
                                <HiUsers className="w-7 h-7" />
                            </div>
                        </div>
                    </Card>
                </div>

                {/* TABS ROLES & STAFF */}
                <Card className="shadow-sm">
                    <Tabs aria-label="Gestión de Seguridad" variant="underline">
                        {/* TAB 1: ROLES & MATRIZ DE PERMISOS */}
                        <TabItem active title="Roles de Seguridad" icon={HiKey}>
                            <div className="pt-4 space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {roles.map((r) => (
                                        <div
                                            key={r.id}
                                            className="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 flex flex-col justify-between space-y-3"
                                        >
                                            <div>
                                                <div className="flex items-center justify-between">
                                                    <h3 className="font-bold text-gray-900 dark:text-white text-base">
                                                        {r.name}
                                                    </h3>
                                                    <Badge color="indigo" className="font-mono">
                                                        {r.users_count} usuarios
                                                    </Badge>
                                                </div>
                                                <p className="text-xs text-gray-500 mt-1">
                                                    Guard: <span className="font-mono">{r.guard_name}</span>
                                                </p>
                                            </div>

                                            <div>
                                                <p className="text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                                    Permisos ({r.permissions?.length || 0}):
                                                </p>
                                                <div className="flex flex-wrap gap-1">
                                                    {(!r.permissions || r.permissions.length === 0) ? (
                                                        <span className="text-xs text-gray-400 italic">Sin permisos</span>
                                                    ) : (
                                                        r.permissions.map((p) => (
                                                            <Badge key={p} color="gray" className="text-[10px] font-mono">
                                                                {p}
                                                            </Badge>
                                                        ))
                                                    )}
                                                </div>
                                            </div>

                                            <div className="pt-2 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                                                <Button size="xs" color="light" onClick={() => handleOpenEditRole(r)}>
                                                    <HiPencilAlt className="w-3.5 h-3.5 mr-1 text-blue-600" />
                                                    Configurar Permisos
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </TabItem>

                        {/* TAB 2: STAFF & ASIGNACIÓN DE ROLES */}
                        <TabItem title="Equipo de Staff" icon={HiUserGroup}>
                            <div className="pt-4 overflow-x-auto">
                                <Table hoverable>
                                    <TableHead className="bg-gray-100 dark:bg-gray-700 text-xs">
                                        <TableHeadCell>Nombre / Email</TableHeadCell>
                                        <TableHeadCell>Tipo de Cuenta</TableHeadCell>
                                        <TableHeadCell>Roles Asignados</TableHeadCell>
                                        <TableHeadCell>Estado</TableHeadCell>
                                        <TableHeadCell className="text-right">Acción</TableHeadCell>
                                    </TableHead>
                                    <TableBody className="divide-y text-xs">
                                        {staffUsers.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={5} className="text-center py-8 text-gray-400">
                                                    No se registran miembros de staff en la plataforma.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            staffUsers.map((u) => (
                                                <TableRow key={u.id}>
                                                    <TableCell>
                                                        <div className="space-y-0.5">
                                                            <p className="font-bold text-gray-900 dark:text-white text-sm">{u.name}</p>
                                                            <p className="text-[11px] font-mono text-gray-400">{u.email}</p>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="capitalize font-medium text-gray-600 dark:text-gray-300">
                                                        {u.type?.replace("_", " ")}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex flex-wrap gap-1">
                                                            {(!u.roles || u.roles.length === 0) ? (
                                                                <Badge color="warning" className="text-[10px]">
                                                                    Sin Rol
                                                                </Badge>
                                                            ) : (
                                                                u.roles.map((r) => (
                                                                    <Badge key={r} color="indigo" className="text-[10px]">
                                                                        {r}
                                                                    </Badge>
                                                                ))
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge color={u.is_active ? "success" : "failure"} className="w-fit">
                                                            {u.is_active ? "Activo" : "Inactivo"}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <Button size="xs" color="blue" onClick={() => handleOpenAssignModal(u)}>
                                                            <HiShieldCheck className="w-3.5 h-3.5 mr-1" />
                                                            Asignar Roles
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </TabItem>
                    </Tabs>
                </Card>

                {/* MODAL CREAR / EDITAR ROL */}
                <Modal show={roleModalOpen} onClose={() => setRoleModalOpen(false)} size="lg">
                    <ModalHeader>
                        {editingRole ? `Editar Rol: ${editingRole.name}` : "Nuevo Rol de Seguridad"}
                    </ModalHeader>
                    <form onSubmit={handleRoleSubmit}>
                        <ModalBody className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Nombre del Rol <span className="text-red-500">*</span>
                                </label>
                                <TextInput
                                    required
                                    placeholder="Ej: Agente de Soporte, Gestor de Contenidos..."
                                    value={roleName}
                                    onChange={(e) => setRoleName(e.target.value)}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                                    Seleccionar Permisos ({selectedPermissions.length} seleccionados):
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-60 overflow-y-auto p-2 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    {permissions.map((p) => {
                                        const isChecked = selectedPermissions.includes(p.name);
                                        return (
                                            <div
                                                key={p.id}
                                                onClick={() => handleTogglePermission(p.name)}
                                                className={`p-2.5 rounded-lg border cursor-pointer flex items-center gap-2.5 transition-colors text-xs ${
                                                    isChecked
                                                        ? "border-blue-500 bg-blue-50/50 dark:bg-blue-900/20 text-blue-900 dark:text-blue-200"
                                                        : "border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                                                }`}
                                            >
                                                <Checkbox
                                                    checked={isChecked}
                                                    onChange={() => {}}
                                                />
                                                <span className="font-mono">{p.name}</span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setRoleModalOpen(false)} disabled={submittingRole}>
                                Cancelar
                            </Button>
                            <Button color="blue" type="submit" disabled={submittingRole}>
                                {submittingRole ? <Spinner size="sm" className="mr-2" /> : <HiCheckCircle className="w-4 h-4 mr-2" />}
                                Guardar Rol y Permisos
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                {/* MODAL ASIGNAR ROLES A USUARIO */}
                <Modal show={assignModalOpen} onClose={() => setAssignModalOpen(false)} size="md">
                    <ModalHeader>
                        Asignar Roles a: {selectedUser?.name}
                    </ModalHeader>
                    <form onSubmit={handleAssignSubmit}>
                        <ModalBody className="space-y-4">
                            <p className="text-xs text-gray-500">
                                Selecciona los roles que tendrá el usuario <strong>{selectedUser?.email}</strong> en el sistema central:
                            </p>

                            <div className="space-y-2">
                                {roles.map((r) => {
                                    const isAssigned = assignedRoles.includes(r.name);
                                    return (
                                        <div
                                            key={r.id}
                                            onClick={() => handleToggleAssignRole(r.name)}
                                            className={`p-3 rounded-lg border cursor-pointer flex items-center justify-between text-xs transition-colors ${
                                                isAssigned
                                                    ? "border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20 text-indigo-900 dark:text-indigo-200 font-bold"
                                                    : "border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                                            }`}
                                        >
                                            <div className="flex items-center gap-2">
                                                <Checkbox checked={isAssigned} onChange={() => {}} />
                                                <span>{r.name}</span>
                                            </div>
                                            <Badge color="gray" className="text-[10px]">
                                                {r.permissions?.length || 0} permisos
                                            </Badge>
                                        </div>
                                    );
                                })}
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button color="gray" onClick={() => setAssignModalOpen(false)} disabled={submittingAssign}>
                                Cancelar
                            </Button>
                            <Button color="blue" type="submit" disabled={submittingAssign}>
                                {submittingAssign ? <Spinner size="sm" className="mr-2" /> : <HiCheckCircle className="w-4 h-4 mr-2" />}
                                Confirmar Asignación
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </div>
        </Dashboard>
    );
};

export default AdminRolesAndStaffPage;
