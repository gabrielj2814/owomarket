
import { Button, Modal, ModalBody, ModalHeader } from "flowbite-react";
import { FC, ReactNode, useState } from "react";

interface ModalAlertConfirmationProps {
    size:                  string;
    openModal:             boolean;
    icon:                  ReactNode;
    text:                  string | ReactNode;
    buttonTextCancel:      string | ReactNode;
    buttonTextAction:      string | ReactNode;
    colorButtonCancel:     string;
    colorButtonAction:     string;
    onClose(statu: boolean): void;
    onClickAction(): void;
}

const ModalAlertConfirmation: FC<ModalAlertConfirmationProps> = ({ openModal, size="md", icon, text, buttonTextCancel, buttonTextAction, colorButtonCancel="alternative", colorButtonAction="success", onClose, onClickAction}) => {


  return (
    <>
      <Modal show={openModal} size={size} onClose={() => onClose(false)} popup>
        <ModalHeader />
        <ModalBody>
          <div className="text-center">
            {icon}
            {/* <HiOutlineExclamationCircle className="mx-auto mb-4 h-14 w-14 text-gray-400 dark:text-gray-200" /> */}
            <h3 className="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
              {text}
            </h3>
            <div className="flex justify-center gap-4">
              <Button color={colorButtonCancel} onClick={() => onClose(false) }>
                {buttonTextCancel}
              </Button>
              <Button color={colorButtonAction} onClick={() => onClickAction() }>
                {buttonTextAction}
              </Button>
            </div>
          </div>
        </ModalBody>
      </Modal>
    </>
  );
}

export default ModalAlertConfirmation
