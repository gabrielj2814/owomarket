- Roles:
  - super_admin -> central
  - tenant_owner -> central
  - customer  -> central
  - owner -> tenant
  - admin -> tenant
  - staff -> tenant

- validaciones login:
  - validaciones en el login 
    - los usuarios inactivos no pueden entrar

- listar los tenants del owner en su vista principal al usar el login central
- login tenancy funcional pero con el diseño del login staff hay que usar el diseño tenant
- que el owner pueda crear otros tenant (vista)
- el owner podra dar de baja sus tenant (vista)
  - acceso desde login
    - funcional
    - usar el disño que le corresponde
- pantalla de perfil funcional (vista)

baja prioridad
- trabajar en el perfil de usuario
  - cambiar avartar de usuario (endpoint)
  - interfaz (4)
    - datos personales
    - clave
    - dar de baja
- remover el softdelete en tenant y user y manejar estados para el tema de la visibilidad 

validaciones:
- validar que el numero de telefono no este en uso
