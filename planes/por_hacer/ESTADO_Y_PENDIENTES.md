# Estado del proyecto y lo que falta

> **Fecha:** 11/09/2026 · Tras cerrar los cinco subsistemas de
> [`DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`](../anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md)
>
> Escrito para retomar sin contexto previo. Lo urgente va primero y separado de lo importante.

---

## 🔴 Roto ahora mismo

### 1. Ninguna tienda puede retirar dinero

El KYC exige verificación para solicitar un retiro, y **no existe ninguna forma de verificar a
nadie**: `ReviewTenantKycUseCase` no tiene controlador ni ruta. Todos los expedientes se quedan
en `pending`.

**Arreglo definitivo:** vista 1 de [`PLAN_VISTAS_PENDIENTES.md`](PLAN_VISTAS_PENDIENTES.md).

**Mitigación inmediata**, si hace falta que alguien cobre antes:

```bash
docker compose exec -T app php artisan tinker --execute="
Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile::where('tenant_id','EL_TENANT')
  ->update(['status'=>'verified','reviewed_at'=>now(),'reviewed_by'=>'manual']);"
```

### 2. El reloj de reclamaciones aprueba todo por silencio

`returns:auto-resolve` corre a diario y resuelve a favor del comprador lo que la tienda no
responde en 5 días. **No hay pantalla donde responder**, así que toda reclamación se aprueba
sola y revierte la venta.

**Mitigación:** subir `central_claim_response_days` en los ajustes de cobro hasta que exista la
vista 2.

### 3. Las tiendas sembradas no tienen KYC

`TenantDemoDataSeeder` no crea perfiles de verificación, así que en un entorno recién sembrado
**ninguna de las nueve tiendas puede retirar**. Conviene añadirlos al seeder, ya verificados: es
dato de demostración y el flujo de retiro no se puede probar sin ellos.

---

## ✅ Lo que está hecho y verificado

| # | Subsistema | Qué quedó |
| :--- | :--- | :--- |
| 1 | **KYC del comerciante** | Cédula y RIF cifrados con hash buscable; exigido para retirar |
| 2 | **Garantía por producto** | `warranty_days`, propagado al catálogo central y visible en la ficha |
| 3 | **Entrega verificada** | El comprador confirma y libera; el plazo libera por silencio; evidencias de las dos partes |
| 4 | **Fondo de garantía** | 10% retenido 60 días, visible en la wallet, variable por reputación |
| 5 | **Reclamaciones** | Resolución, reloj, cobertura con tope, reputación y expediente |

**826 tests de backend, 33 de frontend**, `tsc --noEmit` limpio.

El **hueco 2** que abrió toda esta línea de trabajo —la deuda irrecuperable de un reembolso tras
un retiro pagado— queda cerrado: el fondo hace que no pueda existir por construcción.

---

## Falta interfaz, no lógica

Seis vistas, detalladas en [`PLAN_VISTAS_PENDIENTES.md`](PLAN_VISTAS_PENDIENTES.md). Tres de
ellas necesitan además su controlador y su ruta, que tampoco existen:

- `ReviewTenantKycUseCase` y `FindTenantsByIdentityUseCase` (administrador)
- `BuildClaimDossierUseCase` (administrador)
- `TenantReputation::progress()` (comerciante, añadir a `wallet-summary`)

---

## Decisiones tomadas que todavía no se han aplicado

### El techo mensual de alarma

La decisión dice que superar un gasto mensual en coberturas **no corta los pagos**: dispara una
revisión. Hoy se registra cuánto pone la plataforma en cada reclamación
(`platform_covered_amount`) pero **nadie suma ni avisa**.

Falta un umbral configurable y algo que notifique al superarlo. Sin esto, el mes se descontrola
antes de que nadie lo mire.

### La velocidad de liberación por nivel

La decisión menciona liberación «rápida / estándar / lenta» según reputación. Se implementó solo
el porcentaje de retención, a propósito: el porcentaje ya hace el trabajo y variar también los
días exige tocar `central_payout_hold_days` por otro camino.

**Anotado como decisión, no como olvido.** Si algún día se retoma, que sea porque los datos
digan que el porcentaje solo no basta.

### La suspensión del escaparate como sanción

