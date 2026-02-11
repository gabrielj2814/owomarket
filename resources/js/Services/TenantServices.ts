import { FormCreateAccounTenant } from '@/types/FormCreateAccounTenant';
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
    },

    rejected: async (uuid: string): Promise<ApiResponse<void>> => {
        try{

            let respuesta:ApiResponse<void> = await axiosTenant.patch(`backoffice/${uuid}/rejected`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<void>;
        }
    },

    approved: async (uuid: string): Promise<ApiResponse<void>> => {
        try{

            let respuesta:ApiResponse<void> = await axiosTenant.patch(`backoffice/${uuid}/approved`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<void>;
        }
    },

    createAccountTenant: async (data: FormCreateAccounTenant): Promise<ApiResponse<void, ErrorResponse>> => {
        try {

            const body={
                name: data.name,
                email: data.email,
                phone: data.phone,
                password: data.password,
                confirmPassword: data.confirmPassword,
                store_name: data.store_name,
                tenant_name: data.tenant_name
            }


            let respuesta:ApiResponse<void> = await axiosTenant.post(`create/account`, body)

            return respuesta

        } catch (error) {
            return error as ApiResponse<void>;
        }
    },

    consultMyCompanies: async (idOwner: string, page: number=1, prePage: number=50): Promise<ApiResponse<Tenant[]>> => {
        try {
            const body = {
                id: idOwner,
                prePage
            }

            let respuesta:ApiResponse<Tenant[]> = await axiosTenant.post(`owner/filter/tenants?page=${page}`, body)

            return respuesta


        } catch (error) {
            return error as ApiResponse<Tenant[]>;
        }
    }





}

export default TenantServices


