import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Subsistema 2: el campo de garantia del formulario de producto.
 *
 * Lo que se vigila aqui es UN invariante, y no es cosmetico: el interruptor «tiene garantia»
 * no guarda estado propio, se deriva de `warranty_days`. Si alguien le añade una bandera
 * aparte, «tiene garantia» y «cuantos dias» pueden contradecirse y el fondo de garantia
 * (subsistema 4) recibira una tienda que promete garantia sin plazo.
 *
 * La pagina arrastra el layout, Inertia y tres servicios, asi que se doblan: lo que se prueba
 * es el campo, no el andamiaje.
 */
vi.mock('@/components/layouts/Dashboard', () => ({
    default: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}));

vi.mock('@/components/ui/ProductImageDropzone', () => ({
    default: () => <div data-testid="dropzone" />,
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    router: { visit: vi.fn() },
}));

vi.mock('@/Services/CategoryServices', () => ({
    default: { tree: vi.fn().mockResolvedValue([]), getAll: vi.fn().mockResolvedValue([]), list: vi.fn().mockResolvedValue([]) },
}));

vi.mock('@/Services/BrandServices', () => ({
    default: { tree: vi.fn().mockResolvedValue([]), getAll: vi.fn().mockResolvedValue([]), list: vi.fn().mockResolvedValue([]) },
}));

vi.mock('@/Services/ProductServices', () => ({
    default: {
        getById: vi.fn().mockResolvedValue(null),
        create: vi.fn().mockResolvedValue({}),
        update: vi.fn().mockResolvedValue({}),
    },
}));

import FormProductPage from '@/pages/tenant/modules/product/FormProductPage';

function renderForm() {
    return render(<FormProductPage user_id="u-1" title="Nuevo producto" host="tienda.localhost" user_name="Ana" />);
}

describe('Campo de garantía del producto', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('no pide días mientras el producto no tenga garantía', async () => {
        renderForm();

        expect(await screen.findByText('Este producto tiene garantía')).toBeInTheDocument();
        expect(screen.queryByLabelText('Días de garantía')).not.toBeInTheDocument();
    });

    it('al activar la garantía aparece el plazo, y con un valor por defecto usable', async () => {
        renderForm();
        const user = userEvent.setup();

        await user.click(await screen.findByText('Este producto tiene garantía'));

        // Un plazo por defecto y no vacio: activar el interruptor no puede dejar el producto
        // prometiendo garantia sin decir de cuanto.
        expect(await screen.findByLabelText('Días de garantía')).toHaveValue(30);
    });

    it('al desactivarla el plazo desaparece: sin garantía no hay días que guardar', async () => {
        renderForm();
        const user = userEvent.setup();

        const toggle = await screen.findByText('Este producto tiene garantía');
        await user.click(toggle);
        expect(await screen.findByLabelText('Días de garantía')).toBeInTheDocument();

        await user.click(toggle);

        expect(screen.queryByLabelText('Días de garantía')).not.toBeInTheDocument();
    });

    it('acepta cambiar el plazo', async () => {
        renderForm();
        const user = userEvent.setup();

        await user.click(await screen.findByText('Este producto tiene garantía'));
        const input = await screen.findByLabelText('Días de garantía');

        await user.clear(input);
        await user.type(input, '365');

        expect(input).toHaveValue(365);
    });
});
