# Auditoría de autenticación — `pages/auth/**`

> ## 📌 ESTADO — 23/08/2026
>
> **LOS SEIS CERRADOS: A1 ✅ · A2 ✅ · A3 ✅ · A4 ✅ · A5 ✅ · A6 ✅.**
>
> Cero hallazgos abiertos en `pages/auth/**`. Lo siguiente sin auditar es
> `customer/**` y `marketplace/**` — y ahí dentro está el checkout.
>
> A2 y A3 se cerraron juntos y primero — por delante de A1, que es más visible— porque se
> componían: A3 entregaba la lista de correos con cuenta y A2 dejaba atacar cada uno sin
> freno. A1 rompe una pantalla; estos dos se explotaban.
>
> **Los cinco originales se demostraron contra la aplicación real**, no se dedujeron. A6
> salió al cerrar A2 y se confirmó leyendo el pipeline de tenancy. Los arreglos, en cambio,
> están comprobados con Pest contra el stack HTTP — middleware, validación y rutas reales—,
> no golpeando la aplicación en Laragon. La distinción importa y por eso queda escrita.
>
> Alcance: las 6 páginas de `resources/js/pages/auth/**` (939 líneas) y los endpoints que
> hay detrás — login de staff, de propietario y de cliente, y el flujo de recuperación de
> contraseña por PIN.
>
> **Se comprobaron también cinco cosas que están BIEN**, y se listan al final. Un informe
> que sólo enumera fallos no dice dónde ya no hace falta mirar.
>
> ### Leyenda
> 🔴 crítico · 🟠 alto · 🟡 medio · ✅ cerrado · ⬜ abierto

---

**Fecha:** 22 de agosto de 2026
**Método:** lectura del código y verificación contra la aplicación real corriendo en
Laragon. Cada hallazgo lleva la comprobación que lo demuestra.

---

## Resumen

Dos patrones, y ninguno es «falta código»:

1. **La misma pregunta contestada en varios sitios, y distinto en cada uno.** Qué es una
   contraseña válida tiene cuatro respuestas (A4). Dónde inicia sesión un cliente tiene dos
   implementaciones, y la que está enlazada en el menú es la que no funciona (A1).

2. **Un arreglo que llegó a un flujo y no a su gemelo.** El hallazgo A7 de la auditoría
   original puso límite de tasa al PIN del administrador razonando que «un millón de
   combinaciones se agota dentro de la ventana de validez». El PIN del **cliente** es
   idéntico y se quedó sin límite (A2).

| # | Qué | Severidad | Demostrado |
| :--- | :--- | :--- | :--- |
| **A1** | ✅ La página de login de cliente publica en el endpoint de *staff*: un cliente nunca puede entrar por ahí | 🔴 | ✅ 401 vs 200 con las mismas credenciales |
| **A2** | ✅ El PIN de recuperación no tiene límite de intentos | 🔴 | ✅ 42 intentos, ningún 429 |
| **A3** | ✅ «Olvidé mi contraseña» revela si un correo existe | 🟠 | ✅ 200 vs 404 con mensaje explícito |
| **A4** | ✅ Cuatro reglas de contraseña distintas: se puede crear una cuenta con la que el login se niega a intentar | 🟠 | ✅ lectura de las cuatro |
| **A5** | ✅ Todo el feedback de los logins era `alert()`, y uno acababa en un callejón sin salida | 🟡 | ✅ lectura |
| **A6** | ✅ El alta de tiendas no tiene límite: cada una crea una base de datos MySQL dentro de la petición | 🔴 | ✅ lectura del pipeline de tenancy |

---

## A1. 🔴 El login de cliente publica en el endpoint equivocado

> **Estado:** ✅ CERRADO — 23/08/2026

