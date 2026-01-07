import Dashboard from "@/components/layouts/Dashboard";
import LoaderSpinner from "@/components/LoaderSpinner";
import AdminServices from "@/Services/AdminServices";
import { AlertInputForm } from "@/types/AlertInputForm";
import { ErrorsFormModuleAdmin } from "@/types/ErrorsFormModuleAdmin";
import { FormModuleAdmin } from "@/types/FormModuleAdmin";
import { ErrorResponse } from "@/types/Response/ErrorResponse";
import { Head } from "@inertiajs/react";
import { Breadcrumb, BreadcrumbItem, Button, Card, HelperText, Label, TextInput } from "flowbite-react";
import { FC, ReactNode, useEffect, useState } from "react"
import { HiHome, HiMail, HiOutlineExclamationCircle, HiPhone, HiUser } from "react-icons/hi";
import { LuArrowBigLeft, LuPencil, LuSave, LuSaveOff, LuTriangleAlert, } from "react-icons/lu";
import {v4 as uuidv4} from "uuid"
import { ToastInterface } from "@/types/ToastInterface";
import HeaderToasts from "@/components/HeaderToasts";
import ModalAlertConfirmation from "@/components/ui/ModalAlertConfirmation";


interface FormPageProps{
    title?:       string;
    user_id:      string;
    record_id?:   string;
}

