import { ProductAttributeValue } from './ProductAttributeValue';

export interface ProductAttribute {
    id: string;
    name: string;
    slug: string;
    type: 'select' | 'color' | 'button' | 'radio';
    is_filterable: boolean;
    is_visible: boolean;
    position: number;
    values?: ProductAttributeValue[];
    created_at?: string;
    updated_at?: string;
}
