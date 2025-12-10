import { Pagination } from "flowbite-react";
import { FC, useState } from "react";

interface PaginationNavigationProps {
    currentPageFather:  number;
    itemsPerPageFather: number;
    totalItemsFather:   number;
    className?:        string;

    onPageChangeFather(page: number): void;

}

const PaginationNavigationCustom:FC<PaginationNavigationProps> = ({currentPageFather=1, itemsPerPageFather=50, totalItemsFather=0, className=""  ,onPageChangeFather}) => {
  const [currentPage, setCurrentPage] = useState(currentPageFather);

  const onPageChange = (page: number) => {
    setCurrentPage(page)
    onPageChangeFather(page)
  };

  return (
    <div className={`flex overflow-x-auto justify-center sm:justify-end ${className}`}>
      <Pagination layout="table" currentPage={currentPage} itemsPerPage={itemsPerPageFather} totalItems={totalItemsFather} onPageChange={onPageChange} showIcons />
    </div>
  );
}

export default PaginationNavigationCustom
