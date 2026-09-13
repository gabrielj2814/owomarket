# 🧪 Guion de prueba manual: todos los correos que envía OwOMarket

> **Para quien ejecute estas pruebas (persona o agente).**
>
> **No hay que programar nada.** Este documento es un guion: se ejecutan pasos, se mira qué llega
> a Mailtrap y se anota el resultado. Si algo no funciona, **se reporta — no se arregla**.
>
> Creado el 13/09/2026, tras la fase 3 de [`PLAN_NOTIFICACIONES.md`](../futuros/PLAN_NOTIFICACIONES.md).
> El registro de flujos vive en [`FLUJOS_DE_CORREO.md`](FLUJOS_DE_CORREO.md); este es cómo
> probarlos.

---

## ✅ Ejecutado el 13/09/2026 — 9 de 11 verificados

Con el VPN desconectado, el puerto 2525 abierto y todo comprobado en Mailtrap.

**Los nueve que pasaron**, incluidas las tres comprobaciones que más importaban:

| Comprobación | Resultado |
| :--- | :--- |
| El aviso de KYC **no lleva cédula, RIF ni nombre legal** | ✅ solo el nombre comercial |
| Aprobar un retiro dice **«pagado»**, no «rechazado» | ✅ el estado `settled` se lee bien |
| Una reclamación ganada **por silencio no dice «la tienda aceptó»** | ✅ y además avisa al dueño de que la perdió |
| Un correo sin cuenta **no dispara ningún envío** | ✅ no se puede enumerar usuarios |
| Lo crítico llega con el interruptor apagado | ✅ |
| Lo opcional no llega con el interruptor apagado | ✅ solo campana |

**Los dos que quedaron sin probar**, y por qué:

- **A2 · Reenvío de factura** — no había ninguna factura en la base de datos. El guion suponía
  que existía una; ahora incluye cómo emitirla primero.
- **A3 · Tasa BCV obsoleta** — se reportó como OK porque el comando corrió bien, **pero eso no
  prueba este correo**: el aviso solo sale cuando el scraping FALLA. Con el BCV respondiendo, el
  camino que envía el correo no se ejecuta. El guion ahora explica cómo forzar el fallo.

> **La lección, para la próxima:** *un comando que termina sin error no es una prueba del correo
> que envía cuando algo va mal.* Si el camino que manda el correo no se ejecutó, el resultado es
> **NO PROBADO**, no OK.

**Arreglado de lo reportado:** `MAIL_FROM_ADDRESS` ya no es `hello@example.com`; ahora es
`no-responder@owomarket.com` en `.env` y en `.env.example`.

---

## Reglas de la prueba

1. **No modifiques código.** Si un paso falla, anótalo y sigue con el siguiente.
2. **Anota siempre lo que viste**, no lo que esperabas ver. «No llegó» es un resultado válido y
   útil; «debería haber llegado» no dice nada.
3. **Un correo que llega no es un correo correcto.** Cada paso dice qué comprobar dentro.
4. **Los tests automáticos no cubren esto.** Usan `Mail::fake()` y `MAIL_MAILER=array`: no tocan
   la red, así que pueden estar todos verdes con el SMTP caído.

---

## Paso 0 — ¿Sale algo? (si esto falla, para aquí)

**El VPN bloquea los puertos SMTP.** Comprobado el 13/09/2026: DNS resuelve, pero los puertos
2525, 587, 465 y 25 salen todos bloqueados. **Desconecta el VPN antes de empezar.**

```bash
docker compose exec app php artisan tinker --execute="Illuminate\Support\Facades\Mail::raw('prueba', fn(\$m) => \$m->to('prueba@owomarket.local')->subject('Prueba OwOMarket')); echo 'enviado';"
```

| Resultado | Qué significa |
| :--- | :--- |
| `enviado` y aparece en Mailtrap | Todo listo, sigue al paso 1 |
| `Connection could not be established … timed out` | Red bloqueada. Desconecta el VPN y repite |
| `Authentication failed` | Las credenciales de `.env` ya no valen |

**Configuración actual:** Mailtrap sandbox, `MAIL_MAILER=smtp`, host `sandbox.smtp.mailtrap.io:2525`.

> El remitente es `no-responder@owomarket.com`. Era `hello@example.com` --el valor de ejemplo de
> Laravel-- hasta que la ejecución del 13/09/2026 lo señaló.

---

## Cómo provocar cada correo

Hay **once** envíos. Los tres primeros existían antes; los ocho restantes son del módulo de
notificaciones.

