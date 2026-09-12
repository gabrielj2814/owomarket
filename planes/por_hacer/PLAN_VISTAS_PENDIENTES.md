# Plan — Las vistas que faltan por diseñar

> **Estado:** 🟨 En curso · Redactado el 11/09/2026 · **Vistas 1, 2 y 3 implementadas el 11/09/2026**
>
> Los cinco subsistemas de [`DECISION_GARANTIAS_Y_RESPONSABILIDAD.md`](../anotaciones/DECISION_GARANTIAS_Y_RESPONSABILIDAD.md)
> están completos **por detrás**. Este documento recoge la interfaz que les falta, con lo que
> cada pantalla tiene que hacer y por qué.
>
> Está escrito para retomarlo en otra sesión sin contexto previo: cada vista trae sus endpoints
> reales, su comportamiento y los errores que tiene que saber contar.

---

## ⚠️ Léase esto antes de elegir por dónde empezar

> **Los dos bloqueos de esta sección están CERRADOS.** Se dejan escritos porque explican por
> qué las vistas 1 y 2 existen y qué no hay que deshacer. Lo que sigue describe el estado
> anterior al 11/09/2026.
>
> Apareció además **un tercer bloqueo, por el otro extremo**: `TenantKycCard` pedía
> `/owner/api/kyc/{id}` sin el prefijo `tenant` de la ruta real, así que devolvía 404 siempre,
> el componente lo tragaba en su `catch` y la tarjeta no se pintaba nunca. **Ningún comerciante
> podía enviar su identidad**, de modo que darle al administrador una pantalla para verificar no
> habría servido de nada. Arreglado moviendo la URL a `TenantKycServices`, con un test que mira
> a dónde apunta de verdad — los tests del componente no podían verlo porque doblaban axios, y
> un doble de axios responde a cualquier URL.

### 1. Ninguna tienda puede retirar dinero. Nunca.

El KYC exige verificación para solicitar un retiro. Pero
[`ReviewTenantKycUseCase`](../../src/Admin/Application/UseCase/ReviewTenantKycUseCase.php)
**no tiene controlador ni ruta**: no existe ninguna forma de que un administrador verifique a
nadie. Todos los expedientes se quedan en `pending` para siempre.

**Esto convierte el subsistema 1 en un bloqueo total del cobro.** Es la vista más urgente de la
lista, y hasta que exista conviene marcar los perfiles a mano.

### 2. El reloj de reclamaciones aprueba todo por silencio

`returns:auto-resolve` corre a diario y resuelve a favor del comprador lo que la tienda no
responde en 5 días. **No existe pantalla donde el comerciante pueda responder**, así que hoy
toda reclamación se aprueba sola y revierte la venta.

Mitigación temporal mientras no haya interfaz: subir `central_claim_response_days` en los
ajustes de cobro.

---

## Contexto técnico común

| | |
| :--- | :--- |
| **Stack** | Laravel 12 + Inertia + React 19 + TypeScript |
| **UI** | `flowbite-react` + Tailwind 4 |
| **Tests** | Vitest + React Testing Library en `tests/Frontend/Components/` |
| **Puerta de calidad** | `npx tsc --noEmit` y `npx vitest run` en verde antes de commit |

Los servicios de API viven en `resources/js/Services/`. Los tipos compartidos, en
`resources/js/types/`.

**Convención que conviene respetar:** las pantallas de este proyecto no inventan estado propio
cuando el backend ya lo calcula. Por ejemplo `can_confirm` viene resuelto del servidor para que
la pantalla del comprador y la de la tienda no lleguen a conclusiones distintas sobre el mismo
expediente. Si una vista necesita decidir algo, primero mirar si el endpoint ya lo dice.

---

## Vista 1 — Revisión de KYC (administrador) ✅ HECHA (11/09/2026)

> **Lo entregado:** `ListTenantKycProfilesUseCase`, cuatro controladores y sus rutas bajo
> `staff:manage_tenants` —junto al expediente 360° y el estado de gobernanza, porque es la misma
> clase de decisión: «quién es esta tienda»—, más `AdminKycReviewPage.tsx` con `AdminKycServices`.
>
> **Una desviación deliberada del plan.** La búsqueda por identidad NO es el
> `kyc/identity-search` de texto libre que se proponía aquí, sino
> `GET /admin/api/kyc/profiles/{id}/identity-matches`. Motivo: **la pantalla no tiene el
> número**. Está cifrado y el listado no lo devuelve, así que un buscador donde el administrador
> escriba la cédula sería una caja que nadie puede rellenar — y habría obligado a traer el
> documento al navegador para poder escribirlo, deshaciendo el cifrado por la puerta de atrás.
> Yendo por `id` de expediente, el número se descifra, se convierte en hash y se compara sin
> salir del servidor.
>
> La carga inicial filtra por `pending`, igual que el selector que la pantalla trae
> seleccionado: si no lo hiciera, la primera vista mostraría todos los expedientes bajo una
> etiqueta que dice «Pendientes».

