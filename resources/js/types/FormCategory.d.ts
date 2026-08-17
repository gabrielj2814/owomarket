export interface FormCategory {
    id?: number | null;
    name: string;
    slug?: string;
    description?: string | null;
    image?: string | null;
    parent_id?: number | null;
    is_active?: boolean;
    position?: number;
}
