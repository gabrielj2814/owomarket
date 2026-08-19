# Reglas del Proyecto

1. **Planificación Obligatoria y Aprobación Previa**:
   Antes de realizar cualquier cambio en el código, ejecutar comandos de modificación o implementar nuevas funcionalidades, se debe presentar una planificación detallada de los cambios propuestos. **No se procederá con ninguna ejecución ni modificación hasta recibir la aprobación explícita del usuario.**

2. **Testing Obligatorio, Commits y Push a Origin**:
   Tras implementar y validar que todas las pruebas pasen al 100% (`php artisan test` y `npm run types` con 0 errores), se debe crear el commit correspondiente siguiendo Conventional Commits y ejecutar inmediatamente `git push origin <rama_actual>`.

3. **Ubicación de Planes**:
   Todos los planes de trabajo maestros y especificaciones deben crearse y residir exclusivamente dentro del directorio `planes/`.
