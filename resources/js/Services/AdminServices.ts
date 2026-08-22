import { FormChangePasswordWithPin } from '@/types/FormChangePasswordWithPin';
import { FormModuleAdmin } from '@/types/FormModuleAdmin';
import { FormUpdateProfile } from '@/types/FormUpdateProfile';
import { Admin } from '@/types/models/Admin';
import { ErrorResponse } from '@/types/Response/ErrorResponse';
import { ModuleAdminFormReponseCreate } from '@/types/Response/ModuleAdminFormReponseCreate';
import { ModuleAdminFormReponseUpdate } from '@/types/Response/ModuleAdminFormReponseUpdate';
import { ResponseAvatarUpload } from '@/types/Response/ResponseAvatarUpload';
import { ResponseProfileUpdate } from '@/types/Response/ResponseProfileUpdate';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosAdmin= axios.create({
    baseURL: "/admin/",
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

            const respuesta:ApiResponse<ModuleAdminFormReponseCreate, ErrorResponse> = await axiosAdmin.post(`backoffice/${uuid}/admin`,body)

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

            const respuesta:ApiResponse<ModuleAdminFormReponseUpdate, ErrorResponse> = await axiosAdmin.put(`backoffice/${uuid}/admin/${datos.id}`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<ModuleAdminFormReponseUpdate, ErrorResponse>;
        }
    },

    consultByUuid: async (user_uuid: string): Promise<ApiResponse<Admin>> => {
         try{

            const respuesta:ApiResponse<Admin> = await axiosAdmin.get(`backoffice/${user_uuid}`)

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
            const respuesta:ApiResponse<Admin[]> = await axiosAdmin.post(`backoffice/filter?page=${page}`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<Admin[]>;
        }
    },

    delete: async (uuid: string): Promise<ApiResponse<void>> => {
        try{
            const respuesta:ApiResponse<void> = await axiosAdmin.delete(`backoffice/${uuid}`)

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
            const respuesta:ApiResponse<void> = await axiosAdmin.put(`backoffice/${id}/change-statu`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<void>;
        }
    },

    updateProfile: async (user_uuid: string, datos: FormUpdateProfile): Promise<ApiResponse<ResponseProfileUpdate, ErrorResponse>> => {
        try {
            const respuesta: ApiResponse<ResponseProfileUpdate, ErrorResponse> = await axiosAdmin.put(`backoffice/${user_uuid}/profile`, datos);
            return respuesta;
        } catch (error) {
            return error as ApiResponse<ResponseProfileUpdate, ErrorResponse>;
        }
    },

    uploadAvatar: async (user_uuid: string, file: File): Promise<ApiResponse<ResponseAvatarUpload, ErrorResponse>> => {
        try {
            const formData = new FormData();
            formData.append('avatar', file);

            const respuesta: ApiResponse<ResponseAvatarUpload, ErrorResponse> = await axiosAdmin.post(
                `backoffice/${user_uuid}/profile/avatar`,
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                }
            );
            return respuesta;
        } catch (error) {
            return error as ApiResponse<ResponseAvatarUpload, ErrorResponse>;
        }
    },

    sendSecurityPin: async (user_uuid: string): Promise<ApiResponse<void, ErrorResponse>> => {
        try {
            const respuesta: ApiResponse<void, ErrorResponse> = await axiosAdmin.post(`backoffice/${user_uuid}/profile/send-pin`);
            return respuesta;
        } catch (error) {
            return error as ApiResponse<void, ErrorResponse>;
        }
    },

    changePasswordWithPin: async (user_uuid: string, datos: FormChangePasswordWithPin): Promise<ApiResponse<void, ErrorResponse>> => {
        try {
            const respuesta: ApiResponse<void, ErrorResponse> = await axiosAdmin.put(`backoffice/${user_uuid}/profile/change-password`, datos);
            return respuesta;
        } catch (error) {
            return error as ApiResponse<void, ErrorResponse>;
        }
    }
}

export default AdminServices;
