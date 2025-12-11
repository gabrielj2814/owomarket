import Dashboard from "@/components/layouts/Dashboard"
import { Avatar, Badge, Breadcrumb, BreadcrumbItem, Button, TableCell, TableHead, TableHeadCell, TableRow, ToggleSwitch } from "flowbite-react"
import { FC, ReactNode, useEffect, useState } from "react";
import { Head } from "@inertiajs/react";
import { HiHome, HiPlus } from "react-icons/hi";
import LoaderSpinner from "@/components/LoaderSpinner";
import HeaderToasts from "@/components/HeaderToasts";
import { ToastInterface } from "@/types/ToastInterface";
import {v4 as uuidv4} from "uuid"
import AdminServices from "@/Services/AdminServices";
import TableComponent from "@/components/ui/TableComponent";
import { Admin } from "@/types/models/Admin";
import { LuCheck, LuPencil, LuTrash2, LuTriangleAlert } from "react-icons/lu";
import dayjs from "dayjs"
import utc from "dayjs/plugin/utc"
import timezone from "dayjs/plugin/timezone"
import ModalAlertConfirmation from "@/components/ui/ModalAlertConfirmation";
import { useDebounce } from "@/hooks/useDebounce";
import PaginationNavigationCustom from "@/components/ui/PaginationNavigationCustom";
import FiltersModuleAdminIndex from "@/components/filters/FiltersModuleAdminIndex";
import dateUtils from "@/utils/date";


interface IndexPageProps {
    title?:          string;
    user_id:         string;
    type?:           string;
    titleToast?:     string;
    message?:        string;
}


