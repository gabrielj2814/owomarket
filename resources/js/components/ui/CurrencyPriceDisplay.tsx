import React, { useEffect, useState } from 'react';
import { getActiveExchangeRate } from '@/Services/ExchangeRateServices';

export interface CurrencyPriceDisplayProps {
    priceUsd: number;
    comparePriceUsd?: number;
    exchangeRate?: number;
    showVes?: boolean;
    showUsdt?: boolean;
    showBcvLabel?: boolean;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    className?: string;
    vesClassName?: string;
    cryptoClassName?: string;
    layout?: 'vertical' | 'horizontal' | 'compact';
}

// Tasa de reserva local en caso de que la API demore en responder
const DEFAULT_FALLBACK_RATE = 775.3356;

export const formatUsd = (amount: number): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

export const formatVes = (amount: number): string => {
    return (
        'Bs. ' +
        new Intl.NumberFormat('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount)
    );
};

export const CurrencyPriceDisplay: React.FC<CurrencyPriceDisplayProps> = ({
    priceUsd,
    comparePriceUsd,
    exchangeRate: initialRate,
    showVes = true,
    showUsdt = true,
    showBcvLabel = true,
    size = 'md',
    className = '',
    vesClassName = '',
    cryptoClassName = '',
    layout = 'vertical',
}) => {
    const [rate, setRate] = useState<number>(initialRate ?? DEFAULT_FALLBACK_RATE);

    useEffect(() => {
        if (initialRate && initialRate > 0) {
            setRate(initialRate);
            return;
        }

        let isMounted = true;
        getActiveExchangeRate()
            .then((res) => {
                if (isMounted && res?.data?.rate && res.data.rate > 0) {
                    setRate(res.data.rate);
                }
            })
            .catch(() => {
                // Silencioso, mantiene el valor por defecto
            });

        return () => {
            isMounted = false;
        };
    }, [initialRate]);

    const priceVes = priceUsd * rate;
    const comparePriceVes = comparePriceUsd ? comparePriceUsd * rate : undefined;

    // Tamaños tipográficos
    const sizeClasses = {
        xs: {
            main: 'text-sm font-semibold',
            compare: 'text-xs line-through text-gray-400',
            ves: 'text-xs font-medium text-emerald-700 dark:text-emerald-400',
            crypto: 'text-[10px] text-gray-500 dark:text-gray-400',
            badge: 'text-[9px] px-1 py-0.2',
        },
        sm: {
            main: 'text-base font-bold',
            compare: 'text-xs line-through text-gray-400',
            ves: 'text-xs font-semibold text-emerald-700 dark:text-emerald-400',
            crypto: 'text-xs text-gray-500 dark:text-gray-400',
            badge: 'text-[10px] px-1.5 py-0.5',
        },
        md: {
            main: 'text-xl font-extrabold',
            compare: 'text-sm line-through text-gray-400',
            ves: 'text-sm font-semibold text-emerald-700 dark:text-emerald-400',
            crypto: 'text-xs text-gray-500 dark:text-gray-400',
            badge: 'text-[10px] px-2 py-0.5',
        },
        lg: {
            main: 'text-2xl font-black',
            compare: 'text-base line-through text-gray-400',
            ves: 'text-base font-bold text-emerald-700 dark:text-emerald-400',
            crypto: 'text-sm text-gray-500 dark:text-gray-400',
            badge: 'text-xs px-2 py-0.5',
        },
        xl: {
            main: 'text-3xl font-black tracking-tight',
            compare: 'text-lg line-through text-gray-400',
            ves: 'text-lg font-bold text-emerald-700 dark:text-emerald-400',
            crypto: 'text-sm text-gray-500 dark:text-gray-400',
            badge: 'text-xs px-2.5 py-1',
        },
    }[size];

    return (
        <div className={`currency-price-display flex ${layout === 'horizontal' ? 'flex-row items-baseline gap-3' : 'flex-col gap-0.5'} ${className}`} data-testid="currency-price-display">
            {/* Precio Base en Dólares (USD) */}
            <div className="flex items-baseline gap-2 flex-wrap">
                <span className={`text-gray-900 dark:text-white ${sizeClasses.main}`} data-testid="price-usd">
                    {formatUsd(priceUsd)}
                </span>

                {comparePriceUsd && comparePriceUsd > priceUsd && (
                    <span className={sizeClasses.compare} data-testid="compare-price-usd">
                        {formatUsd(comparePriceUsd)}
                    </span>
                )}

                {showUsdt && (
                    <span className={`inline-flex items-center gap-1 font-medium ${sizeClasses.crypto} ${cryptoClassName}`} data-testid="price-usdt">
                        <span className="text-emerald-600 dark:text-emerald-400 font-bold">≈ {priceUsd.toFixed(2)}</span> USDT
                    </span>
                )}
            </div>

            {/* Equivalente en Bolívares (VES) calculado con Tasa BCV */}
            {showVes && (
                <div className={`flex items-center gap-1.5 flex-wrap ${vesClassName}`} data-testid="price-ves-container">
                    <span className={sizeClasses.ves} data-testid="price-ves">
                        {formatVes(priceVes)}
                    </span>

                    {comparePriceVes && comparePriceVes > priceVes && (
                        <span className="text-xs line-through text-gray-400">
                            {formatVes(comparePriceVes)}
                        </span>
                    )}

                    {showBcvLabel && (
                        <span
                            className={`inline-flex items-center rounded-full font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800 ${sizeClasses.badge}`}
                            title={`Tasa oficial BCV: Bs. ${rate.toFixed(4)}`}
                            data-testid="bcv-badge"
                        >
                            BCV: Bs. {rate > 1000 ? rate.toLocaleString('es-VE', { maximumFractionDigits: 2 }) : rate.toFixed(2)}
                        </span>
                    )}
                </div>
            )}
        </div>
    );
};

export default CurrencyPriceDisplay;