### Cómo era antes de existir

**Dónde:** `resources/js/pages/admin/kyc/AdminKycReviewPage.tsx`

**Falta también el backend HTTP.** Hay que crear controlador y ruta antes de la vista:

- `GET /backoffice/{user_uuid}/kyc` — listado de expedientes, `pending` primero
- `POST /backoffice/{user_uuid}/kyc/{profileId}/review` → `ReviewTenantKycUseCase`
- `GET /backoffice/{user_uuid}/kyc/identity-search` → `FindTenantsByIdentityUseCase`

### Qué tiene que hacer

**Listar** los expedientes con `status`, nombre legal, tienda, teléfono, dirección y fecha de
envío. Lo pendiente primero y lo más viejo arriba: es lo que lleva más tiempo bloqueando el
cobro de alguien.

**Verificar o rechazar.** Rechazar **exige motivo** — el backend devuelve 422 sin él, y el
comerciante necesita saber qué corregir o acabará en soporte preguntando lo que la pantalla
debería haberle dicho.

**Avisar de otras tiendas con la misma identidad.** Es el motivo por el que el KYC existe: sin
esa comprobación, quien quema una tienda abre otra y empieza limpio. La búsqueda devuelve
nombre, estado y fecha de las tiendas que comparten cédula o RIF.

> **Tener dos tiendas no es una falta.** El resultado informa a quien decide, no bloquea. El
> texto de la pantalla tiene que dejarlo claro para que nadie rechace por reflejo.

### Lo que NO debe mostrar

**La cédula y el RIF no vuelven en el listado.** Están cifrados en reposo y el modelo los oculta
al serializar. Si la revisión necesita verlos, eso es un endpoint aparte con su propio registro
de acceso — y una decisión que conviene tomar despacio, no un campo más en una tabla.

### Estados vacíos

- Sin expedientes pendientes: *«No hay verificaciones pendientes.»*
- Sin expedientes en absoluto: explicar que aparecerán cuando una tienda envíe sus datos.

---

## Vista 2 — Reclamaciones de la tienda (comerciante) ✅ HECHA (11/09/2026)

> **Lo entregado:** `TenantReturnsPage.tsx` con `TenantReturnServices`, su ruta
> `/tenant/owner/backoffice/{user}/returns` y una pestaña en la barra del propietario.
>
> **Hizo falta tocar el backend**, aunque el plan lo daba por listo: el listado no devolvía el
> plazo. `days_left` y `deadline_at` viajan ahora con cada reclamación abierta, calculados por
> `ClaimResponseWindow` — un servicio nuevo que `AutoResolveStaleReturnsUseCase` también usa,
> en lugar de su copia privada del plazo. **Si divergieran, la pantalla prometería días que el
> comando no respeta** y el comerciante perdería la venta después de que le dijéramos que tenía
> tiempo; una cuenta atrás en la que no se puede confiar es peor que no tener ninguna.

### Cómo era antes de existir

**Dónde:** `resources/js/pages/tenant/modules/returns/TenantReturnsPage.tsx`

**Endpoints listos:**
- `GET /owner/api/returns/{tenantId}` — devuelve `id, order_number, product_name, amount, reason, description, photos, status, is_open, resolved_by, resolution_notes, created_at`
- `POST /owner/api/returns/{returnId}/resolve` — cuerpo `{ approved: boolean, notes?: string }`

### Qué tiene que hacer

**Listar** las reclamaciones con las abiertas primero (el backend ya las ordena así). Mostrar
producto, importe, motivo, descripción y las fotos que adjuntó el comprador.

**Resolver.** Aprobar o rechazar. Rechazar **exige motivo**.

**Decir lo que está en juego, sin rodeos.** Aprobar revierte la venta: el importe sale del saldo
del comerciante. La pantalla tiene que decirlo antes de que pulse, no después.

**Avisar del reloj.** Cada reclamación abierta debe mostrar **cuántos días quedan** antes de que
se resuelva sola a favor del comprador. Ese es el dato que hace que la pantalla se use: sin él,
el comerciante no sabe que tiene un plazo corriendo.

> Una reclamación resuelta por silencio cuenta como «sin responder» y **baja el nivel de
> reputación a bajo**, lo que duplica la retención de todas sus ventas. Vale la pena decírselo
> en la propia pantalla.

### Mensajes de error del backend

| Situación | Código | Qué mostrar |
| :--- | :--- | :--- |
| Ya resuelta (el reloj se adelantó) | 409 | Refrescar la lista y explicar que el plazo venció |
| Rechazo sin motivo | 422 | Señalar el campo, no un toast genérico |
| Reclamación de otra tienda | 403 | No debería ocurrir; registrar y mostrar error genérico |

