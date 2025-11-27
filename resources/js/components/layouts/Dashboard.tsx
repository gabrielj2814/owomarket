import { FC } from "react";
import SidebarDashboardComponent from "../ui/SidebarDashboardComponent";
import NavBarMovilDashboardComponent from "../ui/NavBarMovilDashboardComponent";
import { Card } from "flowbite-react";

interface DashboardProps {

    children?: React.ReactNode;

}



const Dashboard:FC<DashboardProps> = ({children}) => {
    return (
        <>
        <div className=" h-screen bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            <NavBarMovilDashboardComponent/>
            <div className=" flex flex-row p-4 gap-4">
                <SidebarDashboardComponent />

                <Card className="w-full p-4">
                    {children}
                </Card>

            </div>
        </div>


        </>
    );
}

export default Dashboard;
