import Dashboard from "@/components/layouts/Dashboard";
import AuthServices from "@/Services/AuthServices";
import { Button } from "flowbite-react";

const InicialPage = () => {

    const logout = async () => {

        const respuesta = await AuthServices.logout()
        console.log(respuesta)
        window.location.href = '/auth/login-staff';

    }



    return (
        <>
            <Dashboard>
                <h1 className=" dark:text-white">Dashboard</h1>
            </Dashboard>
        </>
    )
}

export default InicialPage;