### Cuentas de prueba

| Quién | Correo | Contraseña |
| :--- | :--- | :--- |
| Superadministrador | `root@owomarket.local` | la de `USER_PASSWORD_DEV` en `.env` |
| Dueño de la tienda `tecs` | `tecs.owner@owomarket.local` | la misma |
| Comprador | `cliente@owomarket.local` | `Password123!` |

---

## A · Los tres correos que ya existían

### A1 · PIN de seguridad del administrador

1. Entra al backoffice como `root@owomarket.local`.
2. Ve a **Perfil** → apartado de cambiar contraseña.
3. Pide el PIN de seguridad.

**Debe llegar:** un correo con un código de 6 dígitos.

**Comprueba además:** que **ese** PIN es el que la pantalla acepta para completar el cambio. Es
el único de los once que bloquea una acción: sin correo, el administrador no puede cambiar su
contraseña.

### A2 · Reenvío de una factura

> **Ojo: en desarrollo no hay ninguna factura.** Comprobado el 13/09/2026: cero facturas en
> todos los inquilinos. Hay que **emitir una primero**, y eso es parte de la prueba.

1. Entra al panel de la tienda `tecs.owomarket.local` como su dueño.
2. Abre **Facturación** y **emite una factura nueva** (el módulo permite crearla directamente,
   sin necesidad de una venta previa).
3. Sobre esa factura, pulsa reenviar por correo. Acepta una dirección distinta a la del cliente:
   escribe la tuya.

**Comprueba además:** que el PDF adjunto **abre** y que los importes cuadran con la pantalla. Una
factura que llega rota ha fallado igual que una que no llega.

### A3 · Aviso de tasa BCV obsoleta

> **Correr el comando con éxito NO prueba este correo.** Es el fallo de lectura del 13/09/2026:
> el BCV respondió, la tasa se actualizó, y el camino que envía el aviso **ni se ejecutó**.

Hacen falta **dos condiciones a la vez**, y las dos hay que provocarlas:

1. Que el scraping del BCV **falle**.
2. Que la tasa activa lleve **3 días o más** sin actualizarse (`STALE_RATE_ALERT_DAYS`). Con una
   tasa de hoy, el comando solo deja un aviso en el log y **no manda correo**.

**Receta completa, verificada el 13/09/2026.** Envejece la tasa activa, corta el BCV, ejecuta y
lo devuelve todo:

```bash
docker compose exec app php artisan tinker --execute="\Src\ExchangeRate\Infrastructure\Eloquent\Models\ExchangeRate::where('is_active',true)->update(['rate_date' => now()->subDays(5)]); echo 'tasa envejecida';"
docker compose exec app sh -c "echo '127.0.0.1 www.bcv.org.ve' >> /etc/hosts"
docker compose exec app php artisan cache:forget exchange_rate:stale_alert_sent
docker compose exec app php artisan exchange-rate:sync-bcv
```

**Debe llegar a los superadministradores** un aviso de tasa congelada. En el log verás primero
`Fallo en sincronización con BCV: la tasa activa lleva 5 días sin actualizarse`.

**Y ahora devuélvelo todo, sin saltarte nada** — todo el sitio factura con esa tasa:

```bash
docker compose exec app sh -c "grep -v 'bcv.org.ve' /etc/hosts > /tmp/h && cat /tmp/h > /etc/hosts && rm /tmp/h"
docker compose exec app php artisan exchange-rate:sync-bcv
```

Comprueba que la última salida trae **la fecha de hoy**, no la que envejeciste.

> **Por qué no se usa `sed -i` para quitar la línea:** en Docker `/etc/hosts` es un punto de
> montaje, así que `sed -i` falla con *«Resource busy»* — intenta reemplazar el fichero entero y
> no puede. Hay que **sobrescribir el contenido en el mismo inodo**, que es lo que hace el
> `cat … > /etc/hosts` de arriba.

> ⚠️ **Tiene freno: sale una vez al día como mucho.** Si pruebas dos veces seguidas, la segunda
> **no sale y eso no es un fallo**. Para repetir:
> ```bash
> docker compose exec app php artisan cache:forget exchange_rate:stale_alert_sent
> ```

---

## B · Recuperación de contraseña *(nuevo — antes no se enviaba)*

Hasta el 13/09/2026 este flujo generaba el PIN y **no lo mandaba a ningún sitio**: en producción
nadie habría podido recuperar su contraseña.

1. Cierra sesión. Ve a `http://owomarket.local/forgot-password`.
2. Escribe `cliente@owomarket.local` y envía.

