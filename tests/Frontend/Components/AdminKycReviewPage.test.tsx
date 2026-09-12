import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * La pantalla de revisión de identidad (subsistema 1, vista 1).
 *
 * **Es la pantalla que deja cobrar a las tiendas.** Hasta que existió, el KYC exigía identidad
 * verificada para retirar y no había forma de verificar a nadie.
 *
 * Lo que se vigila aquí no es la tabla: es que el documento de identidad no acabe en pantalla,
 * que rechazar sin motivo no llegue a salir, y que el aviso de identidad repetida se lea como
 * contexto y no como una acusación --porque un administrador que lo lea como acusación rechaza
 * por reflejo a un dueño con dos negocios, que es algo perfectamente normal--.
 */
const listar = vi.fn();
const revisar = vi.fn();
const coincidenciasDeIdentidad = vi.fn();

vi.mock('@/Services/AdminKycServices', () => ({
    default: {
        listar: (...args: unknown[]) => listar(...args),
        revisar: (...args: unknown[]) => revisar(...args),
        coincidenciasDeIdentidad: (...args: unknown[]) => coincidenciasDeIdentidad(...args),
    },
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
}));

vi.mock('@/components/layouts/Dashboard', () => ({
    default: ({ children }: { children?: React.ReactNode }) => <div>{children}</div>,
}));

import AdminKycReviewPage from '@/pages/admin/kyc/AdminKycReviewPage';

const perfil = (overrides: Record<string, unknown> = {}) => ({
    id: 'kyc-1',
    tenant_id: 'shop-1',
    tenant_name: 'Tienda de María',
    tenant_slug: 'tienda-maria',
    tenant_status: 'active',
    legal_name: 'María Pérez',
    nationality: 'V',
    phone: '+58 412 1234567',
    address: 'Av. Principal, Caracas',
    has_document: true,
    status: 'pending',
    rejection_reason: null,
    reviewed_at: null,
    reviewed_by: null,
    created_at: '2026-09-01T10:00:00+00:00',
    ...overrides,
});

const pintar = (props: Record<string, unknown> = {}) =>
    render(
        <AdminKycReviewPage
            user_id="admin-1"
            profiles={[perfil()] as never}
            pagination={{ total: 1, current_page: 1, per_page: 15, last_page: 1 }}
            metrics={{ pending_count: 1, verified_count: 0, rejected_count: 0 }}
            {...props}
        />,
    );

