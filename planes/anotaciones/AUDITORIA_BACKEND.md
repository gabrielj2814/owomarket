# Auditoría del backend — módulos de `src/`

> ## 📌 ESTADO — 23/08/2026
>
> **Documento vivo.** Se va ampliando módulo a módulo. **No se está arreglando nada
> todavía**: aquí sólo se registra lo encontrado, con su estado.
>
> Alcance: los módulos de `src/` que nunca han tenido una pasada propia. Han recibido
> arreglos puntuales llegados desde otras auditorías —`StockReserver` por N14 y N36, los
> cupones por B3/C6, las pasarelas por G1— pero **nadie ha mirado el conjunto**.
>
> ### Leyenda
> 🔴 crítico · 🟠 alto · 🟡 medio · ✅ cerrado · ⬜ abierto
>
> ### Nomenclatura
> `PR` Product · `OR` Order · `SH` Shipment · `PY` Payment

---

## Progreso por módulo

| Módulo | Ruta | Tamaño | Estado |
| :--- | :--- | :--- | :--- |
| **Product** | `src/Product/` | 85 ficheros · 4.686 líneas | ✅ **Auditado** — 2 hallazgos |
| **Order** | `src/Order/` | 52 ficheros · 2.917 líneas | ✅ **Auditado** — 1 hallazgo |
| Shipment | `src/Shipment/` | 36 ficheros · 1.730 líneas | ⬜ Pendiente |
| Payment | `src/Payment/` | 33 ficheros · 1.626 líneas | ⬜ Pendiente |

---

## Hallazgos abiertos

| # | Módulo | Qué | Severidad | Estado | Demostrado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **PR1** | Product | Borrado arbitrario de ficheros del disco público | 🔴 | ⬜ Abierto | ⚠️ Sólo lectura |
| **PR2** | Product | Actualizar el stock de un producto con variantes no hace nada | 🟠 | ⬜ Abierto | ⚠️ Sólo lectura |
| **OR1** | Order | El estado de pago no tiene máquina de estados, y `pending` se acepta sin hacer nada | 🟡 | ⬜ Abierto | ⚠️ Sólo lectura |

> **Ninguno está probado ejecutando.** Todos salen de leer el código. En este proyecto esa
> distinción ha importado varias veces en un solo día —una lectura llevó a un falso positivo
> de fijación de sesión, y un fixture incompleto casi archiva el hallazgo T7 como
> inexistente—, así que queda marcada hasta que se demuestren.

---

# Módulo Product

**Auditado el 23/08/2026.** Método: perímetro de rutas y guardas, después integridad de los
datos que el módulo escribe (stock, precios, ficheros).

## PR1. 🔴 Borrado arbitrario de ficheros del disco público

> **Estado:** ⬜ ABIERTO

**Dónde:** `DeleteProductImageDELETEController` → `DeleteProductImageUseCase` →
`LaravelProductMediaStorageService::deleteImage()`

El controlador toma la ruta **directamente del request, sin validarla**:

```php
$imagePath = (string) $request->input('image_path', $request->query('image_path', ''));

if (! empty($imagePath)) {
    $this->deleteUseCase->execute($imagePath);
}
```

Y el servicio la usa tal cual:

```php
if (Storage::disk('public')->exists($relativePath)) {
    Storage::disk('public')->delete($relativePath);
}
```

**No hay ninguna comprobación** de que ese fichero pertenezca a este producto, a esta tienda,
ni siquiera de que esté dentro del directorio de imágenes de producto.

### Qué se puede borrar

El disco `public` no es sólo de Product. Lo comparten:

| Qué | Dónde |
| :--- | :--- |
| Imágenes de producto **de todas las tiendas** | `LaravelProductMediaStorageService` |
| **Avatares de administradores** | `src/Admin/.../LaravelAvatarStorageService.php` |
| **PDFs de facturas** | `src/Billing/.../DomPdfInvoiceGeneratorService.php` |
| **Adjuntos de tickets de soporte** | `src/SupportTicket/.../UploadSupportAttachmentService.php` |

Las facturas y los adjuntos de soporte son **registros**, no adorno: son la prueba
documental de una transacción y de una reclamación.

### Quién puede hacerlo

Cualquiera con sesión de tienda y `manage_catalog` — el permiso más básico del catálogo, el
que tiene cualquier empleado que suba productos. No hace falta ser propietario.

La ruta está correctamente autenticada (`auth` + `throttle:api` + `tenant_can:manage_catalog`);
el problema no es el perímetro, es que **dentro no se comprueba de quién es el fichero**.

### Dos agravantes

