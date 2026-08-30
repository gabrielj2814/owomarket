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
| **Product** | `src/Product/` | 85 ficheros · 4.686 líneas | ✅ Auditado — **2 hallazgos CERRADOS** |
| **Order** | `src/Order/` | 52 ficheros · 2.917 líneas | ✅ Auditado — **1 hallazgo CERRADO** |
| **Shipment** | `src/Shipment/` | 36 ficheros · 1.730 líneas | ✅ Auditado — **1 hallazgo CERRADO** |
| **Payment** | `src/Payment/` | 33 ficheros · 1.626 líneas | ✅ Auditado — **1 hallazgo CERRADO** |

> **Los cuatro módulos auditados y los 5 hallazgos cerrados** —1 🔴 · 2 🟠 · 2 🟡—, cada uno con tests que lo vigilan.

---

## Hallazgos abiertos

| # | Módulo | Qué | Severidad | Estado | Demostrado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **PR1** | Product | Borrado sin acotar dentro del almacenamiento de la tienda | 🟡 | ✅ **Cerrado** | ✅ Probado |
| **PR2** | Product | Actualizar el stock de un producto con variantes no hacía nada | 🟠 | ✅ **Cerrado** | ✅ Probado |
| **OR1** | Order | El estado de pago no tiene máquina de estados, y `pending` se acepta sin hacer nada | 🟡 | ✅ **Cerrado** | ✅ Probado |
| **SH1** | Shipment | Entregar un envío fuerza el pedido a `delivered` saltándose la máquina de estados | 🟠 | ✅ **Cerrado** | ✅ Probado |
| **PY1** | Payment | La capa de pasarelas no la usa nadie, y su endpoint acepta importes sin registrar nada | 🟡 | ✅ **Cerrado** | ✅ Probado |

> **Los cinco quedaron demostrados ejecutando**, y en este proyecto esa distinción no es
> ceremonia. Costó tres correcciones, una por hallazgo:
>
> - **OR1:** el arreglo que salió de la lectura era demasiado estricto y habría bloqueado el
>   reembolso de los métodos de pago más usados. Lo tumbó la suite en la primera ejecución.
> - **SH1:** los tres caminos que la lectura veía separados pasaban todos por el mismo
>   `save()`. Sólo se vio trazando el flujo entero.
> - **PY1:** la auditoría daba por muerta la tabla `payments`, que estaba viva. El `grep`
>   había buscado por el eje equivocado.
>
> Ninguna de las tres se habría visto releyendo el código con más atención.

---

# Módulo Product

**Auditado el 23/08/2026.** Método: perímetro de rutas y guardas, después integridad de los
datos que el módulo escribe (stock, precios, ficheros).

## PR1. 🟡 Borrado sin acotar dentro del almacenamiento de la tienda

> **Estado:** ✅ CERRADO — 23/08/2026
>
> ### ⚠️ La severidad original era FALSA
>
> Este hallazgo se anotó como 🔴 diciendo que se podían borrar ficheros de **otras tiendas y
> del hub central** — avatares de administrador, PDFs de factura. **No es cierto**, y se
> comprobó ejecutando:
>
> ```
> raiz SIN tenancy: /var/www/storage/app/public
> raiz CON tenancy: /var/www/storage/tenant{id}/app/public/
> ```
>
> `FilesystemTenancyBootstrapper` sufija el disco `public` por inquilino
> (`config/tenancy.php`, sección `filesystem`), así que el borrado **nunca pudo salir del
> almacenamiento de esa tienda**. Lo del hub central se escribe sin tenancy, en la raíz sin
> sufijar, y era inalcanzable.
>
> El error fue mío: di por buena la lectura del `Storage::disk('public')->delete()` sin
> comprobar qué raíz tenía ese disco durante una petición de tienda. **Baja de 🔴 a 🟡.**
>
> Lo que sí era cierto y se ha cerrado: dentro de su propia tienda, cualquiera con
> `manage_catalog` podía borrar cualquier fichero pasando su ruta — incluidas las facturas y
> los adjuntos de soporte **de esa tienda**, que son registros.

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

