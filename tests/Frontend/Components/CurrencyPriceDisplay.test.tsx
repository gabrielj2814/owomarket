import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect } from 'vitest';
import CurrencyPriceDisplay, { formatUsd, formatVes } from '@/components/ui/CurrencyPriceDisplay';

describe('CurrencyPriceDisplay Component', () => {
    it('formats USD and VES numbers correctly', () => {
        expect(formatUsd(25.5)).toContain('25.50');
        expect(formatVes(1000)).toContain('Bs.');
    });

    it('renders USD price, USDT equivalence and VES conversion using passed rate', () => {
        render(
            <CurrencyPriceDisplay
                priceUsd={50.0}
                comparePriceUsd={70.0}
                exchangeRate={40.0}
                showVes={true}
                showUsdt={true}
                showBcvLabel={true}
            />
        );

        // USD Price
        expect(screen.getByTestId('price-usd')).toHaveTextContent('$50.00');
        expect(screen.getByTestId('compare-price-usd')).toHaveTextContent('$70.00');

        // USDT Parity
        expect(screen.getByTestId('price-usdt')).toHaveTextContent('50.00 USDT');

        // VES Conversion (50 * 40 = 2,000.00)
        expect(screen.getByTestId('price-ves')).toHaveTextContent('Bs. 2.000,00');

        // BCV Badge
        expect(screen.getByTestId('bcv-badge')).toHaveTextContent('BCV: Bs. 40.00');
    });

    it('hides VES or USDT sections when flags are false', () => {
        render(
            <CurrencyPriceDisplay
                priceUsd={10.0}
                exchangeRate={50.0}
                showVes={false}
                showUsdt={false}
            />
        );

        expect(screen.getByTestId('price-usd')).toBeInTheDocument();
        expect(screen.queryByTestId('price-ves-container')).not.toBeInTheDocument();
        expect(screen.queryByTestId('price-usdt')).not.toBeInTheDocument();
    });
});
