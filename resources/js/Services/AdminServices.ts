import { FormModuleAdmin } from '@/types/FormModuleAdmin';
import { ModuleAdminFormReponseCreate } from '@/types/Response/ModuleAdminFormReponseCreate';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosAdmin= axios.create({
    baseURL: "/backoffice/admin/",
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    }
})



const AdminServices = {

//   login: async (dataFormLogin: { email: string; password: string; }): Promise<ApiResponse<ResponseLogin>> => {
//         try{
//             const headers = {
//                 'Content-Type': 'application/json',
//                 'X-CSRF-TOKEN': getCSRFToken(),
//             }

//             const response: ApiResponse<ResponseLogin> = await axios.post('/auth/login', dataFormLogin, {
//                 headers,
//             });

//             return response
//         }
//         catch(error){
//             return error as ApiResponse<ResponseLogin>;
//         }

//     },

    create:async (uuid: string,datos: FormModuleAdmin): Promise<ApiResponse<ModuleAdminFormReponseCreate>> => {
        try{

            const body={
                name:   datos.name,
                email:  datos.email,
                phone:  datos.phone,
            }

            let respuesta:ApiResponse<ModuleAdminFormReponseCreate> = await axiosAdmin.post(`${uuid}/module/admin/create`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<ModuleAdminFormReponseCreate>;
        }
    }




}

export default AdminServices;
