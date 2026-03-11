import Dashboard from "@/components/layouts/Dashboard";
import { Head } from "@inertiajs/react";
import { Breadcrumb, BreadcrumbItem, Button, Card, Label, Textarea, TextInput, ToggleSwitch } from "flowbite-react";
import { FC, useState } from "react";
import { HiHome } from "react-icons/hi";
import { LuArrowBigLeft, LuSave, LuSaveOff } from "react-icons/lu";

interface FormProductPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const FormProductPage:FC<FormProductPageProps> = ({user_id, title, host, user_name}) => {

      const [isVisible, setIsVisible] = useState(false);
      const [isFeatured, setIsFeatured] = useState(false);
    //   const [isDigital, setIsDigital] = useState(false);

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

                <form onSubmit={() => alert('envio uwu')}>
                    <div className={`overflow-scroll overflow-x-hidden overflow-y-auto mb-2`} style={{ height: "calc(100vh - 270px)" }}>
                        <h2 className="text-3xl font-bold dark:text-white">Required Fields</h2>
                        <Card className="w-full p-4 mb-3">
                            <div className="flex flex-wrap flex-row">
                                {/* <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2 lg:p-0">
                                        <div className="mb-2 block">
                                            <Label htmlFor="name" color={`${(errorForms.name!=null)?errorForms.name.type:"gray"}`} >Name <span className="text-red-500">(*)</span></Label>
                                        </div>
                                        <TextInput id="name" type="text" name="name" icon={HiUser} placeholder="Name" onChange={handlersChangeForm} value={form.name} required color={`${(errorForms.name!=null)?errorForms.name.type:"gray"}`} />
                                        {errorForms.name!=null &&
                                            <HelperText color={`${(errorForms.name!=null)?errorForms.name.type:"gray"}`}>
                                                {errorForms.name.message}
                                            </HelperText>
                                        }
                                </div> */}
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="name"  >Name <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="name" type="text" name="name"  placeholder="Name"  required  />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="slug"  >Slug <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="slug" type="text" name="slug"  placeholder="Slug"  required  />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="sku"  >Sku <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="sku" type="text" name="sku"  placeholder="Sku"  required  />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="price"  >Price <span className="text-red-500">(*)</span></Label>
                                    </div>
                                    <TextInput id="price" type="number" name="price"  placeholder="Price"  required  />
                                </div>
                            </div>
                            {/* <div className=" dark:text-white">Fields</div> */}
                        </Card>

                        <h2 className="text-2xl font-bold dark:text-white">Optional Fields</h2>

                        <h2 className="text-1xl font-bold dark:text-white">General</h2>
                        <Card className="w-full p-4 mb-3">
                            {/* <div className=" dark:text-white">Fields</div> */}
                            <div className="flex flex-wrap flex-row mb-5">
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="quantity"  >Quantity </Label>
                                    </div>
                                    <TextInput id="quantity" type="number" name="quantity"  placeholder="Quantity"   />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="min_quantity"  >Minimum Quantity </Label>
                                    </div>
                                    <TextInput id="min_quantity" type="number" name="min_quantity"  placeholder="Minimum Quantity"   />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="max_quantity"  >Maximum Quantity </Label>
                                    </div>
                                    <TextInput id="max_quantity" type="number" name="max_quantity"  placeholder="Maximum Quantity"   />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="track_quantity"  >Track Quantity </Label>
                                    </div>
                                    <TextInput id="track_quantity" type="number" name="track_quantity"  placeholder="Track Quantity"   />
                                </div>
                                <div className=" w-full p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="description"  >Description </Label>
                                    </div>
                                    <Textarea className="resize-none" id="description" name="description"  placeholder="Description"   />
                                </div>
                                <div className=" w-full p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="short_description"  >Short Description </Label>
                                    </div>
                                    <Textarea className="resize-none" id="short_description" name="short_description"  placeholder="Short Description"   />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="cost_price"  >Cost Price </Label>
                                    </div>
                                    <TextInput id="cost_price" type="number" name="cost_price"  placeholder="Cost Price"   />
                                </div>

                            </div>
                            <div className="flex flex-wrap flex-row">
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <ToggleSwitch checked={isVisible} label="Is Visible" onChange={setIsVisible} />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <ToggleSwitch checked={isFeatured} label="Is Featured" onChange={setIsFeatured} />
                                </div>
                            </div>

                        </Card>


                        <h2 className="text-1xl font-bold dark:text-white">Dimensions</h2>
                        <Card className="w-full p-4 mb-3">
                            {/* <div className=" dark:text-white">Fields</div> */}
                            <div className="flex flex-wrap flex-row mb-5">
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="weight"  >Weight </Label>
                                    </div>
                                    <TextInput id="weight" type="number" name="weight"  placeholder="Weight"   />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="height"  >Height </Label>
                                    </div>
                                    <TextInput id="height" type="number" name="height"  placeholder="Height"   />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="width"  >Width </Label>
                                    </div>
                                    <TextInput id="width" type="number" name="width"  placeholder="Width"   />
                                </div>
                                <div className=" w-full sm:w-6/12 md:w-6/12 lg:w-3/12 p-2">
                                    <div className="mb-2 block">
                                        <Label htmlFor="length"  >Length </Label>
                                    </div>
                                    <TextInput id="length" type="number" name="length"  placeholder="Length"   />
                                </div>
                            </div>
                        </Card>
                    </div>

                    <div className="flex flex-row flex-wrap justify-end gap-4">
                        <Button pill color="red" onClick={() => alert("Cancelar")} className="w-full sm:w-auto order-2 sm:order-1"> <LuSaveOff className=" w-6 h-6 mr-1"/> Cancelar</Button>
                        <Button pill type="submit" className="w-full sm:w-auto order-1 sm:order-2"> <LuSave className=" w-6 h-6 mr-1"/>   Save</Button>
                    </div>
                </form>

                {/* <h2 className="text-1xl font-bold dark:text-white">Digital</h2>
                <Card className="w-full p-4 mb-3">
                    <div className=" dark:text-white">Fields</div>
                </Card> */}
            </Dashboard>

        </>
    );


}

export default FormProductPage;
