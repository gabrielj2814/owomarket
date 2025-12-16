import dayjs from "dayjs"
import {v4 as uuidv4} from "uuid"
import utc from "dayjs/plugin/utc"
import timezone from "dayjs/plugin/timezone";
import { FC, ReactNode, useEffect, useState } from "react";
import dateUtils from "@/utils/date";
import { ToastInterface } from "@/types/ToastInterface";
import LoaderSpinner from "@/components/LoaderSpinner";
import { Head } from "@inertiajs/react";
import HeaderToasts from "@/components/HeaderToasts";
import Dashboard from "@/components/layouts/Dashboard";
import { Breadcrumb, BreadcrumbItem, Card, TableCell, TableHead, TableHeadCell, TableRow } from "flowbite-react";
import { HiHome } from "react-icons/hi";
import FiltersModuleTenantIndex from "@/components/filters/FiltersModuleTenantIndex";
import { useDebounce } from "@/hooks/useDebounce";
import Tenant from "@/types/models/Tenant";
import TenantServices from "@/Services/TenantServices";
import TableComponent from "@/components/ui/TableComponent";
import PaginationNavigationCustom from "@/components/ui/PaginationNavigationCustom";

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

    const [Tenants,                  setTenants]                       = useState<Tenant[]>([]);

    const [currentPage,              setCurrentPage]                  = useState<number>(1);
    const [totalPage,                settotalPage]                    = useState<number>(0);
    const [lastPage,                 setLastPage]                     = useState<number>(0);
    const [prePage,                  setPrePage]                      = useState<number>(50);

    // filtro
    const [search,                   setSearch]                       = useState<string>("")
    const [filtroDesdeUTC,           setFiltroDesdeUTC]               = useState<string>(dateUtils.procesarFechaCompleto(FechaDesde).paraBD)
    const [filtroHastaUTC,           setFiltroHastaUTC]               = useState<string>(dateUtils.procesarFechaCompleto(FechaHasta).paraBD)
    const [filtroDesde,              setFiltroDesde]                  = useState<Date>(FechaDesde)
    const [filtroHasta,              setFiltroHasta]                  = useState<Date>(FechaHasta)
    const debouncedSearchTerm = useDebounce(search, 500);


    useEffect(() => {
        if(type!=null && titleToast!=null && message!=null){
            createToast(type, titleToast, message, <HiHome/>)
        }
        const incializar= async () => {
            setStateLodaer(true)
            await filtrarTenant(currentPage);
            setStateLodaer(false)
            setCargaInicialCompleta(true)
        }
        incializar()
    },[])

    // este useeffect es para el filftro de buscador
    useEffect(() => {
        if(cargaInicialCompleta){
            const inicializar = async () => {
                setStateLodaer(true)
                await filtrarTenant(currentPage);
                setStateLodaer(false)
            }
            inicializar()
        }
    }, [debouncedSearchTerm, status, filtroDesdeUTC, filtroHastaUTC, currentPage]);

    const onPageChange = (page: number) => {
        setCurrentPage(page)
    };

    const onChangeSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSearch(e.target.value)
    }

    const onChangeDesde= (date: Date | null) => {
        if(date==null){
            return
        }

        let fecha=dateUtils.procesarFechaCompleto(date)
        console.log("fecha desde local => ",fecha)
        setFiltroDesdeUTC(fecha.paraBD)
        setFiltroDesde(date)
    }

    const onChangeHasta= (date: Date | null) => {
        if(date==null){
            return
        }
        let fecha=dateUtils.procesarFechaCompleto(date)
        setFiltroHastaUTC(fecha.paraBD)
        setFiltroHasta(date)
    }


    const actualizarTabla= async () => {
        setStateLodaer(true)
        await filtrarTenant(1);
        setStateLodaer(false)
    }

    const filtrarTenant = async (page:number = 1) => {
        const respuestaApi= await TenantServices.filtrar(search,filtroDesdeUTC, filtroHastaUTC, prePage, page);

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

    const buildRowTable= (data: Tenant[] = [] ): ReactNode[] => {
        let rows:ReactNode[] = []

        rows= data.map<ReactNode>( (item) => {
            return (
            <TableRow className="bg-white dark:border-gray-700 dark:bg-gray-800">
                <TableCell className="">
                   {item.name}
                </TableCell>
                <TableCell className="">
                   {item.slug}
                </TableCell>
                <TableCell className="">
                   {item.currency.code}
                </TableCell>
                <TableCell className="">
                   {item.timezone}
                </TableCell>
                <TableCell className="">
                   {dayjs.utc(item.created_at.date).tz(zonaHorariaSistema).format("DD/MM/YYYY")}
                </TableCell>
                <TableCell className="">
                   {item.status}
                </TableCell>
            </TableRow>
            )
        } )

        return rows
    }


    const buildMovil= (data: Tenant[] = [] ): ReactNode[] => {
        let rows:ReactNode[] = []

        rows= data.map<ReactNode>( (item) => {
            return (
                <Card className="mb-4">
                    <div><span className="font-bold">Name:</span>  <span>{item.name}</span> </div>
                    <div><span className="font-bold">Slug:</span>  <span>{item.slug}</span> </div>
                    <div><span className="font-bold">Currency:</span>  <span>{item.currency.code}</span> </div>
                    <div><span className="font-bold">Timezone:</span>  <span>{item.timezone}</span> </div>
                    <div><span className="font-bold">Created At:</span>  <span>{dayjs.utc(item.created_at.date).tz(zonaHorariaSistema).format("DD/MM/YYYY")}</span> </div>
                    <div><span className="font-bold">Status:</span>  <span>{item.status}</span> </div>
                    {/* <ToggleSwitch checked={item.is_active} label="Activo" onChange={(statusAdmin: boolean) =>  actualizarEstadoUsuario(item.id, statusAdmin)}  /> */}
                    <div className=" flex flex-row gap-4">
                    {/* <div className=" basis-6/12">
                        <Button title="See" className="w-full" onClick={() => verRegistro()}>  <LuEye className=" w-5 h-5" />  </Button>
                    </div> */}
                    </div>

                </Card>
            )
        } )

        return rows
    }



    const TableHeaders= (
            <TableHead className=" sticky top-0 z-10">
              <TableRow>
                <TableHeadCell>Name</TableHeadCell>
                <TableHeadCell>Slug</TableHeadCell>
                <TableHeadCell>Currency</TableHeadCell>
                <TableHeadCell>Timezone</TableHeadCell>
                <TableHeadCell>Created At</TableHeadCell>
                <TableHeadCell>Status</TableHeadCell>
              </TableRow>
            </TableHead>
        )

    const tableRowContent= buildRowTable(Tenants)
    const movilRowContent= buildMovil(Tenants)


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
                <FiltersModuleTenantIndex
                search={search}
                fechaDesde={filtroDesde}
                fechaHasta={filtroHasta}
                onChangeSearch={onChangeSearch}
                onChangeDesde={onChangeDesde}
                onChangeHasta={onChangeHasta}
                />


                <div className={`overflow-scroll overflow-x-hidden overflow-y-auto`} style={{ height: "calc(100vh - 340px)" }}>
                    <TableComponent className="hidden lg:block" TableHead={TableHeaders} TableContent={tableRowContent} colSpan={6} />
                    <div className="block lg:hidden">
                        {movilRowContent}
                    </div>
                </div>

                <PaginationNavigationCustom className="pt-5" currentPageFather={currentPage} itemsPerPageFather={prePage} totalItemsFather={totalPage} onPageChangeFather={onPageChange}/>




            </Dashboard>

        </>
    )

}

export default IndexPage
