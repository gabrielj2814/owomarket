import { useDashboard } from "@/contexts/DashboardContext";
import {
    Avatar,
    Button,
    Drawer,
    DrawerHeader,
    DrawerItems,
    Dropdown,
    DropdownDivider,
    DropdownHeader,
    DropdownItem,
    Navbar,
    NavbarBrand,
    Sidebar,
    SidebarCollapse,
    SidebarItem,
    SidebarItemGroup,
    SidebarItems,
    TextInput,
} from "flowbite-react";
import { useState } from "react";
import {
    HiAdjustments,
    HiBookmark,
    HiChartPie,
    HiDocumentText,
    HiIdentification,
    HiLogout,
    HiReceiptTax,
    HiSearch,
    HiShoppingBag,
    HiTicket,
    HiTruck,
    HiUser,
    HiUsers,
    HiViewGrid,
} from "react-icons/hi";
import {
    LuBell,
    LuFolderTree,
    LuMenu,
    LuReceipt,
    LuSettings,
    LuStore,
    LuUserPlus,
    LuUserRoundSearch,
    LuUsers,
} from "react-icons/lu";
import { TbBuildingStore } from "react-icons/tb";

const NavBarMovilDashboardComponent = () => {
    const { state, actions } = useDashboard();
    const [isOpen, setIsOpen] = useState(false);

    const handleClose = () => setIsOpen(false);

    const logout = async () => {
        actions.load(true);
        const respuestaAction = await actions.logout();
        if (respuestaAction.data.code === 200) {
            window.location.href = "/auth/login";
        } else {
            actions.load(false);
            alert("Error al hacer logout");
        }
    };

    return (
        <>
            <Navbar fluid rounded>
                <NavbarBrand href="#">
                    <img src="/favicon.svg" className="mr-3 h-6 sm:h-9" alt="Flowbite React Logo" />
                    <span className="self-center whitespace-nowrap text-xl font-semibold dark:text-white">OwOMarket</span>
                </NavbarBrand>
                <div className="flex md:order-2">
                    <Dropdown
                        arrowIcon={false}
                        inline
                        label={
                            <>
                                <LuBell className="w-9 h-9 mr-2 lg:mr-5 block cursor-pointer rounded-lg p-2 text-base font-normal text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" />
                                <Avatar className="cursor-pointer" alt="User Avatar" img={state.authUser.user_avatar} rounded />
                            </>
                        }
                    >
                        <DropdownHeader>
                            <span className="block text-sm">{state.authUser.user_name}</span>
                            <span className="block truncate text-sm font-medium">{state.authUser.user_email}</span>
                        </DropdownHeader>
                        <DropdownItem href={`/admin/backoffice/${state.authUser.user_id}/profile`}>Perfil</DropdownItem>
                        <DropdownDivider />
                        <DropdownItem onClick={logout}>Sign out</DropdownItem>
                    </Dropdown>
                    <Button className="block ml-3 lg:hidden" color="light" onClick={() => setIsOpen(true)}>
                        <LuMenu className="h-6 w-6" />
                    </Button>
                </div>
            </Navbar>
            <Drawer open={isOpen} onClose={handleClose}>
                <DrawerHeader title="MENÚ" titleIcon={() => <></>} />
                <DrawerItems>
                    <Sidebar aria-label="Sidebar with multi-level dropdown" className="[&>div]:bg-transparent [&>div]:p-0">
                        <div className="flex h-full flex-col justify-between py-2">
                            <div>
                                <form className="pb-3 md:hidden">
                                    <TextInput icon={HiSearch} type="search" placeholder="Search" required size={32} />
                                </form>

                                {/* rutas centrales admin */}
                                {state.authUser.user_type === "super_admin" && (
                                    <SidebarItems>
                                        <SidebarItemGroup>
                                            <SidebarItem href={`/admin/backoffice/${state.authUser.user_id}/dashboard`} icon={HiChartPie}>
                                                Dashboard
                                            </SidebarItem>
                                            <SidebarItem href={`/admin/backoffice/${state.authUser.user_id}/profile`} icon={HiUser}>
                                                My Perfil
                                            </SidebarItem>
                                            <SidebarItem href={`/admin/backoffice/${state.authUser.user_id}/module`} icon={HiUsers}>
                                                Admins
                                            </SidebarItem>
                                            <SidebarCollapse icon={LuStore} label="Tenants">
                                                <SidebarItem icon={LuUsers} href={`/tenant/backoffice/${state.authUser.user_id}/module`}>
                                                    Tenants
                                                </SidebarItem>
                                                <SidebarItem icon={LuUserPlus} href={`/tenant/backoffice/${state.authUser.user_id}/module/request`}>
                                                    Request
                                                </SidebarItem>
                                                <SidebarItem icon={LuUserRoundSearch} href={`/tenant/backoffice/${state.authUser.user_id}/module/suspended`}>
                                                    Suspended
                                                </SidebarItem>
                                            </SidebarCollapse>
                                            <SidebarItem icon={HiLogout} onClick={logout}>
                                                Log Out
                                            </SidebarItem>
                                        </SidebarItemGroup>
                                        <SidebarItemGroup>
                                            <SidebarItem icon={TbBuildingStore}>Settings Marketplace</SidebarItem>
                                        </SidebarItemGroup>
                                    </SidebarItems>
                                )}

                                {/* rutas central tenant owner */}
                                {state.authUser.user_type === "tenant_owner" && (
                                    <SidebarItems>
                                        <SidebarItemGroup>
                                            <SidebarItem href={`/tenant/owner/backoffice/${state.authUser.user_id}/dashboard`} icon={HiChartPie}>
                                                Dashboard
                                            </SidebarItem>
                                            <SidebarItem href={`/admin/backoffice/${state.authUser.user_id}/profile`} icon={HiUser}>
                                                My Perfil
                                            </SidebarItem>
                                            <SidebarItem icon={HiLogout} onClick={logout}>
                                                Log Out
                                            </SidebarItem>
                                        </SidebarItemGroup>
                                    </SidebarItems>
                                )}

                                {/* rutas tenant owner & tenant admin */}
                                {(state.authUser.user_type === "owner" || state.authUser.user_type === "admin") && (
                                    <SidebarItems>
                                        <SidebarItemGroup>
                                            <SidebarItem href={`/tenant/backoffice/${state.authUser.user_id}/dashboard`} icon={HiChartPie}>
                                                Dashboard
                                            </SidebarItem>
                                            <SidebarItem href={`/admin/backoffice/${state.authUser.user_id}/profile`} icon={HiUser}>
                                                My Perfil
                                            </SidebarItem>

                                            <SidebarCollapse icon={LuFolderTree} label="Catálogo">
                                                <SidebarItem icon={HiShoppingBag} href={`/product/backoffice/${state.authUser.user_id}/module`}>
                                                    Productos
                                                </SidebarItem>
                                                <SidebarItem icon={HiViewGrid} href={`/category/backoffice/${state.authUser.user_id}/module`}>
                                                    Categorías
                                                </SidebarItem>
                                                <SidebarItem icon={HiBookmark} href={`/brand/backoffice/${state.authUser.user_id}/module`}>
                                                    Marcas
                                                </SidebarItem>
                                                <SidebarItem icon={HiAdjustments} href={`/attribute/backoffice/${state.authUser.user_id}/module`}>
                                                    Atributos
                                                </SidebarItem>
                                                <SidebarItem icon={HiTicket} href={`/coupon/backoffice/${state.authUser.user_id}/module`}>
                                                    Cupones
                                                </SidebarItem>
                                            </SidebarCollapse>

                                            <SidebarCollapse icon={LuReceipt} label="Facturación">
                                                <SidebarItem icon={HiDocumentText} href={`/billing/backoffice/${state.authUser.user_id}/module`}>
                                                    Facturas
                                                </SidebarItem>
                                                <SidebarItem icon={HiIdentification} href={`/billing/backoffice/${state.authUser.user_id}/settings`}>
                                                    Datos Fiscales
                                                </SidebarItem>
                                            </SidebarCollapse>

                                            <SidebarCollapse icon={LuSettings} label="Configuración">
                                                <SidebarItem icon={HiReceiptTax} href={`/tax/backoffice/${state.authUser.user_id}/module`}>
                                                    Impuestos
                                                </SidebarItem>
                                                <SidebarItem icon={HiTruck} href={`/shipping/backoffice/${state.authUser.user_id}/module`}>
                                                    Envíos
                                                </SidebarItem>
                                            </SidebarCollapse>

                                            <SidebarItem icon={HiLogout} onClick={logout}>
                                                Log Out
                                            </SidebarItem>
                                        </SidebarItemGroup>
                                    </SidebarItems>
                                )}
                            </div>
                        </div>
                    </Sidebar>
                </DrawerItems>
            </Drawer>
        </>
    );
};

export default NavBarMovilDashboardComponent;
