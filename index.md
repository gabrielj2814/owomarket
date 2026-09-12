# Qué falta en OwoMarket

> **Última actualización:** 12/09/2026 · Rama `moduleProduct`
> **Estado de la suite:** 863 tests de backend, 87 de frontend, `tsc --noEmit` limpio.
>
> Este fichero es el punto de entrada: qué queda, por qué importa y dónde está escrito.
> Está ordenado por lo que conviene hacer antes, no por tamaño.

---

## Por dónde seguir mañana

**Lo que más rinde ahora son las notificaciones**, y no es la tarea más vistosa.

Los cinco subsistemas de garantías están construidos y **todos dependen de que alguien se
entere**: que la tienda sepa que tiene una reclamación con un reloj corriendo, que el comprador
sepa que le respondieron, que el comerciante sepa que le verificaron el KYC. Hoy cada actor
tiene que entrar a mirar por si acaso — y el reloj de reclamaciones corre igual.

→ [`planes/futuros/PLAN_NOTIFICACIONES.md`](planes/futuros/PLAN_NOTIFICACIONES.md)

La alternativa razonable es cerrar el subsistema 5 del todo con la fase 2 del escaparate, que
es lo único que impide a un comprador de tienda reclamar.

---

## 🔴 Lo que deja un flujo a medias

### Fase 2 del escaparate: el comprador de una tienda no puede reclamar

Desde el 12/09/2026 ya ve sus pedidos y confirma la entrega. **Reclamar, no.**
`CreateCustomerReturnRequestUseCase` busca el pedido en `CentralOrder`, y un pedido de
escaparate vive en la base del inquilino.

Es un cambio en el corazón del subsistema 5, así que merece su propio ciclo de diseño.

→ [`planes/por_hacer/PLAN_PEDIDOS_ESCAPARATE.md`](planes/por_hacer/PLAN_PEDIDOS_ESCAPARATE.md)

### El techo mensual de alarma no existe

La decisión de garantías dice que superar un gasto mensual en coberturas **no corta los pagos**:
dispara una revisión. Se registra cuánto pone la plataforma en cada reclamación
(`platform_covered_amount`) pero **nadie suma ni avisa**. Sin un umbral configurable, el mes se
descontrola antes de que alguien lo mire.

→ [`planes/por_hacer/ESTADO_Y_PENDIENTES.md`](planes/por_hacer/ESTADO_Y_PENDIENTES.md)

---

## 🟡 Deuda con fecha de caducidad

### Los datos de identidad del expediente, pendientes del abogado

El expediente de reclamación entrega **todos** los datos del comerciante, cédula y RIF
incluidos. Fue una decisión explícita de fase de desarrollo, y **hay que resolverla antes de
producción**.

Cuando vuelvas con la lista de campos a quitar, el único sitio que tocar es el bloque `store` de
`BuildClaimDossierUseCase`. Todo lo relacionado lleva la marca `pendiente-abogado:` — hoy son
7 apariciones:

```bash
grep -rn "pendiente-abogado:" src/ tests/ resources/
```

La segunda pregunta para el abogado sigue abierta y sin nada construido encima: **qué dice la
ley venezolana sobre la responsabilidad solidaria de un marketplace** que cobra todas las
ventas.

### Los pedidos de invitado anteriores al 12/09/2026 son huérfanos

Nadie puede demostrar que son suyos, así que ni se ven, ni se confirman, ni se reclamarán. Los
nuevos sí quedan enlazados si se compra con sesión, y el checkout lo advierte antes de pagar.

En desarrollo da igual. **Antes de que haya compras reales, no.**

---

## 🟡 Migración a Flowbite

**56 páginas de 83 ya lo usan**, y 21 componentes de 33. `reglas.md` §1.3 lo exige.

El portal del cliente ya tiene su tema (`portalTheme`), así que sus 9 pantallas restantes son
mecánicas. **El escaparate y el panel del comerciante necesitan decidir su tema antes de
tocarlos** — si no, cada pantalla se migrará con su propio `className` y habremos repetido el
problema que el tema viene a resolver. Y el escaparate es la cara pública: cómo se ve es una
decisión de producto.

Aparte: `AdminMasterBrandsPage`, `AdminMasterCategoriesPage` y `AdminHomeBannersPage` tienen el
botón de borrar **sin relleno** por usar `color="failure"`, que no existe como color de botón en
esta versión de Flowbite. Un minuto cada uno.

→ [`planes/por_hacer/PLAN_MIGRACION_FLOWBITE.md`](planes/por_hacer/PLAN_MIGRACION_FLOWBITE.md)

---

## 🟢 Decisiones tomadas y no aplicadas

Las tres están razonadas en [`DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`](planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md)
y resumidas en [`ESTADO_Y_PENDIENTES.md`](planes/por_hacer/ESTADO_Y_PENDIENTES.md):

