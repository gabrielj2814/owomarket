# 📋 Plan: Registro de auditoría de operaciones sensibles

> **Estado:** propuesta, sin aprobar.
> **Origen:** auditoría del 23/08/2026.
> **La tabla ya existe.** Esto no es construir un módulo: es escribir en el que hay.

---

## 🎯 Objetivo

Que quede rastro de quién hizo qué en las operaciones que mueven dinero o cambian el estado
de una tienda. Hoy sólo queda rastro de una.

---

## 🔍 Por qué: la evidencia

`central_audit_logs` está creada (`2026_08_21_000002`) y tiene su modelo `CentralAuditLog`.

**Se escribe desde exactamente un sitio:** `AdminImpersonateTenantUseCase` — cuando un
administrador entra a una tienda suplantando a su dueño.

Todo lo demás no deja rastro, y todo lo demás ya existe y funciona:

| Operación | Dónde vive | ¿Queda rastro? |
| :--- | :--- | :--- |
| Aprobar o rechazar un **retiro** | `ApproveCentralPayoutRequestUseCase` | ❌ |
| Aprobar o rechazar un **cambio de plan** | `ApproveTenantPlanChangeRequestUseCase` | ❌ |
| Fijar una **comisión personalizada** a una tienda | `/api/central/monetization/custom-commission` | ❌ |
| **Suspender, activar o rechazar** una tienda | `/tenant/backoffice/{id}/*` | ❌ |
| Cambiar los **datos de cobro de la plataforma** | `AdminUpdateCentralPaymentSettingsPUTController` | ❌ |
| Confirmar una **liquidación** de comisiones | `ConfirmAndSettleCommissionUseCase` | ❌ |
| Crear, editar o borrar un **administrador** | `/admin/backoffice/{uuid}/admin` | ❌ |

Las seis primeras tocan dinero. La séptima toca quién puede tocarlo.

### El escenario que lo justifica

Un comerciante reclama que le suspendieron la tienda sin motivo, o que su retiro se aprobó
por un importe distinto al que pidió. **Hoy no hay forma de responderle** — ni de darle la
razón ni de quitársela.

Y con el hallazgo **T1** cerrado sabemos que el saldo de los retiros se comprobaba al pedir y
no al pagar: si algo se pagó mal antes de esa corrección, no hay manera de saber quién lo
aprobó.

---

## 🗺️ Alcance

- Un servicio compartido —`RegistraOperacionSensible` o similar— que escriba en la tabla que
  ya existe: **quién**, **qué**, **sobre qué recurso**, **desde qué IP**, **cuándo** y el
  **estado anterior y posterior** cuando aplique.
- Llamarlo desde los siete puntos de la tabla de arriba.
- Pantalla de consulta en el panel del administrador, con filtro por actor, por tipo de
  operación y por rango de fechas.
- **Sólo lectura y sin borrado.** Un registro de auditoría que se puede editar no es un
  registro de auditoría.

---

## ⚠️ Dos cosas que decidir

1. **¿Retención?** Estos registros crecen. Hay que decidir cuánto se guardan antes de
   construirlo, no después.
2. **¿Datos personales dentro?** Guardar el estado anterior y posterior de un perfil implica
   guardar el correo o el teléfono de alguien. Conviene acotar qué campos se registran.

---

## 🚫 Fuera de alcance

- **Auditoría de operaciones del comerciante dentro de su tienda** (editar productos, cambiar
  precios). Es otro problema, vive en la base del inquilino y merece su propio plan.
- **Exportar el registro** a un SIEM o servicio externo. Primero que exista.

---

## 💡 Por qué es barato

La tabla, el modelo y un punto de escritura funcionando ya están. **Lo que falta es llamarlo
desde los sitios que importan**, y son siete llamadas.
