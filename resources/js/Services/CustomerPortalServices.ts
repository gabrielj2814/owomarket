import axios from 'axios';

export interface CustomerProfileData {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    document_id?: string | null;
    avatar?: string | null;
    addresses?: CustomerAddressData[];
}

export interface CustomerAddressData {
    id: string;
    customer_id?: string;
    label: string;
    address: string;
    city: string;
    state?: string | null;
    zip_code?: string | null;
    country: string;
    is_default: boolean;
}

export interface CustomerOrderItemData {
    id: string;
    tenant_id: string;
    /** Nombre real de la tienda; antes el frontend mostraba el UUID (hallazgo G15). */
    tenant_name?: string | null;
    product_id: string;
    /** Slug del producto en el catálogo central, para que el enlace lleve a algún sitio. */
    product_slug?: string | null;
    product_name: string;
    sku?: string | null;
    price: number;
    quantity: number;
    total: number;
}

export interface CustomerOrderData {
    id: string;
    order_number: string;
    customer_name: string;
    customer_email: string;
    customer_phone?: string | null;
    shipping_address?: {
        address: string;
        city: string;
        state?: string;
        notes?: string;
    };
    payment_method: string;
    payment_details?: {
        bank_origin?: string;
        phone_origin?: string;
        reference_number?: string;
        rate_bcv?: number;
        total_bs?: number;
        binance_id?: string;
        transaction_hash?: string;
    };
    subtotal: number;
    discount_amount: number;
    shipping_amount: number;
    total: number;
    currency: string;
    status: 'pending' | 'paid' | 'processing' | 'completed' | 'cancelled';
    payment_status: 'pending' | 'paid' | 'failed';
    created_at: string;
    items?: CustomerOrderItemData[];
}

export interface OrderTrackingData {
    order_id: string;
    order_number: string;
    status: string;
    payment_status: string;
    current_step: number;
    courier?: string | null;
    tracking_number?: string | null;
    tracking_url?: string | null;
    timeline: {
        step: number;
        key: string;
        title: string;
        description: string;
        timestamp?: string | null;
        is_completed: boolean;
        is_current: boolean;
    }[];
}

export interface CustomerInvoiceData {
    id: string;
    invoice_number: string;
    order_id: string;
    order_number: string;
    customer_name: string;
    customer_email: string;
    customer_document_id: string;
    date: string;
    total_usd: number;
    total_ves: number;
    exchange_rate_bcv: number;
    payment_method: string;
    payment_status: string;
    pdf_url: string;
}

export interface CustomerReturnRequestData {
    id: string;
    order_id: string;
    order_number: string;
    customer_id: string;
    product_id: string;
    product_name: string;
    tenant_id: string;
    reason: string;
    description: string;
    photos?: string[];
    status: 'requested' | 'in_review' | 'approved' | 'rejected' | 'refunded';
    admin_notes?: string | null;
    created_at: string;
}

export interface CustomerCouponData {
    id: string;
    code: string;
    title: string;
    description: string;
    discount_type: 'percentage' | 'fixed';
    discount_value: number;
    min_purchase: number;
    valid_until: string;
    is_active: boolean;
    badge: string;
}

export interface CustomerWishlistItemData {
    id: string;
    product_id: string;
    tenant_id: string;
    product_name: string;
    product_slug?: string | null;
    product_price: number;
    product_image?: string | null;
    created_at: string;
}

export const CustomerPortalServices = {
    // Perfil
    async getProfile(customerId: string) {
        const res = await axios.get(`/api/central/customer/profile/${customerId}`);
        return res.data;
    },

    async updateProfile(customerId: string, data: Partial<CustomerProfileData> & { current_password?: string; new_password?: string }) {
        const res = await axios.put(`/api/central/customer/profile/${customerId}`, data);
        return res.data;
    },

    // Direcciones
    async addAddress(customerId: string, address: Omit<CustomerAddressData, 'id'>) {
        const res = await axios.post(`/api/central/customer/profile/${customerId}/address`, address);
        return res.data;
    },

    async updateAddress(customerId: string, addressId: string, address: Partial<CustomerAddressData>) {
        const res = await axios.put(`/api/central/customer/profile/${customerId}/address/${addressId}`, address);
        return res.data;
    },

    async deleteAddress(customerId: string, addressId: string) {
        const res = await axios.delete(`/api/central/customer/profile/${customerId}/address/${addressId}`);
        return res.data;
    },

    async setDefaultAddress(customerId: string, addressId: string) {
        const res = await axios.patch(`/api/central/customer/profile/${customerId}/address/${addressId}/default`);
        return res.data;
    },

    // Pedidos y Tracking
    async getOrders(customerId: string, filters?: { status?: string; search?: string; page?: number; limit?: number }) {
        const res = await axios.get(`/api/central/customer/orders`, {
            params: { customer_id: customerId, ...filters },
        });
        return res.data;
    },

    async getOrderDetail(customerId: string, orderId: string) {
        const res = await axios.get(`/api/central/customer/orders/${orderId}`, {
            params: { customer_id: customerId },
        });
        return res.data;
    },

    async getOrderTracking(customerId: string, orderId: string) {
        const res = await axios.get(`/api/central/customer/orders/${orderId}/tracking`, {
            params: { customer_id: customerId },
        });
        return res.data;
    },

    // Facturas
    async getInvoices(customerId: string) {
        const res = await axios.get(`/api/central/customer/invoices`, {
            params: { customer_id: customerId },
        });
        return res.data;
    },

    getInvoicePdfUrl(customerId: string, orderId: string): string {
        return `/api/central/customer/invoices/${orderId}/pdf?customer_id=${encodeURIComponent(customerId)}`;
    },

    // Devoluciones (RMA)
    async getReturns(customerId: string) {
        const res = await axios.get(`/api/central/customer/returns`, {
            params: { customer_id: customerId },
        });
        return res.data;
    },

    async createReturn(payload: {
        customer_id: string;
        order_id: string;
        product_id: string;
        reason: string;
        description: string;
        photos?: string[];
    }) {
        const res = await axios.post(`/api/central/customer/returns`, payload);
        return res.data;
    },

    // Cupones
    async getCoupons(customerId?: string) {
        const res = await axios.get(`/api/central/customer/coupons`, {
            params: { customer_id: customerId },
        });
        return res.data;
    },

    // Reseñas
    async getPendingReviews(customerId: string) {
        const res = await axios.get(`/api/central/customer/reviews/pending`, {
            params: { customer_id: customerId },
        });
        return res.data;
    },

    async submitReview(payload: { customer_id: string; order_id: string; product_id: string; rating: number; title?: string; comment: string }) {
        const res = await axios.post(`/api/central/customer/reviews`, payload);
        return res.data;
    },

    // Favoritos (Wishlist)
    async getWishlist(customerId: string) {
        const res = await axios.get(`/api/central/customer/wishlist`, {
            params: { customer_id: customerId },
        });
        return res.data;
    },

    async toggleWishlist(payload: {
        customer_id: string;
        product_id: string;
        tenant_id: string;
        product_name: string;
        product_slug?: string;
        product_price: number;
        product_image?: string;
    }) {
        const res = await axios.post(`/api/central/customer/wishlist/toggle`, payload);
        return res.data;
    },

    // Recuperación de Contraseña
    async requestPasswordReset(email: string) {
        const res = await axios.post(`/api/central/customer/forgot-password`, { email });
        return res.data;
    },

    async resetPassword(payload: { email: string; pin_code: string; password: string }) {
        const res = await axios.post(`/api/central/customer/reset-password`, payload);
        return res.data;
    },
};

export default CustomerPortalServices;
