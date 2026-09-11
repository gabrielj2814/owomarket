# Decisión — Quién responde por la garantía, y cómo se cobra

> **Tipo:** registro de decisión de negocio. No es un plan de implementación.
> **Fecha:** 10/09/2026 · **Revisado el mismo día** (ver «Historial» al final)
> **Estado:** decidido. De aquí salen cinco planes, ninguno escrito todavía.
>
> Esto no se puede volver a derivar leyendo el código: son decisiones de negocio que
> condicionan el diseño técnico de todo lo que venga después. Se anotan para no tener que
> volver a discutirlas.

---

## De dónde sale

Del **hueco 2** de [`PLAN_REEMBOLSO_TRAS_RETIRO.md`](../por_hacer/PLAN_REEMBOLSO_TRAS_RETIRO.md):
un comerciante al que se le reembolsó una venta cuyo dinero ya había retirado arrastra un saldo
negativo que solo se compensa contra ventas futuras. Si deja de vender, nunca se compensa, y la
plataforma no tiene forma de cobrarlo.

El plan dejaba cuatro preguntas de negocio abiertas. Este documento las responde.

---

## La decisión: la tienda responde, la plataforma cubre con tope

**La obligada por la garantía frente al comprador es la tienda.** La plataforma no es
aseguradora: es árbitro, y cobra contra dinero que ya retiene.

**Pero si la tienda no responde, el comprador cobra igual, hasta un tope.** La plataforma pone
la diferencia con un límite por pedido, y la tienda paga el precio en reputación y en deuda.

Es un modelo C escorado hacia A: **la tienda responde por defecto y siempre; la plataforma
cubre el hueco cuando falla, sin que ese hueco pueda crecer sin límite.**

### Los modelos que se consideraron

| Modelo | Veredicto |
| :--- | :--- |
| **A — solo responde la tienda** | Exposición cero para la plataforma, pero el comprador que topa con una tienda que se fue se queda sin nada. Demasiado frágil para un marketplace que quiere que confíen en él |
| **B — la plataforma responde siempre, sin tope** | Exposición ilimitada. Pagas primero y cobras después, o no cobras. Convierte el hueco 2 en el modelo de negocio en vez de eliminarlo |
| **C — la tienda responde, la plataforma cubre con tope** | ✅ **Elegido.** El comprador nunca se queda tirado y la plataforma conoce su peor caso |

### Por qué no el B puro

Lo que sostiene una garantía incondicional es que **esté financiada**. Si vas a pagar los
reembolsos que la tienda no paga, ese coste sale de la comisión, y hace falta saber qué tasa de
reclamaciones esperas para saber si el 8% lo cubre. Sin ese número no se elige un modelo de
negocio: se firma un cheque en blanco.

Conviene además recordar de dónde viene la confianza del comprador. El **escrow** —el dinero no
llega a la tienda hasta que el comprador confirma que recibió el producto— ya cubre el miedo
número uno: *«pago y no me llega nada»*, y lo cubre sin que la plataforma ponga un bolívar
propio. Lo que la cobertura añade encima es el periodo de garantía posterior: un riesgo menos
frecuente, que es exactamente el que tiene sentido acotar con un tope en vez de asumir entero.

### Los dos topes, y por qué se comportan distinto

**Tope por pedido — es un muro.** Limita de verdad la exposición y es una regla publicable: el
comprador sabe de antemano hasta dónde llega la cobertura.

**Techo mensual — es una alarma, no un muro.** Superarlo **no interrumpe los pagos**: dispara
una revisión obligatoria. Si un mes se dispara, algo concreto lo está causando —una tienda, un
fallo del flujo de entrega, un abuso— y lo que toca es mirar la causa.

La razón de que no sea un muro: si el techo se agota el día 20, los compradores del 21 al 30
descubren que la garantía prometida no aplica **por un motivo que no tiene nada que ver con su
compra**. Es indefendible en atención al cliente e impublicable en unos términos. El riesgo ya
lo acota el tope por pedido; el mensual está para obligar a intervenir antes de que el mes se
descontrole.