const IndexPage: FC<IndexPageProps> = ({ title = "Nuevo Modulo OwOMarket", user_id, type=null, message=null, titleToast=null }) => {

    const zonaHorariaSistema=import.meta.env.TIME_ZONE_SISTEMA
    const fechasDelMesActual= dateUtils.getFirstAndLastDayOfCurrentMonth()

    const FechaDesde=new Date(fechasDelMesActual.firstDay.toString());
    const FechaHasta=new Date(fechasDelMesActual.lastDay.toString());

    dayjs.extend(utc);
    dayjs.extend(timezone);

    const [cargaInicialCompleta,     setCargaInicialCompleta]         = useState(false);
    const [stateLodaer,              setStateLodaer]                  = useState(false);
    const [stateModalDelete,         setStateModalDelete]             = useState(false);

    const [mapToast,                 setMapToast]                     = useState<Map<string,ToastInterface>>(new Map<string,ToastInterface>())

    const [admins,                   setAdmins]                       = useState<Admin[]>([]);

    const [uuidAdminDelete,          setUuidAdminDelete]              = useState<string>();
    const [currentPage,              setCurrentPage]                  = useState<number>(1);
    const [totalPage,                settotalPage]                    = useState<number>(0);
    const [lastPage,                 setLastPage]                     = useState<number>(0);
    const [prePage,                  setPrePage]                      = useState<number>(50);

    // filtro
    const [status,                   setStatus]                       = useState<boolean>(true)
    const [search,                   setSearch]                       = useState<string>("")
    const [filtroDesdeUTC,           setFiltroDesdeUTC]               = useState<string>(dateUtils.procesarFechaCompleto(FechaDesde).paraBD)
    const [filtroHastaUTC,           setFiltroHastaUTC]               = useState<string>(dateUtils.procesarFechaCompleto(FechaHasta).paraBD)
    const [filtroDesde,              setFiltroDesde]                  = useState<Date>(FechaDesde)
    const [filtroHasta,              setFiltroHasta]                  = useState<Date>(FechaHasta)
    const debouncedSearchTerm = useDebounce(search, 500);




    const irHaFormularioDeCrear = () => {
        setStateLodaer(true)
        window.location.href=`/backoffice/admin/${user_id}/module/admin/record`
    }

    useEffect(() => {
        if(type!=null && titleToast!=null && message!=null){
            createToast(type, titleToast, message, <HiHome/>)
        }
        const incializar= async () => {
            setStateLodaer(true)
            await filtrarAdmins(currentPage);
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
                await filtrarAdmins(currentPage);
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
        await filtrarAdmins(1);
        setStateLodaer(false)
    }

    const filtrarAdmins = async (page:number = 1) => {
        const respuestaApi= await AdminServices.filtrar(search,filtroDesdeUTC, filtroHastaUTC, status, prePage, page);

        if(respuestaApi.data.code!=200){
            return
        }
        let data= (respuestaApi.data.data!=null)? respuestaApi.data.data: []
        let last= (respuestaApi.data.pagination!=null)? respuestaApi.data.pagination.last_page: 0
        let pre= (respuestaApi.data.pagination!=null)? respuestaApi.data.pagination.per_page: 0
        let total= (respuestaApi.data.pagination!=null)? respuestaApi.data.pagination.total: 0
        setAdmins(data)
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

    const verRegistro = () => {
        alert("ula")
    }

    const irAhFormularioEdit = (uuid: string) => {
        setStateLodaer(true)
        window.location.href=`/backoffice/admin/${user_id}/module/admin/record/${uuid}`
    }

    const mostrarModalDelete= (uuid: string) => {
        setUuidAdminDelete(uuid)
        setStateModalDelete(true)
    }

    const cerrarModalDelete= () => {
        setStateModalDelete(false)
    }

    const eliminar = async () => {
        if(uuidAdminDelete==null){
            return
        }
        setStateLodaer(true)

        let respuestaApi = await AdminServices.delete(uuidAdminDelete)
        console.log("respuesta api => ",respuestaApi)
        if(respuestaApi.status!=200){
            createToast("failure", `Error: ${respuestaApi.status}`, respuestaApi.response?.data.message , <LuTriangleAlert/>)
            setStateLodaer(false)
            cerrarModalDelete()
            return
        }

        createToast("success", `Operation Complete`, undefined , <LuCheck/>)
        // setStateLodaer(false)
        cerrarModalDelete()
        setCurrentPage(1)
        actualizarTabla()
    }

    const actualizarEstadoUsuario= async (id: string, statusAdmin: boolean) => {
        // console.log("id => ",id)
        // console.log("statusAdmin => ",statusAdmin)
        setStateLodaer(true)
        const respuestaApi= await AdminServices.changeStatu(id, statusAdmin)
        if(respuestaApi.status!=200){
            createToast("failure", `Error: ${respuestaApi.status}`, respuestaApi.response?.data.message , <LuTriangleAlert/>)
            setStateLodaer(false)
            return
        }
        createToast("success", `Change Statu Successfully`, undefined , <LuCheck/>)
        actualizarTabla()
    }

    const buildRowTable= (data: Admin[] = [] ): ReactNode[] => {
        let rows:ReactNode[] = []

        rows= data.map<ReactNode>( (item) => {
            return (
            <TableRow className="bg-white dark:border-gray-700 dark:bg-gray-800">
                <TableCell onClick={verRegistro} className="flex flex-row items-center">
                    <Avatar className="cursor-pointer inline-block mr-3" alt="User Avatar" img={item.avatar} rounded/>
                    {item.name}
                </TableCell>
                <TableCell onClick={verRegistro} > {item.email} </TableCell>
                <TableCell onClick={verRegistro} > {item.phone} </TableCell>
                <TableCell >
                    <ToggleSwitch checked={item.is_active} label="Activo" onChange={(statusAdmin: boolean) =>  actualizarEstadoUsuario(item.id, statusAdmin)}  />
                    {/* {(item.is_active==true)?<Badge  size="sm" color="success">Activo</Badge>:<Badge  size="sm" color="failure">Inactivo</Badge>}  */}
                </TableCell>
                <TableCell onClick={verRegistro} > {dayjs.utc(item.created_at.date).tz(zonaHorariaSistema).format("DD/MM/YYYY hh:mm:ss A")} </TableCell>
                <TableCell onClick={verRegistro} > {dayjs.utc(item.updated_at.date).tz(zonaHorariaSistema).format("DD/MM/YYYY hh:mm:ss A")} </TableCell>
                <TableCell> <Button color="yellow" title="edit" onClick={() => irAhFormularioEdit(item.id)}> <LuPencil className=" w-5 h-5"/> </Button> </TableCell>
                <TableCell> <Button color="red" title="delete" onClick={() => mostrarModalDelete(item.id)}>  <LuTrash2 className=" w-5 h-5" />  </Button>  </TableCell>
            </TableRow>
            )
        } )

        return rows
    }


    const TableHeaders= (
        <TableHead className=" sticky top-0 z-10">
          <TableRow>
            <TableHeadCell>Name</TableHeadCell>
            <TableHeadCell>Email</TableHeadCell>
            <TableHeadCell>Phone</TableHeadCell>
            <TableHeadCell>Statu</TableHeadCell>
            <TableHeadCell>Created At</TableHeadCell>
            <TableHeadCell>Update At</TableHeadCell>
            <TableHeadCell></TableHeadCell>
            <TableHeadCell></TableHeadCell>
          </TableRow>
        </TableHead>
    )

    const tableRowContent= buildRowTable(admins)


    return (
        <>
            <LoaderSpinner status={stateLodaer} />
            <Head>
                <title>{title}</title>
            </Head>

            <HeaderToasts list={Array.from(mapToast.values())}/>

            <ModalAlertConfirmation
            openModal={stateModalDelete}
            size="md"
            icon={<LuTrash2 className="mx-auto mb-4 h-14 w-14 text-gray-400 dark:text-gray-200"  />}
            text="Are you sure you want to delete?"
            buttonTextCancel="No, I want not cancel"
            buttonTextAction="yes, I want delete"
            onClose={cerrarModalDelete}
            onClickAction={eliminar}
            colorButtonCancel="alternative"
            colorButtonAction="red"
            />

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

                <FiltersModuleAdminIndex
                search={search}
                status={status}
                fechaDesde={filtroDesde}
                fechaHasta={filtroHasta}
                onChangeSearch={onChangeSearch}
                onChangeDesde={onChangeDesde}
                onChangeHasta={onChangeHasta}
                onChangeStatus={setStatus}
                />


                <div className={`overflow-scroll overflow-x-hidden overflow-y-auto`} style={{ height: "calc(100vh - 470px)" }}>
                    <TableComponent TableHead={TableHeaders} TableContent={tableRowContent} colSpan={8} />
                </div>

                <PaginationNavigationCustom className="pt-5" currentPageFather={currentPage} itemsPerPageFather={prePage} totalItemsFather={totalPage} onPageChangeFather={onPageChange}/>



            </Dashboard>
        </>
    )
}

export default IndexPage
