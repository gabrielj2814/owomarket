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
    TextInput,
} from "flowbite-react";
import React, { FC, useEffect, useRef, useState } from "react";
import {
    HiChatAlt2,
    HiCheckCircle,
    HiClock,
    HiDocumentDownload,
    HiEye,
    HiHome,
    HiPaperAirplane,
    HiPaperClip,
    HiPhotograph,
    HiPlay,
    HiRefresh,
    HiSearch,
    HiUserCircle,
    HiVideoCamera,
    HiXCircle,
} from "react-icons/hi";
import { LuBuilding2, LuLifeBuoy, LuUserCheck } from "react-icons/lu";

interface Attachment {
    url: string;
    type: "image" | "video" | "file";
    original_name: string;
    size_bytes?: number;
    mime_type?: string;
}

interface Message {
    id: string;
    ticket_id: string;
    sender_type: "customer" | "tenant" | "admin" | "system";
    sender_id: string;
    sender_name: string;
    message: string;
    attachments?: Attachment[];
    created_at: string;
}

interface SupportTicket {
    id: string;
    ticket_number: string;
    requester_type: "tenant" | "customer";
    user_id: string;
    tenant_id?: string | null;
    category: string;
    priority: "low" | "medium" | "high" | "urgent";
    status: "open" | "in_progress" | "waiting_reply" | "resolved" | "closed";
    subject: string;
    description: string;
    attachments?: Attachment[];
    created_at: string;
    last_reply_at?: string | null;
    messages?: Message[];
}

interface AdminSupportTicketsPageProps {
    title?: string;
    user_id: string;
    tickets: SupportTicket[];
    pagination: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
    metrics: {
        total_open: number;
        total_pending: number;
        tenant_tickets_count: number;
        customer_tickets_count: number;
        resolved_count: number;
    };
    filters?: {
        requester_type?: string;
        status?: string;
        priority?: string;
        category?: string;
        search?: string;
    };
}

