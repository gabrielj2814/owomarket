import CurrencyDB from "../CurrencyDB"
import Domain from "./domain"
import { TenantOwner } from "./TenantOwner"

export default interface Tenant {
       id:              string
       name:            string
       slug:            string
       status:          string
       timezone:        string
       currency:        CurrencyDB
       owners?:         TenantOwner[]
       request:         string
       created_at:      DateDB
       updated_at:      DateDB
       deleted_at:      DateDB
       domain?:         Domain


}
