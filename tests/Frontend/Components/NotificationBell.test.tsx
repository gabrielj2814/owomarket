import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * La campana del buzón (fase 1 de notificaciones).
 *
 * Sustituye a una que **era decoración**: estaba dentro de la etiqueta del desplegable del
 * avatar, así que pulsarla abría el menú de perfil.
 *
 * Lo que se vigila son las tres decisiones que deciden si el buzón sirve o se ignora:
 *
 * 1. El contador **solo aparece si hay algo**. Un «0» permanente entrena a no mirar.
 * 2. Abrir la campana **no marca nada como leído**. Marcarlo borraría la única señal de que hay
 *    una reclamación esperando con el reloj corriendo.
 * 3. Un fallo de red **no se lee como «no tienes nada»**. Es el hallazgo N35, y aquí lo que se
 *    confunde con silencio tiene un plazo detrás.
 */
const buzon = vi.fn();
const marcarLeido = vi.fn();

vi.mock('@/Services/NotificationServices', () => ({
    default: {
        buzon: (...args: unknown[]) => buzon(...args),
        marcarLeido: (...args: unknown[]) => marcarLeido(...args),
    },
}));

import NotificationBell from '@/components/ui/NotificationBell';

const aviso = (overrides: Record<string, unknown> = {}) => ({
    id: 'n-1',
    read: false,
    created_at: new Date().toISOString(),
    type: 'claim.opened',
    title: 'Tienes una reclamación sin responder',
    body: 'Un comprador reclamó «Auriculares» del pedido ORD-1.',
    url: '/tenant/owner/backoffice/u-1/returns',
    days_left: 3,
    ...overrides,
});

const abrirCampana = async () => {
    // El contenido vive en un desplegable: hay que abrirlo para que se pinte.
    fireEvent.click(screen.getByLabelText(/notificaciones/i));
};

describe('Campana de notificaciones', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        buzon.mockResolvedValue({ data: { items: [aviso()], unread: 1 } });
        marcarLeido.mockResolvedValue({ data: { marked: 1 } });
    });

    it('enseña el contador solo cuando hay avisos sin leer', async () => {
        render(<NotificationBell audience="staff" />);

        expect(await screen.findByTestId('campana-contador')).toHaveTextContent('1');
    });

    it('sin nada sin leer no enseña contador', async () => {
        buzon.mockResolvedValue({ data: { items: [aviso({ read: true })], unread: 0 } });

        render(<NotificationBell audience="staff" />);

        await waitFor(() => expect(buzon).toHaveBeenCalled());
        expect(screen.queryByTestId('campana-contador')).not.toBeInTheDocument();
    });

    it('abrir la campana no marca nada como leído', async () => {
        /*
         * EL TEST QUE IMPORTA. Marcar al abrir dejaría el número limpio y borraría el rastro de
         * lo que aún no se ha atendido: el comerciante abriría por curiosidad y perdería la
         * única señal de que tiene una reclamación esperando.
         */
        render(<NotificationBell audience="staff" />);

        await screen.findByTestId('campana-contador');
        await abrirCampana();

        expect(marcarLeido).not.toHaveBeenCalled();
    });

    it('pulsar un aviso sí lo marca, porque entonces sí se ha visto', async () => {
        render(<NotificationBell audience="staff" />);

        await abrirCampana();
        fireEvent.click(await screen.findByTestId('aviso-n-1'));

        await waitFor(() => expect(marcarLeido).toHaveBeenCalledWith('staff', 'n-1'));
    });

    it('enseña la cuenta atrás del plazo, que es la razón de que el aviso exista', async () => {
        render(<NotificationBell audience="staff" />);

        await abrirCampana();

        expect(await screen.findByText(/3 días/i)).toBeInTheDocument();
    });

    it('un fallo de red no se lee como «no tienes nada»', async () => {
        buzon.mockRejectedValue(new Error('network'));

        render(<NotificationBell audience="staff" />);

        await abrirCampana();

        expect(await screen.findByTestId('campana-error')).toHaveTextContent(/No pudimos cargar/i);
        expect(screen.queryByTestId('campana-vacia')).not.toBeInTheDocument();
    });

    it('el buzón vacío invita en vez de disculparse', async () => {
        buzon.mockResolvedValue({ data: { items: [], unread: 0 } });

        render(<NotificationBell audience="staff" />);

        await abrirCampana();

        expect(await screen.findByTestId('campana-vacia')).toHaveTextContent(/necesite tu atención/i);
    });

    it('el comprador pide su propio buzón, no el del personal', async () => {
        render(<NotificationBell audience="customer" />);

        await waitFor(() => expect(buzon).toHaveBeenCalledWith('customer'));
    });
});
