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
            <h1>hola usuario</h1>

            <Button onClick={() => logout()}>Logout</Button>

        </>
    )
}

export default InicialPage;
