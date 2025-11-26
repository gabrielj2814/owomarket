import { ApiResponse } from '@/types/ResponseApi';
import axios from 'axios';

const getCSRFToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token || '';
};

const LoginServices = {

    login: async (dataFormLogin: { email: string; password: string; }): Promise<ApiResponse<ResponseLogin>> => {
        const headers = {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCSRFToken(),
        }

        const response = await axios.post<ApiResponse<ResponseLogin>>('/auth/login', dataFormLogin, {
            headers,
        });
        if(response.status === 200){
            return response.data;
        }
        return response.data

    }


}

export default LoginServices;
