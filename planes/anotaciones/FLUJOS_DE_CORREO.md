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

**Con el VPN activo no sale ningún correo.** Los cuatro puertos SMTP (2525, 587, 465 y 25) salen
bloqueados aunque el DNS resuelva bien — y cuatro puertos caídos a la vez no es Mailtrap, es la
red.

**Con el VPN apagado sí sale.** Verificado el 13/09/2026 contra Mailtrap real: nueve de los once
flujos comprobados uno a uno, incluidas las tres validaciones que más importaban —que el aviso de
KYC no lleve la cédula, que aprobar un retiro diga «pagado», y que una reclamación ganada por
silencio no le atribuya la decisión a la tienda—.

→ El informe completo y el guion están en [`PRUEBA_MANUAL_CORREO.md`](PRUEBA_MANUAL_CORREO.md).

### ~~Un detalle de configuración que sí conviene cambiar~~ ✅ ARREGLADO el 13/09/2026

El remitente era `hello@example.com`, el valor de ejemplo de Laravel — que muchos servidores
puntúan como spam. La prueba manual lo señaló y ahora es `no-responder@owomarket.com`, en `.env`
y en `.env.example`.

---

## Los tres envíos que existen hoy

Los tres son directos con `Mail::to()`. **No hay módulo de notificaciones**: eso es justo lo que
construye [`PLAN_NOTIFICACIONES.md`](../ESTADO_DEL_PROYECTO.md), y su fase 3 los migrará
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

### 2 · Reenvío de una factura ⏳ *sin verificar*

> **No se pudo probar el 13/09/2026: no hay ninguna factura en la base de datos de ningún
> inquilino.** Hay que emitir una primero desde el módulo de Facturación. Es el único de los once
> que sigue sin comprobarse contra un servidor real.


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

### 3 · Aviso de tasa BCV obsoleta ⏳ *camino verificado, entrega no*

> El 13/09/2026 se reportó como OK porque el comando corrió bien — **pero eso no prueba este
> correo**: el aviso solo sale cuando el scraping FALLA, y el BCV respondió. Forzando el fallo se
> confirmó que el camino llega hasta el mailer; la entrega quedó sin ver porque el VPN estaba
> activo en ese momento. La receta para forzarlo está en
> [`PRUEBA_MANUAL_CORREO.md`](PRUEBA_MANUAL_CORREO.md).


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
[`PLAN_NOTIFICACIONES.md`](../ESTADO_DEL_PROYECTO.md), y conviene que no se olvide.

El personal del backoffice no tiene flujo de recuperación en absoluto.

---

> **Desde el módulo de notificaciones hay diez envíos más** (fases 2 a 4). El guion completo para
> probarlos todos a mano —incluidos estos tres— está en
> [`PRUEBA_MANUAL_CORREO.md`](PRUEBA_MANUAL_CORREO.md).
>
> Los dos últimos, de la fase 4, son **periódicos**: los dispara un comando diario y **llevan
> freno**, así que la segunda ejecución del día no manda nada. Eso no es un fallo.

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
