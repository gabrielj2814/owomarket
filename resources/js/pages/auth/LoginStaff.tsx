import LoaderSpinner from "@/components/LoaderSpinner";
import AuthServices from "@/Services/AuthServices";
import FormLogin from "@/types/FormLogin";
import { Button, Card, Checkbox, Label, TextInput } from "flowbite-react";
import React, { useState } from "react";
import { HiLockClosed, HiMail } from "react-icons/hi";
import { LuSend, LuStore  } from "react-icons/lu";

interface PasswordValidationRules {
  [key: string]: RegExp;
}



const LoginStaff = () => {

    // ======= States =======

    const [statuFormLogin,  setStatuFormLogin] = useState<FormLogin>({
        email: "",
        password: '',
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

        irHaPorElRol(respuestaServidor.data.data.rol);
    }


    const irHaPorElRol = (rol:string) => {
        if(rol === 'super_admin'){
            window.location.href = '/auth/pagina-inicial';
        }
        if(rol === 'tenant_owner'){
            window.location.href = '/auth/pagina-inicial';
        }
        if(rol === 'customer'){
            window.location.href = '/auth/pagina-inicial';
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
                            <h1 className=" text-2xl text-gray-600 dark:text-gray-400 mb-5 font-bold">Staff Sing In</h1>
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

export default LoginStaff;
