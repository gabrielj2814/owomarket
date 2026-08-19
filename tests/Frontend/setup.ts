import '@testing-library/jest-dom/vitest';
import React from 'react';
import { vi } from 'vitest';

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
