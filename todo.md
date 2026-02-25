- Roles:
  - [x] super_admin -> central
  - [x] tenant_owner -> central
  - [x] customer  -> central 
  - [x] owner -> tenant
  - admin -> tenant
  - staff -> tenant
  - customer -> tenant (sin password)

por hacer
- validaciones login
  - ya muestra la pantalla 403 pero hay que agregarle estilos a la vista
- trello del proyecto para mejorar el flujo de desarrollo del proyecto
  - anotoar los pendientes y las implementaciones en el trellos
  - definir los siguiente modulos en el trello
  
tenant modules:
- product 
- categories
- CRM
  - clients
- orders
  - new
  - status
- invoices 
  
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
