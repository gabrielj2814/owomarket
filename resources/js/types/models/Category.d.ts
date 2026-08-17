export interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image: string | null;
    parent_id: number | null;
    is_active: boolean;
    position: number;
    created_at: string | null;
    updated_at: string | null;
    children?: Category[];
}
