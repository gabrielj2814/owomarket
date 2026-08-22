import fs from 'node:fs';
import path from 'node:path';
import { ESTADO_SESION, expect, FICHERO_UUID, test as setup, tenantBaseURL, tenantOwner } from './fixtures';

/**
 * Inicia sesión UNA vez y guarda las cookies para el resto de specs.
 *
 * Sin esto, cada spec que necesita backoffice hacía su propio login, y el límite de N18
 * —5 intentos por minuto contra la misma cuenta— convertía la suite en intermitente en
 * cuanto se ejecutaba dos veces seguidas. Comprobado: pasó.
 *
 * Bajar el límite no era opción: existe justamente para que probar contraseñas salga
 * caro. Lo que sobraba eran los logins repetidos.
 */
setup('inicia sesión como propietario de la tienda', async ({ page }) => {
    // En Windows con el servidor propio el login no puede completarse (la llamada interna
    // se bloquea con un solo worker), así que se deja un estado vacío y los specs que
    // dependen de él se saltan solos.
    if (process.platform === 'win32' && new URL(tenantBaseURL).port === '8000') {
        fs.mkdirSync(path.dirname(ESTADO_SESION), { recursive: true });
        fs.writeFileSync(ESTADO_SESION, JSON.stringify({ cookies: [], origins: [] }));
        fs.writeFileSync(FICHERO_UUID, '');

        return;
    }

    await page.goto(`${tenantBaseURL}/auth/login`);
    await page.getByPlaceholder('name@owomarket.com').fill(tenantOwner.email);
    await page.getByPlaceholder('password').fill(tenantOwner.password);
    await page.getByRole('button', { name: /Submit/i }).click();

    await expect(page).toHaveURL(/\/tenant\/backoffice\/[0-9a-f-]+\/dashboard/i);

    fs.mkdirSync(path.dirname(ESTADO_SESION), { recursive: true });
    fs.writeFileSync(FICHERO_UUID, new URL(page.url()).pathname.split('/')[3]);

    await page.context().storageState({ path: ESTADO_SESION });
});
