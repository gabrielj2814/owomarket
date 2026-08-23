# Auditoría del alta de comercios — `pages/signup/**`

> ## 📌 ESTADO — 23/08/2026
>
> **S1 ✅ · S2 ✅ · S3 ✅. Cero hallazgos abiertos.**
>
> Alcance: `CreateAccountTenantPage.tsx` (398 líneas), `CreateTenantOwnerAccountFormRequest`
> y el controlador del alta. El límite de tasa que faltaba ya se cerró como **A6**.

---

**Fecha:** 23 de agosto de 2026
**Método:** lectura del código y **prueba contra el enrutador real** para S1.

---

## Resumen

La página en sí está mejor construida que las de acceso: no hay un solo `alert()`, y mapea
los errores de validación del servidor campo a campo. Los tres hallazgos no son de forma:
son un destino que no existe, una regla que no llegó, y un formulario público relleno con
datos de otro.

| # | Qué | Severidad | Demostrado |
| :--- | :--- | :--- | :--- |
| **S1** | ✅ El alta termina redirigiendo a una URL que no existe | 🔴 | ✅ 404 contra el enrutador real |
| **S2** | ✅ El alta de comerciante tenía la regla de contraseña más floja de las cuatro | 🟠 | ✅ lectura + test |
| **S3** | ✅ El formulario público venía relleno con los datos de otra persona | 🟠 | ✅ lectura |

---

## S1. 🔴 El alta termina en una página que no existe

> **Estado:** ✅ CERRADO — 23/08/2026

**Dónde:** `CreateAccountTenantPage.tsx:152`

```tsx
setSuccessMessage("¡Cuenta creada exitosamente! Redirigiendo al panel de inicio de sesión...");
setTimeout(() => { window.location.href = "/auth/login-staff"; }, 1800);
```

`login-staff` es el **nombre** de la ruta (`central.web.auth.login-staff`). La **URL** es
`/auth/login`. Alguien tomó una cosa por la otra.

### Demostrado

Contra el enrutador real:

```
GET /auth/login-staff  → 404
GET /auth/login        → 200
```

### Por qué es el peor de los tres

El comerciante rellena el alta, se le crea la cuenta **y su base de datos MySQL entera,
síncrona dentro de la petición** (ver A6). Lee «Redirigiendo al panel de inicio de sesión»,
espera 1,8 segundos y aterriza en un error. **Su tienda existe y él cree que el alta
falló.** Lo más probable es que lo intente otra vez.

Es A1 con otra cara: la interfaz prometiendo un destino que el backend no tiene.

### ✅ Cómo se cerró

`/auth/login`. Se evaluó usar el helper que Wayfinder ya genera (`loginStaff.url()`) y **se
descartó**: el generado es una URL *absoluta* con el dominio incrustado
(`//owomarket.local/auth/login`), que en producción apuntaría al sitio equivocado. La cadena
relativa funciona en cualquier dominio. Aquí la opción aburrida es además la correcta.

**Vigilado por:** un test que comprueba que `/auth/login` responde 200 y que
`/auth/login-staff` sigue sin existir. Si alguien mueve esa ruta, el test cae y obliga a
mirar la redirección del alta.

---

## S2. 🟠 El alta de comerciante tenía la regla de contraseña más floja

> **Estado:** ✅ CERRADO — 23/08/2026

**Dónde:** `CreateTenantOwnerAccountFormRequest:35` — `'required|string|min:8|max:72'`.

**Es el cuarto hermano de A4.** Aquel hallazgo nombraba el registro de comprador y el reset;
el cierre llegó a esos dos. Después apareció el cambio desde «Mi Perfil» (tercero), y ahora
esto.

Y es el que más pesa: un dueño de tienda controla su catálogo, sus pedidos y sus
liquidaciones. Aceptaba `aaaaaaaa` mientras un comprador ya no podía registrarse con eso.

### ✅ Cómo se cerró

`Password::defaults()`, la misma definición que los otros tres.

Se quitaron también los mensajes propios de `password.min` y `password.max`, que decían «al
menos 8 caracteres» — texto que ya no describe la regla. **Un mensaje que miente sobre lo
que se pide es peor que ninguno:** deja al comerciante corrigiendo lo que no falla. El de
`Password::defaults()` enumera los requisitos que faltan.

Y en la página, el `password.length < 8` propio se sustituyó por la comprobación de campo
obligatorio, que no es una regla de contraseña sino de formulario.

### Un fallo del cierre de A4 que esto destapó

Este formulario llevaba `max:72` — el límite de bcrypt, **por encima del cual trunca en
silencio**, de modo que dos contraseñas distintas que compartan los primeros 72 bytes abren
la misma cuenta. Al unificar en A4 se perdió: `Password::defaults()` no lo tenía.

Ahora vive dentro de la definición única (`Password::min(8)->max(72)->...`), así que lo
tienen los cuatro flujos y no sólo el que ya lo traía. **Al unificar se cayó una parte que
estaba bien**, y conviene tenerlo presente: unificar no es sólo quitar copias, es asegurarse
de que la que queda es la mejor de todas.

---

## S3. 🟠 El formulario público venía relleno con los datos de otra persona

> **Estado:** ✅ CERRADO — 23/08/2026

```tsx
name: "Jaen Doe",
email: "Jaen@hoyoverse.com",
password: "Jaen_Doe1234",
store_name: "Zenless Zone Zero Corp",
tenant_name: "zenless-zone-zero-corp.owomarket.local"
```

Es la página **pública** de alta de comercios. Cualquier visitante la veía rellena y a un
clic de dar de alta esa tienda. Con A6 sabemos lo que cuesta ese clic: **una base de datos
MySQL entera, creada síncronamente dentro de la petición**, antes de que nadie apruebe nada.

### ✅ Cómo se cerró

Campos vacíos. Y se aprovechó para cerrar el mismo problema en sus dos hermanas —
`LoginStaff.tsx` y `LoginTenantPage.tsx` traían un usuario y una contraseña reales del
entorno de desarrollo dentro del bundle de JavaScript que se sirve a cualquiera. Estaban
anotadas como punto 3 de `por_revisar.md`, que queda cerrado.

Se decidió vaciar **siempre**, sin excepción para desarrollo: menos condicionales y ninguna
posibilidad de que la excepción se cuele en una build de producción.

---

## Lo que se comprobó y está BIEN

| Qué | Resultado |
| :--- | :--- |
| **`alert()`** | ✅ Ninguno. Esta página ya usaba estado y mensajes en línea |
| **Errores de validación** | ✅ Mapea la respuesta del servidor campo a campo, no un mensaje genérico |
| **Límite de tasa** | ✅ `throttle:altas` desde A6 |
| **`noValidate` en el form** | ✅ Deliberado: la validación la hace el código, no el navegador, así los mensajes son consistentes |
