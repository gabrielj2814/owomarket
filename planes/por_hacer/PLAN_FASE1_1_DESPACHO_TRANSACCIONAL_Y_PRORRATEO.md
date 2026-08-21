# PLAN — Fase 1.1: Despacho multi-tienda transaccional, idempotente y con prorrateo

> **Origen:** hallazgos C2 y D1 de `planes/anotaciones/AUDITORIA_BUGS_2026_08_21.md` (punto 6 del plan de acción, primer punto de la Fase 1 «Integridad del dinero»)
> **Severidad:** 🔴 Crítico — ambos provocan cobros incorrectos a los comerciantes
> **Tamaño:** 1 migración, 1 modelo nuevo, 1 servicio nuevo, 2 casos de uso reescritos, 1 controlador, 2 archivos de frontend, 1 archivo de test
> **Estado:** ✅ Implementado — pendiente de validación con `php artisan test`
> **Depende de:** Fase 0.4 (los resolvers de precio ya normalizan las líneas del pedido)

---

## 1. C2 — El despacho no era transaccional ni idempotente

`DispatchCentralOrderToTenantsUseCase` recorría las tiendas con `try { ... } finally { ... }` **sin `catch`**, sin transacción en ningún nivel y sin clave de idempotencia.

**Escenario de la auditoría:** carrito de 3 tiendas; la base de datos del tenant 2 no responde. El tenant 1 ya tiene su pedido, su `payment` y su comisión; el cliente recibe un error y reintenta. Se creaba un **segundo `CentralOrder` completo** y el tenant 1 acababa con dos pedidos idénticos y dos comisiones — se le cobraba dos veces por una sola compra.

### 1.1 Solución en dos niveles

El problema tiene dos capas y hacía falta atacar las dos: sin la primera, un reintento crea otro pedido central; sin la segunda, un despacho reintentado duplica dentro del mismo pedido.

**Nivel 1 — Idempotencia del checkout.** Columna `central_orders.idempotency_key` (única). El navegador genera una clave por intento de compra y la reutiliza en los reintentos; si ya existe un pedido con esa clave, se devuelve **ese mismo** y se relanza el despacho (que es idempotente por tienda, así que sólo alcanza a las que quedaron pendientes).

**Nivel 2 — Idempotencia del despacho.** Tabla `central_order_dispatches` con índice único `(central_order_id, tenant_id)`. Antes de despachar a una tienda se inserta la reserva; si la inserción falla porque ya existe, esa tienda ya fue atendida y se salta.

Es importante que la exclusividad la dé **el índice único de la base de datos** y no una lectura previa en PHP: dos procesos simultáneos no pueden ganar los dos.

### 1.2 Transacción y manejo de errores

- La creación del `CentralOrder` y sus líneas va dentro de `DB::transaction`. Antes, un fallo a mitad dejaba un pedido sin líneas —o con parte de ellas— imposible de despachar.
- El pedido de cada tienda, su `payment` y su marca de despacho van dentro de una transacción propia.
- El `catch` que faltaba: el fallo de una tienda **ya no aborta las demás ni desaparece en silencio**; se registra en `central_order_dispatches.error_message` y en el log.

**Nota deliberada:** el despacho va **fuera** de la transacción del pedido central. Escribe en las bases de datos de las tiendas (otra conexión), así que englobarlo no daría atomicidad real — sería una falsa sensación de seguridad. Su garantía viene de la idempotencia, no de la transacción.

---

## 2. D1 — El envío, el descuento y los impuestos se perdían al repartir

```php
$dto = new CreateOrderData(
    ...
    taxAmount: 0.0,
    shippingAmount: 0.0,
    discountAmount: 0.0,
);
$tenantOrderTotal = $tenantOrder->total()->amount();  // ← subtotal bruto
```

Ese subtotal bruto se usaba como (a) importe del `payment` y (b) base de la comisión.

**Escenario numérico de la auditoría:** carrito de dos tiendas, A=$60 y B=$40, envío $10, cupón −$30.

| | Antes | Ahora |
| :--- | :--- | :--- |
| Paga el cliente | $80 | $80 |
| Pedido tienda A | $60 | $48 (60 + 6 − 18) |
| Pedido tienda B | $40 | $32 (40 + 4 − 12) |
| **Suma** | **$100 ≠ $80** | **$80 ✓** |
| `payments` registrados | $100 | $80 |
| Comisión al 8% | $8,00 | $5,60 |

