export interface ErrorsFormAttribute {
    name?: string[];
    slug?: string[];
    type?: string[];
    is_filterable?: string[];
    is_visible?: string[];
    position?: string[];
    values?: string[];
    [key: string]: string[] | undefined;
}
