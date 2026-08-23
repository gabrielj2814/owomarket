import LoaderSpinner from "@/components/LoaderSpinner";
import AuthServices from "@/Services/AuthServices";
import FormLogin from "@/types/FormLogin";
import { Button, Card, Checkbox, Label, TextInput } from "flowbite-react";
import React, { FC, useState } from "react";
import { HiLockClosed, HiMail } from "react-icons/hi";
import { HiOutlineExclamationCircle } from "react-icons/hi2";
import { LuSend, LuStore  } from "react-icons/lu";

interface LoginTenantPageProps {
    domain: string;
}



const LoginTenantPage:FC<LoginTenantPageProps> = ({ domain }) => {

    const centralDomain = import.meta.env.VITE_APP_CENTRAL_DOMAIN;
    // console.log("centralDomain:", centralDomain);
    // ======= States =======

    const [statuFormLogin,  setStatuFormLogin] = useState<FormLogin>({
        email: "admin@chivostore.com",
        password: 'EndAdmin_12345678',
    });

    const [statusLoader,    setStatusLoader]   = useState<boolean>(false);

    /*
     * Hallazgo A5. Todo el feedback de esta pantalla era alert(): bloquea el hilo, no se
     * puede estilar, no es accesible y se pierde en cuanto se acepta. Las paginas de
     * recuperacion ya usaban estado y mensaje en linea; estos logins eran los que se
     * habian quedado atras, asi que se copia ese patron en vez de inventar otro.
     */
    const [errorMsg,        setErrorMsg]      = useState<string | null>(null);

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
        setErrorMsg(null);

        if(!validarEmail(statuFormLogin.email)){
            setErrorMsg("El email no es válido.");
            return
        }

        /*
         * Hallazgo A4: aqui se comprobaba el FORMATO de la contrasena antes de enviarla
         * —8-72, mayuscula, minuscula, digito y simbolo—. Validar el formato de una
         * contrasena que ya existe no protege nada: la contrasena ya esta creada. Lo unico
         * que conseguia era dejar fuera a quien tuviera una antigua que no cumpliera las
         * reglas de hoy, sin que el servidor comprobara esas reglas en ningun momento.
         *
         * Que es una contrasena valida se decide ahora en un solo sitio y solo al CREARLA:
         * Password::defaults() en AppServiceProvider.
         */

        setStatusLoader(true);

        const respuestaServidor= await AuthServices.login(statuFormLogin)

        // console.log(respuestaServidor);

        setStatusLoader(false);

        if(respuestaServidor.status !== 200){
            setErrorMsg(respuestaServidor.response?.data?.message || "No se pudo iniciar sesión. Revisa tus credenciales.");
            return null
        }

        if(respuestaServidor.data.data == null){
            setErrorMsg(respuestaServidor.response?.data?.message || "No se pudo iniciar sesión. Revisa tus credenciales.");
            return null
        }

        const { rol , uuid } = respuestaServidor.data.data

        irHaPorElRol(rol,uuid);
    }


    const irHaPorElRol = (rol:string,uuid:string) => {
        const BACKOFICCE_TENANT_OWNER_DASHBOARD = `/tenant/backoffice/${uuid}/dashboard`;
        if(rol === 'owner'){
            window.location.href = BACKOFICCE_TENANT_OWNER_DASHBOARD;
        }
    }
    // ======= Render =======

    return (
        <>
            <main className="flex flex-col items-center justify-center h-screen bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400">
                <LoaderSpinner status={statusLoader} />
                <Card className="w-11/12 lg:w-8/12">
                    <div className="flex flex-row">
                        <div className="w-3/6 hidden lg:flex  flex-col justify-center p-5 text-gray-600 dark:text-gray-400  ">
                            <h3 className="text-xl font-bold mb-7"><LuStore className=" inline-block text-4xl stroke-blue-600"/> Marketplace for business</h3>
                            <h3 className="text-4xl font-bold mb-3">Store Management Portal</h3>
                            <h3 className="text-xl text-gray-300">Welcomen back. Manage your products, orders, and staff</h3>
                        </div>
                        <div className="w-full lg:w-3/6 ">
                            <h1 className=" text-2xl text-gray-600 dark:text-gray-400 mb-5 font-bold">Login Staff Sing In</h1>
                            <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
                            {errorMsg && (
                                <div role="alert" className="p-3 rounded-xl bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 text-xs font-bold flex items-center gap-2">
                                    <HiOutlineExclamationCircle className="w-4 h-4 flex-shrink-0" />
                                    {errorMsg}
                                </div>
                            )}
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
                                {/* <div className="flex items-center gap-2">
                                    <Checkbox id="remember" />
                                    <Label htmlFor="remember">Remember me</Label>
                                </div> */}
                                <Button type="submit"> <LuSend/>  Submit</Button>
                                <h6 className=" text-center text-sm text-gray-600 dark:text-gray-400">For authorized personnel only</h6>
                            </form>
                        </div>
                    </div>
                </Card>

            </main>





        </>
    )

}

export default LoginTenantPage;
