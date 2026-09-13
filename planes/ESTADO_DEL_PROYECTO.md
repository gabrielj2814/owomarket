# Estado de OwoMarket

> **13/09/2026** · Rama `moduleProduct` · 939 tests de backend, 115 de frontend, `tsc` limpio.
>
> **Este fichero dice QUÉ ESTÁ HECHO.** Su pareja, [`COMO_FUNCIONA.md`](COMO_FUNCIONA.md),
> dice **cómo funciona** el negocio y la aplicación: el recorrido del dinero, las garantías, la
> arquitectura y las reglas que explican el código raro. Antes de tocar nada, los dos.
>
> Sustituye a `ESTADO_Y_PENDIENTES.md` y a los 60 planes ya implementados, que se borraron al
> escribirlo — siguen en el historial de git si alguien los necesita.
>
> Lo que **no** está aquí, y no debe borrarse: `planes/anotaciones/`, que es el *porqué* de las
> decisiones y de los hallazgos que el código cita por número.

---

## 1 · Lo que ya hace

Nada de esta lista está a medias: todo tiene pantalla, ruta y tests.

### El dinero

| | |
| :--- | :--- |
| **La plataforma cobra todas las ventas** | Pago Móvil y Binance Pay, con confirmación manual del cobro |
| **Carrito multi-tienda** | Se compra de varias tiendas y se paga una sola factura |
| **Comisiones** | Configurables por plan y personalizables por tienda; se calculan, activan, liberan y revierten |
| **Tasa BCV** | Scraping automático tres veces al día, con tasa de respaldo y aviso si se queda vieja |
| **Moneda dual** | Precio en dólares, cobro en bolívares a la tasa congelada de cada venta |
| **Billetera del comerciante** | Saldo disponible, retenido y deuda; solicitud de retiro con verificación de saldo |
| **Retiros** | Aprobación y rechazo por el administrador, con comisión interbancaria |
| **Liquidaciones y notas de crédito** | Documentos de periodo cerrado; una reversión posterior entra como fila negativa |
| **Suscripciones** | Planes, cambio de plan con solicitud y aprobación, facturación B2B |

### Las garantías — los cinco subsistemas, completos

| # | Qué hace |
| :--- | :--- |
| 1 | **KYC del comerciante**: envío, revisión, cédula y RIF cifrados con hash de búsqueda. Sin verificar, no cobra |
| 2 | **KYC del comprador**: cédula exigida al reclamar, no al comprar |
| 3 | **Escrow de entrega**: el comerciante declara, el comprador confirma, y solo entonces se libera el dinero. Si nadie confirma, se libera por plazo |
| 4 | **Fondo de garantía**: un porcentaje de cada venta queda retenido 30 días, y el porcentaje lo decide la reputación de la tienda |
| 5 | **Reclamaciones**: el comprador abre, la tienda responde con reloj, el reloj resuelve el silencio a favor del comprador, y el administrador tiene expediente con PDF |

Y encima: **techo mensual de alarma** del gasto en coberturas, en dólares y convertido venta a
venta; **reputación de tres niveles** que mueve la retención; **pantalla de Reglas de garantía**
con los ocho ajustes que gobiernan el dinero.

### Los avisos

**Once eventos, tres audiencias, campana y correo.** Lo que tiene dinero o un plazo detrás sale
por correo siempre; el resto se activa desde la campana. Dos comandos diarios cubren lo periódico
—reclamación a punto de vencer, techo mensual superado— con freno para no repetirse.

### Las tres caras del producto

- **Escaparate de cada tienda**: catálogo, ficha, carrito, checkout, «mis pedidos» con
  confirmación de entrega y reclamación.
- **Marketplace central**: catálogo unificado, carrito multi-tienda, checkout, portal del
  comprador con pedidos, seguimiento, facturas, devoluciones, reseñas, favoritos y perfil.
- **Backoffice del comerciante**: productos con variantes y atributos, pedidos, envíos, cupones,
  clientes, facturación, billetera, reclamaciones, soporte.
- **Backoffice del administrador**: tiendas, KYC, pedidos globales, cobros por confirmar, retiros,
  cambios de plan, planes, reclamaciones y expedientes, catálogo maestro, moderación, CMS, tasa
  BCV, reglas de garantía, staff y permisos, pista de auditoría.

### Lo transversal

**OwO Pass** (una cuenta para todas las tiendas, con SSO de un clic), autorización por middleware
en todas las superficies, tickets de soporte con adjuntos, arquitectura hexagonal por contexto,
Flowbite en 81 de 83 páginas, y testing con Pest + Vitest.

---

## 2 · Lo que no hace todavía

Tienes razón en que «con plan escrito» y «función nueva sin empezar» son lo mismo: lo único que
cambia es si alguien ya pensó cómo hacerlo. Va todo junto, ordenado por lo que más rinde.

| Qué falta | ¿Hay plan? | Por qué importa |
| :--- | :--- | :--- |
| **Pagarle al comprador cuando gana una reclamación** | ❌ no | Aprobar una reclamación **revierte el dinero del comerciante y mide la cobertura, pero no le transfiere nada al comprador**. Hoy es un acto manual, fuera del sistema. Es el hueco más grande que queda |
| **Registro de auditoría de operaciones sensibles** | ✅ [`PLAN_REGISTRO_AUDITORIA.md`](futuros/PLAN_REGISTRO_AUDITORIA.md) | La tabla y la pantalla existen, **pero solo se escribe en ellas desde tres sitios**. Aprobar un retiro, suspender una tienda o cambiar los datos de cobro no dejan rastro: si un comerciante reclama, no hay con qué responderle |
| **Refresco de diseño del escaparate** | ❌ no | Lo decidiste al cerrar la migración a Flowbite: se migró conservando el aspecto para que el refresco fuera un cambio aparte. Es la cara pública y sigue sin empezar |
| **Historial de movimientos de stock** | ✅ [`PLAN_HISTORIAL_STOCK.md`](futuros/PLAN_HISTORIAL_STOCK.md) | Y con él llega gratis el aviso de stock bajo, lo único que quedó fuera de notificaciones |
| **Seis módulos avanzados del panel del comerciante** | ✅ [`PLANIFICACION_MODULOS_AVANZADOS_TENANT.md`](por_hacer/PLANIFICACION_MODULOS_AVANZADOS_TENANT.md) | Funcionalidad nueva, sin nada roto detrás |