### ✅ Cómo se cerró

`deleteImage()` recibe ahora el inquilino —el mismo parámetro que `uploadImage()` ya tenía— y
sólo borra rutas bajo `tenants/{id}/products/`. Simetría a propósito: si un día cambia el
esquema de rutas, las dos mitades están a la vista en el mismo fichero.

**No se comprueba contra `product_images`**, que sería lo natural, porque el formulario borra
una imagen recién subida **antes** de guardar el producto: en ese momento no existe ninguna
fila que la reclame, y exigirla rompería el caso legítimo. Se comprobó leyendo el flujo del
`ProductImageDropzone` antes de elegir.

También se rechaza cualquier ruta con `..` —Flysystem ya lo impide, pero quien manda eso está
intentando algo— y se rechaza el borrado si no hay inquilino: **negar de más es recuperable,
borrar de más no.**

**Vigilado por cuatro tests**, incluido el del caso legítimo por HTTP —por ruta y por URL—
para que cerrar la puerta no se llevara por delante el flujo del formulario.

Los casos negativos se comprueban contra el servicio y no por HTTP, y queda anotado por qué:
`Storage::fake()` y el sufijado de disco de la tenancy no conviven bien en un test de
petición, y una aserción sobre el disco tras un `deleteJson` mide una raíz distinta de la que
usó el request. Se probó, dio falsos negativos, y un test que falla por el andamiaje es peor
que no tenerlo.

---

## PR2. 🟠 Actualizar el stock de un producto con variantes no hacía nada

> **Estado:** ✅ CERRADO — 23/08/2026

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

### ✅ Cómo se cerró

Las dos cosas, no una: el endpoint acepta `variant_id` y escribe en la variante, **y** si el
producto tiene variantes y no se dice cuál, responde 422 con el motivo. Se exige además que
la variante sea de ese producto — sin esa condición, un id de variante ajena repondría stock
de otro artículo del catálogo.

La regla vive en el repositorio y no en el caso de uso porque es una pregunta de
persistencia: **dónde** está el stock de este producto.

**Y un fallo latente que apareció de camino:** el controlador hacía
`code: (int) ($e->getCode() ?: 400)`. Un `QueryException` trae el SQLSTATE como cadena
—`'HY000'`—, y castearlo da **0**, que no es un estado HTTP válido: Symfony reventaba con
«The HTTP status code "0" is not valid» y el error real quedaba enterrado bajo un 500 sin
mensaje. Eso me costó tres intentos de diagnóstico. Ahora el código se acota a 400–599.

**Vigilado por cuatro tests:** sin variantes sigue funcionando, con variantes y sin decir
cuál se rechaza, la variante concreta se repone, y una variante de otro producto se rechaza.

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

> **Estado:** ✅ CERRADO — 30/08/2026

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

### 3. Y una tercera que la lectura inicial no vio

`Order::refund()` comprueba la guarda del **pedido** y después escribe
`paymentStatus = REFUNDED` sin comprobar nada:

```php
$this->status = OrderStatus::REFUNDED;
$this->paymentStatus = PaymentStatus::REFUNDED;   // ← sin guarda
```

Eso alcanza también a `RefundOrderUseCase`, que es un segundo endpoint. Un pago que **falló**
podía quedar marcado como «reembolsado»: ahí nunca hubo dinero que devolver.

## ✅ Cómo se cerró

`PaymentStatus` tiene ahora sus tres guardas, con el mismo estilo que `OrderStatus` y sin
auto-transiciones:

| Guarda | Desde |
| :--- | :--- |
| `canBePaid()` | `pending`, `failed` — un pago fallido se reintenta |
| `canBeFailed()` | `pending` |
| `canBeRefunded()` | `paid`, `pending` — el porqué de `pending` está más abajo |

Las tres se comprueban en `markPaymentPaid()`, `markPaymentFailed()` y `refund()`. En
`refund()` va **después** de la guarda del pedido, para no cambiar los mensajes que ya
existían.

