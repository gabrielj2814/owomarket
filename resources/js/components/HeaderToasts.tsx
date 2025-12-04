import { ToastInterface } from "@/types/ToastInterface"
import { Toast, ToastToggle } from "flowbite-react"
import { FC, ReactNode } from "react"


interface HeaderToastsProps {
    list: ToastInterface[]
}

const HeaderToasts:FC<HeaderToastsProps> = ({list= []}) => {

    const colorsIcon:any= {
        "success":"bg-green-100 text-green-500 dark:bg-green-800 dark:text-green-200",
        "warning":"bg-orange-100 text-orange-500 dark:bg-orange-700 dark:text-orange-200",
        "failure":"bg-red-100 text-red-500 dark:bg-red-800 dark:text-red-200",
        "info":"bg-cyan-100 text-cyan-500 dark:bg-cyan-900 dark:text-cyan-300",
        "grey":"bg-cyan-100 text-cyan-500 dark:bg-cyan-900 dark:text-cyan-300",
    }


    return (<div className="relative">
       <div className="absolute z-50 top-2 right-2">
            {list.map(toast => {
                return (
                    <Toast className="mb-3 border border-gray-200 bg-white shadow-md dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex items-start">
                            {toast.icon &&
                                <div className={`inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${colorsIcon[toast.type]}`}>
                                    {toast.icon}
                                </div>
                            }

                            <div className="ml-3 text-sm font-normal">
                            <span className="mb-1 text-sm font-semibold text-gray-900 dark:text-white">{toast.title}</span>
                            {toast.message &&
                                <div className="mb-2 text-sm font-normal">{toast.message}</div>
                            }
                            </div>
                            <ToastToggle />
                        </div>
                    </Toast>
                )
            })}
       </div>

    </div>)

}

export default HeaderToasts