const FormPage:FC<FormPageProps> = ({title="Nueno Modulo", user_id, record_id=null}) => {
    // tecno-isekaic.owner@owomarket.local

    const [load,                       setLoad] = useState<boolean>(false)
    const [statuModalCancel,           setStatuModalCancel] = useState<boolean>(false)
    const [statuModalSave,             setStatuModalSave] = useState<boolean>(false)

    const [recordId,                   setRecordId] = useState<string|null>(record_id)

    const [mapToast,                   setMapToast] = useState<Map<string,ToastInterface>>(new Map<string,ToastInterface>())

    const [form,                       setForm] = useState<FormModuleAdmin>({
        id: (record_id!=null)?record_id:"",
        name: "",
        email: "",
        phone: "",
    })

    const [errorForms,           setErrorForms] = useState<ErrorsFormModuleAdmin>({
        name:  null,
        email: null,
        phone: null,
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

    const handlersSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault()
        mostrarModalSave()
    }

    const enviar = async () => {
        if(!validarFormulario()){
            return
        }

        setLoad(true)
        if(recordId==null){
            const respuestaApi=await AdminServices.create(user_id,form)
            setLoad(false)
            if(respuestaApi.status!=200){
                // console.log(respuestaApi.response?.data.errors)
                if(respuestaApi.response){
                    if(respuestaApi.response.data.errors){
                        capturarErroresForm(respuestaApi.response.data.errors)
                        createToast("warning", `Error ${respuestaApi.status}: Error Create Admin`, undefined, <LuTriangleAlert />)
                    }
                }
                cerrarModalSave()
                return
            }
            cerrarModalSave()
            createToast("success", "Admin Created", "The Admin Create Successful",<LuSave/>)
            irHaInicio()
        }
        else{
            const respuestaApi=await AdminServices.update(user_id,form)
            setLoad(false)
            if(respuestaApi.status!=200){
                if(respuestaApi.response){
                    if(respuestaApi.response.data.errors){
                        capturarErroresForm(respuestaApi.response.data.errors)
                        createToast("warning", `Error ${respuestaApi.status}: Error Update Admin`, undefined, <LuTriangleAlert />)
                    }
                }
                cerrarModalSave()
                return
            }
            cerrarModalSave()
            createToast("success", "Admin Updated", "The Admin Update Successful", <LuPencil/>)
            irHaInicio()
        }
    }

    const capturarErroresForm= (errores: ErrorResponse): void => {

        Object.entries(errores).forEach(([field, messages]) => {
            const error: string = messages[0];
            updateErrorForm(field,{type: "failure", message: messages[0]})
            // console.log(`Error en ${field}: ${error}`);
            // Ej: setFieldError(field, error);
        });

    }

    const updateErrorForm= (name:string ,error: AlertInputForm | null) => {
        if(error==null){
            setErrorForms(prev => ({
                ...prev,
                [name]: null
            }));
        }
         setErrorForms(prev => ({
            ...prev,
            [name]: error
        }));
    }

     const validarFormulario= (): boolean => {
        let estadoFormulario= true
        // validaciones name
        if(form.name.trim()==""){
            updateErrorForm("name",{type: "failure", message: "el campo no puede quedar vació"})
            estadoFormulario= false
        }

        // validaciones email
        if(form.email.trim()==""){
            updateErrorForm("email",{type: "failure", message: "el campo no puede quedar vació"})
            estadoFormulario= false
        }

        // validaciones phone
        if(form.phone.trim()==""){
            updateErrorForm("phone",{type: "failure", message: "el campo no puede quedar vació"})
            estadoFormulario= false
        }

        if(estadoFormulario){
            updateErrorForm("name",{type: "success", message: ""})
            updateErrorForm("email",{type: "success", message: ""})
            updateErrorForm("phone",{type: "success", message: ""})
        }

        return estadoFormulario
    }

    const createToast = (type: string, title: string, message?: string, icon?: ReactNode) => {
        const uuid= uuidv4();
        const dataToast:ToastInterface={
            type,
            title,
            message,
            icon
        }

        setMapToast(prevMap => {
            const newMap = new Map(prevMap);
            newMap.set(uuid, dataToast);
            return newMap;
        });

        // Opcional: Eliminar el toast después de un tiempo
        setTimeout(() => {
            removeToast(uuid);
        }, 5000); // 5 segundos
    }

    const removeToast = (uuid: string) => {
        setMapToast(prevMap => {
            const newMap = new Map(prevMap);
            newMap.delete(uuid);
            return newMap;
        });
    }

    const mostrarModalCancelar = () => {
        setStatuModalCancel(true)
    }

    const cerrarModalCancelar = () => {
        setStatuModalCancel(false);
    }

    const actionModalCancelar = () => {
         setStatuModalCancel(true);
         setLoad(true)
         cancelar()
    }

    const mostrarModalSave = () => {
        setStatuModalSave(true)
    }

    const cerrarModalSave = () => {
        setStatuModalSave(false)
    }

    const regresar = () => {
        window.location.href=`/admin/backoffice/${user_id}/module`
    }

    const irHaInicio = (type?:string, message?: string) => {
        if(type!=null && message!= null){
            window.location.href=`/admin/backoffice/${user_id}/module?type=${type}&message=${message}`
        }
        window.location.href=`/admin/backoffice/${user_id}/module`
    }

    const cancelar = () => {
        window.location.href=`/admin/backoffice/${user_id}/module`
    }




    return (
        <>
            <LoaderSpinner status={load} />
            <Head>
                <title>{title}</title>
            </Head>

            {/* Modal Cancel */}
            <ModalAlertConfirmation
            openModal={statuModalCancel}
            size="md"
            icon={<HiOutlineExclamationCircle className="mx-auto mb-4 h-14 w-14 text-gray-400 dark:text-gray-200"  />}
            text="Are you sure you want to cancel the transaction?"
            buttonTextCancel="No, I want not cancel"
            buttonTextAction="yes, I want cancel"
            onClose={cerrarModalCancelar}
            onClickAction={actionModalCancelar}
            colorButtonCancel="alternative"
            colorButtonAction="red"
            />

            <ModalAlertConfirmation
            openModal={statuModalSave}
            size="md"
            icon={<LuSave className="mx-auto mb-4 h-14 w-14 text-gray-400 dark:text-gray-200"  />}
            text="Are you sure you want to save?"
            buttonTextCancel="No, I want not cancel"
            buttonTextAction="yes, I want save"
            onClose={cerrarModalSave}
            onClickAction={enviar}
            colorButtonCancel="alternative"
            colorButtonAction="green"
            />

            <HeaderToasts list={mapToast.values().toArray()}/>

            <Dashboard user_uuid={user_id}>
                <Breadcrumb aria-label="Solid background breadcrumb example" className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-2">
                    <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                        Home
                    </BreadcrumbItem>
                    <BreadcrumbItem href={`/admin/backoffice/${user_id}/module/admin`} >Admins</BreadcrumbItem>
                    <BreadcrumbItem >
                        {record_id==null &&
                            "New Record"
                        }
                        {record_id!=null &&
                            form.name
                        }
                    </BreadcrumbItem>
                </Breadcrumb>
                <div className="flex flex-row mb-2">
                    <Button pill color="red" onClick={regresar}><LuArrowBigLeft className=" w-6 h-6 mr-1"/>  Back</Button>
                </div>
                <Card className="p-4 mb-3">
                        <h2 className=" text-3xl p-2">Form</h2>
                        <form onSubmit={handlersSubmit}>
                            <input type="hidden" id="id" name="id" value={(record_id==null)?"":record_id}/>
                            <div className="flex flex-wrap flex-row mb-5 lg:gap-4">
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2 lg:p-0">
                                    <div className="mb-2 block">
                                        <Label htmlFor="name" color={`${(errorForms.name!=null)?errorForms.name.type:"gray"}`} >Name <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="name" type="text" name="name" icon={HiUser} placeholder="Name" onChange={handlersChangeForm} value={form.name} required color={`${(errorForms.name!=null)?errorForms.name.type:"gray"}`} />
                                    {errorForms.name!=null &&
                                        <HelperText color={`${(errorForms.name!=null)?errorForms.name.type:"gray"}`}>
                                            {errorForms.name.message}
                                        </HelperText>
                                    }
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2 lg:p-0">
                                    <div className="mb-2 block">
                                        <Label htmlFor="email" color={`${(errorForms.email!=null)?errorForms.email.type:"gray"}`}>Email <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="email" type="email" name="email" icon={HiMail} placeholder="email@owomarket.com" onChange={handlersChangeForm} value={form.email} color={`${(errorForms.email!=null)?errorForms.email.type:"gray"}`} required />
                                    {errorForms.email!=null &&
                                        <HelperText color={`${(errorForms.email!=null)?errorForms.email.type:"gray"}`}>
                                            {errorForms.email.message}
                                        </HelperText>
                                    }
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2 lg:p-0">
                                    <div className="mb-2 block">
                                        <Label htmlFor="phone" color={`${(errorForms.phone!=null)?errorForms.phone.type:"gray"}`}>Phone <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="phone" type="text" name="phone" icon={HiPhone} placeholder="00000000000" onChange={handlersChangeForm} value={form.phone} required color={`${(errorForms.phone!=null)?errorForms.phone.type:"gray"}`} />
                                    {errorForms.phone!=null &&
                                        <HelperText color={`${(errorForms.phone!=null)?errorForms.phone.type:"gray"}`}>
                                            {errorForms.phone.message}
                                        </HelperText>
                                    }
                                </div>
                            </div>

                            <div className="flex flex-row flex-wrap justify-end gap-4">
                                <Button pill color="red" onClick={mostrarModalCancelar} className="w-full sm:w-auto order-2 sm:order-1"> <LuSaveOff className=" w-6 h-6 mr-1"/> Cancelar</Button>
                                <Button pill type="submit" className="w-full sm:w-auto order-1 sm:order-2"> <LuSave className=" w-6 h-6 mr-1"/>   Save</Button>
                            </div>
                        </form>
                </Card>

            </Dashboard>

        </>
    )
}

export default FormPage
