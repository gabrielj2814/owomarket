import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import { Breadcrumb, BreadcrumbItem, Card } from "flowbite-react";
import { FC, useEffect } from "react";
import { HiHome } from "react-icons/hi";


interface AdminDashboardPageProps {
    title?:          string;
    user_id:         string
}

const AdminDashboardPage:FC<AdminDashboardPageProps> = ({title, user_id}) => {

    return (
        <>
            <Head>
                <title>{title}</title>
            </Head>
            <Dashboard user_uuid={user_id}>
                <Breadcrumb aria-label="Solid background breadcrumb example" className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-5">
                    <BreadcrumbItem icon={HiHome}>
                        Home
                    </BreadcrumbItem>
                </Breadcrumb>
                <Card className="w-full p-4">
                    <div className=" dark:text-white">Tenant Owner Dashboard Central</div>
                </Card>
            </Dashboard>
        </>
    )
}

export default AdminDashboardPage;
