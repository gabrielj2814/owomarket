import { useDashboard } from "@/contexts/DashboardContext";
import { Sidebar, SidebarCollapse, SidebarItem, SidebarItemGroup, SidebarItems } from "flowbite-react";
import { HiArrowSmRight, HiChartPie, HiClock, HiFilter, HiInbox, HiShoppingBag, HiTable, HiUser, HiUsers, HiViewBoards } from "react-icons/hi";
import { LuLogOut, LuSettings, LuShield, LuStore, LuUserPlus, LuUserRoundSearch, LuUsers} from "react-icons/lu";
import { TbBuildingStore, TbWorldWww } from "react-icons/tb";

const SidebarMarketComponent = () => {

    // const {state, actions} = useDashboard()


    const logout = () => {
        // actions.load(true)
        // const respuestaAction = await actions.logout()
        // if (respuestaAction.data.code == 200) {
        //     window.location.href = '/auth/login';
        // }
        // else {
        //     actions.load(false)
        //     alert("Error al hacer logout")
        // }
        alert("Cerrar sesión")
    }



  return (
      <Sidebar aria-label="Default sidebar example" className="hidden lg:block">
         <SidebarItems>
            {/* tenant */}
            {/*  */}
            <SidebarItemGroup>
                <SidebarItem href={`#`} icon={HiFilter}>
                    Filters
                </SidebarItem>
            </SidebarItemGroup>
            <SidebarItemGroup>
                <SidebarItem href={`#`} icon={HiClock}>
                    News
                </SidebarItem>
                <SidebarItem href={`#`} icon={HiClock}>
                    Best Seller
                </SidebarItem>
                <SidebarItem href={`#`} icon={HiClock}>
                    On Sale
                </SidebarItem>
            </SidebarItemGroup>
        </SidebarItems>
    </Sidebar>
  );
}

export default SidebarMarketComponent;
