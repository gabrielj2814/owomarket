import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Reclamaciones de la tienda (subsistema 5, vista 2).
 *
 * **La pantalla que impide que el reloj lo apruebe todo por silencio.** Antes de que existiera,
 * `returns:auto-resolve` resolvía a favor del comprador cada reclamación que la tienda no
 * contestaba, y no había dónde contestar.
 *
 * Lo que se vigila aquí no es la lista: es que el comerciante vea **el reloj corriendo**, que
 * sepa **lo que cuesta aprobar** antes de pulsar, y que rechazar sin motivo no llegue a salir.
 */
const listar = vi.fn();
const resolver = vi.fn();

vi.mock('@/Services/TenantReturnServices', () => ({
    default: {
        listar: (...args: unknown[]) => listar(...args),
        resolver: (...args: unknown[]) => resolver(...args),
    },
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children }: { children?: React.ReactNode }) => <a>{children}</a>,
}));

vi.mock('@/components/layouts/Dashboard', () => ({
    default: ({ children }: { children?: React.ReactNode }) => <div>{children}</div>,
}));

import TenantReturnsPage from '@/pages/tenant/returns/TenantReturnsPage';

const reclamacion = (overrides: Record<string, unknown> = {}) => ({
    id: 'rec-1',
    order_number: 'ORD-9001',
    product_name: 'Auriculares',
    amount: 45.5,
    reason: 'defectuoso',
    description: 'Llegó sin sonido en un lado.',
    photos: [],
    status: 'requested',
    is_open: true,
    resolved_by: null,
    resolution_notes: null,
    days_left: 3,
    deadline_at: '2026-09-14T10:00:00+00:00',
    created_at: '2026-09-09T10:00:00+00:00',
    ...overrides,
});

const pintar = (props: Record<string, unknown> = {}) =>
    render(
        <TenantReturnsPage
            user_id="owner-1"
            tenants={[{ id: 'shop-1', name: 'Mi Tienda' }]}
            response_days={5}
            {...props}
        />,
    );

describe('Reclamaciones de la tienda', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        listar.mockResolvedValue({ data: [reclamacion()] });
    });

    it('enseña cuántos días quedan antes de que se resuelva sola', async () => {
        // EL DATO QUE HACE QUE LA PANTALLA SE USE. Sin él, el comerciante no sabe que hay un
        // plazo corriendo y descubre la reclamación cuando ya le revirtió la venta.
        pintar();

        expect(await screen.findByTestId('reloj-rec-1')).toHaveTextContent('Quedan 3 días para responder');
    });

    it('avisa de que callar le duplica la retención de todas sus ventas', async () => {
        // Una reclamación vencida cuenta como «sin responder» y baja la reputación a bajo, que
        // pasa la retención del 10% al 20%. Es lo que convierte «puedo responder» en «tengo
        // que responder».
        pintar();

        expect(await screen.findByTestId('aviso-reputacion')).toHaveTextContent(/duplica la retención/i);
    });

    it('dice lo que cuesta aprobar ANTES de que pulse', async () => {
        pintar();

        fireEvent.click(await screen.findByRole('button', { name: 'Aprobar' }));

        expect(await screen.findByText(/revierte la venta/i)).toBeInTheDocument();
        expect(screen.getByText(/salen de tu saldo/i)).toBeInTheDocument();
        expect(resolver).not.toHaveBeenCalled();
    });

    it('rechazar sin motivo no llega a salir', async () => {
        // El comprador ve su reclamación denegada: el motivo es lo único que tendrá para
        // entender por qué.
        pintar();

        fireEvent.click(await screen.findByRole('button', { name: 'Rechazar' }));
        fireEvent.click(screen.getAllByRole('button', { name: 'Rechazar' }).slice(-1)[0]);

        expect(await screen.findByRole('alert')).toHaveTextContent(/motivo del rechazo/i);
        expect(resolver).not.toHaveBeenCalled();
    });

    it('rechazar con motivo lo manda tal cual', async () => {
        resolver.mockResolvedValue({ message: 'Reclamación rechazada.' });
        pintar();

        fireEvent.click(await screen.findByRole('button', { name: 'Rechazar' }));
        fireEvent.change(await screen.findByLabelText('Motivo del rechazo'), {
            target: { value: 'Se entregó en perfecto estado, con firma de recepción.' },
        });
        fireEvent.click(screen.getAllByRole('button', { name: 'Rechazar' }).slice(-1)[0]);

        await waitFor(() =>
            expect(resolver).toHaveBeenCalledWith('rec-1', {
                approved: false,
                notes: 'Se entregó en perfecto estado, con firma de recepción.',
            }),
        );
    });

    it('si el reloj se adelanta, lo explica en vez de soltar un error genérico', async () => {
        // 409: la resolvió el comando entre que se cargó la lista y se pulsó el botón. El
        // comerciante merece saber que venció el plazo, no leer «error 409».
        resolver.mockRejectedValue({ response: { status: 409, data: { message: 'Ya resuelta.' } } });
        pintar();

        fireEvent.click(await screen.findByRole('button', { name: 'Aprobar' }));
        fireEvent.click(screen.getAllByRole('button', { name: 'Aprobar' }).slice(-1)[0]);

        expect(await screen.findByTestId('returns-aviso')).toHaveTextContent(/venció el plazo/i);
        // Y se refresca: la lista que tenía delante ya no era cierta.
        await waitFor(() => expect(listar).toHaveBeenCalledTimes(2));
    });

    it('una resuelta por silencio lo dice, y no ofrece botones', async () => {
        // No es lo mismo que la tienda respondiera: el comerciante tiene que ver que perdió la
        // venta por no contestar.
        listar.mockResolvedValue({
            data: [reclamacion({ is_open: false, status: 'approved', resolved_by: 'timeout', days_left: null })],
        });
        pintar();

        expect(await screen.findByTestId('por-silencio-rec-1')).toHaveTextContent(/venció el plazo sin respuesta/i);
        expect(screen.queryByRole('button', { name: 'Aprobar' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Rechazar' })).not.toBeInTheDocument();
    });

    it('el vacío explica qué llenará la lista', async () => {
        listar.mockResolvedValue({ data: [] });
        pintar();

        expect(await screen.findByTestId('returns-vacio')).toHaveTextContent(/cuando un comprador reclame/i);
    });

    it('sin tiendas no pide nada al servidor', async () => {
        pintar({ tenants: [] });

        expect(await screen.findByTestId('returns-sin-tienda')).toBeInTheDocument();
        expect(listar).not.toHaveBeenCalled();
    });
});
