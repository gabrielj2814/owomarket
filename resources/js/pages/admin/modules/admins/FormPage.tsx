import Dashboard from "@/components/layouts/Dashboard";
import LoaderSpinner from "@/components/LoaderSpinner";
import AdminServices from "@/Services/AdminServices";
import { FormModuleAdmin } from "@/types/FormModuleAdmin";
import { Head } from "@inertiajs/react";
import { Breadcrumb, BreadcrumbItem, Button, Card, Label, TextInput } from "flowbite-react";
import { FC, useEffect, useState } from "react"
import { HiHome, HiMail, HiPhone, HiPlus, HiUser } from "react-icons/hi";


interface FormPageProps{
    title?:       string;
    user_id:      string;
    record_id?: string;
}

const FormPage:FC<FormPageProps> = ({title="Nueno Modulo", user_id, record_id=null}) => {

    const [load,           setLoad] = useState<boolean>(false)

    const [recordId,       setRecordId] = useState<string|null>(record_id)

    const [form,           setForm] = useState<FormModuleAdmin>({
        id: (record_id!=null)?record_id:"",
        name: "",
        email: "",
        phone: "",
    })

    // useEffect

    useEffect(() => {
        const inicializar = async () => {
            setLoad(true)
            await consultarAdmin()
            setLoad(false)
        }
        inicializar()
    },[recordId])


    const consultarAdmin =async () => {
        if(record_id==null){
            return

        }

        const repuestaApi = await AdminServices.consultByUuid(record_id)

        if(repuestaApi.status!=200 && repuestaApi.data.data==null){
            return
        }

        if(repuestaApi.data.data==null ){
            return
        }

        setForm({
            id: repuestaApi.data.data.id,
            name: repuestaApi.data.data.name,
            email: repuestaApi.data.data.email,
            phone: repuestaApi.data.data.phone,
        })

    }


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
        setLoad(true)
        // agregar validaciones formulario
        if(recordId==null){
            const respuestaApi=await AdminServices.create(user_id,form)
            if(respuestaApi.status!=200){
                return
            }
            alert("OK Register")
        }
        else{
            const respuestaApi=await AdminServices.update(user_id,form)
              if(respuestaApi.status!=200){
                return
            }
            alert("OK Update")
        }
        setLoad(false)
    }

    const regresar = () => {
        window.location.href=`/backoffice/admin/${user_id}/module/admin`
    }

    const cancelar = () => {
        window.location.href=`/backoffice/admin/${user_id}/module/admin`
    }




    return (
        <>
        <LoaderSpinner status={load} />
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
