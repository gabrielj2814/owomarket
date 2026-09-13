# 📋 Plan: Módulo de notificaciones

> **Estado:** 🟨 **Fases 1, 2 y 3 HECHAS el 13/09/2026** · Fase 4 pendiente ·
> Redactado el 23/08/2026 · Reescrito el 12/09/2026 con el código delante.
> **Es lo que más rinde ahora, y ya no compite con nada:** los cinco subsistemas de garantías
> están terminados y todos dependen de que alguien se entere.
>
> Escrito para retomarlo sin contexto previo.

---

## 🎯 Objetivo

Que las tres audiencias se enteren de lo que les pasa. Hoy no se entera ninguna.

---

## 🔍 La evidencia, actualizada

No existe módulo de notificaciones. En todo `src/` hay **tres** envíos de correo, todos
directos con `Mail::to()` y sin nada compartido detrás:

| Dónde | Para qué |
| :--- | :--- |
| `Admin/.../LaravelSecurityPinMailerService` | PIN de seguridad |
| `Billing/.../LaravelInvoiceMailerService` | Enviar una factura |
| `ExchangeRate/.../MailStaleRateAlerter` | Avisar de tasa obsoleta |

**Y no hay tabla `notifications`.** `database/migrations/` no tiene ninguna: la que Laravel
crea con `notifications:table` nunca se generó.

### Lo que ha cambiado desde agosto, y que el plan original no podía saber

El plan de agosto listaba siete silencios. Desde entonces se construyeron los subsistemas 3, 4
y 5, y **dos de sus silencios son peores que todos los de aquella lista**, porque no son
comodidad: son dinero que se mueve solo.

| Sucede | Debería enterarse | Qué pasa si no se entera |
| :--- | :--- | :--- |
| **Se abre una reclamación contra su tienda** | Comerciante | El reloj resuelve **a favor del comprador a los 5 días** (`returns:auto-resolve`). Pierde la venta sin haber sabido nunca que tenía que contestar |
| **La tienda declara la entrega** | Comprador | Confirmar es lo que **libera el dinero**. Sin aviso, la venta se libera por vencimiento y el comprador pierde su ventana de 14 días para reclamar sin enterarse |

Esos dos son la razón de que este plan sea el siguiente. El resto sigue vigente:

| Sucede | Debería enterarse |
| :--- | :--- |
| Se verifica o rechaza su KYC | Comerciante |
| Llega un pedido del marketplace central a su tienda | Comerciante |
| Se aprueba o rechaza su retiro | Comerciante |
| Se resuelve su cambio de plan | Comerciante |
| Se resuelve su reclamación | Comprador |
| Cambia el estado de su pedido | Comprador |
| Su reseña se publica o se modera | Comprador |
| Hay un KYC, un retiro o un cambio de plan esperando | Administrador |
| **El gasto mensual en coberturas pasó del techo** | Administrador |

El último es del 12/09: `MonthlyCoverageSpend` ya calcula el número y lo pinta donde el
administrador entra, pero **nada le obliga a mirarlo**. La decisión de garantías pide una
«revisión obligatoria», y obligar es justo lo que falta.

### Dos promesas que la aplicación ya hace y no puede cumplir

1. **El cambio de plan** responde: *«Solicitud enviada. Te avisaremos cuando la revisemos.»* No
   hay con qué avisarle. Se escribió sabiendo que el canal no existe.
2. **La campana del backoffice.** `NavBarMovilDashboardComponent` pinta un icono de campana —
   pero está **dentro de la etiqueta del desplegable del avatar**, así que pulsarla abre el menú
   de perfil. Es decoración con forma de promesa.

---

## 🧱 Lo que se descubrió al leer el código, y que decide el diseño

### 1. Las tres audiencias viven en la base CENTRAL

Era el riesgo grande de un sistema multi-inquilino, y **no existe**:

| Audiencia | Modelo | Base |
| :--- | :--- | :--- |
| Administrador | `users` | central |
| Comerciante (personal de tienda) | `users` + pivote `tenant_users` | central |
| Comprador | `central_customers` | central |

