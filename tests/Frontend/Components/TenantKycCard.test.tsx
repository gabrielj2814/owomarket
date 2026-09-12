import { render, screen, waitFor } from '@testing-library/react';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * La tarjeta de verificación de identidad del comerciante (subsistema 1).
 *
 * Lo que se vigila no es el formulario: es **que el comerciante entienda por qué no puede
 * retirar**. Sin identidad verificada el retiro se rechaza en el backend, así que una pantalla
 * que no lo explique le deja un botón que falla y ninguna pista de qué hacer.
 *
 * Y que la cédula enviada no vuelva nunca a la pantalla: el backend no la devuelve, y tenerla
 * dando vueltas por la red solo multiplica los sitios de los que puede escaparse.
 */
const get = vi.fn();
const post = vi.fn();

/*
 * Se dobla el SERVICIO, no axios. El componente llamaba a axios directamente con
 * `/owner/api/kyc/...` --sin el prefijo `tenant` de la ruta real--, asi que en la aplicacion
 * devolvia 404 siempre y la tarjeta no se pintaba nunca: ningun comerciante podia enviar su
 * identidad. Estos tests pasaban igualmente porque el doble de axios respondia a cualquier URL.
 *
 * Doblar el servicio deja la URL en un solo sitio, donde un prefijo equivocado se ve.
 */
vi.mock('@/Services/TenantKycServices', () => ({
    default: {
        estado: (...args: unknown[]) => get(...args),
        enviar: (...args: unknown[]) => post(...args),
    },
}));

import TenantKycCard from '@/components/ui/TenantKycCard';

const estado = (data: Record<string, unknown>) => ({ data });

describe('Verificación de identidad del comerciante', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('pide los datos cuando no hay expediente', async () => {
        get.mockResolvedValue(estado({ status: 'missing', has_document: false }));

        render(<TenantKycCard tenantId="t-1" />);

        expect(await screen.findByTestId('kyc-form')).toBeInTheDocument();
        expect(screen.getByLabelText('Cédula')).toBeInTheDocument();
    });

    it('avisa de que puede seguir vendiendo mientras tanto', async () => {
        // El KYC solo bloquea el retiro, no la venta. Si la pantalla no lo dice, el comerciante
        // asume que su tienda está parada.
        get.mockResolvedValue(estado({ status: 'missing', has_document: false }));

        render(<TenantKycCard tenantId="t-1" />);

        expect(await screen.findByText(/puedes seguir vendiendo/i)).toBeInTheDocument();
    });

    it('en revisión no vuelve a pedir los datos', async () => {
        get.mockResolvedValue(estado({ status: 'pending', has_document: false }));

        render(<TenantKycCard tenantId="t-1" />);

        expect(await screen.findByTestId('kyc-pending')).toBeInTheDocument();
        expect(screen.queryByTestId('kyc-form')).not.toBeInTheDocument();
    });

    it('verificada, lo dice y desaparece el formulario', async () => {
        get.mockResolvedValue(estado({ status: 'verified', has_document: true }));

        render(<TenantKycCard tenantId="t-1" />);

        expect(await screen.findByTestId('kyc-verified')).toBeInTheDocument();
        expect(screen.queryByTestId('kyc-form')).not.toBeInTheDocument();
    });

    it('un rechazo enseña su motivo junto al formulario para corregirlo', async () => {
        // Sin el motivo, el comerciante ve el cobro bloqueado y no sabe qué arreglar.
        get.mockResolvedValue(
            estado({ status: 'rejected', has_document: false, rejection_reason: 'La dirección no coincide.' }),
        );

        render(<TenantKycCard tenantId="t-1" />);

        expect(await screen.findByTestId('kyc-rejection')).toHaveTextContent('La dirección no coincide.');
        expect(screen.getByTestId('kyc-form')).toBeInTheDocument();
    });

    it('no vuelve a mostrar la cédula ya enviada', async () => {
        // El backend no la devuelve; esto vigila que la pantalla tampoco la reconstruya de
        // ningún otro sitio.
        get.mockResolvedValue(
            estado({ status: 'rejected', legal_name: 'María Pérez', phone: '+58 412', address: 'Caracas', has_document: true }),
        );

        render(<TenantKycCard tenantId="t-1" />);

        await waitFor(() => expect(screen.getByTestId('kyc-form')).toBeInTheDocument());
        expect(screen.getByLabelText('Cédula')).toHaveValue('');
        // Lo que no es documento sí se reaprovecha: reescribirlo entero cada vez es lo que hace
        // que la gente abandone el formulario.
        expect(screen.getByLabelText('Dirección completa')).toHaveValue('Caracas');
    });

    it('no pinta nada si el estado no se puede consultar', async () => {
        get.mockRejectedValue(new Error('red caída'));

        const { container } = render(<TenantKycCard tenantId="t-1" />);

        await waitFor(() => expect(get).toHaveBeenCalled());
        expect(container).toBeEmptyDOMElement();
    });
});
