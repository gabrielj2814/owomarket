import Dashboard from "@/components/layouts/Dashboard";
import AdminServices from "@/Services/AdminServices";
import { Head } from "@inertiajs/react";
import {
    Avatar,
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    FileInput,
    Label,
    Spinner,
    TextInput,
} from "flowbite-react";
import { FC, useState } from "react";
import {
    HiCheck,
    HiExclamation,
    HiHome,
    HiLockClosed,
    HiMail,
    HiPhone,
    HiShieldCheck,
    HiUser,
} from "react-icons/hi";

interface ProfileData {
    id: string;
    name: string;
    email: string;
    phone: string;
    avatar: string;
    type: string;
    is_active: boolean;
    has_pin: boolean;
}

interface AdminProfileIndexProps {
    user_uuid: string;
    profile: ProfileData;
}

const Index: FC<AdminProfileIndexProps> = ({ user_uuid, profile }) => {
    // Estado de datos personales y avatar
    const [name, setName] = useState<string>(profile.name);
    const [phone, setPhone] = useState<string>(profile.phone);
    const [avatarUrl, setAvatarUrl] = useState<string>(profile.avatar);

    // Estados de carga y alertas
    const [isUpdatingProfile, setIsUpdatingProfile] = useState<boolean>(false);
    const [isUploadingAvatar, setIsUploadingAvatar] = useState<boolean>(false);
    const [isSendingPin, setIsSendingPin] = useState<boolean>(false);
    const [isChangingPassword, setIsChangingPassword] = useState<boolean>(false);

    // Estados de formulario de contraseña
    const [pin, setPin] = useState<string>("");
    const [newPassword, setNewPassword] = useState<string>("");
    const [newPasswordConfirmation, setNewPasswordConfirmation] = useState<string>("");

    // Mensajes de retroalimentación
    const [alertMessage, setAlertMessage] = useState<{
        type: "success" | "error";
        text: string;
    } | null>(null);

    const getRoleBadgeColor = (type: string) => {
        switch (type) {
            case "super_admin":
                return "purple";
            case "tenant_owner":
                return "info";
            default:
                return "gray";
        }
    };

    const getRoleName = (type: string) => {
        switch (type) {
            case "super_admin":
                return "Super Administrador";
            case "tenant_owner":
                return "Propietario Tenant";
            default:
                return "Administrador";
        }
    };

    // 1. Actualizar Datos Personales usando AdminServices
    const handleUpdateProfile = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsUpdatingProfile(true);
        setAlertMessage(null);

        try {
            const respuestaApi = await AdminServices.updateProfile(user_uuid, { name, phone });

            if (respuestaApi.status === 200) {
                setAlertMessage({
                    type: "success",
                    text: respuestaApi.data?.message || "Datos personales actualizados correctamente.",
                });
            } else {
                const message = (respuestaApi.response?.data as any)?.message || "Error al actualizar los datos personales.";
                setAlertMessage({
                    type: "error",
                    text: message,
                });
            }
        } catch (error: any) {
            setAlertMessage({
                type: "error",
                text: "Ocurrió un error inesperado al conectar con el servidor.",
            });
        } finally {
            setIsUpdatingProfile(false);
        }
    };

    // 2. Subir Foto de Perfil (Avatar) usando AdminServices
    const handleAvatarChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setIsUploadingAvatar(true);
        setAlertMessage(null);

        try {
            const respuestaApi = await AdminServices.uploadAvatar(user_uuid, file);

            if (respuestaApi.status === 200 && respuestaApi.data?.data?.avatar) {
                setAvatarUrl(respuestaApi.data.data.avatar);
                setAlertMessage({
                    type: "success",
                    text: "Foto de perfil actualizada exitosamente.",
                });
            } else {
                const message = (respuestaApi.response?.data as any)?.message || "Error al subir la foto de perfil.";
                setAlertMessage({
                    type: "error",
                    text: message,
                });
            }
        } catch (error: any) {
            setAlertMessage({
                type: "error",
                text: "Ocurrió un error al procesar el archivo.",
            });
        } finally {
            setIsUploadingAvatar(false);
        }
    };

    // 3. Solicitar PIN de Seguridad por Correo usando AdminServices
    const handleSendPin = async () => {
        setIsSendingPin(true);
        setAlertMessage(null);

        try {
            const respuestaApi = await AdminServices.sendSecurityPin(user_uuid);

            if (respuestaApi.status === 200) {
                setAlertMessage({
                    type: "success",
                    text: respuestaApi.data?.message || "PIN enviado exitosamente a tu correo.",
                });
            } else {
                const message = (respuestaApi.response?.data as any)?.message || "No se pudo enviar el PIN de seguridad.";
                setAlertMessage({
                    type: "error",
                    text: message,
                });
            }
        } catch (error: any) {
            setAlertMessage({
                type: "error",
                text: "Error de red al solicitar el PIN de seguridad.",
            });
        } finally {
            setIsSendingPin(false);
        }
    };

    // 4. Cambiar Contraseña usando el PIN y AdminServices
    const handleChangePassword = async (e: React.FormEvent) => {
        e.preventDefault();
        if (newPassword !== newPasswordConfirmation) {
            setAlertMessage({
                type: "error",
                text: "La confirmación de la contraseña no coincide.",
            });
            return;
        }

        setIsChangingPassword(true);
        setAlertMessage(null);

        try {
            const respuestaApi = await AdminServices.changePasswordWithPin(user_uuid, {
                pin,
                password: newPassword,
                password_confirmation: newPasswordConfirmation,
            });

            if (respuestaApi.status === 200) {
                setAlertMessage({
                    type: "success",
                    text: respuestaApi.data?.message || "Contraseña actualizada exitosamente.",
                });
                setPin("");
                setNewPassword("");
                setNewPasswordConfirmation("");
            } else {
                const message = (respuestaApi.response?.data as any)?.message || "Error al cambiar la contraseña.";
                setAlertMessage({
                    type: "error",
                    text: message,
                });
            }
        } catch (error: any) {
            setAlertMessage({
                type: "error",
                text: "Ocurrió un error al procesar el cambio de contraseña.",
            });
        } finally {
            setIsChangingPassword(false);
        }
    };

    return (
        <>
            <Head>
                <title>Mi Perfil | OwoMarket</title>
            </Head>
            <Dashboard user_uuid={user_uuid}>
                <Breadcrumb
                    aria-label="Breadcrumb de Perfil"
                    className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-5"
                >
                    <BreadcrumbItem icon={HiHome} href={`/admin/backoffice/${user_uuid}/dashboard`}>
                        Dashboard
                    </BreadcrumbItem>
                    <BreadcrumbItem icon={HiUser}>Mi Perfil</BreadcrumbItem>
                </Breadcrumb>

                {/* Banner de Notificación / Alerta */}
                {alertMessage && (
                    <div
                        className={`p-4 mb-6 text-sm rounded-lg flex items-center gap-3 ${
                            alertMessage.type === "success"
                                ? "bg-green-50 text-green-800 dark:bg-gray-800 dark:text-green-400 border border-green-200"
                                : "bg-red-50 text-red-800 dark:bg-gray-800 dark:text-red-400 border border-red-200"
                        }`}
                        role="alert"
                    >
                        {alertMessage.type === "success" ? (
                            <HiCheck className="w-5 h-5 flex-shrink-0" />
                        ) : (
                            <HiExclamation className="w-5 h-5 flex-shrink-0" />
                        )}
                        <span>{alertMessage.text}</span>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Tarjeta de Información General y Avatar */}
                    <Card className="lg:col-span-1 text-center p-6 flex flex-col items-center">
                        <div className="relative mb-4">
                            <Avatar
                                img={avatarUrl || undefined}
                                placeholderInitials={name.slice(0, 2).toUpperCase()}
                                rounded
                                size="xl"
                                className="shadow-lg border-2 border-indigo-500"
                            />
                            {isUploadingAvatar && (
                                <div className="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center">
                                    <Spinner size="md" color="info" />
                                </div>
                            )}
                        </div>

                        <h3 className="text-xl font-bold text-gray-900 dark:text-white mb-1">
                            {name}
                        </h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-3">{profile.email}</p>

                        <div className="mb-4">
                            <Badge color={getRoleBadgeColor(profile.type)} className="text-xs px-3 py-1">
                                {getRoleName(profile.type)}
                            </Badge>
                        </div>

                        <div className="w-full mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <Label htmlFor="avatar-upload" className="block text-sm font-medium mb-2 text-left">
                                Cambiar Foto de Perfil
                            </Label>
                            <FileInput
                                id="avatar-upload"
                                accept="image/*"
                                onChange={handleAvatarChange}
                                disabled={isUploadingAvatar}
                            />
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400 text-left">
                                Formatos: JPG, PNG, WEBP (Max 2MB)
                            </p>
                        </div>
                    </Card>

                    {/* Formulario de Datos Personales y Cambio de Contraseña */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* 1. Datos Personales */}
                        <Card>
                            <h4 className="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <HiUser className="text-indigo-600 dark:text-indigo-400" />
                                Datos Personales
                            </h4>

                            <form onSubmit={handleUpdateProfile} className="space-y-4">
                                <div>
                                    <Label htmlFor="name" className="mb-1 block">
                                        Nombre Completo
                                    </Label>
                                    <TextInput
                                        id="name"
                                        icon={HiUser}
                                        type="text"
                                        value={name}
                                        onChange={(e) => setName(e.target.value)}
                                        required
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="email" className="mb-1 block">
                                        Correo Electrónico (No modificable)
                                    </Label>
                                    <TextInput
                                        id="email"
                                        icon={HiMail}
                                        type="email"
                                        value={profile.email}
                                        disabled
                                        className="bg-gray-100 dark:bg-gray-700 cursor-not-allowed"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="phone" className="mb-1 block">
                                        Teléfono de Contacto
                                    </Label>
                                    <TextInput
                                        id="phone"
                                        icon={HiPhone}
                                        type="text"
                                        placeholder="+58 412 1234567"
                                        value={phone}
                                        onChange={(e) => setPhone(e.target.value)}
                                    />
                                </div>

                                <div className="flex justify-end pt-2">
                                    <Button
                                        type="submit"
                                        color="indigo"
                                        disabled={isUpdatingProfile}
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white"
                                    >
                                        {isUpdatingProfile ? (
                                            <>
                                                <Spinner size="sm" className="mr-2" />
                                                Guardando...
                                            </>
                                        ) : (
                                            "Guardar Cambios"
                                        )}
                                    </Button>
                                </div>
                            </form>
                        </Card>

                        {/* 2. Seguridades y Cambio de Contraseña mediante PIN */}
                        <Card>
                            <h4 className="text-lg font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                <HiShieldCheck className="text-indigo-600 dark:text-indigo-400" />
                                Cambio de Contraseña Seguro (PIN por Correo)
                            </h4>

                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Para mayor seguridad, debes solicitar un PIN de verificación que será enviado a tu correo electrónico registrado. El PIN expira en 15 minutos.
                            </p>

                            {/* Botón para generar PIN */}
                            <div className="bg-indigo-50 dark:bg-gray-800 p-4 rounded-lg border border-indigo-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                                <div className="text-sm">
                                    <p className="font-semibold text-indigo-950 dark:text-indigo-200">
                                        Paso 1: Generar PIN de Seguridad
                                    </p>
                                    <p className="text-gray-500 dark:text-gray-400 text-xs">
                                        Se enviará un código de 6 dígitos a <strong>{profile.email}</strong>.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    color="dark"
                                    onClick={handleSendPin}
                                    disabled={isSendingPin}
                                    className="w-full sm:w-auto"
                                >
                                    {isSendingPin ? (
                                        <>
                                            <Spinner size="sm" className="mr-2" />
                                            Enviando...
                                        </>
                                    ) : (
                                        "Enviar PIN al Correo"
                                    )}
                                </Button>
                            </div>

                            {/* Formulario de Contraseña con PIN */}
                            <form onSubmit={handleChangePassword} className="space-y-4">
                                <div>
                                    <Label htmlFor="pin" className="mb-1 block">
                                        Paso 2: Ingrese el PIN recibido (6 dígitos)
                                    </Label>
                                    <TextInput
                                        id="pin"
                                        icon={HiLockClosed}
                                        type="text"
                                        maxLength={6}
                                        placeholder="Ej: 123456"
                                        value={pin}
                                        onChange={(e) => setPin(e.target.value)}
                                        required
                                    />
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="newPassword" className="mb-1 block">
                                            Nueva Contraseña
                                        </Label>
                                        <TextInput
                                            id="newPassword"
                                            icon={HiLockClosed}
                                            type="password"
                                            placeholder="Mínimo 8 caracteres"
                                            value={newPassword}
                                            onChange={(e) => setNewPassword(e.target.value)}
                                            required
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="newPasswordConfirmation" className="mb-1 block">
                                            Confirmar Nueva Contraseña
                                        </Label>
                                        <TextInput
                                            id="newPasswordConfirmation"
                                            icon={HiLockClosed}
                                            type="password"
                                            placeholder="Repite la contraseña"
                                            value={newPasswordConfirmation}
                                            onChange={(e) => setNewPasswordConfirmation(e.target.value)}
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="flex justify-end pt-2">
                                    <Button
                                        type="submit"
                                        color="failure"
                                        disabled={isChangingPassword || !pin}
                                    >
                                        {isChangingPassword ? (
                                            <>
                                                <Spinner size="sm" className="mr-2" />
                                                Actualizando...
                                            </>
                                        ) : (
                                            "Actualizar Contraseña"
                                        )}
                                    </Button>
                                </div>
                            </form>
                        </Card>
                    </div>
                </div>
            </Dashboard>
        </>
    );
};

export default Index;
