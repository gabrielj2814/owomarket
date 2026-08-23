import '@testing-library/jest-dom/vitest';
import React from 'react';
import { vi } from 'vitest';

/*
 * La tasa de cambio, simulada para todos los tests de componente.
 *
 * `CurrencyPriceDisplay` pide la tasa activa al montarse cuando no se la pasan por prop
 * (hallazgo G13: una promesa compartida a nivel de modulo, para que 24 tarjetas no hagan 24
 * peticiones). En los tests eso era una peticion de RED DE VERDAD: happy-dom sirve
 * `http://localhost:3000` como URL por defecto, asi que una llamada relativa intentaba
 * conectarse a ese puerto.
 *
 * El sintoma era un volcado de ECONNREFUSED ::1:3000 despues del resumen de resultados —
 * ruido que parecia un fallo de CI y no lo era: la ejecucion siempre salio con codigo 0.
 * Lo que si era de verdad es que los tests de componente tocaban la red.
 *
 * Se simula aqui y no en `ProductCard.test.tsx` a proposito: cualquier componente que
 * renderice un precio hereda esa peticion, asi que arreglarlo en un solo test dejaria al
 * siguiente con el mismo problema.
 */
vi.mock('@/Services/ExchangeRateServices', () => ({
    getSharedActiveRate: vi.fn().mockResolvedValue(null),
    default: { getSharedActiveRate: vi.fn().mockResolvedValue(null) },
}));

// Mock Inertia.js
vi.mock('@inertiajs/react', () => {
    return {
        Link: ({ href, children, className, onClick, ...rest }: any) => {
            return React.createElement(
                'a',
                {
                    href: typeof href === 'string' ? href : '#',
                    className,
                    onClick,
                    ...rest,
                },
                children
            );
        },
        usePage: () => ({
            props: {
                flash: {},
                auth: { user: null },
                tenant: null,
                current_domain: 'owomarket.test',
                errors: {},
            },
            url: '/',
            component: 'Storefront/Home',
            version: null,
        }),
        useForm: (initialData = {}) => ({
            data: initialData,
            setData: vi.fn(),
            post: vi.fn(),
            put: vi.fn(),
            patch: vi.fn(),
            delete: vi.fn(),
            processing: false,
            errors: {},
            reset: vi.fn(),
            clearErrors: vi.fn(),
            setError: vi.fn(),
            hasErrors: false,
            isDirty: false,
        }),
        router: {
            visit: vi.fn(),
            get: vi.fn(),
            post: vi.fn(),
            put: vi.fn(),
            patch: vi.fn(),
            delete: vi.fn(),
            reload: vi.fn(),
            cancel: vi.fn(),
            on: vi.fn(),
        },
    };
});

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation((query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: vi.fn(),
        removeListener: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    })),
});

// Mock window.scrollTo
Object.defineProperty(window, 'scrollTo', {
    writable: true,
    value: vi.fn(),
});
