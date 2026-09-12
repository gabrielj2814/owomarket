import { render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * «Mis pedidos» del escaparate (fase 1 del plan del escaparate).
 *
 * Lo que se vigila son **los tres estados que la pantalla no puede confundir**: no has entrado,
 * has entrado y no tienes nada, y tienes pedidos. Confundir el primero con el segundo le diría
 * a un comprador que no ha comprado nada cuando lo que pasa es que no ha iniciado sesión.
 *
 * Y que el botón de confirmar aparezca exactamente cuando el servidor dice que toca.
 */
const misPedidos = vi.fn();
const openAuthModal = vi.fn();

vi.mock('@/Services/StorefrontOrderServices', () => ({
    default: {
        misPedidos: (...args: unknown[]) => misPedidos(...args),
        getDeliveryStatus: vi.fn().mockResolvedValue({ data: null }),
        confirmDelivery: vi.fn(),
    },
}));

vi.mock('@/contexts/CustomerAuthContext', () => ({
    useCustomerAuth: () => ({ customer: { id: 'c-1' }, openAuthModal }),
}));

vi.mock('@/components/layouts/StorefrontLayout', () => ({
    default: ({ children }: { children?: React.ReactNode }) => <div>{children}</div>,
}));

const panelRenderizado = vi.fn();
vi.mock('@/components/ui/customer/DeliveryConfirmationPanel', () => ({
    default: ({ orderId }: { orderId: string }) => {
        panelRenderizado(orderId);
        return <div data-testid={`panel-${orderId}`}>panel</div>;
    },
}));

import StorefrontMyOrdersPage from '@/pages/marketplace/orders/StorefrontMyOrdersPage';

const pedido = (overrides: Record<string, unknown> = {}) => ({
    id: 'o-1',
    order_number: 'ORD-9001',
    status: 'processing',
    total: 45.5,
    currency: 'USD',
    created_at: '2026-09-09T10:00:00+00:00',
    items: [{ id: 'i-1', product_id: 'p-1', product_name: 'Auriculares', quantity: 1, price: 45.5 }],
    delivery: null,
    ...overrides,
});

describe('Mis pedidos en el escaparate', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        misPedidos.mockResolvedValue({ data: [pedido()] });
    });

    it('lista los pedidos con su importe y sus artículos', async () => {
        render(<StorefrontMyOrdersPage store_settings={{ store_name: 'Tienda Ana' }} />);

        expect(await screen.findByTestId('pedido-o-1')).toBeInTheDocument();
        expect(screen.getByText('ORD-9001')).toBeInTheDocument();
        expect(screen.getByText(/Auriculares/)).toBeInTheDocument();
    });

    it('sin sesión invita a entrar, no dice que no tienes nada', async () => {
        // EL TEST QUE IMPORTA de esta pantalla. Son dos cosas distintas, y confundirlas le dice
        // a un comprador que no ha comprado nada cuando solo le falta iniciar sesión.
        misPedidos.mockRejectedValue({ response: { status: 401 } });

        render(<StorefrontMyOrdersPage />);

        expect(await screen.findByTestId('pedidos-sin-sesion')).toHaveTextContent(/Entra para ver tus pedidos/i);
        expect(screen.queryByTestId('pedidos-vacio')).not.toBeInTheDocument();
    });

    it('con sesión y sin pedidos explica lo del invitado', async () => {
        // Quien compró como invitado no verá nada nunca: hay que decirle por qué, o parecerá
        // que sus compras se perdieron.
        misPedidos.mockResolvedValue({ data: [] });

        render(<StorefrontMyOrdersPage store_settings={{ store_name: 'Tienda Ana' }} />);

        expect(await screen.findByTestId('pedidos-vacio')).toHaveTextContent(/compraste como invitado/i);
        expect(screen.queryByTestId('pedidos-sin-sesion')).not.toBeInTheDocument();
    });

    it('enseña el panel de confirmación solo cuando el servidor dice que toca', async () => {
        misPedidos.mockResolvedValue({
            data: [
                pedido({
                    delivery: {
                        declared_delivered_at: '2026-09-10T10:00:00+00:00',
                        confirmed_at: null,
                        released_at: null,
                        can_confirm: true,
                    },
                }),
            ],
        });

        render(<StorefrontMyOrdersPage />);

        expect(await screen.findByTestId('panel-o-1')).toBeInTheDocument();
    });

    it('un pedido que no toca confirmar no enseña el panel', async () => {
        // `can_confirm` lo decide el servidor. Si la pantalla lo dedujera por su cuenta,
        // enseñaría un botón que el backend rechaza.
        misPedidos.mockResolvedValue({
            data: [
                pedido({
                    delivery: {
                        declared_delivered_at: null,
                        confirmed_at: null,
                        released_at: null,
                        can_confirm: false,
                    },
                }),
            ],
        });

        render(<StorefrontMyOrdersPage />);

        await screen.findByTestId('pedido-o-1');
        expect(screen.queryByTestId('panel-o-1')).not.toBeInTheDocument();
    });

    it('una entrega ya cerrada lo dice y no vuelve a pedir confirmación', async () => {
        misPedidos.mockResolvedValue({
            data: [
                pedido({
                    delivery: {
                        declared_delivered_at: '2026-09-01T10:00:00+00:00',
                        confirmed_at: '2026-09-02T10:00:00+00:00',
                        released_at: '2026-09-02T10:00:00+00:00',
                        can_confirm: false,
                    },
                }),
            ],
        });

        render(<StorefrontMyOrdersPage />);

        expect(await screen.findByTestId('recibido-o-1')).toHaveTextContent(/Entrega cerrada/i);
        expect(screen.queryByTestId('panel-o-1')).not.toBeInTheDocument();
    });

    it('un fallo de red se distingue de no tener pedidos', async () => {
        misPedidos.mockRejectedValue({ response: { status: 500 } });

        render(<StorefrontMyOrdersPage />);

        expect(await screen.findByTestId('pedidos-error')).toBeInTheDocument();
        await waitFor(() => expect(screen.queryByTestId('pedidos-vacio')).not.toBeInTheDocument());
    });
});
