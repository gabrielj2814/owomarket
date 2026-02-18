import DateDB from "../DateDB";

export default interface TenantUser {
    id: string,
    id_tenant: string,
    id_user: string,
    role: string,
    permissions: string[],
    created_at: DateDB,
    updated_at?: DateDB
}
