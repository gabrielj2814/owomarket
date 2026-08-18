import React, { useEffect, useState } from 'react';
import Dashboard from '@/components/layouts/Dashboard';
import TenantSettingsServices from '@/Services/TenantSettingsServices';
import { ErrorsFormUpdateStoreSettings } from '@/types/ErrorsFormTenantSettings';
import { FormUpdateStoreSettings } from '@/types/FormTenantSettings';
import { StoreSettingsFlat } from '@/types/models/TenantSettings';
import { Head } from '@inertiajs/react';
import {
    Alert,
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
    Select,
    Spinner,
    TabItem,
    Tabs,
    TextInput,
    Textarea,
} from 'flowbite-react';
import {
    HiCheck,
    HiGlobeAlt,
    HiHome,
    HiInformationCircle,
    HiOutlineExternalLink,
    HiPhotograph,
    HiSave,
    HiSearch,
} from 'react-icons/hi';

interface TenantSettingsPageProps {
    title: string;
    user_id: string;
    host: string;
    user_name: string;
}

export default function TenantSettingsPage({
    title,
    user_id,
    host,
    user_name,
}: TenantSettingsPageProps) {
    const [formData, setFormData] = useState<StoreSettingsFlat>({
        store_name: '',
        store_email: '',
        currency: 'USD',
        contact_phone: '',
        address: '',
        logo_url: '',
        banner_url: '',
        social_facebook: '',
        social_instagram: '',
        social_whatsapp: '',
        social_twitter: '',
        seo_title: '',
        seo_description: '',
        seo_keywords: '',
    });

    const [loading, setLoading] = useState<boolean>(true);
    const [saving, setSaving] = useState<boolean>(false);
    const [errors, setErrors] = useState<ErrorsFormUpdateStoreSettings>({});
    const [toastMessage, setToastMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const showToast = (type: 'success' | 'error', text: string) => {
        setToastMessage({ type, text });
        setTimeout(() => setToastMessage(null), 4000);
    };

    const fetchSettings = async () => {
        setLoading(true);
        try {
            const response = await TenantSettingsServices.getStoreSettings();
            if (response.data && (response.data.code === 200 || response.data.status === 'success') && response.data.data) {
                const flat = response.data.data.flat;
                setFormData({
                    store_name: flat.store_name || '',
                    store_email: flat.store_email || '',
                    currency: flat.currency || 'USD',
                    contact_phone: flat.contact_phone || '',
                    address: flat.address || '',
                    logo_url: flat.logo_url || '',
                    banner_url: flat.banner_url || '',
                    social_facebook: flat.social_facebook || '',
                    social_instagram: flat.social_instagram || '',
                    social_whatsapp: flat.social_whatsapp || '',
                    social_twitter: flat.social_twitter || '',
                    seo_title: flat.seo_title || '',
                    seo_description: flat.seo_description || '',
                    seo_keywords: flat.seo_keywords || '',
                });
            }
        } catch (e) {
            showToast('error', 'Error al cargar la configuración de la tienda.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchSettings();
    }, []);

    const handleChange = (field: keyof StoreSettingsFlat, value: string) => {
        setFormData((prev) => ({
            ...prev,
            [field]: value,
        }));
        if (errors[field as keyof ErrorsFormUpdateStoreSettings]) {
            setErrors((prev) => ({
                ...prev,
                [field]: undefined,
            }));
        }
    };

    const handleSave = async (e?: React.FormEvent) => {
        if (e) e.preventDefault();
        setSaving(true);
        setErrors({});

        try {
            const payload: FormUpdateStoreSettings = {
                store_name: formData.store_name.trim() !== '' ? formData.store_name.trim() : undefined,
                store_email: formData.store_email.trim() !== '' ? formData.store_email.trim() : undefined,
                currency: formData.currency.trim() !== '' ? formData.currency.trim() : undefined,
                contact_phone: formData.contact_phone ? formData.contact_phone.trim() : undefined,
                address: formData.address ? formData.address.trim() : undefined,
                logo_url: formData.logo_url ? formData.logo_url.trim() : undefined,
                banner_url: formData.banner_url ? formData.banner_url.trim() : undefined,
                social_facebook: formData.social_facebook ? formData.social_facebook.trim() : undefined,
                social_instagram: formData.social_instagram ? formData.social_instagram.trim() : undefined,
                social_whatsapp: formData.social_whatsapp ? formData.social_whatsapp.trim() : undefined,
                social_twitter: formData.social_twitter ? formData.social_twitter.trim() : undefined,
                seo_title: formData.seo_title ? formData.seo_title.trim() : undefined,
                seo_description: formData.seo_description ? formData.seo_description.trim() : undefined,
                seo_keywords: formData.seo_keywords ? formData.seo_keywords.trim() : undefined,
            };

            const response = await TenantSettingsServices.updateStoreSettings(payload);
            if (response.data && (response.data.code === 200 || response.data.status === 'success')) {
                showToast('success', 'Configuración de la tienda guardada con éxito.');
            } else {
                showToast('error', response.data?.message || 'Error al guardar configuración.');
                if (response.data?.errors) {
                    setErrors(response.data.errors);
                }
            }
        } catch (e) {
            showToast('error', 'Error de comunicación con el servidor.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />
            <div className="p-4 sm:p-6 space-y-6 max-w-6xl mx-auto">
                {/* Toast Notification */}
                {toastMessage && (
                    <div
                        className={`fixed top-5 right-5 z-50 flex items-center p-4 mb-4 text-sm rounded-lg shadow-lg ${
                            toastMessage.type === 'success'
                                ? 'text-green-800 bg-green-100 dark:bg-green-800 dark:text-green-200 border border-green-300'
                                : 'text-red-800 bg-red-100 dark:bg-red-800 dark:text-red-200 border border-red-300'
                        }`}
                    >
                        <span className="font-medium mr-2">
                            {toastMessage.type === 'success' ? 'Éxito:' : 'Error:'}
                        </span>
                        {toastMessage.text}
                    </div>
                )}

                {/* Breadcrumb */}
                <Breadcrumb>
                    <BreadcrumbItem href={`/tenant/backoffice/${user_id}/dashboard`} icon={HiHome}>
                        Dashboard
                    </BreadcrumbItem>
                    <BreadcrumbItem>Configuración de Tienda</BreadcrumbItem>
                </Breadcrumb>

                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">
                            Configuración de la Tienda
                        </h1>
                        <p className="text-sm text-gray-500 mt-1">
                            Personaliza la identidad de tu comercio, moneda por defecto, imágenes de marca, canales sociales y optimización SEO.
                        </p>
                    </div>
                    <Button
                        color="blue"
                        onClick={() => handleSave()}
                        disabled={saving || loading}
                    >
                        {saving ? <Spinner size="sm" className="mr-2" /> : <HiSave className="mr-2 h-4 w-4" />}
                        Guardar Cambios
                    </Button>
                </div>

                {loading ? (
                    <div className="py-20 text-center">
                        <Spinner size="xl" />
                        <p className="text-sm text-gray-500 mt-3">Cargando configuración...</p>
                    </div>
                ) : (
                    <Card className="shadow-sm">
                        <Tabs aria-label="Store settings tabs" variant="underline">
                            {/* TAB 1: Información General & Contacto */}
                            <TabItem active title="General & Contacto" icon={HiInformationCircle}>
                                <div className="space-y-4 pt-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label htmlFor="store_name_input">Nombre Comercial de la Tienda (*)</Label>
                                            <TextInput
                                                id="store_name_input"
                                                value={formData.store_name}
                                                onChange={(e) => handleChange('store_name', e.target.value)}
                                                placeholder="Ej. Mi Tienda Oficial"
                                                required
                                                className="mt-1"
                                                color={errors.store_name ? 'failure' : 'gray'}
                                            />
                                            {errors.store_name && (
                                                <span className="text-xs text-red-600 mt-1 block">{errors.store_name[0]}</span>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="store_email_input">Correo Electrónico de Contacto (*)</Label>
                                            <TextInput
                                                id="store_email_input"
                                                type="email"
                                                value={formData.store_email}
                                                onChange={(e) => handleChange('store_email', e.target.value)}
                                                placeholder="contacto@mitienda.com"
                                                required
                                                className="mt-1"
                                                color={errors.store_email ? 'failure' : 'gray'}
                                            />
                                            {errors.store_email && (
                                                <span className="text-xs text-red-600 mt-1 block">{errors.store_email[0]}</span>
                                            )}
                                        </div>

                                        <div>
                                            <Label htmlFor="currency_input">Moneda Principal de la Tienda (*)</Label>
                                            <Select
                                                id="currency_input"
                                                value={formData.currency}
                                                onChange={(e) => handleChange('currency', e.target.value)}
                                                className="mt-1"
                                            >
                                                <option value="CLP">CLP - Peso Chileno ($)</option>
                                                <option value="USD">USD - Dólar Estadounidense ($)</option>
                                                <option value="EUR">EUR - Euro (€)</option>
                                                <option value="ARS">ARS - Peso Argentino ($)</option>
                                                <option value="COP">COP - Peso Colombiano ($)</option>
                                                <option value="MXN">MXN - Peso Mexicano ($)</option>
                                                <option value="PEN">PEN - Sol Peruano (S/)</option>
                                            </Select>
                                            <p className="text-xs text-gray-400 mt-1">
                                                Moneda base utilizada para cotizaciones, catálogo y transacciones.
                                            </p>
                                        </div>

                                        <div>
                                            <Label htmlFor="contact_phone_input">Teléfono de Soporte / Ventas</Label>
                                            <TextInput
                                                id="contact_phone_input"
                                                value={formData.contact_phone || ''}
                                                onChange={(e) => handleChange('contact_phone', e.target.value)}
                                                placeholder="+56 9 1234 5678"
                                                className="mt-1"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <Label htmlFor="address_input">Dirección Física o Casa Matriz</Label>
                                        <Textarea
                                            id="address_input"
                                            value={formData.address || ''}
                                            onChange={(e) => handleChange('address', e.target.value)}
                                            placeholder="Av. Principal 123, Oficina 402, Santiago, Chile"
                                            rows={2}
                                            className="mt-1"
                                        />
                                    </div>
                                </div>
                            </TabItem>

                            {/* TAB 2: Imagen y Branding */}
                            <TabItem title="Imagen y Marca" icon={HiPhotograph}>
                                <div className="space-y-6 pt-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {/* Logo Section */}
                                        <div className="space-y-3">
                                            <Label htmlFor="logo_url_input">URL del Logotipo</Label>
                                            <TextInput
                                                id="logo_url_input"
                                                value={formData.logo_url || ''}
                                                onChange={(e) => handleChange('logo_url', e.target.value)}
                                                placeholder="https://cdn.tusitio.com/logo.png"
                                            />
                                            <p className="text-xs text-gray-400">
                                                Recomendado: Imagen PNG transparente o SVG de 250x60 px.
                                            </p>

                                            <div className="mt-2 p-4 border rounded-lg bg-gray-50 dark:bg-gray-800 text-center">
                                                <span className="text-xs font-bold text-gray-500 block mb-2">
                                                    Vista Previa del Logotipo:
                                                </span>
                                                {formData.logo_url ? (
                                                    <img
                                                        src={formData.logo_url}
                                                        alt="Logo Preview"
                                                        className="max-h-16 mx-auto object-contain"
                                                        onError={(e) => {
                                                            (e.target as HTMLImageElement).src = 'https://via.placeholder.com/200x60?text=Logo+Invalido';
                                                        }}
                                                    />
                                                ) : (
                                                    <div className="text-xs text-gray-400 italic py-4">
                                                        Sin logotipo configurado
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        {/* Banner Section */}
                                        <div className="space-y-3">
                                            <Label htmlFor="banner_url_input">URL del Banner Principal / Portada</Label>
                                            <TextInput
                                                id="banner_url_input"
                                                value={formData.banner_url || ''}
                                                onChange={(e) => handleChange('banner_url', e.target.value)}
                                                placeholder="https://cdn.tusitio.com/banner.jpg"
                                            />
                                            <p className="text-xs text-gray-400">
                                                Recomendado: Imagen horizontal de 1920x600 px en formato WebP o JPG.
                                            </p>

                                            <div className="mt-2 p-4 border rounded-lg bg-gray-50 dark:bg-gray-800 text-center">
                                                <span className="text-xs font-bold text-gray-500 block mb-2">
                                                    Vista Previa de la Portada:
                                                </span>
                                                {formData.banner_url ? (
                                                    <img
                                                        src={formData.banner_url}
                                                        alt="Banner Preview"
                                                        className="max-h-24 w-full rounded object-cover"
                                                        onError={(e) => {
                                                            (e.target as HTMLImageElement).src = 'https://via.placeholder.com/600x200?text=Banner+Invalido';
                                                        }}
                                                    />
                                                ) : (
                                                    <div className="text-xs text-gray-400 italic py-6">
                                                        Sin banner configurado
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </TabItem>

                            {/* TAB 3: Redes Sociales */}
                            <TabItem title="Redes Sociales" icon={HiGlobeAlt}>
                                <div className="space-y-4 pt-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label htmlFor="social_facebook_input">Página de Facebook</Label>
                                            <TextInput
                                                id="social_facebook_input"
                                                value={formData.social_facebook || ''}
                                                onChange={(e) => handleChange('social_facebook', e.target.value)}
                                                placeholder="https://facebook.com/tutienda"
                                                className="mt-1"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="social_instagram_input">Perfil de Instagram</Label>
                                            <TextInput
                                                id="social_instagram_input"
                                                value={formData.social_instagram || ''}
                                                onChange={(e) => handleChange('social_instagram', e.target.value)}
                                                placeholder="https://instagram.com/tutienda"
                                                className="mt-1"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="social_whatsapp_input">WhatsApp Business (Número o Link)</Label>
                                            <TextInput
                                                id="social_whatsapp_input"
                                                value={formData.social_whatsapp || ''}
                                                onChange={(e) => handleChange('social_whatsapp', e.target.value)}
                                                placeholder="+56912345678 o https://wa.me/56912345678"
                                                className="mt-1"
                                            />
                                        </div>

                                        <div>
                                            <Label htmlFor="social_twitter_input">Cuenta de Twitter / X</Label>
                                            <TextInput
                                                id="social_twitter_input"
                                                value={formData.social_twitter || ''}
                                                onChange={(e) => handleChange('social_twitter', e.target.value)}
                                                placeholder="https://x.com/tutienda"
                                                className="mt-1"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </TabItem>

                            {/* TAB 4: SEO y Posicionamiento */}
                            <TabItem title="SEO & Buscadores" icon={HiSearch}>
                                <div className="space-y-5 pt-4">
                                    <div className="space-y-3">
                                        <div>
                                            <Label htmlFor="seo_title_input">Título Meta para Motores de Búsqueda (Title Tag)</Label>
                                            <TextInput
                                                id="seo_title_input"
                                                value={formData.seo_title || ''}
                                                onChange={(e) => handleChange('seo_title', e.target.value)}
                                                placeholder="Ej. Mi Tienda - Los mejores artículos al mejor precio"
                                                className="mt-1"
                                            />
                                            <span className="text-xs text-gray-400">
                                                Recomendado: 50 a 60 caracteres ({formData.seo_title?.length || 0} caracteres)
                                            </span>
                                        </div>

                                        <div>
                                            <Label htmlFor="seo_description_input">Descripción Meta (Meta Description)</Label>
                                            <Textarea
                                                id="seo_description_input"
                                                value={formData.seo_description || ''}
                                                onChange={(e) => handleChange('seo_description', e.target.value)}
                                                placeholder="Resumen atractivo que aparecerá bajo el título en los resultados de Google..."
                                                rows={3}
                                                className="mt-1"
                                            />
                                            <span className="text-xs text-gray-400">
                                                Recomendado: 120 a 160 caracteres ({formData.seo_description?.length || 0} caracteres)
                                            </span>
                                        </div>

                                        <div>
                                            <Label htmlFor="seo_keywords_input">Palabras Clave (Keywords)</Label>
                                            <TextInput
                                                id="seo_keywords_input"
                                                value={formData.seo_keywords || ''}
                                                onChange={(e) => handleChange('seo_keywords', e.target.value)}
                                                placeholder="tienda online, electronica, calzado, santiago chile"
                                                className="mt-1"
                                            />
                                            <p className="text-xs text-gray-400 mt-1">
                                                Separadas por coma.
                                            </p>
                                        </div>
                                    </div>

                                    {/* Google Search Result Preview Card */}
                                    <div className="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800 space-y-2">
                                        <div className="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            <span>Previsualización en Resultados de Google:</span>
                                        </div>
                                        <div className="p-3 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700 font-sans">
                                            <span className="text-xs text-gray-600 dark:text-gray-400 block truncate">
                                                https://{host}
                                            </span>
                                            <h4 className="text-blue-700 dark:text-blue-400 text-base hover:underline cursor-pointer font-medium mt-0.5">
                                                {formData.seo_title || formData.store_name || 'Mi Tienda Online'}
                                            </h4>
                                            <p className="text-xs text-gray-600 dark:text-gray-300 line-clamp-2 mt-1">
                                                {formData.seo_description ||
                                                    'Descubre nuestra amplia selección de productos con los mejores precios y envíos a todo el país.'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </TabItem>
                        </Tabs>

                        {/* Bottom Save Action */}
                        <div className="flex justify-end pt-4 border-t dark:border-gray-700">
                            <Button
                                color="blue"
                                onClick={() => handleSave()}
                                disabled={saving}
                            >
                                {saving ? <Spinner size="sm" className="mr-2" /> : <HiSave className="mr-2 h-4 w-4" />}
                                Guardar Cambios
                            </Button>
                        </div>
                    </Card>
                )}
            </div>
        </Dashboard>
    );
}
