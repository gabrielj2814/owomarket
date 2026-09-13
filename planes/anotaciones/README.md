# Anotaciones: el porqué, y las referencias

**Esta carpeta no se borra aunque su contenido parezca antiguo.** Los comentarios del código
citan hallazgos por número —«hallazgo N35», «hallazgo A3», «hallazgo T1»— y esos números viven en
las auditorías de aquí. Sin ellas, decenas de comentarios quedan huérfanos.

> Los enlaces son relativos a propósito: los de antes eran `file:///c:/laragon/...` y solo
> funcionaban en una máquina.

---

## Decisiones y arquitectura

- **[DECISION_GARANTIAS_Y_RESPONSABILIDAD.md](DECISION_GARANTIAS_Y_RESPONSABILIDAD.md)** — el
  documento más importante del proyecto. Quién responde ante el comprador, el fondo retenido, la
  reputación de tres niveles, las cinco capas de escalado y el mapa de los cinco subsistemas.
  **Casi todo lo que parece raro en el código está explicado aquí.**
- **[CONTEXTO_GLOBAL_PROYECTO_OWOMARKET.md](CONTEXTO_GLOBAL_PROYECTO_OWOMARKET.md)** — panorama
  general: stack, bases duales, hub del inquilino, SSO.
- **[ARQUITECTURA_FLUJO_COMPRA_CLIENTES_MARKETPLACE_Y_SSO.md](ARQUITECTURA_FLUJO_COMPRA_CLIENTES_MARKETPLACE_Y_SSO.md)**
  — la experiencia del comprador central, carrito unificado y checkout multi-tienda.
- **[PUNTOS_CLAVE_FACTURACION.md](PUNTOS_CLAVE_FACTURACION.md)** — reglas fiscales, tasa BCV,
  facturas en PDF y divisas.

## Referencias vivas, para consultar mientras se trabaja

- **[PRUEBA_MANUAL_CORREO.md](PRUEBA_MANUAL_CORREO.md)** — los trece flujos de correo, cómo
  provocar cada uno y qué comprobar dentro. Con plantilla para reportar.
- **[FLUJOS_DE_CORREO.md](FLUJOS_DE_CORREO.md)** — el registro de qué envía correo y cuál está
  verificado.
- **[ENTORNO_DE_TESTS.md](ENTORNO_DE_TESTS.md)** — por qué los tests corren en SQLite y qué se
  rompe por ello.

## Historia: de dónde salen los «hallazgos»

Auditorías de agosto de 2026. No se leen de corrido; se consultan cuando un comentario cita un
número.

`AUDITORIA_BUGS_2026_08_21` · `AUDITORIA_AUTH_2026_08_22` · `AUDITORIA_PANELES_2026_08_22` ·
`AUDITORIA_BACKEND` · `AUDITORIA_DOCKER_2026_08_23` · `AUDITORIA_PORTAL_CLIENTE_2026_08_23` ·
`AUDITORIA_SIGNUP_2026_08_23` · `BARRIDO_COMENTARIOS_2026_08_23` · [`por_revisar.md`](por_revisar.md)

> `por_revisar.md` tiene sus cuatro puntos resueltos, y se conserva por lo que enseña: **dos de
> los cuatro estaban mal diagnosticados**, y fue levantar la aplicación lo que los desmintió — no
> leer el código ni mirar los tests.