const AdminSupportTicketsPage: FC<AdminSupportTicketsPageProps> = ({
    title = "Mesa Central de Soporte y Tickets",
    user_id,
    tickets: initialTickets,
    pagination: initialPagination,
    metrics: initialMetrics,
    filters: initialFilters,
}) => {
    const [ticketsList, setTicketsList] = useState<SupportTicket[]>(initialTickets || []);
    const [selectedTicket, setSelectedTicket] = useState<SupportTicket | null>(initialTickets?.[0] || null);
    const [metrics, setMetrics] = useState(initialMetrics);
    const [pagination, setPagination] = useState(initialPagination);

    // Filtros
    const [requesterFilter, setRequesterFilter] = useState<string>(initialFilters?.requester_type || "all");
    const [statusFilter, setStatusFilter] = useState<string>(initialFilters?.status || "all");
    const [priorityFilter, setPriorityFilter] = useState<string>(initialFilters?.priority || "all");
    const [searchTerm, setSearchTerm] = useState<string>(initialFilters?.search || "");
    const [loading, setLoading] = useState(false);

    // Formulario de respuesta
    const [replyMessage, setReplyMessage] = useState("");
    const [newStatus, setNewStatus] = useState<string>("");
    const [replyFiles, setReplyFiles] = useState<File[]>([]);
    const [submittingReply, setSubmittingReply] = useState(false);
    // Unica pagina de las cuatro que no tenia estado de aviso: el fallo al responder un
    // ticket salia por alert(). Misma forma `{type, text}` que usan las demas.
    const [replyFeedback, setReplyFeedback] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    // Visor multimedia
    const [previewImage, setPreviewImage] = useState<string | null>(null);
    const [previewVideo, setPreviewVideo] = useState<string | null>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    useEffect(() => {
        scrollToBottom();
    }, [selectedTicket?.messages]);

    const fetchTickets = async (page = 1) => {
        setLoading(true);
        try {
            const response = await axios.post("/admin/api/support/tickets/filter", {
                requester_type: requesterFilter,
                status: statusFilter,
                priority: priorityFilter,
                search: searchTerm,
                page,
            });
            if (response.data.code === 200) {
                setTicketsList(response.data.data.tickets);
                setPagination(response.data.data.pagination);
                setMetrics(response.data.data.metrics);
                if (response.data.data.tickets.length > 0 && (!selectedTicket || !response.data.data.tickets.some((t: SupportTicket) => t.id === selectedTicket.id))) {
                    setSelectedTicket(response.data.data.tickets[0]);
                }
            }
        } catch (error) {
            console.error("Error al cargar tickets:", error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchTickets(1);
    };

    const handleSendReply = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedTicket || (!replyMessage.trim() && replyFiles.length === 0)) return;

        // Limpiar el aviso anterior: si no, un error de hace un rato se queda en pantalla
        // mientras el envio nuevo va bien.
        setReplyFeedback(null);

        setSubmittingReply(true);
        try {
            const formData = new FormData();
            formData.append("message", replyMessage);
            if (newStatus) {
                formData.append("status", newStatus);
            }
            replyFiles.forEach((file) => {
                formData.append("attachments[]", file);
            });

            const response = await axios.post(`/admin/api/support/tickets/${selectedTicket.id}/reply`, formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            if (response.data.code === 200) {
                setSelectedTicket(response.data.data.ticket);
                setReplyMessage("");
                setReplyFiles([]);
                setNewStatus("");
                fetchTickets(pagination.current_page);
            }
        } catch (error: any) {
            setReplyFeedback({ type: 'error', text: error.response?.data?.message || 'No se pudo enviar la respuesta.' });
        } finally {
            setSubmittingReply(false);
        }
    };

    const handleQuickStatusChange = async (ticketId: string, status: string) => {
        try {
            const response = await axios.patch(`/admin/api/support/tickets/${ticketId}/status`, { status });
            if (response.data.code === 200) {
                setSelectedTicket(response.data.data);
                fetchTickets(pagination.current_page);
            }
        } catch (error) {
            console.error("Error al actualizar estado:", error);
        }
    };

    const handleApplyCannedResponse = (text: string, statusToSet?: string) => {
        setReplyMessage(text);
        if (statusToSet) {
            setNewStatus(statusToSet);
        }
    };

    return (
        <>
            <Head>
                <title>{title}</title>
            </Head>
            <Dashboard user_uuid={user_id}>
                {/* Breadcrumb */}
                <Breadcrumb aria-label="Navegación Admin" className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-5">
                    <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                        Dashboard
                    </BreadcrumbItem>
                    <BreadcrumbItem>Mesa Central de Soporte</BreadcrumbItem>
                </Breadcrumb>

                {/* Banner Header */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <LuLifeBuoy className="text-blue-600 dark:text-blue-400 text-3xl" />
                            Helpdesk & Centro de Soporte Universal
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Atiende incidencias técnicas, solicitudes financieras y consultas de comercios inquilinos y clientes del marketplace.
                        </p>
                    </div>
                    <Button size="xs" color="light" onClick={() => fetchTickets(pagination.current_page)}>
                        <HiRefresh className="mr-1 h-4 w-4" /> Actualizar Cola
                    </Button>
                </div>

                {/* Métricas Cards */}
                <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
                    <Card className="p-3 border-l-4 border-red-500 shadow-sm">
                        <p className="text-[11px] font-semibold uppercase text-gray-500">Abiertos / En Proceso</p>
                        <h3 className="text-xl font-bold text-red-600 dark:text-red-400 mt-1">{metrics?.total_open || 0}</h3>
                    </Card>
                    <Card className="p-3 border-l-4 border-amber-500 shadow-sm">
                        <p className="text-[11px] font-semibold uppercase text-gray-500">Esperando Respuesta</p>
                        <h3 className="text-xl font-bold text-amber-600 dark:text-amber-400 mt-1">{metrics?.total_pending || 0}</h3>
                    </Card>
                    <Card className="p-3 border-l-4 border-blue-500 shadow-sm">
                        <p className="text-[11px] font-semibold uppercase text-gray-500">De Tiendas Inquilinas</p>
                        <h3 className="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">{metrics?.tenant_tickets_count || 0}</h3>
                    </Card>
                    <Card className="p-3 border-l-4 border-purple-500 shadow-sm">
                        <p className="text-[11px] font-semibold uppercase text-gray-500">De Clientes Marketplace</p>
                        <h3 className="text-xl font-bold text-purple-600 dark:text-purple-400 mt-1">{metrics?.customer_tickets_count || 0}</h3>
                    </Card>
                    <Card className="p-3 border-l-4 border-green-500 shadow-sm">
                        <p className="text-[11px] font-semibold uppercase text-gray-500">Resueltos / Cerrados</p>
                        <h3 className="text-xl font-bold text-green-600 dark:text-green-400 mt-1">{metrics?.resolved_count || 0}</h3>
                    </Card>
                </div>

                {/* Filtros */}
                <Card className="p-3 mb-6 shadow-sm">
                    <form onSubmit={handleFilterSubmit} className="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Origen</label>
                            <select
                                value={requesterFilter}
                                onChange={(e) => setRequesterFilter(e.target.value)}
                                className="w-full text-xs bg-gray-50 border border-gray-300 text-gray-900 rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="all">Todos los orígenes</option>
                                <option value="tenant">Tiendas Inquilinas</option>
                                <option value="customer">Clientes Compradores</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                            <select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                                className="w-full text-xs bg-gray-50 border border-gray-300 text-gray-900 rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="all">Todos los estados</option>
                                <option value="open">Abierto</option>
                                <option value="in_progress">En Progreso</option>
                                <option value="waiting_reply">Esperando Respuesta</option>
                                <option value="resolved">Resuelto</option>
                                <option value="closed">Cerrado</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Prioridad</label>
                            <select
                                value={priorityFilter}
                                onChange={(e) => setPriorityFilter(e.target.value)}
                                className="w-full text-xs bg-gray-50 border border-gray-300 text-gray-900 rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="all">Todas las prioridades</option>
                                <option value="urgent">Urgente</option>
                                <option value="high">Alta</option>
                                <option value="medium">Media</option>
                                <option value="low">Baja</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Búsqueda</label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="N° ticket o asunto..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                sizing="sm"
                            />
                        </div>
                        <div>
                            <Button type="submit" size="xs" color="blue" className="w-full" disabled={loading}>
                                {loading ? <Spinner size="xs" className="mr-1" /> : <HiSearch className="mr-1" />} Filtrar
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* Split-Pane: Lista a la izquierda / Chat a la derecha */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    {/* COLUMNA IZQUIERDA: LISTA DE TICKETS (5 COLS) */}
                    <div className="lg:col-span-5 space-y-3">
                        <div className="flex items-center justify-between px-1">
                            <span className="text-xs font-semibold uppercase text-gray-500">
                                Tickets ({pagination?.total || 0})
                            </span>
                        </div>

                        {ticketsList.length === 0 ? (
                            <Card className="text-center p-6 text-gray-500 text-sm">
                                No se encontraron tickets en esta vista.
                            </Card>
                        ) : (
                            <div className="space-y-2 max-h-[680px] overflow-y-auto pr-1">
                                {ticketsList.map((ticket) => {
                                    const isSelected = selectedTicket?.id === ticket.id;
                                    return (
                                        <div
                                            key={ticket.id}
                                            onClick={() => setSelectedTicket(ticket)}
                                            className={`p-3 rounded-lg border cursor-pointer transition-all duration-150 ${
                                                isSelected
                                                    ? "bg-blue-50/80 border-blue-500 shadow-sm dark:bg-blue-900/20 dark:border-blue-500"
                                                    : "bg-white border-gray-200 hover:border-gray-300 dark:bg-gray-800 dark:border-gray-700"
                                            }`}
                                        >
                                            <div className="flex items-center justify-between mb-1">
                                                <div className="flex items-center gap-1.5">
                                                    {ticket.requester_type === "tenant" ? (
                                                        <Badge color="purple" icon={LuBuilding2} className="text-[10px] px-1.5 py-0.5">
                                                            Tienda
                                                        </Badge>
                                                    ) : (
                                                        <Badge color="info" icon={LuUserCheck} className="text-[10px] px-1.5 py-0.5">
                                                            Cliente
                                                        </Badge>
                                                    )}
                                                    <span className="text-xs font-mono font-bold text-gray-700 dark:text-gray-300">
                                                        {ticket.ticket_number}
                                                    </span>
                                                </div>
                                                <span className="text-[10px] text-gray-400">
                                                    {new Date(ticket.created_at).toLocaleDateString()}
                                                </span>
                                            </div>

                                            <h4 className="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                                {ticket.subject}
                                            </h4>
                                            <p className="text-xs text-gray-500 dark:text-gray-400 line-clamp-1 mt-0.5">
                                                {ticket.description}
                                            </p>

                                            <div className="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                                <div className="flex items-center gap-1">
                                                    <Badge
                                                        color={
                                                            ticket.priority === "urgent" ? "failure" :
                                                            ticket.priority === "high" ? "warning" : "gray"
                                                        }
                                                        className="text-[10px] capitalize"
                                                    >
                                                        {ticket.priority}
                                                    </Badge>
                                                    <Badge
                                                        color={
                                                            ticket.status === "open" ? "failure" :
                                                            ticket.status === "in_progress" ? "warning" :
                                                            ticket.status === "waiting_reply" ? "purple" : "success"
                                                        }
                                                        className="text-[10px] capitalize"
                                                    >
                                                        {ticket.status.replace("_", " ")}
                                                    </Badge>
                                                </div>
                                                {ticket.attachments && ticket.attachments.length > 0 && (
                                                    <span className="text-[11px] text-blue-600 dark:text-blue-400 flex items-center gap-0.5 font-medium">
                                                        <HiPaperClip className="text-xs" /> {ticket.attachments.length}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* COLUMNA DERECHA: DETALLE DEL TICKET & HILO DE CHAT (7 COLS) */}
                    <div className="lg:col-span-7">
                        {selectedTicket ? (
                            <Card className="p-0 shadow-sm overflow-hidden flex flex-col h-[750px]">
                                {/* Cabecera de Ticket */}
                                <div className="p-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                    <div className="flex flex-wrap items-center justify-between gap-2 mb-2">
                                        <div className="flex items-center gap-2">
                                            <span className="text-base font-mono font-bold text-blue-600 dark:text-blue-400">
                                                {selectedTicket.ticket_number}
                                            </span>
                                            <Badge
                                                color={selectedTicket.requester_type === "tenant" ? "purple" : "info"}
                                                className="capitalize"
                                            >
                                                {selectedTicket.requester_type === "tenant" ? "Tienda Inquilina" : "Comprador Marketplace"}
                                            </Badge>
                                        </div>

                                        {/* Selector rápido de Estado */}
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs font-medium text-gray-500">Estado:</span>
                                            <select
                                                value={selectedTicket.status}
                                                onChange={(e) => handleQuickStatusChange(selectedTicket.id, e.target.value)}
                                                className="text-xs font-semibold bg-white border border-gray-300 text-gray-900 rounded-lg p-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            >
                                                <option value="open">Abierto</option>
                                                <option value="in_progress">En Progreso</option>
                                                <option value="waiting_reply">Esperando Respuesta</option>
                                                <option value="resolved">Resuelto</option>
                                                <option value="closed">Cerrado</option>
                                            </select>
                                        </div>
                                    </div>

                                    <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                        {selectedTicket.subject}
                                    </h3>
                                    <p className="text-xs text-gray-500 mt-1">
                                        Categoría: <span className="font-semibold uppercase">{selectedTicket.category}</span> • Prioridad: <span className="font-semibold uppercase">{selectedTicket.priority}</span>
                                    </p>
                                </div>

                                {/* Hilo de Mensajes */}
                                <div className="flex-1 p-4 overflow-y-auto space-y-4 bg-gray-50/50 dark:bg-gray-900/30">
                                    {/* Mensaje original / Apertura */}
                                    <div className="flex gap-3">
                                        <div className="flex-shrink-0 w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold text-xs">
                                            {selectedTicket.requester_type === "tenant" ? "T" : "C"}
                                        </div>
                                        <div className="flex-1 bg-white dark:bg-gray-800 p-3.5 rounded-lg rounded-tl-none border border-gray-200 dark:border-gray-700 shadow-sm">
                                            <div className="flex items-center justify-between mb-1">
                                                <span className="text-xs font-bold text-gray-900 dark:text-white">
                                                    {selectedTicket.requester_type === "tenant" ? "Comercio Inquilino" : "Cliente Comprador"}
                                                </span>
                                                <span className="text-[10px] text-gray-400">
                                                    {new Date(selectedTicket.created_at).toLocaleString()}
                                                </span>
                                            </div>
                                            <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                                                {selectedTicket.description}
                                            </p>

                                            {/* Adjuntos del ticket inicial */}
                                            {selectedTicket.attachments && selectedTicket.attachments.length > 0 && (
                                                <div className="mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                                                    <p className="text-[11px] font-semibold text-gray-500 mb-1.5 flex items-center gap-1">
                                                        <HiPaperClip /> Archivos Adjuntos ({selectedTicket.attachments.length}):
                                                    </p>
                                                    <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                        {selectedTicket.attachments.map((att, idx) => (
                                                            <div
                                                                key={idx}
                                                                className="relative group border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-900 p-1"
                                                            >
                                                                {att.type === "image" ? (
                                                                    <div
                                                                        className="cursor-pointer"
                                                                        onClick={() => setPreviewImage(att.url)}
                                                                    >
                                                                        <img src={att.url} alt={att.original_name} className="h-20 w-full object-cover rounded" />
                                                                        <div className="text-[10px] text-gray-600 dark:text-gray-400 truncate mt-1">
                                                                            {att.original_name}
                                                                        </div>
                                                                    </div>
                                                                ) : att.type === "video" ? (
                                                                    <div
                                                                        className="cursor-pointer"
                                                                        onClick={() => setPreviewVideo(att.url)}
                                                                    >
                                                                        <div className="h-20 bg-gray-800 rounded flex flex-col items-center justify-center text-white">
                                                                            <HiPlay className="text-2xl text-blue-400" />
                                                                            <span className="text-[9px] mt-0.5">Ver Video</span>
                                                                        </div>
                                                                        <div className="text-[10px] text-gray-600 dark:text-gray-400 truncate mt-1">
                                                                            {att.original_name}
                                                                        </div>
                                                                    </div>
                                                                ) : (
                                                                    <a
                                                                        href={att.url}
                                                                        target="_blank"
                                                                        rel="noopener noreferrer"
                                                                        className="flex items-center gap-1.5 p-2 text-xs text-blue-600 hover:underline"
                                                                    >
                                                                        <HiDocumentDownload className="text-lg" />
                                                                        <span className="truncate">{att.original_name}</span>
                                                                    </a>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Mensajes del Hilo */}
                                    {selectedTicket.messages && selectedTicket.messages.map((msg) => {
                                        const isAdmin = msg.sender_type === "admin";
                                        return (
                                            <div key={msg.id} className={`flex gap-3 ${isAdmin ? "flex-row-reverse" : ""}`}>
                                                <div className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs ${
                                                    isAdmin ? "bg-blue-600 text-white" : "bg-gray-200 dark:bg-gray-700 text-gray-600"
                                                }`}>
                                                    {isAdmin ? "A" : "U"}
                                                </div>
                                                <div className={`flex-1 max-w-[85%] p-3.5 rounded-lg border shadow-sm ${
                                                    isAdmin
                                                        ? "bg-blue-600 text-white border-blue-700 rounded-tr-none"
                                                        : "bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 rounded-tl-none"
                                                }`}>
                                                    <div className="flex items-center justify-between mb-1">
                                                        <span className="text-xs font-bold">
                                                            {msg.sender_name || (isAdmin ? "Soporte OwOMarket" : "Usuario")}
                                                        </span>
                                                        <span className={`text-[10px] ${isAdmin ? "text-blue-200" : "text-gray-400"}`}>
                                                            {new Date(msg.created_at).toLocaleString()}
                                                        </span>
                                                    </div>
                                                    <p className="text-sm whitespace-pre-line">{msg.message}</p>

                                                    {/* Adjuntos del mensaje */}
                                                    {msg.attachments && msg.attachments.length > 0 && (
                                                        <div className="mt-2 pt-2 border-t border-white/20 dark:border-gray-700">
                                                            <div className="grid grid-cols-2 gap-2">
                                                                {msg.attachments.map((att, idx) => (
                                                                    <div key={idx} className="cursor-pointer" onClick={() => att.type === "image" ? setPreviewImage(att.url) : att.type === "video" ? setPreviewVideo(att.url) : null}>
                                                                        {att.type === "image" ? (
                                                                            <img src={att.url} alt="Adjunto" className="h-16 w-full object-cover rounded border border-white/30" />
                                                                        ) : att.type === "video" ? (
                                                                            <div className="h-16 bg-black/40 rounded flex items-center justify-center text-white">
                                                                                <HiPlay className="text-xl" />
                                                                            </div>
                                                                        ) : (
                                                                            <a href={att.url} target="_blank" rel="noopener noreferrer" className="text-xs underline text-white">
                                                                                Descargar {att.original_name}
                                                                            </a>
                                                                        )}
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                    <div ref={messagesEndRef} />
                                </div>

                                {/* Formulario de Respuesta Administrativa */}
                                <div className="p-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                                    {/* Plantillas Rápidas (Canned Responses) */}
                                    <div className="flex items-center gap-1.5 overflow-x-auto pb-2 mb-2 text-xs">
                                        <span className="text-gray-400 text-[10px] font-semibold uppercase whitespace-nowrap">Plantillas:</span>
                                        <button
                                            type="button"
                                            onClick={() => handleApplyCannedResponse("Hola, estamos verificando la información suministrada con nuestro departamento técnico y te daremos respuesta a la brevedad posible.", "in_progress")}
                                            className="px-2 py-0.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 rounded text-[11px] text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                        >
                                            🔍 En Verificación
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => handleApplyCannedResponse("Hemos procesado tu requerimiento satisfactoriamente. Por favor confirma si todo funciona correctamente.", "waiting_reply")}
                                            className="px-2 py-0.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 rounded text-[11px] text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                        >
                                            ✅ Solución Aplicada
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => handleApplyCannedResponse("El caso ha sido resuelto y liquidado. Damos por cerrado este ticket de soporte. ¡Gracias por confiar en OwOMarket!", "resolved")}
                                            className="px-2 py-0.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 rounded text-[11px] text-gray-700 dark:text-gray-300 whitespace-nowrap"
                                        >
                                            🎉 Caso Resuelto
                                        </button>
                                    </div>

                                    <form onSubmit={handleSendReply}>
                                        {replyFeedback && (
                                            <div
                                                role={replyFeedback.type === 'error' ? 'alert' : 'status'}
                                                className={`mb-2 p-2 rounded-lg text-[11px] font-bold border ${
                                                    replyFeedback.type === 'error'
                                                        ? 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800'
                                                        : 'bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800'
                                                }`}
                                            >
                                                {replyFeedback.text}
                                            </div>
                                        )}
                                        <textarea
                                            rows={2}
                                            value={replyMessage}
                                            onChange={(e) => setReplyMessage(e.target.value)}
                                            placeholder="Escribe una respuesta como Super Admin..."
                                            className="w-full text-sm bg-gray-50 border border-gray-300 text-gray-900 rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            required={replyFiles.length === 0}
                                        />

                                        <div className="flex flex-wrap items-center justify-between gap-2 mt-2">
                                            <div className="flex items-center gap-2">
                                                {/* Botón de Adjuntar Fotos/Videos */}
                                                <label className="cursor-pointer flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400 hover:text-blue-600 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">
                                                    <HiPaperClip className="text-sm" />
                                                    <span>Adjuntar ({replyFiles.length})</span>
                                                    <input
                                                        type="file"
                                                        multiple
                                                        accept="image/*,video/*"
                                                        className="hidden"
                                                        onChange={(e) => {
                                                            if (e.target.files) {
                                                                setReplyFiles(Array.from(e.target.files));
                                                            }
                                                        }}
                                                    />
                                                </label>

                                                {/* Selector de nuevo estado con la respuesta */}
                                                <select
                                                    value={newStatus}
                                                    onChange={(e) => setNewStatus(e.target.value)}
                                                    className="text-xs bg-gray-50 border border-gray-300 rounded p-1 dark:bg-gray-700 dark:border-gray-600 text-gray-700 dark:text-gray-300"
                                                >
                                                    <option value="">Mantener estado actual</option>
                                                    <option value="in_progress">Cambiar a: En Progreso</option>
                                                    <option value="waiting_reply">Cambiar a: Esperando Respuesta</option>
                                                    <option value="resolved">Cambiar a: Resuelto</option>
                                                    <option value="closed">Cambiar a: Cerrado</option>
                                                </select>
                                            </div>

                                            <Button type="submit" size="xs" color="blue" disabled={submittingReply}>
                                                {submittingReply ? <Spinner size="xs" className="mr-1" /> : <HiPaperAirplane className="mr-1" />}
                                                Enviar Respuesta
                                            </Button>
                                        </div>
                                    </form>
                                </div>
                            </Card>
                        ) : (
                            <Card className="text-center p-12 text-gray-500">
                                Selecciona un ticket de la lista para ver su conversación y resolverlo.
                            </Card>
                        )}
                    </div>
                </div>

                {/* MODAL VISOR DE IMAGEN CON ZOOM */}
                <Modal show={Boolean(previewImage)} size="4xl" onClose={() => setPreviewImage(null)}>
                    <ModalHeader>Visor de Evidencia / Imagen</ModalHeader>
                    <ModalBody className="p-2 flex items-center justify-center bg-black/90">
                        {previewImage && (
                            <img src={previewImage} alt="Evidencia" className="max-h-[75vh] w-auto object-contain rounded" />
                        )}
                    </ModalBody>
                </Modal>

                {/* MODAL REPRODUCTOR DE VIDEO HTML5 */}
                <Modal show={Boolean(previewVideo)} size="4xl" onClose={() => setPreviewVideo(null)}>
                    <ModalHeader>Reproductor de Video</ModalHeader>
                    <ModalBody className="p-2 flex items-center justify-center bg-black">
                        {previewVideo && (
                            <video controls autoPlay className="max-h-[75vh] w-full rounded">
                                <source src={previewVideo} />
                                Tu navegador no soporta reproducción de video.
                            </video>
                        )}
                    </ModalBody>
                </Modal>
            </Dashboard>
        </>
    );
};

export default AdminSupportTicketsPage;