### Y dos que no son código

**Las tres preguntas del abogado**, ninguna con nada construido encima:

1. Qué datos de identidad del comerciante se le pueden entregar a un comprador que denuncia. Hoy
   se entregan **todos**, cédula y RIF incluidos, por decisión explícita de fase de desarrollo.
   El único sitio que tocar es el bloque `store` de `BuildClaimDossierUseCase`:
   ```bash
   grep -rn "pendiente-abogado:" src/ tests/ resources/
   ```
2. Qué dice la ley venezolana sobre la **responsabilidad solidaria** de un marketplace que cobra
   todas las ventas.
3. Si **14 días** de ventana para reclamar cumple el mínimo legal. Si no, se sube desde Reglas de
   garantía sin tocar código.

**Los pedidos de invitado anteriores al 12/09/2026 son huérfanos.** Nadie puede demostrar que son
suyos, así que ni se ven, ni se confirman, ni se reclaman. Los nuevos sí quedan enlazados si se
compra con sesión, y el checkout lo advierte antes de pagar. En desarrollo da igual; **antes de
que haya compras reales, no**.

---

## 3 · Decisiones tomadas y no aplicadas

No son olvidos: están razonadas en
[`DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`](anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md)
y se decidió no construirlas todavía.

**Suspender el escaparate como sanción.** `tenants.status` ya admite `suspended` y la reputación
ya identifica a quién, pero nada los conecta. Conviene pensarlo despacio: suspender a una tienda
que debe dinero **garantiza que no lo pague nunca**, porque la deuda se salda vendiendo.

**Bloquear la reapertura por identidad.** La búsqueda encuentra las tiendas que comparten cédula,
pero nada impide abrir otra. Es deliberado —informa, no bloquea—; el día que se automatice, la
pieza que falta es un aviso en el alta de tienda.

**Velocidad de liberación por nivel de reputación.** Se implementó solo el porcentaje de
retención, a propósito: el porcentaje ya hace el trabajo, y variar además los días exige tocar
`central_payout_hold_days` por otro camino. Si se retoma, que sea porque los datos digan que el
porcentaje solo no basta.

---

## 4 · Riesgos anotados y no resueltos

**Una reclamación sobre un pedido sin comisión registrada se resuelve cubriendo cero, en
silencio.** La tasa congelada sale de `platform_commissions`; sin fila, el tope en bolívares es
cero y la cobertura sale cero. Hoy solo pasa con datos sembrados a mano —los pedidos reales sí
registran comisión— pero debería fallar ruidosamente el día que importe.

**Los tests corren en SQLite y producción es MySQL.** Ya costó dos incidentes de identificadores
demasiado largos. Lo cubre `MigrationIdentifierLengthTest`, pero es una grieta con una sola tabla.
→ [`ENTORNO_DE_TESTS.md`](anotaciones/ENTORNO_DE_TESTS.md)

**La reputación se deriva en cada consulta** (dos `COUNT`). Barato donde se usa hoy; un N+1 si
alguna pantalla llega a listar doscientas tiendas.

**El camino `payout` de `GenerateTenantCommissionSettlementUseCase`** sigue con su `max(0.0, …)`
y crea liquidaciones en USD que el saldo no cuenta. No mueve dinero hoy.

**Dos de los once correos siguen sin verificarse contra un servidor real**: el reenvío de factura
(no hay ninguna factura en la base) y el aviso de tasa BCV obsoleta (solo sale cuando el scraping
falla). Los dos tienen receta en
[`PRUEBA_MANUAL_CORREO.md`](anotaciones/PRUEBA_MANUAL_CORREO.md).

Los atajos deliberados están marcados en el código:

```bash
grep -rn "ponytail:" src/ resources/
```

---

## 5 · Qué leer antes de tocar código

- **[`COMO_FUNCIONA.md`](COMO_FUNCIONA.md)** — el negocio, el dinero, las garantías y la
  arquitectura. Si algo del código parece innecesariamente complicado, la razón suele estar ahí.
- [`reglas.md`](../reglas.md) — obligatorio. Servicios para toda llamada HTTP, Flowbite en todas
  las vistas, arquitectura hexagonal, tests antes del commit.
- [`anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`](anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md)
  — el porqué de los cinco subsistemas. Casi todo lo que parece raro está explicado ahí.
- [`por_hacer/PLAN_EJECUCION_MIGRACIONES_Y_SEEDERS.md`](por_hacer/PLAN_EJECUCION_MIGRACIONES_Y_SEEDERS.md)
  — runbook para reconstruir el entorno desde cero.
- [`anotaciones/PRUEBA_MANUAL_CORREO.md`](anotaciones/PRUEBA_MANUAL_CORREO.md) — los trece flujos
  de correo y cómo probarlos a mano.

Los comentarios del código citan hallazgos por número —«hallazgo N35», «hallazgo A3»—. Esos
números viven en las auditorías de `anotaciones/`, y por eso esa carpeta no se toca.
