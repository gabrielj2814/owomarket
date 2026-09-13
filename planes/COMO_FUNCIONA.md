# Cómo funciona OwoMarket

> **13/09/2026** · El negocio y la aplicación, en un sitio.
>
> Este fichero explica **cómo funciona**; [`ESTADO_DEL_PROYECTO.md`](ESTADO_DEL_PROYECTO.md)
> explica **qué está hecho**. Son los dos que hay que tener delante antes de tocar nada.
>
> **Se actualiza cada vez que se implementa algo que cambia una regla o un recorrido.** Un
> documento de «cómo funciona» que se queda atrás es peor que no tenerlo: se lee con confianza y
> miente.

---

## 1 · El negocio, en un párrafo

OwoMarket es un **marketplace multi-tienda para Venezuela**. Cada comercio tiene su propia tienda
en su subdominio (`tecs.owomarket.local`), con su catálogo, sus pedidos y su base de datos
aparte. Y todos se compran a la vez desde el dominio central (`owomarket.local`): un carrito con
productos de varias tiendas, **una sola factura**, un solo pago.

**La plataforma cobra todas las ventas.** El dinero entra en su cuenta, no en la del comerciante,
y después se le liquida descontando la comisión. Eso es lo que hace posible el carrito unificado
—y lo que obliga a todo el sistema de garantías que viene después.

Se paga en **bolívares**, por Pago Móvil o Binance Pay, contra un precio puesto en **dólares** y
convertido a la tasa del BCV del día. Cada venta guarda la tasa con la que se cobró: esa tasa
congelada manda en todo lo que pase después.

---

## 2 · Las tres audiencias

Son tres productos con tres públicos, y casi todas las decisiones de diseño salen de distinguirlos.

| Quién | Dónde vive | Qué hace |
| :--- | :--- | :--- |
| **Comprador** | Dominio central y escaparates | Compra, sigue su pedido, confirma la entrega, reclama |
| **Comerciante** | Su backoffice (dominio central) y su escaparate | Vende, despacha, responde reclamaciones, cobra |
| **Administrador** | Backoffice central | Aprueba tiendas y KYC, confirma cobros, paga retiros, arbitra |

**Las tres tienen su cuenta en la base central.** El comerciante y el administrador son filas de
`users`; el comprador, de `central_customers`. El `Customer` que vive en la base de cada tienda
es un registro comercial de esa tienda, **no una cuenta**: el SSO lo enlaza con la cuenta central
por `customers.central_uuid`.

### OwO Pass

Una sola cuenta de comprador para todas las tiendas. Se registra en el dominio central y entra en
cualquier escaparate con un clic: `/sso/consume` canjea un token y deja en la sesión de la tienda
`tenant_customer_id` y `central_customer_id`.

**El identificador que importa es el central.** Es con el que los subsistemas de garantías conocen
al comprador, y es el que nunca sale del cuerpo de una petición — siempre de la sesión.

---

## 3 · El recorrido del dinero

Es la columna vertebral del producto. Cada paso existe para cerrar un hueco concreto.

```
 compra  →  cobro  →  confirmación  →  despacho  →  entrega  →  liberación  →  reserva  →  retiro
                          (admin)                   (tienda)    (comprador)     (30 días)
```

**1 · Compra.** El comprador paga a la plataforma. Se crea el pedido central y, por cada tienda
del carrito, un pedido de tienda en su base. Se registra la comisión en `awaiting_payment`.

**2 · Confirmación del cobro.** Un administrador comprueba la referencia bancaria y la confirma.
La comisión pasa a `pending` y el pedido se despacha a las tiendas.
**Nada se mueve antes de esto**: el pago es manual por diseño, porque en Venezuela no hay pasarela
que lo automatice.

**3 · Entrega declarada.** El comerciante marca el pedido como entregado. **Esto ya no libera el
dinero: arranca un reloj.** Antes sí lo liberaba, y eso significaba que el comerciante declaraba
su propia entrega y con ella cobraba — esa era la puerta.

**4 · Liberación.** Hay exactamente dos causas legítimas, y **ninguna es el comerciante**:
- el comprador confirma que recibió, o
- pasan los días de espera sin que nadie diga nada (`central_delivery_confirmation_days`, 7).

**5 · Reserva.** Al liberar, un porcentaje de la parte del comerciante **se queda retenido 30 días
más** (`central_guarantee_reserve_days`). Ese colchón es lo que paga una reclamación posterior sin
tener que perseguir a nadie.

**6 · Retiro.** El comerciante pide, el administrador aprueba y transfiere. Se descuenta la
comisión interbancaria si el banco destino no es el de la plataforma.

### Los números que gobiernan todo esto

Los ocho son configurables desde **Reglas de garantía** en el backoffice. Vacío significa «usa el
valor por defecto», y el propio campo lo enseña en gris.

