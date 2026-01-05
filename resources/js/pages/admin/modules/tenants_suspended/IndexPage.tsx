import FiltersModuleTenantSuspendedIndex from "@/components/filters/FiltersModuleTenantSuspendedIndex";
import Dashboard from "@/components/layouts/Dashboard";
import LoaderSpinner from "@/components/LoaderSpinner";
import { Head } from "@inertiajs/react";
import utc from "dayjs/plugin/utc"
import timezone from "dayjs/plugin/timezone";
import dayjs from "dayjs";
import { Avatar, Badge, Breadcrumb, BreadcrumbItem, Button, Card, HelperText, TableCell, TableHead, TableHeadCell, TableRow } from "flowbite-react";
import { FC, ReactNode, useEffect, useState } from "react";
import { HiHome, HiSearch } from "react-icons/hi";
import Tenant from "@/types/models/Tenant";
import { TenantOwner } from "@/types/models/TenantOwner";
import dateUtils from "@/utils/date";
import { useDebounce } from "@/hooks/useDebounce";
import { ToastInterface } from "@/types/ToastInterface";
import {v4 as uuidv4} from "uuid"
import TenantServices from "@/Services/TenantServices";
import { LuDatabase, LuEye } from "react-icons/lu";
import TableComponent from "@/components/ui/TableComponent";
import PaginationNavigationCustom from "@/components/ui/PaginationNavigationCustom";
import HeaderToasts from "@/components/HeaderToasts";
import ModalDetails from "@/components/ui/ModalDetails";

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

    const [cargaInicialCompleta,              setCargaInicialCompleta]                     = useState<boolean>(false);
    const [stateLodaer,                       setStateLodaer]                              = useState<boolean>(false);
    const [statusModalDetails,                setStatusModalDetails]                       = useState<boolean>(false);
    const [stateModalConfirmatedSuspended,    setStateModalConfirmatedSuspended]           = useState<boolean>(false);
    const [uuidTenant,                        setUuidTenant]                               = useState<string>("");

    const [mapToast,                          setMapToast]                                 = useState<Map<string,ToastInterface>>(new Map<string,ToastInterface>())

    const [Tenants,                           setTenants]                                  = useState<Tenant[]>([]);
    const [Tenant,                            setTenant]                                   = useState<Tenant | null>(null);
    const [TenantOwner,                       setTenantOwner]                              = useState<TenantOwner[]>([]);

    const [currentPage,                       setCurrentPage]                              = useState<number>(1);
    const [totalPage,                         settotalPage]                                = useState<number>(0);
    const [lastPage,                          setLastPage]                                 = useState<number>(0);
    const [prePage,                           setPrePage]                                  = useState<number>(50);

    // filtro
    const [search,                            setSearch]                                   = useState<string>("")
    const [filtroDesdeUTC,                    setFiltroDesdeUTC]                           = useState<string>(dateUtils.procesarFechaCompleto(FechaDesde).paraBD)
    const [filtroHastaUTC,                    setFiltroHastaUTC]                           = useState<string>(dateUtils.procesarFechaCompleto(FechaHasta).paraBD)
    const [filtroDesde,                       setFiltroDesde]                              = useState<Date>(FechaDesde)
    const [filtroHasta,                       setFiltroHasta]                              = useState<Date>(FechaHasta)
    const [filtroStatus,                      setFiltroStatus]                             = useState<boolean>(true)
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
    }, [debouncedSearchTerm, filtroDesdeUTC, filtroHastaUTC, currentPage, filtroStatus]);

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

    const filtrarTenant = async (page:number = 1) => {
        const status=(filtroStatus==true)? "suspended": "inactive";
        const request="approved"; // TODO: esto volverlo un fiultro despues
        const respuestaApi= await TenantServices.filtrar(search, filtroDesdeUTC, filtroHastaUTC, status, request, prePage, page);

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

     const openModalDetails = async (uuid: string) => {
        // console.log("uuid => ",uuid)
        setStateLodaer(true)
        const respuestaApi= await TenantServices.consultTenantByUuid(uuid)
        if(respuestaApi.status!=200){
            createToast("failure", "Error", "el Tenant no fue encontrado", <HiSearch/>)
            setStateLodaer(false)
            setTenant(null)
            setTenantOwner([])
            return
        }

        if(respuestaApi.data.data == null){
            setTenant(null)
            setTenantOwner([])
            setStateLodaer(false)
            setStatusModalDetails(true)
            return null
        }

        if(respuestaApi.data.data.owners == null){
            setTenantOwner([])
            setStateLodaer(false)
            setStatusModalDetails(true)
            return null
        }

        console.log("detalle tenant",respuestaApi.data.data)
        console.log("owners tenant",respuestaApi.data.data?.owners)

        setTenant(respuestaApi.data.data)
        setTenantOwner(respuestaApi.data.data?.owners)
        setStateLodaer(false)
        setStatusModalDetails(true)
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
                    <TableCell className="" onClick={() => openModalDetails(item.id)}>
                       {item.name}
                    </TableCell>
                    <TableCell className="" onClick={() => openModalDetails(item.id)}>
                       {item.slug}
                    </TableCell>
                    <TableCell className="" onClick={() => openModalDetails(item.id)}>
                       {item.currency.code}
                    </TableCell>
                    <TableCell className="" onClick={() => openModalDetails(item.id)}>
                       {item.timezone}
                    </TableCell>
                    <TableCell className="" onClick={() => openModalDetails(item.id)}>
                       {dayjs.utc(item.created_at.date).tz(zonaHorariaSistema).format("DD/MM/YYYY")}
                    </TableCell>
                    <TableCell>
                        {/* <Button color="red" title="delete" onClick={() => mostrarModalConfirmatedSuspended(item.id)}>  <LuArchiveRestore className=" w-5 h-5" />  </Button> */}
                    </TableCell>
                </TableRow>
                )
            } )

            return rows
        }


        const buildMovil= (data: Tenant[] = [] ): ReactNode[] => {
            let rows:ReactNode[] = []


            if(data.length==0){
                return [
                     <Card className="mb-4 text-gray-700 dark:text-gray-400">
                        <div className=" flex flex-row justify-center">
                            <LuDatabase className=" inline-block w-6 h-6 mr-2"/> <span className="inline-block"> No Data</span>
                        </div>
                    </Card>
                ]
            }

            rows= data.map<ReactNode>( (item) => {
                return (
                    <Card className="mb-4">
                        <div><span className="font-bold">Name:</span>  <span>{item.name}</span> </div>
                        <div><span className="font-bold">Slug:</span>  <span>{item.slug}</span> </div>
                        <div><span className="font-bold">Currency:</span>  <span>{item.currency.code}</span> </div>
                        <div><span className="font-bold">Timezone:</span>  <span>{item.timezone}</span> </div>
                        <div><span className="font-bold">Created At:</span>  <span>{dayjs.utc(item.created_at.date).tz(zonaHorariaSistema).format("DD/MM/YYYY")}</span> </div>
                        <div className=" flex flex-row flex-wrap gap-4">
                            <div className=" basis-full">
                                <Button title="See" className="w-full" onClick={() => openModalDetails(item.id)}>  <LuEye className=" w-5 h-5" />  </Button>
                            </div>
                            {/* <div className=" basis-full">
                                <Button color="red" className="w-full" title="delete" onClick={() => mostrarModalConfirmatedSuspended(item.id)}>  <LuArchiveRestore className=" w-5 h-5" />  </Button>
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
                <TableHeadCell></TableHeadCell>
              </TableRow>
            </TableHead>
        )

        const tableRowContent= buildRowTable(Tenants)
        const movilRowContent= buildMovil(Tenants)


        const buildRowTableModalDetails= (owners: TenantOwner[] = [] ): ReactNode[] => {
            let rows:ReactNode[] = []

            rows= owners.map<ReactNode>( (item) => {
                return (
                <TableRow className="bg-white dark:border-gray-700 dark:bg-gray-800">
                    <TableCell className="flex flex-row items-center">
                       <Avatar className="cursor-pointer inline-block mr-3" alt="User Avatar" img={item.avatar} rounded/>
                        <div>
                            <div>{item.name}</div>
                        </div>
                    </TableCell>
                    <TableCell >
                       {item.email}
                       <HelperText className="w-full">{item.phone}</HelperText>
                    </TableCell>
                    <TableCell >
                       {(item.is_active==true)?<Badge  size="sm" color="success">Activo</Badge>:<Badge  size="sm" color="failure">Inactivo</Badge>}
                    </TableCell>
                </TableRow>
                )
            } )

            return rows
        }

        const buildMovilModalDetails= (owners: TenantOwner[] = [] ): ReactNode[] => {
            let rows:ReactNode[] = []

            if(owners.length==0){
                return [
                     <Card className="mb-4 text-gray-700 dark:text-gray-400">
                        <div className=" flex flex-row justify-center">
                            <LuDatabase className=" inline-block w-6 h-6 mr-2"/> <span className="inline-block"> No Data</span>
                        </div>
                    </Card>
                ]
            }

            rows= owners.map<ReactNode>( (item) => {
                return (
                    <Card className="mb-4 text-gray-700 dark:text-gray-400">
                        <div className=" flex flex-row justify-center mb-5">
                             <Avatar size="xl" className="cursor-pointer inline-block mr-3" alt="User Avatar" img={item.avatar} rounded bordered  color={(item.is_active==true)?"success":"pink"}/>
                        </div>
                        <div><span className="font-bold">Name:</span>  <span>{item.name}</span> </div>
                        <div><span className="font-bold">Email:</span>  <span>{item.email}</span> </div>
                        <div><span className="font-bold">Phone:</span>  <span>{item.phone}</span> </div>
                        <div><span className="font-bold">Status:</span>  <span>{(item.is_active==true)?<Badge  size="sm" color="success">Activo</Badge>:<Badge  size="sm" color="failure">Inactivo</Badge>}</span> </div>
                    </Card>
                )
            } )

            return rows
        }


        const TableHeadersModalDetails= (
            <TableHead className=" sticky top-0 z-10">
              <TableRow>
                <TableHeadCell>Owner</TableHeadCell>
                <TableHeadCell>Contacto</TableHeadCell>
                <TableHeadCell>Status</TableHeadCell>
              </TableRow>
            </TableHead>
        )

        const onCloseModalConfirmatedSuspended = () => {
            setStateModalConfirmatedSuspended(false)
        }


    return (
        <>

            {/* <LoaderSpinner status={stateLodaer} /> */}
            <Head>
                <title>{title}</title>
            </Head>

            <HeaderToasts list={Array.from(mapToast.values())}/>

              <ModalDetails title="Tenant Details" size="2xl"  onClose={setStatusModalDetails} openModal={statusModalDetails} >
                <h2 className="text-gray-700 dark:text-gray-400 mb-5">Owners</h2>
                <TableComponent className="hidden lg:block " TableHead={TableHeadersModalDetails} TableContent={buildRowTableModalDetails(TenantOwner)} colSpan={3} />
                <div className="block lg:hidden">
                    {buildMovilModalDetails(TenantOwner)}
                </div>
            </ModalDetails>


            <Dashboard user_uuid={user_id}>
                <Breadcrumb aria-label="Solid background breadcrumb example" className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-2">
                    <BreadcrumbItem href={`/backoffice/admin/${user_id}/dashboard`} icon={HiHome}>
                        Home
                    </BreadcrumbItem>
                    <BreadcrumbItem href={`/backoffice/tenant/${user_id}/module`} >Tenants Suspended</BreadcrumbItem>
                </Breadcrumb>

                <FiltersModuleTenantSuspendedIndex
                search={search}
                fechaDesde={filtroDesde}
                fechaHasta={filtroHasta}
                status={filtroStatus}
                onChangeSearch={onChangeSearch}
                onChangeDesde={onChangeDesde}
                onChangeHasta={onChangeHasta}
                onChangeStatus={setFiltroStatus}
                />




                <div className={`overflow-scroll overflow-x-hidden overflow-y-auto`} style={{ height: "calc(100vh - 380px)" }}>
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
