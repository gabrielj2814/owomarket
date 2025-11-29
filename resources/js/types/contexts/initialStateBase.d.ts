import { AuthUser } from "../models/AuthUser";

export  interface  initialStateBase {
    user_uuid:   string;
    loading:     boolean;
    logout:      boolean;
    authUser:   AuthUser;
}