`PENDING` deja de ser `null` en el caso de uso y **se rechaza con el motivo**. No se
implementa la reversión real: arrastraría deshacer la comisión ya activada, que es otro
módulo y otra decisión. Rechazar con motivo ya arregla lo que estaba roto —responder éxito a
un cambio que no ocurre—, y no finge arreglar lo que no arregla.

Se deja `'pending'` en el `in:` del FormRequest a propósito: rechazarlo en el dominio da un
mensaje que explica **por qué**, mientras que sacarlo de la lista sólo daría un 422 genérico.

**Sin clase de excepción nueva.** `InvalidOrderStateTransitionException` ya existía y el
controlador ya la convierte en 400; le basta un segundo constructor nombrado, `payment()`.
Nadie las distinguiría en un `catch`.

### ⚠️ El primer arreglo estaba mal, y lo cazó la suite

`canBeRefunded()` se escribió primero como `paid` a secas, con el razonamiento evidente: sin
pago no hay nada que devolver. **Es falso en este proyecto**, y lo dice el propio código unas
líneas más arriba, en el comentario de N15:

> *«para pago móvil, transferencia manual y contra entrega [el `payment_status`] es siempre
> `pending`»*

Un pedido entregado y cobrado en mano tiene el pago en `pending` **para siempre**, porque
nadie toca el endpoint de pagos. Exigir `paid` para reembolsar habría bloqueado la devolución
de los métodos de pago más usados de la plataforma.

Lo tumbó `TenantMonetizationAndCommissionTest` —el test del hallazgo D2— en la primera
ejecución: 400 donde esperaba 200. **La lectura no lo habría visto nunca**, porque el dato que
lo invalida no está en `Order` ni en `PaymentStatus`, sino en un comentario de otro módulo.
Es exactamente la distinción que este documento lleva marcando desde el principio, esta vez
en contra de quien la escribió.

Así que `pending → refunded` se permite. Lo que la guarda sí frena: **reembolsar un pago que
falló** y **reembolsar dos veces**.

### Vigilado por diez tests

Nueve en `tests/Unit/Order/Domain/OrderPaymentStatusTest.php` —los tres caminos legítimos
(`pending → paid`, reintento tras `failed`, y el reembolso de pago móvil desde `pending`) y
los rechazos (`refunded → paid`, `paid → paid`, `paid → failed`, `failed → refunded`, doble
reembolso)— y uno en `OrderUseCasesTest` que comprueba que `'pending'` falla **y que
`save()` no llega a ejecutarse**.

### Lo que queda fuera

Revertir `paid → pending` de verdad. Y el fondo del asunto que asomó al cerrar esto: en pago
móvil, transferencia y contra entrega **el `payment_status` no refleja la realidad**, se queda
en `pending` aunque el dinero haya entrado. Eso no es un problema de máquina de estados —es
que nadie registra ese cobro— y merece su propio hallazgo.

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

---

# Módulo Shipment

**Auditado el 23/08/2026.** Mismo método, con una pregunta añadida: Shipment escribe sobre
el pedido, y en Order acabamos de verificar que esas transiciones están protegidas en el
dominio. La pregunta era si Shipment las respeta.

**No las respeta.**

## SH1. 🟠 Entregar un envío fuerza el pedido a `delivered` saltándose su máquina de estados

> **Estado:** ✅ CERRADO — 30/08/2026

**Dónde:** `EloquentShipmentRepository::save()`

```php
$order = EloquentOrder::find($shipment->orderId());
if ($order !== null) {
    if ($shipment->isDelivered()) {
        $order->update([
            'status' => 'delivered',
            'delivered_at' => ...,
        ]);
    } elseif ($shipment->isInTransit() && ! in_array($order->status, ['delivered', 'cancelled', 'refunded'], true)) {
        $order->update(['status' => 'shipped', ...]);
    }
}
```

Escribe el estado del pedido **con `$order->update()` sobre el modelo Eloquent**, sin pasar
por la entidad `Order`. Y `Order::markAsDelivered()` sí comprueba `canBeDelivered()` y lanza
`InvalidOrderStateTransitionException` — pero desde aquí no se llama nunca.

### La asimetría es lo que lo delata