### El incentivo que hay que vigilar

Que la plataforma pague tiene un efecto secundario que conviene tener presente al diseñar el
subsistema 5: **si el comprador cobra igual, la tienda pierde parte de su razón para responder.**
Ignorar la reclamación deja de dejar a un cliente tirado y pasa a ser cómodo.

Por eso la presión sobre la tienda no puede apoyarse solo en que el cliente se queje. Se apoya
en dos cosas que sí le duelen y que están descritas más abajo: **la reputación —que le toca el
flujo de caja— y la deuda**, que se le cobra de sus propias ventas.

---

## Reputación: tres niveles que mueven dinero

Tres niveles: **alto**, **medio** y **bajo**, **visibles para el comprador** en el perfil de la
tienda y en las fichas de producto.

### Lo que decide el nivel

Una insignia sola es débil: a un comerciante le importa poco una etiqueta. **El nivel decide
cuánto se le retiene y con qué rapidez se le libera**, que es lo que un comerciante siente todos
los días:

| Nivel | Retención | Liberación |
| :--- | :--- | :--- |
| **Alto** | Porcentaje bajo | Rápida |
| **Medio** | Porcentaje medio | Estándar |
| **Bajo** | Porcentaje alto | Lenta |

Esto hace tres cosas a la vez:

- **La reputación deja de ser simbólica** y pasa a ser dinero, sin coste para la plataforma.
- **La exposición se auto-regula:** se retiene más de las tiendas que más probablemente generen
  reclamaciones, y menos de las que nunca dan problemas.
- **Premia sin regalar.** Una tienda con historial limpio cobra antes — el mejor incentivo
  posible, y no cuesta un bolívar.

Es la unión natural de la reputación con el fondo de garantía (subsistema 4).

### Entrada, subida y bajada

**Entra en medio.** Una tienda sin historial no es una tienda buena: es una desconocida, y ahí
está el riesgo real. Ni *alto* —que pondría la máxima exposición donde menos información hay— ni
*bajo*, que asfixiaría el flujo de caja de comerciantes nuevos y honestos, que es justo a quien
se quiere atraer.

**Se sube por historial limpio, automáticamente.** Tras N ventas entregadas sin reclamaciones
sin responder, sube sola. **El progreso tiene que ser visible desde el panel del comerciante:**
cuánto le falta. Es objetivo, no admite discusión, y le da un motivo real para arreglar el
problema en vez de irse y volver con otro nombre.

**Con deuda pendiente, el nivel topa en medio.** No se llega a *alto* debiendo dinero a la
plataforma.

Esto último merece explicarse, porque parecían dos reglas incompatibles y no lo son: **son dos
cosas distintas las que se recuperan.**

| Qué | Cómo se recupera |
| :--- | :--- |
| **El nivel** mide comportamiento | Se arregla comportándose: historial limpio, automático |
| **La deuda** no se perdona por portarse bien | Se paga — y **ya se paga sola**: el saldo negativo se absorbe contra ventas futuras |

La deuda no exige reunir una suma y entregarla: vendiendo, se paga. Ese mecanismo **ya existe y
está verificado con tests** (commit `ae2f83e`,
[`CreditNoteBalanceTest.php`](../../tests/Feature/Monetization/CreditNoteBalanceTest.php)).

Así las dos palancas se mueven a la vez y ninguna atrapa a nadie: **portarse bien saca del suelo,
saldar la deuda da acceso al techo.** Y como la deuda se cobra de las ventas, la única forma de
pagarla es seguir vendiendo — que es exactamente donde debe estar el incentivo. Exigir las dos
como condición doble habría hecho que una tienda muy endeudada no subiera nunca, y una tienda que
no puede recuperarse no mejora: se va y se registra otra vez.

### Que se calcule de una sola señal

**Reclamaciones sin responder**, y nada más, al menos al empezar. Habrá presión para meter
entregas tarde, reseñas y cancelaciones en el mismo índice. Un índice compuesto es imposible de
explicarle a un comerciante enfadado, y **todo lo que no se puede explicar se acaba no
aplicando**.

