import { Alert, Button } from 'flowbite-react';
import { HiOutlineExclamationTriangle } from 'react-icons/hi2';

/**
 * Aviso de que los datos del portal no se pudieron cargar (hallazgo G15 / N35).
 *
 * Las nueve páginas de `pages/customer` terminaban su carga con `.catch(() => {})`, así
 * que un error de red era **indistinguible de «no tienes pedidos»**: el cliente veía una
 * lista vacía y se iba pensando que no había comprado nunca.
 */
export default function PortalLoadError({ onRetry }: { onRetry?: () => void }) {
    return (
        <Alert color="failure" icon={HiOutlineExclamationTriangle} className="mb-4">
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <span className="text-xs">
                    No pudimos cargar tus datos. Puede ser un problema de conexión;{' '}
                    <strong>esto no significa que no tengas información aquí</strong>.
                </span>

                {onRetry && (
                    <Button size="xs" color="light" onClick={onRetry} className="shrink-0 font-semibold">
                        Reintentar
                    </Button>
                )}
            </div>
        </Alert>
    );
}
