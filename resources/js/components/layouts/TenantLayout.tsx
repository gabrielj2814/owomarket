import React, { FC } from "react";
import CustomerAuthModal from "@/components/ui/storefront/CustomerAuthModal";
import NavBarMovilMarketComponent from "../ui/marketplace/NavBarMovilMarketComponent";
import SidebarMarketComponent from "../ui/marketplace/SidebarMarketComponent";

interface TenantLayoutProps {
    children?: React.ReactNode;
}

const TenantLayout:FC<TenantLayoutProps> = ({children}) => {


    return (
        <>
             <div className=" h-screen bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400 overflow-hidden">
                    <div className=" h-screen bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400 overflow-hidden">
                        {/*
                          * Hallazgo A1: el menu de este layout abre el modal de acceso, y el
                          * modal solo se renderizaba en CentralLayout y StorefrontLayout. Sin
                          * esta linea, openAuthModal() cambia el estado y no aparece nada.
                          * El provider ya es global (app.tsx), asi que solo faltaba montarlo.
                          */}
                        <CustomerAuthModal />
                        <NavBarMovilMarketComponent/>
                        <div className=" flex flex-row p-4 gap-4">
                            <SidebarMarketComponent/>
                            <div className="w-full">
                                {children}
                            </div>

                        </div>
                    </div>
             </div>

        </>
    )

}

export default TenantLayout;
