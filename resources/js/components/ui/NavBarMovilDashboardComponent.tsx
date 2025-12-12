

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
    SidebarItem,
    SidebarItemGroup,
    SidebarItems,
    TextInput,
} from "flowbite-react";
import { useState } from "react";
import {
    HiChartPie,
    HiLogout,
    HiSearch,
    HiUser,
    HiUsers,
} from "react-icons/hi";
import { LuBell, LuMenu, LuSettings, LuShield, LuStore } from "react-icons/lu";
import { TbBuildingStore, TbWorldWww } from "react-icons/tb";

const NavBarMovilDashboardComponent = () => {

    //

    const { state, actions } = useDashboard()

    const [isOpen, setIsOpen] = useState(false);

    const handleClose = () => setIsOpen(false);

    const logout = async () => {
        actions.load(true)
        const respuestaAction = await actions.logout()
        if (respuestaAction.data.code == 200) {
            window.location.href = '/auth/login-staff';
        }
        else {
            actions.load(false)
            alert("Error al hacer logout")
        }
    }

    return (
        <>

            <Navbar fluid rounded>
                <NavbarBrand href="https://flowbite-react.com">
                    <img src="/favicon.svg" className="mr-3 h-6 sm:h-9" alt="Flowbite React Logo" />
                    {/* <img src={storage.local.get("images/owo_logo.png").url} className="mr-3 h-6 sm:h-15" alt="Flowbite React Logo" /> */}
                    <span className="self-center whitespace-nowrap text-xl font-semibold dark:text-white">OwOMarket</span>
                </NavbarBrand>
                <div className="flex md:order-2">
                    <Dropdown
                        arrowIcon={false}
                        inline
                        label={
                            // https://i.pinimg.com/originals/b0/ce/76/b0ce76f4cdb95ef13afa21a889adfc71.jpg
                            // https://i.pinimg.com/736x/d4/e7/55/d4e755d2cf5476ef130b7bdc1d78de4e.jpg
                            <>
                                {/* transforma luego a un componente que cuando se tenaga notificaciones cambiar de icon */}
                                <LuBell className="w-9 h-9 mr-2 lg:mr-5 block cursor-pointer rounded-lg p-2 text-base font-normal text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700" />
                                <Avatar className="cursor-pointer" alt="User Avatar" img={state.authUser.user_avatar} rounded />
                            </>
                        }
                    >
                        <DropdownHeader>
                            <span className="block text-sm">{state.authUser.user_name}</span>
                            <span className="block truncate text-sm font-medium">{state.authUser.user_email}</span>
                        </DropdownHeader>
                        {/* <DropdownItem>Dashboard</DropdownItem> */}
                        <DropdownItem>Perfil</DropdownItem>
                        <DropdownItem>Settings</DropdownItem>
                        <DropdownDivider />
                        <DropdownItem onClick={logout}>Sign out</DropdownItem>
                    </Dropdown>
                    <Button className="block ml-3 lg:hidden" color="light" onClick={() => setIsOpen(true)} >
                        <LuMenu className="h-6 w-6" />
                    </Button>
                    {/* <NavbarToggle onClick={() => setIsOpen(true)} className="" /> */}
                </div>
            </Navbar>
            <Drawer open={isOpen} onClose={handleClose}>
                <DrawerHeader title="MENU" titleIcon={() => <></>} />
                <DrawerItems>
                    <Sidebar
                        aria-label="Sidebar with multi-level dropdown example"
                        className="[&>div]:bg-transparent [&>div]:p-0"
                    >
                        <div className="flex h-full flex-col justify-between py-2">
                            <div>
                                <form className="pb-3 md:hidden">
                                    <TextInput icon={HiSearch} type="search" placeholder="Search" required size={32} />
                                </form>
                                <SidebarItems>
                                    <SidebarItemGroup>
                                        <SidebarItem href={`/backoffice/admin/${state.authUser.user_id}/dashboard`} icon={HiChartPie}>
                                            Dashboard
                                        </SidebarItem>
                                        <SidebarItem href="#" icon={HiUser}>
                                            My Perfil
                                        </SidebarItem>
                                        <SidebarItem href={`/backoffice/admin/${state.authUser.user_id}/module`} icon={HiUsers}>
                                            Admins
                                        </SidebarItem>
                                        <SidebarItem href="#" icon={LuStore}>
                                            Tenants
                                        </SidebarItem>
                                        {/* <SidebarItem href="#" icon={TbWorldWww}>
                                            Domains
                                        </SidebarItem>
                                        <SidebarItem href="#" icon={LuShield}>
                                            Security
                                        </SidebarItem>
                                        <SidebarItem href="#" icon={LuSettings}>
                                            Settings
                                        </SidebarItem> */}
                                        <SidebarItem icon={HiLogout} onClick={logout}>
                                            Log Out
                                        </SidebarItem>
                                    </SidebarItemGroup>
                                    <SidebarItemGroup>
                                        {/* condiguración global del markeplace  */}
                                        {/* categorias y etc.., */}
                                        <SidebarItem icon={TbBuildingStore}>
                                            Settings Marketplace
                                        </SidebarItem>
                                        {/* <SidebarItem href="https://github.com/themesberg/flowbite-react/" icon={HiClipboard}>
                                            Docs
                                        </SidebarItem> */}
                                        {/* <SidebarItem href="https://flowbite-react.com/" icon={HiCollection}>
                                            Components
                                        </SidebarItem>
                                        <SidebarItem href="https://github.com/themesberg/flowbite-react/issues" icon={HiInformationCircle}>
                                            Help
                                        </SidebarItem> */}
                                    </SidebarItemGroup>
                                </SidebarItems>
                            </div>
                        </div>
                    </Sidebar>
                </DrawerItems>
            </Drawer>
        </>
    );

}

export default NavBarMovilDashboardComponent;