### 2.1 El reparto: método del resto mayor

`Src\CentralMarketplace\Application\Service\CentralOrderProrator` reparte en proporción al subtotal de cada tienda. El detalle que importa es el redondeo: se trabaja en **céntimos enteros** y los que sobran o faltan se asignan a las tiendas con mayor resto pendiente. Así **la suma de las partes es siempre exactamente el importe original**, sin céntimos perdidos ni inventados.

Sin esto, un envío de $10 entre tres tiendas daría $3,33 × 3 = $9,99 y faltaría un céntimo en cada pedido.

El mismo servicio reparte la comisión entre las líneas del pedido, lo que cierra de paso un «menor de dinero» que la auditoría lista aparte: antes la comisión oficial se redondeaba una vez sobre el total y luego se recalculaba ítem a ítem, y no cuadraban (tres ítems de $3,33 al 8% daban $0,81 por ítems frente a $0,80 registrados).

---

## 3. ✅ Decisión de negocio — CONFIRMADA (21/08/2026)

La auditoría dice, literalmente, que hay que «documentar explícitamente sobre qué base se cobra la comisión (bruto o neto) — ahora mismo el código dice una cosa y el negocio probablemente quiere otra».

**Decisión tomada por el propietario del producto: comisión sobre la MERCANCÍA NETA de descuento, SIN incluir el envío.** No se cobra comisión sobre dinero que el comerciante nunca recibió.

```
base = subtotal de la tienda − descuento prorrateado
```

El razonamiento: el envío no es ingreso del comerciante (lo absorbe el transportista), y un descuento reduce lo que el comerciante cobra de verdad. Cobrar sobre el bruto significa cobrarle comisión por dinero que nunca recibió.

Con el ejemplo de arriba al 8%: base A = 60 − 18 = $42 → $3,36; base B = 40 − 12 = $28 → $2,24; **total $5,60**, que es exactamente lo que la auditoría señala como correcto frente a los $8,00 anteriores.

**Alternativas evaluadas y descartadas:**

| Base | Comisión del ejemplo | Argumento | Decisión |
| :--- | :---: | :--- | :--- |
| **Mercancía neta** | $5,60 | El comerciante paga por lo que cobra | ✅ **Elegida** |
| Mercancía bruta (comportamiento anterior) | $8,00 | El descuento lo absorbe íntegro el comerciante | Descartada |
| Total cobrado, envío incluido | $6,40 | La plataforma participa también del envío | Descartada |

Si el negocio cambiara de criterio, es tocar **un solo punto**: el método `recordCommission()` de `DispatchCentralOrderToTenantsUseCase`, que está marcado con un aviso en el código.

---

## 4. Qué cierra esta fase

| Hallazgo | Estado |
| :--- | :--- |
| **C2** — sin transacción en la creación del pedido central | ✅ Cerrado |
| **C2** — sin idempotencia: reintento = pedido y comisión duplicados | ✅ Cerrado (dos niveles) |
| **C2** — `try/finally` sin `catch` que ocultaba el fallo de una tienda | ✅ Cerrado |
| **D1** — envío y descuento perdidos al repartir | ✅ Cerrado |
| **D1** — `payments` por importe distinto al cobrado | ✅ Cerrado |
| **D1** — base de la comisión sin documentar | ✅ Cerrado, documentado y confirmado por negocio (ver sección 3) |
| Menor: comisión por ítem que no cuadra con la registrada | ✅ Cerrado |
| **D1** — impuestos (`taxAmount`) | ⬜ Sigue en 0.0: el checkout central no calcula impuestos en ningún momento (ver G9). No es un reparto que se pierda, es un cálculo que no existe |

---

## 5. Tareas

