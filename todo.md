<!-- npm run dev -- --host 0.0.0.0 --port 5173 -->


login api
- crear los VO que usara la entity de usuario
- crear entity de usuario con lo que usara el servicio de authentication
- consultar lo datos del usuario por email
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
- tipar con un entity de usuario la respuesta buscar por correo que te trae la api de usario




TODO: configurar todos los modelos que son exclusivos de los tenant usen el uuid



