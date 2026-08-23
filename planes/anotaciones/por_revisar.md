# Por revisar

> Cosas ciertas y comprobadas que quedaron fuera del alcance de lo que se estaba
> arreglando. No son hallazgos de auditoría: son deudas conocidas, con su motivo de
> aplazamiento. Última actualización: 23/08/2026.

---

## 1. A1 no se ha comprobado a mano contra la aplicación

**Qué:** el cierre de A1 borró `/auth/customer/login` y cambió los tres enlaces del menú
móvil para que abran el modal de acceso. Eso obligó a montar `<CustomerAuthModal />` en
[`TenantLayout`](../../resources/js/components/layouts/TenantLayout.tsx), que no lo
renderizaba.

**Qué está demostrado:** un test comprueba que la ruta responde 404
(`tests/Feature/CentralCustomer/CentralCustomerAuthTest.php`). `tsc` y el build pasan.

**Qué NO está demostrado:** que al pulsar «Login» en el menú móvil el modal aparezca de
verdad. Nadie ha hecho ese clic. Es el único cambio de la jornada del 23/08 cuyo resultado
no puede demostrar ningún test de los que se corrieron.

**Riesgo si está mal:** el menú móvil se queda sin acceso — el estado cambia y no aparece
nada. Sería peor que el bug original, que al menos mostraba una pantalla.

**Cómo comprobarlo:** levantar la app, entrar por el dominio de una tienda en móvil, abrir
el menú y pulsar Login. El cajón debe cerrarse y aparecer el modal.

---

## 2. `vitest` sale con error aunque los 16 tests pasen

**Qué:** `npm run test:unit` da `Test Files 7 passed (7)` y `Tests 16 passed (16)`, y
*después* de terminar escupe:

```
AggregateError: connect ECONNREFUSED ::1:3000 / 127.0.0.1:3000
Error: socket hang up
```

Algo intenta conectarse a un servidor en el puerto 3000 que no está levantado, una vez
acabada la ejecución.

**Por qué importa:** el comando sale con código distinto de cero. Si hay CI, está marcando
la build en rojo sin que ningún test falle — y eso enseña a ignorar el rojo, que es peor
que no tener CI.

**Estado:** es anterior a la sesión del 23/08 y no lo causó ninguno de los arreglos de esa
jornada. No se tocó por no mezclarlo con la auditoría.

---

## 3. Los dos logins llevan credenciales de prueba precargadas

**Dónde:** [`LoginStaff.tsx`](../../resources/js/pages/auth/LoginStaff.tsx) y
[`LoginTenantPage.tsx`](../../resources/js/pages/auth/LoginTenantPage.tsx).

```tsx
const [statuFormLogin, setStatuFormLogin] = useState<FormLogin>({
    email: "root@owomarket.local",
    password: 'OwO_12345678',
});
```

El formulario nace relleno con un usuario y una contraseña reales del entorno de
desarrollo. Se vio al cerrar A5 y se dejó a propósito: A5 era sobre el `alert()`, y esto es
otra cosa.

**Por qué importa si esto va a producción:** son credenciales en el bundle de JavaScript
que se sirve al navegador de cualquiera. Aunque esas cuentas no existan en producción,
publican la convención de nombres y el formato de contraseña que usa la plataforma.

**Pendiente de decidir:** si se vacían siempre, o solo cuando `import.meta.env.DEV` sea
falso.
