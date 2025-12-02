import { FormModuleAdmin } from '@/types/FormModuleAdmin';
import { Admin } from '@/types/models/Admin';
import { ModuleAdminFormReponseCreate } from '@/types/Response/ModuleAdminFormReponseCreate';
import { ModuleAdminFormReponseUpdate } from '@/types/Response/ModuleAdminFormReponseUpdate';
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
    },

    update:async (uuid: string,datos: FormModuleAdmin): Promise<ApiResponse<ModuleAdminFormReponseUpdate>> => {
        try{

            const body={
                id:     datos.id,
                name:   datos.name,
                email:  datos.email,
                phone:  datos.phone,
            }

            let respuesta:ApiResponse<ModuleAdminFormReponseUpdate> = await axiosAdmin.put(`${uuid}/module/admin/update/${datos.id}`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<ModuleAdminFormReponseUpdate>;
        }
    },

    consultByUuid: async (user_uuid: string): Promise<ApiResponse<Admin>> => {
         try{

            let respuesta:ApiResponse<Admin> = await axiosAdmin.get(`consult/${user_uuid}`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<Admin>;
        }
    }




}

export default AdminServices;
