import Dashboard from "@/components/layouts/Dashboard"
import { Alert, Breadcrumb, BreadcrumbItem, Button, Card } from "flowbite-react"
import { FC, useEffect, useState } from "react";
import { Head } from "@inertiajs/react";
import { HiHome, HiInformationCircle, HiPlus } from "react-icons/hi";
import LoaderSpinner from "@/components/LoaderSpinner";


interface IndexPageProps {
    title?: string;
    user_id: string
}


const IndexPage: FC<IndexPageProps> = ({ title = "Nuevo Modulo OwOMarket", user_id }) => {

    const [stateLodaer, setStateLodaer] = useState(false);

    const irHaFormularioDeCrear = () => {
        setStateLodaer(true)
        window.location.href=`/backoffice/admin/${user_id}/module/admin/record`
    }



    return (
        <>
            <LoaderSpinner status={stateLodaer} />
            <Head>
                <title>{title}</title>
            </Head>

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
