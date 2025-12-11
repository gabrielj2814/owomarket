import { FormModuleAdmin } from '@/types/FormModuleAdmin';
import { Admin } from '@/types/models/Admin';
import { ErrorResponse } from '@/types/Response/ErrorResponse';
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


    create:async (uuid: string,datos: FormModuleAdmin): Promise<ApiResponse<ModuleAdminFormReponseCreate, ErrorResponse>> => {
        try{

            const body={
                name:   datos.name,
                email:  datos.email,
                phone:  datos.phone,
            }

            let respuesta:ApiResponse<ModuleAdminFormReponseCreate, ErrorResponse> = await axiosAdmin.post(`${uuid}/admin`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<ModuleAdminFormReponseCreate, ErrorResponse>;
        }
    },

    update:async (uuid: string,datos: FormModuleAdmin): Promise<ApiResponse<ModuleAdminFormReponseUpdate, ErrorResponse>> => {
        try{

            const body={
                id:     datos.id,
                name:   datos.name,
                email:  datos.email,
                phone:  datos.phone,
            }

            let respuesta:ApiResponse<ModuleAdminFormReponseUpdate, ErrorResponse> = await axiosAdmin.put(`${uuid}/admin/${datos.id}`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<ModuleAdminFormReponseUpdate, ErrorResponse>;
        }
    },

    consultByUuid: async (user_uuid: string): Promise<ApiResponse<Admin>> => {
         try{

            let respuesta:ApiResponse<Admin> = await axiosAdmin.get(`${user_uuid}`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<Admin>;
        }
    },

    filtrar: async (search: string|null, fechaDesdeUTC: string, fechaHastaUTC: string, status: boolean, prePage: number= 50, page: number=1): Promise<ApiResponse<Admin[]>> => {
         try{
            const body={
                search,
                fechaDesdeUTC,
                fechaHastaUTC,
                status,
                prePage,
            }
            let respuesta:ApiResponse<Admin[]> = await axiosAdmin.post(`filter?page=${page}`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<Admin[]>;
        }
    },

    delete: async (uuid: string): Promise<ApiResponse<void>> => {
        try{
            let respuesta:ApiResponse<void> = await axiosAdmin.delete(`${uuid}`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<void>;
        }
    },

    changeStatu: async (id: string, statu: boolean): Promise<ApiResponse<void>> => {
        try{
            const body={
                id,
                statu
            }
            let respuesta:ApiResponse<void> = await axiosAdmin.put(`${id}/change-statu`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<void>;
        }
    }




}

export default AdminServices;