Mírense las dos ramas juntas:

| Rama | ¿Comprueba el estado del pedido? |
| :--- | :--- |
| `isInTransit()` → `shipped` | ✅ Sí — excluye `delivered`, `cancelled` y `refunded` |
| `isDelivered()` → `delivered` | ❌ **No comprueba nada** |

Alguien pensó en el problema para una rama y no para la otra. No es un descuido de diseño:
es un descuido de una línea.

### El camino completo

`CreateShipmentUseCase` **tampoco comprueba el estado del pedido** — crea el envío para
cualquier `order_id` que exista (la validación es `exists:orders,id` y nada más). Así que:

1. Un pedido en `processing` recibe un envío.
2. El pedido se cancela — legítimo, `canBeCancelled()` lo permite en `processing`. **Se
   repone el stock** (N13) y **se revierte la comisión** (D2).
3. Se marca el envío como entregado.
4. El pedido pasa a `delivered`.

### Lo que queda descuadrado

Un pedido que dice **entregado**, cuya mercancía volvió al inventario y por el que la
plataforma **no cobra comisión**. Las tres cosas no pueden ser ciertas a la vez.

Y no hace falta mala fe: basta con que el almacén marque la entrega de un paquete que ya
había salido, después de que atención al cliente cancelara el pedido. Dos personas haciendo
su trabajo.

## ✅ Cómo se cerró

Al trazar el camino real apareció algo que la lectura no había visto: **los tres caminos que
escriben un envío —crear, actualizar seguimiento y entregar— pasan por el mismo
`save()`**. No hacían falta tres guardas ni el «separadamente» que pedía este apartado. Una,
en el punto por donde pasan todos.

Y las tres preguntas del hallazgo resultaron ser la misma: *¿este pedido admite un envío?*

```php
public function acceptsShipments(): bool
{
    return in_array($this, [self::CONFIRMED, self::PROCESSING, self::SHIPPED, self::DELIVERED], true);
}
```

| Estado del pedido | ¿Admite envío? | Por qué |
| :--- | :--- | :--- |
| `pending` | ❌ | Despachar sin confirmar se salta el paso de confirmar |
| `confirmed`, `processing` | ✅ | El caso normal |
| `shipped`, `delivered` | ✅ | Ya tienen envíos, y un pedido admite varios |
| `cancelled`, `refunded` | ❌ | Cerrado: stock repuesto (N13) y comisión revertida (D2) |

La regla vive en `OrderStatus`, no en un `in_array` escrito a mano dentro de un repositorio.
Y las dos transiciones dejaron de tener listas propias: cada una consulta la guarda del
dominio que ya existía —`canBeShipped()` y `canBeDelivered()`—, así que la asimetría entre
ramas que delataba el fallo ya no puede reaparecer.

**Se rechaza, no se salta en silencio.** Es la misma clase de fallo que PR2 y OR1 —aceptar la
operación y no aplicarla—, y aquí además el `save()` corre dentro de `DB::transaction`, así
que el envío tampoco se guarda: no queda uno huérfano marcado como entregado.

**Con excepción propia**, `ShipmentNotAllowedForOrderException`, al revés que en OR1. El
motivo es quién la lee: en OR1 la come un `catch` de un endpoint de backoffice; aquí la come
el del almacén generando una guía, y *«no se puede cambiar el estado de la orden de 'pending'
a 'shipped'»* no le dice qué hacer. El mensaje ahora sí: **«El pedido está sin confirmar.
Confirma el pedido antes de generar la guía de despacho.»**

### El alcance se amplió a propósito

La auditoría proponía como mínimo copiar la exclusión a la rama de `delivered`. Se decidió ir
al arreglo correcto e incluir además **exigir el pedido confirmado antes de despachar**, que
la lectura había dejado fuera. Hoy un pedido `pending` con guía saltaba directo a `shipped`
sin pasar por `confirmed`.

Se planteó como decisión de negocio y no como corrección técnica, porque cambia cómo trabaja
el comerciante, y **se aprobó**.

### Y por eso hubo que tocar el frontend

