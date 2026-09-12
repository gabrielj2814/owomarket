import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import PortalActionFeedback, { PortalFeedback } from '@/components/ui/customer/PortalActionFeedback';
import PortalLoadError from '@/components/ui/customer/PortalLoadError';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CustomerPortalServices, {
    CustomerOrderData,
    CustomerReturnRequestData,
} from '@/Services/CustomerPortalServices';
import { Head, Link } from '@inertiajs/react';
import {
    Badge,
    Button,
    Card,
    Label,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Select,
    Textarea,
} from 'flowbite-react';
import React, { useEffect, useState } from 'react';
import {
    HiOutlineArrowPathRoundedSquare,
    HiOutlineCheckCircle,
    HiOutlineClock,
    HiOutlineIdentification,
    HiOutlinePlus,
    HiOutlineXCircle,
} from 'react-icons/hi2';

/**
 * Devoluciones del comprador (subsistema 5, vista del comprador).
 *
 * ## Qué se añadió
 *
 * La pantalla mostraba el estado con una insignia de color y nada más. El backend ya devolvía
 * `resolution_notes`, `resolved_by` y `resolved_at` —el modelo se serializa entero, sin
 * `$hidden`— pero **el tipo no los declaraba y aquí no se pintaban**: el comprador veía
 * «Rechazada» y no tenía forma de saber por qué, teniendo la plataforma ese texto guardado.
 *
 * Tres cosas que ahora dice, y por qué cada una:
 *
 * 1. **El estado con palabras**, no solo con color. Un color no se lee igual para todos, y
 *    «aprobada» significa cosas distintas según quién la aprobó.
 * 2. **El motivo del rechazo.** Es lo único que el comprador tendrá para entender la decisión
 *    o para rebatirla.
 * 3. **Cómo se resolvió.** Que la tienda no contestara y venciera el plazo NO es lo mismo que
 *    una respuesta suya, aunque el resultado le favorezca igual.
 *
 * Y la cédula se avisa **antes** del formulario: sin ella el backend devuelve 422 al enviar, y
 * descubrirlo después de escribir la explicación entera es perder el texto y la paciencia.
 *
 * ## Sobre el aspecto
 *
 * Componentes de Flowbite a secas: el aspecto lo pone `portalTheme` desde
 * `CustomerAccountLayout`. Si algo aquí necesitara un `className` para parecerse a sus
 * hermanas, el sitio de arreglarlo es el tema, no esta página.
 */
const MOTIVOS = [
    'Producto dañado o roto',
    'Producto no coincide con la descripción',
    'Talla o variante incorrecta',
    'Defecto de fábrica',
    'Otro motivo',
];

/**
 * La insignia dice el estado; esta frase dice qué significa.
 *
 * Depende de `resolved_by` y no solo del estado, porque **una aprobación por silencio no la
 * aprobó la tienda**. Decir «la tienda aceptó tu reclamación» cuando la tienda no contestó es
 * atribuirle una decisión que no tomó, y encima contradice la línea de debajo, que explica que
 * venció el plazo. Una pantalla que se contradice a sí misma no se cree.
 */
const explicacionDe = (status: string, resolvedBy: string | null | undefined): string => {
    if (status === 'approved') {
        return resolvedBy === 'merchant'
            ? 'La tienda aceptó tu reclamación: se te devuelve el importe.'
            : 'Tu reclamación se aprobó: se te devuelve el importe.';
    }

    const fijas: Record<string, string> = {
        requested: 'La tienda todavía no ha respondido.',
        in_review: 'La tienda está revisando tu reclamación.',
        rejected: 'La tienda rechazó tu reclamación.',
        refunded: 'El importe ya se te devolvió.',
    };

    return fijas[status] ?? 'Tu reclamación está en curso.';
};

