import { Data } from '@/types/ResponseApi';
import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

const axiosCentral = axios.create({
    timeout: 20000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export interface CentralCheckoutItemPayload {
    tenant_id: string;
    product_id: string;
    /** Hallazgo N36: sin esto el comerciante no sabe que variante enviar. */
    variant_id?: string | null;
    product_name: string;
    sku?: string;
    price: number;
    quantity: number;
    attributes?: Record<string, string>;
}

export interface CreateCentralOrderPayload {
    /** Hallazgo N28: un código de cupón por tienda; los cupones son de tienda. */
    coupons?: Record<string, string>;
    customer: {
        id?: string;
        central_uuid?: string;
        name: string;
        email: string;
        phone?: string;
        document_id?: string;
    };
    shipping_address: {
        address: string;
        city: string;
        state?: string;
        zip?: string;
        notes?: string;
    };
    payment_method: 'pago_movil' | 'binance_pay' | 'bank_transfer' | string;
    payment_details?: {
        bank_origin?: string;
        phone_origin?: string;
        reference_number?: string;
        binance_id?: string;
        transaction_hash?: string;
        crypto_currency?: string;
        [key: string]: any;
    };
    shipping_amount?: number;
    discount_amount?: number;
    coupon_code?: string;
    /**
     * Clave de idempotencia (hallazgo C2). Se genera UNA vez por intento de
     * compra y se reutiliza en los reintentos: si el pedido ya se creó, el
     * servidor devuelve ese mismo en lugar de duplicarlo con sus comisiones.
     */
    idempotency_key?: string;
    currency?: string;
    items: CentralCheckoutItemPayload[];
}

export interface CentralOrderConfirmationResponse {
    id: string;
    order_number: string;
    status: string;
    payment_status: string;
    payment_method: string;
    payment_details?: any;
    subtotal: number;
    shipping_amount: number;
    discount_amount: number;
    total: number;
    currency: string;
    created_at: string;
    customer: {
        name: string;
        email: string;
        phone?: string;
    };
    shipping_address?: any;
    stores_count: number;
    stores_breakdown: Array<{
        tenant_id: string;
        store_name: string;
        store_domain?: string | null;
        subtotal: number;
        items_count: number;
        items: Array<{
            id: string;
            product_id: string;
            product_name: string;
            sku?: string;
            price: number;
            quantity: number;
            total: number;
            tenant_order_id?: string | null;
            commission_amount?: number;
        }>;
    }>;
}

export interface CentralProductItem {
    id: string;
    tenant_id: string;
    tenant_product_id: string;
    tenant_name?: string;
    tenant_domain?: string;
    name: string;
    slug: string;
    description?: string | null;
    sku?: string | null;
    barcode?: string | null;
    price: number;
    compare_price?: number | null;
    quantity: number;
    is_visible: boolean;
    is_featured: boolean;
    category_name?: string | null;
    brand_name?: string | null;
    images?: Array<{
        id?: string;
        image_path: string;
        alt_text?: string;
        is_default?: boolean;
    }>;
    variants?: Array<{
        id: string;
        sku: string;
        price: number;
        quantity: number;
        attributes: Record<string, string>;
    }>;
    created_at?: string;
}

export interface TenantStoreItem {
    id: string;
    name: string;
    slug: string;
    domain?: string;
    description?: string;
    logo?: string;
    banner?: string;
    products_count?: number;
}

export interface MarketplaceHomeData {
    featured_stores: TenantStoreItem[];
    featured_products: CentralProductItem[];
    recent_products: CentralProductItem[];
    categories: Array<{ name: string; count: number }>;
}

export interface MarketplaceProductFilterParams {
    search?: string;
    category?: string;
    brand?: string;
    tenant_id?: string;
    min_price?: number;
    max_price?: number;
    sort_by?: 'price_asc' | 'price_desc' | 'newest' | 'name';
    page?: number;
    per_page?: number;
}

export interface CentralProductsListResponse {
    products: CentralProductItem[];
    total: number;
    current_page: number;
    last_page: number;
}

export interface CentralProductDetailResponse {
    product: CentralProductItem;
    related: CentralProductItem[];
    store: TenantStoreItem;
}

export interface CentralRevalidatedCartLine {
    tenant_id: string;
    product_id: string;
    /** Hallazgo N36: identifica la linea cuando el mismo producto va con dos opciones. */
    variant_id?: string | null;
    central_product_id?: string;
    available: boolean;
    reason: string | null;
    name?: string;
    sku?: string | null;
    price?: number;
    quantity?: number;
    available_stock?: number;
    price_changed?: boolean;
    previous_price?: number | null;
    quantity_reduced?: boolean;
}

export interface CentralRevalidateCartResponse {
    lines: CentralRevalidatedCartLine[];
    has_changes: boolean;
}

export interface CentralOrderQuote {
    by_tenant: Record<
        string,
        {
            subtotal: number;
            shipping: number;
            tax: number;
            discount: number;
            coupon_code: string | null;
            coupon_error: string | null;
        }
    >;
    subtotal: number;
    shipping: number;
    tax: number;
    discount: number;
    total: number;
}

const CentralMarketplaceServices = {
    getHomeData: async (): Promise<Data<MarketplaceHomeData>> => {
        try {
            const response = await axiosCentral.get<Data<MarketplaceHomeData>>('/api/central/marketplace/home-data');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al cargar los datos del marketplace',
                    data: {
                        featured_stores: [],
                        featured_products: [],
                        recent_products: [],
                        categories: [],
                    },
                    meta: [],
                }
            );
        }
    },

    getProducts: async (params: MarketplaceProductFilterParams = {}): Promise<Data<CentralProductsListResponse>> => {
        try {
            const response = await axiosCentral.get<Data<CentralProductsListResponse>>('/api/central/marketplace/products', { params });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar productos del marketplace',
                    data: { products: [], total: 0, current_page: 1, last_page: 1 },
                    meta: [],
                }
            );
        }
    },

    getProductDetail: async (slugOrId: string): Promise<Data<CentralProductDetailResponse>> => {
        try {
            const response = await axiosCentral.get<Data<CentralProductDetailResponse>>(`/api/central/marketplace/product/${slugOrId}`);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar el detalle del producto',
                    data: null,
                    meta: [],
                }
            );
        }
    },

    getStores: async (): Promise<Data<TenantStoreItem[]>> => {
        try {
            const response = await axiosCentral.get<Data<TenantStoreItem[]>>('/api/central/marketplace/stores');
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al consultar tiendas asociadas',
                    data: [],
                    meta: [],
                }
            );
        }
    },

    /**
     * Hallazgo N31: la Fase 3.2 dio revalidacion al carrito de tienda y dejo fuera el
     * central, que seguia con precios y stock congelados en `localStorage`.
     */
    revalidateCart: async (
        items: Array<{ tenant_id: string; product_id: string; variant_id?: string | null; quantity: number; price?: number }>,
    ): Promise<Data<CentralRevalidateCartResponse>> => {
        try {
            const response = await axiosCentral.post<Data<CentralRevalidateCartResponse>>('cart/revalidate', { items });
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'No se pudo comprobar el carrito',
                    data: null as any,
                }
            );
        }
    },

    /**
     * Hallazgos N34 y N28: el checkout central mostraba el subtotal puro como total.
     * Devuelve lo mismo que calculara el servidor al crear el pedido.
     */
    quote: async (payload: {
        items: Array<{ tenant_id: string; product_id: string; variant_id?: string | null; quantity: number }>;
        shipping_address?: Record<string, unknown>;
        coupons?: Record<string, string>;
    }): Promise<Data<CentralOrderQuote>> => {
        try {
            const response = await axiosCentral.post<Data<CentralOrderQuote>>('checkout/quote', payload);
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'No se pudo calcular el total',
                    data: null as any,
                }
            );
        }
    },

    createUnifiedOrder: async (
        payload: CreateCentralOrderPayload,
    ): Promise<Data<{ order_id: string; order_number: string; total: number; redirect_url: string }>> => {
        try {
            const response = await axiosCentral.post<Data<{ order_id: string; order_number: string; total: number; redirect_url: string }>>(
                '/api/central/marketplace/checkout/create-order',
                payload,
            );
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al procesar el pedido unificado multi-tienda',
                    data: null as any,
                    meta: [],
                }
            );
        }
    },

    getOrderConfirmation: async (orderIdOrNumber: string): Promise<Data<CentralOrderConfirmationResponse>> => {
        try {
            const response = await axiosCentral.get<Data<CentralOrderConfirmationResponse>>(
                `/api/central/marketplace/order/${orderIdOrNumber}/confirmation`,
            );
            return response.data;
        } catch (error: any) {
            return (
                error.response?.data || {
                    status: 'error',
                    code: 500,
                    message: 'Error al recuperar la confirmación de la orden',
                    data: null as any,
                    meta: [],
                }
            );
        }
    },
};

export default CentralMarketplaceServices;