### El coste de que sea visible

Que el comprador vea el nivel es lo que le da fuerza disuasoria, y es una decisión tomada. El
efecto colateral hay que aceptarlo de frente: **una tienda en nivel bajo vende muy poco.** Por eso
el camino de vuelta no es un detalle — es lo que evita que *bajo* sea una sentencia de muerte que
empuje a la tienda a desaparecer con la deuda puesta.

---

## Las cinco capas de escalado

Cuando la tienda no responde a una reclamación, en orden de coste creciente:

1. **Reloj con resolución por defecto.** La tienda tiene N días para responder. Silencio = se
   resuelve a favor del cliente, automáticamente. **Sin esto, ignorar es la estrategia
   ganadora**, y todo lo demás sobra.
2. **Cobro contra el fondo.** La resolución carga contra la reserva retenida. Es la capa 1 con
   dientes, y es la que paga la mayoría de los casos.
3. **Cobertura de la plataforma, con tope.** Si el fondo no alcanza, la plataforma pone la
   diferencia hasta el tope por pedido. El comprador cobra; la tienda queda con **deuda** —que se
   absorberá de sus ventas futuras— y **baja de nivel**.
4. **Sanciones de plataforma.** Registro público de reclamaciones sin responder, nivel bajo
   visible, y suspensión del escaparate. Efectivas solo mientras la tienda quiera seguir
   vendiendo.
5. **Denuncia asistida.** El comprador puede denunciar y la plataforma **le entrega un
   expediente**: pruebas, cronología e identidad verificada de la tienda.

### Sobre la capa 5

**Casi nunca recupera el dinero.** Los importes son pequeños y el proceso lento. Su valor está en
disuadir —una tienda que sabe que ignorar genera un expediente con su identidad dentro se comporta
distinto— y en cerrar el proceso con dignidad para el comprador.

**Es entregar un expediente, no acompañar un proceso.** Asesorar legalmente al comprador
convertiría a la plataforma en parte del conflicto. La línea es deliberada.

**Y es un indicador:** si las denuncias son frecuentes, la reserva está mal dimensionada.

### ⚠️ Pendiente con abogado

Entregarle a un comprador los datos de identidad de un comerciante **no es automático**. Hay que
determinar qué se le puede dar al comprador y qué solo a la autoridad.

Y algo más de fondo: **no está verificado qué dice la ley venezolana de protección al consumidor
sobre la responsabilidad solidaria de un marketplace**. En bastantes jurisdicciones la plataforma
tiene obligaciones frente al comprador independientemente de sus términos y condiciones, y más si
cobra el dinero — que es el caso aquí, porque OwoMarket cobra todas las ventas. **Ofrecer
cobertura propia con tope acerca todavía más a esa figura.**

**Lo decidido en este documento sirve para construir, no para blindarse.** Consultar antes de
publicar términos y condiciones.

---

## Por qué el KYC deja de ser opcional

El KYC (cédula, RIF, teléfono, dirección, de cliente y de tienda) parecía justificarse con un
«por si hay que hacer algo legal». Aquí es el sustrato sin el cual las capas 4 y 5 son teatro:

- La capa 5 **no tiene contra quién dirigirse** sin la identidad verificada del responsable.
- Y la capa 4 se evapora: **sin identidad, la tienda que quema su reputación se registra otra vez
  con otro nombre.** El valor principal del KYC aquí no es la denuncia — es que **el nivel bajo y
  la deuda sobrevivan al cierre de la tienda**. Sin eso, todo el sistema de reputación se resetea
  con un registro nuevo.

Dos avisos de diseño:

- **Pedir cédula para comprar mata la conversión.** Niveles: datos mínimos para comprar, KYC
  completo para vender o para abrir una reclamación. El coste se paga cuando hay algo en juego.
- **Guardar cédulas y RIF es un pasivo, no un activo.** Cifrado en reposo, control de acceso y
  política de retención desde el primer commit.

---

## Los cinco subsistemas

