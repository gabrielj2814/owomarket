# 📋 Plan: Módulo de notificaciones

> **Estado:** plan de implementación, listo para empezar · Redactado el 23/08/2026 ·
> **Reescrito el 12/09/2026** tras leer el código con los subsistemas 1–5 ya construidos.
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
morfismos** (`Relation::enforceMorphMap`) con alias cortos y estables:

```php
Relation::enforceMorphMap([
    'staff'    => Src\User\Infrastructure\Eloquent\Models\User::class,
    'customer' => Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer::class,
]);
```

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

### Fase 1 — El núcleo y los dos avisos que hacen funcionar lo ya construido

Lo mínimo que existe y ya sirve.

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

### Fase 2 — El resto del ciclo de garantías

- KYC verificado o rechazado → comerciante.
- Reclamación resuelta → comprador (con el motivo, que ya se guarda en `resolution_notes`).
- Reclamación resuelta **por silencio** → comerciante. Es la que más va a doler, y es la que
  hace que la siguiente sí se conteste.
- Retiro y cambio de plan resueltos → comerciante.
- KYC, retiro y cambio de plan pendientes → administrador.

### Fase 3 — El correo

- Canal `mail` **en cola**, con las preferencias mandando.
- Plantillas sobre el layout de correo que ya usan las tres que existen.
- **Migrar los tres envíos sueltos** al módulo, para que dejen de ser tres caminos paralelos.
- El freno (`NotificationThrottle`) aplicado a todo lo repetible.

**Antes de esta fase hay que decidir el proveedor de correo.** Hoy `MAIL_MAILER=log`: nada sale.

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
