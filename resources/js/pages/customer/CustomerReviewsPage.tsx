import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import PortalActionFeedback, { PortalFeedback } from '@/components/ui/customer/PortalActionFeedback';
import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import CustomerPortalServices from '@/Services/CustomerPortalServices';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import { Head } from '@inertiajs/react';
import {
    Button,
    Card,
    Label,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Textarea,
    TextInput,
} from 'flowbite-react';
import React, { useEffect, useState } from 'react';
import { HiOutlineCheckCircle, HiOutlineStar, HiStar } from 'react-icons/hi2';

/**
 * Reseñas del comprador.
 *
 * El botón de calificar usa `color="accent"` —el ámbar del tema— y no el azul: no es la acción
 * principal de la página ni una alerta, es algo que el portal pide sin exigir. Que el color
 * viva en el tema y no aquí es lo que evita que la próxima pantalla que pida algo parecido
 * invente su propio ámbar.
 */
export const CustomerReviewsPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [pending, setPending] = useState<any[]>([]);
    const [reviewed, setReviewed] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);

    const [showModal, setShowModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<any | null>(null);
    const [rating, setRating] = useState(5);
    const [title, setTitle] = useState('');
    const [comment, setComment] = useState('');
    const [submitting, setSubmitting] = useState(false);
    // Hallazgo C2: el resultado de cada accion, en linea en vez de un alert().
    const [feedback, setFeedback] = useState<PortalFeedback | null>(null);

    const loadReviews = () => {
        if (!customer?.id) return;
        setLoading(true);
        CustomerPortalServices.getPendingReviews(customer.id)
            .then((res) => {
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
        setFeedback(null);
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
            setFeedback({ type: 'success', text: '¡Gracias! Tu reseña se publicó correctamente.' });
        } catch (err: any) {
            setFeedback({ type: 'error', text: err.response?.data?.message || 'No se pudo publicar la reseña.' });
        } finally {
            setSubmitting(false);
        }
    };

    /** Las cinco estrellas, en lectura o en selección. */
    const Estrellas = ({ valor, tamano = 'h-4 w-4' }: { valor: number; tamano?: string }) => (
        <>
            {[...Array(5)].map((_, i) => (
                <HiStar
                    key={i}
                    className={`${tamano} ${i < valor ? 'text-amber-400' : 'text-gray-300 dark:text-gray-700'}`}
                />
            ))}
        </>
    );

    return (
        <CustomerAccountLayout
            title="Mis Reseñas & Calificaciones"
            description="Comparte tu opinión sobre los productos comprados para ayudar a la comunidad de compradores."
        >
            {loadError && <PortalLoadError />}
            <PortalActionFeedback feedback={feedback} />

            <Head title="Mis Reseñas - OwOMarket" />

            <section className="mb-8">
                <h3 className="mb-4 flex items-center gap-2 text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                    <HiOutlineStar className="h-5 w-5 text-amber-500" />
                    Productos Pendientes por Calificar ({pending.length})
                </h3>

                {!loading && pending.length === 0 ? (
                    <Card>
                        <p data-testid="resenas-pendientes-vacio" className="text-center text-xs text-gray-400">
                            No tienes productos pendientes por calificar.
                        </p>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        {pending.map((item, idx) => (
                            <Card
                                key={idx}
                                theme={{ root: { children: 'flex h-full flex-row items-center justify-between gap-4 p-5' } }}
                            >
                                <div className="min-w-0">
                                    <h4 className="text-xs font-bold text-gray-900 dark:text-white">
                                        {item.product_name}
                                    </h4>
                                    <span className="text-[11px] text-gray-400">
                                        Orden {item.order_number} • Comprado el {item.purchased_at}
                                    </span>
                                </div>
                                <Button
                                    color="accent"
                                    size="xs"
                                    className="shrink-0"
                                    onClick={() => openReviewModal(item)}
                                >
                                    Calificar
                                </Button>
                            </Card>
                        ))}
                    </div>
                )}
            </section>

            <section>
                <h3 className="mb-4 flex items-center gap-2 text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                    <HiOutlineCheckCircle className="h-5 w-5 text-green-600" />
                    Reseñas Publicadas ({reviewed.length})
                </h3>

                {!loading && reviewed.length === 0 ? (
                    <Card>
                        <p data-testid="resenas-publicadas-vacio" className="text-center text-xs text-gray-400">
                            Aún no has publicado reseñas.
                        </p>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {reviewed.map((r, idx) => (
                            <Card key={idx} theme={{ root: { children: 'flex h-full flex-col gap-2 p-5' } }}>
                                <div className="flex items-center justify-between">
                                    <h4 className="text-xs font-bold text-gray-900 dark:text-white">
                                        {r.product_name}
                                    </h4>
                                    <div className="flex items-center gap-0.5">
                                        <Estrellas valor={r.rating} />
                                    </div>
                                </div>
                                {r.title && (
                                    <h5 className="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                        {r.title}
                                    </h5>
                                )}
                                <p className="text-xs italic text-gray-600 dark:text-gray-400">«{r.comment}»</p>
                            </Card>
                        ))}
                    </div>
                )}
            </section>

            <Modal show={showModal && selectedItem !== null} onClose={() => setShowModal(false)} size="md">
                <ModalHeader>Calificar {selectedItem?.product_name}</ModalHeader>
                <form onSubmit={handleSubmitReview}>
                    <ModalBody>
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="resena-puntuacion">Puntuación</Label>
                                <div id="resena-puntuacion" className="flex items-center gap-2">
                                    {[1, 2, 3, 4, 5].map((star) => (
                                        <button
                                            type="button"
                                            key={star}
                                            onClick={() => setRating(star)}
                                            aria-label={`${star} ${star === 1 ? 'estrella' : 'estrellas'}`}
                                            className="p-1 transition hover:scale-110 focus:outline-none focus:ring-2 focus:ring-amber-300"
                                        >
                                            <HiStar
                                                className={`h-8 w-8 ${star <= rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-700'}`}
                                            />
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="resena-titulo">Título de tu Reseña (Opcional)</Label>
                                <TextInput
                                    id="resena-titulo"
                                    value={title}
                                    onChange={(e) => setTitle(e.target.value)}
                                    placeholder="ej. Excelente calidad y envío rápido"
                                />
                            </div>

                            <div>
                                <Label htmlFor="resena-comentario">Tu Comentario</Label>
                                <Textarea
                                    id="resena-comentario"
                                    value={comment}
                                    onChange={(e) => setComment(e.target.value)}
                                    required
                                    rows={3}
                                    placeholder="¿Qué te pareció el producto? ¿Cumplió con tus expectativas?"
                                />
                            </div>
                        </div>
                    </ModalBody>
                    <ModalFooter>
                        <Button type="submit" color="accent" size="sm" disabled={submitting}>
                            {submitting ? 'Publicando...' : 'Publicar Reseña'}
                        </Button>
                        <Button type="button" color="subtle" size="sm" onClick={() => setShowModal(false)}>
                            Cancelar
                        </Button>
                    </ModalFooter>
                </form>
            </Modal>
        </CustomerAccountLayout>
    );
};

export default CustomerReviewsPage;