Un backend que rechaza y una interfaz que sigue ofreciendo el botón es peor que no arreglar
nada: el comerciante pulsa «Nueva Guía Despacho» y se come un 400 sin saber por qué. En
`ShowOrderDetailPage` los tres botones que abren el modal comparten ahora un `puedeDespachar`
que replica `acceptsShipments()`, y el estado vacío de la sección de envíos explica el motivo
en vez de ofrecer un botón que va a fallar.

### Vigilado por tres tests

En `ShipmentLifecycleEndToEndTest`, sobre el harness que ya existía en vez de montar otro:

1. **El caso del hallazgo, en orden real:** el envío nace con el pedido vivo, el pedido se
   cancela después, y al marcar la entrega se rechaza. Se comprueba que el pedido sigue
   `cancelled` **y que el envío tampoco quedó entregado** —la transacción lo deshizo—,
   porque medio arreglo aquí sería otro descuadre.
2. Generar guía para un pedido cancelado se rechaza, y no queda envío suyo.
3. Generar guía para un pedido sin confirmar se rechaza, **y pasa en cuanto se confirma**:
   sin esa segunda mitad el test no distingue una guarda correcta de una puerta tapiada.

El ciclo legítimo completo que ya cubría el fichero sigue verde sin tocarlo.

---

## Lo que se comprobó y está BIEN

| Qué | Resultado |
| :--- | :--- |
| **Perímetro de rutas** | ✅ Las 7 rutas de `api-tenant/shipment/*` llevan `Authenticate` + `throttle:api` + `tenant_can:manage_orders` |
| **Rama `shipped` de la sincronización** | ✅ Excluye `delivered`, `cancelled` y `refunded` — es la que está bien hecha |
| **Entrega idempotente** | ✅ `Shipment::markAsDelivered()` sale temprano si ya está entregado, y rellena `shippedAt` si faltaba |
| **Validación de seguimiento** | ✅ `tracking_number` obligatorio con longitud, `carrier`, `service`, `cost` con `min:0` y `shipped_at` como fecha |
| **Validación al crear** | ✅ `order_id` con `exists:orders,id` |
| **Escritura transaccional** | ✅ El `save()` del repositorio corre dentro de una transacción, así que envío y pedido se actualizan juntos o no se actualiza ninguno |

---

# Módulo Payment

**Auditado el 23/08/2026.** Es el módulo con más dinero encima, así que la pregunta de
entrada fue la de siempre: ¿se fía alguien de un importe que llega del cliente?

**La respuesta corta: el camino que se usa de verdad está bien. El que no se usa, no.**

## Lo primero, una corrección del propio proceso

A mitad de la auditoría estuve a punto de anotar un hallazgo falso: que los datos bancarios
que el administrador configura —banco, cédula, teléfono, titular y Binance Pay ID de la
plataforma— **se guardaban y no los leía nadie**. El `grep` de `CentralSetting` fuera de
`src/Payment/` volvía vacío y encajaba con el patrón de C1 y T3.

**Era falso.** `CentralPaymentMethodsProvider` los lee y los lleva al checkout central; el
grep no lo vio porque el proveedor vive dentro del propio módulo. El comentario del
controlador lo dice sin ambigüedad:

> *«La Fase 0.5 sacó los datos de cobro de demostración del checkout del inquilino, pero el
> central se quedó con los suyos incrustados en el TSX. Ahora salen de `central_settings`, y
> un método sin configurar no se ofrece.»*

Queda escrito porque el error es instructivo: **un `grep` que vuelve vacío no demuestra que
algo esté muerto**, sólo que no está donde se buscó.

---

## PY1. 🟡 La capa de pasarelas es infraestructura paralela que no participa en ningún cobro

> **Estado:** ✅ CERRADO — 30/08/2026 · **Borrada**

El módulo tiene **dos mitades que no se tocan**.

### La mitad viva

| Qué | Quién la usa |
| :--- | :--- |
| `CentralPaymentMethodsProvider` | El checkout central, con los datos de `central_settings` |
| `StorefrontPaymentMethodsProvider` | El checkout de cada tienda, con los ajustes de esa tienda |

