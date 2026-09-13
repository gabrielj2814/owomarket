# 📧 Flujos que envían correo — lista para probar a mano

> **Para qué es esto:** comprobar a mano que cada envío sale de verdad. Los tests no lo
> comprueban —`Mail::fake()` no toca la red— y en esta máquina el VPN bloquea los puertos SMTP,
> así que **la única prueba válida es la que hace una persona con la red abierta**.
>
> **Se actualiza cada vez que se añade un envío.** Si un flujo manda correo y no está aquí, la
> lista miente y deja de servir.
>
> Creado el 13/09/2026.

---

## Antes de probar nada: ¿sale algo?

Si esto falla, no hace falta probar ningún flujo — no va a salir ninguno.

```bash
docker compose exec app php artisan tinker --execute="Illuminate\Support\Facades\Mail::raw('prueba', fn(\$m) => \$m->to('prueba@owomarket.local')->subject('Prueba OwOMarket')); echo 'enviado';"
```

**Comprobado el 13/09/2026 y falló** con `Connection could not be established … Operation timed
out`. El diagnóstico descarta la configuración:

| Comprobación | Resultado |
| :--- | :--- |
| DNS de `sandbox.smtp.mailtrap.io` desde el contenedor | resuelve bien |
| Puertos 2525, 587, 465 y 25 | **los cuatro bloqueados** |

Cuatro puertos caídos a la vez no es Mailtrap: es la red. **Con el VPN activo, el correo saliente
no funciona.** Hay que probar con el VPN apagado.

### Un detalle de configuración que sí conviene cambiar

`MAIL_FROM_ADDRESS="hello@example.com"` es el valor de ejemplo que trae Laravel. Sale como
remitente en los tres correos de abajo, y muchos servidores lo puntúan como spam. Debería ser un
buzón del dominio, por ejemplo `no-responder@owomarket.com`.

---

## Los tres envíos que existen hoy

Los tres son directos con `Mail::to()`. **No hay módulo de notificaciones**: eso es justo lo que
construye [`PLAN_NOTIFICACIONES.md`](../futuros/PLAN_NOTIFICACIONES.md), y su fase 3 los migrará
para que dejen de ser tres caminos paralelos.

### 1 · PIN de seguridad del administrador

| | |
| :--- | :--- |
| **Quién lo recibe** | El propio administrador |
| **Qué lo dispara** | Pedir el PIN al ir a **cambiar su contraseña** desde el perfil |
| **Código** | `GenerateSecurityPinUseCase` → `LaravelSecurityPinMailerService` |
| **Plantilla** | `app/Mail/SecurityPinMail.php` |

**Cómo probarlo:**

1. Entra al backoffice como `root@owomarket.local`.
2. Ve a **Perfil** (`/admin/backoffice/{tu-uuid}/profile`).
3. En el apartado de cambiar contraseña, pide el PIN de seguridad.
4. En Mailtrap debe llegar un correo con un código de 6 dígitos.

**Qué comprobar además del envío:** que **ese** PIN es el que la pantalla acepta para completar
el cambio de contraseña, y que caduca cuando dice que caduca. Es el único de los tres que
bloquea una acción: si el correo no llega, el administrador no puede cambiar su contraseña.

### 2 · Reenvío de una factura

| | |
| :--- | :--- |
| **Quién lo recibe** | El cliente de la factura, o el correo que se escriba al reenviar |
| **Qué lo dispara** | El comerciante pulsa reenviar en su módulo de facturación |
| **Código** | `ResendInvoiceMailUseCase` → `LaravelInvoiceMailerService` |
| **Ruta** | `POST /api-tenant/billing/invoices/{id}/resend-email` |

**Cómo probarlo:**

1. Entra al panel de una tienda (por ejemplo `tecs.owomarket.local`) como su dueño.
2. Abre **Facturación** y elige una factura existente.
3. Pulsa reenviar por correo. Acepta un correo distinto al del cliente: escribe uno tuyo.
4. En Mailtrap debe llegar la factura.

**Qué comprobar además del envío:** que el PDF adjunto abre y que los importes cuadran con la
pantalla. Una factura que llega rota es peor que una que no llega.

