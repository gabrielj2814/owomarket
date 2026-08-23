# Barrido de comentarios que afirman protecciones — `admin/**` y `tenant/**`

> ## 📌 ESTADO — 23/08/2026
>
> **T1 ✅ cerrado.** Primer barrido transversal, no una auditoría por carpetas.
>
> Motivo: casi todo este código lo generó Gemini 3.7, y su defecto característico no es
> «falta código» sino **texto plausible que no se corresponde con el comportamiento**. Un
> generador escribe bien los comentarios; que digan la verdad es otra cosa.
>
> La auditoría de paneles del 22/08 (P0–P3) cubrió **la autorización** de estas carpetas y
> lo dijo explícitamente: *«primera pasada, centrada en AUTORIZACIÓN […] y no por el
> contenido de las páginas»*. Este barrido va a por el contenido.

---

## Método

Extraer los comentarios que **afirman** una garantía —«siempre», «nunca», «ya no», «no
puede», «se valida»— y verificar cada afirmación contra el código. No leer las 23.941 líneas.

---

## Afirmaciones verificadas y CIERTAS

No hace falta volver a mirarlas:

| Dónde | Qué afirma | |
| :--- | :--- | :--- |
| `GenerateTenantOwnerSsoTokenPOSTController` | «La identidad SIEMPRE sale de la sesión, nunca del cuerpo» | ✅ |
| `ListTenantOwnerProductsUseCase` | «el listado nunca se amplía más allá de sus propias tiendas» | ✅ |
| `Admin/Routes/web.php` | el PIN del administrador lleva `throttle:5,15` | ✅ |
| `CreateTenantOwnerPayoutRequestUseCase` | «no puede superar el saldo disponible» | ✅ **para el caso secuencial** — el saldo descuenta los retiros pendientes, así que pedir dos veces seguidas no cuela |

Esa última fila es la que abrió T1: la afirmación era cierta, pero sólo cubría la mitad del
recorrido del dinero.

---

## Una corrección sobre el recuento anterior

Al cerrar A2 se dijo que **tres** sitios afirmaban un límite de tasa inexistente. Verificado
aquí: eran **dos**.

- `apiCentral.php` — «el PIN ya llevaba freno desde la Fase 4.1» → **falsa**, hablaba del PIN
  del cliente, que no tenía nada.
- `GovernanceRoutesAreGatedTest` — «su protección es el límite de tasa» → **falsa**, no
  existía.
- `RateLimitingTest` — «puso `throttle:5,15` en las dos rutas del PIN» → **cierta**. Hablaba
  del PIN del **administrador**. Era ambigua, no mentirosa.

Al cerrar A2 esa tercera se dio por falsa y se reescribió, con lo que **se metió una
afirmación falsa donde había una verdadera** — el defecto que se venía a corregir. Está
revertida con la precisión que le faltaba.

**La lección:** una frase verdadera y vaga se confunde con una mentira. Antes de corregir un
comentario hay que verificar *qué* afirma exactamente — no sólo si la protección existe,
sino de qué ruta habla.

---

## T1. 🔴 El saldo se comprobaba al pedir el retiro y nunca más

> **Estado:** ✅ CERRADO — 23/08/2026

**Dónde:** `ApproveCentralPayoutRequestUseCase` — el paso donde el dinero sale.

Comprobaba tres cosas: que la solicitud existiera, que estuviera `pending`, y que trajera
referencia bancaria. **El saldo no se volvía a mirar en ningún momento.**

Y la creación (`CreateTenantOwnerPayoutRequestUseCase`) hacía su comprobación **sin
`lockForUpdate`**, dentro de una transacción — lo cual no impide nada: dos lecturas
simultáneas ven el mismo saldo y las dos pasan.

### Dos caminos al mismo sitio

1. **Concurrencia.** N solicitudes simultáneas, todas ≤ saldo, todas aceptadas.
2. **El saldo cambia entre pedir y aprobar.** Una devolución, un ajuste de comisión,
   cualquier cosa que reduzca las ganancias netas. El administrador aprueba y se paga sobre
   un saldo que ya no existe.

