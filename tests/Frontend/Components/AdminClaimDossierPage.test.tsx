import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Reclamaciones y expedientes del backoffice (subsistema 5, fase D).
 *
 * Lo que se vigila: que el listado no traiga identidad que no necesita, que la cronología esté
 * —es la mitad del expediente—, que una resolución por silencio se distinga de una respuesta, y
 * que el papel se pueda descargar, porque su destino es acompañar una denuncia.
 */
const listar = vi.fn();
const expedienteFn = vi.fn();

vi.mock('@/Services/AdminClaimServices', () => ({
    default: {
        listar: (...args: unknown[]) => listar(...args),
        expediente: (...args: unknown[]) => expedienteFn(...args),
        urlDelPdf: (id: string) => `/admin/api/claims/${id}/dossier.pdf`,
    },
}));

vi.mock('@inertiajs/react', () => ({ Head: () => null }));

vi.mock('@/components/layouts/Dashboard', () => ({
    default: ({ children }: { children?: React.ReactNode }) => <div>{children}</div>,
}));

import AdminClaimDossierPage from '@/pages/admin/support/AdminClaimDossierPage';

const fila = (overrides: Record<string, unknown> = {}) => ({
    id: 'c-1',
    order_number: 'ORD-9001',
    product_name: 'Auriculares',
    amount: 45.5,
    tenant_id: 'shop-1',
    tenant_name: 'Tienda de María',
    customer_email: 'ana@example.com',
    reason: 'defectuoso',
    status: 'requested',
    is_open: true,
    resolved_by: null,
    platform_covered_amount: 0,
    created_at: '2026-09-09T10:00:00+00:00',
    ...overrides,
});

const dossier = () => ({
    claim: {
        id: 'c-1',
        order_number: 'ORD-9001',
        product_name: 'Auriculares',
        amount: 45.5,
        reason: 'defectuoso',
        description: 'Llegó sin sonido.',
        photos: [],
        status: 'approved',
        delivered_at: '2026-08-20T10:00:00+00:00',
        claimed_at: '2026-08-25T10:00:00+00:00',
        resolved_at: '2026-08-30T10:00:00+00:00',
        resolved_by: 'timeout',
        resolution_notes: null,
        platform_covered_amount: 12.5,
    },
    customer: { name: 'Ana', document_id: 'V-1111111', email: 'ana@example.com', phone: '+58 412' },
    store: {
        tenant_id: 'shop-1',
        legal_name: 'María Pérez',
        cedula: 'V-12345678',
        nationality: 'V',
        rif: 'J-401234567',
        phone: '+58 412 1234567',
        address: 'Av. Principal, Caracas',
        kyc_status: 'verified',
        reputation: {
            level: 'bajo',
            reserve_percent: 20,
            deliveries: 3,
            deliveries_for_next: 10,
            unanswered_claims: 2,
            has_debt: false,
        },
    },
    delivery: {
        declared_delivered_at: '2026-08-20T10:00:00+00:00',
        confirmed_at: null,
        released_by: 'timeout',
        shipment_evidence: [{ url: '/x.jpg' }],
        confirmation_evidence: [],
    },
});

const pintar = (props: Record<string, unknown> = {}) =>
    render(
        <AdminClaimDossierPage
            user_id="admin-1"
            claims={[fila()] as never}
            pagination={{ total: 1, current_page: 1, per_page: 15, last_page: 1 }}
            metrics={{
                open_count: 1,
                timeout_count: 0,
                total_count: 1,
                coverage_month: { month: '2026-09', spent_usd: 0, threshold_usd: 2000, over: false },
            }}
            {...props}
        />,
    );

describe('Reclamaciones y expedientes del backoffice', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        expedienteFn.mockResolvedValue({ data: dossier() });
    });

    it('el listado no enseña ningún documento de identidad', () => {
        // Se mira muchas veces; que el dato sensible llegue solo al abrir el expediente es
        // justamente la separación que hace el backend.
        const { container } = pintar();

        expect(container.textContent).not.toContain('12345678');
        expect(container.textContent).not.toMatch(/\bRIF\b/);
    });

    it('destaca cuántas resolvió el reloj por silencio de la tienda', () => {
        // Es la señal que la decisión de garantías pide vigilar.
        pintar({ metrics: { open_count: 4, timeout_count: 7, total_count: 20 } });

        expect(screen.getByTestId('metrica-silencios')).toHaveTextContent('7');
        expect(screen.getByText(/resueltas por silencio/i)).toBeInTheDocument();
    });

    it('marca en la lista la que se resolvió sola', () => {
        pintar({ claims: [fila({ status: 'approved', is_open: false, resolved_by: 'timeout' })] });

        expect(screen.getByTestId('silencio-c-1')).toHaveTextContent(/por silencio/i);
    });

    it('el expediente trae la cronología con sus tres fechas', async () => {
        // Es la mitad del expediente: sin fechas no hay caso que defender.
        pintar();

        fireEvent.click(screen.getByRole('button', { name: 'Ver expediente' }));

        const cronologia = await screen.findByTestId('cronologia');
        expect(cronologia).toHaveTextContent(/Entrega registrada/);
        expect(cronologia).toHaveTextContent(/presenta la reclamación/);
        expect(cronologia).toHaveTextContent(/Resolución/);
    });

    it('el expediente distingue una resolución por vencimiento', async () => {
        pintar();

        fireEvent.click(screen.getByRole('button', { name: 'Ver expediente' }));

        expect(await screen.findByText(/la tienda no respondió/i)).toBeInTheDocument();
    });

    it('el expediente entrega la identidad de la tienda y avisa de que está pendiente de revisión', async () => {
        // pendiente-abogado: decisión de fase de desarrollo. El aviso en pantalla es lo que
        // impide que alguien dé por sentado que esto ya pasó por revisión legal.
        pintar();

        fireEvent.click(screen.getByRole('button', { name: 'Ver expediente' }));

        const identidad = await screen.findByTestId('identidad-tienda');
        expect(identidad).toHaveTextContent('V-12345678');
        expect(identidad).toHaveTextContent('J-401234567');
        expect(identidad).toHaveTextContent(/pendiente de revisión legal/i);
    });

    it('el expediente se puede descargar en PDF', async () => {
        // Su destino es acompañar una denuncia: tiene que salir de la pantalla.
        pintar();

        fireEvent.click(screen.getByRole('button', { name: 'Ver expediente' }));

        await screen.findByTestId('expediente');
        expect(screen.getByRole('link', { name: /Descargar PDF/i })).toHaveAttribute('href', '/admin/api/claims/c-1/dossier.pdf');
    });

    it('si el expediente falla, lo dice en vez de enseñar uno vacío', async () => {
        // Un expediente en blanco parece un caso sin pruebas, que es peor que un error.
        expedienteFn.mockRejectedValue({ response: { data: { message: 'Reclamación no encontrada.' } } });

        pintar();
        fireEvent.click(screen.getByRole('button', { name: 'Ver expediente' }));

        expect(await screen.findByTestId('claims-error')).toHaveTextContent('Reclamación no encontrada.');
        await waitFor(() => expect(screen.queryByTestId('expediente')).not.toBeInTheDocument());
    });

    it('sin reclamaciones abiertas lo dice con esas palabras', () => {
        pintar({ claims: [], metrics: { open_count: 0, timeout_count: 0, total_count: 0 } });

        expect(screen.getByTestId('claims-vacio')).toHaveTextContent('No hay reclamaciones abiertas.');
    });
});
