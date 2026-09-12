import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

/**
 * Reclamaciones y expedientes, lado administrador (subsistema 5, fase D).
 *
 * **El listado y el expediente son dos peticiones distintas a propósito.** El listado se
 * consulta muchas veces y no lleva ningún dato de identidad; el expediente completo es una
 * petición deliberada. Esa separación es lo que hace que el dato sensible viaje solo cuando
 * alguien de verdad va a usarlo — juntarlos por comodidad la desharía.
 */
const axiosClaims = axios.create({
    baseURL: '/admin/api/claims',
    timeout: 15000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export type ClaimStatus = 'requested' | 'in_review' | 'approved' | 'rejected' | 'refunded';

export interface AdminClaimRow {
    id: string;
    order_number: string;
    product_name: string;
    amount: number;
    tenant_id: string;
    tenant_name: string | null;
    customer_email: string | null;
    reason: string;
    status: ClaimStatus;
    is_open: boolean;
    /** `merchant` si respondió la tienda, `timeout` si venció el plazo. */
    resolved_by: string | null;
    /** Lo que puso la plataforma de su bolsillo al cubrir esta reclamación. */
    platform_covered_amount: number;
    created_at: string | null;
}

export interface ClaimPagination {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
}

export interface ClaimMetrics {
    open_count: number;
    /** Cuántas resolvió el reloj porque la tienda no contestó. */
    timeout_count: number;
    total_count: number;
}

export interface ClaimListResult {
    claims: AdminClaimRow[];
    pagination: ClaimPagination;
    metrics: ClaimMetrics;
}

/**
 * El expediente completo.
 *
 * `store` incluye cédula y RIF por una decisión explícita de fase de desarrollo, pendiente de
 * revisión legal. Ver la nota `pendiente-abogado:` en `BuildClaimDossierUseCase`.
 */
export interface ClaimDossier {
    claim: {
        id: string;
        order_number: string;
        product_name: string;
        amount: number;
        reason: string;
        description: string | null;
        photos: string[];
        status: ClaimStatus;
        delivered_at: string | null;
        claimed_at: string | null;
        resolved_at: string | null;
        resolved_by: string | null;
        resolution_notes: string | null;
        platform_covered_amount: number;
    };
    customer: {
        name: string | null;
        email: string | null;
        phone: string | null;
        document_id: string | null;
    };
    store: {
        tenant_id: string;
        legal_name: string | null;
        cedula: string | null;
        nationality: string | null;
        rif: string | null;
        phone: string | null;
        address: string | null;
        kyc_status: string;
        reputation: {
            level: string;
            reserve_percent: number;
            deliveries: number;
            deliveries_for_next: number;
            unanswered_claims: number;
            has_debt: boolean;
        };
    };
    delivery: {
        declared_delivered_at: string | null;
        confirmed_at: string | null;
        released_by: string | null;
        shipment_evidence: Array<Record<string, unknown>>;
        confirmation_evidence: Array<Record<string, unknown>>;
    } | null;
}

interface AdminApiResponse<T = unknown> {
    status: 'success' | 'error';
    code: number;
    message: string;
    data: T;
}

const AdminClaimServices = {
    listar: async (params: { status?: string; search?: string; page?: number; per_page?: number } = {}) => {
        const response = await axiosClaims.get<AdminApiResponse<ClaimListResult>>('', { params });

        return response.data;
    },

    expediente: async (claimId: string) => {
        const response = await axiosClaims.get<AdminApiResponse<ClaimDossier>>(`/${claimId}/dossier`);

        return response.data;
    },

    /**
     * La URL del PDF, para un enlace normal.
     *
     * No se descarga por `axios`: un `<a href>` deja que el navegador lo guarde con su nombre
     * y su diálogo de siempre. Traerlo como blob solo añadiría código para reconstruir a mano
     * lo que el navegador ya hace bien.
     */
    urlDelPdf: (claimId: string) => `/admin/api/claims/${claimId}/dossier.pdf`,
};

export default AdminClaimServices;