- **Suspender el escaparate como sanción.** `tenants.status` ya admite `suspended` y la
  reputación ya identifica a quién, pero nada los conecta. Conviene pensarlo despacio:
  suspender a una tienda que debe dinero **garantiza que no lo pague nunca**, porque la deuda se
  salda vendiendo.
- **Bloquear la reapertura por identidad.** La búsqueda encuentra las tiendas que comparten
  cédula, pero nada impide abrir otra. Es deliberado —informa, no bloquea—; el día que se
  automatice, la pieza que falta es un aviso en el alta de tienda.
- **Velocidad de liberación por nivel.** Se implementó solo el porcentaje de retención, a
  propósito. Anotado como decisión, no como olvido.

---

## ⚪ Riesgos anotados, no resueltos

- **Los tests corren en SQLite y producción es MySQL.** Ya costó dos incidentes de
  identificadores demasiado largos. Lo cubre `MigrationIdentifierLengthTest`, pero es una grieta
  con una sola tabla. → [`planes/anotaciones/ENTORNO_DE_TESTS.md`](planes/anotaciones/ENTORNO_DE_TESTS.md)
- **La reputación se deriva en cada consulta** (dos `COUNT`). Barato donde se usa hoy; un N+1 si
  alguna pantalla llega a listar doscientas tiendas.
- **El camino `payout` de `GenerateTenantCommissionSettlementUseCase`** sigue con su
  `max(0.0, …)` y crea liquidaciones en USD que el saldo no cuenta. No mueve dinero hoy.

Los atajos deliberados están marcados en el código:

```bash
grep -rn "ponytail:" src/ resources/
```

---

## 📋 Planes que conviene revisar antes de usarlos

Dos planes de `planes/futuros/` parecen **superados por trabajo ya hecho**. No los borro sin que
alguien lo confirme, pero no los empieces sin mirarlos:

- [`PLAN_RESOLUCION_DEVOLUCIONES.md`](planes/futuros/PLAN_RESOLUCION_DEVOLUCIONES.md) — pedía
  «que una devolución tenga quién la reciba y la resuelva». Eso es exactamente lo que construyó
  el subsistema 5, fase A.
- [`PLAN_REGISTRO_AUDITORIA.md`](planes/futuros/PLAN_REGISTRO_AUDITORIA.md) — la pista de
  auditoría ya existe, con su pantalla en el backoffice.

Sin tocar y sin empezar: [`PLANIFICACION_MODULOS_AVANZADOS_TENANT.md`](planes/por_hacer/PLANIFICACION_MODULOS_AVANZADOS_TENANT.md)
(6 módulos del backoffice del inquilino) y [`PLAN_HISTORIAL_STOCK.md`](planes/futuros/PLAN_HISTORIAL_STOCK.md).

---

## ✅ Lo que se cerró el 11 y 12 de septiembre

Las seis vistas de [`PLAN_VISTAS_PENDIENTES.md`](planes/por_hacer/PLAN_VISTAS_PENDIENTES.md),
más tres fallos que no estaban en ningún plan y solo aparecieron al ejecutar la aplicación:

| # | Vista | Lo que desbloqueó |
| :--- | :--- | :--- |
| 1 | Revisión de KYC (admin) | **Ninguna tienda podía cobrar**: el caso de uso no tenía ruta |
| 2 | Reclamaciones de la tienda | El reloj **aprobaba todo por silencio**; no había dónde responder |
| 3 | Reputación en la billetera | El comerciante veía bajar su saldo sin saber por qué |
| 4 | Estado de la reclamación (comprador) | Veía «Rechazada» sin el motivo, que el backend ya guardaba |
| 5 | Expediente de reclamación + PDF | El caso de uso era inalcanzable por partida doble |
| 6 | Pedidos del escaparate (fase 1) | **Toda venta de tienda se liberaba por silencio**: nadie podía confirmar |

Los tres fallos encontrados de paso:

- **El KYC estaba roto por los dos extremos**: la tarjeta con la que el comerciante *envía* su
  identidad pedía una URL sin el prefijo `tenant`. 404 siempre, tragado por su `catch`.
- **`color="failure"` no existe como color de botón** en esta versión de Flowbite, así que
  varios botones primarios se pintaban sin relleno.
- **El contenedor `app` no resolvía su propio dominio**, de modo que era imposible entrar en la
  aplicación desde Docker. Resuelto con un alias de red en `docker-compose.yml`.

---

## Lo que hay que leer antes de tocar código

- [`reglas.md`](reglas.md) — obligatorio. Servicios para toda llamada HTTP, Flowbite en todas
  las vistas, arquitectura hexagonal, tests antes del commit, planes en `planes/`.
- [`planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`](planes/anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md)
  — el porqué de los cinco subsistemas. Casi todo lo que parece raro está explicado aquí.
- [`planes/por_hacer/PLAN_EJECUCION_MIGRACIONES_Y_SEEDERS.md`](planes/por_hacer/PLAN_EJECUCION_MIGRACIONES_Y_SEEDERS.md)
  — runbook para reconstruir el entorno desde cero.