El `Customer` de la base del inquilino **no es destinatario de nada**: es el registro comercial
de esa tienda, y el SSO lo enlaza con su cuenta central por `central_uuid`. El destinatario
siempre es la cuenta central.

**Consecuencia:** una sola tabla `notifications` en la base central. Sin tablas por inquilino,
sin consultas cruzadas, sin decidir en qué base vive un aviso.

### 2. Hay CUATRO clases `User` sobre la misma tabla

```
src/Admin/Infrastructure/Eloquent/Models/User.php          → users
src/Authentication/Infrastructure/Eloquent/Models/User.php → users
src/Tenant/Infrastructure/Eloquent/Models/User.php         → users
src/User/Infrastructure/Eloquent/Models/User.php           → users
```

La tabla `notifications` de Laravel es polimórfica: guarda `notifiable_type` con el nombre de
la clase. Con cuatro clases sobre la misma fila, **la misma persona acumularía avisos bajo
cuatro tipos distintos**, y una consulta filtrada por uno se dejaría los otros tres fuera. El
fallo no daría ningún error: simplemente faltarían avisos, que es la peor forma de fallar para
esto.

**Decisión:** el módulo normaliza. Un solo destinatario canónico por audiencia
—`Src\User\...\User` para el personal, `CentralCustomer` para el comprador— y un **mapa de
morfismos** con alias cortos y estables:

```php
Relation::morphMap([
    'staff'    => Src\User\Infrastructure\Eloquent\Models\User::class,
    'customer' => Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer::class,
]);
```

> **`morphMap`, no `enforceMorphMap`.** Este plan proponía el segundo y habría tumbado el
> arranque: además de registrar alias, exige que *toda* relación polimórfica de la aplicación
> esté en el mapa y lanza para las que no — `model_has_roles` de Spatie Permissions y el
> `addressable` de las direcciones, ninguna relacionada con esto. Corregido al construir la
> fase 1.

Guardar `'staff'` en vez del nombre completo de la clase tiene un segundo beneficio: **mover o
renombrar la clase deja de romper las filas ya guardadas**.

### 3. «Avisar a la tienda» no es avisar a nadie

Una tienda no tiene buzón: tiene personas. El pivote `tenant_users` guarda `role` con valores
`owner`, `admin`, `manager`, `staff`.

Hace falta una pieza —`TenantRecipients`— que responda «a quién de esta tienda se avisa de
esto». Empieza en **solo los `owner`**: son los que responden por el dinero, y avisar a los
cuatro roles de todo convierte el buzón en ruido el primer día.

### 4. El sistema ya sabe una lección sobre alarmas, y está escrita

`MailStaleRateAlerter` lleva un freno: **un aviso al día como mucho**, con la marca en caché
hasta el final del día. Su comentario dice por qué: `exchange-rate:sync-bcv` corre tres veces
al día, así que un BCV caído una semana produciría 15 correos *«y el aviso dejaría de leerse
justo cuando importa»*.

Esa lección se hereda entera. Los avisos repetibles —stock bajo, techo mensual, reclamación sin
responder— necesitan freno desde el primer día, no cuando molesten.

El mismo fichero trae la segunda lección: **un fallo de correo no puede tumbar la operación de
negocio**. Se registra y se sigue.

### 5. En local la cola está en `sync`

`QUEUE_CONNECTION=sync` y `MAIL_MAILER=log`. Horizon sobre Redis está verificado en Docker
(`AUDITORIA_DOCKER_2026_08_23.md`), pero **en local una notificación encolada sin worker se
queda esperando para siempre** — le pasó a `DispatchCentralOrderJob`.

Por eso el canal `database` va primero y **sin cola**: escribir una fila es más barato que
encolar, y así el centro de notificaciones funciona en local sin arrancar nada. El correo, que
sí es lento y sí puede fallar, va en cola.

---

## ⚙️ El diseño

### Estructura del módulo

