import { TextInputColors } from "flowbite-react";

export interface AlertInputForm {
    type:      "gray" | "info" | "failure" | "warning" | "success";
    message:   string;
}
