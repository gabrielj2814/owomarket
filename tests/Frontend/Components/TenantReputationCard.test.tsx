import { render, screen } from '@testing-library/react';
import React from 'react';
import { describe, expect, it } from 'vitest';

import TenantReputationCard, { TenantReputationProgress } from '@/components/ui/TenantReputationCard';

/**
 * El nivel de reputación en la billetera (subsistema 5, fase C · vista 3).
 *
 * Lo que se vigila aquí es que el nivel **se explique en dinero y con salida**. Un nivel que
 * baja sin decir por qué ni cómo se recupera no corrige a nadie: empuja a abrir otra tienda con
 * otro nombre, que es exactamente lo que el sistema de reputación intenta evitar.
 */
const progreso = (overrides: Partial<TenantReputationProgress> = {}): TenantReputationProgress => ({
    level: 'medio',
    reserve_percent: 10,
    deliveries: 4,
    deliveries_for_next: 10,
    unanswered_claims: 0,
    has_debt: false,
    ...overrides,
});

describe('Nivel de reputación de la tienda', () => {
    it('dice el nivel en dinero, no como insignia', () => {
        // El nivel decide cuánto se retiene de cada venta. Enseñarlo sin el porcentaje lo
        // convertiría en una pegatina sin consecuencia visible.
        render(<TenantReputationCard reputation={progreso()} />);

        expect(screen.getByTestId('reputacion-card')).toHaveTextContent('se retiene el 10% de cada venta');
    });

    it('aclara que lo retenido no se pierde', () => {
        render(<TenantReputationCard reputation={progreso()} />);

        expect(screen.getByText(/no se pierde/i)).toBeInTheDocument();
    });

    it('enseña siempre el camino de vuelta', () => {
        render(<TenantReputationCard reputation={progreso({ deliveries: 7 })} />);

        expect(screen.getByTestId('reputacion-camino')).toHaveTextContent('7');
        expect(screen.getByTestId('reputacion-camino')).toHaveTextContent(/faltan 3 entregas/i);
    });

    it('en nivel bajo explica el freno antes que el camino', () => {
        // EL CASO QUE IMPORTA. Si una tienda con reclamaciones sin responder solo lee «te
        // faltan N entregas», acumula entregas, no sube, y concluye que el sistema miente.
        render(<TenantReputationCard reputation={progreso({ level: 'bajo', reserve_percent: 20, unanswered_claims: 2 })} />);

        const silencios = screen.getByTestId('reputacion-silencios');
        expect(silencios).toHaveTextContent(/2 reclamaciones que vencieron sin respuesta/i);
        // Y la salida: una sanción de la que no se puede salir solo expulsa.
        expect(silencios).toHaveTextContent(/90 días/i);
    });

    it('explica que la deuda frena el nivel alto sin bloquear el cobro', () => {
        // Se salda vendiendo, y para vender hace falta cobrar. Decirlo evita que el
        // comerciante crea que está bloqueado.
        render(<TenantReputationCard reputation={progreso({ has_debt: true })} />);

        expect(screen.getByTestId('reputacion-deuda')).toHaveTextContent(/no te bloquea el cobro/i);
    });

    it('en nivel alto dice qué lo mantiene, sin prometer más', () => {
        render(<TenantReputationCard reputation={progreso({ level: 'alto', reserve_percent: 5, deliveries: 14 })} />);

        expect(screen.getByTestId('reputacion-card')).toHaveTextContent('se retiene el 5% de cada venta');
        expect(screen.queryByTestId('reputacion-camino')).not.toBeInTheDocument();
        expect(screen.getByText(/dentro del plazo es lo que lo mantiene/i)).toBeInTheDocument();
    });

    it('sin datos de reputación no pinta nada', () => {
        // Un comerciante sin tiendas. Una tarjeta vacía diciendo «nivel medio» sería inventarse
        // un dato sobre una tienda que no existe.
        const { container } = render(<TenantReputationCard reputation={null} />);

        expect(container).toBeEmptyDOMElement();
    });
});
