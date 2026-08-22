import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CustomerPortalServices from '@/Services/CustomerPortalServices';
import {
    HiOutlineStar,
    HiStar,
    HiOutlineCheckCircle,
} from 'react-icons/hi2';

export const CustomerReviewsPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [pending, setPending] = useState<any[]>([]);
    const [reviewed, setReviewed] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);

    // Modal state
    const [showModal, setShowModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<any | null>(null);
    const [rating, setRating] = useState(5);
    const [title, setTitle] = useState('');
    const [comment, setComment] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const loadReviews = () => {
        if (!customer?.id) return;
        setLoading(true);
        CustomerPortalServices.getPendingReviews(customer.id)
            .then(res => {
                if (res?.data) {
                    setPending(res.data.pending || []);
                    setReviewed(res.data.reviewed || []);
                }
            })
            .catch(() => setLoadError(true))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadReviews();
    }, [customer?.id]);

    const openReviewModal = (item: any) => {
        setSelectedItem(item);
        setRating(5);
        setTitle('');
        setComment('');
        setShowModal(true);
    };

    const handleSubmitReview = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!customer?.id || !selectedItem) return;

        setSubmitting(true);
        try {
            await CustomerPortalServices.submitReview({
                customer_id: customer.id,
                order_id: selectedItem.order_id,
                product_id: selectedItem.product_id,
                rating,
                title: title.trim() || undefined,
                comment: comment.trim(),
            });
            setShowModal(false);
            loadReviews();
            alert('¡Gracias! Tu reseña ha sido publicada exitosamente.');
        } catch (err: any) {
            alert(err.response?.data?.message || 'Error al publicar la reseña.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <CustomerAccountLayout
            title="Mis Reseñas & Calificaciones"
            description="Comparte tu opinión sobre los productos comprados para ayudar a la comunidad de compradores."
        >
            {loadError && <PortalLoadError />}

            <Head title="Mis Reseñas - OwOMarket" />

            {/* Pending Reviews */}
            <div className="mb-8">
                <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <HiOutlineStar className="w-5 h-5 text-amber-500" />
                    Productos Pendientes por Calificar ({pending.length})
                </h3>

                {pending.length === 0 ? (
                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-8 text-center border border-gray-200/80 dark:border-gray-800/80 text-gray-400 text-xs">
                        No tienes productos pendientes por calificar.
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {pending.map((item, idx) => (
                            <div
                                key={idx}
                                className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-gray-200/80 dark:border-gray-800/80 flex items-center justify-between gap-4"
                            >
                                <div>
                                    <h4 className="text-xs font-bold text-gray-900 dark:text-white">
                                        {item.product_name}
                                    </h4>
                                    <span className="text-[11px] text-gray-400">
                                        Orden {item.order_number} • Comprado el {item.purchased_at}
                                    </span>
                                </div>
                                <button
                                    onClick={() => openReviewModal(item)}
                                    className="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-500/20 whitespace-nowrap transition"
                                >
                                    Calificar
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Reviewed Products */}
            <div>
                <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <HiOutlineCheckCircle className="w-5 h-5 text-green-600" />
                    Reseñas Publicadas ({reviewed.length})
                </h3>

                {reviewed.length === 0 ? (
                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-8 text-center border border-gray-200/80 dark:border-gray-800/80 text-gray-400 text-xs">
                        Aún no has publicado reseñas.
                    </div>
                ) : (
                    <div className="space-y-3">
                        {reviewed.map((r, idx) => (
                            <div
                                key={idx}
                                className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-gray-200/80 dark:border-gray-800/80"
                            >
                                <div className="flex items-center justify-between mb-2">
                                    <h4 className="text-xs font-bold text-gray-900 dark:text-white">
                                        {r.product_name}
                                    </h4>
                                    <div className="flex items-center gap-0.5 text-amber-400">
                                        {[...Array(5)].map((_, i) => (
                                            <HiStar
                                                key={i}
                                                className={`w-4 h-4 ${i < r.rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-700'}`}
                                            />
                                        ))}
                                    </div>
                                </div>
                                {r.title && (
                                    <h5 className="text-xs font-semibold text-gray-800 dark:text-gray-200 mb-1">
                                        {r.title}
                                    </h5>
                                )}
                                <p className="text-xs text-gray-600 dark:text-gray-400 italic">
                                    "{r.comment}"
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Submit Review Modal */}
            {showModal && selectedItem && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div className="bg-white dark:bg-gray-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-800">
                        <h3 className="text-base font-black text-gray-900 dark:text-white mb-2">
                            Calificar {selectedItem.product_name}
                        </h3>

                        <form onSubmit={handleSubmitReview} className="space-y-4">
                            {/* Stars selector */}
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                                    Puntuación
                                </label>
                                <div className="flex items-center gap-2">
                                    {[1, 2, 3, 4, 5].map((star) => (
                                        <button
                                            type="button"
                                            key={star}
                                            onClick={() => setRating(star)}
                                            className="p-1 text-2xl focus:outline-none transition hover:scale-110"
                                        >
                                            <HiStar className={`w-8 h-8 ${star <= rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-700'}`} />
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Título de tu Reseña (Opcional)
                                </label>
                                <input
                                    type="text"
                                    value={title}
                                    onChange={e => setTitle(e.target.value)}
                                    placeholder="ej. Excelente calidad y envío rápido"
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Tu Comentario
                                </label>
                                <textarea
                                    value={comment}
                                    onChange={e => setComment(e.target.value)}
                                    required
                                    rows={3}
                                    placeholder="¿Qué te pareció el producto? ¿Cumplió con tus expectativas?"
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <div className="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="px-4 py-2 text-xs font-bold text-gray-500 hover:text-gray-700 rounded-xl hover:bg-gray-100 transition"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    disabled={submitting}
                                    className="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-md shadow-amber-500/20 transition disabled:opacity-50"
                                >
                                    {submitting ? 'Publicando...' : 'Publicar Reseña'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </CustomerAccountLayout>
    );
};

export default CustomerReviewsPage;
