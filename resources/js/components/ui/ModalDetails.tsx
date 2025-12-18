
import { Modal, ModalBody, ModalHeader } from "flowbite-react";
import { FC, ReactNode } from "react";

interface ModalDetailsProps {
    title?:                 string;
    size?:                  string;
    openModal:             boolean;
    onClose(statu: boolean): void;
    children: ReactNode;
}

const ModalDetails: FC<ModalDetailsProps> = ({ title="Sin Titulo", openModal, size="md", onClose, children}) => {


  return (
    <>
      <Modal show={openModal} size={size} onClose={() => onClose(false)} popup>
        <ModalHeader >{title}</ModalHeader>
        <ModalBody>
            {children}
        </ModalBody>
      </Modal>
    </>
  );
}

export default ModalDetails
