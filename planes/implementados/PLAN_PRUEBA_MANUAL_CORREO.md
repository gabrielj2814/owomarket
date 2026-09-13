# Plan de Trabajo: Ejecución de Guion de Prueba Manual de Correos (OwOMarket)

**Estado:** En espera de aprobación del usuario  
**Fecha:** 13/09/2026  
**Guion de referencia:** [`planes/anotaciones/PRUEBA_MANUAL_CORREO.md`](../anotaciones/PRUEBA_MANUAL_CORREO.md)  
**Registro de flujos:** [`planes/anotaciones/FLUJOS_DE_CORREO.md`](../anotaciones/FLUJOS_DE_CORREO.md)  

---

## 1. Contexto y Objetivos

OwOMarket cuenta con once flujos de correo saliente (3 existentes y 8 del módulo de notificaciones). La aplicación corre en Docker (`owomarket_app`, `owomarket_web`, `owomarket_db`, `owomarket_redis`, `owomarket_horizon`) con Mailtrap configurado en `sandbox.smtp.mailtrap.io:2525`.

El objetivo es ejecutar rigurosamente el guion manual descrito en `planes/anotaciones/PRUEBA_MANUAL_CORREO.md` paso a paso, provocar cada uno de los envíos sin modificar código y verificar lo que llega a la bandeja de Mailtrap, llenando la plantilla final de resultados.

---

## 2. Reglas Estrictas de Ejecución

1. **Cero modificaciones de código:** Si algún paso o correo falla, no se toca el código fuente. Se anota el resultado exacto, los detalles de error en logs y se continúa con el siguiente paso.
2. **Registro factual:** Anotar lo que realmente se ve y se recibe en Mailtrap (asuntos, remitentes, cuerpos, enlaces, adjuntos), no suposiciones.
3. **Validación de contenido:** Un correo recibido solo es válido si su contenido interno cumple las condiciones específicas descritas en el guion (textos de estado, ausencia de datos sensibles como cédula/RIF en C4, textos precisos en expiración de plazos en C8, etc.).

---

## 3. Estado Inicial Verificado

- **Contenedores Docker:** Activos y saludables (`owomarket_app`, `owomarket_db`, `owomarket_horizon`, `owomarket_redis`, `owomarket_web`).
- **Paso 0 (¿Sale algo?):** **OK**  
  Comando ejecutado dentro del contenedor:
  `Illuminate\Support\Facades\Mail::raw('prueba', ...)`  
  Resultado: `enviado` con código de salida 0. El servidor SMTP de Mailtrap es accesible y no hay bloqueo de puertos.
- **Cuentas de prueba:** Verificada la existencia en base de datos de:
  - Superadministrador: `root@owomarket.local`
  - Comerciante / Dueño de tienda `tecs`: `tecs.owner@owomarket.local`
  - Comprador: `cliente@owomarket.local`

---

## 4. Estrategia de Ejecución Paso a Paso

### Bloque A · Los tres correos existentes
- **A1 · PIN de seguridad del administrador:**
  - Disparo: Generación de PIN de seguridad para `root` (`GenerateSecurityPinUseCase`).
  - Verificación en Mailtrap: Correo recibido con código de 6 dígitos.
  - Verificación en sistema: Validar que el PIN recibido es el aceptado por el cambio de contraseña.
- **A2 · Reenvío de factura:**
  - Disparo: Reenvío de factura en tienda `tecs` (`ResendInvoiceMailUseCase`).
  - Verificación en Mailtrap: Factura recibida con PDF adjunto y comprobación de importes.
- **A3 · Aviso de tasa BCV obsoleta:**
  - Disparo: `docker compose exec app php artisan exchange-rate:sync-bcv` (limpiando caché `exchange_rate:stale_alert_sent` si aplica).
  - Verificación en Mailtrap: Aviso recibido para superadministradores.

