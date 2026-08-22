import { ErrorsFormCreateShipment, ErrorsFormUpdateTracking } from '@/types/ErrorsFormShipment';
import { FilterShipmentsParams, FormCreateShipment, FormUpdateTracking } from '@/types/FormShipment';
import { Shipment, ShipmentMetrics } from '@/types/models/Shipment';
import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosShipment = axios.create({
    baseURL: '/api-tenant/shipment/',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface PaginatedShipmentsData {
    data: Shipment[];
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
}

const ShipmentServices = {
    filtrar: async (params: FilterShipmentsParams = {}): Promise<Data<PaginatedShipmentsData>> => {
        try {
            const body = {
                search: params.search ?? null,
                status: params.status ?? null,
                carrier: params.carrier ?? null,
                order_id: params.order_id ?? null,
                date_from: params.date_from ?? null,
                date_to: params.date_to ?? null,
                per_page: params.per_page ?? 15,
                page: params.page ?? 1,
                sort_by: params.sort_by ?? 'created_at',
                sort_direction: params.sort_direction ?? 'desc',
            };

            const response = await axiosShipment.post<Data<PaginatedShipmentsData>>('filter', body);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al filtrar despachos',
                    data: {
                        data: [],
                        total: 0,
                        current_page: 1,
                        per_page: 15,
                        last_page: 1,
                    },
                }
            );
        }
    },

    getMetrics: async (): Promise<Data<ShipmentMetrics>> => {
        try {
            const response = await axiosShipment.get<Data<ShipmentMetrics>>('metrics');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar métricas de envíos',
                    data: {
                        total_shipments: 0,
                        pending_shipments: 0,
                        in_transit_shipments: 0,
                        delivered_shipments: 0,
                        total_shipping_cost: 0,
                    },
                }
            );
        }
    },

    create: async (data: FormCreateShipment): Promise<Data<Shipment, ErrorsFormCreateShipment>> => {
        try {
            const response = await axiosShipment.post<Data<Shipment, ErrorsFormCreateShipment>>('create', data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al registrar el envío',
                    data: null as any,
                }
            );
        }
    },

    consultById: async (id: string): Promise<Data<Shipment>> => {
        try {
            const response = await axiosShipment.get<Data<Shipment>>(`${id}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar el envío',
                    data: null as any,
                }
            );
        }
    },

    consultByOrderId: async (orderId: string): Promise<Data<Shipment[]>> => {
        try {
            const response = await axiosShipment.get<Data<Shipment[]>>(`order/${orderId}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar los envíos de la orden',
                    data: [],
                }
            );
        }
    },

    updateTracking: async (id: string, data: FormUpdateTracking): Promise<Data<Shipment, ErrorsFormUpdateTracking>> => {
        try {
            const response = await axiosShipment.post<Data<Shipment, ErrorsFormUpdateTracking>>(`${id}/tracking`, data);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al actualizar el seguimiento del envío',
                    data: null as any,
                }
            );
        }
    },

    markAsDelivered: async (id: string, deliveredAt?: string): Promise<Data<Shipment>> => {
        try {
            const response = await axiosShipment.post<Data<Shipment>>(`${id}/deliver`, {
                delivered_at: deliveredAt ?? null,
            });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al marcar el envío como entregado',
                    data: null as any,
                }
            );
        }
    },
};

export default ShipmentServices;
