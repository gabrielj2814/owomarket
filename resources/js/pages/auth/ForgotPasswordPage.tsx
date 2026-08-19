import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import CentralLayout from '@/components/layouts/CentralLayout';
import CustomerPortalServices from '@/Services/CustomerPortalServices';
import {
    HiOutlineKey,
    HiOutlineEnvelope,
    HiOutlineArrowLeft,
    HiOutlineCheckCircle,
    HiOutlineExclamationCircle,
} from 'react-icons/hi2';

export const ForgotPasswordPage: React.FC = () => {
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [successMsg, setSuccessMsg] = useState<string | null>(null);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSuccessMsg(null);
        setErrorMsg(null);
        setLoading(true);

        try {
            const res = await CustomerPortalServices.requestPasswordReset(email.trim());
            setSuccessMsg(res.message || 'Hemos enviado un código PIN de 6 dígitos a tu correo electrónico.');
        } catch (err: any) {
            setErrorMsg(err.response?.data?.message || 'No se pudo enviar el código de recuperación.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <CentralLayout>
            <Head title="Recuperar Contraseña - OwOMarket" />

            <div className="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
                <div className="max-w-md w-full bg-white dark:bg-gray-900 rounded-3xl p-8 shadow-xl border border-gray-200/80 dark:border-gray-800/80">
                    <div className="text-center mb-6">
                        <div className="w-14 h-14 mx-auto rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-600 flex items-center justify-center mb-3">
                            <HiOutlineKey className="w-7 h-7" />
                        </div>
                        <h2 className="text-xl font-black text-gray-900 dark:text-white">
                            Recuperar Contraseña
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Ingresa el correo electrónico asociado a tu cuenta OwO Pass para recibir un PIN de 6 dígitos.
                        </p>
                    </div>

                    {successMsg ? (
                        <div className="space-y-4">
                            <div className="p-4 rounded-2xl bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800 text-xs font-bold flex items-center gap-2">
                                <HiOutlineCheckCircle className="w-5 h-5 flex-shrink-0" />
                                {successMsg}
                            </div>
                            <Link
                                href={`/auth/reset-password?email=${encodeURIComponent(email)}`}
                                className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 text-center block transition"
                            >
                                Continuar e Ingresar Código PIN
                            </Link>
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

                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition disabled:opacity-50"
                            >
                                {loading ? 'Enviando código PIN...' : 'Enviar Código de Recuperación'}
                            </button>
                        </form>
                    )}

                    <div className="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 text-center">
                        <Link
                            href="/"
                            className="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-blue-600 transition"
                        >
                            <HiOutlineArrowLeft className="w-3.5 h-3.5" /> Volver al Inicio
                        </Link>
                    </div>
                </div>
            </div>
        </CentralLayout>
    );
};

export default ForgotPasswordPage;
