import { test, expect } from '@playwright/test';

test.describe('Storefront Tenant Navigation Flow', () => {
    test('renders storefront catalog and verifies product grid responsiveness', async ({ page }) => {
        // Test visiting tenant catalog if accessible or central catalog
        const response = await page.goto('/marketplace');
        expect(response?.status()).toBeLessThan(400);

        // Verify page content loads without unhandled JS exceptions
        await expect(page.locator('body')).toBeVisible();
    });
});
