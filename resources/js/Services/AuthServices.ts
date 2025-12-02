import { AuthUser } from '@/types/models/AuthUser';
import { ApiResponse } from '@/types/ResponseApi';
import { ResponseLogin } from '@/types/ResponseLogin';
import axios from 'axios';

const getCSRFToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token || '';
};

const AuthServices = {

    login: async (dataFormLogin: { email: string; password: string; }): Promise<ApiResponse<ResponseLogin>> => {
        try{
            const headers = {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken(),
            }

            const response: ApiResponse<ResponseLogin> = await axios.post('/auth/login', dataFormLogin, {
                headers,
            });

            return response
        }
        catch(error){
            return error as ApiResponse<ResponseLogin>;
        }

    },

    logout: async (uuid: string): Promise<ApiResponse<boolean>> => {
        const headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCSRFToken(),
        }

        const body= {
            uuid,
        }

        const response: ApiResponse<boolean> = await axios.post('/auth/logout', body, {
            headers,
        });

        return response

    },

    authUser: async (uuid: string): Promise<ApiResponse<AuthUser>> => {
        const headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCSRFToken(),
        }

        const response: ApiResponse<AuthUser> = await axios.get(`/auth/user/${uuid}`, {
            headers,
        });

        return response


    }


}

export default AuthServices;