Lo hablado son cinco cosas independientes. No caben en una especificación y mezclarlas garantiza
que ninguna se termine. Cada una necesita su propio ciclo diseño → plan → implementación.

| # | Subsistema | Qué es | Depende de | Estado |
| :--- | :--- | :--- | :--- | :--- |
| 1 | **KYC** | Identidad verificada de cliente y tienda, por niveles | — | 🟡 Comerciante hecho (11/09/2026) · cliente espera al 5 |
| 2 | **Garantía por producto** | Atributo de catálogo: si tiene garantía y de cuánto tiempo | — | ✅ **Hecho** (10/09/2026) |
| 3 | **Entrega verificada** | Evidencia de envío, confirmación del comprador, liberación del dinero | — | ✅ **Hecho** (11/09/2026) |
| 4 | **Fondo de garantía y reputación** | Reserva retenida por venta, con porcentaje y velocidad de liberación según nivel | 2 | 🟡 Fondo hecho (11/09/2026) · reputación espera al 5 |
| 5 | **Reclamaciones (RMA)** | Disputa, reloj, resolución, cobertura con tope, escalado | 2, 3, 4 | ⬜ Por hacer |

### Subsistema 1 — KYC del comerciante

**Se exige en un solo sitio: para retirar dinero.** No en el alta. Pedirlo antes pondría toda la
fricción delante de un comerciante que todavía no ha visto ningún valor, y una plataforma que
tiene que llenarse de tiendas no se lo puede permitir. En el retiro ya ha vendido, y el
incentivo para rellenar el formulario es su propio dinero.

**El KYC del cliente no se construyó**, y es deliberado: la decisión ya dice «datos mínimos para
comprar, KYC completo para abrir una reclamación» — y las reclamaciones son el subsistema 5.
`central_customers.document_id` ya cubre la compra.

#### Por qué una tabla propia y no las columnas que ya existían

`users` traía `cedula`, `nacionalidad` y `cedula_doc` desde la migración inicial, **muertas** —
ninguna línea de la aplicación las tocaba. Revivirlas parecía lo natural y se descartó por un
motivo concreto: **hay al menos dos modelos Eloquent leyendo esa misma tabla** (el de `Admin` y
el de `User`), y el cast `encrypted` se declara por modelo. Uno escribiría cifrado y el otro
texto plano en la misma columna, y nadie se enteraría hasta intentar descifrar un número que
nunca se cifró. En una columna con documentos de identidad eso no es un bug, es una fuga.

`tenant_kyc_profiles` tiene **un único modelo**, y eso hace ese fallo imposible.

#### Cifrado y hash: los dos hacen falta

Cédula y RIF se guardan **cifrados** (`encrypted`), porque son los identificadores con los que
se suplanta a una persona. Pero la decisión exige poder **bloquear una identidad para que no
abra otra tienda**, y **un dato cifrado no se puede buscar**: el IV es aleatorio, así que el
mismo número cifrado dos veces da valores distintos.

De ahí el par: **la columna cifrada para leer, el hash para buscar**. Sin el hash, la regla de
«no puede reabrir con otro nombre» sería inaplicable — y esa regla es el valor principal que la
decisión le atribuye al KYC.

El hash normaliza a **solo dígitos**, porque si no el bloqueo se esquiva escribiendo el número
con guiones, que es justo lo que haría quien intenta reabrir tras una sanción.

`phone` y `address` quedan en claro: son datos de contacto que la pantalla necesita mostrar, y
la aplicación ya los guarda así en otros sitios. Cifrarlos solo aquí sería teatro incoherente.

#### Lo que se decidió no guardar

**La foto del documento es opcional.** Exigirla convertiría a la plataforma en custodio de
imágenes de identidad desde el primer día — con lo que eso implica de retención y de
responsabilidad si hay una fuga. Los datos bastan para una denuncia y para detectar reaperturas;
si no se guarda, no se puede filtrar.

Cubierto por `tests/Feature/Tenant/TenantKycTest.php` (cifrado, hash y búsqueda por identidad) y
`TenantKycPayoutGateTest.php`. El que vigila es «sin expediente de identidad no se puede
retirar»: no comprueba un mensaje, comprueba que el dinero no sale.

