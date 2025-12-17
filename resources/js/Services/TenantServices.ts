import Tenant from '@/types/models/Tenant';
import { ErrorResponse } from '@/types/Response/ErrorResponse';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosTenant= axios.create({
    baseURL: "/backoffice/tenant/",
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

            let respuesta:ApiResponse<Tenant[]> = await axiosTenant.post(`filter?page=${page}`,body)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<Tenant[]>;
        }
    },

    consultTenantByUuid: async (uuid: string): Promise<ApiResponse<Tenant>> => {
        try{

            let respuesta:ApiResponse<Tenant> = await axiosTenant.get(`${uuid}`)

            return respuesta
        }
        catch(error){
            return error as ApiResponse<Tenant>;
        }
    }



}

export default TenantServices