```
src/Notification/
├── Domain/
│   └── Event/                      NotifiableEvent (qué pasó, sin saber a quién ni por dónde)
├── Application/
│   ├── Contracts/
│   │   └── NotificationDispatcher  el puerto que usan los casos de uso
│   ├── Service/
│   │   ├── TenantRecipients        quién de una tienda recibe qué
│   │   └── NotificationThrottle    el freno heredado de MailStaleRateAlerter
│   └── UseCase/
│       ├── ListNotificationsUseCase
│       ├── MarkNotificationReadUseCase
│       └── UpdateNotificationPreferencesUseCase
└── Infrastructure/
    ├── Laravel/                    las clases Notification del framework
    ├── Eloquent/Models/            Notification, NotificationPreference
    └── Http/                       controladores y rutas
```

**Las notificaciones de Laravel por debajo, no un sistema propio.** El framework ya trae
`Notification`, colas, canales y la tabla. Lo que se construye encima es lo que él no sabe:
quién de una tienda recibe qué, el freno, y las preferencias.

### El puerto, y por qué los casos de uso no llaman a Laravel

Los casos de uso que ya existen —`ResolveReturnRequestUseCase`, `DeclareOrderDeliveredUseCase`,
`ReviewTenantKycUseCase`— reciben un `NotificationDispatcher` y llaman a un método. No importan
`Illuminate\Notifications`, no saben de canales y no saben de destinatarios.

Dos razones, y la segunda es la que importa:

1. `reglas.md` §2: el dominio no importa Laravel.
2. **Un aviso que falla no puede deshacer una reclamación.** Si `ResolveReturnRequestUseCase`
   enviara el correo dentro de su `DB::transaction`, un fallo del mailer revertiría la
   resolución — y con ella la reversión de comisión. La implementación del puerto **captura sus
   propios errores y los registra**, exactamente como `MailStaleRateAlerter`.

### Preferencias

Tabla `notification_preferences`: destinatario + tipo de evento + canal + activo.

**Ausencia significa «sí, por `database`».** Un buzón vacío porque nadie rellenó preferencias es
el mismo silencio que hoy, con más código. El correo, en cambio, se activa: es intrusivo y
cuesta entregabilidad.

**Tres avisos no se pueden apagar**, y conviene decirlo en la pantalla: la reclamación abierta,
la entrega declarada y el KYC resuelto. Los tres tienen un reloj o dinero detrás, y el silencio
le cuesta dinero a quien lo eligió.

---

## 🚧 Las fases

### Fase 1 — El núcleo y los dos avisos que hacen funcionar lo ya construido ✅ (13/09/2026)

> **Verificado en la aplicación real**, no solo en tests: se abrió una reclamación contra
> `tecs`, su dueño vio el «1» rojo en la campana, la abrió, leyó «Te quedan 5 días para
> responder» y al pulsar llegó a la pantalla de reclamaciones — que dice lo mismo, porque el
> plazo sale del mismo `ClaimResponseWindow`.

**Lo que se construyó:** la tabla `notifications` en la base central, el mapa de morfismos, el
puerto `NotificationDispatcher` con su implementación de canal `database`, `TenantRecipients`,
los dos avisos de dinero, el buzón con sus dos rutas y la campana.

#### Una trampa que habría tumbado la aplicación entera

El plan proponía `Relation::enforceMorphMap()`. **Habría reventado el arranque**: ese método no
solo registra alias, también exige que *toda* relación polimórfica de la aplicación esté en el
mapa y lanza para las que no. Aquí hay dos que no tienen nada que ver con notificaciones:
`model_has_roles` de Spatie Permissions y el `addressable` de las direcciones.

Lo correcto es `Relation::morphMap()`, que registra sin exigir.

#### Dos cosas que el plan no había previsto

**`config/auth.php` ya apuntaba a las dos clases canónicas.** De las cuatro clases `User`, los
guards resuelven exactamente la de `Src\User`, y el comprador `CentralCustomer`. Así que
`$request->user()` devuelve siempre un destinatario del mapa y **un solo controlador sirve a las
dos audiencias**: lo único que cambia es el guard que lo protege.

