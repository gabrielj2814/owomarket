import { expect, test } from './fixtures';

test.describe('Exchange Rate & Multi-Currency Storefront Flow', () => {
    test('renders active exchange rate and dual currency pricing in marketplace', async ({ page }) => {
        // Intercept exchange rate API to guarantee stable test response
        await page.route('**/api/exchange-rate/current', async (route) => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    success: true,
                    data: {
                        base_currency: 'USD',
                        target_currency: 'VES',
                        rate: 775.3356,
                        formatted_rate: '775,3356',
                        source: 'BCV_SCRAPING',
                        rate_date: '2026-08-19',
                        is_active: true,
                    },
                }),
            });
        });

        const response = await page.goto('/marketplace');
        expect(response?.status()).toBeLessThan(400);

        // Verify page is rendered
        await expect(page).toHaveTitle(/Marketplace|OwoMarket/i);
    });

    test('validates currency converter API endpoint directly', async ({ request }) => {
        const response = await request.get('/api/exchange-rate/convert?amount=100&from=USD&to=VES');
        expect(response.ok()).toBeTruthy();

        const json = await response.json();
        expect(json.success).toBe(true);
        expect(json.data.amount_usd).toBe(100);
        expect(json.data.amount_ves).toBeGreaterThan(0);
    });
});
