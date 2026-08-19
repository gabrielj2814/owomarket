import { test, expect } from '@playwright/test';

test.describe('Customer Account Portal and Password Recovery Flow', () => {
    test('renders login prompt when accessing /account/dashboard unauthenticated', async ({ page }) => {
        const response = await page.goto('/account/dashboard');
        expect(response?.status()).toBeLessThan(400);

        // Verify title & CTA
        await expect(page.getByRole('heading', { name: /Inicia sesión con tu OwO Pass/i })).toBeVisible();
        await expect(page.getByRole('button', { name: /Ingresar con OwO Pass/i })).toBeVisible();
    });

    test('renders forgot password page and sends recovery PIN code form', async ({ page }) => {
        const response = await page.goto('/auth/forgot-password');
        expect(response?.status()).toBeLessThan(400);

        await expect(page.getByRole('heading', { name: /Recuperar Contraseña/i })).toBeVisible();

        const emailInput = page.getByPlaceholder('tu@email.com');
        await expect(emailInput).toBeVisible();
        await emailInput.fill('customer@test.local');

        const submitBtn = page.getByRole('button', { name: /Enviar Código de Recuperación/i });
        await expect(submitBtn).toBeVisible();
    });

    test('renders reset password page with PIN code and new password inputs', async ({ page }) => {
        const response = await page.goto('/auth/reset-password?email=customer@test.local');
        expect(response?.status()).toBeLessThan(400);

        await expect(page.getByRole('heading', { name: /Restablecer Contraseña/i })).toBeVisible();

        const pinInput = page.getByPlaceholder('123456');
        await expect(pinInput).toBeVisible();

        const passwordInput = page.getByPlaceholder('Mínimo 8 caracteres');
        await expect(passwordInput).toBeVisible();

        const confirmPasswordInput = page.getByPlaceholder('Repite la contraseña');
        await expect(confirmPasswordInput).toBeVisible();
    });
});
