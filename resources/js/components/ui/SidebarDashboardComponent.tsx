import { useDashboard } from "@/contexts/DashboardContext";
import { Sidebar, SidebarCollapse, SidebarItem, SidebarItemGroup, SidebarItems } from "flowbite-react";
import { HiArrowSmRight, HiChartPie, HiInbox, HiShoppingBag, HiTable, HiUser, HiUsers, HiViewBoards } from "react-icons/hi";
import { LuLogOut, LuSettings, LuShield, LuStore, LuUserPlus, LuUserRoundSearch, LuUsers} from "react-icons/lu";
import { TbBuildingStore, TbWorldWww } from "react-icons/tb";

const SidebarDashboardComponent = () => {

    const {state, actions} = useDashboard()


    const logout = async () => {
        actions.load(true)
        const respuestaAction= await actions.logout()
        if(respuestaAction.data.code==200){
            window.location.href = '/auth/login-staff';
        }
        else{
            actions.load(false)
            alert("Error al hacer logout")
        }

    }



  return (
    <Sidebar aria-label="Default sidebar example" className="hidden lg:block">
      <SidebarItems>
        <SidebarItemGroup>
            <SidebarItem href={`/backoffice/admin/${state.authUser.user_id}/dashboard`} icon={HiChartPie}>
                Dashboard
            </SidebarItem>

            <SidebarItem href="#" icon={HiUser} >
                My Perfil
            </SidebarItem>
            <SidebarItem href={`/backoffice/admin/${state.authUser.user_id}/module`} icon={HiUsers} >
                Admins
            </SidebarItem>
            <SidebarCollapse icon={LuStore} label="Tenants">
                <SidebarItem icon={LuUsers} href={`/backoffice/tenant/${state.authUser.user_id}/module`}>Tenants</SidebarItem>
                <SidebarItem icon={LuUserPlus} href="#">Request</SidebarItem>
                <SidebarItem icon={LuUserRoundSearch} href="#">Suspended</SidebarItem>
            </SidebarCollapse>
            {/* <SidebarItem href="#" icon={TbWorldWww} >
                Domains
            </SidebarItem>
            <SidebarItem href="#" icon={LuShield} >
                Security
            </SidebarItem>
            <SidebarItem href="#" icon={LuSettings} >
                Settings
            </SidebarItem> */}

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
    </Sidebar>
  );
}

export default SidebarDashboardComponent;
