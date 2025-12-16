import CurrencyDB from "../CurrencyDB"

export default interface Tenant {
       id:          string
       name:        string
       slug:        string
       status:      string
       timezone:    string
       currency:    CurrencyDB
       request:     string
       created_at:  DateDB
       updated_at:  DateDB
       deleted_at:  DateDB


}