El comprador elige método, envía sus datos de pago, y el comerciante confirma el cobro con
`POST /api-tenant/order/{id}/payment-status`. **Ahí no interviene ninguna pasarela.**

### La mitad muerta

| Qué | Estado |
| :--- | :--- |
| `PagoMovilPaymentGateway`, `BinancePayPaymentGateway`, `CashOnDeliveryPaymentGateway`, `ManualBankTransferPaymentGateway` | Sólo los alcanza `/api-tenant/payment/process` |
| `POST /api-tenant/payment/process` | **Ninguna página del frontend lo llama** |
| `GET /api-tenant/payment/gateways` | Igual, sin llamantes |
| Tabla `payments` y modelo `Payment` | ~~Nada escribe en ella~~ — **esto era falso, ver abajo** |

Se comprobaron los tres: los proveedores no usan el factory de pasarelas,
`ListAvailablePaymentGatewaysUseCase` sólo lo usa su propio controlador, y no hay un solo
`Payment::create()` en `src/`.

### Por qué esto importa aunque hoy no rompa nada

`/api-tenant/payment/process` es un endpoint **vivo y autenticado** que:

- acepta un `amount` arbitrario (`numeric|min:0.01`),
- con `order_id` **opcional** (`nullable`),
- devuelve un `transaction_id` y un `PaymentResult::pending()`,
- y **no registra absolutamente nada** — `ProcessPaymentUseCase` es un paso directo al
  `charge()` de la pasarela y no guarda ni una fila.

Hoy no lo llama nadie. Pero es el endpoint que cualquiera cablearía el día que hiciera falta
«procesar un pago»: se llama exactamente así. Y quien lo hiciera obtendría un flujo **sin
vínculo con el pedido y sin verificación del importe** — el error de B1 esperando a que
alguien lo active.

> ### ⚠️ Corrección: la tabla `payments` estaba viva
>
> Escrito arriba: *«nada escribe en ella en todo el repositorio»*, y de ahí *«cualquier
> informe devolvería cero filas»*. **Las dos cosas son falsas.** Escriben dos sitios, y con
> cuidado:
>
> - `CreateStorefrontOrderPOSTController` en cada compra del escaparate. Lleva un comentario
>   explicando que le quitaron un `catch` vacío para que un pago sin registrar revierta el
>   pedido entero.
> - `DispatchCentralOrderToTenantsUseCase` en cada pedido multi-tienda, con otro explicando
>   que registra el importe imputable a cada tienda y no el subtotal bruto «porque la suma de
>   los `payments` no cuadraba».
>
> El `grep` buscó `Payment::create()` —el modelo Eloquent— y no vio
> `DB::table('payments')->insert()`. **Es el mismo error que este documento describe tres
> párrafos más arriba sobre `CentralSetting`**, cometido por quien acababa de escribirlo.
>
> Lo que sí era cierto: el **modelo** `Payment` no lo usaba nadie. Existe para esa tabla y
> los dos que escriben en ella lo esquivan.

## ✅ Cómo se cerró: borrada

Se eligió la primera salida. El negocio de hoy son métodos manuales que concilia el
comerciante, ese camino funciona y está probado, y cablear una integración de pasarela que
nadie pide es trabajo especulativo. Git conserva lo borrado para el día que entre una
pasarela real.

**De 33 ficheros a 8**, y los ocho son la mitad viva:

| Se fue | Se quedó |
| :--- | :--- |
| Las 4 pasarelas, el factory y su interfaz | `StorefrontPaymentMethodsProvider` |
| `ProcessPaymentUseCase`, `RefundPaymentUseCase`, `ListAvailablePaymentGatewaysUseCase` | `CentralPaymentMethodsProvider` |
| Sus 3 DTOs, sus 2 excepciones y los 5 VO que sólo ellos usaban | El VO `PaymentMethod` |
| Los 2 controladores, el FormRequest y las 2 rutas | `CentralSetting` y los ajustes del admin |
| El modelo `Payment`, sin un solo uso | La tabla `payments` y sus **dos** escritores |
| `PaymentServiceProvider` entero — no ataba otra cosa | `web.php` |