**`Notifiable` escribe en la conexión por defecto**, que en un sistema multi-inquilino es la del
inquilino que esté inicializado. Un aviso escrito mientras alguien navega una tienda habría
acabado en la base de esa tienda, y el buzón —que lee la central— lo habría enseñado vacío. Sin
error. Lo corta el trait `NotifiableCentral`, que sustituye la relación `notifications()`.

#### Dos decisiones de la campana, sobre el ruido

**El contador solo aparece si hay algo.** Un «0» permanente entrena a no mirar, y esta campana
lleva avisos con un reloj detrás.

**Abrirla no marca nada como leído.** Es tentador —deja el número limpio— pero borraría el
rastro de lo que no se ha atendido: el comerciante abriría por curiosidad y perdería la única
señal de que tiene una reclamación esperando. Se marca al pulsar el aviso.

---

### Lo que la fase 1 incluía, y así quedó

- Migración `notifications` en la base central (la de Laravel, más un índice por
  `notifiable_type, notifiable_id, read_at`, que es la consulta del buzón).
- El mapa de morfismos en un proveedor de servicios.
- `NotificationDispatcher` con su implementación de canal `database`.
- `TenantRecipients` (solo `owner`).
- **Los dos avisos de dinero:**
  - *Reclamación abierta* → dueños de la tienda, con **los días que le quedan** para responder,
    que los da `ClaimResponseWindow`. No un número copiado: si divergen, la pantalla promete
    días que el reloj no respeta.
  - *Entrega declarada* → comprador, con el enlace a confirmar.
- Rutas y pantalla: buzón + contador de no leídas.
- **La campana decorativa pasa a funcionar.** Sacarla de la etiqueta del desplegable del avatar,
  que es donde está hoy.

**Tests:** que el aviso llegue a los `owner` y no al `staff`; que un fallo del despachador **no**
revierta la resolución de la reclamación; que el buzón de un comprador no vea los de otro.

### Fase 2 — El resto del ciclo de garantías ✅ (13/09/2026)

> **Verificado en la aplicación real:** el superadministrador vio *«Una tienda envió su
> identidad — cosplay_ está esperando verificación. Sin ella no puede cobrar»* en su campana.
> Es la tercera audiencia, que hasta ahora no había recibido nada.

Nueve eventos en total, y el puerto se lee como el catálogo de todo lo que la plataforma
anuncia:

| Evento | Quién lo recibe |
| :--- | :--- |
| `claim.opened` | Dueños de la tienda |
| `claim.resolved` | Comprador |
| `claim.timedout` | Dueños de la tienda, **solo si la resolvió el reloj** |
| `delivery.declared` | Comprador |
| `kyc.submitted` | Plataforma |
| `kyc.reviewed` | Dueños de la tienda |
| `payout.requested` | Plataforma |
| `payout.resolved` | Dueños de la tienda |
| `plan.requested` / `plan.resolved` | Plataforma / dueños |

#### Nueve clases de notificación se quedaron en una

El idiom de Laravel es una clase por aviso. Al llegar a nueve, las nueve hacían lo mismo:
declarar `['database']` y devolver un array. **Nueve ficheros de veinte líneas cuya única
diferencia es el texto no son nueve conceptos: son un array con ceremonia.**

Queda `InboxNotification`, y el texto vive en el despachador —que ya tenía inyectados los
servicios de los que salen los plazos—. Añadir un aviso pasó de «un fichero nuevo» a «un
método». La fase 3 no se bloquea: `toMail()` puede resolver la plantilla por el `type`.

#### Un solo enganche cubre dos comportamientos

`AutoResolveStaleReturnsUseCase` **reutiliza** `ResolveReturnRequestUseCase` con
`resolvedBy: 'timeout'`. Así que enganchar el aviso en la resolución cubre los dos caminos, y es
el despachador quien decide a quién avisa mirando ese campo. Eso es exactamente lo que compra
que el puerto hable de **eventos** y no de destinatarios.