### Subsistema 2 — lo que quedó construido

Un solo campo `warranty_days` (nullable) en `products` y en `central_products`: `null` es sin
garantía, `N` son N días. Dos columnas para el mismo hecho podían contradecirse; la casilla
«tiene garantía» vive solo en el formulario, donde la redundancia ayuda al comprador y no puede
llegar a la tabla.

Se propaga por `SyncProductToCentralMarketplaceUseCase` —sin eso, el subsistema 4 no sabría
cuánto retener de una venta del marketplace, que es donde la plataforma cobra— y se muestra en
la ficha de las dos tiendas, central y escaparate.

La validación rechaza `0` a propósito: cero días de garantía es no tenerla, ya se dice con
`null`, y dos formas de decir lo mismo divergen en cuanto alguien compare con `> 0` en un sitio
y con `!== null` en otro.

Cubierto por `tests/Feature/Product/CentralCatalogSyncTest.php` (propagación),
`WarrantyDaysValidationTest.php` (la regla) y
`tests/Frontend/Components/ProductWarrantyField.test.tsx` (el campo).

### Subsistema 3, fase A — quién libera el dinero

**El agujero era doble.** Había dos caminos a `delivered` —`DeliverOrderUseCase` y
`MarkShipmentAsDeliveredUseCase`— y los dos están expuestos en `routes/tenantApi.php`. Es
decir: el comerciante declaraba su propia entrega y con ello hacía retirable su propio importe.
Tocar solo uno habría dejado el agujero abierto por el otro lado.

Ahora declarar la entrega **solo arranca un reloj** (`DeclareOrderDeliveredUseCase`). Liberar
tiene dos causas legítimas y ninguna es el comerciante:

| Causa | Quién | `released_by` |
| :--- | :--- | :--- |
| Confirmación | El comprador, desde `POST /deliveries/{orderId}/confirm` | `customer` |
| Vencimiento | Nadie: 7 días de silencio, configurables | `timeout` |

La liberación por vencimiento no es un extra: **sin ella el subsistema sería una trampa**, porque
un comprador que recibe su paquete y no vuelve a entrar dejaría el dinero de la tienda congelado
para siempre.

El expediente vive en la tabla central `order_delivery_confirmations`, uno por pedido **de
tienda** —un carrito repartido entre tres tiendas se confirma tres veces— y no dentro de
`shipments`, que es otra cosa: la logística del comerciante, con varios envíos posibles por
pedido.

`released_by` distingue las dos causas a propósito: es lo único que dirá, cuando haya datos, qué
porcentaje de compradores confirma de verdad, y por tanto si siete días es el plazo correcto.

**Sin pantalla de administrador**, deliberadamente: un humano aprobando cada liberación es el
cuello de botella de la plataforma. El administrador entra solo cuando hay conflicto, y eso es
el subsistema 5.

Cubierto por `tests/Feature/Monetization/DeliveryConfirmationTest.php`. El que vigila es
«declarar la entrega NO libera el dinero».

### Subsistema 4 — el fondo de garantía

**No es un mecanismo nuevo: es hacer parcial la retención que ya existía.**
`TenantAvailableBalance::netEarnings()` ya sumaba solo lo que tiene `released_at` vencido, pero
era todo o nada. Con dos columnas en `platform_commissions` —`reserve_amount` y
`reserve_until`— una venta libera el 90% y guarda el 10% sesenta días más.

Sin tabla de reservas a propósito: **el saldo de una tienda tiene que salir de un solo sitio.**
Una segunda tabla que hubiera que restar aparte es exactamente como nacen las dos consultas que
responden a la misma pregunta y divergen — y este proyecto ya pagó un plan entero por eso.

La reserva se estampa en `ReleaseOrderCommissionUseCase`, que ya corría en el instante correcto
y ya escribía en esa fila. Con una venta de $100 al 8%:

| | Antes | Con el fondo |
| :--- | :--- | :--- |
| Al liberarse | $92 retirables | $82,80 retirables, $9,20 retenidos 60 días |

