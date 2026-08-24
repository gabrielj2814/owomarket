# Planificaciones Futuras

En esta carpeta se almacenan las especificaciones, ideas y planes a futuro que todavía no se van a implementar.
Cuando se apruebe su desarrollo, se moverán a `planes/por_hacer/`.

---

## Índice

### Propuestos el 23/08/2026, a partir de las auditorías

Los cuatro salen de leer el código, no de una lista genérica de comercio electrónico. Cada
uno lleva dentro la evidencia que lo justifica.

| Orden | Plan | Audiencia | Por qué |
| :--- | :--- | :--- | :--- |
| 1 | [Resolución de devoluciones](PLAN_RESOLUCION_DEVOLUCIONES.md) | Comerciante · Cliente | **No es funcionalidad nueva.** El cliente ya crea devoluciones y nadie las resuelve: entran en la tabla y se quedan ahí |
| 2 | [Módulo de notificaciones](PLAN_NOTIFICACIONES.md) | Las tres | Nadie se entera de nada. Multiplica el valor de los otros tres y de lo ya construido |
| 3 | [Registro de auditoría](PLAN_REGISTRO_AUDITORIA.md) | Administrador | La tabla existe y se escribe desde **un** sitio. Aprobar retiros o suspender tiendas no deja rastro |
| 4 | [Historial de movimientos de stock](PLAN_HISTORIAL_STOCK.md) | Comerciante | Cinco caminos escriben sobre el mismo número y ninguno deja constancia |

**El orden importa.** Los dos primeros porque hay **promesas ya hechas al usuario que hoy no
se cumplen**; los dos últimos porque son lo que hará falta el día que algo no cuadre y haya
que explicar por qué.

---

## Lo que se decidió NO proponer

Se anota para que nadie lo vuelva a plantear sin saber por qué se descartó.

### Pasarelas de pago automáticas

Es lo que pide el instinto al abrir `src/Payment/`, y por eso conviene decirlo: **ya hay una
capa de pasarelas escrita que no participa en ningún cobro real** (hallazgo **PY1**, abierto).
Los cuatro adaptadores sólo los alcanza un endpoint que ninguna página llama, y nada escribe
en la tabla `payments`.

Construir integraciones nuevas encima sería añadir una **tercera** implementación a un módulo
que ya tiene dos que no se hablan. Primero hay que decidir qué se hace con la que existe:
borrarla o cablearla.

### Más pantallas para el cliente

Su portal está completo: pedidos, facturas, direcciones, favoritos, reseñas, devoluciones,
cupones y perfil. **El problema no es que le falten pantallas — es que dos de las que tiene no
llevan a ninguna parte** (las devoluciones sin resolver, y los cupones, que hasta el hallazgo
C1 anunciaban códigos que el checkout rechazaba).

Añadir superficie antes de que la existente cumpla lo que promete es cómo se llegó hasta aquí.
