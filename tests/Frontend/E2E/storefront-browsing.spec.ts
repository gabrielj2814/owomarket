import { expect, tenantBaseURL, test } from './fixtures';

/*
| Este spec se llamaba «Storefront Tenant Navigation Flow» pero visitaba `/marketplace`
| del dominio central y sólo comprobaba que `<body>` fuera visible. Ahora recorre de
| verdad la tienda en su subdominio, que es donde vivían los bugs que se escaparon.
*/
test.describe('Navegación de la tienda (subdominio de tenant)', () => {
    test('la portada de la tienda monta el árbol de React', async ({ page }) => {
        const response = await page.goto(`${tenantBaseURL}/`);
        expect(response?.status()).toBeLessThan(400);

        // Inertia deja `data-page` en la raíz; que exista prueba que la vista se sirvió,
        // y el fixture de `pageerror` prueba que el JS de dentro no explotó.
        await expect(page.locator('[data-page]')).toBeAttached();
        await expect(page.locator('#app, [data-page]').first()).not.toBeEmpty();
    });

    test('el catálogo de la tienda responde y renderiza', async ({ page }) => {
        const response = await page.goto(`${tenantBaseURL}/catalog`);
        expect(response?.status()).toBeLessThan(400);

        await expect(page.locator('[data-page]')).toBeAttached();
    });

    test('la pantalla de login de la tienda muestra el formulario', async ({ page }) => {
        const response = await page.goto(`${tenantBaseURL}/auth/login`);
        expect(response?.status()).toBeLessThan(400);

        await expect(page.getByPlaceholder('name@owomarket.com')).toBeVisible();
        await expect(page.getByPlaceholder('password')).toBeVisible();
        await expect(page.getByRole('button', { name: /Submit/i })).toBeEnabled();
    });
});
