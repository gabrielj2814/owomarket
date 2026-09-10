# Decisión — Quién responde por la garantía, y cómo se cobra

> **Tipo:** registro de decisión de negocio. No es un plan de implementación.
> **Fecha:** 10/09/2026
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

El plan dejaba cuatro preguntas de negocio abiertas. Este documento responde dos, y cambia la
naturaleza de las otras dos.

---

## La decisión: modelo A — la tienda responde, la plataforma ejecuta

**La obligada por la garantía frente al comprador es la tienda.** La plataforma no es
aseguradora: es árbitro, y cobra contra dinero que ya retiene.

Se descartaron:

| Modelo | Por qué no |
| :--- | :--- |
| **B — la plataforma responde y luego reclama** | Exposición ilimitada. Pagas primero y cobras después, o no cobras. Convierte el hueco 2 en el modelo de negocio en vez de eliminarlo |
| **C — híbrido con tope fijo** | Acota la exposición, pero el que responde por defecto sigue siendo la plataforma, y esa expectativa del comprador ya no se puede deshacer |

### El argumento que decidió

El modelo B se elige normalmente para comprar confianza en un marketplace nuevo. Pero el
**escrow** —el dinero del comprador no llega a la tienda hasta que él confirma que recibió el
producto— ya cubre el miedo número uno: *«pago y no me llega nada»*, y lo cubre sin que la
plataforma ponga un bolívar propio.

Lo único que B añade encima es la cobertura del periodo de garantía posterior: un riesgo mucho
menos frecuente y mucho menos aterrador. **Sería pagar exposición ilimitada por el tramo de
confianza más barato.**

### La consecuencia que importa

Con el modelo A, **la exposición máxima de la plataforma es el tamaño del fondo retenido**.
Nunca paga más de lo que tiene en la mano.

**El hueco 2 deja de ser un problema que gestionar y pasa a ser una consecuencia de dimensionar
bien una reserva.** No se resuelve persiguiendo morosos: se resuelve no habiendo soltado el
dinero todavía.

### Cortesía acotada

La plataforma se reserva la potestad de asumir reclamaciones pequeñas —por debajo de un importe
a fijar— cuando la tienda ya no tiene fondo. Es reputación a un coste conocido y presupuestable.

**El matiz no es cosmético:** en el modelo A el que responde por defecto es el comerciante y la
plataforma decide caso a caso cuándo poner dinero. Si se invierte, ya no hay vuelta atrás sin
romperle la expectativa al comprador.

---

## Las cuatro capas de escalado

Cuando la tienda no responde a una reclamación, en orden de coste creciente:

1. **Reloj con resolución por defecto.** La tienda tiene N días para responder. Silencio = se
   resuelve a favor del cliente, automáticamente. **Sin esto, ignorar es la estrategia
   ganadora**, y todo lo demás sobra.
2. **Cobro contra el fondo.** La resolución carga contra la reserva retenida, no contra la
   voluntad de la tienda. Es la capa 1 con dientes, y es la que de verdad paga.
3. **Sanciones de la plataforma.** Registro público de reclamaciones sin responder en el perfil
   de la tienda, y suspensión del escaparate. Efectivas solo mientras la tienda quiera seguir
   vendiendo.
4. **Denuncia asistida.** El comprador puede denunciar y la plataforma **le entrega un
   expediente**: pruebas, cronología e identidad verificada de la tienda.

### Sobre la capa 4

**Casi nunca recupera el dinero.** Los importes son pequeños y el proceso lento. Su valor está
en disuadir —una tienda que sabe que ignorar genera un expediente con su identidad dentro se
comporta distinto— y en cerrar el proceso con dignidad para el comprador.

**Es entregar un expediente, no acompañar un proceso.** Asesorar legalmente al comprador
convertiría a la plataforma en parte del conflicto. La línea es deliberada.

**Y es un indicador:** si las denuncias son frecuentes, la reserva está mal dimensionada. Con el
fondo bien calibrado esta capa debería activarse muy pocas veces.

### ⚠️ Pendiente con abogado

Entregarle a un comprador los datos de identidad de un comerciante **no es automático**. Hay que
determinar qué se le puede dar al comprador y qué solo a la autoridad.

Y algo más de fondo: **no está verificado qué dice la ley venezolana de protección al consumidor
sobre la responsabilidad solidaria de un marketplace**. En bastantes jurisdicciones la plataforma
tiene obligaciones frente al comprador independientemente de sus términos y condiciones, y más
si cobra el dinero — que es el caso aquí, porque OwoMarket cobra todas las ventas.

**Lo decidido en este documento sirve para construir, no para blindarse.** Consultar antes de
publicar términos y condiciones.

---

## Por qué el KYC deja de ser opcional

El KYC (cédula, RIF, teléfono, dirección, de cliente y de tienda) parecía justificarse con un
«por si hay que hacer algo legal». En el modelo A es el sustrato sin el cual las capas 3 y 4 son
teatro:

