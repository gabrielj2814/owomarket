import { ErrorsFormUpdateStoreSettings } from '@/types/ErrorsFormTenantSettings';
import { FormSaveSettingItem, FormUpdateStoreSettings } from '@/types/FormTenantSettings';
import { StoreSettingsResponse, TenantSettingItem } from '@/types/models/TenantSettings';
import { Data } from '@/types/ResponseApi';
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
    getStoreSettings: async (): Promise<Data<StoreSettingsResponse>> => {
        try {
            const response = await axiosSettings.get<Data<StoreSettingsResponse>>('');
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

    updateStoreSettings: async (data: FormUpdateStoreSettings): Promise<Data<StoreSettingsResponse, ErrorsFormUpdateStoreSettings>> => {
        try {
            const response = await axiosSettings.put<Data<StoreSettingsResponse, ErrorsFormUpdateStoreSettings>>('', data);
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

    getByGroup: async (group: string): Promise<Data<TenantSettingItem[]>> => {
        try {
            const response = await axiosSettings.get<Data<TenantSettingItem[]>>(`group/${group}`);
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

    getByKey: async (key: string): Promise<Data<TenantSettingItem>> => {
        try {
            const response = await axiosSettings.get<Data<TenantSettingItem>>(`item/${key}`);
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

    saveItem: async (data: FormSaveSettingItem): Promise<Data<TenantSettingItem>> => {
        try {
            const response = await axiosSettings.post<Data<TenantSettingItem>>('item', data);
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

    deleteItem: async (key: string): Promise<Data<null>> => {
        try {
            const response = await axiosSettings.delete<Data<null>>(`item/${key}`);
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
