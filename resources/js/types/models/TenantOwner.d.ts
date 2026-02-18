import DateDB from "../DateDB"

export interface TenantOwner {
   id:          string
   name:        string
   email:       string
   phone:       string
   type:        string
   avatar:      string
   is_active:   boolean
   created_at:  DateDB
   updated_at?:  DateDB
}
