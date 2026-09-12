import getCSRFToken from '@/utils/getCSRFToken';
import axios from 'axios';

/**
 * Verificación de identidad del comerciante, lado administrador (subsistema 1).
 *
 * Hasta que existió esta superficie, `ReviewTenantKycUseCase` no tenía ruta: el KYC exigía
 * identidad verificada para retirar y no había forma de verificar a nadie, así que **ninguna
 * tienda de la plataforma podía cobrar**.
 *
 * **Ningún método de este fichero devuelve la cédula ni el RIF.** El backend no los manda, y
 * aquí tampoco se piden: están cifrados en reposo y traerlos al navegador desharía ese cifrado
 * por la puerta de atrás. La búsqueda por identidad va por `id` de expediente justamente para
 * no tener que escribir un número que la pantalla no debe conocer.
 */
const axiosKyc = axios.create({
    baseURL: '/admin/api/kyc',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCSRFToken(),
    },
});

export type KycStatus = 'pending' | 'verified' | 'rejected';

export interface KycProfileRow {
    id: string;
    tenant_id: string;
    tenant_name: string | null;
    tenant_slug: string | null;
    tenant_status: string | null;
    legal_name: string;
    nationality: string | null;
    phone: string | null;
    address: string | null;
    /** Si el comerciante aportó la foto del documento. La foto en sí no viaja. */
    has_document: boolean;
    status: KycStatus;
    rejection_reason: string | null;
    reviewed_at: string | null;
    reviewed_by: string | null;
    created_at: string | null;
}

export interface KycPagination {
    total: number;
    current_page: number;
    per_page: number;
    last_page: number;
}

export interface KycMetrics {
    pending_count: number;
    verified_count: number;
    rejected_count: number;
}

export interface KycListResult {
    profiles: KycProfileRow[];
    pagination: KycPagination;
    metrics: KycMetrics;
}

/**
 * Otra tienda que comparte cédula o RIF con el expediente consultado.
 *
 * Tener dos tiendas **no es una falta**. Esto informa a quien decide; no bloquea a nadie.
 */
export interface KycIdentityMatch {
    tenant_id: string;
    tenant_name: string | null;
    tenant_status: string | null;
    legal_name: string;
    kyc_status: KycStatus;
    created_at: string | null;
}

export interface KycReviewResult {
    id: string;
    status: KycStatus;
    rejection_reason: string | null;
    reviewed_at: string | null;
    reviewed_by: string | null;
}

interface AdminApiResponse<T = unknown> {
    status: 'success' | 'error';
    code: number;
    message: string;
    data: T;
}

const AdminKycServices = {
    listar: async (params: { status?: string; search?: string; page?: number; per_page?: number } = {}) => {
        const response = await axiosKyc.get<AdminApiResponse<KycListResult>>('/profiles', { params });

        return response.data;
    },

    /**
     * Verificar abre la puerta del retiro. Rechazar **exige motivo**: el backend devuelve 422
     * sin él, y con razón — el comerciante necesita saber qué corregir.
     */
    revisar: async (profileId: string, body: { approved: boolean; reason?: string }) => {
        const response = await axiosKyc.post<AdminApiResponse<KycReviewResult>>(`/profiles/${profileId}/review`, body);

        return response.data;
    },

    coincidenciasDeIdentidad: async (profileId: string) => {
        const response = await axiosKyc.get<AdminApiResponse<KycIdentityMatch[]>>(`/profiles/${profileId}/identity-matches`);

        return response.data;
    },
};

export default AdminKycServices;