### Bloque B · Recuperación de contraseña
- **B1 · Solicitud válida (`cliente@owomarket.local`):**
  - Disparo: `POST /api-central/auth/password/reset-pin` o ruta web con el correo del cliente.
  - Verificación en Mailtrap: Correo con código de 6 dígitos, texto de caducidad a 15 minutos y advertencia de seguridad si no fue solicitado.
  - Verificación en sistema: PIN aceptado en pantalla de reseteo.
- **B2 · Correo inexistente (`noexiste@example.com`):**
  - Disparo: Solicitud con correo no registrado.
  - Verificación: La API/web responde con mensaje genérico preventivo («Si ese correo tiene una cuenta…»), pero **NO debe emitirse ningún correo** a Mailtrap.

### Bloque C · Módulo de Notificaciones (8 envíos)
- **C1 · Reclamación abierta (Crítico):**
  - Disparo: Provocación de `claimOpened` mediante `NotificationDispatcher` / reclamación de cliente.
  - Verificación en Mailtrap: Recibido en `tecs.owner@owomarket.local`, contiene días restantes para responder y enlace a reclamaciones.
- **C2 · Entrega declarada (Crítico):**
  - Disparo: Provocación de `deliveryDeclared` mediante `NotificationDispatcher` / marcación de entrega.
  - Verificación en Mailtrap: Recibido en comprador (`cliente@owomarket.local`), explica propósito de confirmación y plazo de reclamo.
- **C3 · Identidad verificada / rechazada (Crítico):**
  - Disparo: `kycReviewed` con estado verificado y posteriormente con rechazo + motivo.
  - Verificación en Mailtrap: Recibido por dueño de la tienda. En rechazo, comprueba inclusión del motivo.
- **C4 · Identidad enviada (Opcional):**
  - Condición previa: Activar preferencia de correo para `root`.
  - Disparo: `kycSubmitted`.
  - Verificación en Mailtrap: Recibido en `root`. **Validación crítica de privacidad:** NO debe incluir cédula, RIF ni nombre legal (solo nombre comercial de la tienda).
- **C5 · Retiro solicitado (Opcional):**
  - Disparo: `payoutRequested`.
  - Verificación en Mailtrap: Recibido en `root`, con nombre de la tienda e importe.
- **C6 · Retiro aprobado o rechazado (Opcional):**
  - Disparo: `payoutResolved` (estado `settled` para aprobado, `cancelled` con motivo para rechazado).
  - Verificación en Mailtrap: Al aprobar debe decir expresamente **«pagado»** (no «rechazado»). Al rechazar, debe contener el motivo.
- **C7 · Cambio de plan solicitado y resuelto (Opcional):**
  - Disparo: `planChangeRequested` y `planChangeResolved`.
  - Verificación en Mailtrap: Notificación a `root` de solicitud y notificación a dueño de resolución.
- **C8 · Reclamación resuelta (Opcional):**
  - Disparo: Resolución ordinaria y resolución por reloj/vencimiento (`returns:auto-resolve` o `claimResolved`).
  - Verificación en Mailtrap: Al comprador. **Validación crítica:** En resolución por vencimiento, NO debe decir «La tienda aceptó tu reclamación», sino que no respondió en el plazo. Al dueño le debe llegar aviso de reclamación perdida por no responder.

### Bloque D · Comprobaciones del interruptor de preferencias
- **D1 · Lo crítico no se apaga:**
  - Acción: Desactivar `email_enabled` en `tecs.owner`. Provocar C1 (`claimOpened`).
  - Verificación en Mailtrap: El correo DEBE llegar igualmente.
- **D2 · Lo opcional no llega:**
  - Acción: Con `email_enabled` desactivado, provocar C6 (`payoutResolved`).
  - Verificación en Mailtrap: NO debe llegar correo. Debe registrarse únicamente en base de datos (campana).

---

## 5. Salida Final Esperada

Completar la plantilla del informe tal como lo exige el guion y entregar el resumen de hallazgos sin realizar arreglos en código, preservando la fidelidad del diagnóstico.
