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
                <div className={style.contenedor}>
                    <span className={style.loader}></span>
                </div>
            }
        </>
    )


}

export default LoaderSpinner
