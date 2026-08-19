import { ShipmentStatusType } from './models/Shipment';

export interface FormCreateShipment {
    order_id: string;
    carrier: string;
    service: string;
    cost?: number;
    tracking_number?: string;
    notes?: string;
    estimated_delivery?: string;
    metadata?: Record<string, any>;
}

export interface FormUpdateTracking {
    tracking_number: string;
    carrier?: string;
    service?: string;
    cost?: number;
    shipped_at?: string;
    estimated_delivery?: string;
    notes?: string;
}

export interface FilterShipmentsParams {
    search?: string | null;
    status?: ShipmentStatusType | string | null;
    carrier?: string | null;
    order_id?: string | null;
    date_from?: string | null;
    date_to?: string | null;
    page?: number;
    per_page?: number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
}
