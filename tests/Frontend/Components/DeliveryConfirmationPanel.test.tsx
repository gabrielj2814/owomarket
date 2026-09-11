import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Subsistema 3: el panel con el que el comprador confirma que recibió su pedido.
 *
 * Lo que se vigila no es el diseño, es **quién puede liberar el dinero y cuándo**. El botón
 * de confirmar es ahora el sustituto de lo que antes hacía el comerciante desde su propia
 * API, así que enseñarlo cuando no toca es reabrir el agujero por la vía de la interfaz.
 */
const getDeliveryStatus = vi.fn();
const confirmDelivery = vi.fn();

vi.mock('@/Services/CustomerPortalServices', () => ({
    default: {
        getDeliveryStatus: (...args: unknown[]) => getDeliveryStatus(...args),
        confirmDelivery: (...args: unknown[]) => confirmDelivery(...args),
    },
}));

import DeliveryConfirmationPanel from '@/components/ui/customer/DeliveryConfirmationPanel';

const expediente = (overrides: Record<string, unknown> = {}) => ({
    data: {
        order_id: 'ord-1',
        shipment_evidence: [],
        confirmation_evidence: [],
        declared_delivered_at: null,
        confirmed_at: null,
        released_at: null,
        released_by: null,
        can_confirm: false,
        ...overrides,
    },
});

describe('Panel de confirmación de entrega', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('no ofrece confirmar mientras la tienda no declare la entrega', async () => {
        getDeliveryStatus.mockResolvedValue(expediente({ can_confirm: false }));

        render(<DeliveryConfirmationPanel orderId="ord-1" />);

        expect(await screen.findByText(/todavía no ha marcado este pedido como entregado/i)).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /confirmar que lo recibí/i })).not.toBeInTheDocument();
    });

    it('ofrece confirmar cuando la tienda ya declaró la entrega', async () => {
        getDeliveryStatus.mockResolvedValue(expediente({ can_confirm: true, declared_delivered_at: '2026-09-10T10:00:00Z' }));

        render(<DeliveryConfirmationPanel orderId="ord-1" />);

        expect(await screen.findByRole('button', { name: /confirmar que lo recibí/i })).toBeInTheDocument();
    });

    it('confirma sin adjuntar nada: la evidencia se pide, no se impone', async () => {
        getDeliveryStatus
            .mockResolvedValueOnce(expediente({ can_confirm: true }))
            .mockResolvedValueOnce(expediente({ can_confirm: false, released_at: '2026-09-11T10:00:00Z', released_by: 'customer' }));
        confirmDelivery.mockResolvedValue({ status: 'success' });

        render(<DeliveryConfirmationPanel orderId="ord-1" />);
        const user = userEvent.setup();

        await user.click(await screen.findByRole('button', { name: /confirmar que lo recibí/i }));

        await waitFor(() => expect(confirmDelivery).toHaveBeenCalledWith('ord-1', []));
    });

    it('muestra la evidencia que subió la tienda', async () => {
        // Las dos partes ven lo mismo: una prueba solo zanja una discusión si ambas la tienen
        // delante.
        getDeliveryStatus.mockResolvedValue(
            expediente({
                can_confirm: true,
                shipment_evidence: [{ url: '/storage/delivery-evidence/paquete.jpg', type: 'image', original_name: 'paquete.jpg' }],
            }),
        );

        render(<DeliveryConfirmationPanel orderId="ord-1" />);

        expect(await screen.findByAltText('paquete.jpg')).toBeInTheDocument();
    });

    it('una entrega ya cerrada no vuelve a ofrecer el botón', async () => {
        getDeliveryStatus.mockResolvedValue(
            expediente({ can_confirm: false, released_at: '2026-09-10T10:00:00Z', released_by: 'timeout' }),
        );

        render(<DeliveryConfirmationPanel orderId="ord-1" />);

        // Y dice POR QUE se cerró: dar por recibido algo que el comprador nunca confirmó no
        // es lo mismo que una confirmación suya, y ocultarlo le quitaría la única pista de
        // que el plazo corrió sin él.
        expect(await screen.findByTestId('delivery-closed')).toHaveTextContent(/al cumplirse el plazo/i);
        expect(screen.queryByRole('button', { name: /confirmar que lo recibí/i })).not.toBeInTheDocument();
    });

    it('no pinta nada si la tienda no ha registrado ningún expediente', async () => {
        getDeliveryStatus.mockResolvedValue({ data: null });

        const { container } = render(<DeliveryConfirmationPanel orderId="ord-1" />);

        await waitFor(() => expect(getDeliveryStatus).toHaveBeenCalled());
        expect(container).toBeEmptyDOMElement();
    });
});
