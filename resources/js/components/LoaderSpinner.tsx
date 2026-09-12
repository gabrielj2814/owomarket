import { Spinner } from "flowbite-react";
import React from "react";
import style from "../../css/loader.module.css"

interface LoaderSpinnerProps {
    // Puedes agregar props si es necesario
    status: boolean;
}

const LoaderSpinner:React.FC<LoaderSpinnerProps> = ({status=false}) => {

    return (
        <>
            {status==true &&
                /* El velo a pantalla completa se queda en su modulo CSS --Flowbite no tiene
                   un overlay de carga--, pero la ruedecita de dentro si es suya. */
                <div className={style.contenedor}>
                    <Spinner size="xl" aria-label="Cargando" />
                </div>
            }
        </>
    )


}

export default LoaderSpinner
