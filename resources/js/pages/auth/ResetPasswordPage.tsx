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

        if (password.length < 8) {
            setErrorMsg('La contraseña debe tener al menos 8 caracteres.');
            return;
        }

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
                            <div className="p-4 rounded-2xl bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800 text-xs font-bold flex items-center gap-2">
                                <HiOutlineCheckCircle className="w-5 h-5 flex-shrink-0" />
                                {successMsg}
                            </div>
                            <button
                                onClick={() => openAuthModal('login')}
                                className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 text-center block transition"
                            >
                                Iniciar Sesión con Nueva Contraseña
                            </button>
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
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Correo Electrónico
                                </label>
                                <div className="relative">
                                    <HiOutlineEnvelope className="w-4 h-4 text-gray-400 absolute left-3 top-3 pointer-events-none" />
                                    <input
                                        type="email"
                                        value={email}
                                        onChange={e => setEmail(e.target.value)}
                                        required
                                        placeholder="tu@email.com"
                                        className="w-full pl-9 pr-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Código PIN (6 Dígitos)
                                </label>
                                <div className="relative">
                                    <HiOutlineKey className="w-4 h-4 text-gray-400 absolute left-3 top-3 pointer-events-none" />
                                    <input
                                        type="text"
                                        maxLength={6}
                                        value={pinCode}
                                        onChange={e => setPinCode(e.target.value)}
                                        required
                                        placeholder="123456"
                                        className="w-full pl-9 pr-4 py-2.5 rounded-xl text-xs font-mono font-bold tracking-widest bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Nueva Contraseña
                                </label>
                                <div className="relative">
                                    <HiOutlineLockClosed className="w-4 h-4 text-gray-400 absolute left-3 top-3 pointer-events-none" />
                                    <input
                                        type="password"
                                        value={password}
                                        onChange={e => setPassword(e.target.value)}
                                        required
                                        placeholder="Mínimo 8 caracteres"
                                        className="w-full pl-9 pr-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Confirmar Nueva Contraseña
                                </label>
                                <div className="relative">
                                    <HiOutlineLockClosed className="w-4 h-4 text-gray-400 absolute left-3 top-3 pointer-events-none" />
                                    <input
                                        type="password"
                                        value={confirmPassword}
                                        onChange={e => setConfirmPassword(e.target.value)}
                                        required
                                        placeholder="Repite la contraseña"
                                        className="w-full pl-9 pr-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition disabled:opacity-50"
                            >
                                {loading ? 'Restableciendo...' : 'Restablecer Contraseña'}
                            </button>
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
