import React, { useState, useRef } from 'react';
import { Head } from '@inertiajs/react';
import Dashboard from '@/components/layouts/Dashboard';
import {
    HiOutlineChatBubbleLeftRight,
    HiOutlinePlus,
    HiOutlineExclamationTriangle,
    HiOutlinePaperClip,
    HiOutlinePhoto,
    HiOutlineVideoCamera,
    HiOutlineXMark,
    HiOutlineArrowPath,
    HiOutlineBuildingStorefront,
    HiOutlinePaperAirplane,
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

interface TenantStoreSupportPageProps {
    title?: string;
    user_id: string;
    tenant_id: string | null;
    store_name: string;
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
}

export const TenantStoreSupportPage: React.FC<TenantStoreSupportPageProps> = ({
    user_id,
    tenant_id,
    store_name,
    tickets_data: initialData,
}) => {
    const [tickets, setTickets] = useState<SupportTicket[]>(initialData.tickets);
    const [counts, setCounts] = useState(initialData.counts);
    const [selectedTicket, setSelectedTicket] = useState<SupportTicket | null>(null);

    // Modal state
    const [isCreateModalOpen, setIsCreateModalOpen] = useState<boolean>(false);
    const [subject, setSubject] = useState<string>('');
    const [description, setDescription] = useState<string>('');
    const [category, setCategory] = useState<string>('technical_error');
    const [priority, setPriority] = useState<'low' | 'medium' | 'high' | 'urgent'>('medium');
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
        formData.append('subject', subject);
        formData.append('description', description);
        formData.append('category', category);
        formData.append('priority', priority);
        if (tenant_id) formData.append('tenant_id', tenant_id);

        selectedFiles.forEach((file) => {
            formData.append('files[]', file);
        });

        try {
            const response = await axios.post('/support/api/tickets', formData, {
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
                setFeedback({ type: 'success', text: `¡Ticket ${newTicket.ticket_number} enviado con éxito! El equipo de soporte lo atenderá pronto.` });
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
        formData.append('message', replyText);

        replyFiles.forEach((file) => {
            formData.append('files[]', file);
        });

        try {
            const response = await axios.post(`/support/api/tickets/${selectedTicket.id}/messages`, formData, {
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
            const res = await axios.get(`/support/api/tickets/${ticket.id}?user_id=${user_id}`);
            if (res.data?.status === 'success') {
                setSelectedTicket(res.data.data);
            }
        } catch (error) {
            setSelectedTicket(ticket);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={`Soporte Técnico - ${store_name}`} />

            <div className="p-4 sm:p-6 space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-bold text-blue-600 mb-1">
                            <HiOutlineBuildingStorefront className="w-4 h-4" />
                            <span>{store_name}</span>
                        </div>
                        <h1 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <HiOutlineChatBubbleLeftRight className="w-7 h-7 text-blue-600 dark:text-blue-400" />
                            Mesa de Ayuda & Reporte de Incidencias
                        </h1>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Reporta cualquier incidencia técnica o duda con tu tienda. Puedes adjuntar capturas y videos de error.
                        </p>
                    </div>

                    <button
                        onClick={() => setIsCreateModalOpen(true)}
                        className="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2"
                    >
                        <HiOutlinePlus className="w-4 h-4" />
                        <span>Reportar Error / Crear Ticket</span>
                    </button>
                </div>

                {/* Feedback Toast */}
                {feedback && (
                    <div className={`p-4 rounded-2xl text-xs font-semibold ${
                        feedback.type === 'success'
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            : 'bg-red-50 text-red-700 border border-red-200'
                    }`}>
                        {feedback.text}
                    </div>
                )}

                {/* KPI Metrics */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span className="text-[11px] font-bold uppercase text-gray-400">Total Tickets</span>
                        <div className="text-2xl font-black text-gray-900 dark:text-white mt-1">{counts.total}</div>
                    </div>
                    <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span className="text-[11px] font-bold uppercase text-amber-500">Abiertos</span>
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

                {/* Main Content */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Tickets List */}
                    <div className={`${selectedTicket ? 'lg:col-span-5' : 'lg:col-span-12'} space-y-4`}>
                        <div className="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-200 dark:border-gray-700 shadow-sm space-y-3">
                            <h3 className="text-xs font-black uppercase text-gray-400 tracking-wider">
                                Historial de Incidencias de la Tienda ({tickets.length})
                            </h3>

                            {tickets.length === 0 ? (
                                <div className="text-center py-12 text-gray-400 text-xs">
                                    No hay incidencias reportadas en esta tienda.
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
                                                        ? 'border-blue-600 bg-blue-50/40 dark:bg-blue-950/40'
                                                        : 'border-gray-200 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-750/30'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="text-[11px] font-mono font-bold text-blue-600">
                                                        {ticket.ticket_number}
                                                    </span>
                                                    <span className="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-700">
                                                        {ticket.status === 'open' ? 'Abierto' : ticket.status === 'in_progress' ? 'En Revisión' : ticket.status === 'resolved' ? 'Resuelto' : ticket.status}
                                                    </span>
                                                </div>
                                                <h4 className="font-bold text-xs text-gray-900 dark:text-white mt-1 line-clamp-1">
                                                    {ticket.subject}
                                                </h4>
                                                <p className="text-[11px] text-gray-500 line-clamp-2 mt-0.5">
                                                    {ticket.description}
                                                </p>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Thread Drawer */}
                    {selectedTicket && (
                        <div className="lg:col-span-7 bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-6 flex flex-col h-[700px]">
                            <div className="flex items-start justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                                <div>
                                    <span className="text-xs font-mono font-bold text-blue-600">{selectedTicket.ticket_number}</span>
                                    <h3 className="text-base font-black text-gray-900 dark:text-white">{selectedTicket.subject}</h3>
                                </div>
                                <button onClick={() => setSelectedTicket(null)} className="text-gray-400 hover:text-gray-600">
                                    <HiOutlineXMark className="w-5 h-5" />
                                </button>
                            </div>

                            <div className="flex-1 overflow-y-auto space-y-4 pr-2">
                                {(selectedTicket.messages || []).map((msg) => {
                                    const isFromStore = msg.sender_type === 'tenant_owner' || msg.sender_type === 'admin';
                                    return (
                                        <div key={msg.id} className={`flex flex-col ${isFromStore ? 'items-end' : 'items-start'} space-y-1`}>
                                            <span className="text-[10px] text-gray-400 px-1">
                                                {isFromStore ? 'Tú (Tienda)' : '👨‍💻 Soporte Técnico OwOMarket'}
                                            </span>
                                            <div className={`p-4 rounded-2xl max-w-lg text-xs leading-relaxed ${
                                                isFromStore
                                                    ? 'bg-blue-600 text-white rounded-br-none'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-bl-none'
                                            }`}>
                                                <p className="whitespace-pre-wrap">{msg.message}</p>

                                                {msg.attachments && msg.attachments.length > 0 && (
                                                    <div className="mt-3 pt-3 border-t border-white/20 grid grid-cols-2 gap-2">
                                                        {msg.attachments.map((att, idx) => (
                                                            <div
                                                                key={idx}
                                                                onClick={() => setPreviewMediaUrl({ url: att.url, type: att.type as any })}
                                                                className="rounded-xl overflow-hidden cursor-pointer border border-white/20 aspect-video flex items-center justify-center bg-black/10"
                                                            >
                                                                {att.type === 'video' ? (
                                                                    <span className="text-[10px] font-bold text-yellow-300 flex items-center gap-1">
                                                                        <HiOutlineVideoCamera className="w-5 h-5" /> Ver Video
                                                                    </span>
                                                                ) : (
                                                                    <img src={att.url} alt="adjunto" className="w-full h-full object-cover" />
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

                            <form onSubmit={handleSendReply} className="pt-3 border-t border-gray-100 dark:border-gray-700 space-y-2">
                                {replyFilePreviews.length > 0 && (
                                    <div className="flex gap-2 pb-2 overflow-x-auto">
                                        {replyFilePreviews.map((f, i) => (
                                            <div key={i} className="relative w-14 h-14 rounded-xl border overflow-hidden shrink-0">
                                                {f.type === 'video' ? (
                                                    <div className="w-full h-full bg-gray-900 text-yellow-400 flex items-center justify-center">
                                                        <HiOutlineVideoCamera className="w-5 h-5" />
                                                    </div>
                                                ) : (
                                                    <img src={f.url} alt="prev" className="w-full h-full object-cover" />
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => removeFile(i, true)}
                                                    className="absolute top-0.5 right-0.5 bg-black/70 text-white rounded-full p-0.5"
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
                                        className="p-3 rounded-2xl border text-gray-500 hover:text-blue-600"
                                    >
                                        <HiOutlinePaperClip className="w-5 h-5" />
                                    </button>
                                    <input
                                        type="text"
                                        placeholder="Escribe una respuesta o aclaratoria..."
                                        value={replyText}
                                        onChange={(e) => setReplyText(e.target.value)}
                                        className="flex-1 p-3 rounded-2xl border text-xs bg-gray-50 dark:bg-gray-900"
                                    />
                                    <button
                                        type="submit"
                                        disabled={loading || !replyText.trim()}
                                        className="px-5 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold"
                                    >
                                        <HiOutlinePaperAirplane className="w-4 h-4" />
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Crear Ticket Tienda */}
            {isCreateModalOpen && (
                <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white dark:bg-gray-800 rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between pb-3 border-b">
                            <h3 className="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                                <HiOutlineExclamationTriangle className="w-5 h-5 text-amber-500" />
                                Reportar Incidencia en {store_name}
                            </h3>
                            <button onClick={() => setIsCreateModalOpen(false)} className="text-gray-400">
                                <HiOutlineXMark className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleCreateTicket} className="space-y-3">
                            <div>
                                <label className="block text-xs font-bold mb-1">Título del Reporte *</label>
                                <input
                                    type="text"
                                    placeholder="Ej: Error al calcular tasa de cambio en envíos"
                                    value={subject}
                                    onChange={(e) => setSubject(e.target.value)}
                                    className="w-full p-2.5 rounded-xl border text-xs bg-gray-50 dark:bg-gray-900"
                                    required
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-bold mb-1">Categoría</label>
                                    <select
                                        value={category}
                                        onChange={(e) => setCategory(e.target.value)}
                                        className="w-full p-2.5 rounded-xl border text-xs bg-gray-50 dark:bg-gray-900"
                                    >
                                        <option value="technical_error">Fallo Técnico en Backoffice</option>
                                        <option value="products_stock">Catálogo y Stock</option>
                                        <option value="billing_taxes">Facturación e Impuestos</option>
                                        <option value="shipping">Envíos y Logística</option>
                                        <option value="other">Otro</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-bold mb-1">Prioridad</label>
                                    <select
                                        value={priority}
                                        onChange={(e) => setPriority(e.target.value as any)}
                                        className="w-full p-2.5 rounded-xl border text-xs bg-gray-50 dark:bg-gray-900"
                                    >
                                        <option value="low">Baja</option>
                                        <option value="medium">Media</option>
                                        <option value="high">Alta</option>
                                        <option value="urgent">Urgente</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold mb-1">Descripción del Fallo *</label>
                                <textarea
                                    rows={4}
                                    placeholder="Describe qué ocurrió y cómo reproducir el error..."
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    className="w-full p-2.5 rounded-xl border text-xs bg-gray-50 dark:bg-gray-900"
                                    required
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold mb-1">Fotos o Videos de Evidencia</label>
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
                                    className="border-2 border-dashed rounded-xl p-3 text-center cursor-pointer bg-gray-50 dark:bg-gray-900"
                                >
                                    <p className="text-xs font-bold text-gray-600 dark:text-gray-300 flex items-center justify-center gap-1.5">
                                        <HiOutlinePhoto className="w-4 h-4 text-blue-500" />
                                        <span>Seleccionar fotos o videos</span>
                                    </p>
                                </div>

                                {filePreviews.length > 0 && (
                                    <div className="grid grid-cols-4 gap-2 mt-2">
                                        {filePreviews.map((preview, index) => (
                                            <div key={index} className="relative aspect-video rounded-lg overflow-hidden border bg-gray-900">
                                                {preview.type === 'video' ? (
                                                    <div className="w-full h-full flex items-center justify-center text-yellow-400 text-xs">
                                                        <HiOutlineVideoCamera className="w-5 h-5" />
                                                    </div>
                                                ) : (
                                                    <img src={preview.url} alt="prev" className="w-full h-full object-cover" />
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => removeFile(index, false)}
                                                    className="absolute top-0.5 right-0.5 bg-red-600 text-white rounded-full p-0.5"
                                                >
                                                    <HiOutlineXMark className="w-2.5 h-2.5" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t">
                                <button
                                    type="button"
                                    onClick={() => setIsCreateModalOpen(false)}
                                    className="px-4 py-2 text-xs font-bold text-gray-500"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={loading}
                                    className="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20"
                                >
                                    {loading ? 'Enviando...' : 'Generar Ticket'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Visor Multimedia Modal */}
            {previewMediaUrl && (
                <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
                    <div className="relative max-w-3xl w-full bg-black rounded-2xl overflow-hidden">
                        <button
                            onClick={() => setPreviewMediaUrl(null)}
                            className="absolute top-3 right-3 z-10 bg-white/20 text-white rounded-full p-1.5"
                        >
                            <HiOutlineXMark className="w-5 h-5" />
                        </button>
                        <div className="p-3 flex items-center justify-center">
                            {previewMediaUrl.type === 'video' ? (
                                <video src={previewMediaUrl.url} controls autoPlay className="max-h-[70vh] rounded-xl" />
                            ) : (
                                <img src={previewMediaUrl.url} alt="Vista previa" className="max-h-[70vh] object-contain rounded-xl" />
                            )}
                        </div>
                    </div>
                </div>
            )}
        </Dashboard>
    );
};

export default TenantStoreSupportPage;
