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

## Lo que queda por barrer

Este barrido cubrió una pregunta. Las otras, por orden de rendimiento esperado:

1. **`alert()` en `admin/**` y `tenant/**`** — ya localizados **6**, en tarifas de cambio,
   gestión de administradores, soporte y facturación. Mismo hallazgo que A5 y C2.
2. **Formularios con datos precargados reales** (clase S3). En un panel de administración
   sería peor que en el alta pública.
3. **Divergencia entre gemelos** admin/tenant.
4. **Operaciones sensibles sin límite de tasa.**

Ya descartado, con evidencia: **las 10 URLs escritas a mano en esas carpetas resuelven a
rutas reales.** El fallo de S1 no se repite ahí.
