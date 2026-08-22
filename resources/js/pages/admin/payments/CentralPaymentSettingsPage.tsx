import Dashboard from '@/components/layouts/Dashboard';
import getCSRFToken from '@/utils/getCSRFToken';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { Alert, Badge, Breadcrumb, BreadcrumbItem, Button, Card, Label, TextInput } from 'flowbite-react';
import React, { useState } from 'react';
import { HiCheckCircle, HiExclamationCircle, HiHome } from 'react-icons/hi';

interface ActiveMethod {
    id: string;
    name: string;
}

interface Props {
    user_id: string;
    settings: Record<string, string>;
    active_methods: ActiveMethod[];
}

/**
 * Datos de cobro de la plataforma (hallazgo N33).
 *
 * La Fase 3.4 sacó del checkout central los datos bancarios de demostración que estaban
 * incrustados en el TSX —Banesco, J-501234567, 0412-9998877— y los movió a
 * `central_settings`. Pero no había dónde escribirlos: sólo los ponía un seeder de
 * desarrollo. Hasta que se cargan, **el checkout central no ofrece ningún método de pago**,
 * y eso es deliberado: mejor una opción menos que un pago a una cuenta equivocada.
 */
export default function CentralPaymentSettingsPage({ user_id, settings, active_methods }: Props) {
    const [form, setForm] = useState<Record<string, string>>({
        central_pago_movil_bank_name: settings.central_pago_movil_bank_name ?? '',
        central_pago_movil_document_id: settings.central_pago_movil_document_id ?? '',
        central_pago_movil_phone: settings.central_pago_movil_phone ?? '',
        central_pago_movil_holder_name: settings.central_pago_movil_holder_name ?? '',
        central_binance_pay_id: settings.central_binance_pay_id ?? '',
    });
    const [saving, setSaving] = useState(false);
    const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const set = (key: string, value: string) => setForm((prev) => ({ ...prev, [key]: value }));

    const pagoMovilCompleto =
        form.central_pago_movil_bank_name.trim() !== '' &&
        form.central_pago_movil_document_id.trim() !== '' &&
        form.central_pago_movil_phone.trim() !== '';

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setFeedback(null);

        try {
            const res = await axios.put('/admin/backoffice/payment-settings', form, {
                headers: { 'X-CSRF-TOKEN': getCSRFToken() },
            });

            if (res.data?.status === 'success') {
                // Se recarga para que la lista de métodos activos refleje lo que verá el
                // comprador, que es el dato que de verdad importa comprobar.
                window.location.reload();
            } else {
                setFeedback({ type: 'error', text: res.data?.message || 'No se pudieron guardar los datos.' });
            }
        } catch (error: any) {
            setFeedback({ type: 'error', text: error?.response?.data?.message || 'Error de conexión.' });
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <Head title="Datos de Cobro de la Plataforma - OwOMarket" />

            <Dashboard user_uuid={user_id}>
                <Breadcrumb className="mb-5 hidden rounded bg-gray-50 px-5 py-3 lg:block dark:bg-gray-800">
                    <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                        Panel Principal
                    </BreadcrumbItem>
                    <BreadcrumbItem>Datos de Cobro de la Plataforma</BreadcrumbItem>
                </Breadcrumb>

                <div className="mb-5 space-y-1">
                    <h1 className="text-2xl font-black text-gray-900 dark:text-white">
                        Datos de Cobro de la Plataforma
                    </h1>
                    <p className="max-w-3xl text-xs text-gray-500">
                        Son la cuenta a la que transfiere el comprador de un pedido <strong>multi-tienda</strong>:
                        cobra OwOMarket y después liquida con cada comercio.{' '}
                        <strong>Un método que no esté completo no se ofrece en el checkout</strong>, para no enviar
                        dinero a una cuenta equivocada.
                    </p>
                </div>

                {feedback && (
                    <Alert color="failure" className="mb-4">
                        {feedback.text}
                    </Alert>
                )}

                <Card className="mb-5">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-xs font-bold text-gray-700 dark:text-gray-300">
                            Métodos que el comprador ve ahora mismo:
                        </span>

                        {active_methods.length === 0 ? (
                            <Badge color="failure" icon={HiExclamationCircle}>
                                Ninguno — el checkout central no acepta pagos
                            </Badge>
                        ) : (
                            active_methods.map((m) => (
                                <Badge key={m.id} color="success" icon={HiCheckCircle}>
                                    {m.name}
                                </Badge>
                            ))
                        )}
                    </div>
                </Card>

                <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <Card>
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-bold text-gray-900 dark:text-white">Pago Móvil (VES)</h2>
                            <Badge color={pagoMovilCompleto ? 'success' : 'warning'}>
                                {pagoMovilCompleto ? 'Se ofrece' : 'Incompleto: no se ofrece'}
                            </Badge>
                        </div>

                        <p className="text-[11px] text-gray-500">
                            Banco, documento y teléfono son obligatorios los tres: sin uno de ellos el comprador no
                            podría pagar correctamente.
                        </p>

                        <div className="space-y-3">
                            <div>
                                <Label htmlFor="bank">Banco receptor</Label>
                                <TextInput
                                    id="bank"
                                    value={form.central_pago_movil_bank_name}
                                    onChange={(e) => set('central_pago_movil_bank_name', e.target.value)}
                                    placeholder="0105 - Banco Mercantil"
                                />
                            </div>

                            <div>
                                <Label htmlFor="doc">Cédula / RIF</Label>
                                <TextInput
                                    id="doc"
                                    value={form.central_pago_movil_document_id}
                                    onChange={(e) => set('central_pago_movil_document_id', e.target.value)}
                                    placeholder="J-50999888-1"
                                />
                            </div>

                            <div>
                                <Label htmlFor="phone">Teléfono receptor</Label>
                                <TextInput
                                    id="phone"
                                    value={form.central_pago_movil_phone}
                                    onChange={(e) => set('central_pago_movil_phone', e.target.value)}
                                    placeholder="0424-5556677"
                                />
                            </div>

                            <div>
                                <Label htmlFor="holder">Titular (opcional)</Label>
                                <TextInput
                                    id="holder"
                                    value={form.central_pago_movil_holder_name}
                                    onChange={(e) => set('central_pago_movil_holder_name', e.target.value)}
                                    placeholder="OwoMarket C.A."
                                />
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-bold text-gray-900 dark:text-white">Binance Pay (USDT)</h2>
                            <Badge color={form.central_binance_pay_id.trim() !== '' ? 'success' : 'warning'}>
                                {form.central_binance_pay_id.trim() !== '' ? 'Se ofrece' : 'Sin configurar'}
                            </Badge>
                        </div>

                        <div>
                            <Label htmlFor="binance">Binance Pay ID</Label>
                            <TextInput
                                id="binance"
                                value={form.central_binance_pay_id}
                                onChange={(e) => set('central_binance_pay_id', e.target.value)}
                                placeholder="987654321"
                            />
                        </div>

                        <div className="pt-4">
                            <Button type="submit" color="blue" disabled={saving} className="w-full font-bold">
                                {saving ? 'Guardando…' : 'Guardar datos de cobro'}
                            </Button>
                        </div>
                    </Card>
                </form>
            </Dashboard>
        </>
    );
}
