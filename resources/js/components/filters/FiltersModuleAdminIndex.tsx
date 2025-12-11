import { Card, Datepicker, TextInput, ToggleSwitch } from "flowbite-react"
import { FC } from "react";
import { LuSearch } from "react-icons/lu";

interface FiltersModuleAdminIndexProps {
    search?:       string;
    fechaDesde:    Date;
    fechaHasta:    Date;
    status:        boolean;
    onChangeStatus(checked: boolean): void
    onChangeDesde(date: Date | null): void
    onChangeHasta(date: Date | null): void
    onChangeSearch(e: React.ChangeEvent<HTMLInputElement>) : void

}

const FiltersModuleAdminIndex:FC<FiltersModuleAdminIndexProps> = ({search="", fechaDesde, fechaHasta, status= true, onChangeStatus, onChangeDesde, onChangeHasta, onChangeSearch }) => {

    return (
      <Card className="mb-3">
            <div className="flex w-full flex-row items-center gap-4">
                <div className=" w-3/12">
                    <TextInput id="search" type="text" placeholder="Search by name or email" icon={LuSearch} value={search} onChange={onChangeSearch} />
                </div>
                <div>
                     <Datepicker name="filterFrom" title="Filter from" value={fechaDesde} onChange={onChangeDesde}/>
                </div>
                <div>
                     <Datepicker name="filterUpTo" title="Filter up to" value={fechaHasta} onChange={onChangeHasta}/>
                </div>
                <div>
                     <ToggleSwitch checked={status} label="Activo" onChange={onChangeStatus} />
                </div>
            </div>
            {/* <div className="flex max-w-md flex-col gap-4">

            </div> */}
        </Card>
    )

}

export default FiltersModuleAdminIndex
