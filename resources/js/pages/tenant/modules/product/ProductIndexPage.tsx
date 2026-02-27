import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import { Breadcrumb, BreadcrumbItem, Button, Card } from "flowbite-react";
import { FC } from "react";
import { HiHome, HiPlus } from "react-icons/hi";

interface ProductIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const ProductIndexPage: FC<ProductIndexPageProps> = ({ user_id, title, host, user_name }) => {



        const irHaFormularioDeCrear = () => {
            window.location.href = `/product/backoffice/${user_id}/module/record`;
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
                <div className="w-full flex flex-row justify-end mb-5">
                    <Button onClick={irHaFormularioDeCrear} pill> <HiPlus className=" w-6 h-6 mr-1"/>  Create</Button>
                </div>

                <Card className="w-full p-4">
                    <div className=" dark:text-white">Modulo de producto</div>
                </Card>
            </Dashboard>
        </>
    )


}

export default ProductIndexPage;