| Ajuste | Por defecto | Qué decide |
| :--- | ---: | :--- |
| `central_delivery_confirmation_days` | 7 | Cuánto se espera la confirmación del comprador |
| `central_payout_hold_days` | 1 | Retención tras liberar, antes de poder retirar |
| `central_guarantee_reserve_percent` | *según reputación* | Cuánto se aparta de cada venta |
| `central_guarantee_reserve_days` | 30 | Cuánto sigue apartado |
| `central_claim_window_days` | 14 | Cuánto tiene el comprador para reclamar |
| `central_claim_response_days` | 5 | Cuánto tiene la tienda para responder |
| `central_claim_coverage_cap` | $200 | Tope que pone la plataforma por reclamación |
| `central_claim_monthly_alarm_usd` | $2.000 | Techo mensual de alarma |

> ⚠️ **`central_guarantee_reserve_percent` es especial.** Si esa fila existe con cualquier valor,
> **el sistema de reputación deja de aplicarse** y todas las tiendas retienen lo mismo. Vacío =
> manda el nivel de cada tienda. La pantalla lo avisa junto al campo.

### La comisión

Se resuelve con tres niveles de prioridad: comisión personalizada de la tienda → comisión de su
plan de suscripción → **8% global por defecto**.

---

## 4 · Las garantías: por qué existen y cómo encajan

La pregunta que las origina está razonada entera en
[`anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`](anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md):
**si el comprador paga a la plataforma y el producto no llega o llega mal, ¿quién responde?**

La respuesta elegida: **la tienda primero, contra su fondo retenido; la plataforma cubre el hueco
hasta un tope por pedido.** De ahí salen cinco subsistemas.

| # | Qué resuelve |
| :--- | :--- |
| **1 · KYC del comerciante** | Sin identidad verificada **no se puede retirar dinero**. Cédula y RIF cifrados, con hash para poder buscar sin descifrar |
| **2 · KYC del comprador** | Cédula exigida **al reclamar**, no al comprar. Pedirla para comprar mata la conversión; una reclamación puede acabar en denuncia y no funciona contra alguien sin identificar |
| **3 · Entrega verificada** | El escrow del recorrido de arriba: la tienda declara, el comprador confirma, y solo entonces se libera |
| **4 · Fondo de garantía** | El porcentaje retenido, que decide la reputación |
| **5 · Reclamaciones** | Abrir, responder con reloj, resolver, y el expediente del administrador |

### La reputación, que no es una insignia

Tres niveles, y lo que deciden es **cuánto dinero se retiene de cada venta**:

| Nivel | Se retiene | Cómo se llega |
| :--- | ---: | :--- |
| **Alto** | 5% | 10 entregas confirmadas, sin silencios en 90 días y sin deuda |
| **Medio** | 10% | El punto de partida, y donde cae quien debe dinero |
| **Bajo** | 20% | Haber dejado vencer una reclamación sin responder, en los últimos 90 días |

**La deuda te baja a medio, nunca a bajo.** Una tienda que debe dinero necesita vender para
pagarlo, y hundirla a bajo haría más difícil justo lo que tiene que hacer. Es la misma razón por
la que suspender una tienda deudora **garantiza que no pague nunca**.

El nivel **se deriva en cada consulta**, no se guarda. Un nivel almacenado necesita disparadores
de recálculo, y uno desfasado es un porcentaje de reserva equivocado — o sea, dinero.

### El reloj de las reclamaciones

Una reclamación abierta que la tienda no contesta en 5 días **se resuelve a favor del comprador**.
Es lo que hace que ignorar deje de ser la estrategia ganadora.

Y para que esa consecuencia sea defendible, el comerciante recibe **dos avisos**: uno al abrirse y
otro el día antes de vencer. Perder una venta tras dos avisos es una decisión suya.

---

## 5 · La aplicación por dentro

### Dos bases de datos, y qué vive en cada una

| Base | Qué guarda |
| :--- | :--- |
| **Central** (`owomarket_dev`) | Tiendas y dominios, usuarios, clientes centrales, pedidos unificados, comisiones, liquidaciones, tasas BCV, catálogo maestro, KYC, reclamaciones, notificaciones, ajustes |
| **Por inquilino** (`tenant_*`) | Catálogo local, pedidos de tienda, clientes de esa tienda, envíos, facturas, cupones, reseñas, impuestos, ajustes de tienda |

**La regla práctica:** si un dato lo necesita el administrador, la reputación o el dinero, vive en
la central. Si es del día a día de una tienda, vive en la suya.

Hay un cruce deliberado: `customer_return_requests` es central aunque el pedido sea de una tienda.
Guardarlo en la del inquilino lo haría invisible para el administrador, la reputación y el fondo.

### Arquitectura hexagonal

```
src/{Contexto}/
├── Domain/           entidades y contratos, sin Laravel
├── Application/
│   ├── Contracts/    puertos
│   ├── DTOs/
│   ├── Service/      servicios de dominio
│   └── UseCase(s)/   la lógica de negocio
└── Infrastructure/
    ├── Eloquent/     modelos y repositorios
    ├── Http/         controladores y rutas
    └── Console/      comandos
```