---

## Vista 3 — Reputación de la tienda (comerciante) ✅ HECHA (11/09/2026)

> **Lo entregado:** `progress()` expuesto en `GET /tenant/owner/api/wallet-summary` y el
> componente `TenantReputationCard`, pegado a la retención que explica.
>
> `reserve_percent` viaja **con** el nivel en vez de traducirlo la pantalla: mantener la tabla
> de porcentajes en dos sitios significaría que, el día que cambie, el comerciante lea un número
> que no es el que se le aplica — y ese número es dinero suyo retenido.

### Cómo era antes de existir

**Dónde:** ampliar `resources/js/pages/tenant/wallet/TenantOwnerWalletPage.tsx`

**Falta el endpoint.** `TenantReputation::progress()` existe y devuelve
`{ level, deliveries, deliveries_for_next, unanswered_claims, has_debt }`, pero nadie lo expone.
Lo natural es añadirlo a `GET /owner/api/wallet-summary`, que la pantalla ya consume.

### Qué tiene que hacer

**Decir en qué nivel está y qué significa en dinero.** El nivel no es una insignia: decide
cuánto se retiene de cada venta.

| Nivel | Retención |
| :--- | :--- |
| Alto | 5% |
| Medio | 10% |
| Bajo | 20% |

**Mostrar el camino de vuelta.** Cuántas entregas lleva y cuántas le faltan para subir. La
decisión de garantías insiste en esto: *«un nivel que baja sin decir por qué ni cómo se recupera
no corrige a nadie — empuja a abrir otra tienda con otro nombre»*.

**Explicar los frenos.** Si tiene deuda, no llega a alto aunque cumpla las entregas. Si tiene
reclamaciones sin responder, está en bajo hasta que pasen 90 días desde la última.

### Tono

Esta pantalla se lee cuando algo va mal. Explicar sin regañar: el objetivo es que el comerciante
sepa qué hacer, no que se sienta castigado.

---

## Vista 4 — Expediente de reclamación (administrador) ✅ HECHA (12/09/2026)

> **Lo entregado:** `ListAdminClaimsUseCase` (nuevo), tres controladores y sus rutas bajo
> `staff:manage_support`, `AdminClaimDossierPage.tsx` con su servicio, y el PDF por dompdf
> —el mismo camino que las facturas—.
>
> **Hizo falta un listado, y no estaba en el plan.** `BuildClaimDossierUseCase` pide un
> `claimId` y ninguna pantalla central listaba reclamaciones, así que era código inalcanzable
> por partida doble: sin ruta y sin forma de obtener el identificador.
>
> El listado y el expediente son **dos peticiones separadas** a propósito: la lista se consulta
> muchas veces y no lleva ningún dato de identidad; el expediente es un clic deliberado. Esa
> separación es lo que hace que el dato sensible viaje solo cuando alguien va a usarlo.
>
> ### ⚠️ El expediente SÍ incluye la cédula y el RIF de la tienda
>
> **Esto invierte lo que decía este plan**, y no es un descuido. Decisión del 11/09/2026, con el
> proyecto en desarrollo y sin usuarios reales: se entrega todo lo que hay, porque sin identidad
> completa una denuncia no tiene contra quién dirigirse. La pregunta legal sigue abierta y la
> respuesta llegará como una lista de campos a quitar.
>
> Por eso los campos se arman en **un solo sitio** —el bloque `store` de
> `BuildClaimDossierUseCase`, con su nota `pendiente-abogado:`— y ni el controlador, ni la
> pantalla, ni el PDF filtran por su cuenta: dos sitios decidiendo qué sale acaban divergiendo.
> Quitar un campo será borrar una línea.
>
> El cifrado en reposo **no se tocó**: hay un test que lo vigila. Lo que cambió es quién puede
> leerlo a través de una puerta con permiso, no cómo está escrito en disco.

### Cómo era antes de existir

**Dónde:** `resources/js/pages/admin/support/AdminClaimDossierPage.tsx`

**Falta el backend HTTP.** `BuildClaimDossierUseCase` existe sin controlador ni ruta.

### Qué tiene que hacer

Presentar en una sola página lo que el caso de uso ya devuelve: la reclamación con su
cronología, el comprador, la identidad verificada de la tienda, las evidencias de envío y
recepción, y el nivel de reputación.

**La cronología es la mitad del expediente:** cuándo se entregó, cuándo se reclamó y cuándo se
resolvió. Presentarla como una secuencia con fechas, porque es lo que sostiene un caso.

**Poder imprimirlo o exportarlo.** El destino de este documento es acompañar una denuncia, así
que tiene que salir de la pantalla en algo que se pueda entregar.

### Lo que NO debe mostrar

