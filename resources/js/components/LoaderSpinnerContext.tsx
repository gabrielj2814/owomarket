import React from "react";
import style from "../../css/loader.module.css"
import { useDashboard } from "@/contexts/DashboardContext";

const LoaderSpinnerContext= () => {


    const { state, actions } = useDashboard()


    return (
        <>
            {state.loading==true &&
                <div className={style.contenedor}>
                    <span className={style.loader}></span>
                </div>
            }
        </>
    )


}

export default LoaderSpinnerContext
