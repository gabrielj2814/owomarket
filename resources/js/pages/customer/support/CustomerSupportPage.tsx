import React, { useState, useRef } from 'react';
import { Head } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import {
    HiOutlineChatBubbleLeftRight,
    HiOutlinePlus,
    HiOutlineExclamationTriangle,
    HiOutlinePaperClip,
    HiOutlinePhoto,
    HiOutlineVideoCamera,
    HiOutlineXMark,
    HiOutlineArrowPath,
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
    attachments: AttachmentItem[] | null;
    created_at: string;
    updated_at: string;
    messages?: TicketMessage[];
}

interface CustomerSupportPageProps {
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
}

export const CustomerSupportPage: React.FC<CustomerSupportPageProps> = ({
    user_id,
    tickets_data: initialData,
}) => {
    const [tickets, setTickets] = useState<SupportTicket[]>(initialData.tickets);
    const [counts, setCounts] = useState(initialData.counts);
    const [selectedTicket, setSelectedTicket] = useState<SupportTicket | null>(null);

    // Modal state
    const [isCreateModalOpen, setIsCreateModalOpen] = useState<boolean>(false);
    const [subject, setSubject] = useState<string>('');
    const [description, setDescription] = useState<string>('');
    const [category, setCategory] = useState<string>('order_issue');
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
        formData.append('requester_type', 'customer');
        formData.append('subject', subject);
        formData.append('description', description);
        formData.append('category', category);
        formData.append('priority', 'medium');

        selectedFiles.forEach((file) => {
            formData.append('files[]', file);
        });

        try {
            const response = await axios.post('/api/support/tickets', formData, {
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
                setFeedback({ type: 'success', text: `¡Reporte ${newTicket.ticket_number} enviado exitosamente! Te responderemos pronto.` });
            }
        } catch (err: any) {
            setFeedback({ type: 'error', text: err?.response?.data?.message || 'Error al enviar el reporte.' });
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
        formData.append('sender_type', 'customer');
        formData.append('message', replyText);

        replyFiles.forEach((file) => {
            formData.append('files[]', file);
        });

        try {
            const response = await axios.post(`/api/support/tickets/${selectedTicket.id}/messages`, formData, {
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
            alert(err?.response?.data?.message || 'Error al enviar mensaje.');
        } finally {
            setLoading(false);
        }
    };

    const openTicketDetails = async (ticket: SupportTicket) => {
        try {
            const res = await axios.get(`/api/support/tickets/${ticket.id}?user_id=${user_id}`);
            if (res.data?.status === 'success') {
                setSelectedTicket(res.data.data);
            }
        } catch (error) {
            setSelectedTicket(ticket);
        }
    };

    return (
        <CustomerAccountLayout
            title="Centro de Ayuda & Soporte"
            description="Reporta incidencias con tus pedidos, pagos o solicita asistencia con fotos y videos de evidencia."
        >
            <Head title="Centro de Ayuda & Soporte - OwOMarket" />

            <div className="space-y-6">
                {/* Header Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div>
                        <h2 className="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <HiOutlineChatBubbleLeftRight className="w-6 h-6 text-blue-600" />
                            Mis Solicitudes de Ayuda e Incidencias
                        </h2>
                        <p className="text-xs text-gray-500 mt-0.5">
                            Seguimiento de consultas y reporte de problemas con atención prioritaria.
                        </p>
                    </div>

                    <button
                        onClick={() => setIsCreateModalOpen(true)}
                        className="px-5 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2"
                    >
                        <HiOutlinePlus className="w-4 h-4" />
                        <span>Nueva Consulta / Reportar Error</span>
                    </button>
                </div>

                {feedback && (
                    <div className={`p-4 rounded-2xl text-xs font-semibold ${
                        feedback.type === 'success'
                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                            : 'bg-red-50 text-red-700 border border-red-200'
                    }`}>
                        {feedback.text}
                    </div>
                )}

                {/* Main Content */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* List */}
                    <div className={`${selectedTicket ? 'lg:col-span-5' : 'lg:col-span-12'} space-y-3`}>
                        {tickets.length === 0 ? (
                            <div className="text-center py-16 bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-700 text-gray-400 text-xs">
                                No tienes solicitudes de soporte activas. ¡Todo está en orden!
                            </div>
                        ) : (
                            tickets.map(ticket => (
                                <div
                                    key={ticket.id}
                                    onClick={() => openTicketDetails(ticket)}
                                    className={`p-4 rounded-2xl border cursor-pointer transition ${
                                        selectedTicket?.id === ticket.id
                                            ? 'border-blue-600 bg-blue-50/40 dark:bg-blue-950/40'
                                            : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="flex items-center justify-between">
                                        <span className="text-[11px] font-mono font-bold text-blue-600">{ticket.ticket_number}</span>
                                        <span className="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-700">
                                            {ticket.status === 'open' ? 'Abierto' : ticket.status === 'in_progress' ? 'En Proceso' : ticket.status === 'resolved' ? 'Resuelto' : ticket.status}
                                        </span>
                                    </div>
                                    <h4 className="font-bold text-xs text-gray-900 dark:text-white mt-1">{ticket.subject}</h4>
                                    <p className="text-[11px] text-gray-500 line-clamp-1 mt-0.5">{ticket.description}</p>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Chat Drawer */}
                    {selectedTicket && (
                        <div className="lg:col-span-7 bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm space-y-4 flex flex-col h-[650px]">
                            <div className="flex items-start justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                                <div>
                                    <span className="text-xs font-mono font-bold text-blue-600">{selectedTicket.ticket_number}</span>
                                    <h3 className="text-sm font-black text-gray-900 dark:text-white">{selectedTicket.subject}</h3>
                                </div>
                                <button onClick={() => setSelectedTicket(null)} className="text-gray-400 hover:text-gray-600">
                                    <HiOutlineXMark className="w-5 h-5" />
                                </button>
                            </div>

                            <div className="flex-1 overflow-y-auto space-y-4 pr-2">
                                {(selectedTicket.messages || []).map((msg) => {
                                    const isFromCustomer = msg.sender_type === 'customer';
                                    return (
                                        <div key={msg.id} className={`flex flex-col ${isFromCustomer ? 'items-end' : 'items-start'} space-y-1`}>
                                            <span className="text-[10px] text-gray-400 px-1">
                                                {isFromCustomer ? 'Tú' : '👨‍💻 Equipo de Soporte OwOMarket'}
                                            </span>
                                            <div className={`p-3.5 rounded-2xl max-w-md text-xs leading-relaxed ${
                                                isFromCustomer
                                                    ? 'bg-blue-600 text-white rounded-br-none'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white rounded-bl-none'
                                            }`}>
                                                <p className="whitespace-pre-wrap">{msg.message}</p>

                                                {msg.attachments && msg.attachments.length > 0 && (
                                                    <div className="mt-2 pt-2 border-t border-white/20 grid grid-cols-2 gap-2">
                                                        {msg.attachments.map((att, idx) => (
                                                            <div
                                                                key={idx}
                                                                onClick={() => setPreviewMediaUrl({ url: att.url, type: att.type as any })}
                                                                className="rounded-lg overflow-hidden cursor-pointer border border-white/20 aspect-video flex items-center justify-center bg-black/10"
                                                            >
                                                                {att.type === 'video' ? (
                                                                    <span className="text-[10px] font-bold text-yellow-300 flex items-center gap-1">
                                                                        <HiOutlineVideoCamera className="w-4 h-4" /> Video
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

                            <form onSubmit={handleSendReply} className="pt-2 border-t border-gray-100 dark:border-gray-700 space-y-2">
                                {replyFilePreviews.length > 0 && (
                                    <div className="flex gap-2 pb-2 overflow-x-auto">
                                        {replyFilePreviews.map((f, i) => (
                                            <div key={i} className="relative w-12 h-12 rounded-lg border overflow-hidden shrink-0">
                                                {f.type === 'video' ? (
                                                    <div className="w-full h-full bg-gray-900 text-yellow-400 flex items-center justify-center">
                                                        <HiOutlineVideoCamera className="w-4 h-4" />
                                                    </div>
                                                ) : (
                                                    <img src={f.url} alt="prev" className="w-full h-full object-cover" />
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => removeFile(i, true)}
                                                    className="absolute top-0.5 right-0.5 bg-black/70 text-white rounded-full p-0.5"
                                                >
                                                    <HiOutlineXMark className="w-2.5 h-2.5" />
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
                                        className="p-2.5 rounded-xl border text-gray-500 hover:text-blue-600"
                                    >
                                        <HiOutlinePaperClip className="w-4 h-4" />
                                    </button>
                                    <input
                                        type="text"
                                        placeholder="Escribe tu mensaje..."
                                        value={replyText}
                                        onChange={(e) => setReplyText(e.target.value)}
                                        className="flex-1 p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs bg-gray-50 dark:bg-gray-900"
                                    />
                                    <button
                                        type="submit"
                                        disabled={loading || !replyText.trim()}
                                        className="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold"
                                    >
                                        <HiOutlinePaperAirplane className="w-4 h-4" />
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal: Nuevo Ticket Cliente */}
            {isCreateModalOpen && (
                <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
                    <div className="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
                        <div className="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                            <h3 className="text-base font-black text-gray-900 dark:text-white">
                                Nueva Solicitud de Ayuda
                            </h3>
                            <button onClick={() => setIsCreateModalOpen(false)} className="text-gray-400">
                                <HiOutlineXMark className="w-5 h-5" />
                            </button>
                        </div>

                        <form onSubmit={handleCreateTicket} className="space-y-3">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Asunto o Motivo *
                                </label>
                                <input
                                    type="text"
                                    placeholder="Ej: Problema con el pago de mi pedido #ORD-1234"
                                    value={subject}
                                    onChange={(e) => setSubject(e.target.value)}
                                    className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-medium bg-gray-50 dark:bg-gray-900"
                                    required
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Categoría
                                </label>
                                <select
                                    value={category}
                                    onChange={(e) => setCategory(e.target.value)}
                                    className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 text-xs bg-gray-50 dark:bg-gray-900"
                                >
                                    <option value="order_issue">Incidencia con un Pedido / Envío</option>
                                    <option value="payment_issue">Problema con Pago Móvil / Binance Pay</option>
                                    <option value="technical_error">Fallo en la Página / App</option>
                                    <option value="account">Mi Cuenta / Datos Personales</option>
                                    <option value="other">Otra Consulta</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Detalle del Problema *
                                </label>
                                <textarea
                                    rows={4}
                                    placeholder="Explícanos lo ocurrido con el mayor detalle posible..."
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    className="w-full p-2.5 rounded-xl border border-gray-300 dark:border-gray-700 text-xs bg-gray-50 dark:bg-gray-900"
                                    required
                                />
                            </div>

                            {/* Dropzone */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Fotos o Videos del Comprobante / Error (Opcional)
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
                                    className="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-3 text-center cursor-pointer bg-gray-50 dark:bg-gray-900"
                                >
                                    <p className="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center justify-center gap-1.5">
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
                                    className="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20"
                                >
                                    {loading ? 'Enviando...' : 'Enviar Reporte'}
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
        </CustomerAccountLayout>
    );
};

export default CustomerSupportPage;