#### Tres textos que el código tuvo que ir a leer

1. **`settled`, no `paid`.** Es el estado que deja `ApproveCentralPayoutRequestUseCase` al
   aprobar un retiro. Con el estado supuesto, el aviso le habría dicho «rechazado» a quien
   acababa de cobrar.
2. **Una aprobación por silencio no la aprobó la tienda.** El mismo error que ya se corrigió en
   la pantalla del comprador, ahora también en el buzón.
3. **Los rechazos llevan su motivo** —KYC, retiro, plan—. Un rechazo sin explicación deja a
   quien lo recibe adivinando qué corregir.

#### Y una trampa de fixtures que conviene recordar

`type` **no está en `$fillable`** de `Src\User\...\User`, así que `User::create(['type' =>
'super_admin'])` lo descarta en silencio y el usuario nace sin tipo. `platformAdmins()` entonces
no encuentra a nadie y los avisos a la plataforma se pierden —con su aviso en el log, pero sin
error—. En producción no pasa: `CreateSuperAdminCommand` lo asigna directamente.

### Fase 3 — El correo ✅ (13/09/2026)

**Probada contra Mailtrap real el 13/09/2026**, con el VPN apagado: los ocho avisos del módulo
llegaron y las tres validaciones que más importaban pasaron —el aviso de KYC no lleva la cédula,
aprobar un retiro dice «pagado», y una reclamación ganada por silencio no le atribuye la decisión
a la tienda—. El informe está en
[`PRUEBA_MANUAL_CORREO.md`](../anotaciones/PRUEBA_MANUAL_CORREO.md).

Solo quedan sin verificar los dos correos **que ya existían antes** de este módulo: el reenvío de
factura (no hay ninguna factura en la base) y el aviso de tasa BCV obsoleta (solo sale cuando el
scraping falla).

#### El reparto: lo crítico no se apaga, el resto llega apagado

Los dos errores posibles duelen en direcciones opuestas. Mandar de más llena de correo a quien no
lo pidió —y entonces deja de leer también los que importan—. Mandar de menos deja a un
comerciante sin enterarse de una reclamación con el reloj corriendo.

| | Sale por correo |
| :--- | :--- |
| `claim.opened`, `delivery.declared`, `kyc.reviewed` | **Siempre.** Tienen un reloj o dinero detrás |
| Los demás | Solo si la persona lo activó |

Son exactamente los tres que este plan ya dejaba sin poder apagar. Que se puedan apagar sería
como dejar apagar la alarma de incendios — y el interruptor lo dice donde se apaga, porque creer
que silenciaste ruido y perder una reclamación sería culpa de esa frase.

#### Una preferencia por persona, no una por tipo de aviso

La tentación es «cada aviso con su casilla». Nadie rellena veinte casillas, y un panel de
preferencias vacío es el mismo silencio de hoy con más pantallas. La pregunta es una: **¿te mando
también correo?** La tabla admite una columna `types` el día que alguien pida afinar.

#### La plantilla es la de Laravel

Los tres correos que ya existían construyen su HTML a mano, cada uno el suyo. `MailMessage` se ve
bien, es responsive y respeta `MAIL_FROM_NAME`. Escribir una cuarta plantilla propia habría sido
una más que nadie mantiene.

#### Se arregló un callejón sin salida

`SendCentralCustomerPasswordResetPinUseCase` generaba el PIN, lo guardaba con 15 minutos de
caducidad **y no lo mandaba a ningún sitio**. El controlador lo devuelve solo en `local` y
`testing`, así que **en producción nadie habría podido recuperar su contraseña**.

Va por `Notification::route('mail', …)` porque quien recupera no tiene sesión, y **no se encola**
a diferencia del resto: no es un aviso, es un paso que la persona está esperando delante de la
pantalla. Con la cola caída, encolarlo la dejaría mirando «revisa tu correo» para siempre.

#### Una trampa que costó un test

