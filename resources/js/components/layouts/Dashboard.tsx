import { FC } from "react";
import SidebarDashboardComponent from "../ui/SidebarDashboardComponent";
import NavBarMovilDashboardComponent from "../ui/NavBarMovilDashboardComponent";
import { Card } from "flowbite-react";
import { DashboardProvider } from "@/contexts/DashboardContext";
import LoaderSpinnerContext from "../LoaderSpinnerContext";

interface DashboardProps {
    user_uuid: string,
    children?: React.ReactNode;
}



const Dashboard:FC<DashboardProps> = ({children, user_uuid}) => {
    return (
        <>
        <DashboardProvider user_uuid={user_uuid}>
            <LoaderSpinnerContext/>
            <div className="h-screen flex flex-col bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400 overflow-hidden">
                <NavBarMovilDashboardComponent/>
                <div className="flex flex-row p-4 gap-4 flex-1 overflow-hidden">
                    <SidebarDashboardComponent />
                    <div className="w-full h-full overflow-y-auto pr-2 pb-16">
                        {children}
                    </div>
                </div>
            </div>

        </DashboardProvider>
        </>
    );
}

export default Dashboard;
