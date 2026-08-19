import { Order } from './Order';

export type ShipmentStatusType = 'pending' | 'in_transit' | 'delivered';

export interface Shipment {
    id: string;
    order_id: string;
    tracking_number: string | null;
    carrier: string;
    service: string;
    cost: number;
    notes: string | null;
    status: ShipmentStatusType;
    shipped_at: string | null;
    estimated_delivery: string | null;
    delivered_at: string | null;
    metadata: Record<string, any> | null;
    created_at: string | null;
    updated_at: string | null;
    order?: Order | null;
}

export interface ShipmentMetrics {
    total_shipments: number;
    pending_shipments: number;
    in_transit_shipments: number;
    delivered_shipments: number;
    total_shipping_cost: number;
}