`ShouldQueue` necesita `Illuminate\Bus\Queueable`, no `InteractsWithQueue`: es el que aporta
`$connection`, `$queue` y `$delay`. Sin él, encolar lanza «Undefined property: $connection» — y
con el despachador capturando, **el aviso se perdía sin que nadie viera el error**. Lo cazó el
test del catálogo, no el ojo.

#### Dos cosas del plan que NO se hicieron, y por qué

**Migrar los tres envíos sueltos al módulo.** Son tres flujos que funcionan; reescribirlos ahora
es riesgo sin beneficio inmediato, y uno de ellos —la factura— lleva adjunto. Queda pendiente y
anotado; el módulo no los necesita para nada.

**El freno (`NotificationThrottle`).** Ninguno de los nueve avisos actuales se repite: una
reclamación se abre una vez, una entrega se declara una vez. El caso que de verdad repite es el
techo mensual de la fase 4, y **construir el freno antes de tener qué frenar es inventarse un
problema**. La lección de `MailStaleRateAlerter` sigue anotada para entonces.

### Fase 4 — Lo periódico

- **Techo mensual superado → administrador.** Aquí sí hace falta un comando: el número se
  deriva al consultarlo, así que sin alguien que pregunte no hay momento en el que avisar.
  Mensual, con freno.
- *Stock bajo* → comerciante, si se retoma [`PLAN_HISTORIAL_STOCK.md`](PLAN_HISTORIAL_STOCK.md).
- Reclamación **a punto de vencer** → comerciante, un día antes. Es el aviso que convierte el
  reloj en justo: perder por silencio después de dos avisos es decisión suya.

---

## 🚫 Fuera de alcance, y por qué

- **SMS y WhatsApp.** Cuestan por mensaje y exigen decidir proveedor. El canal `database` cubre
  la mayor parte del valor a coste cero.
- **Push del navegador.** Service worker y permisos; no compensa hasta que lo de arriba funcione.
- **Plantillas configurables por el comerciante.** Más adelante, si hace falta.
- **Notificar al `Customer` de la base del inquilino.** No tiene cuenta ni sesión propia: el
  destinatario es siempre la cuenta central enlazada. Un comprador que compró como invitado no
  recibe nada, que es el mismo precio ya aceptado en
  [`PLAN_PEDIDOS_ESCAPARATE.md`](../por_hacer/PLAN_PEDIDOS_ESCAPARATE.md).

---

## ⚠️ Las trampas, anotadas antes de caer en ellas

**El buzón se llena y deja de leerse.** Es el mismo fallo que el techo mensual evita siendo una
alarma y no un muro. Por eso los `owner` primero y no los cuatro roles, y por eso el freno desde
el día uno.

**Un aviso dentro de una transacción puede deshacer dinero.** El despachador captura y registra;
nunca propaga.

**Cuatro clases `User` sobre una tabla.** Sin normalizar el destinatario, faltarán avisos y
nadie verá un error.

**`sync` en local.** Si la fase 3 encola correos y alguien prueba sin worker, no saldrá ninguno
y parecerá un fallo del código.

**Los plazos se leen de su servicio, nunca se copian.** `ClaimResponseWindow` y `ClaimWindow`
existen justo por esto. Un aviso que dice «te quedan 2 días» y un comando que resuelve esa noche
es peor que no avisar.

---

## 🔗 Relación con los otros planes

- [`PLAN_RESOLUCION_DEVOLUCIONES.md`](PLAN_RESOLUCION_DEVOLUCIONES.md) — **superado** por el
  subsistema 5, fase A. Revisar antes de usarlo.
- [`PLAN_REGISTRO_AUDITORIA.md`](PLAN_REGISTRO_AUDITORIA.md) — **superado**: la pista de
  auditoría ya existe con su pantalla.
- [`PLAN_HISTORIAL_STOCK.md`](PLAN_HISTORIAL_STOCK.md) — sigue vigente, y su aviso de stock bajo
  es una notificación de la fase 4.
