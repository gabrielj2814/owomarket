# 📋 Plan: Módulo de notificaciones

> **Estado:** propuesta, sin aprobar.
> **Origen:** auditoría del 23/08/2026.
> **De los cuatro planes futuros, es el que más rinde**, porque multiplica el valor de los
> otros tres y de cosas que ya están construidas.

---

## 🎯 Objetivo

Que las tres audiencias se enteren de lo que les pasa. Hoy no se entera ninguna.

---

## 🔍 Por qué: la evidencia

No existe módulo de notificaciones. En todo `src/` hay **tres** envíos de correo, todos
directos con `Mail::to()` y sin nada compartido detrás:

| Dónde | Para qué |
| :--- | :--- |
| `Admin/.../LaravelSecurityPinMailerService` | PIN de seguridad |
| `Billing/.../LaravelInvoiceMailerService` | Enviar una factura |
| `ExchangeRate/.../MailStaleRateAlerter` | Avisar de tasa obsoleta |

Todo lo demás ocurre en silencio:

| Sucede | Debería enterarse | Se entera hoy |
| :--- | :--- | :--- |
| Llega un pedido del marketplace central a su tienda | Comerciante | ❌ |
| Se aprueba o rechaza su retiro | Comerciante | ❌ |
| Se resuelve su cambio de plan | Comerciante | ❌ |
| Su tienda queda suspendida | Comerciante | ❌ |
| Cambia el estado de su pedido | Cliente | ❌ |
| Se responde su devolución | Cliente | ❌ |
| Su reseña se publica o se modera | Cliente | ❌ |

### El caso que lo resume

El flujo de cambio de plan (hallazgo **T3**, implementado el 23/08) responde al comerciante:

> *«Solicitud enviada. Te avisaremos cuando la revisemos.»*

**Y no hay con qué avisarle.** Esa frase se escribió sabiendo que el canal no existe, y queda
como deuda explícita: o se construye esto, o esa promesa es otra de las que este proyecto
hace y no cumple.

---

## 🗺️ Alcance

### Núcleo compartido

- Un módulo `src/Notification/` con las notificaciones de Laravel por debajo — **no hay que
  inventar nada**, el framework ya trae `Notification`, colas y canales.
- **Canal `database` primero**, correo después. Un centro de notificaciones dentro de la
  aplicación es más barato de construir, no depende de entregabilidad de correo, y sirve
  igual para las tres audiencias.
- Preferencias por usuario: qué quiere recibir y por dónde.

### Por audiencia

| Audiencia | Eventos mínimos |
| :--- | :--- |
| **Comerciante** | Pedido nuevo · retiro resuelto · cambio de plan resuelto · tienda suspendida · stock bajo |
| **Cliente** | Cambio de estado del pedido · devolución resuelta · reseña moderada |
| **Administrador** | Alta de tienda pendiente · retiro pendiente · cambio de plan pendiente |

---

## ⚙️ Nota de infraestructura

**Las notificaciones deben ir en cola, y la cola ya funciona.** El 23/08 se verificó Horizon
sobre Redis en Docker, procesando trabajos de verdad (ver `AUDITORIA_DOCKER_2026_08_23.md`).

Pero atención a lo que se aprendió ahí: en local, `QUEUE_CONNECTION` está en `sync`. Una
notificación encolada **sin worker** se queda en la cola para siempre, exactamente como le
pasaba a `DispatchCentralOrderJob`. Eso hay que tenerlo presente al probar.

---

## 🚫 Fuera de alcance

- **SMS y WhatsApp.** Cuestan dinero por mensaje y exigen decisiones de proveedor. El canal
  `database` cubre el 80% del valor a coste cero.
- **Notificaciones push del navegador.** Necesitan service worker y permisos; el retorno no
  compensa hasta que lo de arriba funcione.
- **Plantillas de correo configurables por el comerciante.** Más adelante, si hace falta.

---

## 🔗 Relación con los otros planes

Los otros tres planes futuros **funcionan sin esto, pero rinden la mitad**:

- `PLAN_RESOLUCION_DEVOLUCIONES.md` — el cliente tendría que entrar a mirar si le
  respondieron.
- `PLAN_REGISTRO_AUDITORIA.md` — sin alertas, el registro sólo sirve para el forense de
  después, no para enterarse a tiempo.
- `PLAN_HISTORIAL_STOCK.md` — el aviso de stock bajo es justamente una notificación.
