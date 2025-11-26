import { ApiResponse } from '@/types/ResponseApi';
import { ResponseLogin } from '@/types/ResponseLogin';
import axios from 'axios';
import { log } from 'console';

const getCSRFToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token || '';
};

const AuthServices = {

    login: async (dataFormLogin: { email: string; password: string; }): Promise<ApiResponse<ResponseLogin>> => {
        const headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCSRFToken(),
        }

        const response: ApiResponse<ResponseLogin> = await axios.post('/auth/login', dataFormLogin, {
            headers,
        });

        return response

    },

    logout: async (): Promise<ApiResponse<null>> => {
        const headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCSRFToken(),
        }

        const response: ApiResponse<null> = await axios.post('/auth/logout', {}, {
            headers,
        });

        return response

    }


}

export default AuthServices;