**10% y 60 días, configurables** (`central_guarantee_reserve_percent` y
`central_guarantee_reserve_days`). Conservador a propósito, por la asimetría ya anotada: bajar
una retención después es un regalo, subirla es una discusión con cada tienda.

Dos detalles que no son cosméticos:

- **Una nota de crédito no genera reserva.** Lleva la parte del comerciante en negativo;
  retener un porcentaje de una deuda no significa nada y además restaría al revés, aumentando
  el saldo de quien debe dinero.
- **El fondo se muestra aparte en la wallet** (`retained_reserve_ves`), separado de los otros
  dos motivos de retención. Al comerciante no puede bajarle el saldo sin que pueda ver por qué.

Cubierto por `tests/Feature/Monetization/GuaranteeReserveTest.php`. El que vigila es «el saldo
retirable baja en el importe del fondo»: sin él, la reserva sería contabilidad decorativa.

#### Lo que se dejó fuera, y por qué

**Los tres niveles de reputación.** Se calculan de «reclamaciones sin responder», y las
reclamaciones son el subsistema 5: hoy la señal no existe. Un `reputation_level` que nunca
puede cambiar de valor es un `config()` disfrazado de columna. La fórmula queda lista para que
el porcentaje sea variable; el nivel entra cuando haya con qué moverlo.

**El plazo por producto.** `warranty_days` ya existe, pero la comisión se crea sabiendo solo
totales, no qué productos lleva el pedido. Usarlo exigiría pasarlo desde el despacho y desde el
checkout del escaparate — y en el escaparate los artículos ni siquiera están en la base
central, así que el comportamiento saldría distinto según el canal. Plazo plano, una sola regla
para todos, hasta que haya un motivo medido para afinar.

### Subsistema 3, fase B — las evidencias

La tienda adjunta fotos o vídeo al enviar (`POST order/{id}/shipment-evidence`) y el comprador
puede adjuntar las suyas al confirmar. **Ninguna de las dos libera nada**: siguen liberando la
confirmación o el plazo. Lo que cambian es la discusión — sin evidencia, «lo envié» contra «no
me llegó» es la palabra de uno contra la del otro y la plataforma no tiene con qué decidir.

Viven en el expediente central para que las vean las tres partes: en la base de la tienda ni
el comprador ni el administrador podrían consultarlas, que es justo lo que las haría inútiles.
Por el mismo motivo los dos lados ven **el mismo** expediente, con un solo caso de uso de
proyección: una prueba solo zanja una discusión si ambas partes la tienen delante.

La evidencia del comprador es **opcional** a propósito. Exigirle una foto para poder confirmar
convertiría el trámite en un obstáculo, y quien no confirma libera por plazo igualmente: lo
único que se lograría es que nadie confirmara nunca.

Reutiliza `UploadSupportAttachmentService` —que ya validaba imágenes y vídeo con su límite de
50MB— con un parámetro de carpeta añadido. Un segundo subidor solo habría creado dos sitios
donde cambiar el mismo límite.

Cubierto por `tests/Feature/Monetization/DeliveryEvidenceTest.php` y
`tests/Frontend/Components/DeliveryConfirmationPanel.test.tsx`.

### Orden recomendado

**El 3 primero.** Arregla un agujero que existe hoy y no depende de nada. El 2 es casi gratis y
puede ir en paralelo. **El 4 antes que el 5, siempre**: sin fondo, las reclamaciones no tienen con
qué pagarse. El 1 antes que el 5, porque es lo que hace que su escalado y la reputación
signifiquen algo.

**Y conviene tenerlo claro: el hueco 2 no lo cierra el 3, lo cierra el 4.**

### El agujero que justifica empezar por el 3

`MarkShipmentAsDeliveredUseCase` se expone en [`routes/tenantApi.php:87`](../../routes/tenantApi.php).
Es decir: **el comerciante declara la entrega desde su propia API, y esa declaración arranca el
reloj de liberación de su propio dinero.** El comprador no interviene en ningún momento.

