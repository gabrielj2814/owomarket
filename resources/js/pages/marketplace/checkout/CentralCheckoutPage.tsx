import React, { useEffect, useRef, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CentralLayout from '@/components/layouts/CentralLayout';
import { useCentralCart } from '@/contexts/CentralCartContext';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CentralMarketplaceServices, {
    CreateCentralOrderPayload,
} from '@/Services/CentralMarketplaceServices';
import {
    HiOutlineShoppingBag,
    HiOutlineBuildingStorefront,
    HiOutlineShieldCheck,
    HiOutlineDevicePhoneMobile,
    HiOutlineCurrencyDollar,
    HiOutlineLockClosed,
    HiOutlineArrowPath,
    HiOutlineCheckCircle,
} from 'react-icons/hi2';

import { getActiveExchangeRate } from '@/Services/ExchangeRateServices';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';

interface CentralCheckoutPageProps {
    domain?: string;
}

const CentralCheckoutPageContent: React.FC<CentralCheckoutPageProps> = ({ domain }) => {
    const { items, getItemsByStore, getSubtotal, getItemCount, clearCart } = useCentralCart();
    const { customer, isAuthenticated, openAuthModal } = useCustomerAuth();

    const [bcvRate, setBcvRate] = useState<number>(775.3356);

    useEffect(() => {
        getActiveExchangeRate()
            .then((res) => {
                if (res?.data?.rate && res.data.rate > 0) {
                    setBcvRate(res.data.rate);
                }
            })
            .catch(() => {});
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
        typeof crypto !== 'undefined' && 'randomUUID' in crypto
            ? crypto.randomUUID()
            : `ck-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`
    );

    const totalBs = (subtotal * bcvRate).toFixed(2);

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
            items: items.map(i => ({
                tenant_id: i.tenant_id,
                product_id: i.product_id,
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
                'Tu pedido se registro correctamente, pero no pudimos abrir la pagina de confirmacion. NO vuelvas a pagar: revisa tus pedidos en tu cuenta.'
            );
            setSubmitting(false);
        } else {
            setErrorMsg(res.message || 'Error al procesar la orden unificada');
            setSubmitting(false);
        }
    };

    if (items.length === 0) {
        return (
            <div className="text-center py-24 space-y-4">
                <div className="w-16 h-16 bg-gray-100 dark:bg-gray-800 text-gray-400 rounded-full flex items-center justify-center mx-auto">
                    <HiOutlineShoppingBag className="w-8 h-8" />
                </div>
                <h2 className="text-xl font-bold text-gray-900 dark:text-white">Tu carrito está vacío</h2>
                <p className="text-xs text-gray-500">Agrega productos antes de realizar el checkout.</p>
                <Link
                    href="/marketplace"
                    className="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white font-bold rounded-xl text-xs"
                >
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
                <div className="border-b border-gray-200 dark:border-gray-800 pb-4">
                    <h1 className="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <HiOutlineLockClosed className="w-7 h-7 text-blue-600" />
                        Checkout Unificado Multi-Tienda
                    </h1>
                    <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Completa tus datos una sola vez. Nosotros nos encargamos de dividir y despachar cada paquete con su respectiva tienda.
                    </p>
                </div>

                {errorMsg && (
                    <div className="p-4 rounded-2xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-xs font-semibold">
                        {errorMsg}
                    </div>
                )}

                <form onSubmit={handleSubmitOrder} className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    {/* Left Forms Column */}
                    <div className="lg:col-span-7 space-y-6">
                        {/* 1. Customer Information */}
                        <div className="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4 shadow-sm">
                            <div className="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                                <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span className="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] font-black flex items-center justify-center">
                                        1
                                    </span>
                                    Información del Comprador
                                </h3>

                                {!isAuthenticated && (
                                    <button
                                        type="button"
                                        onClick={() => openAuthModal()}
                                        className="text-xs text-blue-600 font-bold hover:underline"
                                    >
                                        ¿Tienes cuenta OwO Pass? Inicia sesión
                                    </button>
                                )}
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Nombre y Apellido *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={name}
                                        onChange={e => setName(e.target.value)}
                                        placeholder="Ej: Gabriel Martínez"
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Correo Electrónico *
                                    </label>
                                    <input
                                        type="email"
                                        required
                                        value={email}
                                        onChange={e => setEmail(e.target.value)}
                                        placeholder="gabriel@ejemplo.com"
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Teléfono Celular
                                    </label>
                                    <input
                                        type="tel"
                                        value={phone}
                                        onChange={e => setPhone(e.target.value)}
                                        placeholder="0412-1234567"
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Cédula / DNI
                                    </label>
                                    <input
                                        type="text"
                                        value={documentId}
                                        onChange={e => setDocumentId(e.target.value)}
                                        placeholder="V-12345678"
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* 2. Shipping Address */}
                        <div className="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-4 shadow-sm">
                            <div className="border-b border-gray-100 dark:border-gray-800 pb-3">
                                <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span className="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] font-black flex items-center justify-center">
                                        2
                                    </span>
                                    Dirección de Entrega
                                </h3>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="sm:col-span-2 space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Dirección Exacta *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={address}
                                        onChange={e => setAddress(e.target.value)}
                                        placeholder="Av. Principal, Edificio, Apartamento..."
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Ciudad *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={city}
                                        onChange={e => setCity(e.target.value)}
                                        placeholder="Caracas"
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Estado / Región
                                    </label>
                                    <input
                                        type="text"
                                        value={state}
                                        onChange={e => setState(e.target.value)}
                                        placeholder="Miranda"
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                </div>

                                <div className="sm:col-span-2 space-y-1">
                                    <label className="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Notas de entrega (Opcional)
                                    </label>
                                    <textarea
                                        value={notes}
                                        onChange={e => setNotes(e.target.value)}
                                        rows={2}
                                        placeholder="Punto de referencia o instrucciones especiales..."
                                        className="w-full px-3 py-2 text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* 3. Payment Gateway Selection */}
                        <div className="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-5 shadow-sm">
                            <div className="border-b border-gray-100 dark:border-gray-800 pb-3">
                                <h3 className="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span className="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] font-black flex items-center justify-center">
                                        3
                                    </span>
                                    Método de Pago Central
                                </h3>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {/* Pago Móvil Option */}
                                <button
                                    type="button"
                                    onClick={() => setPaymentMethod('pago_movil')}
                                    className={`p-4 rounded-2xl border text-left transition flex items-start gap-3 ${
                                        paymentMethod === 'pago_movil'
                                            ? 'border-blue-600 bg-blue-50/50 dark:bg-blue-900/30 ring-2 ring-blue-500/20'
                                            : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'
                                    }`}
                                >
                                    <div className="p-2.5 rounded-xl bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300">
                                        <HiOutlineDevicePhoneMobile className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-gray-900 dark:text-white">Pago Móvil</h4>
                                        <p className="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                            Transferencia instantánea en Bs. con tasa BCV.
                                        </p>
                                    </div>
                                </button>

                                {/* Binance Pay Option */}
                                <button
                                    type="button"
                                    onClick={() => setPaymentMethod('binance_pay')}
                                    className={`p-4 rounded-2xl border text-left transition flex items-start gap-3 ${
                                        paymentMethod === 'binance_pay'
                                            ? 'border-yellow-500 bg-yellow-50/50 dark:bg-yellow-900/30 ring-2 ring-yellow-500/20'
                                            : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'
                                    }`}
                                >
                                    <div className="p-2.5 rounded-xl bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-300">
                                        <HiOutlineCurrencyDollar className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h4 className="text-xs font-bold text-gray-900 dark:text-white">Binance Pay</h4>
                                        <p className="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                            Pagos instantáneos con USDT y QR.
                                        </p>
                                    </div>
                                </button>
                            </div>

                            {/* Gateway Specific Form Details */}
                            {paymentMethod === 'pago_movil' ? (
                                <div className="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 space-y-4">
                                    <div className="space-y-1 text-xs">
                                        <span className="font-bold text-blue-600 dark:text-blue-400">Datos Oficiales para Pago Móvil:</span>
                                        <div className="grid grid-cols-2 gap-2 p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 font-mono text-[11px]">
                                            <div><strong>Banco:</strong> Banesco (0134)</div>
                                            <div><strong>C.I.:</strong> J-501234567</div>
                                            <div><strong>Teléfono:</strong> 0412-9998877</div>
                                            <div><strong>Monto:</strong> Bs. {totalBs}</div>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                                Banco Emisor
                                            </label>
                                            <input
                                                type="text"
                                                value={bankOrigin}
                                                onChange={e => setBankOrigin(e.target.value)}
                                                placeholder="Mercantil, Banesco..."
                                                className="w-full px-3 py-2 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                                Teléfono Emisor
                                            </label>
                                            <input
                                                type="text"
                                                value={phoneOrigin}
                                                onChange={e => setPhoneOrigin(e.target.value)}
                                                placeholder="0414-0000000"
                                                className="w-full px-3 py-2 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                                Nro. Referencia *
                                            </label>
                                            <input
                                                type="text"
                                                required
                                                value={referenceNumber}
                                                onChange={e => setReferenceNumber(e.target.value)}
                                                placeholder="Últimos 6 u 8 dígitos"
                                                className="w-full px-3 py-2 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg"
                                            />
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 space-y-4">
                                    <div className="space-y-1 text-xs">
                                        <span className="font-bold text-yellow-600 dark:text-yellow-400">Datos Oficiales Binance Pay:</span>
                                        <div className="p-3 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 font-mono text-[11px] space-y-1">
                                            <div><strong>Binance Pay ID:</strong> 88992211 (OwOMarket Central)</div>
                                            <div><strong>Monto en USDT:</strong> ${subtotal.toFixed(2)} USDT</div>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                                Tu Binance ID / Nickname
                                            </label>
                                            <input
                                                type="text"
                                                value={binanceId}
                                                onChange={e => setBinanceId(e.target.value)}
                                                placeholder="Ej: 123456789"
                                                className="w-full px-3 py-2 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                                Order ID / Tx Hash *
                                            </label>
                                            <input
                                                type="text"
                                                required
                                                value={transactionHash}
                                                onChange={e => setTransactionHash(e.target.value)}
                                                placeholder="Código de transacción Binance"
                                                className="w-full px-3 py-2 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg"
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Summary Column */}
                    <div className="lg:col-span-5 space-y-6">
                        <div className="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-6 shadow-sm sticky top-24">
                            <h3 className="text-base font-black text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-3">
                                Desglose de Paquetes ({storeGroups.length} {storeGroups.length === 1 ? 'Tienda' : 'Tiendas'})
                            </h3>

                            {/* Store items list */}
                            <div className="space-y-4 max-h-80 overflow-y-auto pr-1">
                                {storeGroups.map(group => (
                                    <div
                                        key={group.tenant_id}
                                        className="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 space-y-2"
                                    >
                                        <div className="flex items-center justify-between text-xs font-bold text-gray-900 dark:text-white border-b border-gray-200/50 dark:border-gray-700/50 pb-1.5">
                                            <span className="flex items-center gap-1.5">
                                                <HiOutlineBuildingStorefront className="w-3.5 h-3.5 text-purple-600" />
                                                {group.tenant_name}
                                            </span>
                                            <span>${group.subtotal.toFixed(2)}</span>
                                        </div>
                                        <div className="space-y-1.5 text-[11px] text-gray-600 dark:text-gray-300">
                                            {group.items.map(item => (
                                                <div key={item.id} className="flex justify-between">
                                                    <span className="truncate max-w-[200px]">
                                                        {item.quantity}x {item.product_name}
                                                    </span>
                                                    <span className="font-semibold">
                                                        ${(item.price * item.quantity).toFixed(2)}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Totals */}
                            <div className="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-800 text-xs">
                                <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>Subtotal ({totalCount} items):</span>
                                    <span className="font-bold text-gray-900 dark:text-white">${subtotal.toFixed(2)} USD</span>
                                </div>
                                <div className="pt-2 border-t border-gray-100 dark:border-gray-800">
                                    <span className="text-xs font-bold text-gray-900 dark:text-white block uppercase tracking-wider mb-1">
                                        Total a Pagar:
                                    </span>
                                    <CurrencyPriceDisplay
                                        priceUsd={subtotal}
                                        exchangeRate={bcvRate}
                                        size="lg"
                                        showVes={true}
                                        showUsdt={true}
                                        showBcvLabel={true}
                                    />
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={submitting}
                                className="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-sm shadow-lg shadow-blue-500/20 disabled:opacity-50 transition flex items-center justify-center gap-2"
                            >
                                {submitting ? (
                                    <>
                                        <HiOutlineArrowPath className="w-5 h-5 animate-spin" />
                                        <span>Procesando Orden...</span>
                                    </>
                                ) : (
                                    <>
                                        <HiOutlineCheckCircle className="w-5 h-5" />
                                        <span>Confirmar y Pagar Orden</span>
                                    </>
                                )}
                            </button>

                            <div className="flex items-center justify-center gap-2 text-[11px] text-gray-400 text-center">
                                <HiOutlineShieldCheck className="w-4 h-4 text-green-500 inline" />
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
