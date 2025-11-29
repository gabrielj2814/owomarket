import Dashboard from "@/components/layouts/Dashboard";
import { FC, useEffect } from "react";


interface AdminDashboardPageProps {
    title?:          string;
    user_id:         string
}

const AdminDashboardPage:FC<AdminDashboardPageProps> = ({title, user_id}) => {
    useEffect(() => {
        if (title) {
            document.title = title;
        } else {
            document.title = "Admin Dashboard";
        }
    });

    return (
        <>
            <Dashboard user_uuid={user_id}>
                <h1 className=" dark:text-white">Admin Dashboard</h1>
            </Dashboard>
        </>
    )
}

export default AdminDashboardPage;
