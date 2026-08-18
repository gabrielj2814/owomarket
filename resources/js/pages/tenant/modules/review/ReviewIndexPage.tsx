import React, { useEffect, useState } from 'react';
import Dashboard from '@/components/layouts/Dashboard';
import ReviewServices from '@/Services/ReviewServices';
import { FilterReviewsParams } from '@/types/FormProductReview';
import { ProductRatingSummary, ProductReview } from '@/types/models/ProductReview';
import { Head, Link } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Pagination,
    Progress,
    Select,
    Spinner,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    TextInput,
    Textarea,
} from 'flowbite-react';
import {
    HiChatAlt2,
    HiCheckCircle,
    HiEye,
    HiEyeOff,
    HiHome,
    HiPlus,
    HiRefresh,
    HiSearch,
    HiStar,
    HiTag,
    HiTrash,
    HiUser,
    HiXCircle,
} from 'react-icons/hi';

interface ReviewIndexPageProps {
    title: string;
    user_id: string;
    host: string;
    user_name: string;
}

export default function ReviewIndexPage({
    title,
    user_id,
    host,
    user_name,
}: ReviewIndexPageProps) {
    const [reviews, setReviews] = useState<ProductReview[]>([]);
    const [summary, setSummary] = useState<ProductRatingSummary>({
        product_id: null,
        total_reviews: 0,
        average_rating: 0,
        star_breakdown: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
    });
    const [loading, setLoading] = useState<boolean>(true);
    const [loadingAction, setLoadingAction] = useState<boolean>(false);
    const [toastMessage, setToastMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    // Filters & Pagination State
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [searchTerm, setSearchTerm] = useState<string>('');
    const [filterRating, setFilterRating] = useState<string>('all');
    const [filterStatus, setFilterStatus] = useState<string>('all');
    const [filterResponse, setFilterResponse] = useState<string>('all');

    // Modals State
    const [isRespondModalOpen, setIsRespondModalOpen] = useState<boolean>(false);
    const [selectedReview, setSelectedReview] = useState<ProductReview | null>(null);
    const [responseText, setResponseText] = useState<string>('');

    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState<boolean>(false);
    const [reviewToDelete, setReviewToDelete] = useState<ProductReview | null>(null);

    const showToast = (type: 'success' | 'error', text: string) => {
        setToastMessage({ type, text });
        setTimeout(() => setToastMessage(null), 4000);
    };

    const fetchSummary = async () => {
        try {
            const res = await ReviewServices.getSummary();
            if (res.data && (res.data.code === 200 || res.data.status === 'success') && res.data.data) {
                setSummary(res.data.data);
            }
        } catch (e) {
            // Silently fail
        }
    };

    const fetchReviews = async (page: number = 1) => {
        setLoading(true);
        try {
            const params: FilterReviewsParams = {
                page,
                per_page: 15,
                search: searchTerm.trim() !== '' ? searchTerm.trim() : null,
                rating: filterRating !== 'all' ? parseInt(filterRating, 10) : null,
                is_approved: filterStatus === 'approved' ? true : filterStatus === 'pending' ? false : null,
                has_response: filterResponse === 'responded' ? true : filterResponse === 'unanswered' ? false : null,
                sort_by: 'created_at',
                sort_direction: 'desc',
            };

            const response = await ReviewServices.filtrar(params);
            if (response.data && (response.data.code === 200 || response.data.status === 'success') && response.data.data) {
                setReviews(response.data.data.data);
                setCurrentPage(response.data.data.current_page);
                setTotalPages(response.data.data.last_page);
                setTotalItems(response.data.data.total);
            }
        } catch (e) {
            showToast('error', 'Error al cargar el listado de reseñas.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchSummary();
        fetchReviews(1);
    }, [searchTerm, filterRating, filterStatus, filterResponse]);

    const handlePageChange = (page: number) => {
        setCurrentPage(page);
        fetchReviews(page);
    };

    // Toggle Moderate (Approve / Reject)
    const handleToggleModerate = async (review: ProductReview) => {
        setLoadingAction(true);
        const newStatus = !review.is_approved;
        try {
            const response = await ReviewServices.moderate(review.id, newStatus);
            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast(
                    'success',
                    newStatus ? 'Reseña aprobada y visible en catálogo.' : 'Reseña ocultada de la tienda pública.'
                );
                fetchReviews(currentPage);
                fetchSummary();
            } else {
                showToast('error', response.data?.message || 'Error al actualizar moderación.');
            }
        } catch (e) {
            showToast('error', 'Error de conexión.');
        } finally {
            setLoadingAction(false);
        }
    };

    // Open Respond Modal
    const handleOpenRespondModal = (review: ProductReview) => {
        setSelectedReview(review);
        setResponseText(review.response || '');
        setIsRespondModalOpen(true);
    };

    // Submit Response
    const handleSubmitResponse = async () => {
        if (!selectedReview) return;
        setLoadingAction(true);
        try {
            const response = await ReviewServices.respond(selectedReview.id, responseText);
            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast('success', 'Respuesta pública del comercio guardada con éxito.');
                setIsRespondModalOpen(false);
                fetchReviews(currentPage);
            } else {
                showToast('error', response.data?.message || 'Error al guardar respuesta.');
            }
        } catch (e) {
            showToast('error', 'Error de comunicación con el servidor.');
        } finally {
            setLoadingAction(false);
        }
    };

    // Open Delete Modal
    const handleOpenDeleteModal = (review: ProductReview) => {
        setReviewToDelete(review);
        setIsDeleteModalOpen(true);
    };

    // Submit Delete
    const handleSubmitDelete = async () => {
        if (!reviewToDelete) return;
        setLoadingAction(true);
        try {
            const response = await ReviewServices.delete(reviewToDelete.id);
            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast('success', 'Reseña eliminada permanentemente.');
                setIsDeleteModalOpen(false);
                fetchReviews(currentPage);
                fetchSummary();
            } else {
                showToast('error', response.data?.message || 'Error al eliminar reseña.');
            }
        } catch (e) {
            showToast('error', 'Error de conexión.');
        } finally {
            setLoadingAction(false);
        }
    };

    const handleClearFilters = () => {
        setSearchTerm('');
        setFilterRating('all');
        setFilterStatus('all');
        setFilterResponse('all');
    };

    const renderStars = (rating: number) => {
        return (
            <div className="flex items-center text-amber-400">
                {[1, 2, 3, 4, 5].map((star) => (
                    <HiStar
                        key={star}
                        className={`h-4 w-4 ${star <= rating ? 'text-amber-400 fill-current' : 'text-gray-300 dark:text-gray-600'}`}
                    />
                ))}
                <span className="ml-1 text-xs font-bold text-gray-700 dark:text-gray-300">
                    {rating}.0
                </span>
            </div>
        );
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />
            <div className="p-4 sm:p-6 space-y-6 max-w-7xl mx-auto">
                {/* Toast Notification */}
                {toastMessage && (
                    <div
                        className={`fixed top-5 right-5 z-50 flex items-center p-4 mb-4 text-sm rounded-lg shadow-lg ${
                            toastMessage.type === 'success'
                                ? 'text-green-800 bg-green-100 dark:bg-green-800 dark:text-green-200 border border-green-300'
                                : 'text-red-800 bg-red-100 dark:bg-red-800 dark:text-red-200 border border-red-300'
                        }`}
                    >
                        <span className="font-medium mr-2">
                            {toastMessage.type === 'success' ? 'Éxito:' : 'Error:'}
                        </span>
                        {toastMessage.text}
                    </div>
                )}

                {/* Breadcrumb */}
                <Breadcrumb>
                    <BreadcrumbItem href={`/tenant/backoffice/${user_id}/dashboard`} icon={HiHome}>
                        Dashboard
                    </BreadcrumbItem>
                    <BreadcrumbItem>Reseñas y Calificaciones</BreadcrumbItem>
                </Breadcrumb>

                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">
                            Reseñas y Calificaciones
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">
                            Modera las opiniones de tus compradores, responde a inquietudes y visualiza el rating promedio de tu tienda.
                        </p>
                    </div>
                    <Button
                        color="gray"
                        size="sm"
                        onClick={() => {
                            fetchSummary();
                            fetchReviews(currentPage);
                        }}
                    >
                        <HiRefresh className="mr-1 h-4 w-4" />
                        Actualizar
                    </Button>
                </div>

                {/* Metrics KPI Cards & Star Breakdown */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Overall Rating Card */}
                    <Card className="shadow-sm flex flex-col justify-center items-center text-center p-6 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-gray-800 dark:to-gray-900 border-amber-200 dark:border-gray-700">
                        <span className="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                            Puntuación Global Promedio
                        </span>
                        <div className="text-5xl font-black text-gray-900 dark:text-white mt-2">
                            {summary.average_rating > 0 ? summary.average_rating.toFixed(1) : '0.0'}
                        </div>
                        <div className="flex items-center text-amber-400 my-2">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <HiStar
                                    key={star}
                                    className={`h-6 w-6 ${
                                        star <= Math.round(summary.average_rating)
                                            ? 'text-amber-400 fill-current'
                                            : 'text-gray-300 dark:text-gray-600'
                                    }`}
                                />
                            ))}
                        </div>
                        <p className="text-xs text-gray-500">
                            Basado en {summary.total_reviews} {summary.total_reviews === 1 ? 'opinión verificada' : 'opiniones verificadas'}
                        </p>
                    </Card>

                    {/* Star Breakdown Progress Card */}
                    <Card className="shadow-sm lg:col-span-2 space-y-2">
                        <h3 className="text-sm font-bold text-gray-900 dark:text-white mb-2">
                            Distribución de Estrellas
                        </h3>
                        {[5, 4, 3, 2, 1].map((star) => {
                            const count = summary.star_breakdown?.[star] || 0;
                            const percentage = summary.total_reviews > 0 ? Math.round((count / summary.total_reviews) * 100) : 0;
                            return (
                                <div key={star} className="flex items-center gap-3 text-xs">
                                    <span className="w-12 font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1">
                                        {star} <HiStar className="h-3.5 w-3.5 text-amber-400 fill-current inline" />
                                    </span>
                                    <div className="flex-1">
                                        <Progress
                                            progress={percentage}
                                            color={star >= 4 ? 'yellow' : star === 3 ? 'blue' : 'red'}
                                            size="sm"
                                        />
                                    </div>
                                    <span className="w-16 text-right text-gray-500 font-mono">
                                        {count} ({percentage}%)
                                    </span>
                                </div>
                            );
                        })}
                    </Card>
                </div>

                {/* Filters Section */}
                <Card className="shadow-sm">
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                        <div className="md:col-span-2">
                            <Label htmlFor="search_input" className="text-xs">Buscar</Label>
                            <div className="relative mt-1">
                                <TextInput
                                    id="search_input"
                                    placeholder="Buscar por título, comentario, cliente o producto..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    icon={HiSearch}
                                />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="filter_rating" className="text-xs">Calificación</Label>
                            <Select
                                id="filter_rating"
                                value={filterRating}
                                onChange={(e) => setFilterRating(e.target.value)}
                                className="mt-1"
                            >
                                <option value="all">Todas las Estrellas</option>
                                <option value="5">5 Estrellas (Excelente)</option>
                                <option value="4">4 Estrellas (Muy Bueno)</option>
                                <option value="3">3 Estrellas (Regular)</option>
                                <option value="2">2 Estrellas (Malo)</option>
                                <option value="1">1 Estrella (Pésimo)</option>
                            </Select>
                        </div>

                        <div>
                            <Label htmlFor="filter_status" className="text-xs">Moderación</Label>
                            <Select
                                id="filter_status"
                                value={filterStatus}
                                onChange={(e) => setFilterStatus(e.target.value)}
                                className="mt-1"
                            >
                                <option value="all">Todos los Estados</option>
                                <option value="approved">Aprobadas / Visibles</option>
                                <option value="pending">Pendientes / Ocultas</option>
                            </Select>
                        </div>

                        <div>
                            <Label htmlFor="filter_response" className="text-xs">Respuesta</Label>
                            <Select
                                id="filter_response"
                                value={filterResponse}
                                onChange={(e) => setFilterResponse(e.target.value)}
                                className="mt-1"
                            >
                                <option value="all">Todas</option>
                                <option value="responded">Con Respuesta</option>
                                <option value="unanswered">Sin Responder</option>
                            </Select>
                        </div>
                    </div>

                    {(searchTerm || filterRating !== 'all' || filterStatus !== 'all' || filterResponse !== 'all') && (
                        <div className="flex justify-end pt-2">
                            <Button color="gray" size="xs" onClick={handleClearFilters}>
                                <HiXCircle className="mr-1 h-4 w-4" />
                                Limpiar Filtros
                            </Button>
                        </div>
                    )}
                </Card>

                {/* Reviews Table Card */}
                <Card className="shadow-sm">
                    <div className="flex justify-between items-center border-b pb-3">
                        <h3 className="font-bold text-gray-900 dark:text-white">
                            Listado de Reseñas ({totalItems})
                        </h3>
                    </div>

                    {loading ? (
                        <div className="py-16 text-center">
                            <Spinner size="xl" />
                            <p className="text-sm text-gray-500 mt-2">Cargando reseñas...</p>
                        </div>
                    ) : reviews.length === 0 ? (
                        <div className="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                            <HiChatAlt2 className="mx-auto h-12 w-12 text-gray-400" />
                            <p className="text-base font-semibold text-gray-700 dark:text-gray-300 mt-2">
                                No se encontraron reseñas con los filtros actuales.
                            </p>
                            <p className="text-xs text-gray-500 mt-1">
                                Las opiniones registradas por los compradores aparecerán aquí para moderación.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {reviews.map((rev) => (
                                <div
                                    key={rev.id}
                                    className="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 space-y-3"
                                >
                                    {/* Header Row: Product, Rating, Badges, Action Buttons */}
                                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b pb-3 dark:border-gray-700">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <span className="font-bold text-gray-900 dark:text-white text-base">
                                                    {rev.product?.name || 'Producto del Catálogo'}
                                                </span>
                                                {rev.product?.sku && (
                                                    <span className="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded font-mono text-gray-600 dark:text-gray-300">
                                                        {rev.product.sku}
                                                    </span>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-2 mt-1">
                                                {renderStars(rev.rating)}
                                                <span className="text-xs text-gray-400">|</span>
                                                <span className="text-xs text-gray-500">
                                                    {rev.created_at ? new Date(rev.created_at).toLocaleDateString() : '-'}
                                                </span>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Badge color={rev.is_approved ? 'success' : 'warning'} size="sm">
                                                {rev.is_approved ? 'Publicada / Aprobada' : 'Pendiente / Oculta'}
                                            </Badge>
                                            {rev.is_verified && (
                                                <Badge color="info" size="sm">
                                                    Compra Verificada
                                                </Badge>
                                            )}
                                        </div>
                                    </div>

                                    {/* Middle Row: Customer info & Review text */}
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                            <HiUser className="h-4 w-4 text-gray-400" />
                                            <span className="font-bold text-gray-900 dark:text-white">
                                                {rev.customer?.name || 'Cliente'}
                                            </span>
                                            {rev.customer?.email && (
                                                <span className="text-gray-400">({rev.customer.email})</span>
                                            )}
                                        </div>

                                        {rev.title && (
                                            <h4 className="text-sm font-bold text-gray-900 dark:text-white">
                                                "{rev.title}"
                                            </h4>
                                        )}

                                        {rev.comment && (
                                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                                {rev.comment}
                                            </p>
                                        )}
                                    </div>

                                    {/* Response Box if exists */}
                                    {rev.response && (
                                        <div className="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800 text-xs">
                                            <div className="flex items-center justify-between font-bold text-blue-900 dark:text-blue-300 mb-1">
                                                <span>Respuesta oficial de la Tienda:</span>
                                                <span className="font-normal text-gray-500">
                                                    {rev.responded_at ? new Date(rev.responded_at).toLocaleDateString() : ''}
                                                </span>
                                            </div>
                                            <p className="text-blue-800 dark:text-blue-200 italic">
                                                "{rev.response}"
                                            </p>
                                        </div>
                                    )}

                                    {/* Bottom Action Buttons */}
                                    <div className="flex justify-end gap-2 pt-2 border-t dark:border-gray-700">
                                        <Button
                                            color={rev.is_approved ? 'light' : 'success'}
                                            size="xs"
                                            onClick={() => handleToggleModerate(rev)}
                                            disabled={loadingAction}
                                        >
                                            {rev.is_approved ? (
                                                <>
                                                    <HiEyeOff className="mr-1 h-3.5 w-3.5" />
                                                    Ocultar de la Tienda
                                                </>
                                            ) : (
                                                <>
                                                    <HiCheckCircle className="mr-1 h-3.5 w-3.5" />
                                                    Aprobar y Publicar
                                                </>
                                            )}
                                        </Button>

                                        <Button
                                            color="blue"
                                            size="xs"
                                            onClick={() => handleOpenRespondModal(rev)}
                                            disabled={loadingAction}
                                        >
                                            <HiChatAlt2 className="mr-1 h-3.5 w-3.5" />
                                            {rev.response ? 'Editar Respuesta' : 'Responder al Cliente'}
                                        </Button>

                                        <Button
                                            color="failure"
                                            size="xs"
                                            onClick={() => handleOpenDeleteModal(rev)}
                                            disabled={loadingAction}
                                        >
                                            <HiTrash className="mr-1 h-3.5 w-3.5" />
                                            Eliminar
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="flex justify-center pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Pagination
                                currentPage={currentPage}
                                totalPages={totalPages}
                                onPageChange={handlePageChange}
                                showIcons
                                previousLabel="Anterior"
                                nextLabel="Siguiente"
                            />
                        </div>
                    )}
                </Card>
            </div>

            {/* Modal: Responder Reseña */}
            <Modal
                show={isRespondModalOpen}
                onClose={() => setIsRespondModalOpen(false)}
                size="md"
            >
                <ModalHeader>
                    Responder a Opinión de Cliente
                </ModalHeader>
                <ModalBody className="space-y-4">
                    {selectedReview && (
                        <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs space-y-1">
                            <span className="font-bold text-gray-700 dark:text-gray-300">
                                {selectedReview.customer?.name || 'Cliente'} ({selectedReview.rating}★):
                            </span>
                            <p className="italic text-gray-600 dark:text-gray-400">
                                "{selectedReview.comment || selectedReview.title || 'Sin comentario de texto'}"
                            </p>
                        </div>
                    )}

                    <div>
                        <Label htmlFor="merchant_response_input">Respuesta Pública Oficial (*)</Label>
                        <Textarea
                            id="merchant_response_input"
                            value={responseText}
                            onChange={(e) => setResponseText(e.target.value)}
                            placeholder="Escribe un mensaje de agradecimiento o aclaración para el cliente..."
                            rows={4}
                            required
                        />
                        <p className="text-xs text-gray-400 mt-1">
                            Esta respuesta será visible públicamente bajo la reseña en la ficha del producto. Deja en blanco para eliminar la respuesta anterior.
                        </p>
                    </div>
                </ModalBody>
                <ModalFooter className="flex justify-end gap-2">
                    <Button color="gray" onClick={() => setIsRespondModalOpen(false)}>
                        Cancelar
                    </Button>
                    <Button
                        color="blue"
                        onClick={handleSubmitResponse}
                        disabled={loadingAction}
                    >
                        {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                        Guardar Respuesta
                    </Button>
                </ModalFooter>
            </Modal>

            {/* Modal: Confirmar Eliminación */}
            <Modal
                show={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                size="md"
            >
                <ModalHeader>
                    Confirmar Eliminación de Reseña
                </ModalHeader>
                <ModalBody className="space-y-3">
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        ¿Estás seguro de que deseas eliminar permanentemente esta opinión de cliente? Esta acción no se puede deshacer y recalculará las métricas de la tienda.
                    </p>
                </ModalBody>
                <ModalFooter className="flex justify-end gap-2">
                    <Button color="gray" onClick={() => setIsDeleteModalOpen(false)}>
                        Cancelar
                    </Button>
                    <Button
                        color="failure"
                        onClick={handleSubmitDelete}
                        disabled={loadingAction}
                    >
                        {loadingAction ? <Spinner size="sm" className="mr-2" /> : null}
                        Eliminar Definitivamente
                    </Button>
                </ModalFooter>
            </Modal>
        </Dashboard>
    );
}
