export interface FormCustomerAddress {
    id?: string;
    type: string;
    first_name: string;
    last_name: string;
    company?: string;
    address_line_1: string;
    address_line_2?: string;
    city: string;
    state: string;
    postal_code: string;
    country: string;
    phone?: string;
    is_default: boolean;
}

export interface FormCustomer {
    name: string;
    email: string;
    phone: string;
    birth_date: string;
    gender: string;
    is_active: boolean;
    accepts_marketing: boolean;
    addresses?: FormCustomerAddress[];
}
