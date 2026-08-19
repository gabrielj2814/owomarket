import React, { useEffect, useMemo, useState } from 'react';
import StorefrontLayout from '@/components/layouts/StorefrontLayout';
import { useCart } from '@/contexts/CartContext';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CurrencyPriceDisplay from '@/components/ui/CurrencyPriceDisplay';
import StorefrontServices, { CreateStorefrontOrderPayload } from '@/Services/StorefrontServices';
import { StorefrontCheckoutPageProps } from '@/types/models/Storefront';
import {
    Alert,
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
    Modal,
    ModalBody,
    ModalHeader,
    Radio,
    Spinner,
    TextInput,
    Textarea,
} from 'flowbite-react';
import {
    HiArrowLeft,
    HiArrowRight,
    HiCheck,
    HiCheckCircle,
    HiCreditCard,
    HiHome,
    HiIdentification,
    HiInformationCircle,
    HiLockClosed,
    HiMail,
    HiOutlineShoppingBag,
    HiPhone,
    HiShieldCheck,
    HiTag,
    HiTruck,
    HiUser,
} from 'react-icons/hi';
import { FaBitcoin, FaCopy, FaMobileAlt, FaMoneyBillWave, FaQrcode, FaUniversity } from 'react-icons/fa';

function CheckoutPageContent({
    domain,
    store_settings,
    categories = [],
    shipping_methods = [],
    payment_methods = [],
    auth_user = null,
}: StorefrontCheckoutPageProps) {
    const { items, subtotal, discountAmount, total, coupon, clearCart, formatPrice } = useCart();
    const { customer, addresses, openAuthModal } = useCustomerAuth();

    // Steps: 1 = Contact & Customer, 2 = Shipping Address & Method, 3 = Payment & Login Gate
    const [currentStep, setCurrentStep] = useState<1 | 2 | 3>(1);

    // Form: Customer
    const [customerName, setCustomerName] = useState<string>(auth_user?.name || '');
    const [customerEmail, setCustomerEmail] = useState<string>(auth_user?.email || '');
    const [customerPhone, setCustomerPhone] = useState<string>('');
    const [customerDoc, setCustomerDoc] = useState<string>('');

    // Form: Shipping Address
    const [address, setAddress] = useState<string>('');
    const [city, setCity] = useState<string>('Santiago');
    const [stateRegion, setStateRegion] = useState<string>('Región Metropolitana');
    const [zipCode, setZipCode] = useState<string>('');
    const [shippingNotes, setShippingNotes] = useState<string>('');

    // Auto-fill from CustomerAuthContext
    useEffect(() => {
        if (customer) {
            if (!customerName && customer.name) setCustomerName(customer.name);
            if (!customerEmail && customer.email) setCustomerEmail(customer.email);
            if (!customerPhone && customer.phone) setCustomerPhone(customer.phone);
            if (!customerDoc && customer.document_id) setCustomerDoc(customer.document_id);
        }
    }, [customer]);

    useEffect(() => {
        if (addresses && addresses.length > 0 && !address) {
            const defaultAddr = addresses.find((a) => a.is_default) || addresses[0];
            if (defaultAddr) {
                setAddress(defaultAddr.address);
                setCity(defaultAddr.city);
                if (defaultAddr.state) setStateRegion(defaultAddr.state);
                if (defaultAddr.zip_code) setZipCode(defaultAddr.zip_code);
            }
        }
    }, [addresses]);
    // Shipping & Payment selection
    const [selectedShippingMethodId, setSelectedShippingMethodId] = useState<string>(
        shipping_methods.length > 0 ? shipping_methods[0].id : 'standard'
    );
    const [selectedPaymentMethodId, setSelectedPaymentMethodId] = useState<string>(
        payment_methods.length > 0 ? payment_methods[0].id : 'bank_transfer'
    );

    // Form: Payment Details (Pago Móvil & Binance Pay)
    const [pagoMovilBank, setPagoMovilBank] = useState<string>('0102 - Banco de Venezuela');
    const [pagoMovilPhone, setPagoMovilPhone] = useState<string>('');
    const [pagoMovilRef, setPagoMovilRef] = useState<string>('');
    const [binanceId, setBinanceId] = useState<string>('');
    const [binanceTxHash, setBinanceTxHash] = useState<string>('');
    const [copyFeedback, setCopyFeedback] = useState<string | null>(null);

    const handleCopy = (text: string, label: string) => {
        navigator.clipboard.writeText(text);
        setCopyFeedback(`¡${label} copiado!`);
        setTimeout(() => setCopyFeedback(null), 3000);
    };

    // Auth gate modal state
    const [isAuthGateModalOpen, setIsAuthGateModalOpen] = useState<boolean>(false);
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    // Resolved shipping cost
    const selectedShippingMethod = useMemo(() => {
        return shipping_methods.find((s) => s.id === selectedShippingMethodId) || shipping_methods[0];
    }, [selectedShippingMethodId, shipping_methods]);

    const shippingCost = selectedShippingMethod ? selectedShippingMethod.price : 0;
    const finalGrandTotal = Math.max(0, subtotal - discountAmount + shippingCost);

    // Validations per step
    const isStep1Valid = customerName.trim() !== '' && customerEmail.trim() !== '';
    const isStep2Valid = address.trim() !== '' && city.trim() !== '';

    // Step navigation
    const handleProceedToStep2 = (e: React.FormEvent) => {
        e.preventDefault();
        if (!isStep1Valid) {
            setErrorMessage('Por favor ingresa tu nombre y correo electrónico para continuar.');
            return;
        }
        setErrorMessage(null);
        setCurrentStep(2);
    };

    const handleProceedToStep3 = (e: React.FormEvent) => {
        e.preventDefault();
        if (!isStep2Valid) {
            setErrorMessage('Por favor completa la dirección de despacho y la comuna / ciudad.');
            return;
        }
        setErrorMessage(null);

        // Check login gate condition
        if (!auth_user && !customer) {
            setIsAuthGateModalOpen(true);
            return;
        }

        setCurrentStep(3);
    };

    // Submit Order
    const handleConfirmOrder = async () => {
        if (items.length === 0) {
            setErrorMessage('El carrito de compras está vacío.');
            return;
        }

        if (selectedPaymentMethodId === 'pago_movil' && !pagoMovilRef.trim()) {
            setErrorMessage('Por favor ingresa el número de referencia del Pago Móvil realizado.');
            return;
        }

        if (selectedPaymentMethodId === 'binance_pay' && !binanceTxHash.trim()) {
            setErrorMessage('Por favor ingresa el Hash o ID de la transacción de Binance Pay.');
            return;
        }

        setIsSubmitting(true);
        setErrorMessage(null);

        let paymentDetails: Record<string, any> | undefined = undefined;
        if (selectedPaymentMethodId === 'pago_movil') {
            paymentDetails = {
                bank_origin: pagoMovilBank.trim(),
                phone_origin: (pagoMovilPhone || customerPhone).trim(),
                reference_number: pagoMovilRef.trim(),
            };
        } else if (selectedPaymentMethodId === 'binance_pay') {
            paymentDetails = {
                binance_id: binanceId.trim() || undefined,
                transaction_hash: binanceTxHash.trim(),
                crypto_currency: 'USDT',
            };
        }

        const payload: CreateStorefrontOrderPayload = {
            customer: {
                name: customerName.trim(),
                email: customerEmail.trim(),
                phone: customerPhone.trim() || undefined,
                document_id: customerDoc.trim() || undefined,
            },
            shipping_address: {
                address: address.trim(),
                city: city.trim(),
                state: stateRegion.trim() || undefined,
                zip: zipCode.trim() || undefined,
                notes: shippingNotes.trim() || undefined,
            },
            shipping_method: selectedShippingMethodId,
            shipping_amount: shippingCost,
            payment_method: selectedPaymentMethodId,
            payment_details: paymentDetails,
            coupon_code: coupon?.code || undefined,
            items: items.map((it) => ({
                product_id: it.productId,
                product_name: it.name,
                sku: it.sku || 'SKU-DEFAULT',
                price: it.price,
                quantity: it.quantity,
                variant_id: it.variantId,
                attributes: it.attributes,
            })),
        };

        try {
            const res = await StorefrontServices.createOrder(payload);

            if ((res.code === 201 || res.code === 200) && res.data?.redirect_url) {
                clearCart();
                window.location.href = res.data.redirect_url;
            } else {
                setErrorMessage(res.message || 'Error al procesar la orden. Por favor intenta de nuevo.');
            }
        } catch {
            setErrorMessage('Error de conexión con el servidor de pagos.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <>
            <div className="space-y-8 max-w-6xl mx-auto">
                {/* Breadcrumb */}
                <Breadcrumb>
                    <BreadcrumbItem href="/" icon={HiHome}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem href="/cart">Carrito</BreadcrumbItem>
                    <BreadcrumbItem>Checkout</BreadcrumbItem>
                </Breadcrumb>

                {/* Progress Step Indicator */}
                <div className="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
                    <div className="flex items-center justify-between max-w-2xl mx-auto text-xs font-bold">
                        {/* Step 1 */}
                        <div
                            className={`flex items-center gap-2 ${
                                currentStep >= 1
                                    ? 'text-blue-600 dark:text-blue-400'
                                    : 'text-gray-400'
                            }`}
                        >
                            <span
                                className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-black ${
                                    currentStep >= 1
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-400'
                                }`}
                            >
                                1
                            </span>
                            <span className="hidden sm:inline">1. Datos Personales</span>
                        </div>

                        <div
                            className={`flex-1 h-0.5 mx-3 ${
                                currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-800'
                            }`}
                        />

                        {/* Step 2 */}
                        <div
                            className={`flex items-center gap-2 ${
                                currentStep >= 2
                                    ? 'text-blue-600 dark:text-blue-400'
                                    : 'text-gray-400'
                            }`}
                        >
                            <span
                                className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-black ${
                                    currentStep >= 2
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-400'
                                }`}
                            >
                                2
                            </span>
                            <span className="hidden sm:inline">2. Despacho y Envío</span>
                        </div>

                        <div
                            className={`flex-1 h-0.5 mx-3 ${
                                currentStep === 3 ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-800'
                            }`}
                        />

                        {/* Step 3 */}
                        <div
                            className={`flex items-center gap-2 ${
                                currentStep === 3
                                    ? 'text-blue-600 dark:text-blue-400'
                                    : 'text-gray-400'
                            }`}
                        >
                            <span
                                className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-black ${
                                    currentStep === 3
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'bg-gray-100 dark:bg-gray-800 text-gray-400'
                                }`}
                            >
                                3
                            </span>
                            <span className="hidden sm:inline">3. Pago y Confirmación</span>
                        </div>
                    </div>
                </div>

                {/* Error Banner */}
                {errorMessage && (
                    <Alert color="failure" onDismiss={() => setErrorMessage(null)}>
                        <span className="font-bold">Atención:</span> {errorMessage}
                    </Alert>
                )}

                {/* 2-Column Checkout Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    {/* Left Column: Interactive Forms based on Step */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* ===================== STEP 1: CONTACT & CUSTOMER ===================== */}
                        {currentStep === 1 && (
                            <Card className="shadow-sm rounded-2xl">
                                <div className="border-b dark:border-gray-800 pb-3 flex justify-between items-center">
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                            1. Información de Contacto y Facturación
                                        </h2>
                                        <p className="text-xs text-gray-500 mt-0.5">
                                            Ingresa tus datos personales para emitir el comprobante y coordinar tu orden.
                                        </p>
                                    </div>
                                    {auth_user && (
                                        <Badge color="success" size="sm">
                                            Sesión Iniciada: {auth_user.name}
                                        </Badge>
                                    )}
                                </div>

                                {/* OwO Pass Integration Banner */}
                                {customer ? (
                                    <div className="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 p-3 rounded-xl flex items-center justify-between text-xs font-semibold text-blue-800 dark:text-blue-300">
                                        <div className="flex items-center gap-2">
                                            <HiCheckCircle className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                            <span>Conectado con OwO Pass: <strong>{customer.name}</strong> ({customer.email})</span>
                                        </div>
                                    </div>
                                ) : !auth_user && (
                                    <div className="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/40 dark:to-indigo-950/40 border border-blue-200 dark:border-blue-800/60 p-3 rounded-xl flex items-center justify-between gap-3">
                                        <div className="flex items-center gap-2.5">
                                            <div className="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs shadow-sm">
                                                <HiShieldCheck className="w-4 h-4" />
                                            </div>
                                            <div>
                                                <p className="text-xs font-bold text-gray-900 dark:text-white">
                                                    ¿Tienes una cuenta en OwOMarket?
                                                </p>
                                                <p className="text-[11px] text-gray-500 dark:text-gray-400">
                                                    Inicia sesión con OwO Pass para autocompletar tus datos y direcciones.
                                                </p>
                                            </div>
                                        </div>
                                        <Button
                                            size="xs"
                                            color="blue"
                                            type="button"
                                            onClick={() => openAuthModal('login')}
                                            className="font-bold shrink-0"
                                        >
                                            ⚡ Iniciar con OwO Pass
                                        </Button>
                                    </div>
                                )}

                                <form onSubmit={handleProceedToStep2} className="space-y-4 pt-2">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <Label htmlFor="cust_name">Nombre y Apellido (*)</Label>
                                            <TextInput
                                                id="cust_name"
                                                icon={HiUser}
                                                placeholder="Ej. Juan Pérez"
                                                required
                                                value={customerName}
                                                onChange={(e) => setCustomerName(e.target.value)}
                                                className="mt-1"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="cust_email">Correo Electrónico (*)</Label>
                                            <TextInput
                                                id="cust_email"
                                                type="email"
                                                icon={HiMail}
                                                placeholder="juan.perez@email.com"
                                                required
                                                value={customerEmail}
                                                onChange={(e) => setCustomerEmail(e.target.value)}
                                                className="mt-1"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <Label htmlFor="cust_phone">Teléfono de Contacto</Label>
                                            <TextInput
                                                id="cust_phone"
                                                type="tel"
                                                icon={HiPhone}
                                                placeholder="+56 9 1234 5678"
                                                value={customerPhone}
                                                onChange={(e) => setCustomerPhone(e.target.value)}
                                                className="mt-1"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="cust_doc">RUT / Documento de Identidad</Label>
                                            <TextInput
                                                id="cust_doc"
                                                icon={HiIdentification}
                                                placeholder="12.345.678-9"
                                                value={customerDoc}
                                                onChange={(e) => setCustomerDoc(e.target.value)}
                                                className="mt-1"
                                            />
                                        </div>
                                    </div>

                                    <div className="flex justify-between items-center pt-4 border-t dark:border-gray-800">
                                        <a
                                            href="/cart"
                                            className="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-gray-900 dark:hover:text-white"
                                        >
                                            <HiArrowLeft className="w-4 h-4" />
                                            Volver al Carrito
                                        </a>

                                        <Button color="blue" size="md" type="submit">
                                            <span className="flex items-center gap-2 font-bold">
                                                Continuar al Despacho
                                                <HiArrowRight className="w-4 h-4" />
                                            </span>
                                        </Button>
                                    </div>
                                </form>
                            </Card>
                        )}

                        {/* ===================== STEP 2: SHIPPING ADDRESS & METHOD ===================== */}
                        {currentStep === 2 && (
                            <Card className="shadow-sm rounded-2xl">
                                <div className="border-b dark:border-gray-800 pb-3 flex justify-between items-center">
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                            2. Dirección de Entrega y Método de Envío
                                        </h2>
                                        <p className="text-xs text-gray-500 mt-0.5">
                                            Indica dónde deseas recibir tus productos y el tipo de despacho.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setCurrentStep(1)}
                                        className="text-xs text-blue-600 dark:text-blue-400 hover:underline font-bold"
                                    >
                                        Editar Datos (1)
                                    </button>
                                </div>

                                <form onSubmit={handleProceedToStep3} className="space-y-4 pt-2">
                                    <div>
                                        <Label htmlFor="ship_addr">Dirección de Calle y Número (*)</Label>
                                        <TextInput
                                            id="ship_addr"
                                            placeholder="Ej. Av. Providencia 1234, Depto 502"
                                            required
                                            value={address}
                                            onChange={(e) => setAddress(e.target.value)}
                                            className="mt-1"
                                        />
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <Label htmlFor="ship_city">Ciudad / Comuna (*)</Label>
                                            <TextInput
                                                id="ship_city"
                                                placeholder="Santiago"
                                                required
                                                value={city}
                                                onChange={(e) => setCity(e.target.value)}
                                                className="mt-1"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="ship_state">Región / Estado</Label>
                                            <TextInput
                                                id="ship_state"
                                                placeholder="Región Metropolitana"
                                                value={stateRegion}
                                                onChange={(e) => setStateRegion(e.target.value)}
                                                className="mt-1"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="ship_zip">Código Postal</Label>
                                            <TextInput
                                                id="ship_zip"
                                                placeholder="7500000"
                                                value={zipCode}
                                                onChange={(e) => setZipCode(e.target.value)}
                                                className="mt-1"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="ship_notes">Indicaciones de Entrega (Opcional)</Label>
                                        <Textarea
                                            id="ship_notes"
                                            placeholder="Ej. Dejar en conserjería si no hay nadie en el departamento."
                                            rows={2}
                                            value={shippingNotes}
                                            onChange={(e) => setShippingNotes(e.target.value)}
                                            className="mt-1 text-xs"
                                        />
                                    </div>

                                    {/* Shipping Options Selector */}
                                    <div className="space-y-2 pt-3 border-t dark:border-gray-800">
                                        <Label className="text-xs font-bold uppercase text-gray-500 block">
                                            Selecciona el Método de Envío:
                                        </Label>
                                        <div className="space-y-2">
                                            {shipping_methods.map((method) => {
                                                const isSelected = selectedShippingMethodId === method.id;
                                                return (
                                                    <label
                                                        key={method.id}
                                                        className={`flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all ${
                                                            isSelected
                                                                ? 'bg-blue-50/70 dark:bg-blue-950/40 border-blue-600 shadow-sm'
                                                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                                        }`}
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <Radio
                                                                name="shipping_method_radio"
                                                                value={method.id}
                                                                checked={isSelected}
                                                                onChange={() => setSelectedShippingMethodId(method.id)}
                                                            />
                                                            <div>
                                                                <span className="text-sm font-bold text-gray-900 dark:text-white block">
                                                                    {method.title}
                                                                </span>
                                                                <span className="text-xs text-gray-500">
                                                                    {method.description}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <span className="text-sm font-black text-gray-900 dark:text-white">
                                                            {method.price === 0
                                                                ? 'Gratis'
                                                                : formatPrice(method.price)}
                                                        </span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>

                                    <div className="flex justify-between items-center pt-4 border-t dark:border-gray-800">
                                        <button
                                            type="button"
                                            onClick={() => setCurrentStep(1)}
                                            className="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-gray-900 dark:hover:text-white"
                                        >
                                            <HiArrowLeft className="w-4 h-4" />
                                            Volver a Datos de Contacto
                                        </button>

                                        <Button color="blue" size="md" type="submit">
                                            <span className="flex items-center gap-2 font-bold">
                                                Continuar al Pago
                                                <HiArrowRight className="w-4 h-4" />
                                            </span>
                                        </Button>
                                    </div>
                                </form>
                            </Card>
                        )}

                        {/* ===================== STEP 3: PAYMENT & CONFIRMATION ===================== */}
                        {currentStep === 3 && (
                            <Card className="shadow-sm rounded-2xl space-y-6">
                                <div className="border-b dark:border-gray-800 pb-3 flex justify-between items-center">
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                            3. Método de Pago y Confirmación
                                        </h2>
                                        <p className="text-xs text-gray-500 mt-0.5">
                                            Selecciona cómo deseas abonar tu pedido.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setCurrentStep(2)}
                                        className="text-xs text-blue-600 dark:text-blue-400 hover:underline font-bold"
                                    >
                                        Editar Envío (2)
                                    </button>
                                </div>

                                {/* Authenticated User Banner */}
                                {auth_user && (
                                    <div className="p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-xl flex items-center justify-between text-xs">
                                        <div className="flex items-center gap-2">
                                            <HiCheckCircle className="w-5 h-5 text-blue-600" />
                                            <span>
                                                Comprando como <strong>{auth_user.name}</strong> ({auth_user.email})
                                            </span>
                                        </div>
                                        <Badge color="success">Autenticado</Badge>
                                    </div>
                                )}

                                {/* Payment Methods Options */}
                                <div className="space-y-3">
                                    <Label className="text-xs font-bold uppercase text-gray-500 block">
                                        Selecciona tu Forma de Pago:
                                    </Label>

                                    <div className="space-y-3">
                                        {payment_methods.map((method) => {
                                            const isSelected = selectedPaymentMethodId === method.id;
                                            return (
                                                <div
                                                    key={method.id}
                                                    onClick={() => setSelectedPaymentMethodId(method.id)}
                                                    className={`p-4 rounded-xl border cursor-pointer transition-all space-y-2 ${
                                                        isSelected
                                                            ? 'bg-blue-50/60 dark:bg-blue-950/40 border-blue-600 shadow-sm'
                                                            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-gray-300'
                                                    }`}
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <Radio
                                                                name="payment_method_radio"
                                                                value={method.id}
                                                                checked={isSelected}
                                                                onChange={() => setSelectedPaymentMethodId(method.id)}
                                                            />
                                                            <span className="text-sm font-bold text-gray-900 dark:text-white">
                                                                {method.name}
                                                            </span>
                                                        </div>
                                                        <div className="text-gray-400">
                                                            {method.id === 'pago_movil' && <FaMobileAlt className="w-5 h-5 text-blue-600" />}
                                                            {method.id === 'binance_pay' && <FaBitcoin className="w-6 h-6 text-amber-500" />}
                                                            {method.id === 'bank_transfer' && <FaUniversity className="w-5 h-5 text-gray-500" />}
                                                            {method.id === 'cash_on_delivery' && <FaMoneyBillWave className="w-5 h-5 text-green-600" />}
                                                        </div>
                                                    </div>

                                                    <p className="text-xs text-gray-500 pl-7">
                                                        {method.description}
                                                    </p>

                                                    {/* PAGO MOVIL INTERACTIVE PANEL */}
                                                    {isSelected && method.id === 'pago_movil' && (
                                                        <div className="ml-7 mt-3 p-4 bg-gradient-to-br from-blue-50/90 to-indigo-50/60 dark:from-gray-900 dark:to-blue-950/40 border border-blue-200 dark:border-blue-800/60 rounded-xl space-y-3 cursor-default" onClick={(e) => e.stopPropagation()}>
                                                            <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-blue-100 dark:border-gray-800 pb-2.5">
                                                                <div>
                                                                    <span className="text-[11px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400 flex items-center gap-1.5">
                                                                        📱 Datos para realizar el Pago Móvil
                                                                    </span>
                                                                    <p className="text-xs text-gray-600 dark:text-gray-400">
                                                                        Monto a transferir: <strong className="text-blue-700 dark:text-blue-300 font-black">Bs. {(finalGrandTotal * ((method as any).exchange_rate_ves || 40.50)).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                                                                        <span className="text-[10px] text-gray-400 ml-1.5 font-mono">(Tasa ref: Bs. {(method as any).exchange_rate_ves || 40.50} / USD)</span>
                                                                    </p>
                                                                </div>
                                                                {copyFeedback && (
                                                                    <span className="text-[11px] bg-green-100 text-green-800 px-2 py-0.5 rounded font-bold self-start">
                                                                        {copyFeedback}
                                                                    </span>
                                                                )}
                                                            </div>

                                                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                                                                <div className="bg-white dark:bg-gray-800/80 p-2.5 rounded-lg border dark:border-gray-700">
                                                                    <span className="text-[10px] text-gray-400 block font-semibold">BANCO RECEPTOR</span>
                                                                    <span className="font-bold text-gray-900 dark:text-white truncate block">{(method as any).bank_name || '0102 - Banco de Venezuela'}</span>
                                                                </div>
                                                                <div className="bg-white dark:bg-gray-800/80 p-2.5 rounded-lg border dark:border-gray-700 flex justify-between items-center">
                                                                    <div>
                                                                        <span className="text-[10px] text-gray-400 block font-semibold">CÉDULA / RIF</span>
                                                                        <span className="font-bold text-gray-900 dark:text-white">{(method as any).document_id || 'J-50123456-0'}</span>
                                                                    </div>
                                                                    <button type="button" onClick={() => handleCopy((method as any).document_id || 'J-50123456-0', 'RIF')} className="text-gray-400 hover:text-blue-600 p-1">
                                                                        <FaCopy className="w-3.5 h-3.5" />
                                                                    </button>
                                                                </div>
                                                                <div className="bg-white dark:bg-gray-800/80 p-2.5 rounded-lg border dark:border-gray-700 flex justify-between items-center">
                                                                    <div>
                                                                        <span className="text-[10px] text-gray-400 block font-semibold">TELÉFONO RECEPTOR</span>
                                                                        <span className="font-bold text-gray-900 dark:text-white">{(method as any).phone || '0412-1234567'}</span>
                                                                    </div>
                                                                    <button type="button" onClick={() => handleCopy((method as any).phone || '0412-1234567', 'Teléfono')} className="text-gray-400 hover:text-blue-600 p-1">
                                                                        <FaCopy className="w-3.5 h-3.5" />
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div className="pt-2 border-t border-blue-100 dark:border-gray-800 space-y-2">
                                                                <Label className="text-xs font-bold text-gray-900 dark:text-white">
                                                                    Ingresa el Comprobante de tu Pago Móvil:
                                                                </Label>
                                                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                                                    <div>
                                                                        <TextInput
                                                                            id="pago_movil_bank"
                                                                            placeholder="Banco Emisor (Ej. Banesco)"
                                                                            value={pagoMovilBank}
                                                                            onChange={(e) => setPagoMovilBank(e.target.value)}
                                                                            className="text-xs"
                                                                        />
                                                                    </div>
                                                                    <div>
                                                                        <TextInput
                                                                            id="pago_movil_phone"
                                                                            placeholder="Teléfono Emisor"
                                                                            value={pagoMovilPhone || customerPhone}
                                                                            onChange={(e) => setPagoMovilPhone(e.target.value)}
                                                                            className="text-xs"
                                                                        />
                                                                    </div>
                                                                    <div>
                                                                        <TextInput
                                                                            id="pago_movil_ref"
                                                                            placeholder="Nº Referencia (*)"
                                                                            required
                                                                            value={pagoMovilRef}
                                                                            onChange={(e) => setPagoMovilRef(e.target.value)}
                                                                            className="text-xs"
                                                                        />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* BINANCE PAY INTERACTIVE PANEL */}
                                                    {isSelected && method.id === 'binance_pay' && (
                                                        <div className="ml-7 mt-3 p-4 bg-gradient-to-br from-amber-50/80 to-yellow-50/50 dark:from-gray-900 dark:to-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-xl space-y-3 cursor-default" onClick={(e) => e.stopPropagation()}>
                                                            <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-amber-100 dark:border-gray-800 pb-2.5">
                                                                <div>
                                                                    <span className="text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                                                        🟡 Pagar con Binance Pay (USDT)
                                                                    </span>
                                                                    <p className="text-xs text-gray-600 dark:text-gray-400">
                                                                        Total a pagar: <strong className="text-amber-700 dark:text-amber-300 font-black">{finalGrandTotal.toFixed(2)} USDT</strong> (Red interna Binance Pay sin comisión de gas)
                                                                    </p>
                                                                </div>
                                                                {copyFeedback && (
                                                                    <span className="text-[11px] bg-green-100 text-green-800 px-2 py-0.5 rounded font-bold self-start">
                                                                        {copyFeedback}
                                                                    </span>
                                                                )}
                                                            </div>

                                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                                                                <div className="space-y-2">
                                                                    <div className="bg-white dark:bg-gray-800/80 p-3 rounded-lg border dark:border-gray-700 flex justify-between items-center">
                                                                        <div>
                                                                            <span className="text-[10px] text-gray-400 block font-semibold">BINANCE PAY ID</span>
                                                                            <span className="font-mono text-base font-black text-gray-900 dark:text-white">{(method as any).binance_pay_id || '284759302'}</span>
                                                                        </div>
                                                                        <Button size="xs" color="light" type="button" onClick={() => handleCopy((method as any).binance_pay_id || '284759302', 'Binance Pay ID')}>
                                                                            <FaCopy className="mr-1 w-3 h-3" /> Copiar ID
                                                                        </Button>
                                                                    </div>

                                                                    <div className="text-[11px] text-gray-500 space-y-1">
                                                                        <p>1. Abre tu App de Binance y ve a <strong>Pay</strong>.</p>
                                                                        <p>2. Envía <strong>{finalGrandTotal.toFixed(2)} USDT</strong> al Pay ID o escanea el QR.</p>
                                                                        <p>3. Pega el <strong>ID o Hash de la transacción</strong> abajo.</p>
                                                                    </div>
                                                                </div>

                                                                <div className="flex flex-col items-center justify-center p-3 bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700">
                                                                    <img
                                                                        src={(method as any).qr_code || 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=binancepay://pay?id=284759302'}
                                                                        alt="Binance Pay QR"
                                                                        className="w-28 h-28 object-contain rounded"
                                                                    />
                                                                    <span className="text-[10px] text-gray-400 font-semibold mt-1">Escanear con Binance App</span>
                                                                </div>
                                                            </div>

                                                            <div className="pt-2 border-t border-amber-100 dark:border-gray-800 space-y-2">
                                                                <Label className="text-xs font-bold text-gray-900 dark:text-white">
                                                                    Comprobante de Binance Pay:
                                                                </Label>
                                                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                    <TextInput
                                                                        id="binance_id_buyer"
                                                                        placeholder="Tu Binance Pay ID / Nickname (Opcional)"
                                                                        value={binanceId}
                                                                        onChange={(e) => setBinanceId(e.target.value)}
                                                                        className="text-xs"
                                                                    />
                                                                    <TextInput
                                                                        id="binance_tx_hash"
                                                                        placeholder="ID de Orden / Hash de Transacción (*)"
                                                                        required
                                                                        value={binanceTxHash}
                                                                        onChange={(e) => setBinanceTxHash(e.target.value)}
                                                                        className="text-xs"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* If Bank Transfer and selected, show account instructions */}
                                                    {isSelected && method.instructions && (
                                                        <div className="ml-7 mt-2 p-3 bg-white dark:bg-gray-900 border rounded-lg text-[11px] text-gray-700 dark:text-gray-300 space-y-1 font-mono">
                                                            <p className="font-bold text-gray-900 dark:text-white">Datos de Transferencia:</p>
                                                            <p>{method.instructions}</p>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>

                                {/* Final Confirmation Button */}
                                <div className="space-y-3 pt-4 border-t dark:border-gray-800">
                                    <Button
                                        color="blue"
                                        size="xl"
                                        className="w-full shadow-xl shadow-blue-500/20"
                                        disabled={isSubmitting}
                                        onClick={handleConfirmOrder}
                                    >
                                        {isSubmitting ? (
                                            <span className="flex items-center gap-2">
                                                <Spinner size="sm" />
                                                Procesando y creando orden...
                                            </span>
                                        ) : (
                                            <span className="flex items-center justify-center gap-2 font-black text-base">
                                                <HiLockClosed className="w-5 h-5" />
                                                Confirmar y Realizar Pedido ({formatPrice(finalGrandTotal)})
                                            </span>
                                        )}
                                    </Button>

                                    <div className="flex items-center justify-center gap-2 text-center text-xs text-gray-500">
                                        <HiShieldCheck className="w-4 h-4 text-green-500" />
                                        <span>Garantía de compra 100% protegida por OwoMarket</span>
                                    </div>
                                </div>
                            </Card>
                        )}
                    </div>

                    {/* Right Column: Order Items Summary (Sticky) */}
                    <div className="space-y-4 lg:sticky lg:top-28">
                        <Card className="shadow-sm rounded-2xl">
                            <h3 className="text-base font-bold text-gray-900 dark:text-white pb-3 border-b dark:border-gray-800 flex justify-between items-center">
                                <span>Resumen ({items.length} productos)</span>
                                <a
                                    href="/cart"
                                    className="text-xs text-blue-600 dark:text-blue-400 hover:underline font-normal"
                                >
                                    Editar Carrito
                                </a>
                            </h3>

                            {/* Mini Items List */}
                            <div className="divide-y dark:divide-gray-800 max-h-60 overflow-y-auto pr-1">
                                {items.map((it) => (
                                    <div key={it.id} className="py-2.5 flex items-center justify-between gap-3 text-xs">
                                        <div className="flex items-center gap-2 min-w-0">
                                            <div className="w-10 h-10 bg-gray-50 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0 border flex items-center justify-center">
                                                {it.image ? (
                                                    <img src={it.image} alt={it.name} className="w-full h-full object-contain p-0.5" />
                                                ) : (
                                                    <HiOutlineShoppingBag className="w-4 h-4 text-gray-400" />
                                                )}
                                            </div>
                                            <div className="min-w-0">
                                                <p className="font-semibold text-gray-900 dark:text-white truncate">
                                                    {it.name}
                                                </p>
                                                <p className="text-[10px] text-gray-400">
                                                    Cant: {it.quantity} × {formatPrice(it.price)}
                                                </p>
                                            </div>
                                        </div>
                                        <span className="font-bold text-gray-900 dark:text-white flex-shrink-0">
                                            {formatPrice(it.price * it.quantity)}
                                        </span>
                                    </div>
                                ))}
                            </div>

                            {/* Cost Breakdown */}
                            <div className="space-y-2 text-xs text-gray-600 dark:text-gray-300 pt-3 border-t dark:border-gray-800">
                                <div className="flex justify-between">
                                    <span>Subtotal:</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">
                                        {formatPrice(subtotal)}
                                    </span>
                                </div>

                                {discountAmount > 0 && (
                                    <div className="flex justify-between text-green-600 dark:text-green-400 font-semibold">
                                        <span>Descuento ({coupon?.code}):</span>
                                        <span>-{formatPrice(discountAmount)}</span>
                                    </div>
                                )}

                                <div className="flex justify-between">
                                    <span>Envío ({selectedShippingMethod?.title}):</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">
                                        {shippingCost === 0 ? 'Gratis' : formatPrice(shippingCost)}
                                    </span>
                                </div>

                                <div className="pt-3 border-t dark:border-gray-800">
                                    <span className="font-bold text-gray-900 dark:text-white block text-xs uppercase tracking-wider mb-1">
                                        Total a Pagar:
                                    </span>
                                    <CurrencyPriceDisplay
                                        priceUsd={finalGrandTotal}
                                        size="lg"
                                        showVes={true}
                                        showUsdt={true}
                                        showBcvLabel={true}
                                    />
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>

                {/* ===================== LOGIN GATE MODAL ===================== */}
                <Modal
                    show={isAuthGateModalOpen}
                    onClose={() => setIsAuthGateModalOpen(false)}
                    size="md"
                >
                    <ModalHeader>Iniciar Sesión para Completar Pago</ModalHeader>
                    <ModalBody className="space-y-4 text-center py-6">
                        <div className="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto">
                            <HiLockClosed className="w-8 h-8" />
                        </div>

                        <div className="space-y-1.5">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                Autenticación Requerida
                            </h3>
                            <p className="text-xs text-gray-500 max-w-sm mx-auto leading-relaxed">
                                Para emitir tu comprobante de compra y procesar el pago con seguridad, por favor inicia sesión o ingresa a tu cuenta.
                            </p>
                        </div>

                        <div className="space-y-2 pt-2">
                            <Button
                                color="blue"
                                size="md"
                                className="w-full font-bold"
                                onClick={() => (window.location.href = `/auth/login?redirect=${encodeURIComponent(window.location.pathname)}`)}
                            >
                                <HiUser className="mr-2 h-4 w-4" />
                                Iniciar Sesión / Registrarme
                            </Button>

                            {/* Optional dev/test bypass button */}
                            <Button
                                color="light"
                                size="sm"
                                className="w-full text-xs font-semibold"
                                onClick={() => {
                                    setIsAuthGateModalOpen(false);
                                    setCurrentStep(3);
                                }}
                            >
                                Continuar como Invitado (Modo Pruebas)
                            </Button>
                        </div>
                    </ModalBody>
                </Modal>
            </div>
        </>
    );
}

export default function TenantCheckoutPage(props: StorefrontCheckoutPageProps) {
    return (
        <StorefrontLayout
            domain={props.domain}
            title="Finalizar Compra"
            storeSettings={props.store_settings}
            categories={props.categories}
            authUser={props.auth_user}
        >
            <CheckoutPageContent {...props} />
        </StorefrontLayout>
    );
}
