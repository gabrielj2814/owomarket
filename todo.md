- Roles:
  - [x] super_admin -> central
  - [x] tenant_owner -> central
  - [x] customer  -> central 
  - [x] owner -> tenant
  - admin -> tenant
  - staff -> tenant

por hacer
- validaciones login
  - los usuarios inactivos no pueden entrar
  - denegar el acceso al login del tenant si el tenant esta en los siguientes estados (rechazado, en progreso, inactivo)
  
baja prioridad
- remover el softdelete en tenant y user y manejar estados para el tema de la visibilidad 

implementar:
- implementar SSO propio para que el cliente pueda navegar entre sub dominios sin teneque que crear cuentas por cada tienda
- docker
- kunbernetes
- ci/cd

lista de validaciones:
- validar que el numero de telefono no este en uso
- validar que el email no este en uso
- en la pantalla de crear cuanta para el owner y la modal al ingresar el nombre del negocio que el campo de slug del dominio se contralla a medida que escribe el nombre del negocio
- validaciones de clave al usar el login y al crear la cuenta del tenant
- email unicos
- telefono unico