**Ni cédula ni RIF.** Hay un test que lo vigila (`ClaimDossierTest`). Meterlos en un documento
que acaba en una captura de pantalla o en un ticket desharía el cifrado por la puerta de atrás.

---

## Vista 5 — Estado de la reclamación (comprador) ✅ HECHA (11/09/2026)

> **Lo entregado:** `CustomerReturnsPage` explica el estado con palabras, muestra
> `resolution_notes` como respuesta de la tienda —aparte de `admin_notes`, que es otra voz— y
> distingue una resolución por vencimiento de una respuesta real. Sin cédula, el botón abre un
> aviso con enlace al perfil en vez del formulario.
>
> **Cero backend:** el endpoint ya devolvía el modelo entero sin `$hidden`, así que los tres
> campos ya viajaban al navegador; solo faltaba declararlos en el tipo y pintarlos.
>
> De paso se migró a Flowbite, que es donde arrancó
> [`PLAN_MIGRACION_FLOWBITE.md`](PLAN_MIGRACION_FLOWBITE.md).
>
> **Un fallo que solo se vio en el navegador:** la pantalla llegó a decir «La tienda aceptó tu
> reclamación» justo encima de «la tienda no respondió dentro del plazo». Se contradecía, y
> además le atribuía a la tienda una decisión que no tomó. El texto del estado depende ahora de
> `resolved_by`, con su test.

### Cómo era antes de existir

**Dónde:** ampliar `resources/js/pages/customer/CustomerReturnsPage.tsx`

Hoy muestra el estado con una insignia. Le falta lo que el subsistema 5 añadió.

### Qué tiene que hacer

**Explicar el estado con palabras, no solo con color.** Aprobada, rechazada o en revisión.

**Mostrar el motivo del rechazo** (`resolution_notes`). Sin él, el comprador ve su reclamación
denegada y no sabe por qué.

**Distinguir cómo se resolvió** (`resolved_by`): una resolución por vencimiento del plazo no es
lo mismo que una respuesta de la tienda, y el comprador merece saber cuál fue.

**Avisar de que hace falta la cédula.** Abrir una reclamación exige `document_id` en el perfil;
sin él el backend devuelve 422. Es mejor decirlo **antes** de que rellene el formulario y
enlazar a su perfil.

---

## Vista 6 — Pedidos del comprador en el escaparate

**Dónde:** no existe. Es una superficie nueva.

**Es la más grande de la lista y la única que no es "una pantalla más".** El comprador de una
tienda del escaparate no tiene ningún sitio donde ver sus pedidos, así que **no puede confirmar
una entrega ni abrir una reclamación**.

El modelo de datos ya lo admite: la reclamación se indexa por `tenant_order_id`, que una venta
de escaparate sí tiene. Lo que falta es la superficie.

**Antes de construirla hay que decidir cómo se autentica** ese comprador: el escaparate tiene
SSO con el portal central, y los clientes de tienda llevan `central_uuid`. Esa decisión
condiciona toda la vista, así que conviene tomarla antes de dibujar nada.

---

## Orden recomendado

| # | Vista | Por qué en ese puesto |
| :--- | :--- | :--- |
| 1 | ~~**Revisión de KYC**~~ ✅ | Sin ella **nadie cobra**. Todo lo demás puede esperar; esto no |
| 2 | ~~**Reclamaciones de la tienda**~~ ✅ | El reloj ya corre y aprueba solo. Cada día sin esto son ventas revertidas sin que nadie las mirara |
| 3 | ~~**Reputación**~~ ✅ | Barata —el cálculo existe— y es lo que da sentido a la 2 |
| 4 | ~~**Estado de la reclamación (comprador)**~~ ✅ | Cierra el círculo del comprador |
| 5 | ~~**Expediente**~~ ✅ | Solo se usa en el caso raro; puede esperar |
| 6 | **Pedidos del escaparate** | Proyecto aparte, con una decisión de autenticación por delante |

---

## Qué se espera de cada vista, sin excepción

**Funciona en móvil.** La mayoría de estos usuarios son comerciantes con el teléfono en la mano.

**El foco del teclado se ve.** Y `prefers-reduced-motion` se respeta.

**Los errores dicen qué hacer.** El backend devuelve mensajes en castellano ya redactados para
mostrarse: usarlos en vez de inventar otros. Un error nunca se disculpa ni es vago sobre lo que
pasó.

**Los vacíos invitan a actuar.** Una lista sin elementos explica qué la llenará.

**Una acción se llama igual de principio a fin.** Si el botón dice «Aprobar», el aviso posterior
dice «Aprobada».

**Con su test de Vitest**, como exige `reglas.md`. Y lo que se prueba es el comportamiento que
importa: que el botón de resolver no aparezca cuando no toca, que el motivo del rechazo se vea,
que la cédula no acabe en pantalla.
