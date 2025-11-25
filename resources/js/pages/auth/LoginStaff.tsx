import { Button, Card, Checkbox, Label, TextInput } from "flowbite-react";
import { HiLockClosed, HiMail } from "react-icons/hi";
import { LuSend, LuStore  } from "react-icons/lu";


const LoginStaff = () => {

    return (
        <>
            <main className="flex min-h-screen flex-col items-center justify-center bg-gray-100">
                <Card className="w-11/12 lg:w-8/12">
                    <div className="flex flex-row">
                        <div className="w-3/6 hidden lg:flex  flex-col justify-center p-5 text-white   ">
                            <h3 className="text-xl font-bold mb-7"><LuStore className=" inline-block text-4xl stroke-blue-600"/> Marketplace for business</h3>
                            <h3 className="text-4xl font-bold mb-3">Store Management Portal</h3>
                            <h3 className="text-xl text-gray-300">Welcomen back. Manage your products, orders, and staff</h3>
                        </div>
                        <div className="w-full lg:w-3/6 ">
                            <h1 className=" text-2xl text-white mb-5 font-bold">Staff Sing In</h1>
                            <form className="flex flex-col gap-4">
                                <div className="">
                                    <div className="mb-2 block">
                                        <Label htmlFor="emailInput">Email</Label>
                                    </div>
                                    <TextInput id="emailInput" type="email" icon={HiMail} placeholder="name@owomarket.com" required />
                                </div>
                                <div className="mb-5">
                                    <div className="mb-2 block">
                                        <Label htmlFor="passwordInput">Password</Label>
                                    </div>
                                    <TextInput id="passwordInput" type="password" icon={HiLockClosed} placeholder="password" required />
                                </div>
                                {/* <div className="flex items-center gap-2">
                                    <Checkbox id="remember" />
                                    <Label htmlFor="remember">Remember me</Label>
                                </div> */}
                                <Button type="submit"> <LuSend/>  Submit</Button>
                                <h6 className=" text-center text-sm text-white ">For authorized personnel only</h6>
                            </form>
                        </div>
                    </div>
                </Card>

            </main>





        </>
    )

}

export default LoginStaff;