La capa 4 del escalado contempla suspender a la tienda que acumula reclamaciones sin responder.
`tenants.status` ya tiene `suspended` y el nivel de reputación ya identifica a quién, pero nada
los conecta.

Conviene pensarlo despacio: suspender a una tienda que debe dinero **garantiza que no lo pague
nunca**, porque la deuda se salda vendiendo.

### El bloqueo de reapertura por identidad

`FindTenantsByIdentityUseCase` encuentra las tiendas que comparten cédula o RIF, pero **nada
impide** abrir una nueva. Es deliberado —informa a quien decide, no bloquea— pero el día que se
quiera automatizar, la pieza que falta es un aviso en el alta de tienda.

---

## La revisión con fecha

La decisión fijó una revisión **a los 90 días o a las primeras N ventas entregadas**, lo que
llegue antes. Los tres campos de medición ya se capturan, así que esa revisión podrá responder:

| Pregunta | De dónde sale |
| :--- | :--- |
| ¿El 10% / 60 días fue correcto? | `platform_covered_amount` frente a `reserve_amount` |
| ¿Cuántos compradores confirman de verdad? | `order_delivery_confirmations.released_by` |
| ¿Cuánto tarda una tienda en responder? | `resolved_at` menos `created_at` |
| ¿Cuántos días después de recibir se reclama? | `delivered_at` frente a `created_at` |

**Los porcentajes son configurables y NO retroactivos**: lo ya retenido se rige por la regla
vigente cuando se vendió. Ajustarlos no mueve el saldo de nadie hacia atrás.

---

## Riesgos conocidos, anotados para que no sorprendan

**Los tests corren en SQLite y producción es MySQL.** Ya costó dos incidentes de identificadores
demasiado largos. Lo cubre `MigrationIdentifierLengthTest`, pero es una grieta con una sola
tabla: hay más diferencias entre los dos motores que ese test no ve.

**La reputación se deriva en cada consulta.** Dos `COUNT` por cálculo, barato donde se usa
—liberar una venta—. Si una pantalla llega a listar doscientas tiendas con su nivel, eso es un
N+1 y habrá que cachear. Anotado en el código con `ponytail:`.

**El camino `payout` de `GenerateTenantCommissionSettlementUseCase` sigue con su `max(0.0, …)`**
y crea liquidaciones en USD que el saldo no cuenta. No mueve dinero hoy; si alguna vez se
unifica la moneda de las liquidaciones, hay que resolverlo antes.

**Las reclamaciones del escaparate están modeladas pero son inalcanzables.** Sin vista de pedidos
para el comprador de tienda, no tiene dónde reclamar. Es la puerta trasera que quedó entornada.

---

## Lo pendiente con abogado

Dos preguntas concretas, sin respuesta y sin las cuales no conviene publicar términos:

1. **¿Qué datos de identidad de un comerciante se le pueden entregar a un comprador** que
   denuncia, y cuáles solo a la autoridad?
2. **¿Qué dice la ley venezolana sobre la responsabilidad solidaria de un marketplace** que cobra
   todas las ventas? Ofrecer cobertura propia con tope acerca todavía más a la figura de
   vendedor.

**Lo decidido en este proyecto sirve para construir, no para blindarse.**

---

## Otros planes vivos

- [`PLAN_REEMBOLSO_TRAS_RETIRO.md`](PLAN_REEMBOLSO_TRAS_RETIRO.md) — cerrado, se conserva por la
  lección: un plan que razonaba sobre el código y dos sondas de veinte líneas tumbaron su
  premisa.
- [`PLAN_EJECUCION_MIGRACIONES_Y_SEEDERS.md`](PLAN_EJECUCION_MIGRACIONES_Y_SEEDERS.md) — runbook
  para reconstruir el entorno desde cero.
- [`planes/futuros/PLAN_NOTIFICACIONES.md`](../futuros/PLAN_NOTIFICACIONES.md) — **sube de
  prioridad**. Todo lo construido depende de que alguien se entere: que la tienda sepa que tiene
  una reclamación con un reloj corriendo, que el comprador sepa que le respondieron, que el
  comerciante sepa que le verificaron el KYC. Sin notificaciones, cada actor tiene que entrar a
  mirar por si acaso.
