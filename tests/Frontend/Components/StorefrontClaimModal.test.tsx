import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * El formulario con el que un comprador de tienda abre una reclamación (fase 2 del escaparate).
 *
 * Lo que se vigila es lo que le cuesta caro al comprador si falla: que el texto que escribe
 * llegue con el artículo correcto, que un error del servidor se le enseñe **tal cual** —es el
 * que explica la causa concreta— y que reabrir el modal sobre otro artículo no arrastre lo que
 * escribió para el anterior.
 */
const crearReclamacion = vi.fn();

vi.mock('@/Services/StorefrontOrderServices', () => ({
    default: {
        crearReclamacion: (...args: unknown[]) => crearReclamacion(...args),
    },
}));

import StorefrontClaimModal from '@/components/ui/storefront/StorefrontClaimModal';

const onClose = vi.fn();
const onCreated = vi.fn();

const montar = (props: Partial<React.ComponentProps<typeof StorefrontClaimModal>> = {}) =>
    render(<StorefrontClaimModal open orderId="o-1" productId="p-1" productName="Auriculares" onClose={onClose} onCreated={onCreated} {...props} />);

describe('Reclamar desde el escaparate', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        crearReclamacion.mockResolvedValue({ status: 'success' });
    });

    it('envía el artículo del que se partió, no uno elegido a mano', async () => {
        montar();

        fireEvent.change(screen.getByLabelText(/Qué pasó/i), {
            target: { value: 'Llegó con la diadema partida.' },
        });
        fireEvent.click(screen.getByRole('button', { name: /Enviar reclamación/i }));

        await waitFor(() =>
            expect(crearReclamacion).toHaveBeenCalledWith({
                order_id: 'o-1',
                product_id: 'p-1',
                reason: 'Producto dañado o roto',
                description: 'Llegó con la diadema partida.',
            }),
        );
        expect(onCreated).toHaveBeenCalled();
    });

    it('el mensaje del servidor se enseña tal cual', async () => {
        /*
         * Es el que dice la causa concreta —sin cédula, fuera de plazo, ya hay una viva—. Una
         * frase genérica obligaría al comprador a adivinar cuál de las tres es.
         */
        crearReclamacion.mockRejectedValue({
            response: { data: { message: 'El plazo para reclamar este pedido venció (60 días desde la entrega).' } },
        });

        montar();

        fireEvent.change(screen.getByLabelText(/Qué pasó/i), { target: { value: 'Algo pasó.' } });
        fireEvent.click(screen.getByRole('button', { name: /Enviar reclamación/i }));

        expect(await screen.findByTestId('reclamacion-error')).toHaveTextContent(/El plazo para reclamar/i);
        expect(onCreated).not.toHaveBeenCalled();
    });

    it('un fallo sin mensaje no deja al comprador sin explicación', async () => {
        crearReclamacion.mockRejectedValue(new Error('network'));

        montar();

        fireEvent.change(screen.getByLabelText(/Qué pasó/i), { target: { value: 'Algo pasó.' } });
        fireEvent.click(screen.getByRole('button', { name: /Enviar reclamación/i }));

        expect(await screen.findByTestId('reclamacion-error')).toHaveTextContent(/Vuelve a intentarlo/i);
    });

    it('cambiar de artículo no arrastra lo escrito para el anterior', async () => {
        // Sin esto, la explicación de un producto reaparece sobre OTRO, y quien no se fije
        // reclama lo que no quería.
        const { rerender } = montar();

        fireEvent.change(screen.getByLabelText(/Qué pasó/i), { target: { value: 'Texto del primero.' } });

        rerender(<StorefrontClaimModal open orderId="o-1" productId="p-2" productName="Teclado" onClose={onClose} onCreated={onCreated} />);

        await waitFor(() => expect(screen.getByLabelText(/Qué pasó/i)).toHaveValue(''));
    });
});