**Debe llegar:** un correo con un código de 6 dígitos y el texto «Caduca en 15 minutos».

**Comprueba además:**
- Que el código del correo **es el que la pantalla de restablecer acepta**.
- Que el correo dice qué hacer si no fuiste tú.

**Y una prueba que tiene que NO enviar nada:** repite con `noexiste@example.com`. La pantalla
debe responder **lo mismo** («Si ese correo tiene una cuenta…») y **no debe llegar ningún
correo**. Si llega, se puede averiguar qué direcciones tienen cuenta — anótalo como fallo grave.

---

## C · Los avisos del módulo de notificaciones

### Cómo funciona el reparto, para no reportar falsos fallos

| Tipo de aviso | ¿Sale por correo? |
| :--- | :--- |
| **Críticos** (reclamación abierta, entrega declarada, identidad resuelta) | **Siempre**, aunque el interruptor esté apagado |
| Los demás (retiros, cambios de plan, KYC enviado, reclamación resuelta) | **Solo si** la persona activó «Enviarme también por correo» |

**El interruptor está en la campana**, al final del desplegable. Para probar los no críticos,
**actívalo primero** con la cuenta que vaya a recibirlos.

---

### C1 · Reclamación abierta → dueño de la tienda *(crítico)*

Es el aviso más importante del sistema: sin él, el comerciante pierde la venta a los 5 días sin
saber que tenía que contestar.

1. Entra en `http://tecs.owomarket.local` como `cliente@owomarket.local` (botón **Entrar con OwO
   Pass**).
2. Ve a **Mis pedidos**. Si hay un artículo con el botón **«Tengo un problema»**, púlsalo.
3. Escribe un motivo y envía.

**Debe llegar a `tecs.owner@owomarket.local`:** «Tienes una reclamación sin responder».

**Comprueba además:** que dice **cuántos días quedan** para responder y que el botón lleva a la
pantalla de reclamaciones de la tienda — y que ahí aparece esa reclamación.

Si no hay ningún artículo reclamable, se puede provocar directamente:

```bash
docker compose exec app php artisan tinker --execute="\$d=app(Src\Notification\Application\Contracts\NotificationDispatcher::class); \$r=Src\CentralCustomer\Infrastructure\Eloquent\Models\CustomerReturnRequest::where('status','requested')->latest()->first(); \$d->claimOpened(\$r->id); echo 'avisado';"
```

### C2 · Entrega declarada → comprador *(crítico)*

Confirmar es lo que libera el dinero del comerciante. Sin este aviso, la venta se libera sola por
plazo.

En el panel de la tienda, marca un pedido como **entregado**.

**Debe llegar al comprador:** «Confirma que recibiste tu pedido».

**Comprueba además:** que explica **para qué** sirve confirmar («al confirmar, se le paga») y que
menciona los días que tiene para reclamar. Un botón sin motivo se ignora.

### C3 · Identidad verificada o rechazada → dueño *(crítico)*

1. Como `root`, ve a **Verificación de identidad** (KYC) en el backoffice.
2. Aprueba o rechaza un expediente. **Si rechazas, escribe un motivo.**

**Debe llegar al dueño de esa tienda:** «Tu identidad quedó verificada» o «no se pudo verificar».

**Comprueba además:** que si rechazaste, **el motivo aparece en el correo**. Y que en los dos
casos se dice que sin verificar no puede cobrar.

### C4 · Identidad enviada → superadministradores *(opcional)*

Activa el interruptor de correo con `root` antes de probar.

Como dueño de una tienda, envía el formulario de verificación de identidad desde la billetera.

**Debe llegar a `root`:** «Una tienda envió su identidad».

**Comprueba además — y esto importa:** el correo **NO debe contener la cédula, el RIF ni el
nombre legal**. Solo el nombre de la tienda. Si aparece un documento de identidad, **anótalo como
fallo grave**.

### C5 · Retiro solicitado → superadministradores *(opcional)*

Como dueño, solicita un retiro desde la billetera.

**Debe llegar a `root`:** «Hay un retiro esperando», con el nombre de la tienda y el importe.

### C6 · Retiro aprobado o rechazado → dueño *(opcional)*

Como `root`, aprueba o rechaza un retiro pendiente.

**Debe llegar al dueño:** «Tu retiro fue pagado» o «fue rechazado».

**Comprueba además:** que **aprobar dice «pagado», no «rechazado»** — el estado interno es
`settled` y con el estado mal leído el texto se invierte. Y que un rechazo lleva su motivo.

