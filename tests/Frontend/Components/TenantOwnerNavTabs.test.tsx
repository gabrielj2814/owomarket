import React from 'react';
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import TenantOwnerNavTabs from '@/components/tenant/TenantOwnerNavTabs';

describe('TenantOwnerNavTabs Component', () => {
    it('renders all 5 main navigation tabs for tenant owner hub', () => {
        render(<TenantOwnerNavTabs userId="user-123" activeTab="dashboard" />);

        expect(screen.getByText(/Mis Tiendas & Sucursales/i)).toBeInTheDocument();
        expect(screen.getByText(/Billetera & Liquidaciones/i)).toBeInTheDocument();
        expect(screen.getByText(/Catálogo & Marketplace Central/i)).toBeInTheDocument();
        expect(screen.getByText(/Suscripciones & Facturas B2B/i)).toBeInTheDocument();
        expect(screen.getByText(/Centro de Soporte & Reportes/i)).toBeInTheDocument();
    });

    it('highlights the active tab properly', () => {
        render(<TenantOwnerNavTabs userId="user-123" activeTab="support" />);

        const supportTab = screen.getByText(/Centro de Soporte & Reportes/i).closest('a');
        expect(supportTab).toHaveClass('bg-blue-600');
        expect(supportTab).toHaveClass('text-white');
    });
});
