import Dashboard from '@/components/layouts/Dashboard';
import getCSRFToken from '@/utils/getCSRFToken';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { Alert, Badge, Breadcrumb, BreadcrumbItem, Button, Card, Label, TextInput } from 'flowbite-react';
import React, { useState } from 'react';
import { HiExclamation, HiHome } from 'react-icons/hi';

interface CoverageMonth {
    month: string;
    label: string;
    spent_usd: number;
    claims: number;
    over: boolean;
}

interface Props {
    user_id: string;
    settings: Record<string, string>;
    defaults: Record<string, string>;
    coverage_months: CoverageMonth[];
    coverage_threshold: number;
}

/**
 * Las reglas del dinero en tránsito (subsistemas 3, 4 y 5).
 *
 * ## Qué arregla
 *
 * Los ocho ajustes ya estaban en la lista blanca del backend y **ninguno tenía campo en
 * ninguna pantalla**: los números que gobiernan cuándo cobra un comerciante, cuánto se aparta
 * y hasta cuándo se puede reclamar se cambiaban escribiendo en la base de datos a mano.
 *
 * ## Las tres decisiones de esta pantalla
 *
 * 1. **Agrupados por la pregunta que responden**, siguiendo el viaje del dinero, no por número
 *    de subsistema. Al administrador le importa «¿cuándo cobra la tienda?», no «¿esto es del 3
 *    o del 4?».
 * 2. **Vacío significa algo, y se dice cuál.** El marcador de posición lleva el valor por
 *    defecto que usa el código, así que de un vistazo se ve qué está tocado a mano. Antes no
 *    había forma de saberlo.
 * 3. **El aviso del porcentaje está donde se toca.** Con un número ahí, el sistema de
 *    reputación deja de aplicarse a todas las tiendas — y eso no puede seguir siendo un
 *    secreto del código.
 *
 * ## El techo mensual
 *
 * Va arriba porque es lo único de esta pantalla que hay que **mirar**; lo demás es para
 * cambiar. Se enseñan seis meses en vez del actual solo: un mes que se pasó sigue viéndose
 * después, aunque nadie mirara ese día.
 */
interface Campo {
    key: string;
    label: string;
    help: string;
    suffix?: string;
}

const GRUPOS: Array<{ titulo: string; descripcion: string; campos: Campo[] }> = [
    {
        titulo: 'Cuándo cobra el comerciante',
        descripcion:
            'El dinero de una venta no llega a la tienda hasta que el comprador confirma que recibió. Estos dos números dicen cuánto se espera y cuánto más se retiene después.',
        campos: [
            {
                key: 'central_delivery_confirmation_days',
                label: 'Espera de confirmación',
                suffix: 'días',
                help: 'Lo que se espera a que el comprador diga que le llegó. Pasado el plazo la venta se libera igual, para que un comprador que no contesta no congele el dinero de la tienda.',
            },
            {
                key: 'central_payout_hold_days',
                label: 'Retención tras entregar',
                suffix: 'días',
                help: 'Desde que la venta se libera hasta que el importe es retirable. Cero es válido: significa que lo entregado se puede retirar en el acto.',
            },
        ],
    },
    {
        titulo: 'El fondo de garantía',
        descripcion:
            'Una parte de lo que gana la tienda se aparta al liberar la venta y sigue retenida un tiempo más. Es el colchón con el que se paga una reclamación sin tener que perseguir a nadie.',
        campos: [
            {
                key: 'central_guarantee_reserve_percent',
                label: 'Porcentaje retenido',
                suffix: '%',
                help: 'Déjalo vacío salvo que sepas lo que haces: vacío significa que el porcentaje lo decide la reputación de cada tienda.',
            },
            {
                key: 'central_guarantee_reserve_days',
                label: 'Días retenido',
                suffix: 'días',
                help: 'Tiene que cubrir el plazo para reclamar más el de respuesta de la tienda. Más allá de eso, es dinero del comerciante retenido cuando reclamar ya era imposible.',
            },
        ],
    },
    {
        titulo: 'Las reclamaciones',
        descripcion: 'Cuánto tiempo tiene el comprador, cuánto la tienda, y hasta dónde llega la plataforma cuando el saldo de la tienda no alcanza.',
        campos: [
            {
                key: 'central_claim_window_days',
                label: 'Ventana para reclamar',
                suffix: 'días',
                help: 'Contados desde la entrega. Es la promesa que ve el comprador, así que subirlo es fácil y bajarlo le quita derechos a quien ya compró.',
            },
            {
                key: 'central_claim_response_days',
                label: 'Plazo de respuesta de la tienda',
                suffix: 'días',
                help: 'Si la tienda no contesta dentro del plazo, la reclamación se resuelve a favor del comprador.',
            },
            {
                key: 'central_claim_coverage_cap',
                label: 'Tope por reclamación',
                suffix: '$',
                help: 'Lo máximo que pone la plataforma en una reclamación cuando el saldo de la tienda no da. Es un muro de verdad, y una regla publicable.',
            },
            {
                key: 'central_claim_monthly_alarm_usd',
                label: 'Techo mensual de alarma',
                suffix: '$',
                help: 'No corta ningún pago: marca el mes para revisarlo. Si fuera un muro, los compradores de final de mes se quedarían sin garantía por algo que no tiene que ver con su compra.',
            },
        ],
    },
];

