

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
    NavbarCollapse,
    NavbarLink,
    Sidebar,
    SidebarCollapse,
    SidebarItem,
    SidebarItemGroup,
    SidebarItems,
    TextInput,
} from "flowbite-react";
import { useState } from "react";
import {
    HiChartPie,
    HiClock,
    HiHome,
    HiLogin,
    HiLogout,
    HiSearch,
    HiUser,
    HiUsers,
} from "react-icons/hi";
import { LuBell, LuMenu, LuStore, LuUsers } from "react-icons/lu";
import { TbBuildingStore } from "react-icons/tb";

const NavBarMovilMarketComponent = () => {

    const centralDomain = import.meta.env.VITE_APP_CENTRAL_DOMAIN;
    const APP_URL = import.meta.env.VITE_APP_URL;

    const [isOpen, setIsOpen] = useState(false);

    // const { state, actions } = useDashboard()


    const handleClose = () => setIsOpen(false);

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
        <>

            <Navbar fluid rounded>
                <NavbarBrand href="https://flowbite-react.com">
                    <img src="/favicon.svg" className="mr-3 h-6 sm:h-9" alt="Flowbite React Logo" />
                    <span className="self-center whitespace-nowrap text-xl font-semibold dark:text-white">OwOMarket</span>
                </NavbarBrand>
                <div className="flex md:order-2">
                    <Dropdown
                        arrowIcon={false}
                        inline
                        label={
                            // https://i.pinimg.com/originals/b0/ce/76/b0ce76f4cdb95ef13afa21a889adfc71.jpg
                            // https://i.pinimg.com/736x/d4/e7/55/d4e755d2cf5476ef130b7bdc1d78de4e.jpg
                            // https://i.pinimg.com/originals/e8/70/80/e870804e27b0a544329be37868b77054.jpg
                            <>
                                <Avatar className="cursor-pointer" alt="User Avatar" img="https://i.pinimg.com/originals/e8/70/80/e870804e27b0a544329be37868b77054.jpg" rounded />
                            </>
                        }
                    >
                        <DropdownHeader>
                            <span className="block text-sm">Customer Name</span>
                            <span className="block truncate text-sm font-medium">custorme@gmail.com</span>
                        </DropdownHeader>
                        <DropdownItem>Perfil</DropdownItem>
                        <DropdownDivider />
                        <DropdownItem onClick={logout}>Sign out</DropdownItem>
                    </Dropdown>
                    <Button className="block ml-3 lg:hidden" color="light" onClick={() => setIsOpen(true)} >
                        <LuMenu className="h-6 w-6" />
                    </Button>
                    {/* <NavbarToggle onClick={() => setIsOpen(true)} className="" /> */}
                </div>
                <NavbarCollapse>
                    {/* central */}
                    {window.location.hostname == centralDomain &&
                        <>
                            <NavbarLink href="/">Home</NavbarLink>
                            <NavbarLink href="#">Shops</NavbarLink>
                            <NavbarLink href="#">About</NavbarLink>
                            <NavbarLink href="#">FAQ</NavbarLink>
                            <NavbarLink href="/tenant/create/account">Create your business</NavbarLink>
                        </>
                    }

                    {/* Tenant */}
                    {window.location.hostname != centralDomain &&
                        <>
                            <NavbarLink href="/">Home</NavbarLink>
                            <NavbarLink href="#">Products</NavbarLink>
                        </>
                    }
                    <NavbarLink href={APP_URL+"/auth/customer/login"}>Login</NavbarLink>
                </NavbarCollapse>
            </Navbar>
            <Drawer open={isOpen} onClose={handleClose}>
                <DrawerHeader title="MENU" titleIcon={() => <LuStore className=" inline-block text-blue-700 w-8 h-8 mr-2"/> } />
                <DrawerItems>
                    <Sidebar
                        aria-label="Sidebar with multi-level dropdown example"
                        className="[&>div]:bg-transparent [&>div]:p-0"
                    >
                        <div className="flex h-full flex-col justify-between py-2">
                            <div>
                                {/* central */}
                                {window.location.hostname == centralDomain &&
                                    <>
                                     <SidebarItems>
                                        <SidebarItemGroup>

                                            <SidebarItem href={`/`} icon={HiHome}>
                                                Home
                                            </SidebarItem>
                                            <SidebarItem href="#" icon={HiClock}>
                                                About
                                            </SidebarItem>
                                            <SidebarItem href={`#`} icon={HiClock}>
                                                FAQ
                                            </SidebarItem>

                                            <SidebarItem href={`/tenant/create/account`} icon={LuStore}>
                                                Create your business
                                            </SidebarItem>
                                            <SidebarItem href={APP_URL+"/auth/customer/login"} icon={HiLogin}>
                                                Login
                                            </SidebarItem>
                                        </SidebarItemGroup>
                                    </SidebarItems>
                                    </>
                                }

                                {/* Tenant */}
                                {window.location.hostname !== centralDomain &&
                                    <>
                                     <SidebarItems>
                                        <SidebarItemGroup>

                                            <SidebarItem href={`/`} icon={HiHome}>
                                                Home
                                            </SidebarItem>
                                            <SidebarItem href="#" icon={HiClock}>
                                                Products
                                            </SidebarItem>
                                            <SidebarItem href={APP_URL+"/auth/customer/login"} icon={HiLogin}>
                                                Login
                                            </SidebarItem>
                                        </SidebarItemGroup>
                                    </SidebarItems>
                                    </>
                                }
                            </div>
                        </div>
                    </Sidebar>
                </DrawerItems>
            </Drawer>
        </>
    );

}

export default NavBarMovilMarketComponent;