const INSIGNIA: Record<string, { texto: string; color: string; Icono: typeof HiOutlineClock }> = {
    approved: { texto: 'Aprobada', color: 'success', Icono: HiOutlineCheckCircle },
    refunded: { texto: 'Reembolsada', color: 'purple', Icono: HiOutlineCheckCircle },
    rejected: { texto: 'Rechazada', color: 'failure', Icono: HiOutlineXCircle },
    in_review: { texto: 'En revisión', color: 'blue', Icono: HiOutlineClock },
    requested: { texto: 'Solicitada', color: 'warning', Icono: HiOutlineClock },
};

/**
 * Cómo se resolvió, en palabras del comprador.
 *
 * `resolved_by` vale `merchant` o `timeout`. Cualquier otra cosa cae en una frase neutra a
 * propósito: si algún día se guardara ahí un identificador interno, no debe acabar en la
 * pantalla de un cliente.
 */
const comoSeResolvio = (resolvedBy: string | null | undefined): string | null => {
    if (!resolvedBy) return null;
    if (resolvedBy === 'timeout') {
        return 'La tienda no respondió dentro del plazo, así que se resolvió a tu favor.';
    }
    if (resolvedBy === 'merchant') return 'Respondió la tienda.';

    return 'La resolvió el equipo de OwOMarket.';
};

