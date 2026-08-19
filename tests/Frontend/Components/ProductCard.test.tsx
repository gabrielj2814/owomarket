import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import ProductCard from '@/components/ui/storefront/ProductCard';
import { CartProvider } from '@/contexts/CartContext';
import { StorefrontProduct } from '@/types/models/Storefront';

const mockProduct: StorefrontProduct = {
    id: 'prod-uuid-1',
    name: 'Teclado Mecánico RGB Pro',
    slug: 'teclado-mecanico-rgb-pro',
    sku: 'TEC-001',
    price: 89.99,
    compare_price: 119.99,
    quantity: 15,
    is_visible: true,
    rating: 4.8,
    reviews_count: 24,
    brand_name: 'Logitech',
    category_name: 'Periféricos',
    image: 'https://images.unsplash.com/photo-1587829741301-dc798b83add3',
};

const renderWithCart = (product: StorefrontProduct) => {
    return render(
        <CartProvider currency="USD" domain="tecs.owomarket.test">
            <ProductCard product={product} />
        </CartProvider>
    );
};

describe('ProductCard Component', () => {
    it('renders product information, title, category and brand correctly', () => {
        renderWithCart(mockProduct);

        expect(screen.getByText('Teclado Mecánico RGB Pro')).toBeInTheDocument();
        expect(screen.getByText('Periféricos')).toBeInTheDocument();
        expect(screen.getByText('Logitech')).toBeInTheDocument();
        expect(screen.getByText(/89\.99/)).toBeInTheDocument();
        expect(screen.getByText(/119\.99/)).toBeInTheDocument();
        expect(screen.getByText('-25%')).toBeInTheDocument();
        expect(screen.getByText('Disponible')).toBeInTheDocument();
    });

    it('shows out of stock badge and disables add to cart button when quantity is 0', () => {
        const outOfStockProduct = { ...mockProduct, quantity: 0 };
        renderWithCart(outOfStockProduct);

        expect(screen.getByText('Agotado')).toBeInTheDocument();
        const addButton = screen.getByRole('button', { name: /Sin Existencias/i });
        expect(addButton).toBeDisabled();
    });

    it('allows clicking add to cart when in stock', () => {
        renderWithCart(mockProduct);

        const addButton = screen.getByRole('button', { name: /Añadir al Carrito/i });
        expect(addButton).not.toBeDisabled();
        fireEvent.click(addButton);
    });
});
