import CentralLayout from '@/components/layouts/CentralLayout';
import { useCentralCart } from '@/contexts/CentralCartContext';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CentralMarketplaceServices, { CentralOrderQuote, CreateCentralOrderPayload } from '@/Services/CentralMarketplaceServices';
import { Head, Link } from '@inertiajs/react';
import React, { useEffect, useRef, useState } from 'react';
import {
    HiOutlineArrowPath,
    HiOutlineBuildingStorefront,
    HiOutlineCheckCircle,
    HiOutlineCurrencyDollar,
    HiOutlineDevicePhoneMobile,
    HiOutlineLockClosed,
    HiOutlineShieldCheck,
    HiOutlineShoppingBag,
} from 'react-icons/hi2';

import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';
import { getSharedActiveRate } from '@/Services/ExchangeRateServices';

export interface CentralPaymentMethod {
    id: 'pago_movil' | 'binance_pay';
    name: string;
    bank_name?: string;
    document_id?: string;
    phone?: string;
    holder_name?: string | null;
    exchange_rate_ves?: number;
    binance_pay_id?: string;
    crypto_currency?: string;
}

interface CentralCheckoutPageProps {
    domain?: string;
    /**
     * Datos de cobro de la plataforma. Vienen del servidor desde `central_settings`; un
     * metodo que no este completamente configurado sencillamente no llega aqui.
     */
    payment_methods?: CentralPaymentMethod[];
}

