import Dashboard from "@/components/layouts/Dashboard";
import { FC, useEffect } from "react";


interface AdminDashboardPageProps {
    title?: string;
}

const AdminDashboardPage:FC<AdminDashboardPageProps> = ({title}) => {

    useEffect(() => {
        if (title) {
            document.title = title;
        } else {
            document.title = "Admin Dashboard";
        }
    });

    return (
        <>
            <Dashboard>
                <h1 className=" dark:text-white">Admin Dashboard</h1>
            </Dashboard>
        </>
    )
}

export default AdminDashboardPage;
