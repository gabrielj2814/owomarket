

import AuthServices from "@/Services/AuthServices";
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
  NavbarToggle,
  Sidebar,
  SidebarItem,
  SidebarItemGroup,
  SidebarItems,
  TextInput,
} from "flowbite-react";
import { useState } from "react";
import {
  HiChartPie,
  HiClipboard,
  HiCollection,
  HiInformationCircle,
  HiLogin,
  HiPencil,
  HiSearch,
  HiShoppingBag,
  HiUsers,
} from "react-icons/hi";
import { LuMenu } from "react-icons/lu";

const NavBarMovilDashboardComponent = () => {

    const [isOpen, setIsOpen] = useState(false);

    const handleClose = () => setIsOpen(false);

    const logout = async () => {

        const respuesta = await AuthServices.logout()
        console.log(respuesta)
        window.location.href = '/auth/login-staff';

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
            <Avatar alt="User settings" img="https://i.pinimg.com/736x/d4/e7/55/d4e755d2cf5476ef130b7bdc1d78de4e.jpg" rounded />
          }
        >
          <DropdownHeader>
            <span className="block text-sm">Bonnie Green</span>
            <span className="block truncate text-sm font-medium">name@flowbite.com</span>
          </DropdownHeader>
          <DropdownItem>Dashboard</DropdownItem>
          <DropdownItem>Settings</DropdownItem>
          <DropdownItem>Earnings</DropdownItem>
          <DropdownDivider />
          <DropdownItem onClick={logout}>Sign out</DropdownItem>
        </Dropdown>
        <Button className="block lg:hidden ml-2"  color="light" onClick={() => setIsOpen(true)} >
            <LuMenu className="h-6 w-6"/>
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
                        <SidebarItem href="/" icon={HiChartPie}>
                        Dashboard
                        </SidebarItem>
                        <SidebarItem href="/e-commerce/products" icon={HiShoppingBag}>
                        Products
                        </SidebarItem>
                        <SidebarItem href="/users/list" icon={HiUsers}>
                        Users list
                        </SidebarItem>
                        <SidebarItem href="/authentication/sign-in" icon={HiLogin}>
                        Sign in
                        </SidebarItem>
                        <SidebarItem href="/authentication/sign-up" icon={HiPencil}>
                        Sign up
                        </SidebarItem>
                    </SidebarItemGroup>
                    <SidebarItemGroup>
                        <SidebarItem href="https://github.com/themesberg/flowbite-react/" icon={HiClipboard}>
                        Docs
                        </SidebarItem>
                        <SidebarItem href="https://flowbite-react.com/" icon={HiCollection}>
                        Components
                        </SidebarItem>
                        <SidebarItem href="https://github.com/themesberg/flowbite-react/issues" icon={HiInformationCircle}>
                        Help
                        </SidebarItem>
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
