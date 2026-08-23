import { Alert } from 'flowbite-react';
import { HiOutlineCheckCircle, HiOutlineExclamationTriangle } from 'react-icons/hi2';

export type PortalFeedback = { type: 'success' | 'error'; text: string };

/**
 * Resultado de una accion del comprador dentro del portal (hallazgo C2).
 *
 * Las cuatro paginas que hacen algo —guardar una direccion, pedir una devolucion, publicar
 * una resena, quitar un favorito— contestaban con `alert()`. Un alert() bloquea el hilo, no
 * se puede estilar, no es accesible y hay que descartarlo a mano para poder seguir. Y aqui
 * pesa mas que en los formularios de acceso: son las respuestas a algo que la persona
 * acaba de hacer, asi que un fallo silenciado deja a alguien sin saber si su solicitud de
 * devolucion llego a enviarse.
 *
 * Es un componente compartido y no un banner copiado en cada pagina a proposito: el modo de
 * fallo de este repositorio es que cuatro copias de lo mismo acaben diciendo cuatro cosas
 * distintas.
 *
 * Para los fallos de CARGA existe `PortalLoadError`, que es otra cosa: aquel explica que la
 * lista vacia puede no ser la verdad; este informa del resultado de una accion.
 */
export default function PortalActionFeedback({ feedback }: { feedback: PortalFeedback | null }) {
    if (!feedback) {
        return null;
    }

    const esError = feedback.type === 'error';

    return (
        <Alert
            color={esError ? 'failure' : 'success'}
            icon={esError ? HiOutlineExclamationTriangle : HiOutlineCheckCircle}
            className="mb-4"
            role={esError ? 'alert' : 'status'}
        >
            <span className="text-xs font-semibold">{feedback.text}</span>
        </Alert>
    );
}
