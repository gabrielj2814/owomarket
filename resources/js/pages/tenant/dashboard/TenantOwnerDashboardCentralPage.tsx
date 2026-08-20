import dayjs from "dayjs";
import { v4 as uuidv4 } from "uuid";
import dateUtils from "@/utils/date";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import { Badge, Breadcrumb, BreadcrumbItem, Button, Card, HelperText, Label, Modal, ModalBody, ModalFooter, ModalHeader, TextInput } from "flowbite-react";
import { FC, ReactNode, useEffect, useState } from "react";
import { HiCheck, HiClock, HiDotsVertical, HiHome, HiX } from "react-icons/hi";
import Tenant from "@/types/models/Tenant";
import { ToastInterface } from "@/types/ToastInterface";
import LoaderSpinner from "@/components/LoaderSpinner";
import HeaderToasts from "@/components/HeaderToasts";
import TenantServices from "@/Services/TenantServices";
import TenantOwnerNavTabs from "@/components/tenant/TenantOwnerNavTabs";
import {
    LuClock3,
    LuLink,
    LuPlus,
    LuPower,
    LuPowerOff,
    LuStore,
    LuTrash2,
    LuX,
    LuExternalLink,
    LuKeyRound,
} from "react-icons/lu";
import {
    HiOutlineBuildingStorefront,
    HiOutlineCube,
    HiOutlineShieldCheck,
    HiOutlineArrowPath,
} from "react-icons/hi2";
import axios from "axios";

interface TenantOwnerDashboardCentralPageProps {
    title?: string;
    user_id: string;
}

