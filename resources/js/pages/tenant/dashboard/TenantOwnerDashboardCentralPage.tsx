import dayjs from "dayjs"
import { v4 as uuidv4 } from "uuid"
import dateUtils from "@/utils/date";
import utc from "dayjs/plugin/utc"
import timezone from "dayjs/plugin/timezone";
import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import { Badge, Breadcrumb, BreadcrumbItem, Button, Card } from "flowbite-react";
import { FC, ReactNode, use, useEffect, useState } from "react";
import { HiCheck, HiClock, HiDotsVertical, HiHome } from "react-icons/hi";
import { TbLink, TbPower } from "react-icons/tb";
import Tenant from "@/types/models/Tenant";
import { ToastInterface } from "@/types/ToastInterface";
import LoaderSpinner from "@/components/LoaderSpinner";
import HeaderToasts from "@/components/HeaderToasts";
import TenantServices from "@/Services/TenantServices";
import { LuClock3, LuLink, LuPlus, LuPower, LuPowerOff, LuTrash2, LuX } from "react-icons/lu";


interface TenantOwnerDashboardCentralPageProps {
    title?: string;
    user_id: string
}

const TenantOwnerDashboardCentralPage: FC<TenantOwnerDashboardCentralPageProps> = ({ title, user_id }) => {


    const zonaHorariaSistema = import.meta.env.TIME_ZONE_SISTEMA
    const fechasDelMesActual = dateUtils.getFirstAndLastDayOfCurrentMonth()

    const FechaDesde = new Date(fechasDelMesActual.firstDay.toString());
    const FechaHasta = new Date(fechasDelMesActual.lastDay.toString());

    dayjs.extend(utc);
    dayjs.extend(timezone);


    const [cargaInicialCompleta, setCargaInicialCompleta] = useState<boolean>(false);
    const [stateLodaer, setStateLodaer] = useState<boolean>(false);


    const [tenants, setTenants] = useState<Tenant[]>([])
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPage, settotalPage] = useState<number>(0);
    const [lastPage, setLastPage] = useState<number>(0);
    const [prePage, setPrePage] = useState<number>(50);


    const [mapToast,                          setMapToast]                                 = useState<Map<string,ToastInterface>>(new Map<string,ToastInterface>())



    useEffect(() => {
        // if (type != null && titleToast != null && message != null) {
        //     createToast(type, titleToast, message, <HiHome />)
        // }
        const incializar = async () => {
            setStateLodaer(true)
            await consultOwnerCompanies(currentPage);
            setStateLodaer(false)
            setCargaInicialCompleta(true)
        }
        incializar()
    }, [])


    const consultOwnerCompanies = async (page:number = 1) => {
        const respuestaApi= await TenantServices.consultMyCompanies(user_id,page, 50);

        if(respuestaApi.data.code!=200){
            return
        }

        let data= (respuestaApi.data.data!=null)? respuestaApi.data.data: []
        let last= (respuestaApi.data.pagination!=null)? respuestaApi.data.pagination.last_page: 0
        let pre= (respuestaApi.data.pagination!=null)? respuestaApi.data.pagination.per_page: 0
        let total= (respuestaApi.data.pagination!=null)? respuestaApi.data.pagination.total: 0
        console.log("respuesta api => ",data)
        setTenants(data)
        setLastPage(last)
        setPrePage(pre)
        settotalPage(total)
    }





    const createToast = (type: string, title: string, message?: string, icon?: ReactNode) => {
        const uuid = uuidv4();
        const dataToast: ToastInterface = {
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




    function irAlTenant(url: string) {
        window.open(`http://${url}/auth/login`, "_blank");
    }


    const createCardCompanies= (tenants: Tenant[]): ReactNode[] => {
        return tenants.map((tenant: Tenant, index: number) => {
            return (
                 <Card className="max-w-sm d-block">
                    <div>
                        <div className=" flex flex-nowrap flex-row justify-end">
                            <HiDotsVertical className=" w-5 h-5" />
                        </div>
                        <h5 className="text-2xl mb-2 font-bold tracking-tight text-gray-900 dark:text-white">
                            {tenant.name}
                        </h5>
                        <div className="flex flex-wrap gap-2 mb-2">
                            <div>Status:</div>
                            {tenant.status==="active" &&
                                <Badge className="" color="green" icon={LuPower}>Activo</Badge>
                            }
                            {tenant.status==="inactive" &&
                                <Badge className="" color="red" icon={LuPowerOff}>
                                    Inactive
                                </Badge>
                            }
                            {tenant.status==="suspended" &&
                                <Badge className="" icon={LuClock3}>
                                    Suspended
                                </Badge>
                            }
                        </div>
                        <div className="flex flex-wrap gap-2 mb-2">
                            <div>Requets:</div>
                            {tenant.request==="approved" &&
                                <Badge className="" color="green" icon={LuPower}>Approved</Badge>

                            }
                            {tenant.request==="in progress" &&
                                <Badge className="" icon={LuClock3}>
                                    In progress
                                </Badge>
                            }
                            {tenant.request==="rejected" &&
                                <Badge className="" color="red" icon={LuX}>
                                    Rejected
                                </Badge>
                            }
                        </div>
                    </div>
                    <div className="flex flex-row flex-nowrap justify-between w-full gap-2">
                        <Button className="w-4/12" color="red" title="Cancel" onClick={() => alert("mostrar modal de confirmación para eliminar el tenant")} >
                            <LuTrash2 className="w-4 h-4" />
                        </Button>
                        <Button className="w-4/12" title="Link" onClick={() => irAlTenant(tenant.domain?.domain!)} disabled={(tenant.request==="in progress" || tenant.request==="rejected" || tenant.status==="inactive")}>
                            <LuLink className="w-4 h-4" />
                        </Button>
                    </div>

                </Card>
            )
        })
    }

    const cardCompanies: ReactNode[] = createCardCompanies(tenants)

    return (
        <>
            <LoaderSpinner status={stateLodaer} />
            <Head>
                <title>{title}</title>
            </Head>

            <HeaderToasts list={Array.from(mapToast.values())}/>

            <Dashboard user_uuid={user_id}>
                <Breadcrumb aria-label="Solid background breadcrumb example" className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-5">
                    <BreadcrumbItem icon={HiHome}>
                        Home
                    </BreadcrumbItem>
                </Breadcrumb>
                {/* <Card className="w-full p-4">
                    <div className=" dark:text-white">Tenant Owner Dashboard Central</div>
                </Card> */}
                <div className="w-full flex flex-row justify-end mb-4">
                    <Button className="" title="Create" onClick={() => alert("mostrar modal para crear tenant")} >
                        <LuPlus className="w-4 h-4" />
                    </Button>
                </div>
                <div className="w-full flex flex-row flex-wrap gap-4">
                    {cardCompanies.length>0 &&
                        cardCompanies.map((card, index) => (
                            <div key={index} className="w-4/12">
                                {card}
                            </div>
                        ))
                    }
                </div>

            </Dashboard>
        </>
    )
}

export default TenantOwnerDashboardCentralPage;
