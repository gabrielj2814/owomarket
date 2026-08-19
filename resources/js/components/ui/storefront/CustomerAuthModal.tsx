import React, { useState } from 'react';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import { Alert, Button, Label, Modal, ModalBody, ModalHeader, Spinner, TextInput } from 'flowbite-react';
import {
    HiCheckCircle,
    HiEye,
    HiEyeOff,
    HiIdentification,
    HiInformationCircle,
    HiLockClosed,
    HiMail,
    HiPhone,
    HiShieldCheck,
    HiSparkles,
    HiUser,
} from 'react-icons/hi';

export const CustomerAuthModal: React.FC = () => {
    const { isAuthModalOpen, authModalTab, openAuthModal, closeAuthModal, login, register } = useCustomerAuth();

    // Login Form State
    const [loginEmail, setLoginEmail] = useState<string>('');
    const [loginPassword, setLoginPassword] = useState<string>('');
    const [showLoginPassword, setShowLoginPassword] = useState<boolean>(false);

    // Register Form State
    const [regName, setRegName] = useState<string>('');
    const [regEmail, setRegEmail] = useState<string>('');
    const [regPhone, setRegPhone] = useState<string>('');
    const [regDocumentId, setRegDocumentId] = useState<string>('');
    const [regPassword, setRegPassword] = useState<string>('');
    const [showRegPassword, setShowRegPassword] = useState<boolean>(false);

    // UI & Status
    const [loading, setLoading] = useState<boolean>(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [successMessage, setSuccessMessage] = useState<string | null>(null);

    const handleLoginSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setErrorMessage(null);
        setSuccessMessage(null);

        if (!loginEmail.trim() || !loginPassword) {
            setErrorMessage('Por favor completa todos los campos.');
            return;
        }

        setLoading(true);
        const res = await login({
            email: loginEmail.trim(),
            password: loginPassword,
        });
        setLoading(false);

        if (!res.success) {
            setErrorMessage(res.message || 'Credenciales inválidas.');
        } else {
            setSuccessMessage(res.message || '¡Sesión iniciada con éxito!');
        }
    };

    const handleRegisterSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setErrorMessage(null);
        setSuccessMessage(null);

        if (!regName.trim() || !regEmail.trim() || !regPassword) {
            setErrorMessage('Por favor completa los campos obligatorios (*).');
            return;
        }

        if (regPassword.length < 6) {
            setErrorMessage('La contraseña debe tener al menos 6 caracteres.');
            return;
        }

        setLoading(true);
        const res = await register({
            name: regName.trim(),
            email: regEmail.trim(),
            password: regPassword,
            phone: regPhone.trim() || undefined,
            document_id: regDocumentId.trim() || undefined,
        });
        setLoading(false);

        if (!res.success) {
            setErrorMessage(res.message || 'No se pudo crear la cuenta.');
        } else {
            setSuccessMessage('¡Cuenta creada e iniciada exitosamente!');
        }
    };

    return (
        <Modal show={isAuthModalOpen} onClose={closeAuthModal} size="md">
            <ModalHeader className="border-b dark:border-gray-800">
                <div className="flex items-center gap-2">
                    <div className="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-sm">
                        <HiShieldCheck className="w-5 h-5" />
                    </div>
                    <div>
                        <h3 className="text-base font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                            OwO Pass
                            <span className="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                                Cuenta Universal
                            </span>
                        </h3>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Una sola cuenta para todas las tiendas del ecosistema
                        </p>
                    </div>
                </div>
            </ModalHeader>

            <ModalBody className="space-y-4 pt-2">
                {/* Tabs switch */}
                <div className="grid grid-cols-2 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                    <button
                        type="button"
                        onClick={() => {
                            setErrorMessage(null);
                            openAuthModal('login');
                        }}
                        className={`py-2 text-xs font-bold rounded-lg transition-all ${
                            authModalTab === 'login'
                                ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-900'
                        }`}
                    >
                        Iniciar Sesión
                    </button>
                    <button
                        type="button"
                        onClick={() => {
                            setErrorMessage(null);
                            openAuthModal('register');
                        }}
                        className={`py-2 text-xs font-bold rounded-lg transition-all ${
                            authModalTab === 'register'
                                ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-900'
                        }`}
                    >
                        Crear Cuenta
                    </button>
                </div>

                {errorMessage && (
                    <Alert color="failure" icon={HiInformationCircle}>
                        <span className="text-xs font-medium">{errorMessage}</span>
                    </Alert>
                )}

                {successMessage && (
                    <Alert color="success" icon={HiCheckCircle}>
                        <span className="text-xs font-medium">{successMessage}</span>
                    </Alert>
                )}

                {/* LOGIN FORM */}
                {authModalTab === 'login' ? (
                    <form onSubmit={handleLoginSubmit} className="space-y-4">
                        <div>
                            <Label htmlFor="login_email_input" className="text-xs">
                                Correo Electrónico *
                            </Label>
                            <TextInput
                                id="login_email_input"
                                type="email"
                                icon={HiMail}
                                placeholder="tu@correo.com"
                                value={loginEmail}
                                onChange={(e) => setLoginEmail(e.target.value)}
                                required
                                className="mt-1"
                            />
                        </div>

                        <div>
                            <Label htmlFor="login_password_input" className="text-xs">
                                Contraseña *
                            </Label>
                            <div className="relative mt-1">
                                <TextInput
                                    id="login_password_input"
                                    type={showLoginPassword ? 'text' : 'password'}
                                    icon={HiLockClosed}
                                    placeholder="••••••••"
                                    value={loginPassword}
                                    onChange={(e) => setLoginPassword(e.target.value)}
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowLoginPassword(!showLoginPassword)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                >
                                    {showLoginPassword ? <HiEyeOff className="w-4 h-4" /> : <HiEye className="w-4 h-4" />}
                                </button>
                            </div>
                        </div>

                        <Button
                            type="submit"
                            color="blue"
                            className="w-full font-bold shadow-md shadow-blue-500/20"
                            disabled={loading}
                        >
                            {loading ? (
                                <>
                                    <Spinner size="sm" className="mr-2" />
                                    Verificando con OwO Pass...
                                </>
                            ) : (
                                <>
                                    <HiSparkles className="mr-2 h-4 w-4" />
                                    Iniciar Sesión
                                </>
                            )}
                        </Button>
                    </form>
                ) : (
                    /* REGISTER FORM */
                    <form onSubmit={handleRegisterSubmit} className="space-y-3">
                        <div>
                            <Label htmlFor="reg_name_input" className="text-xs">
                                Nombre Completo *
                            </Label>
                            <TextInput
                                id="reg_name_input"
                                type="text"
                                icon={HiUser}
                                placeholder="Ej. Carlos Mendoza"
                                value={regName}
                                onChange={(e) => setRegName(e.target.value)}
                                required
                                className="mt-1"
                            />
                        </div>

                        <div>
                            <Label htmlFor="reg_email_input" className="text-xs">
                                Correo Electrónico *
                            </Label>
                            <TextInput
                                id="reg_email_input"
                                type="email"
                                icon={HiMail}
                                placeholder="carlos@correo.com"
                                value={regEmail}
                                onChange={(e) => setRegEmail(e.target.value)}
                                required
                                className="mt-1"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            <div>
                                <Label htmlFor="reg_phone_input" className="text-xs">
                                    Teléfono Móvil
                                </Label>
                                <TextInput
                                    id="reg_phone_input"
                                    type="tel"
                                    icon={HiPhone}
                                    placeholder="+58 412 1234567"
                                    value={regPhone}
                                    onChange={(e) => setRegPhone(e.target.value)}
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="reg_doc_input" className="text-xs">
                                    Cédula / DNI
                                </Label>
                                <TextInput
                                    id="reg_doc_input"
                                    type="text"
                                    icon={HiIdentification}
                                    placeholder="V-12345678"
                                    value={regDocumentId}
                                    onChange={(e) => setRegDocumentId(e.target.value)}
                                    className="mt-1"
                                />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="reg_pass_input" className="text-xs">
                                Contraseña *
                            </Label>
                            <div className="relative mt-1">
                                <TextInput
                                    id="reg_pass_input"
                                    type={showRegPassword ? 'text' : 'password'}
                                    icon={HiLockClosed}
                                    placeholder="Mínimo 6 caracteres"
                                    value={regPassword}
                                    onChange={(e) => setRegPassword(e.target.value)}
                                    required
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowRegPassword(!showRegPassword)}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                >
                                    {showRegPassword ? <HiEyeOff className="w-4 h-4" /> : <HiEye className="w-4 h-4" />}
                                </button>
                            </div>
                        </div>

                        <p className="text-[10px] text-gray-400">
                            🔒 Al registrarte, tu cuenta quedará vinculada automáticamente a todas las tiendas oficiales del Marketplace OwOMarket.
                        </p>

                        <Button
                            type="submit"
                            color="blue"
                            className="w-full font-bold shadow-md shadow-blue-500/20"
                            disabled={loading}
                        >
                            {loading ? (
                                <>
                                    <Spinner size="sm" className="mr-2" />
                                    Creando Cuenta Universal...
                                </>
                            ) : (
                                <>
                                    <HiShieldCheck className="mr-2 h-4 w-4" />
                                    Crear Cuenta OwO Pass
                                </>
                            )}
                        </Button>
                    </form>
                )}
            </ModalBody>
        </Modal>
    );
};

export default CustomerAuthModal;
