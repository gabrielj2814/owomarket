export interface Auth {
    user: User;
}

export interface Tenant {
    id: string;
    name: string;
    [key: string]: unknown;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    tenant: Tenant | null;
    current_domain: string;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
