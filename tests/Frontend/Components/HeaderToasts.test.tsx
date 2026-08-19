import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import HeaderToasts from '@/components/HeaderToasts';
import { ToastInterface } from '@/types/ToastInterface';

describe('HeaderToasts Component', () => {
    it('renders flash toasts with correct title and message', () => {
        const mockToasts: ToastInterface[] = [
            {
                type: 'success',
                title: '¡Operación Exitosa!',
                message: 'El producto fue guardado correctamente.',
            },
            {
                type: 'failure',
                title: 'Error de Validación',
                message: 'Por favor complete todos los campos obligatorios.',
            },
        ];

        render(<HeaderToasts list={mockToasts} />);

        expect(screen.getByText('¡Operación Exitosa!')).toBeInTheDocument();
        expect(screen.getByText('El producto fue guardado correctamente.')).toBeInTheDocument();
        expect(screen.getByText('Error de Validación')).toBeInTheDocument();
        expect(screen.getByText('Por favor complete todos los campos obligatorios.')).toBeInTheDocument();
    });

    it('renders nothing when toast list is empty', () => {
        const { container } = render(<HeaderToasts list={[]} />);
        const toastItems = container.querySelectorAll('.border-gray-200');
        expect(toastItems.length).toBe(0);
    });
});