1. **Responde siempre éxito**, borre o no borre algo. Quien lo use a mano no recibe señal
   de error, y quien audite los registros tampoco verá un rastro de intentos fallidos.
2. **Ningún `validate()`**: ni `required`, ni formato, ni longitud.

### Por dónde iría el arreglo

Resolver la imagen por su **id** en la base del inquilino en vez de aceptar una ruta, y
borrar el fichero que esa fila dice. La tenancy ya aísla la base por dominio, así que un id
no puede apuntar fuera de la tienda. Si hay que seguir aceptando rutas, comprobar que
empiecen por el prefijo de imágenes de producto **y** que exista una fila de
`product_images` que las reclame.

### Cómo demostrarlo

Autenticarse como usuario de tienda con `manage_catalog` y llamar al endpoint con la ruta de
un avatar o de una factura. Es la comprobación que falta antes de darlo por bueno.

---

## PR2. 🟠 Actualizar el stock de un producto con variantes no hace nada

> **Estado:** ⬜ ABIERTO

**Dónde:** `UpdateProductStockPATCHController` → `UpdateProductStockUseCase` →
`ProductRepository::updateStock()`

```php
EloquentProduct::where('id', $id->value())->first()?->update(['quantity' => max(0, $quantity)]);
```

Escribe **sólo** en `products.quantity`. Nunca toca `product_variants`.

### Por qué eso es un problema

En un producto con variantes, el `quantity` del padre **no lo mantiene ni lo lee nadie**. Lo
dice el propio código, anotado en el hallazgo N36:

> *«el `quantity` del padre no sirve en un producto con variantes: `StockReserver` sólo
> descuenta de la variante»*

Y la ficha de producto muestra el de la variante cuando la hay:

```tsx
const stockDeclarado = varianteActiva ? varianteActiva.quantity : Number(product.quantity);
```

Así que el comerciante entra en su lista de productos, corrige el stock de una camiseta con
tallas, ve que se guarda correctamente — y **ni lo que se vende ni lo que se muestra cambia**.
El endpoint lo llama `ProductIndexPage.tsx`, la lista del backoffice de la tienda.

### Consecuencia práctica

Un producto agotado sigue agotado después de reponerlo, y el comerciante no tiene forma de
saber por qué. Es un fallo silencioso en el inventario: no da error, simplemente no surte
efecto.

### Por dónde iría el arreglo

Que el endpoint acepte un `variant_id` opcional y escriba donde toca, o que rechace la
operación sobre un producto con variantes indicando que el stock se gestiona por variante.
Lo que no puede es aceptar el cambio y no aplicarlo.

---

## Lo que se comprobó y está BIEN

No hace falta volver a mirarlo:

| Qué | Resultado |
| :--- | :--- |
| **Perímetro de rutas** | ✅ Las 8 rutas de `api-tenant/product/*` llevan `Authenticate` + `throttle:api` + `tenant_can:manage_catalog` |
| **Aislamiento entre tiendas** | ✅ La tenancy resuelve la base por dominio, así que un `{id}` no puede apuntar al producto de otra tienda |
| **Concurrencia de stock en venta** | ✅ `StockReserver` usa `lockForUpdate()` dentro de transacción, y toca la variante cuando se le pasa (N14, N36) |
| **Validación de creación** | ✅ `sku`, `price`, `quantity`, `compare_price`, `cost_price`, `min/max_quantity` con tipos y `min:0` |
| **Validación de stock** | ✅ `required|integer|min:0`, con mensajes propios |
| **Subida de imágenes** | ✅ `file`, `image`, `mimes:jpeg,png,jpg,webp`, `max:5120` |
| **Sincronización con el catálogo central** | ✅ `ProductObserver` cubre `saved`, `deleted` y `restored` — la preocupación de E1 está atendida |
| **Identidad de variantes al editar** | ✅ E4 cerrado: se actualiza lo existente en vez de borrar y recrear con uuid nuevos |

---

# Módulo Order

**Auditado el 23/08/2026.** Mismo método: perímetro, después integridad de estados y dinero.

> **Es el módulo mejor construido que se ha auditado en este proyecto.** La máquina de
> estados del pedido está definida en el dominio y **se respeta entera**; cancelar repone
> stock y revierte comisión; la comisión no se vuelve cobrable hasta que el pago se confirma.
> Un solo hallazgo, y es de coherencia, no de seguridad.

## OR1. 🟡 El estado de pago no tiene máquina de estados

> **Estado:** ⬜ ABIERTO

**Dónde:** `UpdateOrderPaymentStatusUseCase` y `Order::markPaymentPaid()` / `markPaymentFailed()`

