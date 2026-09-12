import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Devoluciones del comprador (subsistema 5, vista del comprador).
 *
 * Lo que se vigila no es la lista: es **que el comprador entienda qué pasó con su reclamación**.
 * El backend ya guardaba `resolution_notes` y `resolved_by`, pero la pantalla solo pintaba una
 * insignia de color, así que quien veía «Rechazada» no tenía forma de saber por qué.
 *
 * Y que sin cédula no llegue a escribir la explicación entera para recibir un 422 al enviar.
 */
const getReturns = vi.fn();
const getOrders = vi.fn();
const createReturn = vi.fn();

vi.mock('@/Services/CustomerPortalServices', () => ({
    default: {
        getReturns: (...args: unknown[]) => getReturns(...args),
        getOrders: (...args: unknown[]) => getOrders(...args),
        createReturn: (...args: unknown[]) => createReturn(...args),
    },
}));

const customer = { id: 'c-1', name: 'Ana', email: 'ana@example.com', document_id: 'V-12345678' };
const useCustomerAuth = vi.fn(() => ({ customer }));

vi.mock('@/contexts/CustomerAuthContext', () => ({
    useCustomerAuth: () => useCustomerAuth(),
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, href }: { children?: React.ReactNode; href?: string }) => <a href={href}>{children}</a>,
}));

vi.mock('@/components/layouts/CustomerAccountLayout', () => ({
    default: ({ children }: { children?: React.ReactNode }) => <div>{children}</div>,
}));

import CustomerReturnsPage from '@/pages/customer/CustomerReturnsPage';

const reclamo = (overrides: Record<string, unknown> = {}) => ({
    id: 'r-1',
    order_id: 'o-1',
    order_number: 'ORD-9001',
    customer_id: 'c-1',
    product_id: 'p-1',
    product_name: 'Auriculares',
    tenant_id: 'shop-1',
    reason: 'Producto dañado o roto',
    description: 'Llegó sin sonido en un lado.',
    status: 'requested',
    admin_notes: null,
    resolved_by: null,
    resolution_notes: null,
    resolved_at: null,
    created_at: '2026-09-09T10:00:00+00:00',
    ...overrides,
});