### C7 · Cambio de plan solicitado y resuelto *(opcional)*

Como dueño, pide un cambio de plan; como `root`, apruébalo o recházalo.

**Debe llegar:** a `root` al pedirlo, y al dueño al resolverse.

**Comprueba además:** esto cumple una promesa que la aplicación lleva haciendo desde agosto — la
pantalla responde *«Te avisaremos cuando la revisemos»*. Verifica que el aviso llega de verdad.

### C8 · Reclamación resuelta → comprador *(opcional)*

Como dueño, responde una reclamación (acepta o rechaza). O deja vencer el plazo:

```bash
docker compose exec app php artisan returns:auto-resolve
```

**Debe llegar al comprador:** «Tu reclamación fue aprobada» o «rechazada».

**Comprueba además — el texto más delicado de todos:** si se resolvió **por vencimiento del
plazo**, el correo **NO debe decir «La tienda aceptó tu reclamación»**. La tienda no aceptó nada:
no contestó. Debe decir que no respondió dentro del plazo. Si dice que la tienda aceptó, anótalo.

Y en ese caso **también debe llegarle al dueño** un aviso de que perdió la reclamación por no
responder.

---

## D · Dos comprobaciones sobre el reparto

### D1 · El interruptor no apaga lo crítico

1. Con `tecs.owner`, abre la campana y **desactiva** «Enviarme también por correo».
2. Provoca una reclamación abierta (C1).

**Debe llegar el correo igualmente.** Si no llega, es un fallo: la pantalla promete que los
avisos urgentes llegan siempre, y esa promesa tiene que ser verdad.

### D2 · Con el interruptor apagado, lo opcional no llega

1. Con el interruptor desactivado, provoca un retiro resuelto (C6).

**No debe llegar correo.** Sí debe aparecer en la campana.

---

## Plantilla para reportar

Copia esto y rellénalo. Un «no probado» es mejor que un «funciona» sin comprobar.

```
Fecha:
VPN: activo / desactivado
Paso 0 (¿sale algo?): OK / FALLA — detalle:

A1 PIN de seguridad:        OK / FALLA / NO PROBADO — qué pasó:
A2 Reenvío de factura:      OK / FALLA / NO PROBADO — qué pasó:
A3 Tasa BCV obsoleta:       OK / FALLA / NO PROBADO — qué pasó:
B  Recuperar contraseña:    OK / FALLA / NO PROBADO — qué pasó:
B  Correo sin cuenta (no debe enviar): OK / FALLA — qué pasó:
C1 Reclamación abierta:     OK / FALLA / NO PROBADO — qué pasó:
C2 Entrega declarada:       OK / FALLA / NO PROBADO — qué pasó:
C3 Identidad resuelta:      OK / FALLA / NO PROBADO — qué pasó:
C4 Identidad enviada:       OK / FALLA / NO PROBADO — ¿traía cédula?:
C5 Retiro solicitado:       OK / FALLA / NO PROBADO — qué pasó:
C6 Retiro resuelto:         OK / FALLA / NO PROBADO — ¿el texto acertó?:
C7 Cambio de plan:          OK / FALLA / NO PROBADO — qué pasó:
C8 Reclamación resuelta:    OK / FALLA / NO PROBADO — ¿atribuyó a la tienda una decisión que no tomó?:
D1 Crítico con correo apagado: OK / FALLA
D2 Opcional con correo apagado: OK / FALLA

Otros problemas vistos de paso:
```

---

## Si algo no llega: qué mirar antes de reportar

```bash
docker compose exec app tail -40 storage/logs/laravel.log
```

| Lo que dice el log | Qué significa |
| :--- | :--- |
| `Aviso sin destinatarios: la tienda no tiene dueños` | La tienda del aviso no tiene ningún usuario con rol `owner`. No es un fallo del correo |
| `Aviso sin destinatarios: no hay superadministradores activos` | Lo mismo para los avisos a la plataforma |
| `No se pudo enviar una notificación` | Ahí está el error real: cópialo entero |
| `No se pudo enviar el codigo de recuperacion` | El SMTP falló en el flujo de contraseña |
| *(nada)* | El aviso ni se intentó: revisa que el paso que lo dispara ocurriera de verdad |

**Un detalle de entorno:** los avisos van en cola. En local `QUEUE_CONNECTION=sync`, así que se
envían en el acto y **no hace falta ningún worker**. Si alguien cambia eso a `redis` sin arrancar
Horizon, no saldrá ningún correo y tampoco aparecerá nada en la campana.