Diseño previsto: la tienda registra el envío con evidencia (foto/vídeo), visible para el comprador
y para el administrador; el comprador confirma la recepción con evidencia; el dinero se libera
entonces.

**Aviso operativo:** *«la liberación la decide un administrador»* funciona con 10 pedidos al día;
con 500 es un empleado a tiempo completo y el cuello de botella de la plataforma. La liberación
debe ser **automática por defecto** —al confirmar el comprador, o al pasar N días sin que nadie
diga nada— y el administrador intervenir **solo en las disputas**. El humano donde hay conflicto,
no en el camino feliz.

### El 5 no se empieza de cero

Ya existe [`planes/futuros/PLAN_RESOLUCION_DEVOLUCIONES.md`](../futuros/PLAN_RESOLUCION_DEVOLUCIONES.md),
y ya existe la tabla `customer_return_requests` con un flujo por el que **el cliente crea
devoluciones que nadie resuelve**. El subsistema 5 tiene que **absorber ese plan**, no duplicarlo.

---

## Cómo se arranca sin histórico

OwoMarket no ha operado todavía: **no hay ni una sola devolución real de la que sacar una tasa
de reclamaciones.** Y sin esa tasa no se puede calcular el porcentaje de retención.

La salida no es adivinar el número: es **elegir una política que no dependa de él**.

### La postura: conservador, y que suban solos

Retención inicial prudente para todas las tiendas, tope por pedido bajo, y confianza en que el
sistema de niveles saque rápido a las que se portan bien.

El motivo es una asimetría que conviene tener muy presente: **es muchísimo más fácil bajar una
retención que subirla.** Bajarla es un regalo que el comerciante celebra. Subirla, al descubrir a
los seis meses que el número se quedó corto, se vive como una traición — le estás quitando flujo
de caja a gente que ya organizó su negocio contando con él, y es una discusión con cada tienda.

Y el coste de pasarse de prudente es aquí menor de lo habitual: **una tienda que se porta bien
sale sola** subiendo de nivel. La prudencia inicial no la castiga mucho tiempo, le pone un plazo.

### Las cuatro palancas que no necesitan histórico

**1. La reputación ya es el recolector de datos.** No hace falta un número de plataforma, porque
la retención se individualiza por tienda. Todas entran en *medio* y **el historial de cada una es
el dato que baja su propia retención**. No se espera a tener estadísticas para actuar: se actúa
mientras se generan. El porcentaje global es solo el punto de partida de una curva que cada tienda
recorre sola.

**2. El tope por pedido protege desde el día uno, y no requiere estadística.** Un porcentaje mal
calculado hace daño lentamente; un tope evita el caso catastrófico de inmediato. Arrancar con tope
bajo —cubre bien los pedidos pequeños, que son la mayoría— y subirlo cuando haya datos.

**3. El plazo de garantía ya es una señal de riesgo que existe hoy.** No se conoce la tasa de
reclamaciones, pero **sí qué productos prometen 12 meses y cuáles ninguno**. Garantía larga =
exposición más larga = más retención. No es conjetura: es una relación directa que sale del
subsistema 2, y da variación de riesgo sin un solo dato histórico.

**4. Dimensionar por política, no por probabilidad.** En vez de «¿qué porcentaje de ventas se
reclama?» —que no se sabe—, «¿cuántas reclamaciones simultáneas quiero poder cubrir de una tienda
sin poner dinero propio?». Eso es una decisión, no una estimación, y se responde hoy.

### Configurable no basta

- **Revisión con fecha**, no «cuando nos acordemos»: a los 90 días o a las primeras N ventas
  entregadas, alguien mira los números de verdad.
- **El cambio de porcentaje NO es retroactivo.** Lo ya retenido se rige por la regla vigente
  cuando se vendió. Si no, cada ajuste mueve hacia atrás el saldo de todas las tiendas y nadie
  entiende su wallet. Es la misma idea que ya rige la tasa de cambio, que se congela por venta y
  no se revaloriza al consultar.

### ⚠️ Instrumentar antes de necesitarlo

