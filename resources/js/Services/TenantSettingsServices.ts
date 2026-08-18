import { ErrorsFormUpdateStoreSettings } from '@/types/ErrorsFormTenantSettings';
import { FormSaveSettingItem, FormUpdateStoreSettings } from '@/types/FormTenantSettings';
import { StoreSettingsResponse, TenantSettingItem } from '@/types/models/TenantSettings';
import { ApiResponse } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosSettings = axios.create({
    baseURL: '/api-tenant/settings/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

const TenantSettingsServices = {
    getStoreSettings: async (): Promise<ApiResponse<StoreSettingsResponse>> => {
        try {
            const response = await axiosSettings.get<ApiResponse<StoreSettingsResponse>>('');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar configuraciones de la tienda',
                    data: {
                        grouped: {
                            general: {
                                store_name: 'Mi Tienda Online',
                                store_email: 'contacto@tienda.com',
                                currency: 'USD',
                                contact_phone: null,
                                address: null,
                            },
                            appearance: {
                                logo_url: null,
                                banner_url: null,
                            },
                            social: {
                                facebook: null,
                                instagram: null,
                                whatsapp: null,
                                twitter: null,
                            },
                            seo: {
                                meta_title: null,
                                meta_description: null,
                                meta_keywords: null,
                            },
                        },
                        flat: {
                            store_name: 'Mi Tienda Online',
                            store_email: 'contacto@tienda.com',
                            currency: 'USD',
                            contact_phone: null,
                            address: null,
                            logo_url: null,
                            banner_url: null,
                            social_facebook: null,
                            social_instagram: null,
                            social_whatsapp: null,
                            social_twitter: null,
                            seo_title: null,
                            seo_description: null,
                            seo_keywords: null,
                        },
                    },
                }
            );
        }
    },

    updateStoreSettings: async (
        data: FormUpdateStoreSettings
    ): Promise<ApiResponse<StoreSettingsResponse, ErrorsFormUpdateStoreSettings>> => {
        try {
            const response = await axiosSettings.put<ApiResponse<StoreSettingsResponse, ErrorsFormUpdateStoreSettings>>(
                '',
                data
            );
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al actualizar configuración de la tienda',
                    data: null as any,
                }
            );
        }
    },

    getByGroup: async (group: string): Promise<ApiResponse<TenantSettingItem[]>> => {
        try {
            const response = await axiosSettings.get<ApiResponse<TenantSettingItem[]>>(`group/${group}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: `Error al consultar parámetros del grupo ${group}`,
                    data: [],
                }
            );
        }
    },

    getByKey: async (key: string): Promise<ApiResponse<TenantSettingItem>> => {
        try {
            const response = await axiosSettings.get<ApiResponse<TenantSettingItem>>(`item/${key}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: `Error al consultar parámetro ${key}`,
                    data: null as any,
                }
            );
        }
    },

    saveItem: async (data: FormSaveSettingItem): Promise<ApiResponse<TenantSettingItem>> => {
        try {
            const response = await axiosSettings.post<ApiResponse<TenantSettingItem>>('item', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al guardar parámetro individual',
                    data: null as any,
                }
            );
        }
    },

    deleteItem: async (key: string): Promise<ApiResponse<null>> => {
        try {
            const response = await axiosSettings.delete<ApiResponse<null>>(`item/${key}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al eliminar parámetro',
                    data: null,
                }
            );
        }
    },
};

export default TenantSettingsServices;
