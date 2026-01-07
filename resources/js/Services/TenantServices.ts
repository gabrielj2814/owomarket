import Tenant from '@/types/models/Tenant';
import { ErrorResponse } from '@/types/Response/ErrorResponse';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosTenant= axios.create({
    baseURL: "/tenant/",
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    }
})


const TenantServices = {

    filtrar: async (search: string|null, fechaDesdeUTC: string, fechaHastaUTC: string, status: string, request: string, prePage: number= 50, page: number=1): Promise<ApiResponse<Tenant[]>> => {
        try{
            const body={
                search,
                fechaDesdeUTC,
                fechaHastaUTC,
                status,
                tenantRequest:request,
                prePage,
            }

            let respuesta:ApiResponse<Tenant[]> = await axiosTenant.post(`backoffice/filter?page=${page}`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<Tenant[]>;
        }
    },

    consultTenantByUuid: async (uuid: string): Promise<ApiResponse<Tenant>> => {
        try{

            let respuesta:ApiResponse<Tenant> = await axiosTenant.get(`backoffice/${uuid}`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<Tenant>;
        }
    },

    suspended: async (uuid: string): Promise<ApiResponse<void>> => {
        try{

            let respuesta:ApiResponse<void> = await axiosTenant.patch(`backoffice/${uuid}/suspended`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<void>;
        }
    },

    active: async (uuid: string): Promise<ApiResponse<void>> => {
        try{

            let respuesta:ApiResponse<void> = await axiosTenant.patch(`backoffice/${uuid}/active`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<void>;
        }
    },

    inactive: async (uuid: string): Promise<ApiResponse<void>> => {
        try{

            let respuesta:ApiResponse<void> = await axiosTenant.patch(`backoffice/${uuid}/inactive`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<void>;
        }
    }



}

export default TenantServices


