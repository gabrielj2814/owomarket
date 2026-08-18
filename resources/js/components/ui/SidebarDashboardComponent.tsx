import { useDashboard } from "@/contexts/DashboardContext";
import { Sidebar, SidebarCollapse, SidebarItem, SidebarItemGroup, SidebarItems } from "flowbite-react";
import {
    HiAdjustments,
    HiBookmark,
    HiChartPie,
    HiDocumentText,
    HiIdentification,
    HiReceiptTax,
    HiShoppingBag,
    HiTicket,
    HiTruck,
    HiUser,
    HiUsers,
    HiViewGrid,
} from "react-icons/hi";
import {
    LuFolderTree,
    LuLogOut,
    LuReceipt,
    LuSettings,
    LuStore,
    LuUserPlus,
    LuUserRoundSearch,
    LuUsers,
} from "react-icons/lu";
import { TbBuildingStore } from "react-icons/tb";

const SidebarDashboardComponent = () => {
    const { state, actions } = useDashboard();

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
        <Sidebar aria-label="Default sidebar example" className="hidden lg:block">
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
                        <SidebarItem href="#" icon={LuLogOut} onClick={logout}>
                            Log Out
                        </SidebarItem>
                    </SidebarItemGroup>
                    <SidebarItemGroup>
                        <SidebarItem href="#" icon={TbBuildingStore}>
                            Settings Marketplace
                        </SidebarItem>
                    </SidebarItemGroup>
                </SidebarItems>
            )}

            {/* rutas centrales tenant owner */}
            {state.authUser.user_type === "tenant_owner" && (
                <SidebarItems>
                    <SidebarItemGroup>
                        <SidebarItem href={`/tenant/owner/backoffice/${state.authUser.user_id}/dashboard`} icon={HiChartPie}>
                            Dashboard
                        </SidebarItem>
                        <SidebarItem href={`/admin/backoffice/${state.authUser.user_id}/profile`} icon={HiUser}>
                            My Perfil
                        </SidebarItem>
                        <SidebarItem href="#" icon={LuLogOut} onClick={logout}>
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

                        <SidebarItem href="#" icon={LuLogOut} onClick={logout}>
                            Log Out
                        </SidebarItem>
                    </SidebarItemGroup>
                </SidebarItems>
            )}
        </Sidebar>
    );
};

export default SidebarDashboardComponent;
