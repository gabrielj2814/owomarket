import React, { useMemo, useState } from 'react';
import StorefrontLayout from '@/components/layouts/StorefrontLayout';
import { useCart } from '@/contexts/CartContext';
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
import { FaMoneyBillWave, FaUniversity } from 'react-icons/fa';

function CheckoutPageContent({
    domain,
    store_settings,
    categories = [],
    shipping_methods = [],
    payment_methods = [],
    auth_user = null,
}: StorefrontCheckoutPageProps) {
    const { items, subtotal, discountAmount, total, coupon, clearCart, formatPrice } = useCart();

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
    // Shipping & Payment selection
    const [selectedShippingMethodId, setSelectedShippingMethodId] = useState<string>(
        shipping_methods.length > 0 ? shipping_methods[0].id : 'standard'
    );
    const [selectedPaymentMethodId, setSelectedPaymentMethodId] = useState<string>(
        payment_methods.length > 0 ? payment_methods[0].id : 'bank_transfer'
    );

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
        if (!auth_user) {
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

        setIsSubmitting(true);
        setErrorMessage(null);

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

            const apiData = res.data;
            if (apiData && (apiData.code === 201 || apiData.code === 200) && apiData.data) {
                clearCart();
                window.location.href = apiData.data.redirect_url;
            } else {
                setErrorMessage(apiData?.message || 'Error al procesar la orden. Por favor intenta de nuevo.');
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
                                                            {method.id === 'bank_transfer' && <FaUniversity className="w-5 h-5" />}
                                                            {method.id === 'webpay' && <HiCreditCard className="w-6 h-6 text-blue-600" />}
                                                            {method.id === 'cash_on_delivery' && <FaMoneyBillWave className="w-5 h-5 text-green-600" />}
                                                        </div>
                                                    </div>

                                                    <p className="text-xs text-gray-500 pl-7">
                                                        {method.description}
                                                    </p>

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

                                <div className="flex justify-between items-baseline pt-3 border-t dark:border-gray-800 text-sm">
                                    <span className="font-bold text-gray-900 dark:text-white">Total a Pagar:</span>
                                    <span className="text-xl font-black text-gray-900 dark:text-white">
                                        {formatPrice(finalGrandTotal)}
                                    </span>
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
