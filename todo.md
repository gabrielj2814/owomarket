<!-- npm run dev -- --host 0.0.0.0 --port 5173 -->


login api
- crear repositori para poder consultar usuario por correo
- crear servicio donde valide la clave del usuario y genere el token si la clave es valida
- respoder con su token y su rol

login web
- crear los VO que usara la entity de usuario
- crear entity de usuario con lo que usara el servicio de authentication
- consultar lo datos del usuario por email
- crear repositori para poder consultar usuario por correo
- crear servicio donde valide la clave del usuario y genere un auth web
- respoder dependiendo del rol del usuario
  - super_admin  -> url panel admin
  - tenant owner -> url panel tenant owner
  - customer     -> url panel customer

Servicio de Auth
- tipar con un entity de usuario la respuesta buscar por correo que te trae la api de usuario

Servicio de usuario
- agregar un form request para que valide que los datos que le estan llegando sean correcto en este caso el correo del usuario
