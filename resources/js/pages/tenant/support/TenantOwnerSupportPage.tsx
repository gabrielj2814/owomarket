import React, { useState, useRef } from 'react';
import { Head } from '@inertiajs/react';
import Dashboard from '@/components/layouts/Dashboard';
import TenantOwnerNavTabs from '@/components/tenant/TenantOwnerNavTabs';
import {
    HiOutlineChatBubbleLeftRight,
    HiOutlinePlus,
    HiOutlineExclamationTriangle,
    HiOutlineCheckCircle,
    HiOutlineClock,
    HiOutlinePaperClip,
    HiOutlinePhoto,
    HiOutlineVideoCamera,
    HiOutlineXMark,
    HiOutlineArrowPath,
    HiOutlineBuildingStorefront,
    HiOutlineTag,
    HiOutlinePaperAirplane,
    HiOutlineEye,
} from 'react-icons/hi2';
import axios from 'axios';

interface AttachmentItem {
    url: string;
    type: 'image' | 'video' | 'file';
    original_name: string;
    size_bytes: number;
    mime_type: string;
}

interface TicketMessage {
    id: string;
    ticket_id: string;
    sender_type: string;
    sender_name: string;
    message: string;
    attachments: AttachmentItem[] | null;
    created_at: string;
}

interface SupportTicket {
    id: string;
    ticket_number: string;
    category: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    status: 'open' | 'in_progress' | 'waiting_reply' | 'resolved' | 'closed';
    subject: string;
    description: string;
    tenant_id: string | null;
    attachments: AttachmentItem[] | null;
    created_at: string;
    updated_at: string;
    messages?: TicketMessage[];
}

interface TenantOwnerSupportPageProps {
    title?: string;
    user_id: string;
    tickets_data: {
        tickets: SupportTicket[];
        counts: {
            total: number;
            open: number;
            in_progress: number;
            waiting_reply: number;
            resolved: number;
        };
        pagination: any;
    };
    tenants: Array<{ id: string; name: string; slug: string }>;
}

