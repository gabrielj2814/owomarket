# 📋 Plan: Resolución de devoluciones

> **Estado:** propuesta, sin aprobar.
> **Origen:** auditoría del 23/08/2026. No es una idea nueva — es **terminar algo que ya está
> a medias y que el cliente ya ve funcionando**.

---

## 🎯 Objetivo

Que una solicitud de devolución tenga quién la reciba, la resuelva y se lo comunique al
cliente. Hoy no lo tiene.

---

## 🔍 Por qué: la evidencia

Esto no sale de un análisis de mercado, sale de leer el código:

| Qué existe | Estado |
| :--- | :--- |
| Tabla `customer_return_requests` | ✅ Creada (`2026_08_19_000010`) |
| `POST /api/central/customer/returns` — el cliente **crea** | ✅ Funciona |
| `GET /api/central/customer/returns` — el cliente **lista** las suyas | ✅ Funciona |
| Pantalla `CustomerReturnsPage` en el portal | ✅ Existe |
| **Alguna ruta que apruebe, rechace o cambie el estado** | ❌ **No existe** |
| **Alguna pantalla de comerciante o administrador** | ❌ **No existe** |

**La solicitud entra en la tabla y se queda ahí para siempre.**

Es el mismo patrón que los hallazgos C1 (cupones anunciados que el checkout rechazaba) y T3
(el botón «Mejorar Plan» que no mandaba nada): **la interfaz recoge la petición y nadie la
recibe.** La diferencia es que aquí el cliente ya está esperando una respuesta.

Por eso va el primero de los cuatro planes: **no es funcionalidad nueva, es una promesa
incumplida.**

---

## 🗺️ Alcance

### Lado del comerciante

- Listado de devoluciones de su tienda, con filtro por estado.
- Resolver una: **aprobar**, **rechazar** (con motivo obligatorio) o **pedir más
  información**.
- Al aprobar, decidir explícitamente si se repone el stock. **No automático**: una
  devolución por producto defectuoso no vuelve al inventario vendible.

### Lado del administrador

- Vista global de devoluciones de todas las tiendas, sólo lectura, para arbitrar disputas.
- **No** resuelve por el comerciante: es su venta y su decisión.

### Lado del cliente

- Ver el estado real y el motivo cuando se rechaza. Hoy sólo ve que la creó.

---

## ⚠️ Decisiones que hay que tomar antes

1. **¿Reponer stock al aprobar?** Ver arriba: debe ser una elección del comerciante, no un
   efecto automático.
2. **¿Devolución implica reembolso?** Order ya tiene `refund()` con su guarda
   `canBeRefunded()` y `ReverseOrderCommissionUseCase`. Hay que decidir si aprobar una
   devolución dispara el reembolso del pedido o son dos pasos separados.
3. **¿Quién paga el envío de vuelta?** No hay nada modelado para eso.

---

## 🚫 Fuera de alcance

- **Devoluciones parciales por línea de pedido.** El modelo actual es por producto; ampliarlo
  es otro proyecto.
- **Etiquetas de envío de retorno.** Depende de integraciones con transportistas que no
  existen.

---

## 🔗 Depende de

`PLAN_NOTIFICACIONES.md` — sin notificaciones, el cliente tendrá que entrar a mirar si le
respondieron. Se puede construir antes, pero rinde la mitad.
