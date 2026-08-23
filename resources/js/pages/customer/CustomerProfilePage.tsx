import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import CustomerAccountLayout from '@/components/layouts/CustomerAccountLayout';
import { useCustomerAuth } from '@/contexts/CustomerAuthContext';
import CustomerPortalServices from '@/Services/CustomerPortalServices';
import {
    HiOutlineUser,
    HiOutlineLockClosed,
    HiOutlineCheckCircle,
    HiOutlineExclamationCircle,
} from 'react-icons/hi2';

export const CustomerProfilePage: React.FC = () => {
    const { customer, login } = useCustomerAuth();

    // Personal info state
    const [name, setName] = useState(customer?.name || '');
    const [phone, setPhone] = useState(customer?.phone || '');
    const [documentId, setDocumentId] = useState(customer?.document_id || '');
    const [avatar, setAvatar] = useState(customer?.avatar || '');

    // Password state
    const [currentPassword, setCurrentPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');

    const [saving, setSaving] = useState(false);
    const [successMsg, setSuccessMsg] = useState<string | null>(null);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    const handleUpdateProfile = async (e: React.FormEvent) => {
        e.preventDefault();
        setSuccessMsg(null);
        setErrorMsg(null);

        if (!customer?.id) return;

        if (newPassword) {
            /*
             * Hallazgo A4: aqui habia un `newPassword.length < 8` propio, el quinto sitio
             * que contestaba que es una contrasena valida. La regla vive en
             * Password::defaults() y el servidor devuelve 422 con el mensaje exacto.
             */
            if (newPassword !== confirmPassword) {
                setErrorMsg('La confirmación de la contraseña no coincide.');
                return;
            }
            if (!currentPassword) {
                setErrorMsg('Debes ingresar tu contraseña actual para establecer una nueva.');
                return;
            }
        }

        setSaving(true);
        try {
            const res = await CustomerPortalServices.updateProfile(customer.id, {
                name,
                phone,
                document_id: documentId,
                avatar,
                current_password: currentPassword || undefined,
                new_password: newPassword || undefined,
            });

            if (res.data?.customer) {
                login(res.data.customer);
            }

            setSuccessMsg('Tus datos han sido actualizados exitosamente.');
            setCurrentPassword('');
            setNewPassword('');
            setConfirmPassword('');
        } catch (err: any) {
            setErrorMsg(err.response?.data?.message || 'Error al actualizar el perfil. Verifica los datos.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <CustomerAccountLayout
            title="Mi Perfil & Seguridad"
            description="Actualiza tus datos de contacto, identificación fiscal y credenciales de acceso."
        >
            <Head title="Mi Perfil - OwOMarket" />

            <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 sm:p-8 shadow-sm border border-gray-200/80 dark:border-gray-800/80">
                {/* Feedback Alerts */}
                {successMsg && (
                    <div className="mb-6 p-4 rounded-2xl bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800 text-xs font-bold flex items-center gap-2">
                        <HiOutlineCheckCircle className="w-5 h-5 flex-shrink-0" />
                        {successMsg}
                    </div>
                )}
                {errorMsg && (
                    <div className="mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 text-xs font-bold flex items-center gap-2">
                        <HiOutlineExclamationCircle className="w-5 h-5 flex-shrink-0" />
                        {errorMsg}
                    </div>
                )}

                <form onSubmit={handleUpdateProfile} className="space-y-8">
                    {/* Section 1: Personal Information */}
                    <div>
                        <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                            <HiOutlineUser className="w-4 h-4 text-blue-600" />
                            Información Personal y Contacto
                        </h3>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Nombre Completo
                                </label>
                                <input
                                    type="text"
                                    value={name}
                                    onChange={e => setName(e.target.value)}
                                    required
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Correo Electrónico (OwO Pass)
                                </label>
                                <input
                                    type="email"
                                    value={customer?.email || ''}
                                    disabled
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-100 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 text-gray-400 cursor-not-allowed"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Teléfono / WhatsApp
                                </label>
                                <input
                                    type="tel"
                                    value={phone}
                                    onChange={e => setPhone(e.target.value)}
                                    placeholder="0412-1234567"
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Cédula o RIF (Para Facturación)
                                </label>
                                <input
                                    type="text"
                                    value={documentId}
                                    onChange={e => setDocumentId(e.target.value)}
                                    placeholder="V-12345678 o J-12345678-0"
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <hr className="border-gray-200 dark:border-gray-800" />

                    {/* Section 2: Password Change */}
                    <div>
                        <h3 className="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                            <HiOutlineLockClosed className="w-4 h-4 text-blue-600" />
                            Cambiar Contraseña (Opcional)
                        </h3>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Contraseña Actual
                                </label>
                                <input
                                    type="password"
                                    value={currentPassword}
                                    onChange={e => setCurrentPassword(e.target.value)}
                                    placeholder="••••••••"
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Nueva Contraseña
                                </label>
                                <input
                                    type="password"
                                    value={newPassword}
                                    onChange={e => setNewPassword(e.target.value)}
                                    placeholder="Mínimo 8 caracteres"
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                    Confirmar Nueva Contraseña
                                </label>
                                <input
                                    type="password"
                                    value={confirmPassword}
                                    onChange={e => setConfirmPassword(e.target.value)}
                                    placeholder="Repite la contraseña"
                                    className="w-full px-4 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Submit Button */}
                    <div className="flex justify-end">
                        <button
                            type="submit"
                            disabled={saving}
                            className="px-6 py-3 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-lg shadow-blue-500/25 transition disabled:opacity-50"
                        >
                            {saving ? 'Guardando cambios...' : 'Guardar Datos del Perfil'}
                        </button>
                    </div>
                </form>
            </div>
        </CustomerAccountLayout>
    );
};

export default CustomerProfilePage;
