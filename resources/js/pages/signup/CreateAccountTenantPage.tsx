import LoaderSpinner from "@/components/LoaderSpinner";
import TenantServices from "@/Services/TenantServices";
import { FormCreateAccounTenant } from "@/types/FormCreateAccounTenant";
import { Button, Label, TextInput } from "flowbite-react";
import { useState } from "react";
import { HiMail, HiPhone, HiUser } from "react-icons/hi";
import { LuSend, LuStore } from "react-icons/lu";
import { TbPassword } from "react-icons/tb";


const CreateAccountTenantPage = () => {

    const [formulario, setFormulario] = useState<FormCreateAccounTenant>({
        name: "Jaen Doe",
        email: "Jaen@hoyoverse.com",
        phone: "12345678901",
        password: "Jaen_Doe1234",
        confirmPassword: "Jaen_Doe1234",
        store_name: "Zenless Zone Zero Corp",
        tenant_name: "zenless-zone-zero-corp.owomarket.local"
    });

    const [statusLoader, setStatusLoader] = useState<boolean>(false);


    const handlersChangeForm = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormulario(prev => ({
            ...prev,
            [name]: value
        }));
    }


    const handleSubmitForm = async (e: React.FormEvent) => {
        e.preventDefault();
        console.log(formulario);
        setStatusLoader(true);

        const respuestaApi= await TenantServices.createAccountTenant(formulario);

        setStatusLoader(false);

        if(respuestaApi.status !== 201){
            alert(respuestaApi.response?.data.message);
            return;
        }

        alert("Cuenta creada correctamente. Por favor, revise su correo electrónico para más detalles.");

        setFormulario({
            name: "",
            email: "",
            phone: "",
            password: "",
            confirmPassword: "",
            store_name: "",
            tenant_name: ""
        });

        location.href= "/auth/login-staff";
    }

    return (
        <main className="flex flex-row h-screen bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            <LoaderSpinner status={statusLoader} />

            <div className=" basis-full lg:basis-1/2 hidden lg:block p-4 h-screen">
                <div className=" text-2xl font-bold mb-10"> <LuStore className=" inline-block text-blue-700 w-10 h-10"/>  OwOMarket</div>

                <div className=" w-full flex flex-row justify-center">
                    <div className=" text-center">
                        <img className=" w-xl h-xl rounded-2xl mb-5" src="https://i.pinimg.com/736x/24/81/d1/2481d19f7d6d2062cc987c2384f0096e.jpg" alt="" />
                        <h2 className="text-4xl mb-3 font-bold">Open your store on OwOMarket</h2>
                        <div>Join hundreds of sellers and start reaching new customers today.</div>
                    </div>
                </div>

            </div>

            <div className=" basis-full lg:basis-1/2 h-screen overflow-y-auto bg-gray-200 text-gray-600 dark:bg-gray-950 dark:text-gray-400 flex flex-col items-center p-10">

                <div className=" w-10/12 lg:w-7/12">
                    <h1 className=" text-4xl mb-8 font-bold">Create Your Store & Account</h1>

                    <form className="" onSubmit={handleSubmitForm}>
                        <h2 className="text-1xl mb-2 font-bold">Personal Information</h2>

                        <div className="w-full flex flex-row flex-wrap justify-between">
                            <div className=" basis-full mb-2">
                                <div className="mb-2 block">
                                    <Label htmlFor="name">Name</Label>
                                </div>
                                <TextInput id="name" type="text" name="name" icon={HiUser} placeholder="Name" required value={formulario.name} onChange={handlersChangeForm} />
                            </div>
                            <div className="basis-full md:basis-5/12 mb-2">
                                <div className="mb-2 block">
                                    <Label htmlFor="email">Email</Label>
                                </div>
                                <TextInput id="email" type="email" name="email" icon={HiMail} placeholder="name@owomarket.com" required value={formulario.email} onChange={handlersChangeForm} />
                            </div>
                            <div className="basis-full md:basis-5/12 mb-2">
                                <div className="mb-2 block">
                                    <Label htmlFor="phone">Phone</Label>
                                </div>
                                <TextInput id="phone" type="text" name="phone" icon={HiPhone} placeholder="phone" required value={formulario.phone} onChange={handlersChangeForm} />
                            </div>
                        </div>
                        <div className="w-full flex flex-row flex-wrap justify-between mb-10">
                            <div className="basis-full md:basis-5/12 mb-2 md:mb-0">
                                <div className="mb-2 block">
                                    <Label htmlFor="password">Password</Label>
                                </div>
                                <TextInput id="password" type="password" name="password" icon={TbPassword} placeholder="Password" required value={formulario.password} onChange={handlersChangeForm} />
                            </div>
                            <div className="basis-full md:basis-5/12">
                                <div className="mb-2 block">
                                    <Label htmlFor="confirmPassword">Confirm Password</Label>
                                </div>
                                <TextInput id="confirmPassword" type="password" name="confirmPassword" icon={TbPassword} placeholder="Confirm Password" required value={formulario.confirmPassword} onChange={handlersChangeForm} />
                            </div>
                        </div>

                        <h2 className="text-1xl mb-3 font-bold">Store Information</h2>

                        <div className="w-full flex flex-row flex-wrap justify-between mb-20">
                            <div className="basis-full mb-2">
                                <div className="mb-2 block">
                                    <Label htmlFor="store_name">Store Name</Label>
                                </div>
                                <TextInput id="store_name" type="text" name="store_name" icon={LuStore} placeholder="Store Name" required value={formulario.store_name} onChange={handlersChangeForm} />
                            </div>
                            <div className="basis-full">
                                <div className="mb-2 block">
                                    <Label htmlFor="tenant_name">Tenant Name (Your store's unique address)</Label>
                                </div>
                                <TextInput id="tenant_name" type="text" name="tenant_name" icon={LuStore} placeholder="Tenant Name" required value={formulario.tenant_name} onChange={handlersChangeForm} />
                            </div>
                        </div>

                        <Button className=" w-full" type="submit"> <LuSend/> Register Store & Account</Button>


                    </form>

                </div>


            </div>

            {/* formulario movil */}





        </main>
    );


}

export default CreateAccountTenantPage;


