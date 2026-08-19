import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import ModalAlertConfirmation from '@/components/ui/ModalAlertConfirmation';

describe('ModalAlertConfirmation Component', () => {
    it('renders modal content and text when openModal is true', () => {
        const onClose = vi.fn();
        const onClickAction = vi.fn();

        render(
            <ModalAlertConfirmation
                openModal={true}
                size="md"
                icon={<span data-testid="alert-icon">⚠️</span>}
                text="¿Estás seguro de eliminar este producto?"
                buttonTextCancel="Cancelar"
                buttonTextAction="Eliminar"
                colorButtonCancel="alternative"
                colorButtonAction="failure"
                onClose={onClose}
                onClickAction={onClickAction}
            />
        );

        expect(screen.getByText('¿Estás seguro de eliminar este producto?')).toBeInTheDocument();
        expect(screen.getByText('Cancelar')).toBeInTheDocument();
        expect(screen.getByText('Eliminar')).toBeInTheDocument();
        expect(screen.getByTestId('alert-icon')).toBeInTheDocument();
    });

    it('triggers onClose when cancel button is clicked', () => {
        const onClose = vi.fn();
        const onClickAction = vi.fn();

        render(
            <ModalAlertConfirmation
                openModal={true}
                size="md"
                icon={null}
                text="Confirmar acción"
                buttonTextCancel="No, cancelar"
                buttonTextAction="Sí, continuar"
                colorButtonCancel="alternative"
                colorButtonAction="success"
                onClose={onClose}
                onClickAction={onClickAction}
            />
        );

        const cancelButton = screen.getByText('No, cancelar');
        fireEvent.click(cancelButton);

        expect(onClose).toHaveBeenCalledWith(false);
        expect(onClickAction).not.toHaveBeenCalled();
    });

    it('triggers onClickAction when action button is clicked', () => {
        const onClose = vi.fn();
        const onClickAction = vi.fn();

        render(
            <ModalAlertConfirmation
                openModal={true}
                size="md"
                icon={null}
                text="Confirmar acción"
                buttonTextCancel="Cancelar"
                buttonTextAction="Aceptar"
                colorButtonCancel="alternative"
                colorButtonAction="success"
                onClose={onClose}
                onClickAction={onClickAction}
            />
        );

        const actionButton = screen.getByText('Aceptar');
        fireEvent.click(actionButton);

        expect(onClickAction).toHaveBeenCalledTimes(1);
    });
});
