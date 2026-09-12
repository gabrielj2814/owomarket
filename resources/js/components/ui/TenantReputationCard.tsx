import { Alert } from 'flowbite-react';
import React from 'react';
import { HiOutlineShieldCheck } from 'react-icons/hi2';

/**
 * El nivel de reputación de la tienda (subsistema 5, fase C · vista 3).
 *
 * **El nivel no es una insignia: decide cuánto se retiene de cada venta.** Por eso vive en la
 * billetera y no en un perfil — es donde el comerciante mira su dinero, y donde nota el efecto.
 *
 * `TenantReputation::progress()` existía sin que nadie lo expusiera, así que hasta ahora el
 * comerciante veía bajar su saldo disponible sin manera de saber que el motivo era su nivel.
 *
 * ## El tono importa aquí más que en ninguna otra pantalla
 *
 * Esto se lee cuando algo va mal. La decisión de garantías lo dice sin rodeos: *«un nivel que
 * baja sin decir por qué ni cómo se recupera no corrige a nadie — empuja a abrir otra tienda
 * con otro nombre»*. El objetivo es que sepa qué hacer, no que se sienta castigado: de ahí que
 * el camino de vuelta esté siempre visible, incluso en el nivel más bajo.
 */
export interface TenantReputationProgress {
    level: 'alto' | 'medio' | 'bajo';
    /** Cuánto se retiene de cada venta en este nivel. Lo dice el backend: mantener la tabla
     *  aquí significaría que el día que cambie, el comerciante lea un número que no se le aplica. */
    reserve_percent: number;
    deliveries: number;
    deliveries_for_next: number;
    unanswered_claims: number;
    has_debt: boolean;
}

interface TenantReputationCardProps {
    reputation: TenantReputationProgress | null;
}

/*
 * El color NO es decoracion: codifica el nivel, y con el la retencion. Los tonos exactos viven
 * en `tenantPanelTheme.alert`, para que otra pantalla que muestre lo mismo no invente los suyos.
 */
const APARIENCIA: Record<string, { titulo: string; color: string; icono: string }> = {
    alto: { titulo: 'Nivel alto', color: 'success', icono: 'text-emerald-600 dark:text-emerald-400' },
    medio: { titulo: 'Nivel medio', color: 'info', icono: 'text-sky-600 dark:text-sky-400' },
    bajo: { titulo: 'Nivel bajo', color: 'warning', icono: 'text-amber-600 dark:text-amber-400' },
};

const TenantReputationCard: React.FC<TenantReputationCardProps> = ({ reputation }) => {
    if (reputation === null) return null;

    const apariencia = APARIENCIA[reputation.level] ?? APARIENCIA.medio;
    const faltan = Math.max(0, reputation.deliveries_for_next - reputation.deliveries);

    return (
        <Alert data-testid="reputacion-card" color={apariencia.color} className="mb-4">
            <div className="flex items-start gap-3">
                <HiOutlineShieldCheck className={`mt-0.5 h-5 w-5 shrink-0 ${apariencia.icono}`} />
                <div className="min-w-0">
                    <p className="font-black">
                        {apariencia.titulo}: se retiene el {reputation.reserve_percent}% de cada venta.
                    </p>
                    <p className="mt-1">
                        Esa parte no se pierde: se guarda durante el plazo de reclamación y vuelve a tu saldo cuando
                        pasa.
                    </p>

                    {/*
                      * Los frenos van ANTES del camino de vuelta: si una tienda con
                      * reclamaciones sin responder solo lee «te faltan N entregas», acumula
                      * entregas y no sube, y concluye que el sistema miente.
                      */}
                    {reputation.unanswered_claims > 0 && (
                        <p data-testid="reputacion-silencios" className="mt-2 font-bold">
                            {reputation.unanswered_claims === 1
                                ? 'Tienes 1 reclamación que venció sin respuesta.'
                                : `Tienes ${reputation.unanswered_claims} reclamaciones que vencieron sin respuesta.`}{' '}
                            Mientras cuenten, el nivel se queda en bajo. Dejan de contar a los 90 días de la última, y
                            responder a tiempo evita que se sumen más.
                        </p>
                    )}

                    {reputation.has_debt && (
                        <p data-testid="reputacion-deuda" className="mt-2 font-bold">
                            Tienes una deuda pendiente con la plataforma. Mientras exista no se llega al nivel alto,
                            aunque acumules entregas. Se salda vendiendo: no te bloquea el cobro.
                        </p>
                    )}

                    {reputation.level === 'alto' ? (
                        <p className="mt-2">
                            Estás en el nivel con la retención más baja. Responder a las reclamaciones dentro del plazo
                            es lo que lo mantiene.
                        </p>
                    ) : (
                        <p data-testid="reputacion-camino" className="mt-2">
                            Llevas <strong>{reputation.deliveries}</strong> de{' '}
                            <strong>{reputation.deliveries_for_next}</strong> entregas confirmadas.{' '}
                            {faltan === 0
                                ? 'Ya tienes las entregas necesarias para subir.'
                                : faltan === 1
                                  ? 'Te falta 1 entrega confirmada para subir de nivel.'
                                  : `Te faltan ${faltan} entregas confirmadas para subir de nivel.`}
                        </p>
                    )}
                </div>
            </div>
        </Alert>
    );
};

export default TenantReputationCard;
