import Dashboard from '@/components/layouts/Dashboard';
import TenantOwnerNavTabs from '@/components/tenant/TenantOwnerNavTabs';
import tenantPanelTheme from '@/theme/tenantPanelTheme';
import { Head } from '@inertiajs/react';
import { ThemeProvider } from 'flowbite-react';
import React from 'react';

/**
 * Lo que todas las pantallas del comerciante tienen en común.
 *
 * Antes cada una repetía las mismas tres cosas: el `<Dashboard>`, el `<Head>` y la barra de
 * pestañas. Ahora añade una cuarta —el tema de la zona— y por eso existe como componente.
 *
 * ## Por qué el tema se aplica aquí y no en `Dashboard`
 *
 * `Dashboard` sería el sitio obvio, pero **lo comparten el panel del comerciante y el
 * backoffice del administrador**, que usa el aspecto por defecto de Flowbite. Colgar
 * `tenantPanelTheme` ahí repintaría el backoffice entero de rebote, sin que nadie lo pidiera.
 *
 * ## La pestaña es opcional a propósito
 *
 * `TenantStoreSupportPage` vive en el mismo panel pero no pertenece a la navegación del
 * propietario: es el soporte de UNA tienda, no de la cuenta. Sin `activeTab` no se pintan
 * pestañas, y así esa pantalla comparte el tema sin fingir que está en un sitio donde no está.
 */
interface TenantOwnerShellProps {
    userId: string;
    title: string;
    /** Omitir en las pantallas que no son de la navegación del propietario. */
    activeTab?: React.ComponentProps<typeof TenantOwnerNavTabs>['activeTab'];
    children?: React.ReactNode;
}

const TenantOwnerShell: React.FC<TenantOwnerShellProps> = ({ userId, title, activeTab, children }) => (
    <Dashboard user_uuid={userId}>
        <Head title={title} />
        <ThemeProvider theme={tenantPanelTheme}>
            {activeTab && <TenantOwnerNavTabs userId={userId} activeTab={activeTab} />}
            {children}
        </ThemeProvider>
    </Dashboard>
);

export default TenantOwnerShell;