### Demostrado

El segundo camino, con un test: ventas de 500 y comisión de 40 → 460 disponibles; se solicita
el retiro de 460; después entra un ajuste de comisión de 400 que deja el saldo en 60; se
aprueba → **pasaba**. La plataforma pagaba 460 sobre un saldo de 60.

El primer camino (la carrera) está **verificado leyendo, no ejecutado**: el entorno de tests
usa SQLite en memoria, donde cada conexión abre una base distinta y no se puede montar
concurrencia real. Queda dicho para no vender como probado lo que no lo está.

### Es el mismo fallo que ya se corrigió dos veces

- **C3** — carrera al generar liquidaciones. Corregido, con su propio `SettlementConcurrencyTest`.
- **B3/C6** — «N peticiones paralelas pasaban todas la comprobación previa» en los cupones.
  Corregido.

La lección llegó a las liquidaciones y a los cupones, y **se saltó los retiros** — el único
de los tres donde sale dinero de verdad.

### ✅ Cómo se cerró

La fórmula del saldo vivía como método **privado** dentro del caso de uso de Tenant, así que
la aprobación —que está en el módulo Admin— no podía usarla. Ésa es la causa estructural: no
es que a alguien se le olvidara comprobar, es que **no tenía con qué**.

Se extrajo a `Src\Monetization\Application\Service\TenantAvailableBalance`, compartido por
los dos. Copiarla al módulo Admin habría sido fabricar el gemelo divergente a mano, y este
repositorio ya ha demostrado de sobra que las copias divergen.

**Son dos preguntas distintas y confundirlas rompe uno de los dos flujos:**

- `requestable()` — cuánto puede *pedir* el comerciante. Descuenta los retiros pagados **y
  los pendientes**: si no, pedir dos veces seguidas el saldo entero colaría.
- `settleable()` — cuánto se puede *pagar* ahora. **No** descuenta los pendientes, y es
  deliberado: al aprobar hay que comparar contra el importe de esta solicitud, y si se
  descontaran, la propia solicitud estaría restada de su propio respaldo y no se aprobaría
  jamás. Con dos pendientes a la vez se rechazarían las dos en vez de pagar la primera.

Al aprobar en orden, cada una pasa a `settled` y reduce el saldo de la siguiente. Ése es
exactamente el comportamiento que se quiere.

Se añadió además `lockForUpdate` en las dos rutas de lectura del saldo.

### Y un cuarto test que consagraba el fallo

`AdminPhaseOneOperationsTest` creaba retiros **sin ninguna comisión de respaldo** —un pago
sin ventas detrás— y afirmaba que se podían aprobar. Es el cuarto caso en un día de un test
que blinda el comportamiento equivocado, después de A3, A4 y C1. El fixture ahora crea las
ventas que respaldan el retiro.

---

## Barrido 4 — divergencia entre gemelos

**Método:** en vez de comparar carpetas a ojo, detectar mecánicamente **el mismo controlador
expuesto desde varias rutas con guardas distintas**. Es la forma exacta del hallazgo P0
(«el duplicado esquivaba al portero»), y sale de una consulta sobre `route:list --json`.

Seis divergencias. **Dos eran fallos, cuatro no.**

### T7. 🟠 `/auth/user/{uuid}` devolvía el perfil de cualquiera

> **Estado:** ✅ CERRADO — 23/08/2026

`CurrentUserGETController` está expuesto dos veces:

| Ruta | Guarda | Audiencia |
| :--- | :--- | :--- |
| `api/auth/interna/user/{uuid}` | `InternalServiceMiddleware` | servicio a servicio |
| `auth/user/{user_uuid}` | `auth` a secas | cara al usuario |

El controlador toma el uuid de la URL y **nunca lo compara con la sesión**. Para el interno
eso es correcto — su trabajo es consultar a cualquiera. Para el otro no, y **la semántica del
interno se coló en el de usuario**.

