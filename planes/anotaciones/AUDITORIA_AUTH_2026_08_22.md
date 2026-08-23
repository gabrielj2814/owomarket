# Auditoría de autenticación — `pages/auth/**`

> ## 📌 ESTADO — 22/08/2026
>
> **5 hallazgos abiertos: A1 🔴 · A2 🔴 · A3 🟠 · A4 🟠 · A5 🟡.**
> **Los cinco están demostrados contra la aplicación real**, no deducidos.
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
| **A1** | La página de login de cliente publica en el endpoint de *staff*: un cliente nunca puede entrar por ahí | 🔴 | ✅ 401 vs 200 con las mismas credenciales |
| **A2** | El PIN de recuperación no tiene límite de intentos | 🔴 | ✅ 42 intentos, ningún 429 |
| **A3** | «Olvidé mi contraseña» revela si un correo existe | 🟠 | ✅ 200 vs 404 con mensaje explícito |
| **A4** | Cuatro reglas de contraseña distintas: se puede crear una cuenta con la que el login se niega a intentar | 🟠 | ✅ lectura de las cuatro |
| **A5** | Todo el feedback de los tres logins es `alert()`, y uno acaba en un callejón sin salida | 🟡 | ✅ lectura |

---

## A1. 🔴 El login de cliente publica en el endpoint equivocado

> **Estado:** ⬜ ABIERTO

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

---

## A2. 🔴 El PIN de recuperación no tiene límite de intentos

> **Estado:** ⬜ ABIERTO

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

---

## A3. 🟠 «Olvidé mi contraseña» revela si un correo existe

> **Estado:** ⬜ ABIERTO

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

---

## A4. 🟠 Cuatro reglas de contraseña distintas

> **Estado:** ⬜ ABIERTO

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

---

## A5. 🟡 Todo el feedback de los logins es `alert()`

> **Estado:** ⬜ ABIERTO

Las tres páginas de login usan `alert()` para errores y aciertos:
[`LoginStaff.tsx:103,108,121,126`](../../resources/js/pages/auth/LoginStaff.tsx#L103),
[`LoginTenantPage.tsx:104,109,122,127`](../../resources/js/pages/auth/LoginTenantPage.tsx#L104),
[`LoginCustomerPage.tsx:103,108,121,126,140`](../../resources/js/pages/auth/LoginCustomerPage.tsx#L103).

No es sólo estética. Un `alert()` bloquea el hilo, no se puede estilar, no es accesible y
—en el caso de `alert("Login exitoso como cliente")`— es literalmente el final del camino:
el usuario acierta la contraseña, acepta un diálogo del navegador y se queda donde estaba.

Las páginas de recuperación (`ForgotPasswordPage`, `ResetPasswordPage`) ya usan estado y
mensajes en línea. Los tres logins son los únicos que se quedaron atrás.

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
- `resources/js/pages/signup/**` (1 página, 398 líneas). Ya se detectó de pasada que
  **`POST /tenant/create/account` no tiene ningún límite de tasa**: el alta de tiendas es
  ilimitada, y cada tienda aprobada crea una base de datos. Pendiente de confirmar en qué
  momento se crea esa base, que es lo que decide la severidad.