const CLAVES = GRUPOS.flatMap((g) => g.campos.map((c) => c.key));

const dolares = (n: number) => `$${n.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function AdminGuaranteeRulesPage({ user_id, settings, defaults, coverage_months, coverage_threshold }: Props) {
    const [form, setForm] = useState<Record<string, string>>(Object.fromEntries(CLAVES.map((k) => [k, settings[k] ?? ''])));
    const [saving, setSaving] = useState(false);
    const [feedback, setFeedback] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const set = (key: string, value: string) => setForm((prev) => ({ ...prev, [key]: value }));

    const mesActual = coverage_months[0];
    const mesesPasados = coverage_months.slice(1).filter((m) => m.over);
    const reputacionAnulada = form.central_guarantee_reserve_percent.trim() !== '';

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setFeedback(null);

        try {
            const res = await axios.put('/admin/backoffice/payment-settings', form, {
                headers: { 'X-CSRF-TOKEN': getCSRFToken() },
            });

            if (res.data?.status === 'success') {
                // Se recarga porque el techo y la tabla de meses se calculan en el servidor:
                // dejarlos con los valores viejos junto al umbral nuevo sería enseñar una
                // comparación que ya no es cierta.
                window.location.reload();
            } else {
                setFeedback({ type: 'error', text: res.data?.message || 'No se pudieron guardar las reglas.' });
            }
        } catch (error: any) {
            const errores = error?.response?.data?.errors;
            setFeedback({
                type: 'error',
                text: errores ? Object.values(errores).flat().join(' ') : error?.response?.data?.message || 'Error de conexión.',
            });
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <Head title="Reglas de garantía - OwOMarket" />

            <Dashboard user_uuid={user_id}>
                <Breadcrumb className="mb-5 hidden rounded bg-gray-50 px-5 py-3 lg:block dark:bg-gray-800">
                    <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                        Panel Principal
                    </BreadcrumbItem>
                    <BreadcrumbItem>Reglas de garantía</BreadcrumbItem>
                </Breadcrumb>

                <div className="mb-5 space-y-1">
                    <h1 className="text-2xl font-black text-gray-900 dark:text-white">Reglas de garantía</h1>
                    <p className="max-w-3xl text-xs text-gray-500">
                        Los ocho números que gobiernan el dinero mientras va del comprador a la tienda. Cambiarlos afecta a las ventas que ocurran a
                        partir de ahora, no a las que ya están en curso.
                    </p>
                </div>

                {feedback && (
                    <Alert color="failure" className="mb-4">
                        {feedback.text}
                    </Alert>
                )}

                <Card className="mb-5">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 className="text-sm font-bold text-gray-900 dark:text-white">Lo que ha puesto la plataforma este mes</h2>
                            <p className="mt-0.5 text-[11px] text-gray-500">
                                Lo que se pagó de reclamaciones porque el saldo de la tienda no daba. Cada venta convertida a su propia tasa.
                            </p>
                        </div>
                        <div className="text-right">
                            <div
                                data-testid="cobertura-mes"
                                className={`text-3xl font-black tabular-nums ${
                                    mesActual?.over ? 'text-red-600 dark:text-red-500' : 'text-gray-900 dark:text-white'
                                }`}
                            >
                                {dolares(mesActual?.spent_usd ?? 0)}
                            </div>
                            <div className="text-[11px] text-gray-500">
                                de {dolares(coverage_threshold)} · {mesActual?.label}
                            </div>
                        </div>
                    </div>

                    {mesActual?.over && (
                        <Alert color="warning" icon={HiExclamation} data-testid="cobertura-superado">
                            <span className="font-bold">Este mes pasó del techo.</span> No se ha cortado ningún pago — el techo es una alarma—. Toca
                            mirar qué lo causa: una tienda concreta, un fallo en el flujo de entrega, o un abuso.
                        </Alert>
                    )}

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="text-gray-500">
                                <tr>
                                    <th className="py-1.5 font-semibold">Mes</th>
                                    <th className="py-1.5 text-right font-semibold">Puesto</th>
                                    <th className="py-1.5 text-right font-semibold">Reclamaciones</th>
                                    <th className="py-1.5 text-right font-semibold">Estado</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                {coverage_months.map((m) => (
                                    <tr key={m.month} data-testid={`mes-${m.month}`}>
                                        <td className="py-1.5 text-gray-700 dark:text-gray-300">{m.label}</td>
                                        <td className="py-1.5 text-right text-gray-900 tabular-nums dark:text-white">{dolares(m.spent_usd)}</td>
                                        <td className="py-1.5 text-right text-gray-500 tabular-nums">{m.claims}</td>
                                        <td className="py-1.5 text-right">
                                            {m.over ? (
                                                <Badge className="inline-flex" color="failure">
                                                    Pasó del techo
                                                </Badge>
                                            ) : (
                                                <span className="text-gray-400">—</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {mesesPasados.length > 0 && (
                        <p className="text-[11px] text-gray-500">
                            {mesesPasados.length === 1
                                ? `${mesesPasados[0].label} también pasó del techo y sigue sin revisar aquí.`
                                : `${mesesPasados.length} meses anteriores también pasaron del techo.`}
                        </p>
                    )}
                </Card>

                <form onSubmit={handleSubmit} className="space-y-5">
                    {GRUPOS.map((grupo) => (
                        <Card key={grupo.titulo}>
                            <div>
                                <h2 className="text-sm font-bold text-gray-900 dark:text-white">{grupo.titulo}</h2>
                                <p className="mt-0.5 max-w-3xl text-[11px] text-gray-500">{grupo.descripcion}</p>
                            </div>

                            <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
                                {grupo.campos.map((campo) => (
                                    <div key={campo.key}>
                                        <Label htmlFor={campo.key}>
                                            {campo.label}
                                            {campo.suffix && <span className="ml-1 font-normal text-gray-400">({campo.suffix})</span>}
                                        </Label>
                                        <TextInput
                                            id={campo.key}
                                            value={form[campo.key]}
                                            onChange={(e) => set(campo.key, e.target.value)}
                                            placeholder={defaults[campo.key]}
                                        />
                                        <p className="mt-1 text-[11px] text-gray-500">{campo.help}</p>

                                        {campo.key === 'central_guarantee_reserve_percent' && reputacionAnulada && (
                                            <Alert color="warning" icon={HiExclamation} className="mt-2" data-testid="aviso-reputacion">
                                                Con un número aquí,{' '}
                                                <span className="font-bold">
                                                    todas las tiendas retienen lo mismo y la reputación deja de aplicarse
                                                </span>
                                                . Vacío = manda el nivel de cada tienda.
                                            </Alert>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </Card>
                    ))}

                    <div className="flex items-center justify-between gap-4">
                        <p className="text-[11px] text-gray-500">Un campo vacío usa el valor por defecto, que es el que aparece en gris.</p>
                        <Button type="submit" color="blue" disabled={saving} className="font-bold">
                            {saving ? 'Guardando…' : 'Guardar reglas'}
                        </Button>
                    </div>
                </form>
            </Dashboard>
        </>
    );
}
