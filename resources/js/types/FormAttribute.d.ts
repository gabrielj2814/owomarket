export interface FormAttributeValue {
    id?: string;
    value: string;
    color?: string | null;
    image?: string | null;
    position?: number;
}

export interface FormAttribute {
    name: string;
    slug?: string | null;
    type?: 'select' | 'color' | 'button' | 'radio';
    is_filterable?: boolean;
    is_visible?: boolean;
    position?: number;
    values?: FormAttributeValue[];
}
