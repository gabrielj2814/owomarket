import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import { Breadcrumb, BreadcrumbItem, Button, Card } from "flowbite-react";
import { FC } from "react";
import { HiHome } from "react-icons/hi";
import { LuArrowBigLeft } from "react-icons/lu";

interface FormProductPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const FormProductPage:FC<FormProductPageProps> = ({user_id, title, host, user_name}) => {

    const regresar = () => {
        window.location.href=`/product/backoffice/${user_id}/module`;
    }

    return (
        <>
            <Head>
                <title>{title}</title>
            </Head>
            <Dashboard user_uuid={user_id} >
                <Breadcrumb aria-label="Solid background breadcrumb example" className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-5">
                    <BreadcrumbItem icon={HiHome} href={`/tenant/backoffice/${user_id}/dashboard`}>
                        Home
                    </BreadcrumbItem>
                    <BreadcrumbItem href={`/product/backoffice/${user_id}/module`}>
                        Product
                    </BreadcrumbItem>
                </Breadcrumb>
                <div className="flex flex-row mb-2">
                    <Button pill color="red" onClick={regresar}><LuArrowBigLeft className=" w-6 h-6 mr-1"/>  Back</Button>
                </div>

                <h2 className="text-3xl font-bold dark:text-white">Required Fields</h2>
                <Card className="w-full p-4 mb-3">
                    <div className=" dark:text-white">Fields</div>
                </Card>

                <h2 className="text-2xl font-bold dark:text-white">Optional Fields</h2>

                <h2 className="text-1xl font-bold dark:text-white">Dimensions</h2>
                <Card className="w-full p-4 mb-3">
                    <div className=" dark:text-white">Fields</div>
                </Card>
            </Dashboard>

        </>
    );


}

export default FormProductPage;