const TenantOwnerDashboardCentralPage: FC<TenantOwnerDashboardCentralPageProps> = ({ title, user_id }) => {
    dayjs.extend(utc);
    dayjs.extend(timezone);

    const [modalCreatedTenant, setModalCreatedTenant] = useState<boolean>(false);
    const [storeName, setStoreName] = useState<string>("");
    const [errorStoreName, setErrorStoreName] = useState<string>("");
    const [stateLodaer, setStateLodaer] = useState<boolean>(false);
    const [ssoLoadingMap, setSsoLoadingMap] = useState<Record<string, boolean>>({});

    const [tenants, setTenants] = useState<Tenant[]>([]);
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [mapToast, setMapToast] = useState<Map<string, ToastInterface>>(new Map<string, ToastInterface>());

    useEffect(() => {
        const inicializar = async () => {
            setStateLodaer(true);
            await consultOwnerCompanies(currentPage);
            setStateLodaer(false);
        };
        inicializar();
    }, []);

    const actualizarLista = async (page: number) => {
        setCurrentPage(page);
        setStateLodaer(true);
        await consultOwnerCompanies(page);
        setStateLodaer(false);
    };

    const consultOwnerCompanies = async (page: number = 1) => {
        const respuestaApi = await TenantServices.consultMyCompanies(user_id, page, 50);

        if (respuestaApi.data.code != 200) {
            return;
        }

        const data = (respuestaApi.data.data != null) ? respuestaApi.data.data : [];
        setTenants(data);
    };

    const createToast = (type: string, title: string, message?: string, icon?: ReactNode) => {
        const uuid = uuidv4();
        const dataToast: ToastInterface = {
            type,
            title,
            message,
            icon,
        };

        setMapToast(prevMap => {
            const newMap = new Map(prevMap);
            newMap.set(uuid, dataToast);
            return newMap;
        });

        setTimeout(() => {
            setMapToast(prevMap => {
                const newMap = new Map(prevMap);
                newMap.delete(uuid);
                return newMap;
            });
        }, 5000);
    };

    const sendRequestCreateTenant = async () => {
        if (storeName.trim() === "") {
            createToast("error", "El nombre de la tienda es obligatorio", undefined, <HiClock />);
            setErrorStoreName("El nombre de la tienda es obligatorio");
            return;
        }
        setErrorStoreName("");
        setStateLodaer(true);
        const respuestaApi = await TenantServices.sendRequestCreateTenant(user_id, storeName);

        if (respuestaApi.data.code != 200) {
            createToast("failure", `Error: ${respuestaApi.data.message}`, undefined, <HiX />);
            setErrorStoreName(respuestaApi.data.message || "Error");
            setStateLodaer(false);
            return;
        }

        await actualizarLista(1);
        setStoreName("");
        setModalCreatedTenant(false);
        createToast("success", "Solicitud de tienda enviada exitosamente", undefined, <HiCheck />);
    };

    const handleSSOAccess = async (tenantId: string) => {
        setSsoLoadingMap(prev => ({ ...prev, [tenantId]: true }));
        try {
            const response = await axios.post('/tenant/owner/api/sso-token', {
                user_id,
                tenant_id: tenantId,
            });

            if (response.data?.status === 'success') {
                const redirectUrl = response.data.data.redirect_url;
                window.open(redirectUrl, '_blank');
                createToast("success", "Acceso SSO concedido", "Abriendo sesión en el backoffice de la tienda", <LuKeyRound />);
            }
        } catch (error: any) {
            createToast("failure", error?.response?.data?.message || "Error al generar sesión SSO", undefined, <HiX />);
        } finally {
            setSsoLoadingMap(prev => ({ ...prev, [tenantId]: false }));
        }
    };

    const activeCount = tenants.filter(t => t.status === "active").length;
    const inProgressCount = tenants.filter(t => t.request === "in progress").length;

    return (
        <>
            <LoaderSpinner status={stateLodaer} />
            <Head>
                <title>{title || "Dashboard Central Tenant Owner - OwOMarket"}</title>
            </Head>

            <Modal show={modalCreatedTenant} onClose={() => setModalCreatedTenant(false)}>
                <ModalHeader>Crear Nueva Tienda / Sucursal</ModalHeader>
                <form onSubmit={(e) => { e.preventDefault(); sendRequestCreateTenant(); }}>
                    <ModalBody>
                        <div className="w-full space-y-3">
                            <div>
                                <Label htmlFor="store_name">Nombre Comercial de la Tienda</Label>
                                <TextInput
                                    id="store_name"
                                    type="text"
                                    icon={LuStore}
                                    placeholder="Ej: Tecno Store Las Mercedes"
                                    required
                                    value={storeName}
                                    onChange={(e) => setStoreName(e.target.value)}
                                />
                                {errorStoreName.trim() !== "" && (
                                    <HelperText color="failure">
                                        <span className="font-medium">{errorStoreName}</span>
                                    </HelperText>
                                )}
                            </div>
                            <p className="text-xs text-gray-500">
                                Tu tienda se creará de inmediato con subdominio dedicado y podrás acceder a su backoffice con 1 clic.
                            </p>
                        </div>
                    </ModalBody>
                    <ModalFooter>
                        <Button color="blue" type="submit">Enviar Solicitud</Button>
                        <Button color="gray" onClick={() => setModalCreatedTenant(false)}>
                            Cancelar
                        </Button>
                    </ModalFooter>
                </form>
            </Modal>

            <HeaderToasts list={Array.from(mapToast.values())} />

            <Dashboard user_uuid={user_id}>
                <div className="p-4 sm:p-6 space-y-6">
                    <TenantOwnerNavTabs userId={user_id} activeTab="dashboard" />

                    {/* Header with Title and Create Button */}
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-sm">
                        <div>
                            <h1 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                                <HiOutlineBuildingStorefront className="w-7 h-7 text-blue-600" />
                                Centro de Mando Multi-Tienda
                            </h1>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Administra todas tus tiendas y franquicias. Accede al backoffice de cada una mediante SSO de 1 clic.
                            </p>
                        </div>

                        <Button
                            color="blue"
                            className="rounded-2xl font-bold shadow-md shadow-blue-500/20 flex items-center gap-2"
                            onClick={() => setModalCreatedTenant(true)}
                        >
                            <LuPlus className="w-5 h-5 mr-1" />
                            <span>Crear Nueva Tienda</span>
                        </Button>
                    </div>

                    {/* KPIs Consolidados */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                <LuStore className="w-6 h-6" />
                            </div>
                            <div>
                                <span className="text-[11px] font-bold uppercase text-gray-400">Total Tiendas</span>
                                <div className="text-2xl font-black text-gray-900 dark:text-white">{tenants.length}</div>
                            </div>
                        </div>

                        <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 flex items-center justify-center shrink-0">
                                <HiOutlineShieldCheck className="w-6 h-6" />
                            </div>
                            <div>
                                <span className="text-[11px] font-bold uppercase text-gray-400">Tiendas Activas</span>
                                <div className="text-2xl font-black text-emerald-600 dark:text-emerald-400">{activeCount}</div>
                            </div>
                        </div>

                        <div className="p-5 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                                <HiOutlineCube className="w-6 h-6" />
                            </div>
                            <div>
                                <span className="text-[11px] font-bold uppercase text-gray-400">En Proceso / Revisión</span>
                                <div className="text-2xl font-black text-purple-600 dark:text-purple-400">{inProgressCount}</div>
                            </div>
                        </div>
                    </div>

                    {/* List of Stores */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {tenants.length === 0 ? (
                            <div className="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-700 text-gray-400 text-xs">
                                No tienes tiendas registradas todavía. ¡Crea tu primera tienda con el botón superior!
                            </div>
                        ) : (
                            tenants.map(tenant => {
                                const domainName = tenant.domain?.domain || `${tenant.slug}.localhost`;
                                const isSsoLoading = !!ssoLoadingMap[tenant.id];

                                return (
                                    <div
                                        key={tenant.id}
                                        className="p-6 rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-5"
                                    >
                                        <div className="space-y-4">
                                            <div className="flex items-center justify-between">
                                                <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-lg flex items-center justify-center shadow-md">
                                                    {tenant.name.substring(0, 2).toUpperCase()}
                                                </div>
                                                <div className="flex items-center gap-1.5">
                                                    {tenant.status === "active" && (
                                                        <span className="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300">
                                                            Activo
                                                        </span>
                                                    )}
                                                    {tenant.status === "inactive" && (
                                                        <span className="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300">
                                                            Inactivo
                                                        </span>
                                                    )}
                                                    {tenant.status === "suspended" && (
                                                        <span className="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                            Suspendido
                                                        </span>
                                                    )}
                                                </div>
                                            </div>

                                            <div>
                                                <h3 className="text-lg font-black text-gray-900 dark:text-white truncate">
                                                    {tenant.name}
                                                </h3>
                                                <a
                                                    href={`http://${domainName}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 mt-0.5"
                                                >
                                                    <span>{domainName}</span>
                                                    <LuExternalLink className="w-3 h-3" />
                                                </a>
                                            </div>
                                        </div>

                                        <div className="pt-4 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                                            <button
                                                onClick={() => handleSSOAccess(tenant.id)}
                                                disabled={isSsoLoading || tenant.status === "inactive"}
                                                className="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2"
                                            >
                                                {isSsoLoading ? (
                                                    <HiOutlineArrowPath className="w-4 h-4 animate-spin" />
                                                ) : (
                                                    <LuKeyRound className="w-4 h-4" />
                                                )}
                                                <span>Acceder al Backoffice (SSO 1-Click)</span>
                                            </button>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>
                </div>
            </Dashboard>
        </>
    );
};

export default TenantOwnerDashboardCentralPage;
