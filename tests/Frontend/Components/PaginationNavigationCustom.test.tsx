import React from 'react';
import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import PaginationNavigationCustom from '@/components/ui/PaginationNavigationCustom';

describe('PaginationNavigationCustom Component', () => {
    it('renders pagination component with current page and total count info', () => {
        const onPageChangeFather = vi.fn();

        render(
            <PaginationNavigationCustom
                currentPageFather={1}
                itemsPerPageFather={10}
                totalItemsFather={50}
                onPageChangeFather={onPageChangeFather}
            />
        );

        expect(screen.getByText('1')).toBeInTheDocument();
    });
});
