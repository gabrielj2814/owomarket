import LoaderSpinner from "@/components/LoaderSpinner";
import TenantServices from "@/Services/TenantServices";
import { FormCreateAccounTenant } from "@/types/FormCreateAccounTenant";
import { Button, Label, TextInput } from "flowbite-react";
import { useState } from "react";
import { HiMail, HiPhone, HiUser, HiCheckCircle, HiExclamationCircle } from "react-icons/hi";
import { LuSend, LuStore } from "react-icons/lu";
import { TbPassword } from "react-icons/tb";

interface ValidationErrors {
    name?: string;
    email?: string;
    phone?: string;
    password?: string;
    confirmPassword?: string;
    store_name?: string;
    tenant_name?: string;
    general?: string;
}

const CreateAccountTenantPage = () => {
    const [formulario, setFormulario] = useState<FormCreateAccounTenant>({
        name: "Jaen Doe",
        email: "Jaen@hoyoverse.com",
        phone: "04121234567",
        password: "Jaen_Doe1234",
        confirmPassword: "Jaen_Doe1234",
        store_name: "Zenless Zone Zero Corp",
        tenant_name: "zenless-zone-zero-corp.owomarket.local"
    });

    const [errors, setErrors] = useState<ValidationErrors>({});
    const [statusLoader, setStatusLoader] = useState<boolean>(false);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const slugify = (text: string) => {
        return text
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-');
    };

    const handlersChangeForm = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;

        setFormulario(prev => {
            const updated = { ...prev, [name]: value };

            // Auto-generar tenant_name cuando se edita store_name si no ha sido modificado manualmente
            if (name === "store_name" && value.length > 0) {
                const baseDomain = window.location.host.includes('.local')
                    ? 'owomarket.local'
                    : 'owomarket.com';
                updated.tenant_name = `${slugify(value)}.${baseDomain}`;
            }

            return updated;
        });

        // Limpiar error del campo modificado
        if (errors[name as keyof ValidationErrors]) {
            setErrors(prev => ({
                ...prev,
                [name]: undefined,
                general: undefined,
            }));
        }
    };

    const validateFrontend = (): boolean => {
        const newErrors: ValidationErrors = {};

        if (!formulario.name || formulario.name.trim().length < 3) {
            newErrors.name = "El nombre debe tener al menos 3 caracteres.";
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!formulario.email || !emailRegex.test(formulario.email)) {
            newErrors.email = "Ingresa un correo electrónico válido.";
        }

        const phoneClean = formulario.phone.replace(/[\s\-\+\(\)]/g, '');
        if (!formulario.phone || phoneClean.length < 8) {
            newErrors.phone = "El teléfono debe tener al menos 8 dígitos.";
        }

        if (!formulario.password || formulario.password.length < 8) {
            newErrors.password = "La contraseña debe tener al menos 8 caracteres.";
        }

        if (formulario.password !== formulario.confirmPassword) {
            newErrors.confirmPassword = "Las contraseñas no coinciden.";
        }

        if (!formulario.store_name || formulario.store_name.trim().length < 3) {
            newErrors.store_name = "El nombre de la tienda debe tener al menos 3 caracteres.";
        }

        if (!formulario.tenant_name || formulario.tenant_name.trim().length < 2) {
            newErrors.tenant_name = "El subdominio es obligatorio.";
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmitForm = async (e: React.FormEvent) => {
        e.preventDefault();
        setSuccessMessage(null);

        if (!validateFrontend()) {
            return;
        }

        setStatusLoader(true);
        setErrors({});

        try {
            const respuestaApi = await TenantServices.createAccountTenant(formulario);
            setStatusLoader(false);

            if (respuestaApi.status !== 201) {
                const responseData = respuestaApi.response?.data;
                const validationErrors = (responseData?.errors || responseData?.data || {}) as Record<string, any>;

                const backendErrors: ValidationErrors = {};
                if (typeof validationErrors === 'object' && validationErrors !== null) {
                    Object.keys(validationErrors).forEach((key) => {
                        const errorMsg = Array.isArray(validationErrors[key])
                            ? validationErrors[key][0]
                            : validationErrors[key];
                        backendErrors[key as keyof ValidationErrors] = errorMsg;
                    });
                }

                if (Object.keys(backendErrors).length > 0) {
                    setErrors(backendErrors);
                } else {
                    setErrors({
                        general: responseData?.message || "Error al crear la cuenta del comercio. Verifica los datos ingresados."
                    });
                }
                return;
            }

            setSuccessMessage("¡Cuenta creada exitosamente! Redirigiendo al panel de inicio de sesión...");

            setTimeout(() => {
                window.location.href = "/auth/login-staff";
            }, 1800);
        } catch (error: any) {
            setStatusLoader(false);
            setErrors({
                general: error?.message || "Ocurrió un error inesperado al procesar el registro."
            });
        }
    };

    return (
        <main className="flex flex-row h-screen bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            <LoaderSpinner status={statusLoader} />

            {/* Banner Lateral Izquierdo */}
            <div className="basis-full lg:basis-1/2 hidden lg:block p-4 h-screen">
                <div className="text-2xl font-bold mb-10">
                    <LuStore className="inline-block text-blue-700 w-10 h-10 mr-1"/> OwOMarket
                </div>

                <div className="w-full flex flex-row justify-center">
                    <div className="text-center">
                        <img
                            className="w-xl h-xl rounded-2xl mb-5 shadow-lg object-cover"
                            src="https://i.pinimg.com/736x/24/81/d1/2481d19f7d6d2062cc987c2384f0096e.jpg"
                            alt="OwOMarket Store"
                        />
                        <h2 className="text-4xl mb-3 font-bold text-gray-900 dark:text-white">
                            Open your store on OwOMarket
                        </h2>
                        <div className="text-sm text-gray-500 dark:text-gray-400 font-medium">
                            Join hundreds of sellers and start reaching new customers today.
                        </div>
                    </div>
                </div>
            </div>

            {/* Columna Derecha del Formulario */}
            <div className="basis-full lg:basis-1/2 h-screen overflow-y-auto bg-gray-200 text-gray-600 dark:bg-gray-950 dark:text-gray-400 flex flex-col items-center p-6 sm:p-10">
                <div className="w-full sm:w-10/12 lg:w-9/12">
                    <h1 className="text-3xl sm:text-4xl mb-6 font-bold text-gray-900 dark:text-white">
                        Create Your Store & Account
                    </h1>

                    {/* Alerta de Error General */}
                    {errors.general && (
                        <div className="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-xs font-semibold flex items-center gap-2">
                            <HiExclamationCircle className="w-5 h-5 shrink-0 text-red-500" />
                            <span>{errors.general}</span>
                        </div>
                    )}

                    {/* Alerta de Éxito */}
                    {successMessage && (
                        <div className="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2 animate-fade-in">
                            <HiCheckCircle className="w-5 h-5 shrink-0 text-emerald-500" />
                            <span>{successMessage}</span>
                        </div>
                    )}

                    <form onSubmit={handleSubmitForm} noValidate>
                        <h2 className="text-lg mb-3 font-bold text-gray-800 dark:text-gray-200 border-b border-gray-300 dark:border-gray-800 pb-1">
                            Personal Information
                        </h2>

                        <div className="w-full flex flex-row flex-wrap justify-between">
                            {/* Name */}
                            <div className="basis-full mb-4">
                                <div className="mb-1 block">
                                    <Label htmlFor="name">Name</Label>
                                </div>
                                <TextInput
                                    id="name"
                                    type="text"
                                    name="name"
                                    icon={HiUser}
                                    placeholder="Name"
                                    color={errors.name ? "failure" : "gray"}
                                    value={formulario.name}
                                    onChange={handlersChangeForm}
                                />
                                {errors.name && (
                                    <p className="mt-1 text-xs text-red-600 dark:text-red-400 font-medium">
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            {/* Email */}
                            <div className="basis-full md:basis-[48%] mb-4">
                                <div className="mb-1 block">
                                    <Label htmlFor="email">Email</Label>
                                </div>
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    icon={HiMail}
                                    placeholder="name@owomarket.com"
                                    color={errors.email ? "failure" : "gray"}
                                    value={formulario.email}
                                    onChange={handlersChangeForm}
                                />
                                {errors.email && (
                                    <p className="mt-1 text-xs text-red-600 dark:text-red-400 font-medium">
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            {/* Phone */}
                            <div className="basis-full md:basis-[48%] mb-4">
                                <div className="mb-1 block">
                                    <Label htmlFor="phone">Phone</Label>
                                </div>
                                <TextInput
                                    id="phone"
                                    type="text"
                                    name="phone"
                                    icon={HiPhone}
                                    placeholder="04121234567"
                                    color={errors.phone ? "failure" : "gray"}
                                    value={formulario.phone}
                                    onChange={handlersChangeForm}
                                />
                                {errors.phone && (
                                    <p className="mt-1 text-xs text-red-600 dark:text-red-400 font-medium">
                                        {errors.phone}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Password & Confirm */}
                        <div className="w-full flex flex-row flex-wrap justify-between mb-6">
                            <div className="basis-full md:basis-[48%] mb-4 md:mb-0">
                                <div className="mb-1 block">
                                    <Label htmlFor="password">Password</Label>
                                </div>
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    icon={TbPassword}
                                    placeholder="Password"
                                    color={errors.password ? "failure" : "gray"}
                                    value={formulario.password}
                                    onChange={handlersChangeForm}
                                />
                                {errors.password && (
                                    <p className="mt-1 text-xs text-red-600 dark:text-red-400 font-medium">
                                        {errors.password}
                                    </p>
                                )}
                            </div>

                            <div className="basis-full md:basis-[48%]">
                                <div className="mb-1 block">
                                    <Label htmlFor="confirmPassword">Confirm Password</Label>
                                </div>
                                <TextInput
                                    id="confirmPassword"
                                    type="password"
                                    name="confirmPassword"
                                    icon={TbPassword}
                                    placeholder="Confirm Password"
                                    color={errors.confirmPassword ? "failure" : "gray"}
                                    value={formulario.confirmPassword}
                                    onChange={handlersChangeForm}
                                />
                                {errors.confirmPassword && (
                                    <p className="mt-1 text-xs text-red-600 dark:text-red-400 font-medium">
                                        {errors.confirmPassword}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Store Information */}
                        <h2 className="text-lg mb-3 font-bold text-gray-800 dark:text-gray-200 border-b border-gray-300 dark:border-gray-800 pb-1">
                            Store Information
                        </h2>

                        <div className="w-full flex flex-row flex-wrap justify-between mb-8">
                            {/* Store Name */}
                            <div className="basis-full mb-4">
                                <div className="mb-1 block">
                                    <Label htmlFor="store_name">Store Name</Label>
                                </div>
                                <TextInput
                                    id="store_name"
                                    type="text"
                                    name="store_name"
                                    icon={LuStore}
                                    placeholder="Store Name"
                                    color={errors.store_name ? "failure" : "gray"}
                                    value={formulario.store_name}
                                    onChange={handlersChangeForm}
                                />
                                {errors.store_name && (
                                    <p className="mt-1 text-xs text-red-600 dark:text-red-400 font-medium">
                                        {errors.store_name}
                                    </p>
                                )}
                            </div>

                            {/* Tenant Name */}
                            <div className="basis-full">
                                <div className="mb-1 block">
                                    <Label htmlFor="tenant_name">
                                        Tenant Name (Your store's unique address)
                                    </Label>
                                </div>
                                <TextInput
                                    id="tenant_name"
                                    type="text"
                                    name="tenant_name"
                                    icon={LuStore}
                                    placeholder="your-store.owomarket.local"
                                    color={errors.tenant_name ? "failure" : "gray"}
                                    value={formulario.tenant_name}
                                    onChange={handlersChangeForm}
                                />
                                {errors.tenant_name ? (
                                    <p className="mt-1 text-xs text-red-600 dark:text-red-400 font-medium">
                                        {errors.tenant_name}
                                    </p>
                                ) : (
                                    <p className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Esta será tu dirección web exclusiva para recibir pedidos.
                                    </p>
                                )}
                            </div>
                        </div>

                        <Button className="w-full py-1 text-base font-bold shadow-md" type="submit">
                            <LuSend className="mr-2 h-5 w-5" />
                            Register Store & Account
                        </Button>
                    </form>
                </div>
            </div>
        </main>
    );
};

export default CreateAccountTenantPage;