- [x] Migración: `central_orders.idempotency_key` + tabla `central_order_dispatches`
- [x] Modelo `CentralOrderDispatch`
- [x] Servicio `CentralOrderProrator` (reparto con resto mayor)
- [x] Reescribir `DispatchCentralOrderToTenantsUseCase` (transacción, idempotencia, catch, prorrateo)
- [x] Transacción e idempotencia en `CreateUnifiedCentralOrderUseCase`
- [x] `idempotency_key` en el controlador, el modelo y el payload del frontend
- [x] Generar la clave en `CentralCheckoutPage.tsx` (una por intento, estable entre reintentos)
- [x] Actualizar y ampliar `MultiStoreCentralCheckoutTest.php`
- [ ] `php artisan migrate`
- [ ] `php artisan test`
- [ ] `npm run types`
- [ ] `vendor/bin/pint src/CentralMarketplace/`
- [x] **Confirmar la base de cálculo de la comisión** — confirmada: mercancía neta (sección 3)
- [ ] Commit: `fix(central-marketplace): despacho transaccional e idempotente con prorrateo de envío y descuento`
- [ ] `git push origin <rama_actual>`
- [ ] Mover este documento a `planes/implementados/`

---

## 6. Verificación manual

**Debe seguir funcionando:**
1. Compra multi-tienda: cada tienda recibe su pedido, su pago y su comisión.

**Debe cambiar:**
2. Con envío y cupón, la suma de los pedidos de tienda **cuadra exactamente** con lo que pagó el cliente.
3. Cada tienda ve ahora su parte del envío y del descuento en su propio pedido (antes no veía ninguno de los dos).
4. Pulsar dos veces «Confirmar pedido» → **un solo pedido**, una sola comisión.
5. Si una tienda falla, las demás se despachan igual y el fallo queda registrado en `central_order_dispatches` con su mensaje.

Consulta útil para auditar despachos fallidos:

```sql
SELECT central_order_id, tenant_id, status, error_message
FROM central_order_dispatches WHERE status = 'failed';
```

---

## 7. Riesgo

**Medio-alto: cambia importes que los comerciantes ven y cobran.**

1. **Las comisiones bajan** para los pedidos con descuento (en el ejemplo, de $8,00 a $5,60). Es la corrección de un sobrecobro, pero **cambia los ingresos de la plataforma** y debe ser una decisión consciente, no un efecto colateral. De ahí la sección 3.
2. **Los pedidos de tienda ahora incluyen envío y descuento.** Si algún informe, exportación o integración del backoffice asumía que `shipping_amount` y `discount_amount` de un pedido venido del marketplace central eran siempre 0, dejará de cuadrar.
3. **La migración añade un índice único sobre `idempotency_key`.** Es nullable, así que las filas existentes (todas con NULL) no colisionan — MySQL y SQLite permiten múltiples NULL en un índice único. Conviene confirmarlo en el motor de producción antes de migrar.
4. **Los pedidos ya despachados no tienen fila en `central_order_dispatches`.** Si alguien relanzara el despacho de un pedido antiguo, se duplicaría, porque no hay reserva previa que lo impida. Si eso es un escenario real, hay que rellenar la tabla a partir de `central_order_items.tenant_order_id` antes de desplegar:
   ```sql
   INSERT INTO central_order_dispatches (id, central_order_id, tenant_id, tenant_order_id, status, dispatched_at, created_at, updated_at)
   SELECT UUID(), central_order_id, tenant_id, tenant_order_id, 'dispatched', NOW(), NOW(), NOW()
   FROM central_order_items WHERE tenant_order_id IS NOT NULL
   GROUP BY central_order_id, tenant_id, tenant_order_id;
   ```

---

## 8. Trabajo de seguimiento identificado

1. **El despacho sigue siendo síncrono.** La auditoría propone «un job idempotente por (central_order_id, tenant_id)». La idempotencia ya está; falta moverlo a una cola para que una tienda lenta no bloquee la respuesta del checkout ni deje al cliente esperando. Ahora que el despacho es reintentable sin efectos secundarios, ese cambio es seguro de hacer.
2. **Nada reintenta los despachos fallidos.** Quedan en `status = 'failed'` esperando a que alguien los mire. Con la cola del punto anterior, el reintento sería automático.
3. **C3 y C4 siguen abiertos** (liquidaciones y facturas correlativas sin `lockForUpdate`), y **C1** (stock sin transacción) también. Son el punto 8 del plan de acción — la siguiente sub-fase.
4. **Los impuestos siguen sin calcularse** en el checkout central (`taxAmount: 0.0`). Es parte de G9, no de D1.