describe('Revisión de identidad de comerciantes', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        coincidenciasDeIdentidad.mockResolvedValue({ data: [] });
    });

    it('dice cuántas tiendas no pueden cobrar', () => {
        // El número no es decorativo: es la cuenta de comerciantes con el dinero retenido.
        pintar();

        expect(screen.getByTestId('metrica-pendientes')).toHaveTextContent('1');
        expect(screen.getByText(/no puede cobrar/i)).toBeInTheDocument();
    });

    it('no enseña la cédula ni el RIF en ningún sitio', () => {
        // Están cifrados en reposo. Si aparecen en pantalla, acaban en una captura o en un
        // ticket de soporte y el cifrado deja de servir para nada.
        const { container } = pintar();

        expect(container.textContent).not.toMatch(/c[eé]dula/i);
        expect(container.textContent).not.toMatch(/\bRIF\b/);
    });

    it('verificar avisa de que la tienda ya puede retirar', async () => {
        revisar.mockResolvedValue({ message: 'Identidad verificada. La tienda ya puede solicitar retiros.' });
        listar.mockResolvedValue({
            data: {
                profiles: [],
                pagination: { total: 0, current_page: 1, per_page: 15, last_page: 1 },
                metrics: { pending_count: 0, verified_count: 1, rejected_count: 0 },
            },
        });

        pintar();
        fireEvent.click(screen.getByRole('button', { name: 'Verificar' }));

        // El aviso de lo que está en juego va ANTES de pulsar, no después.
        expect(await screen.findByText(/podrá solicitar retiros/i)).toBeInTheDocument();

        fireEvent.click(screen.getAllByRole('button', { name: 'Verificar' }).slice(-1)[0]);

        await waitFor(() => expect(revisar).toHaveBeenCalledWith('kyc-1', { approved: true, reason: undefined }));
        expect(await screen.findByTestId('kyc-aviso')).toHaveTextContent(/ya puede solicitar retiros/i);
    });

    it('rechazar sin motivo no llega a salir, y el error señala el campo', async () => {
        // EL TEST QUE IMPORTA del rechazo: el comerciante ve el cobro bloqueado y el motivo es
        // lo único que tendrá para corregir.
        pintar();
        fireEvent.click(screen.getByRole('button', { name: 'Rechazar' }));

        fireEvent.click(screen.getAllByRole('button', { name: 'Rechazar' }).slice(-1)[0]);

        expect(await screen.findByRole('alert')).toHaveTextContent(/motivo del rechazo/i);
        expect(revisar).not.toHaveBeenCalled();
    });

    it('rechazar con motivo lo manda tal cual', async () => {
        revisar.mockResolvedValue({ message: 'Expediente rechazado.' });
        listar.mockResolvedValue({
            data: {
                profiles: [],
                pagination: { total: 0, current_page: 1, per_page: 15, last_page: 1 },
                metrics: { pending_count: 0, verified_count: 0, rejected_count: 1 },
            },
        });

        pintar();
        fireEvent.click(screen.getByRole('button', { name: 'Rechazar' }));

        fireEvent.change(await screen.findByLabelText('Motivo del rechazo'), {
            target: { value: 'La foto del documento está ilegible.' },
        });
        fireEvent.click(screen.getAllByRole('button', { name: 'Rechazar' }).slice(-1)[0]);

        await waitFor(() =>
            expect(revisar).toHaveBeenCalledWith('kyc-1', {
                approved: false,
                reason: 'La foto del documento está ilegible.',
            }),
        );
    });

    it('avisa de otra tienda con la misma identidad, y deja claro que no es una falta', async () => {
        // La razón de ser del KYC. Pero si el aviso se lee como acusación, el administrador
        // rechaza por reflejo a un dueño con dos negocios.
        coincidenciasDeIdentidad.mockResolvedValue({
            data: [
                {
                    tenant_id: 'shop-2',
                    tenant_name: 'La Segunda Tienda',
                    tenant_status: 'active',
                    legal_name: 'María Pérez',
                    kyc_status: 'verified',
                    created_at: '2026-01-10T10:00:00+00:00',
                },
            ],
        });

        pintar();
        fireEvent.click(screen.getByRole('button', { name: 'Verificar' }));

        expect(await screen.findByText('La Segunda Tienda')).toBeInTheDocument();
        expect(screen.getByText(/no es una falta/i)).toBeInTheDocument();
    });

    it('el vacío de pendientes lo dice con esas palabras', () => {
        pintar({
            profiles: [],
            metrics: { pending_count: 0, verified_count: 3, rejected_count: 0 },
        });

        expect(screen.getByTestId('kyc-vacio')).toHaveTextContent('No hay verificaciones pendientes.');
    });

    it('sin ningún expediente explica qué llenará la lista', () => {
        pintar({
            profiles: [],
            filters: { status: 'all' },
            metrics: { pending_count: 0, verified_count: 0, rejected_count: 0 },
        });

        expect(screen.getByTestId('kyc-vacio')).toHaveTextContent(/cuando una tienda envíe sus datos/i);
    });

    it('un expediente ya revisado no ofrece botones de decisión', () => {
        pintar({ profiles: [perfil({ status: 'verified', reviewed_at: '2026-09-05T10:00:00+00:00' })] });

        expect(screen.queryByRole('button', { name: 'Verificar' })).not.toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Rechazar' })).not.toBeInTheDocument();
    });
});
