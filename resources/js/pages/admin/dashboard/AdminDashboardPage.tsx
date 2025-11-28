import Dashboard from "@/components/layouts/Dashboard";
import { AuthUser } from "@/types/AuthUser";
import { FC, useEffect } from "react";


interface AdminDashboardPageProps {
    title?:          string;
    user_id:         string
    user_name:       string
    user_email:      string
    user_type:       string
    user_avatar:     string
}

const AdminDashboardPage:FC<AdminDashboardPageProps> = ({title, user_id, user_name, user_email, user_avatar, user_type }) => {
    console.log("current user_id => ", user_id)
    console.log("current user_name => ", user_name)
    console.log("current user_email => ", user_email)
    console.log("current user_avatar => ", user_avatar)
    console.log("current user_type => ", user_type)

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
