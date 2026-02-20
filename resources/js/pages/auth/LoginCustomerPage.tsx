import LoaderSpinner from "@/components/LoaderSpinner";
import storage from "@/routes/storage";
import AuthServices from "@/Services/AuthServices";
import FormLogin from "@/types/FormLogin";
import { Button, Card, Checkbox, Label, TextInput } from "flowbite-react";
import React, { useState } from "react";
import { HiLockClosed, HiMail } from "react-icons/hi";
import { LuSend, LuStore  } from "react-icons/lu";

interface PasswordValidationRules {
  [key: string]: RegExp;
}



const LoginCustomerPage = () => {

    const centralDomain = import.meta.env.VITE_APP_CENTRAL_DOMAIN;
    // console.log("centralDomain:", centralDomain);



    // ======= States =======

    const [statuFormLogin,  setStatuFormLogin] = useState<FormLogin>({
        email: "root@owomarket.local",
        password: 'OwO_12345678',
    });

    const [statusLoader,    setStatusLoader]   = useState<boolean>(false);

    // ======= UseEffect =======


    // ======= Validaciones =======
    const validarEmail = (email: string):boolean => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }

    /**
     * Valida una contraseña según las reglas de negocio
     *
     * Realiza las siguientes validaciones:
     * - Longitud mínima y máxima
     * - Presencia de mayúsculas
     * - Presencia de minúsculas
     * - Presencia de números
     * - Presencia de caracteres especiales
     *
     * @param password Contraseña a validar
     * @param minLength Longitud mínima requerida
     * @param maxLength Longitud máxima permitida
     * @returns boolean Indica si la contraseña es válida
     */
    function validatePassword(
        password: string,
        minLength: number = 8,
        maxLength: number = 72
    ): boolean {
        // Validar longitud mínima
        if (password.length < minLength) {
            return false;
        }

        // Validar longitud máxima (límite de BCrypt)
        if (password.length > maxLength) {
            return false;
        }

        // Reglas de complejidad de contraseña
        const rules: PasswordValidationRules = {
            'mayúscula': /[A-Z]/,                      // Al menos una letra mayúscula
            'minúscula': /[a-z]/,                      // Al menos una letra minúscula
            'número': /[0-9]/,                         // Al menos un número
            'carácter especial': /[!@#$%^&*()\-_=+{};:,<.>]/ // Al menos un carácter especial
        };

        // Aplicar cada regla de validación
        for (const [tipo, patron] of Object.entries(rules)) {
            if (!patron.test(password)) {
            return false;
            }
        }

        return true;
    }

    // ======= Handler =======

    const handlersChangeFormLogin = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setStatuFormLogin(prev => ({
            ...prev,
            [name]: value
        }));
    }

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        if(!validarEmail(statuFormLogin.email)){
            alert("El email no es válido");
            return
        }

        if(!validatePassword(statuFormLogin.password)){
            alert("La contraseña no cumple con los requisitos de seguridad");
            return
        }

        setStatusLoader(true);

        let respuestaServidor= await AuthServices.login(statuFormLogin)

        // console.log(respuestaServidor);

        setStatusLoader(false);

        if(respuestaServidor.status !== 200){
            alert(respuestaServidor.response?.data.message);
            return null
        }

        if(respuestaServidor.data.data == null){
            alert(respuestaServidor.response?.data.message);
            return null
        }

        const { rol , uuid } = respuestaServidor.data.data

        irHaPorElRol(rol,uuid);
    }


    const irHaPorElRol = (rol:string,uuid:string) => {
        // const BACKOFICCE_ADMIN_DASHBOARD = `/admin/backoffice/${uuid}/dashboard`;
        // const BACKOFICCE_TENANT_OWNER_DASHBOARD = `/tenant/owner/backoffice/${uuid}/dashboard`;
        if(rol === 'customer'){
            alert("Login exitoso como cliente");
            // window.location.href = BACKOFICCE_ADMIN_DASHBOARD;
        }
    }
    // ======= Render =======

    return (
        <>
            <main className="flex flex-row h-screen bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400">
                <LoaderSpinner status={statusLoader} />
                <div className=" basis-full lg:basis-1/2 h-screen hidden lg:block relative ">
                    <img className=" w-full h-full" src={storage.local.get("images/imagen_login_customer.jpeg").url} alt="" />
                    <div className=" w-full h-full absolute z-10 top-0 left-0 bg-gradient-to-t from-gray-900/90 to-transparent flex flex-col justify-end p-10">
                        <h2 className="text-4xl mb-3 font-bold">Discover your next favorite thing.</h2>
                    </div>
                </div>

                <div className="basis-full lg:basis-1/2 h-screen overflow-y-auto bg-gray-200 text-gray-600 dark:bg-gray-950 dark:text-gray-400 flex flex-col items-center justify-center p-10">
                    {/* <div className=" text-2xl font-bold mb-10 absolute top-5 left-5 block lg:hidden"> <LuStore className=" inline-block text-blue-700 w-10 h-10"/>  OwOMarket</div> */}
                    <div className=" w-full sm:w-10/12 md:w-10/12 lg:w-7/12">
                        <div className=" text-3xl font-bold  mb-10"> <LuStore className=" inline-block text-blue-700 w-8 h-8"/>  OwOMarket</div>
                        <h1 className="text-2xl text-gray-600 dark:text-gray-400 mb-2 font-bold">Welcome Back</h1>
                        <div className="mb-5">Log in to your account to continue.</div>
                        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
                            <div className="">
                                <div className="mb-2 block">
                                    <Label htmlFor="email">Email</Label>
                                </div>
                                <TextInput id="email" type="email" name="email" icon={HiMail} placeholder="name@owomarket.com" onChange={handlersChangeFormLogin} value={statuFormLogin.email} required />
                            </div>
                            <div className="mb-5">
                                <div className="mb-2 block">
                                    <Label htmlFor="password">Password</Label>
                                </div>
                                <TextInput id="password" type="password" name="password" icon={HiLockClosed} placeholder="password" onChange={handlersChangeFormLogin} value={statuFormLogin.password} required />
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox id="remember" />
                                <Label htmlFor="remember">Remember me</Label>
                            </div>
                            <Button type="submit"> <LuSend/>  Submit</Button>
                            <h6 className="text-sm text-gray-600 dark:text-gray-400"><span className="font-bold">Security Notice:</span> Unauthorized access to this management suite is strictly prohibited and monitored. All activities are logged to ensure platform integrity and compliance.</h6>
                        </form>
                    </div>
                </div>

            </main>





        </>
    )

}

export default LoginCustomerPage;