Reglas que no se negocian, de [`reglas.md`](../reglas.md):

- **El dominio no importa Laravel.**
- **Los controladores son finos**: validan, llaman a un caso de uso, devuelven `ApiResponse`.
- **Toda llamada HTTP del frontend pasa por un servicio** de `resources/js/Services/`.
- **Flowbite en todas las vistas**; Tailwind puro solo para lo que Flowbite no cubre.
- **Ningún commit con tests en rojo o tipos rotos.**

### Las tres zonas visuales

Cada una con su propio tema de Flowbite, y son deliberadamente distintas: tres productos, tres
audiencias.

| Zona | Tema | Dónde se aplica |
| :--- | :--- | :--- |
| Portal del comprador | `portalTheme` | `CustomerAccountLayout`, con `root` para cortar herencia |
| Panel del comerciante | `tenantPanelTheme` | `TenantOwnerShell` |
| Escaparate y marketplace | `storefrontTheme` | `CentralLayout` y `StorefrontLayout` |

> Un componente que se pinta en varias zonas **no puede usar colores de un tema**: saldría sin
> relleno en las otras, sin dar ningún error.

### Quién es quién: los guards

| Guard | Modelo | Para quién |
| :--- | :--- | :--- |
| `web` / `auth` | `Src\User\...\User` | Administradores y comerciantes |
| `auth:central_customer` | `CentralCustomer` | Compradores en el dominio central |
| *(sesión SSO)* | — | Compradores en un escaparate: `session('central_customer_id')` |

> **Hay cuatro clases `User` sobre la tabla `users`.** `config/auth.php` usa la canónica
> (`Src\User\...`), y las notificaciones guardan un alias corto (`staff`, `customer`) en vez del
> nombre de la clase. Mezclarlas hace desaparecer datos sin dar ningún error.

### Los avisos

Once eventos, un puerto (`NotificationDispatcher`) que se lee como el catálogo de todo lo que la
plataforma anuncia. Campana siempre; correo **siempre** para lo que tiene dinero o un plazo
detrás, y opcional para el resto.

**Ninguna implementación del puerto puede lanzar excepciones.** Varios avisos salen desde dentro
de transacciones que mueven dinero: un mailer caído desharía la operación.

### Lo que corre solo

| Comando | Cuándo | Qué hace |
| :--- | :--- | :--- |
| `exchange-rate:sync-bcv` | 09:00, 13:00, 17:30 laborables | Tasa del BCV, con respaldo y aviso si se queda vieja |
| `returns:remind-expiring` | 02:45 | Segundo aviso de reclamaciones que vencen mañana |
| `deliveries:release-unconfirmed` | 03:00 | Libera el dinero de entregas que nadie confirmó |
| `returns:auto-resolve` | 03:30 | Resuelve a favor del comprador lo que la tienda no contestó |
| `coverage:check-ceiling` | 07:00 | Avisa si el gasto mensual en coberturas pasó del techo |

> El orden importa: el recordatorio corre **antes** que el reloj. Al revés recordaría
> reclamaciones que acaba de resolver.

---

## 6 · Cinco reglas que explican casi todo el código raro

Si algo parece innecesariamente complicado, suele ser una de estas.

**1 · La identidad sale de la sesión, nunca del cuerpo.** Aceptar un `customer_id` enviado por el
navegador ya permitió una vez registrar una devolución sobre el pedido de otro.

**2 · El servidor decide, la pantalla obedece.** `can_confirm`, `can_claim` y el resto los calcula
el backend con las mismas reglas que aplicará al recibir la acción. Una pantalla que llegue a su
propia conclusión enseña un botón que el backend rechaza — o esconde uno que sí funciona.

**3 · Los plazos se leen de su servicio, nunca se copian.** `ClaimResponseWindow` y `ClaimWindow`
existen por esto. Un aviso que dice «te quedan 2 días» y un comando que resuelve esa noche es peor
que no avisar.

**4 · Un fallo de aviso no puede deshacer dinero.** Los despachadores capturan y registran; nunca
propagan.

**5 · Cada venta lleva su tasa congelada.** Todo lo que se calcule después —saldo, reserva,
cobertura, techo mensual— usa la tasa de esa venta, no la de hoy. Mezclarlas produce números que
no corresponden a ningún dinero real.

---

## 7 · Cómo mantener este fichero

Cuando implementes algo, pregúntate: **¿cambia una regla, un recorrido o un número de los de
arriba?**

- **Sí** → actualiza aquí la parte que cambió, en la misma entrega. No al final.
- **No** (una pantalla nueva, un arreglo) → va solo en
  [`ESTADO_DEL_PROYECTO.md`](ESTADO_DEL_PROYECTO.md).

Lo que **no** va aquí: listas de lo hecho y lo pendiente —eso es del otro fichero— ni detalles que
el código ya explica mejor. Esto es el mapa, no el territorio.
