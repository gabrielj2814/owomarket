export interface ErrorsFormCreateShipment {
    order_id?: string[];
    carrier?: string[];
    service?: string[];
    cost?: string[];
    tracking_number?: string[];
    notes?: string[];
    estimated_delivery?: string[];
    metadata?: string[];
}

export interface ErrorsFormUpdateTracking {
    tracking_number?: string[];
    carrier?: string[];
    service?: string[];
    cost?: string[];
    shipped_at?: string[];
    estimated_delivery?: string[];
    notes?: string[];
}
