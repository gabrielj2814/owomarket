- Roles:
  - super_admin -> central
  - tenant_owner -> central
  - customer  -> central
  - owner -> tenant
  - admin -> tenant
  - staff -> tenant

- agregar que el VO Slug reciba por parametro el dominio ya que ahorita lo esta obteniendo por la función config
- docker
- kunbernetes
- perfil

- validaciones login:
  - los usuarios inactivos no pueden entrar
  - denegar el acceso al login del tenant si el tenant esta en los siguientes estados (rechazado, en progreso, inactivo)

baja prioridad
- trabajar en el perfil de usuario
  - cambiar avartar de usuario (endpoint)
  - interfaz (4)
    - datos personales
    - clave
    - dar de baja
- remover el softdelete en tenant y user y manejar estados para el tema de la visibilidad 

validaciones por hacer:
- validar que el numero de telefono no este en uso
- el campo donde ingresa el nombre del tenant contruirte el slug de su negocio
- validaciones de clave al usar el login y al crear la cuenta del tenant
- email unicos
- telefono unico

implementar:
- docker
- kunbernetes
- ci/cd
