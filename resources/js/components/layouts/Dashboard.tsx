import { FC } from "react";
import SidebarDashboardComponent from "../ui/SidebarDashboardComponent";
import NavBarDashboardComponent from "../ui/NavBarDashboardComponent";

interface DashboardProps {

    children?: React.ReactNode;

}



const Dashboard:FC<DashboardProps> = ({children}) => {
    return (
        <>
            <NavBarDashboardComponent/>
            <div className=" flex min-h-screen flex-row bg-gray-100 p-4 gap-4">
                <SidebarDashboardComponent />
                <div>
                    {children}
                </div>
            </div>

        </>
    );
}

export default Dashboard;
