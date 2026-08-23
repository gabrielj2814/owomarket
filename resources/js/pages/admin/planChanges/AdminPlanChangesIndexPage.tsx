import Dashboard from "@/components/layouts/Dashboard";
import { Head, router } from "@inertiajs/react";
import axios from "axios";
import {
    Badge,
    Button,
    Card,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    Textarea,
} from "flowbite-react";
import React, { FC, useState } from "react";
import { HiCheckCircle, HiXCircle } from "react-icons/hi";
import { HiOutlineArrowsRightLeft } from "react-icons/hi2";

/**
 * Resolución de cambios de plan (hallazgo T3).
 *
 * El comerciante pide el cambio desde su pantalla de facturación; aquí se aprueba o se
 * rechaza. Antes ese botón era un `alert()` que decía «Un asesor te contactará» y no
 * mandaba nada: no existía ni esta pantalla ni la tabla detrás.
 *
 * La columna de comisión no es decorativa: aprobar cambia lo que la plataforma cobra por
 * cada venta de esa tienda, así que quien resuelve tiene que verlo antes de pulsar.
 */
interface PlanChangeRequest {
    id: string;
    tenant_id: string;
    tenant_name: string;
    current_plan: string | null;
    requested_plan: string | null;
    current_commission_rate: number | null;
    requested_commission_rate: number | null;
    billing_cycle: string;
    status: string;
    notes: string | null;
    rejection_reason: string | null;
    created_at: string | null;
    resolved_at: string | null;
}

interface Props {
    user_id: string;
    requests: PlanChangeRequest[];
    metrics: { pending_count: number; approved_count: number; rejected_count: number };
    filters: { status: string };
}

const getCsrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

