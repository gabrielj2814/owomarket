import Dashboard from "@/components/layouts/Dashboard";
import AdminServices from "@/Services/AdminServices";
import { FormModuleAdmin } from "@/types/FormModuleAdmin";
import { Head } from "@inertiajs/react";
import { Breadcrumb, BreadcrumbItem, Button, Card, Label, TextInput } from "flowbite-react";
import { FC, useState } from "react"
import { HiHome, HiMail, HiPhone, HiPlus, HiUser } from "react-icons/hi";


interface FormPageProps{
    title?:       string;
    user_id:      string;
    record_id?: string;
}

const FormPage:FC<FormPageProps> = ({title="Nueno Modulo", user_id, record_id=null}) => {

    const [form, setForm] = useState<FormModuleAdmin>({
        id: (record_id!=null)?record_id:"",
        name: "testA",
        email: "testA@gmail.com",
        phone: "04160430565",
    })


    // Handlers

     const handlersChangeForm = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setForm(prev => ({
            ...prev,
            [name]: value
        }));
    }

    const handlersSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault()

        const respuestaApi= AdminServices.create(user_id,form)
        // alert("hola")
        // console.log("datos formulario => ",form)
    }

    const regresar = () => {
        window.location.href=`/backoffice/admin/${user_id}/module/admin`
    }

    const cancelar = () => {
        window.location.href=`/backoffice/admin/${user_id}/module/admin`
    }




    return (
        <>

         <Head>
                <title>{title}</title>
            </Head>
            <Dashboard user_uuid={user_id}>
                <Breadcrumb aria-label="Solid background breadcrumb example" className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-2">
                    <BreadcrumbItem href={`/backoffice/admin/${user_id}/dashboard`} icon={HiHome}>
                        Home
                    </BreadcrumbItem>
                    <BreadcrumbItem href={`/backoffice/admin/${user_id}/module/admin`} >Admins</BreadcrumbItem>
                    <BreadcrumbItem >
                        {record_id==null &&
                            "Nuevo Registro"
                        }
                        {record_id!=null &&
                            "Nombre de usuario"
                        }
                    </BreadcrumbItem>
                </Breadcrumb>
                <Card className="p-4 mb-3">
                        <h2 className=" text-3xl p-2">Form</h2>
                        <form onSubmit={handlersSubmit}>
                            <input type="hidden" id="id" name="id" value={(record_id==null)?"":record_id}/>
                            <div className="flex flex-wrap flex-row mb-5 lg:gap-4">
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2 lg:p-0">
                                    <div className="mb-2 block">
                                        <Label htmlFor="name">Name <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="name" type="text" name="name" icon={HiUser} placeholder="Name" onChange={handlersChangeForm} value={form.name} required />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2 lg:p-0">
                                    <div className="mb-2 block">
                                        <Label htmlFor="email">Email <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="email" type="email" name="email" icon={HiMail} placeholder="email@owomarket.com" onChange={handlersChangeForm} value={form.email} required />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2 lg:p-0">
                                    <div className="mb-2 block">
                                        <Label htmlFor="phone">Phone <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="phone" type="text" name="phone" icon={HiPhone} placeholder="00000000000" onChange={handlersChangeForm} value={form.phone} required />
                                </div>
                            </div>

                            <div className="flex flex-row justify-end gap-4">
                                <Button pill color="red" onClick={cancelar}>Cancelar</Button>
                                <Button pill type="submit">Save</Button>
                            </div>
                        </form>
                </Card>

            </Dashboard>

        </>
    )
}

export default FormPage
