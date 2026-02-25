import { Badge } from "flowbite-react";
import { HiShieldCheck } from "react-icons/hi";
import { HiMiniLockClosed } from "react-icons/hi2";

const TenantInactivePage = () => {

    return (
        <>
            <main className="flex flex-col justify-center items-center h-screen bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400">

                <div className=" rounded-full p-4 mb-6  dark:bg-gray-950 dark:text-gray-400 ">
                    <HiShieldCheck className="text-6xl text-blue-600 "/>
                </div>

                <h1 className="text-4xl font-bold mb-4">Store Pending Activation</h1>
                <Badge color="warning" size="sm" icon={HiMiniLockClosed} className=" font-bold mb-4">
                    403 ACCESS RESTRICTED
                </Badge>
                <p className="w-1/4 text-center">
                    Welcome to Owomarket! Your store profile has been received. Our platform administrators are currently reviewing your application to ensure it meets our community guidelines. This usually takes 24-48 hours.
                </p>


            </main>
        </>
    );

}

export default TenantInactivePage;