export const TenantOwnerSupportPage: React.FC<TenantOwnerSupportPageProps> = ({
    user_id,
    tickets_data: initialData,
    tenants,
}) => {
    const [tickets, setTickets] = useState<SupportTicket[]>(initialData.tickets);
    const [counts, setCounts] = useState(initialData.counts);
    const [selectedStatus, setSelectedStatus] = useState<string>('');
    const [selectedTicket, setSelectedTicket] = useState<SupportTicket | null>(null);

    // Modal state for creating new ticket
    const [isCreateModalOpen, setIsCreateModalOpen] = useState<boolean>(false);
    const [subject, setSubject] = useState<string>('');
    const [description, setDescription] = useState<string>('');
    const [category, setCategory] = useState<string>('technical_error');
    const [priority, setPriority] = useState<'low' | 'medium' | 'high' | 'urgent'>('medium');
    const [selectedTenantId, setSelectedTenantId] = useState<string>('');
    const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
    const [filePreviews, setFilePreviews] = useState<Array<{ url: string; type: 'image' | 'video'; name: string }>>([]);

    // Reply state
    const [replyText, setReplyText] = useState<string>('');
    const [replyFiles, setReplyFiles] = useState<File[]>([]);
    const [replyFilePreviews, setReplyFilePreviews] = useState<Array<{ url: string; type: 'image' | 'video'; name: string }>>([]);

    // Loading & preview modal
    const [loading, setLoading] = useState<boolean>(false);
    const [previewMediaUrl, setPreviewMediaUrl] = useState<{ url: string; type: 'image' | 'video' } | null>(null);
    const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const fileInputRef = useRef<HTMLInputElement>(null);
    const replyFileInputRef = useRef<HTMLInputElement>(null);

    // Manejar selección de archivos (fotos y videos)
    const handleFileSelection = (e: React.ChangeEvent<HTMLInputElement>, isReply: boolean = false) => {
        if (!e.target.files) return;
        const filesArray = Array.from(e.target.files);

        const newPreviews: Array<{ url: string; type: 'image' | 'video'; name: string }> = [];

        filesArray.forEach(file => {
            const isVideo = file.type.startsWith('video/') || file.name.match(/\.(mp4|webm|mov|avi|mkv)$/i);
            const objectUrl = URL.createObjectURL(file);
            newPreviews.push({
                url: objectUrl,
                type: isVideo ? 'video' : 'image',
                name: file.name,
            });
        });

        if (isReply) {
            setReplyFiles(prev => [...prev, ...filesArray]);
            setReplyFilePreviews(prev => [...prev, ...newPreviews]);
        } else {
            setSelectedFiles(prev => [...prev, ...filesArray]);
            setFilePreviews(prev => [...prev, ...newPreviews]);
        }
    };

    const removeFile = (index: number, isReply: boolean = false) => {
        if (isReply) {
            setReplyFiles(prev => prev.filter((_, i) => i !== index));
            setReplyFilePreviews(prev => prev.filter((_, i) => i !== index));
        } else {
            setSelectedFiles(prev => prev.filter((_, i) => i !== index));
            setFilePreviews(prev => prev.filter((_, i) => i !== index));
        }
    };

    const handleCreateTicket = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setFeedback(null);

        const formData = new FormData();
        formData.append('user_id', user_id);
        formData.append('requester_type', 'tenant_owner');
        formData.append('subject', subject);
        formData.append('description', description);
        formData.append('category', category);
        formData.append('priority', priority);
        if (selectedTenantId) formData.append('tenant_id', selectedTenantId);

        selectedFiles.forEach((file) => {
            formData.append('files[]', file);
        });

        try {
            const response = await axios.post('/tenant/api/support/tickets', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (response.data?.status === 'success') {
                const newTicket = response.data.data;
                setTickets(prev => [newTicket, ...prev]);
                setCounts(prev => ({ ...prev, total: prev.total + 1, open: prev.open + 1 }));
                setIsCreateModalOpen(false);
                setSubject('');
                setDescription('');
                setSelectedFiles([]);
                setFilePreviews([]);
                setFeedback({ type: 'success', text: `¡Ticket ${newTicket.ticket_number} creado con éxito! Nuestro equipo lo revisará a la brevedad.` });
            }
        } catch (err: any) {
            setFeedback({ type: 'error', text: err?.response?.data?.message || 'Error al generar el ticket.' });
        } finally {
            setLoading(false);
        }
    };

    const handleSendReply = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedTicket || !replyText.trim()) return;

        setLoading(true);
        const formData = new FormData();
        formData.append('user_id', user_id);
        formData.append('sender_type', 'tenant_owner');
        formData.append('message', replyText);

        replyFiles.forEach((file) => {
            formData.append('files[]', file);
        });

        try {
            const response = await axios.post(`/tenant/api/support/tickets/${selectedTicket.id}/messages`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            if (response.data?.status === 'success') {
                const newMsg = response.data.data;
                setSelectedTicket(prev => prev ? {
                    ...prev,
                    status: 'in_progress',
                    messages: [...(prev.messages || []), newMsg],
                } : null);

                setReplyText('');
                setReplyFiles([]);
                setReplyFilePreviews([]);
            }
        } catch (err: any) {
            setFeedback({ type: 'error', text: err?.response?.data?.message || 'No se pudo enviar la respuesta.' });
        } finally {
            setLoading(false);
        }
    };

    const openTicketDetails = async (ticket: SupportTicket) => {
        try {
            const res = await axios.get(`/tenant/api/support/tickets/${ticket.id}?user_id=${user_id}`);
            if (res.data?.status === 'success') {
                setSelectedTicket(res.data.data);
            }
        } catch (error) {
            setSelectedTicket(ticket);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title="Centro de Soporte & Reporte de Incidencias - OwOMarket" />

            <div className="p-4 sm:p-6 space-y-6">
                <TenantOwnerNavTabs userId={user_id} activeTab="support" />

                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h1 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <HiOutlineChatBubbleLeftRight className="w-7 h-7 text-blue-600 dark:text-blue-400" />
                            Centro de Soporte & Reporte de Errores
                        </h1>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Comunícate directamente con nuestro equipo de ingeniería y soporte. Puedes adjuntar capturas de pantalla y videos demostrativos.
                        </p>
                    </div>

                    <button
                        onClick={() => setIsCreateModalOpen(true)}
                        className="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2"
                    >
                        <HiOutlinePlus className="w-4 h-4" />
                        <span>Crear Nuevo Ticket / Reportar Error</span>
                    </button>
                </div>

                {/* Feedback Toast */}
                {feedback && (
                    <div className={`p-4 rounded-2xl text-xs font-semibold flex items-center gap-2 ${
                        feedback.type === 'success'
                            ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                            : 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800'
                    }`}>
                        <span>{feedback.text}</span>
                    </div>
                )}

                {/* KPI Metrics */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span className="text-[11px] font-bold uppercase text-gray-400">Total Reportes</span>
                        <div className="text-2xl font-black text-gray-900 dark:text-white mt-1">{counts.total}</div>
                    </div>
                    <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span className="text-[11px] font-bold uppercase text-amber-500">Abiertos / Nuevos</span>
                        <div className="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{counts.open}</div>
                    </div>
                    <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span className="text-[11px] font-bold uppercase text-blue-500">En Revisión</span>
                        <div className="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{counts.in_progress}</div>
                    </div>
                    <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span className="text-[11px] font-bold uppercase text-emerald-500">Resueltos</span>
                        <div className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{counts.resolved}</div>
                    </div>
                </div>

                {/* Main Content: Tickets List & Thread View */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Tickets List */}
                    <div className={`${selectedTicket ? 'lg:col-span-5' : 'lg:col-span-12'} space-y-4`}>
                        <div className="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm space-y-3">
                            <h3 className="text-xs font-black uppercase text-gray-400 tracking-wider">
                                Tus Tickets de Incidencias ({tickets.length})
                            </h3>

                            {tickets.length === 0 ? (
                                <div className="text-center py-12 text-gray-400 text-xs">
                                    No tienes tickets registrados actualmente. Si experimentas algún fallo, ¡crea uno con el botón superior!
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {tickets.map(ticket => {
                                        const isSelected = selectedTicket?.id === ticket.id;
                                        return (
                                            <div
                                                key={ticket.id}
                                                onClick={() => openTicketDetails(ticket)}
                                                className={`p-4 rounded-2xl border cursor-pointer transition ${
                                                    isSelected
                                                        ? 'border-blue-600 bg-blue-50/40 dark:bg-blue-950/40 shadow-sm'
                                                        : 'border-gray-200 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-750/30 hover:border-gray-300'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="text-[11px] font-mono font-bold text-blue-600 dark:text-blue-400">
                                                        {ticket.ticket_number}
                                                    </span>
                                                    <span className={`px-2 py-0.5 rounded-full text-[10px] font-black uppercase ${
                                                        ticket.status === 'open' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' :
                                                        ticket.status === 'in_progress' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' :
                                                        ticket.status === 'resolved' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' :
                                                        'bg-gray-100 text-gray-600'
                                                    }`}>
                                                        {ticket.status === 'open' ? 'Abierto' : ticket.status === 'in_progress' ? 'En Revisión' : ticket.status === 'resolved' ? 'Resuelto' : ticket.status}
                                                    </span>
                                                </div>

                                                <h4 className="font-bold text-xs text-gray-900 dark:text-white mt-1 line-clamp-1">
                                                    {ticket.subject}
                                                </h4>

                                                <p className="text-[11px] text-gray-500 line-clamp-2 mt-0.5">
                                                    {ticket.description}
                                                </p>

                                                <div className="flex items-center justify-between mt-3 pt-2 border-t border-gray-100 dark:border-gray-700/60 text-[10px] text-gray-400">
                                                    <span>Prioridad: <strong className="uppercase">{ticket.priority}</strong></span>
                                                    {ticket.attachments && ticket.attachments.length > 0 && (
                                                        <span className="flex items-center gap-1 text-blue-600 dark:text-blue-400 font-bold">
                                                            <HiOutlinePaperClip className="w-3.5 h-3.5" />
                                                            {ticket.attachments.length} adjuntos
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Ticket Thread & Chat View */}
                    {selectedTicket && (
                        <div className="lg:col-span-7 space-y-4">
                            <div className="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-6 flex flex-col h-[750px]">
                                {/* Thread Header */}
                                <div className="flex items-start justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs font-mono font-bold text-blue-600">{selectedTicket.ticket_number}</span>
                                            <span className="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-700">
                                                {selectedTicket.category}
                                            </span>
                                        </div>
                                        <h3 className="text-base font-black text-gray-900 dark:text-white mt-1">
                                            {selectedTicket.subject}
                                        </h3>
                                    </div>
                                    <button
                                        onClick={() => setSelectedTicket(null)}
                                        className="text-gray-400 hover:text-gray-600 p-1"
                                    >
                                        <HiOutlineXMark className="w-5 h-5" />
                                    </button>
                                </div>

                                {/* Messages Scroll Area */}
                                <div className="flex-1 overflow-y-auto space-y-4 pr-2">
                                    {(selectedTicket.messages || []).map((msg) => {
                                        const isFromUser = msg.sender_type === 'tenant_owner' || msg.sender_type === 'customer';

                                        return (
                                            <div
                                                key={msg.id}
                                                className={`flex flex-col ${isFromUser ? 'items-end' : 'items-start'} space-y-1.5`}
                                            >
                                                <div className="flex items-center gap-2 text-[10px] text-gray-400 font-semibold px-1">
                                                    <span>{isFromUser ? 'Tú' : '👨‍💻 Soporte Técnico OwOMarket'}</span>
                                                    <span>• {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                                </div>

                                                <div className={`p-4 rounded-2xl max-w-lg text-xs leading-relaxed ${
                                                    isFromUser
                                                        ? 'bg-blue-600 text-white rounded-br-none shadow-md shadow-blue-500/10'
                                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-bl-none'
                                                }`}>
                                                    <p className="whitespace-pre-wrap">{msg.message}</p>

                                                    {/* Multimedia Attachments in Chat */}
                                                    {msg.attachments && msg.attachments.length > 0 && (
                                                        <div className="mt-3 pt-3 border-t border-white/20 dark:border-gray-600 grid grid-cols-2 gap-2">
                                                            {msg.attachments.map((att, idx) => (
                                                                <div
                                                                    key={idx}
                                                                    onClick={() => setPreviewMediaUrl({ url: att.url, type: att.type as any })}
                                                                    className="relative group rounded-xl overflow-hidden cursor-pointer border border-white/20 dark:border-gray-600 bg-black/10 aspect-video flex items-center justify-center"
                                                                >
                                                                    {att.type === 'video' ? (
                                                                        <div className="flex flex-col items-center gap-1 text-white">
                                                                            <HiOutlineVideoCamera className="w-6 h-6 text-yellow-300" />
                                                                            <span className="text-[9px] font-bold">Ver Video</span>
                                                                        </div>
                                                                    ) : (
                                                                        <img src={att.url} alt={att.original_name} className="w-full h-full object-cover group-hover:scale-105 transition" />
                                                                    )}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                {/* Reply Input Area */}
                                <form onSubmit={handleSendReply} className="pt-3 border-t border-gray-100 dark:border-gray-700 space-y-3">
                                    {/* Preview of attached files in reply */}
                                    {replyFilePreviews.length > 0 && (
                                        <div className="flex items-center gap-2 overflow-x-auto pb-2">
                                            {replyFilePreviews.map((f, i) => (
                                                <div key={i} className="relative w-16 h-16 rounded-xl border border-gray-200 overflow-hidden bg-gray-50 shrink-0">
                                                    {f.type === 'video' ? (
                                                        <div className="w-full h-full flex items-center justify-center bg-gray-900 text-yellow-400">
                                                            <HiOutlineVideoCamera className="w-5 h-5" />
                                                        </div>
                                                    ) : (
                                                        <img src={f.url} alt="prev" className="w-full h-full object-cover" />
                                                    )}
                                                    <button
                                                        type="button"
                                                        onClick={() => removeFile(i, true)}
                                                        className="absolute top-1 right-1 bg-black/60 text-white rounded-full p-0.5"
                                                    >
                                                        <HiOutlineXMark className="w-3 h-3" />
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    <div className="flex items-center gap-2">
                                        <input
                                            type="file"
                                            ref={replyFileInputRef}
                                            multiple
                                            accept="image/*,video/*"
                                            className="hidden"
                                            onChange={(e) => handleFileSelection(e, true)}
                                        />

                                        <button
                                            type="button"
                                            onClick={() => replyFileInputRef.current?.click()}
                                            className="p-3 rounded-2xl border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-blue-600 transition"
                                            title="Adjuntar fotos o videos"
                                        >
                                            <HiOutlinePaperClip className="w-5 h-5" />
                                        </button>

                                        <input
                                            type="text"
                                            placeholder="Escribe una respuesta o aclaratoria..."
                                            value={replyText}
                                            onChange={(e) => setReplyText(e.target.value)}
                                            className="flex-1 p-3 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs text-gray-900 dark:text-white"
                                        />

                                        <button
                                            type="submit"
                                            disabled={loading || !replyText.trim()}
                                            className="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-blue-500/20"
                                        >
                                            <HiOutlinePaperAirplane className="w-4 h-4" />
                                            <span>Enviar</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal: Crear Nuevo Ticket con Adjuntos Multimedia */}
            {isCreateModalOpen && (
                <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white dark:bg-gray-800 rounded-3xl max-w-xl w-full p-6 space-y-5 shadow-2xl border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                            <h3 className="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                                <HiOutlineExclamationTriangle className="w-5 h-5 text-amber-500" />
                                Reportar Error o Solicitar Soporte
                            </h3>
                            <button onClick={() => setIsCreateModalOpen(false)} className="text-gray-400 hover:text-gray-600">
                                <HiOutlineXMark className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleCreateTicket} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Título o Asunto del Reporte *
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ej: Error al sincronizar stock o fallo en pasarela"
                                    value={subject}
                                    onChange={(e) => setSubject(e.target.value)}
                                    className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs font-medium text-gray-900 dark:text-white"
                                    required
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Categoría de Incidencia
                                    </label>
                                    <select
                                        value={category}
                                        onChange={(e) => setCategory(e.target.value)}
                                        className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs text-gray-900 dark:text-white font-medium"
                                    >
                                        <option value="technical_error">Fallo Técnico en Backoffice / Tienda</option>
                                        <option value="billing_payout">Liquidación / Billetera / Facturación</option>
                                        <option value="marketplace_catalog">Publicador de Catálogo Central</option>
                                        <option value="account_access">Acceso a Cuenta / Credenciales</option>
                                        <option value="feature_request">Sugerencia de Nueva Funcionalidad</option>
                                        <option value="other">Otra Consulta</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Prioridad
                                    </label>
                                    <select
                                        value={priority}
                                        onChange={(e) => setPriority(e.target.value as any)}
                                        className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs text-gray-900 dark:text-white font-medium"
                                    >
                                        <option value="low">Baja (Consulta general)</option>
                                        <option value="medium">Media (Fallo menor)</option>
                                        <option value="high">Alta (Impacto en ventas)</option>
                                        <option value="urgent">Urgente (Tienda no accesible)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Tienda Afectada (Opcional)
                                </label>
                                <select
                                    value={selectedTenantId}
                                    onChange={(e) => setSelectedTenantId(e.target.value)}
                                    className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs text-gray-900 dark:text-white font-medium"
                                >
                                    <option value="">Aplica a todas / General</option>
                                    {tenants.map(t => (
                                        <option key={t.id} value={t.id}>{t.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Descripción Detallada del Error *
                                </label>
                                <textarea
                                    rows={4}
                                    placeholder="Describe qué estabas haciendo, qué mensaje de error apareció y los pasos para reproducirlo..."
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    className="w-full p-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-xs text-gray-900 dark:text-white"
                                    required
                                />
                            </div>

                            {/* Multimedia File Upload Dropzone */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1 flex items-center justify-between">
                                    <span>Adjuntar Fotos o Videos de Evidencia</span>
                                    <span className="text-[10px] text-gray-400 font-normal">PNG, JPG, WEBP, MP4, MOV (Máx 50MB)</span>
                                </label>

                                <input
                                    type="file"
                                    ref={fileInputRef}
                                    multiple
                                    accept="image/*,video/*"
                                    className="hidden"
                                    onChange={(e) => handleFileSelection(e, false)}
                                />

                                <div
                                    onClick={() => fileInputRef.current?.click()}
                                    className="border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-blue-500 rounded-2xl p-4 text-center cursor-pointer transition bg-gray-50/50 dark:bg-gray-900/50"
                                >
                                    <div className="flex justify-center gap-3 text-gray-400 mb-2">
                                        <HiOutlinePhoto className="w-6 h-6 text-blue-500" />
                                        <HiOutlineVideoCamera className="w-6 h-6 text-purple-500" />
                                    </div>
                                    <p className="text-xs font-bold text-gray-700 dark:text-gray-300">
                                        Haz clic para seleccionar imágenes o videos
                                    </p>
                                    <p className="text-[10px] text-gray-400 mt-0.5">
                                        Puedes adjuntar capturas de pantalla del error o grabaciones de pantalla
                                    </p>
                                </div>

                                {/* Previews Grid */}
                                {filePreviews.length > 0 && (
                                    <div className="grid grid-cols-3 sm:grid-cols-4 gap-2 mt-3">
                                        {filePreviews.map((preview, index) => (
                                            <div key={index} className="relative aspect-video rounded-xl overflow-hidden border border-gray-200 bg-gray-900 group">
                                                {preview.type === 'video' ? (
                                                    <div className="w-full h-full flex flex-col items-center justify-center text-yellow-400">
                                                        <HiOutlineVideoCamera className="w-6 h-6" />
                                                        <span className="text-[9px] font-bold text-white mt-1">Video</span>
                                                    </div>
                                                ) : (
                                                    <img src={preview.url} alt={preview.name} className="w-full h-full object-cover" />
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => removeFile(index, false)}
                                                    className="absolute top-1 right-1 bg-red-600 hover:bg-red-700 text-white rounded-full p-1 transition shadow"
                                                >
                                                    <HiOutlineXMark className="w-3 h-3" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            <div className="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <button
                                    type="button"
                                    onClick={() => setIsCreateModalOpen(false)}
                                    className="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-2"
                                >
                                    {loading && <HiOutlineArrowPath className="w-4 h-4 animate-spin" />}
                                    <span>Generar Ticket de Soporte</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Modal: Visor Multimedia de Imágenes y Videos */}
            {previewMediaUrl && (
                <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                    <div className="relative max-w-4xl w-full bg-black rounded-3xl overflow-hidden shadow-2xl">
                        <button
                            onClick={() => setPreviewMediaUrl(null)}
                            className="absolute top-4 right-4 z-10 bg-white/20 hover:bg-white/40 text-white rounded-full p-2 backdrop-blur transition"
                        >
                            <HiOutlineXMark className="w-6 h-6" />
                        </button>

                        <div className="p-4 flex items-center justify-center min-h-[300px] max-h-[80vh]">
                            {previewMediaUrl.type === 'video' ? (
                                <video
                                    src={previewMediaUrl.url}
                                    controls
                                    autoPlay
                                    className="w-full max-h-[70vh] rounded-2xl"
                                />
                            ) : (
                                <img
                                    src={previewMediaUrl.url}
                                    alt="Vista previa"
                                    className="max-w-full max-h-[70vh] object-contain rounded-2xl"
                                />
                            )}
                        </div>
                    </div>
                </div>
            )}
        </Dashboard>
    );
};

export default TenantOwnerSupportPage;
