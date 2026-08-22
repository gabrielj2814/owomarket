import { expect, test } from './fixtures';

test.describe('Central Marketplace Navigation and Landing Flow', () => {
    test('renders central homepage with search, categories, and layout navigation', async ({ page }) => {
        // Visit the central home page
        const response = await page.goto('/');
        expect(response?.status()).toBeLessThan(400);

        // Verify page title and header
        await expect(page).toHaveTitle(/OwoMarket/i);

        // Verify Search input is present and interactive
        const searchInput = page.getByPlaceholder(/Buscar productos/i);
        await expect(searchInput).toBeVisible();
        await searchInput.fill('Laptop');
        await expect(searchInput).toHaveValue('Laptop');

        // Verify key navigation elements
        const loginLink = page.getByRole('link', { name: /Iniciar Sesión|Ingresar|Acceder/i }).first();
        if (await loginLink.isVisible()) {
            await expect(loginLink).toBeEnabled();
        }
    });

    test('can submit search query from homepage to marketplace catalog', async ({ page }) => {
        await page.goto('/');

        const searchInput = page.getByPlaceholder(/Buscar productos/i);
        await searchInput.fill('Monitor');
        await searchInput.press('Enter');

        // Verify navigation or URL update
        await expect(page).toHaveURL(/marketplace\?search=Monitor/i);
    });
});