describe('Devoluciones del comprador', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        useCustomerAuth.mockReturnValue({ customer });
        getOrders.mockResolvedValue({ data: [] });
        getReturns.mockResolvedValue({ data: [reclamo()] });
    });

    it('explica el estado con palabras, no solo con un color', async () => {
        // Un color no le dice a nadie si todavía tiene que esperar o si el asunto terminó.
        expect.assertions(1);
        render(<CustomerReturnsPage />);

        expect(await screen.findByTestId('estado-r-1')).toHaveTextContent('La tienda todavía no ha respondido.');
    });

    it('enseña el motivo del rechazo', async () => {
        // EL TEST QUE IMPORTA. Es lo único que el comprador tendrá para entender la decisión, y
        // la plataforma lo tenía guardado sin mostrarlo.
        getReturns.mockResolvedValue({
            data: [
                reclamo({
                    status: 'rejected',
                    resolved_by: 'merchant',
                    resolution_notes: 'Se entregó en perfecto estado, con firma de recepción.',
                }),
            ],
        });

        render(<CustomerReturnsPage />);

        expect(await screen.findByTestId('notas-tienda-r-1')).toHaveTextContent(
            'Se entregó en perfecto estado, con firma de recepción.',
        );
    });

    it('distingue una resolución por vencimiento de una respuesta de la tienda', async () => {
        // Aunque el resultado le favorezca igual, no es lo mismo que le contestaran.
        getReturns.mockResolvedValue({ data: [reclamo({ status: 'approved', resolved_by: 'timeout' })] });

        render(<CustomerReturnsPage />);

        expect(await screen.findByTestId('resolucion-r-1')).toHaveTextContent(
            /no respondió dentro del plazo/i,
        );
    });

    it('una respuesta real de la tienda se dice como tal', async () => {
        getReturns.mockResolvedValue({ data: [reclamo({ status: 'approved', resolved_by: 'merchant' })] });

        render(<CustomerReturnsPage />);

        expect(await screen.findByTestId('resolucion-r-1')).toHaveTextContent('Respondió la tienda.');
    });

    it('nunca enseña un identificador interno como si fuera una persona', async () => {
        // Si algún día se guardara ahí un id, no puede acabar en la pantalla de un cliente.
        getReturns.mockResolvedValue({
            data: [reclamo({ status: 'approved', resolved_by: '9f8e7d6c-1111-2222-3333-444455556666' })],
        });

        render(<CustomerReturnsPage />);

        const resolucion = await screen.findByTestId('resolucion-r-1');
        expect(resolucion).toHaveTextContent('La resolvió el equipo de OwOMarket.');
        expect(resolucion.textContent).not.toContain('9f8e7d6c');
    });

    it('una aprobación por silencio no se la atribuye a la tienda', async () => {
        // La pantalla llegó a decir «La tienda aceptó tu reclamación» justo encima de «la
        // tienda no respondió dentro del plazo». Una pantalla que se contradice no se cree, y
        // además le atribuía a la tienda una decisión que no tomó.
        getReturns.mockResolvedValue({ data: [reclamo({ status: 'approved', resolved_by: 'timeout' })] });

        render(<CustomerReturnsPage />);

        const estado = await screen.findByTestId('estado-r-1');
        expect(estado).toHaveTextContent('Tu reclamación se aprobó: se te devuelve el importe.');
        expect(estado.textContent).not.toMatch(/la tienda aceptó/i);
    });

    it('una aprobación de la tienda sí se la atribuye a ella', async () => {
        getReturns.mockResolvedValue({ data: [reclamo({ status: 'approved', resolved_by: 'merchant' })] });

        render(<CustomerReturnsPage />);

        expect(await screen.findByTestId('estado-r-1')).toHaveTextContent('La tienda aceptó tu reclamación');
    });

    it('una reclamación abierta no dice cómo se resolvió', async () => {
        render(<CustomerReturnsPage />);

        await screen.findByTestId('estado-r-1');
        expect(screen.queryByTestId('resolucion-r-1')).not.toBeInTheDocument();
    });

    it('sin cédula no abre el formulario: manda al perfil', async () => {
        // Sin ella el backend devuelve 422 al enviar, y descubrirlo entonces es perder la
        // explicación ya escrita.
        useCustomerAuth.mockReturnValue({ customer: { ...customer, document_id: '' } });

        render(<CustomerReturnsPage />);

        fireEvent.click(await screen.findByRole('button', { name: /Nueva Devolución/i }));

        expect(await screen.findByTestId('aviso-cedula')).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /Ir a mi perfil/i })).toHaveAttribute('href', '/account/profile');
        expect(screen.queryByLabelText('Explicación Detallada')).not.toBeInTheDocument();
    });

    it('con cédula abre el formulario directamente', async () => {
        render(<CustomerReturnsPage />);

        fireEvent.click(await screen.findByRole('button', { name: /Nueva Devolución/i }));

        expect(await screen.findByLabelText('Explicación Detallada')).toBeInTheDocument();
        expect(screen.queryByTestId('aviso-cedula')).not.toBeInTheDocument();
    });

    it('el vacío invita a actuar', async () => {
        getReturns.mockResolvedValue({ data: [] });

        render(<CustomerReturnsPage />);

        expect(await screen.findByTestId('returns-vacio')).toHaveTextContent(/No tienes devoluciones en curso/i);
    });

    it('la respuesta de la tienda y la del soporte no se confunden', async () => {
        // Son dos voces distintas: fundirlas dejaría al comprador sin saber quién le contestó.
        getReturns.mockResolvedValue({
            data: [
                reclamo({
                    status: 'rejected',
                    resolved_by: 'merchant',
                    resolution_notes: 'El producto llegó bien.',
                    admin_notes: 'Revisamos el caso y confirmamos la entrega.',
                }),
            ],
        });

        render(<CustomerReturnsPage />);

        expect(await screen.findByTestId('notas-tienda-r-1')).toHaveTextContent('Respuesta de la tienda');
        await waitFor(() => expect(screen.getByText(/Respuesta del Soporte/i)).toBeInTheDocument());
    });
});
