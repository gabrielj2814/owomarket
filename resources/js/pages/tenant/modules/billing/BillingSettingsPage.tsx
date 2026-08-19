import Dashboard from "@/components/layouts/Dashboard";
import BillingServices from "@/Services/BillingServices";
import { ErrorsFormBillingProfile } from "@/types/ErrorsFormBillingProfile";
import { FormBillingProfile } from "@/types/FormBillingProfile";
import { Head, Link } from "@inertiajs/react";
import {
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
    Spinner,
    TextInput,
    Textarea,
} from "flowbite-react";
import { FC, useEffect, useState } from "react";
import {
    HiArrowLeft,
    HiCheck,
    HiHome,
    HiIdentification,
    HiOfficeBuilding,
} from "react-icons/hi";

interface BillingSettingsPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const initialForm: FormBillingProfile = {
    legal_name: "",
    tax_id: "",
    billing_email: "",
    phone: "",
    address_line_1: "",
    address_line_2: "",
    city: "",
    state: "",
    postal_code: "",
    country: "Chile",
    invoice_prefix: "FAC-",
    next_invoice_number: 1,
    invoice_footer_notes: "",
};

const BillingSettingsPage: FC<BillingSettingsPageProps> = ({ user_id, title }) => {
    const [form, setForm] = useState<FormBillingProfile>(initialForm);
    const [errors, setErrors] = useState<ErrorsFormBillingProfile>({});
    const [loading, setLoading] = useState<boolean>(true);
    const [saving, setSaving] = useState<boolean>(false);
    const [toastMessage, setToastMessage] = useState<string | null>(null);

    const showToast = (text: string) => {
        setToastMessage(text);
        setTimeout(() => setToastMessage(null), 4000);
    };

    const fetchProfile = async () => {
        setLoading(true);
        try {
            const res = await BillingServices.getBillingProfile();
            if (res?.data?.data) {
                const p = res.data.data;
                setForm({
                    legal_name: p.legal_name || "",
                    tax_id: p.tax_id || "",
                    billing_email: p.billing_email || "",
                    phone: p.phone || "",
                    address_line_1: p.address?.address_line_1 || "",
                    address_line_2: p.address?.address_line_2 || "",
                    city: p.address?.city || "",
                    state: p.address?.state || "",
                    postal_code: p.address?.postal_code || "",
                    country: p.address?.country || "Chile",
                    invoice_prefix: p.invoice_prefix || "FAC-",
                    next_invoice_number: p.next_invoice_number || 1,
                    invoice_footer_notes: p.invoice_footer_notes || "",
                });
            }
        } catch (error) {
            console.error("Error al cargar perfil fiscal:", error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchProfile();
    }, []);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});

        try {
            const res = await BillingServices.updateBillingProfile(form);
            if ((res as any)?.code === 200 || (res as any)?.status === "success" || res?.data?.code === 200) {
                showToast("Datos fiscales actualizados exitosamente");
            } else if ((res as any)?.errors || (res as any)?.data?.errors) {
                setErrors((res as any)?.errors || (res as any)?.data?.errors);
            }
        } catch (error: any) {
            showToast("Error al guardar los datos fiscales");
        } finally {
            setSaving(false);
        }
    };

    return (
        <Dashboard user_uuid={user_id}>
            <Head title={title} />

            <div className="p-4 space-y-6 max-w-4xl mx-auto">
                {/* Toast Notification */}
                {toastMessage && (
                    <div className="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">
                        {toastMessage}
                    </div>
                )}

                {/* Header & Breadcrumb */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <Breadcrumb aria-label="Breadcrumb">
                            <BreadcrumbItem href={`/tenant/backoffice/${user_id}/dashboard`} icon={HiHome}>
                                Inicio
                            </BreadcrumbItem>
                            <BreadcrumbItem href={`/billing/backoffice/${user_id}/module`}>
                                Facturación
                            </BreadcrumbItem>
                            <BreadcrumbItem>Perfil Fiscal</BreadcrumbItem>
                        </Breadcrumb>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white mt-2 flex items-center gap-2">
                            <HiIdentification className="w-7 h-7 text-blue-600" />
                            Configuración de Datos Fiscales
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Define la información legal y correlativos que se imprimirán en tus facturas y comprobantes.
                        </p>
                    </div>

                    <Link href={`/billing/backoffice/${user_id}/module`}>
                        <Button color="light" size="sm">
                            <HiArrowLeft className="w-4 h-4 mr-1" />
                            Volver a Facturas
                        </Button>
                    </Link>
                </div>

                {loading ? (
                    <div className="flex justify-center items-center py-20">
                        <Spinner size="xl" />
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* 1. Datos de la Empresa / Emisor */}
                        <Card>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 border-b pb-3 dark:border-gray-700">
                                <HiOfficeBuilding className="w-5 h-5 text-blue-600" />
                                1. Identificación de la Empresa
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <Label htmlFor="legal_name">Razón Social o Nombre Legal *</Label>
                                    <TextInput
                                        id="legal_name"
                                        required
                                        value={form.legal_name}
                                        onChange={(e) => setForm({ ...form, legal_name: e.target.value })}
                                        placeholder="Ej: Mi Tienda SpA"
                                    />
                                    {errors.legal_name && (
                                        <span className="text-xs text-red-600">{errors.legal_name[0]}</span>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="tax_id">RUT / RFC / NIF / RUC *</Label>
                                    <TextInput
                                        id="tax_id"
                                        required
                                        value={form.tax_id}
                                        onChange={(e) => setForm({ ...form, tax_id: e.target.value })}
                                        placeholder="Ej: 76.123.456-7"
                                    />
                                    {errors.tax_id && (
                                        <span className="text-xs text-red-600">{errors.tax_id[0]}</span>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="billing_email">Correo Electrónico Fiscal / Facturación *</Label>
                                    <TextInput
                                        id="billing_email"
                                        type="email"
                                        required
                                        value={form.billing_email}
                                        onChange={(e) => setForm({ ...form, billing_email: e.target.value })}
                                        placeholder="facturacion@mitienda.com"
                                    />
                                    {errors.billing_email && (
                                        <span className="text-xs text-red-600">{errors.billing_email[0]}</span>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="phone">Teléfono de Contacto</Label>
                                    <TextInput
                                        id="phone"
                                        value={form.phone || ""}
                                        onChange={(e) => setForm({ ...form, phone: e.target.value })}
                                        placeholder="+56 9 1234 5678"
                                    />
                                </div>
                            </div>
                        </Card>

                        {/* 2. Domicilio Fiscal */}
                        <Card>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white border-b pb-3 dark:border-gray-700">
                                2. Domicilio Fiscal
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                <div className="md:col-span-2">
                                    <Label htmlFor="address_line_1">Dirección Legal / Calle y Número *</Label>
                                    <TextInput
                                        id="address_line_1"
                                        required
                                        value={form.address_line_1}
                                        onChange={(e) => setForm({ ...form, address_line_1: e.target.value })}
                                        placeholder="Av. Providencia 1234, Oficina 501"
                                    />
                                    {errors.address_line_1 && (
                                        <span className="text-xs text-red-600">{errors.address_line_1[0]}</span>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="city">Ciudad / Comuna *</Label>
                                    <TextInput
                                        id="city"
                                        required
                                        value={form.city}
                                        onChange={(e) => setForm({ ...form, city: e.target.value })}
                                        placeholder="Santiago"
                                    />
                                    {errors.city && (
                                        <span className="text-xs text-red-600">{errors.city[0]}</span>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="state">Región / Provincia / Estado *</Label>
                                    <TextInput
                                        id="state"
                                        required
                                        value={form.state}
                                        onChange={(e) => setForm({ ...form, state: e.target.value })}
                                        placeholder="Región Metropolitana"
                                    />
                                    {errors.state && (
                                        <span className="text-xs text-red-600">{errors.state[0]}</span>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="postal_code">Código Postal *</Label>
                                    <TextInput
                                        id="postal_code"
                                        required
                                        value={form.postal_code}
                                        onChange={(e) => setForm({ ...form, postal_code: e.target.value })}
                                        placeholder="8320000"
                                    />
                                    {errors.postal_code && (
                                        <span className="text-xs text-red-600">{errors.postal_code[0]}</span>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="country">País *</Label>
                                    <TextInput
                                        id="country"
                                        required
                                        value={form.country}
                                        onChange={(e) => setForm({ ...form, country: e.target.value })}
                                        placeholder="Chile"
                                    />
                                </div>
                            </div>
                        </Card>

                        {/* 3. Correlativos y Notas Legales */}
                        <Card>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white border-b pb-3 dark:border-gray-700">
                                3. Configuración de Folios y Correlativos
                            </h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <Label htmlFor="invoice_prefix">Prefijo de Facturas *</Label>
                                    <TextInput
                                        id="invoice_prefix"
                                        required
                                        value={form.invoice_prefix}
                                        onChange={(e) => setForm({ ...form, invoice_prefix: e.target.value })}
                                        placeholder="FAC-"
                                    />
                                    <span className="text-xs text-gray-500">Ejemplo: FAC-000001, FE-000100</span>
                                </div>

                                <div>
                                    <Label htmlFor="next_invoice_number">Próximo Número de Factura *</Label>
                                    <TextInput
                                        id="next_invoice_number"
                                        type="number"
                                        min={1}
                                        required
                                        value={form.next_invoice_number}
                                        onChange={(e) => setForm({ ...form, next_invoice_number: parseInt(e.target.value) || 1 })}
                                    />
                                </div>

                                <div className="md:col-span-2">
                                    <Label htmlFor="invoice_footer_notes">Notas al Pie / Condiciones Legales</Label>
                                    <Textarea
                                        id="invoice_footer_notes"
                                        rows={3}
                                        value={form.invoice_footer_notes || ""}
                                        onChange={(e) => setForm({ ...form, invoice_footer_notes: e.target.value })}
                                        placeholder="Texto legal que aparecerá al pie de todas las facturas y comprobantes emitidos..."
                                    />
                                </div>
                            </div>
                        </Card>

                        {/* Botón Guardar */}
                        <div className="flex justify-end gap-3">
                            <Link href={`/billing/backoffice/${user_id}/module`}>
                                <Button color="gray" type="button">
                                    Cancelar
                                </Button>
                            </Link>
                            <Button color="blue" type="submit" disabled={saving}>
                                {saving ? <Spinner size="sm" className="mr-2" /> : <HiCheck className="w-4 h-4 mr-2" />}
                                Guardar Perfil Fiscal
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </Dashboard>
    );
};

export default BillingSettingsPage;
