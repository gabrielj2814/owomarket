import { ReactNode } from "react";
import { IconType } from "react-icons/lib";

export interface ToastInterface {
    type:      string;
    title:     string;
    icon?:     ReactNode;
    message?:  string;

}
