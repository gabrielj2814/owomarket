import { useDashboard } from "@/contexts/DashboardContext";
import { Sidebar, SidebarItem, SidebarItemGroup, SidebarItems } from "flowbite-react";
import { HiArrowSmRight, HiChartPie, HiInbox, HiShoppingBag, HiTable, HiUser, HiViewBoards } from "react-icons/hi";
import { LuLogOut} from "react-icons/lu";

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
            <SidebarItem href="#" icon={HiChartPie}>
                Dashboard
            </SidebarItem>
            <SidebarItem href="#" icon={LuLogOut} onClick={logout}>
                Log Out
            </SidebarItem>
          {/* <SidebarItem href="#" icon={HiViewBoards} label="Pro" labelColor="dark">
            Kanban
          </SidebarItem>
          <SidebarItem href="#" icon={HiInbox} label="3">
            Inbox
          </SidebarItem>
          <SidebarItem href="#" icon={HiUser}>
            Users
          </SidebarItem>
          <SidebarItem href="#" icon={HiShoppingBag}>
            Products
          </SidebarItem>
          <SidebarItem href="#" icon={HiArrowSmRight}>
            Sign In
          </SidebarItem> */}

        </SidebarItemGroup>
      </SidebarItems>
    </Sidebar>
  );
}

export default SidebarDashboardComponent;
