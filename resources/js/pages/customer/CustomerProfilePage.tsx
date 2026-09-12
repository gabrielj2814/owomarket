import { Alert, Button, Card, Label, TextInput } from 'flowbite-react';
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

            <Card>
                {/* Feedback Alerts */}
                {successMsg && (
                    <Alert color="success" icon={HiOutlineCheckCircle} className="rounded-2xl text-xs font-bold">
                        {successMsg}
                    </Alert>
                )}
                {errorMsg && (
                    <Alert color="failure" icon={HiOutlineExclamationCircle} className="rounded-2xl text-xs font-bold">
                        {errorMsg}
                    </Alert>
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
                                <Label htmlFor="perfil-nombre">Nombre Completo</Label>
                                <TextInput id="perfil-nombre" type="text" value={name} onChange={(e) => setName(e.target.value)} required />
                            </div>

                            <div>
                                <Label htmlFor="perfil-correo">Correo Electrónico (OwO Pass)</Label>
                                <TextInput id="perfil-correo" type="email" value={customer?.email || ''} disabled />
                            </div>

                            <div>
                                <Label htmlFor="perfil-telefono">Teléfono / WhatsApp</Label>
                                <TextInput id="perfil-telefono" type="tel" value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="0412-1234567" />
                            </div>

                            <div>
                                <Label htmlFor="perfil-documento">Cédula o RIF (Para Facturación)</Label>
                                <TextInput id="perfil-documento" type="text" value={documentId} onChange={(e) => setDocumentId(e.target.value)} placeholder="V-12345678 o J-12345678-0" />
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
                                <Label htmlFor="perfil-pass-actual">Contraseña Actual</Label>
                                <TextInput id="perfil-pass-actual" type="password" value={currentPassword} onChange={(e) => setCurrentPassword(e.target.value)} placeholder="••••••••" />
                            </div>

                            <div>
                                <Label htmlFor="perfil-pass-nueva">Nueva Contraseña</Label>
                                <TextInput id="perfil-pass-nueva" type="password" value={newPassword} onChange={(e) => setNewPassword(e.target.value)} placeholder="Mínimo 8 caracteres" />
                            </div>

                            <div>
                                <Label htmlFor="perfil-pass-confirmar">Confirmar Nueva Contraseña</Label>
                                <TextInput id="perfil-pass-confirmar" type="password" value={confirmPassword} onChange={(e) => setConfirmPassword(e.target.value)} placeholder="Repite la contraseña" />
                            </div>
                        </div>
                    </div>

                    {/* Submit Button */}
                    <div className="flex justify-end">
                        <Button type="submit" color="primary" disabled={saving}>
                            {saving ? 'Guardando cambios...' : 'Guardar Datos del Perfil'}
                        </Button>
                    </div>
                </form>
            </Card>
        </CustomerAccountLayout>
    );
};

export default CustomerProfilePage;
