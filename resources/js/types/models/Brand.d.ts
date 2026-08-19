export interface Brand {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    logo: string | null;
    is_active: boolean;
    position: number;
    created_at?: string;
    updated_at?: string;
}
