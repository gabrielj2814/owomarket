import { Spinner } from "flowbite-react";
import React from "react";
import style from "../../css/loader.module.css"
import { useDashboard } from "@/contexts/DashboardContext";

const LoaderSpinnerContext= () => {


    const { state, actions } = useDashboard()


    return (
        <>
            {state.loading==true &&
                /* El velo a pantalla completa se queda en su modulo CSS --Flowbite no tiene
                   un overlay de carga--, pero la ruedecita de dentro si es suya. */
                <div className={style.contenedor}>
                    <Spinner size="xl" aria-label="Cargando" />
                </div>
            }
        </>
    )


}

export default LoaderSpinnerContext
