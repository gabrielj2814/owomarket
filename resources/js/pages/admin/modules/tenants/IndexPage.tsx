import dayjs from "dayjs"
import utc from "dayjs/plugin/utc"
import timezone from "dayjs/plugin/timezone";
import { FC, useState } from "react";
import dateUtils from "@/utils/date";
import { ToastInterface } from "@/types/ToastInterface";
import LoaderSpinner from "@/components/LoaderSpinner";
import { Head } from "@inertiajs/react";
import HeaderToasts from "@/components/HeaderToasts";
import Dashboard from "@/components/layouts/Dashboard";
import { Breadcrumb, BreadcrumbItem } from "flowbite-react";
import { HiHome } from "react-icons/hi";

interface IndexPageProps {
    title?:          string;
    user_id:         string;
    type?:           string;
    titleToast?:     string;
    message?:        string;
}

const IndexPage:FC<IndexPageProps> = ({ title = "Nuevo Modulo OwOMarket", user_id, type=null, message=null, titleToast=null }) => {

    const zonaHorariaSistema=import.meta.env.TIME_ZONE_SISTEMA
    const fechasDelMesActual= dateUtils.getFirstAndLastDayOfCurrentMonth()

    const FechaDesde=new Date(fechasDelMesActual.firstDay.toString());
    const FechaHasta=new Date(fechasDelMesActual.lastDay.toString());

    dayjs.extend(utc);
    dayjs.extend(timezone);

    const [cargaInicialCompleta,     setCargaInicialCompleta]         = useState(false);
    const [stateLodaer,              setStateLodaer]                  = useState(false);

    const [mapToast,                 setMapToast]                     = useState<Map<string,ToastInterface>>(new Map<string,ToastInterface>())

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
                    <BreadcrumbItem href={`/backoffice/tenant/${user_id}/module`} >Tenants</BreadcrumbItem>
                </Breadcrumb>



            </Dashboard>

        </>
    )

}

export default IndexPage
