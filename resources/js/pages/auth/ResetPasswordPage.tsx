import { Alert, Button, Label, TextInput } from 'flowbite-react';
import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CentralLayout from '@/components/layouts/CentralLayout';
import CustomerPortalServices from '@/Services/CustomerPortalServices';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import {
    HiOutlineLockClosed,
    HiOutlineEnvelope,
    HiOutlineKey,
    HiOutlineArrowLeft,
    HiOutlineCheckCircle,
    HiOutlineExclamationCircle,
} from 'react-icons/hi2';

export const ResetPasswordPage: React.FC = () => {
    const { openAuthModal } = useCustomerAuth();

    // Get email from URL params if present
    const urlParams = new URLSearchParams(window.location.search);
    const initialEmail = urlParams.get('email') || '';

    const [email, setEmail] = useState(initialEmail);
    const [pinCode, setPinCode] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [successMsg, setSuccessMsg] = useState<string | null>(null);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSuccessMsg(null);
        setErrorMsg(null);

        /*
         * Hallazgo A4: aquí había un `password.length < 8` propio. Era el cuarto sitio que
         * respondía a «qué es una contraseña válida», y el que se quedaba corto en cuanto
         * la regla del servidor pidiera algo más que longitud. Se quita: la regla vive en
         * Password::defaults() y el servidor devuelve 422 con el mensaje exacto.
         */

        if (password !== confirmPassword) {
            setErrorMsg('Las contraseñas no coinciden.');
            return;
        }

        if (pinCode.trim().length !== 6) {
            setErrorMsg('El código PIN debe ser de 6 dígitos.');
            return;
        }

        setLoading(true);

        try {
            const res = await CustomerPortalServices.resetPassword({
                email: email.trim(),
                pin_code: pinCode.trim(),
                password,
            });
            setSuccessMsg(res.message || 'Tu contraseña ha sido restablecida con éxito.');
        } catch (err: any) {
            setErrorMsg(err.response?.data?.message || 'Código PIN inválido o expirado.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <CentralLayout>
            <Head title="Restablecer Contraseña - OwOMarket" />

            <div className="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
                <div className="max-w-md w-full bg-white dark:bg-gray-900 rounded-3xl p-8 shadow-xl border border-gray-200/80 dark:border-gray-800/80">
                    <div className="text-center mb-6">
                        <div className="w-14 h-14 mx-auto rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 flex items-center justify-center mb-3">
                            <HiOutlineLockClosed className="w-7 h-7" />
                        </div>
                        <h2 className="text-xl font-black text-gray-900 dark:text-white">
                            Restablecer Contraseña
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Ingresa el PIN recibido en tu correo y tu nueva contraseña.
                        </p>
                    </div>

                    {successMsg ? (
                        <div className="space-y-4">
                            <Alert color="success" icon={HiOutlineCheckCircle} className="text-xs font-bold">
                                {successMsg}
                            </Alert>
                            <Button
                                color="primary"
                                size="md"
                                className="w-full"
                                onClick={() => openAuthModal('login')}
                            >
                                Iniciar Sesión con Nueva Contraseña
                            </Button>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit} className="space-y-4">
                            {errorMsg && (
                                <div className="p-3 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 text-xs font-bold flex items-center gap-2">
                                    <HiOutlineExclamationCircle className="w-4 h-4 flex-shrink-0" />
                                    {errorMsg}
                                </div>
                            )}

                            <div>
                                <Label htmlFor="reset-correo-electronico">Correo Electrónico</Label>
                                <TextInput id="reset-correo-electronico" icon={HiOutlineEnvelope}
                                        type="email"
                                        value={email}
                                        onChange={e => setEmail(e.target.value)}
                                        required
                                        placeholder="tu@email.com" />
                            </div>

                            <div>
                                <Label htmlFor="reset-codigo-pin-6-digitos">Código PIN (6 Dígitos)</Label>
                                <TextInput id="reset-codigo-pin-6-digitos" icon={HiOutlineKey}
                                        type="text"
                                        maxLength={6}
                                        value={pinCode}
                                        onChange={e => setPinCode(e.target.value)}
                                        required
                                        placeholder="123456" />
                            </div>

                            <div>
                                <Label htmlFor="reset-nueva-contrasena">Nueva Contraseña</Label>
                                <TextInput id="reset-nueva-contrasena" icon={HiOutlineLockClosed}
                                        type="password"
                                        value={password}
                                        onChange={e => setPassword(e.target.value)}
                                        required
                                        placeholder="Mínimo 8 caracteres" />
                            </div>

                            <div>
                                <Label htmlFor="reset-confirmar-nueva-contrase">Confirmar Nueva Contraseña</Label>
                                <TextInput id="reset-confirmar-nueva-contrase" icon={HiOutlineLockClosed}
                                        type="password"
                                        value={confirmPassword}
                                        onChange={e => setConfirmPassword(e.target.value)}
                                        required
                                        placeholder="Repite la contraseña" />
                            </div>

                            <Button type="submit" color="primary" size="md" className="w-full" disabled={loading}>
                                {loading ? 'Restableciendo...' : 'Restablecer Contraseña'}
                            </Button>
                        </form>
                    )}

                    <div className="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 text-center">
                        <Link
                            href="/auth/forgot-password"
                            className="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-blue-600 transition"
                        >
                            <HiOutlineArrowLeft className="w-3.5 h-3.5" /> Reenviar PIN
                        </Link>
                    </div>
                </div>
            </div>
        </CentralLayout>
    );
};

export default ResetPasswordPage;
