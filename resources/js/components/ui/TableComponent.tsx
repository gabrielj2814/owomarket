
import { Table, TableBody, TableCell, TableHead, TableHeadCell, TableRow } from "flowbite-react";
import { FC, ReactNode } from "react";
import { LuDatabase } from "react-icons/lu";

interface TableComponentProps {
    TableHead:     ReactNode
    TableContent?: ReactNode[]
    colSpan:       number
}

const TableComponent:FC<TableComponentProps> = ({TableHead, TableContent=[], colSpan}) => {
  return (
    <Table hoverable >
        {TableHead}
        <TableBody className="divide-y">
            {TableContent &&
                TableContent
            }
            {TableContent.length==0 &&
                 <TableRow className="bg-white dark:border-gray-700 dark:bg-gray-800">
                    <TableCell colSpan={colSpan} className="whitespace-nowrap font-medium text-gray-900 dark:text-white text-center">
                          <LuDatabase className=" inline-block w-6 h-6 mr-2"/> <span className="inline-block"> No Data</span>
                    </TableCell>
                 </TableRow>
            }
        </TableBody>
      </Table>
  );
}

export default TableComponent