### 3 · Aviso de tasa BCV obsoleta

| | |
| :--- | :--- |
| **Quién lo recibe** | Todos los superadministradores |
| **Qué lo dispara** | `exchange-rate:sync-bcv` cuando **no puede leer el BCV** y la tasa activa ya está vieja |
| **Código** | `SyncBcvExchangeRateUseCase` → `MailStaleRateAlerter` |
| **Plantilla** | `app/Mail/StaleExchangeRateMail.php` |

Es el más difícil de provocar, porque hace falta que el BCV falle.

**Cómo probarlo:** apaga la red del contenedor o apunta la URL del BCV a algo que no responda, y
lanza:

```bash
docker compose exec app php artisan exchange-rate:sync-bcv
```

**Ojo con el freno:** este aviso sale **una vez al día como mucho**. La marca vive en caché hasta
el final del día, así que si pruebas dos veces seguidas el segundo no sale — y eso **no es un
fallo**. Para repetir:

```bash
docker compose exec app php artisan cache:forget exchange_rate:stale_alert_sent
```

El freno es deliberado: el comando corre tres veces al día, así que un BCV caído una semana
produciría 15 correos y el aviso dejaría de leerse justo cuando importa.

---

## ⚠️ Un flujo que debería mandar correo y no lo manda

### ~~Recuperación de contraseña del comprador~~ ✅ ARREGLADO el 13/09/2026

> Ya se envía, en la fase 3. Lo de abajo queda como registro de lo que pasaba.



`SendCentralCustomerPasswordResetPinUseCase` genera un PIN de 6 dígitos, lo guarda con 15
minutos de caducidad… **y no lo envía a ningún sitio.**

El controlador devuelve el PIN en la respuesta **solo en `local` y `testing`**. Fuera de ahí
manda `null`. O sea:

- En desarrollo funciona, porque el PIN vuelve en el JSON y la pantalla lo usa.
- **En producción el comprador nunca recibe nada**, y no hay forma de recuperar la contraseña.

No es un agujero de seguridad —el PIN no se filtra fuera de local— pero sí un callejón sin
salida en cuanto se despliegue. Es trabajo de la fase 3 de
[`PLAN_NOTIFICACIONES.md`](../futuros/PLAN_NOTIFICACIONES.md), y conviene que no se olvide.

El personal del backoffice no tiene flujo de recuperación en absoluto.

---

> **Desde la fase 3 (13/09/2026) hay ocho envíos más**, los del módulo de notificaciones. El
> guion completo para probarlos todos a mano —incluidos estos tres— está en
> [`PRUEBA_MANUAL_CORREO.md`](PRUEBA_MANUAL_CORREO.md).

## La fase 1 de notificaciones no añadió ningún correo ✅ (13/09/2026)

Y es deliberado. La fase 1 es canal `database`: escribe una fila y la pinta en la campana. El
correo es lento, puede fallar y depende de entregabilidad — y de rebote, esto permitió construir
y probar el buzón entero **con el VPN puesto**.

Los dos avisos que ya funcionan, y que llegarán también por correo en la fase 3:

| Aviso | Quién lo recibe | Qué lo dispara |
| :--- | :--- | :--- |
| Reclamación abierta | Los **dueños** de la tienda | Un comprador abre una reclamación |
| Entrega declarada | El comprador (si tiene cuenta central enlazada) | La tienda marca el pedido como entregado |

**Cuando la fase 3 encienda el canal `mail`, estos dos entran en la lista de arriba con sus
pasos**, y cada aviso nuevo detrás de ellos.

---

## Cómo se añade un flujo a esta lista

Un envío nuevo entra aquí con cuatro cosas, y ninguna es opcional:

1. **Quién lo recibe.** Si la respuesta es «la tienda», está mal: una tienda no tiene buzón,
   tiene personas.
2. **Qué lo dispara**, en pasos que alguien pueda seguir sin leer el código.
3. **Si tiene freno**, y cómo reiniciarlo. Un aviso que no sale porque el freno lo paró se
   confunde con un aviso roto.
4. **Qué comprobar además de que llegue.** Un correo que llega con el importe equivocado ha
   fallado igual.