El **estado del pedido** está protegido en el dominio: las seis transiciones
—`confirm`, `process`, `markAsShipped`, `markAsDelivered`, `cancel`, `refund`— comprueban su
guarda (`canBeConfirmed()`, `canBeShipped()`, …) y lanzan
`InvalidOrderStateTransitionException` si no procede.

El **estado del pago** no tiene nada de eso:

```php
public function markPaymentPaid(): void
{
    $this->paymentStatus = PaymentStatus::PAID;
    $this->updatedAt = new DateTimeImmutable;
}
```

`PaymentStatus` sólo define predicados (`isPaid()`, `isRefunded()`, …). **No hay ni un
`canBeX()`**, así que aquí no estamos ante una protección escrita y sin cablear —el patrón
habitual de este repositorio— sino ante una que nunca se escribió.

### Las dos consecuencias

**1. Se pueden alcanzar combinaciones incoherentes.** Un pedido `refunded` puede marcarse
como `paid`: el estado del pedido sigue siendo «reembolsado» y el del pago pasa a «pagado».
Nada lo impide y nada lo detecta.

**2. `pending` se acepta y no hace absolutamente nada.**

```php
match ($status) {
    PaymentStatus::PAID => $order->markPaymentPaid(),
    PaymentStatus::FAILED => $order->markPaymentFailed(),
    PaymentStatus::REFUNDED => $order->refund(),
    PaymentStatus::PENDING => null,   // ← acepta y no cambia nada
    ...
};
```

El endpoint devuelve éxito y el pedido se queda como estaba. Un comerciante que marcó
«pagado» por error e intenta revertirlo **recibe confirmación de un cambio que no ocurre**.
Es la misma clase de fallo silencioso que PR2: aceptar la operación y no aplicarla.

### Lo que NO es

**No hay doble cobro.** `ActivateOrderCommissionUseCase` sólo promueve comisiones que están
en `awaiting_payment`, así que marcar «pagado» dos veces no cobra dos veces. Se comprobó a
propósito porque era la sospecha inicial.

### Por dónde iría el arreglo

Darle a `PaymentStatus` las mismas guardas que tiene `OrderStatus`, y que `PENDING` o bien
revierta de verdad —con lo que eso implique para la comisión ya activada— o bien se rechace
con un mensaje claro. Lo que no puede es responder éxito sin hacer nada.

---

## Lo que se comprobó y está BIEN

| Qué | Resultado |
| :--- | :--- |
| **Perímetro de rutas** | ✅ Las 8 rutas de `api-tenant/order/*` llevan `Authenticate` + `throttle:api` + `tenant_can:manage_orders` |
| **Máquina de estados del pedido** | ✅ Las seis transiciones comprueban su guarda en la entidad. Un pedido cancelado no se puede enviar |
| **Despacho de estados** | ✅ El controlador usa un `match` a casos de uso por transición, y la lista `in:` del FormRequest **coincide exactamente** con las ramas: no hay `UnhandledMatchError` alcanzable |
| **Cancelar repone stock** | ✅ N13 cerrado, con el razonamiento de por qué sólo aplica a `pending`/`confirmed`/`processing` — un pedido enviado no se puede cancelar, así que la mercancía nunca salió |
| **Cancelar revierte comisión** | ✅ D2 cerrado: la comisión de una venta que nunca se cobró ya no entra en la liquidación |
| **La comisión espera al pago** | ✅ N15 cerrado: nace en `awaiting_payment` y sólo se vuelve cobrable al confirmar el pago |
| **Activación de comisión idempotente** | ✅ Sólo promueve filas en `awaiting_payment`; marcar «pagado» dos veces no cobra dos veces |
| **Validación al crear** | ✅ Incluye `exists:products,id` por artículo, precios `min:0` y cantidades `min:1` |

### Una observación que no es hallazgo

`CreateOrderUseCase` **acepta el precio de cada artículo tal y como se lo dan** — no lo
resuelve contra el catálogo. Se miró con detenimiento porque es el error de B1, y **aquí no
lo es**:

- Los dos caminos públicos ya resuelven el precio antes de llegar: el checkout del
  escaparate con `StorefrontItemPriceResolver` y el central con `CentralItemPriceResolver`.
- El tercero, `POST /api-tenant/order/create`, es el comerciante creando un pedido manual en
  **su propia** tienda. Fijar el precio ahí es una operación legítima de negocio (venta
  telefónica, mostrador, acuerdo puntual).

Queda anotado porque el siguiente que lo lea se hará la misma pregunta.