const CentralCheckoutPageContent: React.FC<CentralCheckoutPageProps> = ({ domain, payment_methods = [] }) => {
    const pagoMovil = payment_methods.find((m) => m.id === 'pago_movil');
    const { items, getItemsByStore, getSubtotal, getItemCount, clearCart } = useCentralCart();
    const { customer, isAuthenticated, openAuthModal } = useCustomerAuth();

    /**
     * Hallazgo G9: esto era `useState<number>(775.3356)` con un `.catch(() => {})`
     * silencioso, y **nada bloqueaba el envio mientras la tasa no habia cargado**. El
     * comprador podia confirmar un pago en bolivares calculado con una tasa inventada, y
     * transferir un importe que no se correspondia con nada.
     *
     * Ahora arranca en `null` —no hay tasa hasta que el servidor la da— y el boton de
     * confirmar espera. La peticion es la compartida de `getSharedActiveRate` (G13), asi
     * que esta pagina no anade una propia.
     */
    const [bcvRate, setBcvRate] = useState<number | null>(null);
    const [rateFailed, setRateFailed] = useState(false);

    useEffect(() => {
        let isMounted = true;

        void getSharedActiveRate().then((tasa) => {
            if (!isMounted) return;

            if (tasa !== null) {
                setBcvRate(tasa);
            } else {
                setRateFailed(true);
            }
        });

        return () => {
            isMounted = false;
        };
    }, []);

    const storeGroups = getItemsByStore();
    const subtotal = getSubtotal();

    const totalCount = getItemCount();

    // Customer Form
    const [name, setName] = useState(customer?.name || '');
    const [email, setEmail] = useState(customer?.email || '');
    const [phone, setPhone] = useState(customer?.phone || '');
    const [documentId, setDocumentId] = useState('');

    // Shipping Form
    const [address, setAddress] = useState('');
    const [city, setCity] = useState('');
    const [state, setState] = useState('');

    /**
     * Hallazgos N34 y N28: la pantalla mostraba el **subtotal puro como total**, sin envío
     * ni impuestos, así que el importe que el comprador transfería no coincidía con el que
     * se registraba. El presupuesto lo calcula el servidor —cada tienda con sus propias
     * tarifas y cupones— y aquí sólo se muestra.
     */
    const [quote, setQuote] = useState<CentralOrderQuote | null>(null);
    // Hallazgo N28: un codigo por tienda. Los cupones viven en la base de cada inquilino,
    // asi que un codigo solo puede descontar las lineas de la tienda que lo emitio.
    const [coupons, setCoupons] = useState<Record<string, string>>({});

    useEffect(() => {
        if (items.length === 0) return;

        void CentralMarketplaceServices.quote({
            items: items.map((i) => ({
                tenant_id: i.tenant_id,
                product_id: i.product_id,
                // Hallazgo N36: el presupuesto tiene que salir del precio de la variante
                // elegida, no del padre.
                variant_id: i.variant_id ?? null,
                quantity: i.quantity,
            })),
            shipping_address: { city, state },
            coupons,
        }).then((res) => {
            if (res.code === 200 && res.data) setQuote(res.data);
        });
        // Se recalcula si cambia el carrito o el destino: el envío depende de ambos.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [items, city, state, coupons]);

    const totalAPagar = quote?.total ?? subtotal;
    const [notes, setNotes] = useState('');

    // Payment method
    const [paymentMethod, setPaymentMethod] = useState<'pago_movil' | 'binance_pay'>('pago_movil');

    // Pago Móvil details
    const [bankOrigin, setBankOrigin] = useState('');
    const [phoneOrigin, setPhoneOrigin] = useState('');
    const [referenceNumber, setReferenceNumber] = useState('');

    // Binance Pay details
    const [binanceId, setBinanceId] = useState('');
    const [transactionHash, setTransactionHash] = useState('');

    // Submission states
    const [submitting, setSubmitting] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    /**
     * Clave de idempotencia del intento de compra (hallazgo C2).
     *
     * Se genera una sola vez al montar la página y NO cambia entre reintentos:
     * ese es justamente el punto. Si el primer envío creó el pedido pero falló
     * el despacho a alguna tienda, reintentar con la misma clave devuelve el
     * pedido existente en lugar de crear otro con sus comisiones duplicadas.
     */
    const idempotencyKeyRef = useRef<string>(
        typeof crypto !== 'undefined' && 'randomUUID' in crypto ? crypto.randomUUID() : `ck-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`,
    );

    const totalBs = bcvRate !== null ? (totalAPagar * bcvRate).toFixed(2) : null;

    const handleSubmitOrder = async (e: React.FormEvent) => {
        e.preventDefault();
        setErrorMsg(null);

        if (items.length === 0) {
            setErrorMsg('El carrito está vacío. Agrega productos antes de pagar.');
            return;
        }

        if (!name || !email) {
            setErrorMsg('Por favor completa tu nombre y correo electrónico.');
            return;
        }

        if (!address || !city) {
            setErrorMsg('Por favor ingresa tu dirección y ciudad de entrega.');
            return;
        }

        if (paymentMethod === 'pago_movil' && !referenceNumber) {
            setErrorMsg('Por favor ingresa el número de referencia del Pago Móvil.');
            return;
        }

        if (paymentMethod === 'binance_pay' && !transactionHash) {
            setErrorMsg('Por favor ingresa el Order ID o Hash de transacción de Binance Pay.');
            return;
        }

        setSubmitting(true);

        const payload: CreateCentralOrderPayload = {
            customer: {
                central_uuid: customer?.id,
                name,
                email,
                phone,
                document_id: documentId,
            },
            shipping_address: {
                address,
                city,
                state,
                notes,
            },
            payment_method: paymentMethod,
            payment_details:
                paymentMethod === 'pago_movil'
                    ? {
                          bank_origin: bankOrigin,
                          phone_origin: phoneOrigin,
                          reference_number: referenceNumber,
                          rate_bcv: bcvRate,
                          total_bs: totalBs,
                      }
                    : {
                          binance_id: binanceId,
                          transaction_hash: transactionHash,
                          crypto_currency: 'USDT',
                      },
            currency: 'USD',
            idempotency_key: idempotencyKeyRef.current,
            // Hallazgo N28: los cupones son de tienda, uno por tienda como maximo. El
            // servidor los revalida y los consume; esto solo dice cual se intento aplicar.
            coupons: coupons,
            items: items.map((i) => ({
                tenant_id: i.tenant_id,
                product_id: i.product_id,
                variant_id: i.variant_id ?? null,
                product_name: i.product_name,
                sku: i.sku || undefined,
                // El servidor ignora este precio y resuelve el real contra el
                // catálogo central (hallazgo B1); se sigue enviando sólo para
                // no romper el tipo del payload.
                price: i.price,
                quantity: i.quantity,
                attributes: i.attributes,
            })),
        };

        const res = await CentralMarketplaceServices.createUnifiedOrder(payload);

        // Hallazgo G11: se navegaba a `res.data.redirect_url` sin comprobar que existiera,
        // con el carrito ya vaciado. Si faltaba, el comprador acababa en `/undefined` sin
        // carrito y sin numero de pedido.
        if (res.status === 'success' && res.data?.redirect_url) {
            clearCart();
            window.location.href = res.data.redirect_url;
        } else if (res.status === 'success') {
            clearCart();
            setErrorMsg(
                'Tu pedido se registro correctamente, pero no pudimos abrir la pagina de confirmacion. NO vuelvas a pagar: revisa tus pedidos en tu cuenta.',
            );
            setSubmitting(false);
        } else {
            setErrorMsg(res.message || 'Error al procesar la orden unificada');
            setSubmitting(false);
        }
    };

    if (items.length === 0) {
        return (
            <div className="space-y-4 py-24 text-center">
                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
                    <HiOutlineShoppingBag className="h-8 w-8" />
                </div>
                <h2 className="text-xl font-bold text-gray-900 dark:text-white">Tu carrito está vacío</h2>
                <p className="text-xs text-gray-500">Agrega productos antes de realizar el checkout.</p>
                <Link href="/marketplace" className="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white">
                    Explorar Catálogo
                </Link>
            </div>
        );
    }

    return (
        <>
            <Head title="Checkout Unificado Multi-Tienda - OwOMarket Central" />

            <div className="space-y-8">
                {/* Header */}
                <div className="border-b border-gray-200 pb-4 dark:border-gray-800">
                    <h1 className="flex items-center gap-2 text-2xl font-black text-gray-900 sm:text-3xl dark:text-white">
                        <HiOutlineLockClosed className="h-7 w-7 text-blue-600" />
                        Checkout Unificado Multi-Tienda
                    </h1>
                    <p className="mt-1 text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                        Completa tus datos una sola vez. Nosotros nos encargamos de dividir y despachar cada paquete con su respectiva tienda.
                    </p>
                </div>

                {errorMsg && (
                    <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-semibold text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                        {errorMsg}
                    </div>
                )}

                <form onSubmit={handleSubmitOrder} className="grid grid-cols-1 gap-8 lg:grid-cols-12">
                    {/* Left Forms Column */}
                    <div className="space-y-6 lg:col-span-7">
                        {/* 1. Customer Information */}
                        <div className="space-y-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div className="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                                <h3 className="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                                    <span className="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[10px] font-black text-white">
                                        1
                                    </span>
                                    Información del Comprador
                                </h3>

                                {!isAuthenticated && (
                                    <button type="button" onClick={() => openAuthModal()} className="text-xs font-bold text-blue-600 hover:underline">
                                        ¿Tienes cuenta OwO Pass? Inicia sesión
                                    </button>
                                )}
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">Nombre y Apellido *</label>
                                    <input
                                        type="text"
                                        required
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        placeholder="Ej: Gabriel Martínez"
                                        className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">Correo Electrónico *</label>
                                    <input
                                        type="email"
                                        required
                                        value={email}
                                        onChange={(e) => setEmail(e.target.value)}
                                        placeholder="gabriel@ejemplo.com"
                                        className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">Teléfono Celular</label>
                                    <input
                                        type="tel"
                                        value={phone}
                                        onChange={(e) => setPhone(e.target.value)}
                                        placeholder="0412-1234567"
                                        className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">Cédula / DNI</label>
                                    <input
                                        type="text"
                                        value={documentId}
                                        onChange={(e) => setDocumentId(e.target.value)}
                                        placeholder="V-12345678"
                                        className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* 2. Shipping Address */}
                        <div className="space-y-4 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div className="border-b border-gray-100 pb-3 dark:border-gray-800">
                                <h3 className="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                                    <span className="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[10px] font-black text-white">
                                        2
                                    </span>
                                    Dirección de Entrega
                                </h3>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-1 sm:col-span-2">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">Dirección Exacta *</label>
                                    <input
                                        type="text"
                                        required
                                        value={address}
                                        onChange={(e) => setAddress(e.target.value)}
                                        placeholder="Av. Principal, Edificio, Apartamento..."
                                        className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">Ciudad *</label>
                                    <input
                                        type="text"
                                        required
                                        value={city}
                                        onChange={(e) => setCity(e.target.value)}
                                        placeholder="Caracas"
                                        className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">Estado / Región</label>
                                    <input
                                        type="text"
                                        value={state}
                                        onChange={(e) => setState(e.target.value)}
                                        placeholder="Miranda"
                                        className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1 sm:col-span-2">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">Notas de entrega (Opcional)</label>
                                    <textarea
                                        value={notes}
                                        onChange={(e) => setNotes(e.target.value)}
                                        rows={2}
                                        placeholder="Punto de referencia o instrucciones especiales..."
                                        className="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* 3. Payment Gateway Selection */}
                        <div className="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div className="border-b border-gray-100 pb-3 dark:border-gray-800">
                                <h3 className="flex items-center gap-2 text-sm font-bold text-gray-900 dark:text-white">
                                    <span className="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[10px] font-black text-white">
                                        3
                                    </span>
                                    Método de Pago Central
                                </h3>
                            </div>

                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                {/* Pago Móvil Option */}
                                <button
                                    type="button"
                                    onClick={() => setPaymentMethod('pago_movil')}
                                    className={`flex items-start gap-3 rounded-2xl border p-4 text-left transition ${
                                        paymentMethod === 'pago_movil'
                                            ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-500/20 dark:bg-blue-900/30'
                                            : 'border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800'
                                    }`}
                                >
                                    <div className="rounded-xl bg-blue-100 p-2.5 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
                                        <HiOutlineDevicePhoneMobile className="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-gray-900 dark:text-white">Pago Móvil</h4>
                                        <p className="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">
                                            Transferencia instantánea en Bs. con tasa BCV.
                                        </p>
                                    </div>
                                </button>

                                {/* Binance Pay Option */}
                                <button
                                    type="button"
                                    onClick={() => setPaymentMethod('binance_pay')}
                                    className={`flex items-start gap-3 rounded-2xl border p-4 text-left transition ${
                                        paymentMethod === 'binance_pay'
                                            ? 'border-yellow-500 bg-yellow-50/50 ring-2 ring-yellow-500/20 dark:bg-yellow-900/30'
                                            : 'border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800'
                                    }`}
                                >
                                    <div className="rounded-xl bg-yellow-100 p-2.5 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-300">
                                        <HiOutlineCurrencyDollar className="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-gray-900 dark:text-white">Binance Pay</h4>
                                        <p className="mt-0.5 text-[10px] text-gray-500 dark:text-gray-400">Pagos instantáneos con USDT y QR.</p>
                                    </div>
                                </button>
                            </div>

                            {/* Gateway Specific Form Details */}
                            {paymentMethod === 'pago_movil' ? (
                                <div className="space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                                    <div className="space-y-1 text-xs">
                                        <span className="font-bold text-blue-600 dark:text-blue-400">Datos Oficiales para Pago Móvil:</span>
                                        {/* La Fase 0.5 (hallazgo G1) saco los datos de cobro de
                                            demostracion del checkout del inquilino, pero este se
                                            quedo con Banesco / J-501234567 / 0412-9998877
                                            incrustados: el comprador transferia a una cuenta que
                                            no era de nadie. Ahora salen de `central_settings`, y
                                            si no estan configurados no se muestra el panel. */}
                                        {pagoMovil ? (
                                            <div className="grid grid-cols-2 gap-2 rounded-lg border border-gray-200 bg-white p-3 font-mono text-[11px] dark:border-gray-700 dark:bg-gray-900">
                                                <div>
                                                    <strong>Banco:</strong> {pagoMovil.bank_name}
                                                </div>
                                                <div>
                                                    <strong>C.I./RIF:</strong> {pagoMovil.document_id}
                                                </div>
                                                <div>
                                                    <strong>Teléfono:</strong> {pagoMovil.phone}
                                                </div>
                                                <div>
                                                    <strong>Monto:</strong> {totalBs !== null ? `Bs. ${totalBs}` : 'calculando…'}
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-[11px] text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                                                Este método de pago no está disponible ahora mismo. Elige otro o vuelve a intentarlo más tarde.
                                            </div>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">Banco Emisor</label>
                                            <input
                                                type="text"
                                                value={bankOrigin}
                                                onChange={(e) => setBankOrigin(e.target.value)}
                                                placeholder="Mercantil, Banesco..."
                                                className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">Teléfono Emisor</label>
                                            <input
                                                type="text"
                                                value={phoneOrigin}
                                                onChange={(e) => setPhoneOrigin(e.target.value)}
                                                placeholder="0414-0000000"
                                                className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">Nro. Referencia *</label>
                                            <input
                                                type="text"
                                                required
                                                value={referenceNumber}
                                                onChange={(e) => setReferenceNumber(e.target.value)}
                                                placeholder="Últimos 6 u 8 dígitos"
                                                className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900"
                                            />
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                                    <div className="space-y-1 text-xs">
                                        <span className="font-bold text-yellow-600 dark:text-yellow-400">Datos Oficiales Binance Pay:</span>
                                        <div className="space-y-1 rounded-lg border border-gray-200 bg-white p-3 font-mono text-[11px] dark:border-gray-700 dark:bg-gray-900">
                                            <div>
                                                <strong>Binance Pay ID:</strong> 88992211 (OwOMarket Central)
                                            </div>
                                            <div>
                                                <strong>Monto en USDT:</strong> ${totalAPagar.toFixed(2)} USDT
                                            </div>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                                Tu Binance ID / Nickname
                                            </label>
                                            <input
                                                type="text"
                                                value={binanceId}
                                                onChange={(e) => setBinanceId(e.target.value)}
                                                placeholder="Ej: 123456789"
                                                className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">Order ID / Tx Hash *</label>
                                            <input
                                                type="text"
                                                required
                                                value={transactionHash}
                                                onChange={(e) => setTransactionHash(e.target.value)}
                                                placeholder="Código de transacción Binance"
                                                className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900"
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Summary Column */}
                    <div className="space-y-6 lg:col-span-5">
                        <div className="sticky top-24 space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <h3 className="border-b border-gray-100 pb-3 text-base font-black text-gray-900 dark:border-gray-800 dark:text-white">
                                Desglose de Paquetes ({storeGroups.length} {storeGroups.length === 1 ? 'Tienda' : 'Tiendas'})
                            </h3>

                            {/* Store items list */}
                            <div className="max-h-80 space-y-4 overflow-y-auto pr-1">
                                {storeGroups.map((group) => (
                                    <div
                                        key={group.tenant_id}
                                        className="space-y-2 rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-800/50"
                                    >
                                        <div className="flex items-center justify-between border-b border-gray-200/50 pb-1.5 text-xs font-bold text-gray-900 dark:border-gray-700/50 dark:text-white">
                                            <span className="flex items-center gap-1.5">
                                                <HiOutlineBuildingStorefront className="h-3.5 w-3.5 text-purple-600" />
                                                {group.tenant_name}
                                            </span>
                                            <span>${group.subtotal.toFixed(2)}</span>
                                        </div>
                                        <div className="space-y-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                                            {group.items.map((item) => (
                                                <div key={item.id} className="flex justify-between">
                                                    <span className="max-w-[200px] truncate">
                                                        {item.quantity}x {item.product_name}
                                                    </span>
                                                    <span className="font-semibold">${(item.price * item.quantity).toFixed(2)}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Totals */}
                            <div className="space-y-3 border-t border-gray-200 pt-4 text-xs dark:border-gray-800">
                                <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>Subtotal ({totalCount} items):</span>
                                    <span className="font-bold text-gray-900 dark:text-white">${subtotal.toFixed(2)} USD</span>
                                </div>
                                {quote && quote.shipping > 0 && (
                                    <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>Envío:</span>
                                        <span className="font-bold text-gray-900 dark:text-white">${quote.shipping.toFixed(2)} USD</span>
                                    </div>
                                )}

                                {quote && quote.tax > 0 && (
                                    <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                        <span>Impuestos:</span>
                                        <span className="font-bold text-gray-900 dark:text-white">${quote.tax.toFixed(2)} USD</span>
                                    </div>
                                )}

                                {quote && quote.discount > 0 && (
                                    <div className="flex justify-between text-emerald-700 dark:text-emerald-400">
                                        <span>Descuento:</span>
                                        <span className="font-bold">−${quote.discount.toFixed(2)} USD</span>
                                    </div>
                                )}

                                <div className="border-t border-gray-100 pt-2 dark:border-gray-800">
                                    <span className="mb-1 block text-xs font-bold tracking-wider text-gray-900 uppercase dark:text-white">
                                        Total a Pagar:
                                    </span>
                                    <CurrencyPriceDisplay
                                        priceUsd={totalAPagar}
                                        exchangeRate={bcvRate ?? undefined}
                                        size="lg"
                                        showVes={true}
                                        showUsdt={true}
                                        showBcvLabel={true}
                                    />
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={submitting || bcvRate === null}
                                className="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-blue-500/20 transition hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50"
                            >
                                {submitting ? (
                                    <>
                                        <HiOutlineArrowPath className="h-5 w-5 animate-spin" />
                                        <span>Procesando Orden...</span>
                                    </>
                                ) : (
                                    <>
                                        <HiOutlineCheckCircle className="h-5 w-5" />
                                        <span>Confirmar y Pagar Orden</span>
                                    </>
                                )}
                            </button>

                            <div className="flex items-center justify-center gap-2 text-center text-[11px] text-gray-400">
                                <HiOutlineShieldCheck className="inline h-4 w-4 text-green-500" />
                                <span>Tus fondos están protegidos por el protocolo OwOMarket</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
};

const CentralCheckoutPage: React.FC<CentralCheckoutPageProps> = (props) => {
    return (
        <CentralLayout>
            <CentralCheckoutPageContent {...props} />
        </CentralLayout>
    );
};

export default CentralCheckoutPage;