### La condición que se puso al aprobarlo

«Borrar la mitad muerta **y dejar el Pago Móvil, con la confirmación manual**». Se cumple
sola, y por un motivo que conviene dejar escrito porque es contraintuitivo: **el Pago Móvil
que funciona no pasa por `PagoMovilPaymentGateway`.** Su camino es otro:

1. `StorefrontPaymentMethodsProvider` saca banco, cédula y teléfono de los ajustes.
2. El comprador paga por su banco y envía la referencia.
3. `CreateStorefrontOrderPOSTController` crea el pedido y **registra el pago** en `payments`.
4. El comerciante confirma a mano con `POST /api-tenant/order/{id}/payment-status`.

Los cuatro pasos están en la mitad viva. El adaptador era una clase que sólo alcanzaba el
endpoint muerto.

**Y no se afirmó: se demostró.** El test del checkout con Pago Móvil se ejecutó **antes** de
borrar nada y **después**, y pasa igual. Tras OR1 —donde una afirmación razonable resultó
falsa y sólo la suite lo vio— esa comprobación dejó de ser opcional.

### Lo que apareció al borrar

Dos cosas que la lectura no había visto:

**1. `BillingLifecycleEndToEndTest` demostraba el hallazgo sin querer.** Entre emitir facturas,
«pagaba» 595.00 por `/payment/process` contra `'ORD-E2E-001'`, un `order_id` **que no existía
como pedido**, y el endpoint respondía éxito. Era exactamente el fallo descrito —importe
arbitrario, sin vínculo con ningún pedido, sin persistir nada— ejecutándose en verde dentro
de la propia suite del proyecto.

**2. Un `grep` de clases no encuentra rutas.** Al borrar se comprobaron las referencias por
nombre de clase y quedaron seis tests rotos que llamaban a los endpoints **por URL**. La misma
lección de la corrección de arriba, tercera vez en este documento: buscar en un solo eje no
demuestra nada.

`PaymentApiTest` se borró entero —sus cuatro tests eran los endpoints muertos—, y de
`TenantApiAuthorizationTest` la comprobación de perímetro se movió a
`order/{id}/payment-status`, que es la confirmación de cobro que existe de verdad. El fichero
`StorefrontPaymentGatewaysTest` pasó a llamarse `StorefrontCheckoutPaymentsTest`: ya no queda
ninguna pasarela y el nombre habría mandado al siguiente lector a buscar una capa que no está.

**Coste en tests: 12 menos.** Todos cubrían sólo el código borrado.

---

## Lo que se comprobó y está BIEN

| Qué | Resultado |
| :--- | :--- |
| **Perímetro de rutas** | ✅ Los ajustes centrales bajo `auth` + `super_admin`; los endpoints de tienda bajo `auth` + `throttle:api` + `tenant_can:manage_billing` |
| **Los datos de cobro llegan al comprador** | ✅ `CentralPaymentMethodsProvider` los lee de `central_settings` y **un método sin configurar no se ofrece** (G1 cerrado de verdad) |
| **Guardado de ajustes** | ✅ Validado en el controlador, `only(KEYS)` contra asignación masiva, `trim`, y **transaccional**: «todo o nada, unos datos de cobro a medias es justo lo que no queremos que vea el comprador» |
| **No hay credenciales guardadas** | ✅ Las cinco claves son datos de cobro públicos —banco, cédula, teléfono, titular, Binance Pay ID—. No hay `api_key` ni secretos en `central_settings` |
| **Pago Móvil no inventa cobros** | ✅ `charge()` devuelve `PaymentResult::pending()` con la referencia: registra la intención, no afirma que el dinero llegó |

### Una observación que no es hallazgo

El checkout muestra al comprador el **titular, la cédula y el teléfono** de la cuenta de
cobro. Es información personal en una página pública — y es **necesaria**: sin esos tres
datos no se puede hacer un Pago Móvil en Venezuela. Se anota porque llama la atención al
leerlo, no porque haya nada que corregir.
