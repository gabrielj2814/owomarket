import React, { FC } from "react";
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