**Demostrado:** un `tenant_owner` pidiendo el perfil de un `super_admin` → **HTTP 200** con
nombre, correo y rol. La tabla `users` son el personal, los administradores y los
propietarios, así que un comerciante corriente podía enumerar a los administradores de la
plataforma con sus correos — el inventario que se necesita antes de una campaña de phishing.

Es el hallazgo P1 otra vez: *«pasan el `{user_uuid}` de la URL al caso de uso sin compararlo
nunca con la sesión»*.

**Cerrado con `own_user`**, el alias que ya existía desde P1. Y estaba declarada **dos
veces** —central y tenant—, así que se arreglaron las dos. El gemelo, otra vez.

**Un tropiezo que conviene anotar:** la primera versión del test creaba sólo la fila de
`users` y el endpoint devolvía 500 para todos los uuid. Parecía que no había fuga y era el
fixture: el endpoint lee de `auth_users`, una proyección. Un falso negativo por escenario
incompleto es tan peligroso como un falso positivo por lectura.

### T8. 🟡 Un tercer `sso-consume` sin límite de tasa

> **Estado:** ✅ CERRADO — 23/08/2026

`ConsumeTenantOwnerSsoTokenGETController` estaba declarado **tres** veces. Dos con
`throttle:sso`, que N18 puso a propósito por ser el canje de una credencial de un solo uso.
La tercera —`/tenant/auth/sso-consume`, en el dominio central— con sólo `web`.

Nadie generaba esa URL: `GenerateTenantOwnerSsoTokenUseCase` y `AdminImpersonateTenantUseCase`
construyen `{dominio_de_la_tienda}/auth/sso-consume`, que sí tiene freno.

Es la misma limpieza que hizo **P0 en ese mismo fichero** —«se borran los tres duplicados»—
con una que sobrevivió.

**Gravedad medida, no inflada:** el token es `bin2hex(random_bytes(32))`, 256 bits. No se
adivina, así que el freno era defensa en profundidad. Pero el controlador hace
`Auth::login($user, true)` —con «recuérdame»— y por esa puerta lo hacía en el dominio
central, que no es donde el flujo pretende dejar la sesión. Borrada.

### Las cuatro que NO eran fallos

| Divergencia | Veredicto |
| :--- | :--- |
| `GetSupportTicketDetail` y `UpdateSupportTicketStatus` con `support_session` vs `auth` | ✅ No es fuga. `ResolvesSupportRequester` deriva la identidad de la sesión en los dos casos y el caso de uso filtra por `requesterId`. La diferencia es de audiencia: el guarda central admite además sesión de cliente |
| `ListPlansGETController` bajo `super_admin` en central y público en tienda | ✅ Es el catálogo de planes, información comercial pública |
| `ConsultUserByEmailPOSTController` con y sin `throttle` explícito | ✅ Los dos llevan `InternalServiceMiddleware`; el que «faltaba» hereda `throttle:api` del grupo |
| `CurrentUserGETController` en sus dos variantes internas | ✅ Ambas tras `InternalServiceMiddleware` |

---

## Balance de los barridos

**Los cuatro están hechos.**

| Barrido | Resultado |
| :--- | :--- |
| Comentarios que afirman protecciones | ✅ **T1** — el saldo de los retiros |
| `alert()` en admin y tenant | ✅ los 6 — salieron **T3** y **T4** |
| Formularios con datos precargados | ✅ limpio (falso positivo: eran estados de aviso) |
| URLs escritas a mano | ✅ las 10 resuelven — el fallo de S1 no se repite |
| Operaciones sensibles sin límite de tasa | ✅ **T5** — cambiar el plan sin autenticarse |
| Divergencia entre gemelos | ✅ **T7** y **T8** |

**Lo que el método enseñó:** cruzar una pregunta por todo el repositorio rindió siete
hallazgos —dos de ellos en caminos de dinero— mientras que releer una carpeta ya auditada
(`marketplace/**`, 6.540 líneas) rindió cero. Y dos de los siete aparecieron auditando otra
cosa, no revisando la carpeta donde vivían.

Queda abierto **T6** en `por_revisar.md`: `/monetization/summary` y `/monetization/settlements`
siguen siendo GET públicos.