**Dónde:** [`resources/js/pages/auth/LoginCustomerPage.tsx:114`](../../resources/js/pages/auth/LoginCustomerPage.tsx#L114)

```tsx
const respuestaServidor = await AuthServices.login(statuFormLogin)
```

`AuthServices.login()` publica en **`/auth/login`**, que es el login de personal central y
propietarios: busca en la tabla `users`. Un cliente vive en `central_customers`, así que no
está ahí y nunca lo estará.

### Demostrado

Mismas credenciales válidas del cliente de demostración, contra los dos endpoints:

```
/auth/login  (el que usa la página)  → 401  "El usuario no encontrado"
/api/central/customer/login          → 200  "Sesión iniciada correctamente"
```

### Y aunque acertara, no llevaría a ninguna parte

```tsx
const irHaPorElRol = (rol:string, uuid:string) => {
    if (rol === 'customer') {
        alert("Login exitoso como cliente");
        // window.location.href = BACKOFICCE_ADMIN_DASHBOARD;   ← comentado
    }
}
```

### Alcance

La página se sirve en `/auth/customer/login` y está enlazada **tres veces desde el menú
móvil del marketplace** ([`NavBarMovilMarketComponent.tsx`](../../resources/js/components/ui/marketplace/NavBarMovilMarketComponent.tsx)).
El login que sí funciona es el modal de `CustomerAuthContext`
(`CustomerAuthServices.loginCentral`), que es por donde entra todo el mundo en la práctica.

Un cliente que use el menú móvil se encuentra una pantalla que rechaza sus credenciales
correctas.

### Arreglo

O la página usa `CustomerAuthServices.loginCentral` y redirige al portal, o se borra y el
enlace del menú abre el modal que ya funciona. Lo segundo es menos código y una sola forma
de entrar; ahora mismo hay dos y una está rota.

### ✅ Cómo se cerró — se borró

Se evaluaron las dos salidas en serio. **Arreglarla costaba unas diez líneas**, no era
difícil: cambiar `AuthServices.login` por `useCustomerAuth().login`, quitar la validación de
contraseña y redirigir a `/account/dashboard`. La dificultad no fue el criterio.

Lo que decidió fue que una página arreglada no tendría lógica propia — sería el modal con
otro marco alrededor. Y **dos implementaciones de lo mismo es el modo de fallo documentado
de este repositorio**: A7 llegó al PIN del administrador y no al del cliente, A2 y A3
llegaron a las APIs y no a las páginas, tres comentarios distintos afirmaban un límite de
tasa que nadie había puesto. Aquí no era un riesgo teórico.

Además el modal hace dos cosas que la página no hacía y habría que haberle portado:
el intercambio SSO cuando el comprador está en el escaparate de un comerciante en vez de en
el marketplace central, y **no perder el formulario a medio rellenar en el checkout** —
[`TenantCheckoutPage.tsx:1050`](../../resources/js/pages/marketplace/checkout/TenantCheckoutPage.tsx#L1050)
documenta que navegar a una página de login ya se probó y se revirtió justo por eso.

**Se borró:** la página (197 líneas), su controlador `LoginCustomerScreenGETController` y la
ruta `central.web.auth.login-customer`. Los ficheros generados por Wayfinder desaparecieron
solos al regenerar.

**Los tres enlaces del menú móvil** abren ahora el modal. Hizo falta montar
`<CustomerAuthModal />` en [`TenantLayout`](../../resources/js/components/layouts/TenantLayout.tsx),
que es donde vive ese menú y que no lo renderizaba: sin esa línea `openAuthModal()` cambiaba
el estado y no aparecía nada. El provider ya era global, así que era eso solo.

**Se descartó** jubilar el modal y dejar la página como entrada única. `openAuthModal` tiene
siete puntos de llamada, y dos son los checkouts: ahí el modal es lo que evita perder el
formulario, y la página vive en el dominio central mientras el checkout de una tienda vive
en el del comerciante.

**De regalo:** se lleva por delante un tercio de A5. Quedan los `alert()` de `LoginStaff` y
`LoginTenantPage`.

**Vigilado por:** `tests/Feature/CentralCustomer/CentralCustomerAuthTest.php` — un test que
comprueba que la ruta responde 404. Es lo único que impide que la página vuelva sin que
nadie se entere.

---

## A2. 🔴 El PIN de recuperación no tiene límite de intentos

> **Estado:** ✅ CERRADO — 23/08/2026

**Dónde:** [`src/CentralCustomer/Infrastructure/Http/Routes/apiCentral.php:48-49`](../../src/CentralCustomer/Infrastructure/Http/Routes/apiCentral.php#L48)

```php
Route::post('/forgot-password', SendCustomerPasswordResetPinPOSTController::class);
Route::post('/reset-password', ResetCustomerPasswordWithPinPOSTController::class);
```

Ninguna de las dos lleva `throttle`. Y nada cuenta intentos dentro del caso de uso ni de la
tabla: se comprobó.

**Es el hallazgo A7 otra vez.** Aquél decía, sobre el PIN del administrador:

> *«el PIN son 6 dígitos (un millón de combinaciones) y NO había ningún límite de tasa […]
> así que el espacio entero se agotaba dentro de la ventana de validez de 15 minutos»*

El PIN del cliente es el mismo: `random_int(100000, 999999)` con 15 minutos de validez
([`SendCentralCustomerPasswordResetPinUseCase.php:30-32`](../../src/CentralCustomer/Application/UseCases/SendCentralCustomerPasswordResetPinUseCase.php#L30)).
La corrección llegó al flujo del administrador y no al del cliente.

### Demostrado

42 intentos consecutivos contra la aplicación real (12 + 30), **todos 422 «PIN inválido»,
ningún 429**.

### Cuánto de explotable es, con números

Conviene no exagerarlo. Medido en esta máquina:

```
30 intentos secuenciales → 3.308 ms   =  9 intentos/segundo
ventana de 15 min        → ~8.200 intentos  =  0,9 % del espacio
```

Así que **no** se revienta en una ventana. Pero pedir un PIN nuevo tampoco tiene límite, de
modo que se encadenan ventanas: ~110 ciclos para cubrir el espacio, unas **27 horas
secuenciales desde una sola conexión**, o algo más de una hora con veinte en paralelo.
Es un ataque de fin de semana contra una cuenta concreta, no un ataque instantáneo.

### Arreglo

Los limitadores con nombre ya existen desde N18. `throttle:credenciales` encaja tal cual en
`reset-password` (5/min por cuenta + 20/min por IP) y `throttle:altas` o similar en
`forgot-password`, que además hoy permite enviar correos sin freno a costa del proyecto.

### ✅ Cómo se cerró

`reset-password` lleva `throttle:credenciales` tal cual: lo que se está adivinando ahí es
un secreto de seis dígitos, y eso se cuenta por minuto.

`forgot-password` **no** reusa `throttle:altas`. Son 3/hora contando sólo por IP, y aplicado
a la recuperación de contraseña eso deja sin su única puerta a toda una oficina detrás de un
NAT por culpa de una persona. Se añadió `throttle:recuperacion` con los dos cerrojos del
mismo patrón que `credenciales`: **3/hora por cuenta** (que es lo que impide llenarle el
buzón a alguien y encadenar ventanas) y **10/hora por IP**, más holgado a propósito.

**Encontrado de paso, y es la causa de que durara:** el comentario que había sobre estas
rutas afirmaba que *«el PIN ya llevaba freno desde la Fase 4.1»*, y la cabecera de
`RateLimitingTest.php` repetía que aquella fase *«puso `throttle:5,15` en las dos rutas del
PIN»*. Ninguna de las dos cosas era verdad. Dos sitios declaraban cerrada una puerta
abierta. Ambos comentarios están corregidos.

**Vigilado por:** tres tests en [`tests/Feature/Security/RateLimitingTest.php`](../../tests/Feature/Security/RateLimitingTest.php)
— el corte del PIN a los cinco intentos, el corte de peticiones de código a las tres por
hora, y que una cuenta agotada no arrastre a las demás de la misma IP.

---

## A3. 🟠 «Olvidé mi contraseña» revela si un correo existe

> **Estado:** ✅ CERRADO — 23/08/2026

**Dónde:** [`SendCentralCustomerPasswordResetPinUseCase.php:21-22`](../../src/CentralCustomer/Application/UseCases/SendCentralCustomerPasswordResetPinUseCase.php#L21)

```php
if (! $customer) {
    throw new Exception('No existe una cuenta registrada con este correo electrónico.', 404);
}
```

### Demostrado

Misma petición, dos correos:

```
cliente@owomarket.local      → 200  "Se ha generado el código de recuperación…"
no.existe.jamas@example.com  → 404  "No existe una cuenta registrada con este correo"
```

Y [`ForgotPasswordPage.tsx:28`](../../resources/js/pages/auth/ForgotPasswordPage.tsx#L28)
muestra ese mensaje tal cual al visitante.

**Junto con A2 se compone solo:** sin límite de tasa, esto permite recorrer una lista de
correos y quedarse con los que tienen cuenta en la plataforma. La respuesta es distinta en
código HTTP *y* en texto, así que no hace falta ni medir tiempos.

### Arreglo

Responder siempre lo mismo —«si ese correo tiene cuenta, te hemos enviado un código»— y
enviar el PIN sólo cuando exista. Es la respuesta estándar y no le cuesta nada al usuario
legítimo, que de todas formas va a ir a su correo.

### ✅ Cómo se cerró

El caso de uso ya no lanza el 404: cuando el correo no tiene cuenta sale en silencio con la
misma forma, el mismo 200 y el mismo texto, sin crear registro ni enviar nada. El mensaje
vive en una única constante (`MENSAJE_NEUTRO`) usada por las dos ramas, precisamente para
que no puedan divergir por descuido más adelante.

También se neutralizó el texto de reserva de
[`ForgotPasswordPage.tsx`](../../resources/js/pages/auth/ForgotPasswordPage.tsx), que
afirmaba el envío por su cuenta si el servidor no mandaba mensaje.

**Lo que queda, dicho sin adornos:** la rama con cuenta escribe en la base y la otra no, así
que sigue habiendo una diferencia de *tiempo* entre las dos. Es una señal mucho más débil
que un 404 con texto explícito, y taparla del todo pide trabajo simulado. Está reducido, no
eliminado, y así está anotado en el código.

**Vigilado por:** dos tests unitarios y uno de feature que compara las dos respuestas
carácter a carácter. El test que antes **exigía** el 404 se reescribió para exigir lo
contrario: tal como estaba, blindaba la fuga.

---

## A4. 🟠 Cuatro reglas de contraseña distintas

> **Estado:** ✅ CERRADO — 23/08/2026

| Dónde | Qué exige |
| :--- | :--- |
| Registro de cliente, **servidor** ([`RegisterCentralCustomerPOSTController.php:23`](../../src/CentralCustomer/Infrastructure/Http/Controller/RegisterCentralCustomerPOSTController.php#L23)) | `min:6` y nada más |
| Login, **cliente** (las 3 páginas) | 8–72, mayúscula **+** minúscula **+** dígito **+** carácter especial |
| Reset, **cliente** ([`ResetPasswordPage.tsx:34`](../../resources/js/pages/auth/ResetPasswordPage.tsx#L34)) | sólo `>= 8` |
| Reset, **servidor** | `min:8` |

**La consecuencia práctica:** alguien se registra con `abc123` —el servidor lo acepta sin
pestañear— y al ir a iniciar sesión el formulario **ni siquiera envía la petición**. Le
dice «La contraseña no cumple con los requisitos de seguridad», sobre la contraseña que el
propio sistema le dejó elegir cinco minutos antes.

Se sale, pero sólo si adivina que la salida es «olvidé mi contraseña» para ponerse otra que
sí cumpla. Nada se lo sugiere.

Que la validación viva en el cliente tiene además otro efecto: **el servidor no comprueba
esas reglas en ningún momento**, así que son un obstáculo para el usuario honesto y ninguno
para quien envíe la petición a mano.

### Arreglo

Una sola definición, en el servidor, aplicada en registro y en reset. El cliente puede
repetirla para dar aviso temprano, pero no debe ser quien decide — y desde luego el login
no debería validar el formato de una contraseña que ya existe: eso sólo sirve para dejar
fuera a gente con contraseñas antiguas.

### ✅ Cómo se cerró

Primero, el alcance encogió: la validación de cliente del login de comprador se fue con la
página que borró A1. Quedaban cuatro sitios y ahora hay **uno**.

`Password::defaults()` en [`AppServiceProvider`](../../app/Providers/AppServiceProvider.php)
es la única definición: `min(8)->mixedCase()->numbers()->symbols()`. Es la regla que los dos
logins ya exigían en el navegador, así que no sorprende a nadie que conozca el sistema — pero
ahora **la comprueba el servidor**, que antes no la comprobaba en ningún momento. No se
escribió ninguna regla propia: la trae Laravel.

Registro y reset la usan los dos vía `Password::defaults()`, así que coinciden **por
construcción y no por disciplina**. Eso es deliberado: la disciplina es exactamente lo que
falló en A2, en A3 y en A7.

**Se quitaron los otros tres sitios:** `validatePassword` y `PasswordValidationRules` de
`LoginStaff.tsx` y `LoginTenantPage.tsx`, y el `password.length < 8` propio de
`ResetPasswordPage.tsx`.

**Lo que importa de este cambio no es la regla, es a qué se aplica.** Solo a contraseñas
nuevas. Quien se dio de alta cuando el servidor pedía `min:6` sigue entrando con su
contraseña de seis caracteres. Si la regla tocara también al login, cada uno de esos
clientes se quedaría fuera de su propia cuenta, y la única salida sería adivinar que va por
«olvidé mi contraseña» — que es precisamente el callejón que describía este hallazgo, pero
peor.

**Se descartó** `uncompromised()`: consulta HaveIBeenPwned por red dentro del alta, y eso es
latencia y una dependencia externa en el camino crítico.

### ⚠️ Se cerro incompleto DOS VECES — corregido el 23/08/2026

**Segundo hermano perdido:** auditando `signup/**` aparecio un CUARTO sitio donde nace una
contrasena — `CreateTenantOwnerAccountFormRequest`, el alta de comerciante — que seguia en
`min:8|max:72` sin complejidad. Es el que mas pesa de los cuatro: un dueno de tienda
controla su catalogo, sus pedidos y sus liquidaciones, y aceptaba `aaaaaaaa` cuando un
comprador ya no podia. Cerrado como **S2**.

**Y el cierre se llevo por delante algo que estaba bien:** aquel formulario tenia `max:72`,
el limite de bcrypt, por encima del cual trunca en SILENCIO — dos contrasenas distintas que
compartan los primeros 72 bytes abren la misma cuenta. `Password::defaults()` no lo tenia.
Ahora vive dentro de la definicion unica, asi que lo tienen los cuatro flujos y no solo el
que ya lo traia.

La leccion, escrita para la proxima vez: **unificar no es solo quitar copias, es asegurarse
de que la que queda es la mejor de todas.**

---

### ⚠️ Primer hermano perdido — corregido el 23/08/2026

Al auditar `customer/**` aparecio un **tercer** sitio donde nace una contrasena, que este
hallazgo no nombraba y el cierre no toco:
[`UpdateCustomerProfilePUTController:35`](../../src/CentralCustomer/Infrastructure/Http/Controller/UpdateCustomerProfilePUTController.php#L35),
el cambio de contrasena desde «Mi Perfil». Se quedo en `min:8` mientras el registro y el
reset ya exigian mayuscula, minuscula, digito y simbolo.

**La politica se podia esquivar entera:** registrarse cumpliendo la regla y despues cambiar
la contrasena a `aaaaaaaa` desde el perfil. Y la pagina tenia ademas su propio
`newPassword.length < 8`, con lo que los sitios que contestaban a la pregunta seguian siendo
tres, no uno.

Es el patron de este repositorio aplicado al propio arreglo: **llego a dos hermanos de
tres**. Queda anotado sin adornos porque el hallazgo original avisaba justo de esto.

Ya usa `Password::defaults()`, la comprobacion del cliente se quito, y hay un cuarto test que
lo vigila.

---

**Vigilado por cuatro tests**, y el que de verdad importa es el primero:
- una contraseña antigua que ya no cumple la regla **sigue sirviendo para entrar**;
- el registro rechaza una débil;
- el reset la rechaza igual;
- y el cambio desde «Mi Perfil» también.

Dos tests existentes fallaron al aplicar el cambio, y estaban en lo cierto: codificaban las
reglas viejas (`secret12345` en el registro, `new_secure_pass_999` en el reset). Se
actualizaron. Que fallaran exactamente esos dos y ninguno más es la señal de que el cambio
llegó donde debía y no más allá.

---

## A5. 🟡 Todo el feedback de los logins es `alert()`

> **Estado:** ✅ CERRADO — 23/08/2026

Las tres páginas de login usan `alert()` para errores y aciertos:
[`LoginStaff.tsx:103,108,121,126`](../../resources/js/pages/auth/LoginStaff.tsx#L103),
[`LoginTenantPage.tsx:104,109,122,127`](../../resources/js/pages/auth/LoginTenantPage.tsx#L104),
[`LoginCustomerPage.tsx:103,108,121,126,140`](../../resources/js/pages/auth/LoginCustomerPage.tsx#L103).

No es sólo estética. Un `alert()` bloquea el hilo, no se puede estilar, no es accesible y
—en el caso de `alert("Login exitoso como cliente")`— es literalmente el final del camino:
el usuario acierta la contraseña, acepta un diálogo del navegador y se queda donde estaba.

Las páginas de recuperación (`ForgotPasswordPage`, `ResetPasswordPage`) ya usan estado y
mensajes en línea. Los tres logins son los únicos que se quedaron atrás.

### ✅ Cómo se cerró

El tercero —`LoginCustomerPage`, el del `alert("Login exitoso como cliente")` que era el
callejón sin salida— se fue entero con A1. Quedaban dos.

No se inventó un patrón: se copió el que `ForgotPasswordPage` y `ResetPasswordPage` ya
usaban en este mismo directorio. Estado `errorMsg` y un banner en línea dentro del
formulario, con `role="alert"` para que un lector de pantalla lo anuncie — que es la mitad
accesible del hallazgo, y la que no se arregla sola al quitar el `alert()`.

**Se arregló de paso un fallo real que el `alert()` escondía.** Las dos ramas de error leían
`respuestaServidor.response?.data.message`, y la segunda —la que salta cuando el servidor
responde 200 pero sin datos— no tiene `response`, así que el diálogo decía literalmente
`undefined`. Ahora hay texto de reserva.

También se borró un `alert()` de depuración comentado en `LoginTenantPage`.

**Lo que queda igual a propósito:** el `alert()` no era el único problema de estas dos
pantallas, pero sí el del hallazgo. Siguen usando credenciales de prueba precargadas en el
estado inicial, que es otra cosa y no se toca aquí.

---

## A6. 🔴 El alta de tiendas no tiene límite, y cada alta crea una base de datos

> **Estado:** ✅ CERRADO — 23/08/2026

**Dónde:** [`src/Tenant/Infrastructure/Http/Routes/web.php:62`](../../src/Tenant/Infrastructure/Http/Routes/web.php#L62)

Salió de pasada al cerrar A2. La pregunta que quedaba pendiente era *en qué momento se crea
la base de datos*, porque eso decidía la severidad. La respuesta es la peor de las dos:

`CreateTenantUseCase` guarda el tenant → se dispara `TenantCreated` → el pipeline de
[`TenancyServiceProvider.php:28-40`](../../app/Providers/TenancyServiceProvider.php#L28)
corre `CreateDatabase` + `MigrateDatabase` con **`shouldBeQueued(false)`**.

Es decir: **una base de datos MySQL nueva y toda la tanda de migraciones dentro de la propia
petición HTTP**, sin autenticar y antes de que nadie apruebe la tienda. No es «cada tienda
aprobada crea una base»; es cada tienda *solicitada*. Sin tope eso llena el disco y además
retiene un worker por petición, que es la mitad barata del ataque.

### El tercer comentario que declaraba cerrada una puerta abierta

[`GovernanceRoutesAreGatedTest.php:60`](../../tests/Feature/Security/GovernanceRoutesAreGatedTest.php#L60)
exime deliberadamente esta ruta del control de rol, y razona:

> *«El alta pública de tienda es deliberadamente anónima: es el formulario de registro, y su
> protección es el límite de tasa, no el rol.»*

El razonamiento es correcto. El límite de tasa no existía. Con A2 son **tres sitios** en
este repositorio afirmando un freno que no estaba puesto.

### El hermano

`POST /tenant/owner/tenant` ([`web.php:86`](../../src/Tenant/Infrastructure/Http/Routes/web.php#L86))
crea tenants por el mismo camino. Lleva `auth`, pero `auth` no es un tope: un propietario con
sesión podía crear tiendas —y bases de datos— en bucle igual que un anónimo. Buscarlo fue
aplicar el patrón que ya se repite en este proyecto: *cada arreglo llegó a un flujo y se
saltó a su gemelo*.

### ✅ Cómo se cerró

`throttle:altas` a las dos rutas. No hizo falta limitador nuevo: el que existe desde N18 son
3/hora por IP y su comentario ya decía exactamente esto —«crear cuentas es legítimo, crear
cientos es lo que llena la base de basura»—. Crear una tienda es un acto deliberado y raro;
tres por hora sobra para el comerciante honesto, y aquí el argumento del NAT que sí aplicaba
en A2 no aplica: tres altas de tienda por hora desde una oficina no es un patrón legítimo.

**Vigilado por:** dos tests en [`RateLimitingTest.php`](../../tests/Feature/Security/RateLimitingTest.php)
— el corte del alta pública a las tres por hora, y que la ruta del propietario lleve el
limitador. El segundo comprueba el middleware en vez de hacer tres altas reales: montarlas
crearía tres bases de datos de verdad, que es justo lo que se está impidiendo.

---

## Lo que se comprobó y está BIEN

Estas cinco se verificaron a propósito, y no hace falta volver a mirarlas:

| Qué | Resultado |
| :--- | :--- |
| **Fijación de sesión** | ✅ **No la hay.** La lectura del código lo sugería —`Auth::login()` sin `session()->regenerate()`— pero la prueba de comportamiento lo desmiente: la cookie anterior al login **no** autentica después. `Auth::login()` migra la sesión por dentro. Se deja anotado precisamente porque el código invita a la conclusión contraria |
| **Caducidad del PIN** | ✅ Se comprueba con `where('expires_at', '>', now())` |
| **PIN de un solo uso** | ✅ Se borran todos los del correo tras un reset correcto |
| **PIN anterior al pedir otro** | ✅ Se borra antes de crear el nuevo |
| **Fuga del PIN en la respuesta** | ✅ Sólo se devuelve en `local` y `testing` |
| **Correo duplicado al registrar** | ✅ El caso de uso lo rechaza con 422 |
| **El PIN no viaja en la URL** | ✅ La página de reset sólo lee `email` de la query; el PIN lo teclea la persona |

> La primera fila es la que más conviene retener: **una lectura del código me llevó a un
> falso positivo**, y sólo la prueba contra la aplicación real lo evitó. En autenticación,
> el código dice lo que parece; el comportamiento dice lo que es.

---

## Lo que queda por auditar

- `resources/js/pages/customer/**` (11 páginas, 2.801 líneas) — el portal del cliente.
- `resources/js/pages/marketplace/**` (14 páginas, 6.540 líneas) — escaparate y checkout.
- `resources/js/pages/signup/**` (1 página, 398 líneas). El límite de tasa que faltaba
  ya está cerrado como **A6** (ver abajo); la página en sí sigue sin auditar.