export const AdminPlanChangesIndexPage: FC<Props> = ({ user_id, requests, metrics, filters }) => {
    const [procesando, setProcesando] = useState<string | null>(null);
    const [aviso, setAviso] = useState<{ type: "success" | "error"; text: string } | null>(null);

    const [rechazando, setRechazando] = useState<PlanChangeRequest | null>(null);
    const [motivo, setMotivo] = useState("");

    const resolver = async (id: string, accion: "approve" | "reject", cuerpo: object = {}) => {
        setAviso(null);
        setProcesando(id);

        try {
            const res = await axios.post(`/admin/api/plan-changes/${id}/${accion}`, cuerpo, {
                headers: { "X-CSRF-TOKEN": getCsrf() },
            });

            setAviso({ type: "success", text: res.data?.message || "Solicitud resuelta." });
            setRechazando(null);
            setMotivo("");
            router.reload({ only: ["requests", "metrics"] });
        } catch (error: any) {
            setAviso({
                type: "error",
                text: error.response?.data?.message || "No se pudo resolver la solicitud.",
            });
        } finally {
            setProcesando(null);
        }
    };

    const colorEstado = (estado: string) =>
        estado === "pending" ? "warning" : estado === "approved" ? "success" : "failure";

    return (
        <Dashboard user_uuid={user_id}>
            <Head title="Cambios de Plan - OwOMarket Admin" />

            <div className="p-4 sm:p-6 space-y-6">
                <div className="bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-200 dark:border-gray-700">
                    <h1 className="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <HiOutlineArrowsRightLeft className="w-6 h-6 text-indigo-600" />
                        Solicitudes de Cambio de Plan
                    </h1>
                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Aprobar un cambio modifica la comisión que la plataforma cobra a esa tienda por
                        cada venta.
                    </p>
                </div>

                {aviso && (
                    <div
                        role={aviso.type === "error" ? "alert" : "status"}
                        className={`p-3 rounded-2xl text-xs font-bold border ${
                            aviso.type === "error"
                                ? "bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800"
                                : "bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800"
                        }`}
                    >
                        {aviso.text}
                    </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Card><div className="text-xs text-gray-500">Pendientes</div><div className="text-2xl font-black text-amber-600">{metrics.pending_count}</div></Card>
                    <Card><div className="text-xs text-gray-500">Aprobadas</div><div className="text-2xl font-black text-green-600">{metrics.approved_count}</div></Card>
                    <Card><div className="text-xs text-gray-500">Rechazadas</div><div className="text-2xl font-black text-red-600">{metrics.rejected_count}</div></Card>
                </div>

                <div className="flex gap-2">
                    {["pending", "approved", "rejected", "all"].map((s) => (
                        <Button
                            key={s}
                            size="xs"
                            color={filters.status === s ? "blue" : "light"}
                            onClick={() => router.get(`/admin/backoffice/${user_id}/plan-changes`, { status: s }, { preserveState: false })}
                        >
                            {s === "pending" ? "Pendientes" : s === "approved" ? "Aprobadas" : s === "rejected" ? "Rechazadas" : "Todas"}
                        </Button>
                    ))}
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                    {requests.length === 0 ? (
                        <div className="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            No hay solicitudes con este filtro.
                        </div>
                    ) : (
                        <Table hoverable>
                            <TableHead>
                                <TableRow>
                                    <TableHeadCell>Tienda</TableHeadCell>
                                    <TableHeadCell>Cambio</TableHeadCell>
                                    <TableHeadCell>Comisión</TableHeadCell>
                                    <TableHeadCell>Estado</TableHeadCell>
                                    <TableHeadCell>Acciones</TableHeadCell>
                                </TableRow>
                            </TableHead>
                            <TableBody className="divide-y">
                                {requests.map((r) => (
                                    <TableRow key={r.id} className="bg-white dark:bg-gray-800">
                                        <TableCell className="font-bold text-gray-900 dark:text-white">
                                            {r.tenant_name}
                                        </TableCell>
                                        <TableCell className="text-xs">
                                            {r.current_plan ?? "Sin plan"} → <strong>{r.requested_plan}</strong>
                                            <div className="text-[11px] text-gray-500">{r.billing_cycle === "yearly" ? "Anual" : "Mensual"}</div>
                                        </TableCell>
                                        <TableCell className="text-xs">
                                            {r.current_commission_rate ?? "—"}% → <strong>{r.requested_commission_rate ?? "—"}%</strong>
                                        </TableCell>
                                        <TableCell>
                                            <Badge color={colorEstado(r.status)} className="w-fit">
                                                {r.status}
                                            </Badge>
                                            {r.rejection_reason && (
                                                <div className="text-[11px] text-gray-500 mt-1">{r.rejection_reason}</div>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {r.status === "pending" ? (
                                                <div className="flex gap-2">
                                                    <Button
                                                        size="xs"
                                                        color="green"
                                                        disabled={procesando === r.id}
                                                        onClick={() => resolver(r.id, "approve")}
                                                    >
                                                        <HiCheckCircle className="w-4 h-4 mr-1" /> Aprobar
                                                    </Button>
                                                    <Button
                                                        size="xs"
                                                        color="red"
                                                        disabled={procesando === r.id}
                                                        onClick={() => { setRechazando(r); setMotivo(""); }}
                                                    >
                                                        <HiXCircle className="w-4 h-4 mr-1" /> Rechazar
                                                    </Button>
                                                </div>
                                            ) : (
                                                <span className="text-[11px] text-gray-400">Resuelta</span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </div>
            </div>

            <Modal show={rechazando !== null} onClose={() => setRechazando(null)} size="md">
                <ModalHeader>Rechazar cambio de plan</ModalHeader>
                <ModalBody>
                    {/* El motivo es obligatorio también en el servidor: una solicitud que vuelve
                        rechazada sin explicación deja al comerciante sin saber qué corregir. */}
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        El comerciante verá este motivo. Es obligatorio.
                    </p>
                    <Textarea
                        rows={3}
                        value={motivo}
                        onChange={(e) => setMotivo(e.target.value)}
                        placeholder="Ej: hay facturas pendientes de pago."
                    />
                </ModalBody>
                <ModalFooter>
                    <Button
                        color="red"
                        disabled={!motivo.trim() || procesando === rechazando?.id}
                        onClick={() => rechazando && resolver(rechazando.id, "reject", { rejection_reason: motivo.trim() })}
                    >
                        Rechazar solicitud
                    </Button>
                    <Button color="light" onClick={() => setRechazando(null)}>
                        Cancelar
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
};

export default AdminPlanChangesIndexPage;
