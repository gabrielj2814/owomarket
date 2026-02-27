import { useDashboard } from "@/contexts/DashboardContext";
import { Sidebar, SidebarCollapse, SidebarItem, SidebarItemGroup, SidebarItems } from "flowbite-react";
import { HiChartPie, HiShoppingBag, HiUser, HiUsers } from "react-icons/hi";
import { LuLogOut, LuSettings, LuStore, LuUserPlus, LuUserRoundSearch, LuUsers} from "react-icons/lu";
import { TbBuildingStore } from "react-icons/tb";


const SidebarDashboardComponent = () => {

    const {state, actions} = useDashboard()


    const logout = async () => {
        actions.load(true)
        const respuestaAction= await actions.logout()
        if(respuestaAction.data.code==200){
            window.location.href = '/auth/login';
        }
        else{
            actions.load(false)
            alert("Error al hacer logout")
        }

    }



  return (
      <Sidebar aria-label="Default sidebar example" className="hidden lg:block">
        {/* rutas centrales admin */}
        { state.authUser.user_type == "super_admin" &&
            <>
                <SidebarItems>
                    <SidebarItemGroup>
                        <SidebarItem href={`/admin/backoffice/${state.authUser.user_id}/dashboard`} icon={HiChartPie}>
                            Dashboard
                        </SidebarItem>

                        <SidebarItem href="#" icon={HiUser} >
                            My Perfil
                        </SidebarItem>
                        <SidebarItem href={`/admin/backoffice/${state.authUser.user_id}/module`} icon={HiUsers} >
                            Admins
                        </SidebarItem>
                        <SidebarCollapse icon={LuStore} label="Tenants">
                            <SidebarItem icon={LuUsers} href={`/tenant/backoffice/${state.authUser.user_id}/module`}>Tenants</SidebarItem>
                            <SidebarItem icon={LuUserPlus} href={`/tenant/backoffice/${state.authUser.user_id}/module/request`}>Request</SidebarItem>
                            <SidebarItem icon={LuUserRoundSearch} href={`/tenant/backoffice/${state.authUser.user_id}/module/suspended`}>Suspended</SidebarItem>
                        </SidebarCollapse>

                        <SidebarItem href="#" icon={LuLogOut} onClick={logout}>
                            Log Out
                        </SidebarItem>
                    </SidebarItemGroup>
                    <SidebarItemGroup>
                        <SidebarItem href="#" icon={TbBuildingStore} >
                            Settings Marketplace
                        </SidebarItem>

                    </SidebarItemGroup>
                </SidebarItems>
            </>
        }
        {/* rutas centrales tenant owner */}
        { state.authUser.user_type == "tenant_owner" &&
            <>
                <SidebarItems>
                    <SidebarItemGroup>
                        <SidebarItem href={`/tenant/owner/backoffice/${state.authUser.user_id}/dashboard`} icon={HiChartPie}>
                            Dashboard
                        </SidebarItem>

                        <SidebarItem href="#" icon={HiUser} >
                            My Perfil
                        </SidebarItem>

                        <SidebarItem href="#" icon={LuLogOut} onClick={logout}>
                            Log Out
                        </SidebarItem>
                    </SidebarItemGroup>
                </SidebarItems>
            </>
        }
         {/* rutas tenant owner */}
        { state.authUser.user_type == "owner" &&
            <>
                <SidebarItems>
                    <SidebarItemGroup>
                        <SidebarItem href={`/tenant/backoffice/${state.authUser.user_id}/dashboard`} icon={HiChartPie}>
                            Dashboard
                        </SidebarItem>
                        <SidebarItem href="#" icon={HiUser} >
                            My Perfil
                        </SidebarItem>
                         <SidebarItem href={`/product/backoffice/${state.authUser.user_id}/module`} icon={HiShoppingBag}>
                            Products
                        </SidebarItem>
                        <SidebarCollapse icon={LuSettings} label="Settings">
                            <SidebarItem icon={HiShoppingBag} href={`#`}>Products</SidebarItem>
                        </SidebarCollapse>

                        <SidebarItem href="#" icon={LuLogOut} onClick={logout}>
                            Log Out
                        </SidebarItem>
                    </SidebarItemGroup>
                </SidebarItems>
            </>
        }
    </Sidebar>
  );
}

export default SidebarDashboardComponent;
