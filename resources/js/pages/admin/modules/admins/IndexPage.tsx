import Dashboard from "@/components/layouts/Dashboard"
import { Alert, Breadcrumb, BreadcrumbItem, Button, Card } from "flowbite-react"
import { FC, ReactNode, useEffect, useState } from "react";
import { Head } from "@inertiajs/react";
import { HiHome, HiInformationCircle, HiPlus } from "react-icons/hi";
import LoaderSpinner from "@/components/LoaderSpinner";
import HeaderToasts from "@/components/HeaderToasts";
import { ToastInterface } from "@/types/ToastInterface";
import {v4 as uuidv4} from "uuid"


interface IndexPageProps {
    title?:          string;
    user_id:         string;
    type?:           string;
    titleToast?:     string;
    message?:        string;
}


const IndexPage: FC<IndexPageProps> = ({ title = "Nuevo Modulo OwOMarket", user_id, type=null, message=null, titleToast=null }) => {

    const [stateLodaer,    setStateLodaer] = useState(false);

    const [mapToast,       setMapToast]    = useState<Map<string,ToastInterface>>(new Map<string,ToastInterface>())

    const irHaFormularioDeCrear = () => {
        setStateLodaer(true)
        window.location.href=`/backoffice/admin/${user_id}/module/admin/record`
    }

    useEffect(() => {
        if(type!=null && titleToast!=null && message!=null){
            createToast(type, titleToast, message, <HiHome/>)
        }
    },[])

    const createToast = (type: string, title: string, message?: string, icon?: ReactNode) => {
        const uuid= uuidv4();
        const dataToast:ToastInterface={
            type,
            title,
            message,
            icon
        }
        // mapToast.set(uuid, dataToast)
        // setMapToast(mapToast)

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


    // if(type!=null && titleToast!=null && message!=null){
    //     alert("uwu")
    //     createToast(type, titleToast, message, <HiHome/>)
    // }



    return (
        <>
            <LoaderSpinner status={stateLodaer} />
            <Head>
                <title>{title}</title>
            </Head>

            <HeaderToasts list={Array.from(mapToast.values())}/>
            <Dashboard user_uuid={user_id}>

                <Breadcrumb aria-label="Solid background breadcrumb example" className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-2">
                    <BreadcrumbItem href={`/backoffice/admin/${user_id}/dashboard`} icon={HiHome}>
                        Home
                    </BreadcrumbItem>
                    <BreadcrumbItem >Admins</BreadcrumbItem>
                </Breadcrumb>

                <div className="w-full flex flex-row justify-end mb-5">
                    <Button onClick={irHaFormularioDeCrear} pill> <HiPlus className=" w-6 h-6 mr-1"/>  Create</Button>
                </div>
                <Card className="p-4 mb-3">
                    <div className=" dark:text-white">Filtros</div>
                </Card>
                <Card className="p-4">
                    <div className=" dark:text-white">Reporte</div>
                </Card>

            </Dashboard>
        </>
    )
}

export default IndexPage