- La capa 4 **no tiene contra quién dirigirse** sin la identidad verificada del responsable.
- Y la capa 3 se evapora: **sin identidad, la tienda que quema su reputación se registra otra vez
  con otro nombre.** El valor principal del KYC aquí no es la denuncia — es que la sanción
  sobreviva al cierre de la tienda.

Dos avisos de diseño:

- **Pedir cédula para comprar mata la conversión.** Niveles: datos mínimos para comprar, KYC
  completo para vender o para abrir una reclamación. El coste se paga cuando hay algo en juego.
- **Guardar cédulas y RIF es un pasivo, no un activo.** Cifrado en reposo, control de acceso y
  política de retención desde el primer commit.

---

## Los cinco subsistemas

Lo hablado son cinco cosas independientes. No caben en una especificación y mezclarlas garantiza
que ninguna se termine. Cada una necesita su propio ciclo diseño → plan → implementación.

| # | Subsistema | Qué es | Depende de |
| :--- | :--- | :--- | :--- |
| 1 | **KYC** | Identidad verificada de cliente y tienda, por niveles | — |
| 2 | **Garantía por producto** | Atributo de catálogo: si tiene garantía y de cuánto tiempo | — |
| 3 | **Entrega verificada** | Evidencia de envío, confirmación del comprador, liberación del dinero | — |
| 4 | **Fondo de garantía** | Reserva retenida por venta, dimensionada por el plazo de garantía | 2 |
| 5 | **Reclamaciones (RMA)** | Disputa, reloj, resolución, escalado | 2, 3, 4 |

### Orden recomendado

**El 3 primero.** Arregla un agujero que existe hoy y no depende de nada. El 2 es casi gratis y
puede ir en paralelo. **El 4 antes que el 5, siempre**: sin fondo, las reclamaciones no tienen
con qué pagarse. El 1 antes que el 5, porque es lo que da sentido a su escalado.

**Y conviene tenerlo claro: el hueco 2 no lo cierra el 3, lo cierra el 4.**

### El agujero que justifica empezar por el 3

`MarkShipmentAsDeliveredUseCase` se expone en [`routes/tenantApi.php:87`](../../routes/tenantApi.php).
Es decir: **el comerciante declara la entrega desde su propia API, y esa declaración arranca el
reloj de liberación de su propio dinero.** El comprador no interviene en ningún momento.

Diseño previsto: la tienda registra el envío con evidencia (foto/vídeo), visible para el
comprador y para el administrador; el comprador confirma la recepción con evidencia; el dinero
se libera entonces.

**Aviso operativo:** *«la liberación la decide un administrador»* funciona con 10 pedidos al día;
con 500 es un empleado a tiempo completo y el cuello de botella de la plataforma. La liberación
debe ser **automática por defecto** —al confirmar el comprador, o al pasar N días sin que nadie
diga nada— y el administrador intervenir **solo en las disputas**. El humano donde hay conflicto,
no en el camino feliz.

### El 5 no se empieza de cero

Ya existe [`planes/futuros/PLAN_RESOLUCION_DEVOLUCIONES.md`](../futuros/PLAN_RESOLUCION_DEVOLUCIONES.md),
y ya existe la tabla `customer_return_requests` con un flujo por el que **el cliente crea
devoluciones que nadie resuelve**. El subsistema 5 tiene que **absorber ese plan**, no duplicarlo.

Detalle que conviene notar: aquel plan ya decidía que **resuelve el comerciante y el
administrador solo arbitra en modo lectura**. Es exactamente el modelo A, asumido antes de
tener nombre.

---

## Lo que sigue abierto del hueco 2

De las cuatro preguntas originales:

| # | Pregunta | Estado |
| :--- | :--- | :--- |
| 1 | Quién asume la pérdida | ✅ **Respondida.** La tienda. La plataforma solo cobra contra el fondo, más cortesía acotada |
| 2 | Si un saldo negativo bloquea algo | ✅ **Respondida.** Sí: capa 3 —registro público y suspensión— y bloqueo de la identidad para reabrir |
| 3 | Tope de tiempo para reclamar | 🟡 **Se transforma.** Deja de ser arbitrario: es el plazo de garantía del producto (subsistema 2) |
| 4 | Ventana de garantía más larga por categoría | 🟡 **Se transforma.** La sustituye el fondo: en vez de retener el 100% más tiempo, se retiene un porcentaje durante el plazo de garantía |

Queda por decidir, y es el contenido del subsistema 4: **qué porcentaje se retiene y con qué
regla**, y qué le supone eso al flujo de caja de una tienda típica.

**Sigue abierto también** el saldo negativo invisible: `requestable()` y `settleable()` terminan
en `max(0.0, …)`, así que un comerciante que debe 4.600 Bs y uno con saldo cero se ven idénticos.
Bloquea el retiro, que es lo importante, pero nadie puede reclamar lo que no puede ver.
