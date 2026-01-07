import { Button, Label, TextInput } from "flowbite-react";
import { HiMail, HiPhone, HiUser } from "react-icons/hi";
import { LuSend, LuStore } from "react-icons/lu";
import { TbPassword } from "react-icons/tb";


const CreateAccountTenantPage = () => {

    return (
        <main className="flex flex-row h-screen bg-white text-gray-600 dark:bg-gray-900 dark:text-gray-400">
            {/* <LoaderSpinner status={statusLoader} /> */}

            <div className=" basis-full lg:basis-1/2 hidden lg:block p-4 h-screen">
                <div className=" text-2xl font-bold mb-10"> <LuStore className=" inline-block text-blue-700 w-10 h-10"/>  OwOMarket</div>

                <div className=" w-full flex flex-row justify-center">
                    <div className=" text-center">
                        <img className=" w-xl h-xl rounded-2xl mb-5" src="https://i.pinimg.com/736x/24/81/d1/2481d19f7d6d2062cc987c2384f0096e.jpg" alt="" />
                        <h2 className="text-4xl mb-3 font-bold">Open your store on OwOMarket</h2>
                        <div>Join hundreds of sellers and start reaching new customers today.</div>
                    </div>
                </div>

            </div>

            <div className=" basis-full lg:basis-1/2 h-screen overflow-y-auto bg-gray-200 text-gray-600 dark:bg-gray-950 dark:text-gray-400 flex flex-col items-center p-10">

                <div className=" w-10/12 lg:w-7/12">
                    <h1 className=" text-4xl mb-8 font-bold">Create Your Store & Account</h1>

                    <form className="" onSubmit={() => alert("ok")}>
                        <h2 className="text-1xl mb-2 font-bold">Personal Information</h2>

                        <div className="w-full flex flex-row flex-wrap justify-between">
                            <div className=" basis-full mb-2">
                                <div className="mb-2 block">
                                    <Label htmlFor="name">Name</Label>
                                </div>
                                <TextInput id="name" type="text" name="name" icon={HiUser} placeholder="Name" required />
                            </div>
                            <div className="basis-full md:basis-5/12 mb-2">
                                <div className="mb-2 block">
                                    <Label htmlFor="email">Email</Label>
                                </div>
                                <TextInput id="email" type="email" name="email" icon={HiMail} placeholder="name@owomarket.com" required />
                            </div>
                            <div className="basis-full md:basis-5/12 mb-2">
                                <div className="mb-2 block">
                                    <Label htmlFor="phone">Phone</Label>
                                </div>
                                <TextInput id="phone" type="text" name="phone" icon={HiPhone} placeholder="phone" required />
                            </div>
                        </div>
                        <div className="w-full flex flex-row flex-wrap justify-between mb-10">
                            <div className="basis-full md:basis-5/12 mb-2 md:mb-0">
                                <div className="mb-2 block">
                                    <Label htmlFor="password">Password</Label>
                                </div>
                                <TextInput id="password" type="password" name="password" icon={TbPassword} placeholder="Password" required />
                            </div>
                            <div className="basis-full md:basis-5/12">
                                <div className="mb-2 block">
                                    <Label htmlFor="confirmPassword">Confirm Password</Label>
                                </div>
                                <TextInput id="confirmPassword" type="password" name="confirmPassword" icon={TbPassword} placeholder="Confirm Password" required />
                            </div>
                        </div>

                        <h2 className="text-1xl mb-3 font-bold">Store Information</h2>

                        <div className="w-full flex flex-row flex-wrap justify-between mb-20">
                            <div className="basis-full mb-2">
                                <div className="mb-2 block">
                                    <Label htmlFor="storeName">Store Name</Label>
                                </div>
                                <TextInput id="storeName" type="text" name="storeName" icon={LuStore} placeholder="Store Name" required />
                            </div>
                            <div className="basis-full">
                                <div className="mb-2 block">
                                    <Label htmlFor="urlTenant">Tenant Name (Your store's unique address)</Label>
                                </div>
                                <TextInput id="urlTenant" type="text" name="urlTenant" icon={LuStore} placeholder="Url Tenant" required />
                            </div>
                        </div>

                        <Button className=" w-full" type="submit"> <LuSend/> Register Store & Account</Button>


                    </form>

                </div>


            </div>

            {/* formulario movil */}





        </main>
    );


}

export default CreateAccountTenantPage;


