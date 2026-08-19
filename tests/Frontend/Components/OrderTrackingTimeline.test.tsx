import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import OrderTrackingTimeline from '@/components/ui/storefront/OrderTrackingTimeline';

describe('OrderTrackingTimeline Component', () => {
    const mockTimeline = [
        {
            step: 1,
            key: 'placed',
            title: 'Pedido Registrado',
            description: 'Orden ORD-2026-TEST creada exitosamente.',
            timestamp: '2026-08-19 12:00',
            is_completed: true,
            is_current: false,
        },
        {
            step: 2,
            key: 'paid',
            title: 'Pago Confirmado',
            description: 'Pago en Pago Móvil verificado.',
            timestamp: '2026-08-19 12:15',
            is_completed: true,
            is_current: false,
        },
        {
            step: 3,
            key: 'processing',
            title: 'En Preparación',
            description: 'Empacando paquete en almacén central.',
            timestamp: '2026-08-19 14:00',
            is_completed: false,
            is_current: true,
        },
        {
            step: 4,
            key: 'in_transit',
            title: 'En Tránsito',
            description: 'Despachado con MRW.',
            is_completed: false,
            is_current: false,
        },
        {
            step: 5,
            key: 'delivered',
            title: 'Entregado',
            description: 'Paquete entregado.',
            is_completed: false,
            is_current: false,
        },
    ];

    it('renders courier and tracking number when provided', () => {
        render(
            <OrderTrackingTimeline
                currentStep={3}
                courier="MRW Express"
                trackingNumber="MRW-987654321"
                trackingUrl="https://tracking.mrw.com/MRW-987654321"
                timeline={mockTimeline}
            />
        );

        expect(screen.getByText(/MRW Express/i)).toBeInTheDocument();
        expect(screen.getByText(/Guía: MRW-987654321/i)).toBeInTheDocument();
        expect(screen.getByText(/Rastrear en Vivo/i)).toBeInTheDocument();
    });

    it('renders all 5 tracking steps with titles and descriptions', () => {
        render(
            <OrderTrackingTimeline
                currentStep={3}
                courier="Zoom"
                trackingNumber="ZM-112233"
                timeline={mockTimeline}
            />
        );

        expect(screen.getByText('Pedido Registrado')).toBeInTheDocument();
        expect(screen.getByText('Pago Confirmado')).toBeInTheDocument();
        expect(screen.getByText('En Preparación')).toBeInTheDocument();
        expect(screen.getByText('En Tránsito')).toBeInTheDocument();
        expect(screen.getByText('Entregado')).toBeInTheDocument();
    });
});
