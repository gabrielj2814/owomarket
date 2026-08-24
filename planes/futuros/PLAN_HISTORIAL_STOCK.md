# 📋 Plan: Historial de movimientos de stock

> **Estado:** propuesta, sin aprobar.
> **Origen:** auditoría del 23/08/2026.

---

## 🎯 Objetivo

Poder responder a la pregunta *«¿por qué tengo 3 unidades si debería tener 5?»*. Hoy no se
puede.

---

## 🔍 Por qué: la evidencia

**No hay ninguna tabla de movimientos de inventario.** Se comprobó: en
`database/migrations/tenant/` no existe nada de `stock`, `inventory` ni `movement`.

El stock es un único campo mutable —`products.quantity` o `product_variants.quantity`— que se
sube y se baja desde varios sitios:

| Quién lo toca | Cuándo |
| :--- | :--- |
| `StockReserver::reserve()` | Al vender en el escaparate de la tienda |
| `StockReserver::reserve()` | Al despachar un pedido del marketplace central |
| `StockReserver::release()` | Al cancelar un pedido |
| `ProductRepository::updateStock()` | Cuando el comerciante repone a mano |
| La edición del producto | Cuando cambia las variantes |

**Cinco caminos escribiendo sobre el mismo número, y ninguno deja constancia.**

### Por qué no es hipotético

El hallazgo **SH1**, todavía abierto, describe exactamente un descuadre alcanzable: un pedido
en `processing` recibe un envío, se cancela —lo que **repone el stock**— y después se marca
el envío como entregado, con lo que el pedido pasa a `delivered`. Queda mercancía contada dos
veces y nadie puede reconstruir cómo llegó ahí.

Y el hallazgo **PR2**, cerrado el 23/08, era justo esto: reponer stock en un producto con
variantes escribía en un campo que nadie lee. **Durante meses, cada reposición de ese tipo se
perdió sin dejar rastro** — y sólo se descubrió leyendo el código, no porque alguien notara
el descuadre.

---

## 🗺️ Alcance

- Tabla `stock_movements` en la base del inquilino: producto, variante, **delta** (no el
  valor absoluto), motivo, referencia al pedido cuando lo haya, quién y cuándo.
- Escribir desde los cinco caminos de la tabla de arriba. **`StockReserver` es el sitio
  natural** para tres de ellos, y ya está centralizado.
- Vista de historial por producto en el backoffice del comerciante.
- Aviso de stock bajo — que es, en realidad, una notificación.

---

## ⚠️ La decisión que hay que tomar primero

**¿El stock pasa a ser una suma de movimientos, o sigue siendo un campo con historial al
lado?**

- **Campo + historial** (recomendado para empezar): menos invasivo, no toca `StockReserver`
  ni sus bloqueos, y responde la pregunta que motiva el plan. Riesgo: el campo y la suma de
  movimientos pueden divergir.
- **Suma de movimientos** (libro mayor de verdad): imposible que diverja, pero reescribe el
  camino de venta, que hoy funciona y está protegido con `lockForUpdate()` desde N14 y N36.

**No tocaría el segundo sin una razón de peso.** El camino de venta es de las pocas cosas de
este proyecto que están bien resueltas y probadas.

---

## 🚫 Fuera de alcance

- **Gestión de almacenes múltiples.** Otro proyecto entero.
- **Valoración de inventario** (coste medio, FIFO). Es contabilidad, no operaciones.
- **Reconciliación automática** entre el campo y la suma de movimientos. Primero que existan
  los movimientos.

---

## 🔗 Depende de

`PLAN_NOTIFICACIONES.md` para el aviso de stock bajo. El historial en sí no lo necesita.
