import Tenant from "../models/Tenant";
import TenantUser from "../models/TenantUser";

export default interface ResponseOKSendRequestCreateTenant {
    tenant: Tenant,
    tenant_user: TenantUser
}
