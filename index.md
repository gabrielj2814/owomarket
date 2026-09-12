# Qué falta en OwoMarket

> **Última actualización:** 12/09/2026 · Rama `moduleProduct`
> **Estado de la suite:** 891 tests de backend, 103 de frontend, `tsc --noEmit` limpio.
> **Flowbite:** 81/83 páginas y 30/34 componentes.
>
> Este fichero es el punto de entrada: qué queda, por qué importa y dónde está escrito.
> Está ordenado por lo que conviene hacer antes, no por tamaño.

---

## Por dónde seguir mañana

**Lo que más rinde ahora son las notificaciones**, y ya no compite con nada.

El subsistema 5 quedó cerrado el 12/09/2026: un comprador de tienda ya puede reclamar. Lo que
falta ahora no es una pieza del flujo, es que **alguien se entere** de que el flujo ocurrió.

Los cinco subsistemas de garantías están construidos y todos dependen de eso: que la tienda sepa
que tiene una reclamación con un reloj corriendo, que el comprador sepa que le respondieron, que
el comerciante sepa que le verificaron el KYC. Hoy cada actor tiene que entrar a mirar por si
acaso — y el reloj de reclamaciones corre igual.

→ [`planes/futuros/PLAN_NOTIFICACIONES.md`](planes/futuros/PLAN_NOTIFICACIONES.md)

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

Y hay una tercera desde el 12/09/2026: la ventana para reclamar bajó a **14 días**, y hace falta
saber **si la ley exige un plazo mínimo mayor**. Si lo exige, se sube desde Reglas de garantía
sin tocar código — para eso es un ajuste.

### Los pedidos de invitado anteriores al 12/09/2026 son huérfanos

Nadie puede demostrar que son suyos, así que ni se ven, ni se confirman, ni se reclamarán. Los
nuevos sí quedan enlazados si se compra con sesión, y el checkout lo advierte antes de pagar.

En desarrollo da igual. **Antes de que haya compras reales, no.**

---

## ✅ El techo mensual, y los ocho ajustes que gobiernan el dinero

El techo existe: `MonthlyCoverageSpend` suma lo que ha puesto la plataforma mes a mes, **en
dólares y convertido fila a fila** con la tasa congelada de cada venta. En bolívares el umbral
se habría desactivado solo con la inflación, y la suma se habría quedado corta siempre.

No hay comando ni tabla: se deriva de datos que ya no se mueven, así que **un mes que se pasó
sigue viéndose después aunque nadie mirara ese día**.

Y al ir a añadir su ajuste apareció algo más grande: **ninguno de los ocho números que gobiernan
el dinero tenía campo en ninguna pantalla**. Se cambiaban escribiendo en la base de datos, sin
validación. El peor era `central_guarantee_reserve_percent`: con solo existir esa fila, el
sistema de reputación deja de aplicarse a todas las tiendas — y no había dónde verlo. Ahora hay
pantalla (**Reglas de garantía**), validación, y ese aviso escrito junto al campo.

De paso cambiaron dos valores por defecto: **ventana para reclamar 60 → 14 días** y **fondo
retenido 60 → 30**. La reserva solo tiene que cubrir el plazo de reclamar más el de respuesta;
retener más era quedarse el dinero del comerciante cuando reclamar ya era imposible.

→ [`planes/por_hacer/ESTADO_Y_PENDIENTES.md`](planes/por_hacer/ESTADO_Y_PENDIENTES.md)

**Lo que no hace:** empujar. El número está donde el administrador ya entra, pero nada obliga a
mirarlo. Eso es de las notificaciones.

---

## ✅ Subsistema 5, cerrado — el comprador de una tienda ya reclama

Era el último flujo cortado: desde la fase 1 el comprador veía su pedido y confirmaba la
entrega, y ahí se le acababa el camino. Podía dar por recibido un producto roto y no tenía
dónde decirlo.

El plan decía que generalizarlo era «un cambio en el corazón del subsistema 5». **No lo era.**
Nada aguas abajo vuelve a buscar el pedido central: la reversión de comisión, la cobertura, el
expediente y la reputación trabajan todos sobre `tenant_order_id`. Todo el acoplamiento vivía
dentro del caso de uso que crea la reclamación, y salió con un localizador y dos adaptadores.

De paso se cerró **un agujero que estaba abierto en el portal central**: el filtro que solo
ofrecía pedidos completados vivía en el navegador, así que contra la API se podía reclamar un
pedido recién creado y sin pagar. Ahora reclamar exige entrega declarada y estar dentro de una
ventana de 60 días (`central_claim_window_days`).

→ [`planes/por_hacer/PLAN_PEDIDOS_ESCAPARATE.md`](planes/por_hacer/PLAN_PEDIDOS_ESCAPARATE.md)

---

## ✅ Migración a Flowbite — terminada

**81 de 83 páginas y 30 de 34 componentes.** Las tres zonas tienen su propio tema
(`portalTheme`, `tenantPanelTheme`, `storefrontTheme`) y el aspecto no cambió en ninguna.

Lo que queda fuera lo está a propósito, con la razón escrita en cada fichero. Los seis botones
de borrar del backoffice que se veían como texto plano —por usar un color que no existe— están
arreglados.

→ [`planes/por_hacer/PLAN_MIGRACION_FLOWBITE.md`](planes/por_hacer/PLAN_MIGRACION_FLOWBITE.md)

**Lo que sí queda:** el escaparate se migró conservando su aspecto. El **refresco de diseño**
que se decidió hacer encima está pendiente, y necesita dirección: «refrescar» puede ser desde
afinar espaciados hasta una identidad visual nueva, y es la cara pública.

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
- **Una reclamación sobre un pedido sin comisión registrada se resuelve cubriendo cero, en
  silencio.** La tasa congelada sale de `platform_commissions`; sin fila, el tope en bolívares
  es cero. Los pedidos reales sí registran comisión, así que hoy solo se ve en datos sembrados
  a mano — pero conviene que falle ruidosamente el día que importe.

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