La tabla `customer_return_requests` (migración `2026_08_19_000010`) registra motivo, estado,
tienda, producto y fechas. **Le faltan tres campos sin los cuales la revisión a 90 días no podrá
responder nada:**

| Falta | Sin eso no se puede saber |
| :--- | :--- |
| **Importe reclamado** | Cuánto dinero mueven las reclamaciones. Un fondo se dimensiona en dinero, no en unidades |
| **Fecha de entrega del pedido** | Cuántos días después de recibir llega una reclamación — que es justo lo que dice **cuánto tiempo** hay que retener |
| **`resolved_at` propio** | Cuánto tarda una tienda en responder. Con solo `updated_at` la medida es frágil, y esa es **la señal de la que depende toda la reputación** |

Son baratos y hay que añadirlos con el subsistema 5. **Si no se instrumenta ahora, dentro de tres
meses se estará igual de ciego que hoy, pero con tres meses perdidos.**

---

## Lo que sigue abierto del hueco 2

De las cuatro preguntas originales:

| # | Pregunta | Estado |
| :--- | :--- | :--- |
| 1 | Quién asume la pérdida | ✅ **La tienda primero** (fondo), **la plataforma el resto hasta un tope por pedido**, y la tienda queda con deuda que se absorbe de sus ventas |
| 2 | Si un saldo negativo bloquea algo | ✅ **Sí.** Topa el nivel de reputación en medio, lo que encarece su retención y ralentiza sus cobros. Más registro público, suspensión y bloqueo de identidad para reabrir |
| 3 | Tope de tiempo para reclamar | ✅ **Es el plazo de garantía del producto**, que pasa a ser atributo de catálogo (subsistema 2) |
| 4 | Ventana de garantía más larga por categoría | ✅ **Se sustituye por el fondo.** En vez de retener el 100% más tiempo, se retiene un porcentaje —variable según nivel— durante el plazo de garantía |

### Lo que queda por decidir, y es el subsistema 4

**Los números.** Qué porcentaje se retiene en cada nivel, durante cuánto tiempo, cuál es el tope
por pedido, dónde está el techo mensual de alarma, y qué le supone todo eso al flujo de caja de
una tienda típica.

No salen de un cálculo —no hay histórico del que sacarlos— sino de la política descrita en «Cómo
se arranca sin histórico»: conservadores, configurables, no retroactivos, y con una revisión con
fecha puesta.

### Lo que sigue haciendo falta igual

**Que el negativo se pueda ver.** `requestable()` y `settleable()` terminan en `max(0.0, …)`, así
que un comerciante que debe 4.600 Bs y uno con saldo cero se ven idénticos. Bloquea el retiro, que
es lo importante, pero nadie puede reclamar —ni topar el nivel de— lo que no puede ver. Con la
reputación atada a la deuda, esto pasa de conveniente a requisito.

---

## Historial

- **10/09/2026 — decisión inicial.** Modelo A puro: solo responde la tienda, exposición de la
  plataforma igual a cero, con una «cortesía acotada» discrecional y sin prometer nada.
- **10/09/2026 — revisión.** Se formaliza la cobertura: la plataforma paga cuando la tienda no lo
  hace, con tope por pedido y techo mensual de alarma, y entra el sistema de reputación de tres
  niveles ligado a la retención. El motivo del cambio: en modelo A puro, el comprador que topaba
  con una tienda desaparecida se quedaba sin nada, y eso es demasiado frágil para un marketplace
  que aún tiene que ganarse la confianza. La contrapartida —que la tienda pierde parte de su
  incentivo a responder— se compensa con reputación y deuda, no con la queja del cliente.
- **10/09/2026 — arranque sin histórico.** Se añade cómo fijar los porcentajes en una plataforma
  que no ha operado: postura conservadora apoyada en que subir una retención después es mucho más
  costoso que bajarla, las cuatro palancas que funcionan sin datos, la regla de no retroactividad,
  la revisión con fecha, y los tres campos que hay que añadir a `customer_return_requests` para
  que esa revisión pueda responder algo.