export const CustomerReturnsPage: React.FC = () => {
    const { customer } = useCustomerAuth();
    const [returns, setReturns] = useState<CustomerReturnRequestData[]>([]);
    const [orders, setOrders] = useState<CustomerOrderData[]>([]);
    const [loading, setLoading] = useState(true);
    // Hallazgo N35: un error de red era indistinguible de «no tienes nada».
    const [loadError, setLoadError] = useState(false);

    const [showModal, setShowModal] = useState(false);
    // Sin cédula el backend rechaza la solicitud con un 422. Se avisa ANTES de abrir el
    // formulario: que lo descubra al enviar significa perder la explicación que ya escribió.
    const [showCedulaAviso, setShowCedulaAviso] = useState(false);
    const [selectedOrderId, setSelectedOrderId] = useState('');
    const [selectedProductId, setSelectedProductId] = useState('');
    const [reason, setReason] = useState(MOTIVOS[0]);
    const [description, setDescription] = useState('');
    const [submitting, setSubmitting] = useState(false);
    // Hallazgo C2: el resultado de cada accion, en linea en vez de un alert().
    const [feedback, setFeedback] = useState<PortalFeedback | null>(null);

    const tieneCedula = Boolean(customer?.document_id && customer.document_id.trim() !== '');

    const loadData = () => {
        if (!customer?.id) return;
        setLoading(true);
        Promise.all([
            CustomerPortalServices.getReturns(customer.id),
            CustomerPortalServices.getOrders(customer.id, { status: 'completed' }),
        ])
            .then(([returnsRes, ordersRes]) => {
                if (returnsRes?.data) setReturns(returnsRes.data);
                if (ordersRes?.data) setOrders(ordersRes.data);
            })
            .catch(() => setLoadError(true))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        loadData();
    }, [customer?.id]);

    /** Un solo sitio decide si se abre el formulario o el aviso de la cédula. */
    const solicitarDevolucion = () => {
        if (tieneCedula) {
            setShowModal(true);
        } else {
            setShowCedulaAviso(true);
        }
    };

    const handleCreateReturn = async (e: React.FormEvent) => {
        e.preventDefault();
        setFeedback(null);
        if (!customer?.id || !selectedOrderId || !selectedProductId) {
            setFeedback({ type: 'error', text: 'Selecciona una orden y el producto que quieres devolver.' });
            return;
        }

        setSubmitting(true);
        try {
            await CustomerPortalServices.createReturn({
                customer_id: customer.id,
                order_id: selectedOrderId,
                product_id: selectedProductId,
                reason,
                description,
            });
            setShowModal(false);
            setDescription('');
            loadData();
            setFeedback({
                type: 'success',
                text: 'Solicitud de devolución enviada. Te avisaremos cuando la tienda la revise.',
            });
        } catch (err: any) {
            setFeedback({
                type: 'error',
                text: err.response?.data?.message || 'No se pudo enviar la solicitud de devolución.',
            });
        } finally {
            setSubmitting(false);
        }
    };

    const selectedOrder = orders.find((o) => o.id === selectedOrderId);

    return (
        <CustomerAccountLayout
            title="Devoluciones & Garantías (RMA)"
            description="Gestiona reclamos, cambios por garantía y solicitudes de reembolso de tus compras."
        >
            {loadError && <PortalLoadError />}
            <PortalActionFeedback feedback={feedback} />

            <Head title="Devoluciones - OwOMarket" />

            <div className="mb-6 flex items-center justify-between">
                <h3 className="flex items-center gap-2 text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                    <HiOutlineArrowPathRoundedSquare className="h-5 w-5 text-blue-600" />
                    Mis Reclamos ({returns.length})
                </h3>
                <Button color="primary" size="sm" onClick={solicitarDevolucion}>
                    <HiOutlinePlus className="mr-1.5 h-4 w-4" />
                    Nueva Devolución
                </Button>
            </div>

            {!loading && returns.length === 0 ? (
                <Card>
                    <div data-testid="returns-vacio" className="py-6 text-center">
                        <HiOutlineArrowPathRoundedSquare className="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-700" />
                        <h4 className="mb-1 text-base font-bold text-gray-900 dark:text-white">
                            No tienes devoluciones en curso
                        </h4>
                        <p className="mb-6 text-xs text-gray-500 dark:text-gray-400">
                            Si tuviste algún inconveniente con un producto recibido, puedes iniciar un reclamo aquí.
                        </p>
                        <Button color="primary" size="sm" className="mx-auto w-fit" onClick={solicitarDevolucion}>
                            Solicitar Devolución
                        </Button>
                    </div>
                </Card>
            ) : (
                <div className="space-y-4">
                    {returns.map((ret) => {
                        const insignia = INSIGNIA[ret.status] ?? INSIGNIA.requested;
                        const Icono = insignia.Icono;
                        const resolucion = comoSeResolvio(ret.resolved_by);
                        const cerrada = ret.status !== 'requested' && ret.status !== 'in_review';

                        return (
                            <Card key={ret.id} data-testid={`reclamo-${ret.id}`}>
                                <div className="flex flex-col gap-2 border-b border-gray-100 pb-3 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-xs font-bold text-gray-900 dark:text-white">
                                                Orden: {ret.order_number}
                                            </span>
                                            <Badge color={insignia.color} size="xs" icon={Icono}>
                                                {insignia.texto}
                                            </Badge>
                                        </div>
                                        <span className="text-[11px] text-gray-400">
                                            Solicitado el: {ret.created_at ? ret.created_at.substring(0, 10) : 'Reciente'}
                                        </span>
                                    </div>
                                </div>

                                <div className="py-1">
                                    <h4 className="mb-1 text-xs font-bold text-gray-900 dark:text-white">
                                        Producto: {ret.product_name}
                                    </h4>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">
                                        <strong>Motivo:</strong> {ret.reason}
                                    </p>
                                    <p className="mt-1 text-xs italic text-gray-500 dark:text-gray-400">
                                        "{ret.description}"
                                    </p>

                                    {/*
                                      * El estado en palabras. Antes solo estaba el color de la
                                      * insignia, y un color no le dice a nadie si aun tiene que
                                      * esperar o si el asunto ya termino.
                                      */}
                                    <p
                                        data-testid={`estado-${ret.id}`}
                                        className="mt-3 text-xs font-bold text-gray-700 dark:text-gray-300"
                                    >
                                        {explicacionDe(ret.status, ret.resolved_by)}
                                    </p>

                                    {cerrada && resolucion && (
                                        <p data-testid={`resolucion-${ret.id}`} className="mt-1 text-xs text-gray-500">
                                            {resolucion}
                                        </p>
                                    )}

                                    {/*
                                      * Lo que escribio la TIENDA al resolver. En un rechazo es el
                                      * motivo, y es lo unico que el comprador tendra para
                                      * entender la decision. Va aparte de `admin_notes` porque
                                      * son dos voces distintas: fundirlas dejaria al comprador
                                      * sin saber quien le contesto.
                                      */}
                                    {ret.resolution_notes && (
                                        <div
                                            data-testid={`notas-tienda-${ret.id}`}
                                            className="mt-3 rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-300"
                                        >
                                            <strong>Respuesta de la tienda:</strong> {ret.resolution_notes}
                                        </div>
                                    )}

                                    {ret.admin_notes && (
                                        <div className="mt-3 rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300">
                                            <strong>Respuesta del Soporte:</strong> {ret.admin_notes}
                                        </div>
                                    )}
                                </div>
                            </Card>
                        );
                    })}
                </div>
            )}

            {/* Aviso de cédula: sustituye al formulario, no lo acompaña. */}
            <Modal show={showCedulaAviso} onClose={() => setShowCedulaAviso(false)} size="md">
                <ModalHeader>Necesitamos tu cédula primero</ModalHeader>
                <ModalBody>
                    <div data-testid="aviso-cedula" className="flex gap-3 text-xs text-gray-600 dark:text-gray-300">
                        <HiOutlineIdentification className="h-6 w-6 shrink-0 text-blue-600" />
                        <p>
                            Para abrir una reclamación necesitamos tu cédula en el perfil. Una reclamación puede acabar
                            moviendo dinero, y eso no funciona con alguien sin identificar. Es un minuto y solo hay que
                            hacerlo una vez.
                        </p>
                    </div>
                </ModalBody>
                <ModalFooter>
                    <Button as={Link} href="/account/profile" color="primary" size="sm">
                        Ir a mi perfil
                    </Button>
                    <Button color="subtle" size="sm" onClick={() => setShowCedulaAviso(false)}>
                        Ahora no
                    </Button>
                </ModalFooter>
            </Modal>

            <Modal show={showModal} onClose={() => setShowModal(false)} size="lg">
                <ModalHeader>Solicitar Devolución de Producto</ModalHeader>
                <form onSubmit={handleCreateReturn}>
                    <ModalBody>
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="return-order">Selecciona el Pedido</Label>
                                <Select
                                    id="return-order"
                                    value={selectedOrderId}
                                    onChange={(e) => {
                                        setSelectedOrderId(e.target.value);
                                        setSelectedProductId('');
                                    }}
                                    required
                                >
                                    <option value="">Selecciona una orden entregada...</option>
                                    {orders.map((o) => (
                                        <option key={o.id} value={o.id}>
                                            {o.order_number} (${o.total.toFixed(2)})
                                        </option>
                                    ))}
                                </Select>
                            </div>

                            {selectedOrder && selectedOrder.items && (
                                <div>
                                    <Label htmlFor="return-product">Producto a Devolver</Label>
                                    <Select
                                        id="return-product"
                                        value={selectedProductId}
                                        onChange={(e) => setSelectedProductId(e.target.value)}
                                        required
                                    >
                                        <option value="">Selecciona un producto del pedido...</option>
                                        {selectedOrder.items.map((item) => (
                                            <option key={item.id} value={item.product_id}>
                                                {item.product_name} (${item.price.toFixed(2)})
                                            </option>
                                        ))}
                                    </Select>
                                </div>
                            )}

                            <div>
                                <Label htmlFor="return-reason">Motivo del Reclamo</Label>
                                <Select
                                    id="return-reason"
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    required
                                >
                                    {MOTIVOS.map((m) => (
                                        <option key={m} value={m}>
                                            {m}
                                        </option>
                                    ))}
                                </Select>
                            </div>

                            <div>
                                <Label htmlFor="return-description">Explicación Detallada</Label>
                                <Textarea
                                    id="return-description"
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    required
                                    rows={3}
                                    placeholder="Describe qué ocurrió con el producto y qué solución solicitas (cambio o reembolso)..."
                                />
                            </div>
                        </div>
                    </ModalBody>
                    <ModalFooter>
                        <Button type="submit" color="primary" size="sm" disabled={submitting}>
                            {submitting ? 'Enviando...' : 'Enviar Reclamo'}
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

export default CustomerReturnsPage;
