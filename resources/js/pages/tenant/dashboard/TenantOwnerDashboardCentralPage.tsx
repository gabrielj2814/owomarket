import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import { Breadcrumb, BreadcrumbItem, Button, Card } from "flowbite-react";
import { FC, useEffect } from "react";
import { HiHome } from "react-icons/hi";
import { TbLink } from "react-icons/tb";


interface TenantOwnerDashboardCentralPageProps {
    title?:          string;
    user_id:         string
}

const TenantOwnerDashboardCentralPage:FC<TenantOwnerDashboardCentralPageProps> = ({title, user_id}) => {


    function irAlTenant(url: string){
        window.open(url, "_blank");
    }

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
                {/* <Card className="w-full p-4">
                    <div className=" dark:text-white">Tenant Owner Dashboard Central</div>
                </Card> */}

                <div className="flex flex-row flex-wrap">
                  <div className=" w-4/12 p-2">
                      <Card className="max-w-sm d-block">
                        <h5 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Empresa 1
                        </h5>
                         <Button onClick={() => irAlTenant("http://chivostore.owomarket.local/tenant")}>
                            Ir
                            <TbLink />
                        </Button>
                    </Card>
                  </div>
                  <div className=" w-4/12 p-2">
                      <Card className="max-w-sm d-block">
                        <h5 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Empresa 2
                        </h5>
                        <Button>
                            Ir
                            <TbLink />
                        </Button>
                    </Card>
                  </div>
                  <div className=" w-4/12 p-2">
                      <Card className="max-w-sm">
                        <h5 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Empresa 3
                        </h5>
                        <Button>
                            Ir
                            <TbLink />
                        </Button>
                    </Card>
                  </div>
                  <div className=" w-4/12 p-2">
                      <Card className="max-w-sm">
                        <h5 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Empresa 4
                        </h5>
                        <Button>
                            Ir
                            <TbLink />
                        </Button>
                    </Card>
                  </div>
                </div>

            </Dashboard>
        </>
    )
}

export default TenantOwnerDashboardCentralPage;
