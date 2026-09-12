import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Las reglas de garantía del backoffice.
 *
 * Los ocho ajustes que gobiernan el dinero existían en el backend y **no tenían campo en
 * ninguna pantalla**: se cambiaban escribiendo en la base de datos a mano.
 *
 * Lo que se vigila aquí son las tres cosas que la pantalla tiene que decir bien, porque el daño
 * de equivocarse en ellas es dinero:
 *
 * 1. Que **vacío signifique algo visible** — el valor por defecto como marcador de posición—,
 *    o no hay forma de saber qué está tocado a mano.
 * 2. Que el aviso del porcentaje de reserva aparezca **cuando ese campo tiene valor**, porque
 *    es cuando el sistema de reputación deja de aplicarse.
 * 3. Que un mes que pasó del techo se vea, incluso siendo un mes pasado que nadie miró.
 */
const put = vi.fn();

vi.mock('axios', () => ({
    default: { put: (...args: unknown[]) => put(...args) },
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
}));

vi.mock('@/components/layouts/Dashboard', () => ({
    default: ({ children }: { children?: React.ReactNode }) => <div>{children}</div>,
}));

import AdminGuaranteeRulesPage from '@/pages/admin/guarantee/AdminGuaranteeRulesPage';

const DEFAULTS = {
    central_delivery_confirmation_days: '7',
    central_payout_hold_days: '1',
    central_guarantee_reserve_percent: 'según reputación',
    central_guarantee_reserve_days: '30',
    central_claim_window_days: '14',
    central_claim_response_days: '5',
    central_claim_coverage_cap: '200',
    central_claim_monthly_alarm_usd: '2000',
};

const mes = (overrides: Partial<Record<string, unknown>> = {}) => ({
    month: '2026-09',
    label: 'septiembre 2026',
    spent_usd: 0,
    claims: 0,
    over: false,
    ...overrides,
});

const pintar = (props: Record<string, unknown> = {}) =>
    render(
        <AdminGuaranteeRulesPage
            user_id="admin-1"
            settings={{}}
            defaults={DEFAULTS}
            coverage_months={[mes()]}
            coverage_threshold={2000}
            {...props}
        />,
    );

describe('Reglas de garantía', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        put.mockResolvedValue({ data: { status: 'success' } });
    });

    it('un campo sin configurar enseña el valor por defecto como marcador', () => {
        // Es lo que convierte «vacío» en información en vez de en duda. Antes de esta pantalla
        // no había forma de saber si un número estaba puesto a mano o venía del código.
        pintar();

        expect(screen.getByLabelText(/Ventana para reclamar/i)).toHaveAttribute('placeholder', '14');
        expect(screen.getByLabelText(/Ventana para reclamar/i)).toHaveValue('');
    });

    it('un campo configurado enseña su valor, no el defecto', () => {
        pintar({ settings: { central_claim_window_days: '21' } });

        expect(screen.getByLabelText(/Ventana para reclamar/i)).toHaveValue('21');
    });

    it('no avisa de la reputación mientras el porcentaje esté vacío', () => {
        pintar();

        expect(screen.queryByTestId('aviso-reputacion')).not.toBeInTheDocument();
    });

    it('avisa de que la reputación deja de aplicarse en cuanto se escribe un porcentaje', async () => {
        /*
         * EL AVISO QUE IMPORTA. Con un número ahí, `ReleaseOrderCommissionUseCase` ignora el
         * nivel de cada tienda y todas retienen lo mismo: el subsistema 4 se aplana entero. Una
         * fila que alguien dejó puesta probando hacía eso **sin que se viera en ningún sitio**.
         */
        pintar();

        fireEvent.change(screen.getByLabelText(/Porcentaje retenido/i), { target: { value: '15' } });

        expect(await screen.findByTestId('aviso-reputacion')).toHaveTextContent(/la reputación deja de/i);
    });

    it('marca el mes que pasó del techo', () => {
        pintar({
            coverage_months: [mes({ spent_usd: 3200.5, over: true, claims: 4 })],
            coverage_threshold: 2000,
        });

        expect(screen.getByTestId('cobertura-superado')).toHaveTextContent(/No se ha cortado ningún pago/i);
        expect(screen.getByTestId('cobertura-mes')).toHaveTextContent('3.200,50');
    });

    it('un mes pasado que se pasó del techo se sigue viendo', () => {
        // Por esto no hay comando ni aviso guardado: la alarma se deriva de datos que ya no se
        // mueven, así que no depende de que alguien mirara ese día.
        pintar({
            coverage_months: [mes(), mes({ month: '2026-08', label: 'agosto 2026', spent_usd: 2500, over: true, claims: 3 })],
        });

        expect(screen.getByTestId('mes-2026-08')).toHaveTextContent(/Pasó del techo/i);
        expect(screen.queryByTestId('cobertura-superado')).not.toBeInTheDocument();
    });

    it('guarda las ocho reglas y ninguna clave de más', async () => {
        /*
         * Se manda al MISMO endpoint que los datos de cobro. Mandar una clave bancaria vacía
         * desde aquí la borraría, y el checkout central se quedaría sin métodos de pago.
         */
        pintar({ settings: { central_claim_window_days: '14' } });

        fireEvent.click(screen.getByRole('button', { name: /Guardar reglas/i }));

        await waitFor(() => expect(put).toHaveBeenCalled());

        const enviado = put.mock.calls[0][1] as Record<string, string>;
        expect(Object.keys(enviado).sort()).toEqual(Object.keys(DEFAULTS).sort());
    });
});
