# Por revisar

> Cosas ciertas y comprobadas que quedaron fuera del alcance de lo que se estaba
> arreglando. No son hallazgos de auditoría: son deudas conocidas, con su motivo de
> aplazamiento. Última actualización: 23/08/2026.

---

## 1. ✅ RESUELTO (23/08/2026) — A1 comprobado contra la aplicación

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

**Comprobado el 23/08/2026 en Docker, con navegador real**, escaparate de tienda a 375×812:

| | |
| :--- | :--- |
| El escaparate carga | ✅ HTTP 200 |
| Botón de acceso → **modal OwO Pass** | ✅ abre, con sus dos pestañas y el enlace de recuperación |
| `/auth/customer/login` (la ruta que borró A1) | ✅ **404** |
| `/auth/login` y `/auth/forgot-password` | ✅ 200 |

### ⚠️ Y la comprobación corrigió el propio hallazgo A1

**El menú móvil que A1 describía es código inalcanzable.** La cadena está cerrada sobre sí
misma y ningún controlador la abre:

```
tenantHomePage.tsx   ← ningún fichero PHP la renderiza
  └─ TenantLayout
       ├─ NavBarMovilMarketComponent   ← los tres enlaces de A1
       └─ SidebarMarketComponent
```

La home de tienda real es `TenantStorefrontHomePage` sobre `StorefrontLayout`, que es la que
se verificó funcionando. Así que la frase de la auditoría —«un cliente que use el menú móvil
se encuentra una pantalla que rechaza sus credenciales correctas»— **no era cierta: ningún
cliente podía llegar a ese menú**. Y el arreglo de A1 sobre esos tres enlaces se aplicó a
código que nadie ejecuta.

Lo que sí valía de A1 se mantiene y queda verificado: la ruta rota existía y ya da 404.

`tenantHomePage` era el gemelo viejo de `TenantStorefrontHomePage` — uno vivo, otro muerto,
nadie borró el muerto. **Los cuatro ficheros se borraron.**

### Nota de montaje

Para navegar hubo que apuntar `chivostore.owomarket.local` —que ya estaba en el `hosts`— a
la tienda del contenedor, y dejarle **un solo dominio**: los assets se sirven desde el
dominio primario del inquilino, así que con dos dominios el navegador pedía uno que el
`hosts` no resuelve. Es cosa del montaje de prueba, no de la aplicación, pero conviene
saberlo si algún día se sirve una tienda por dos dominios.

---

## 2. ✅ RESUELTO (23/08/2026) — El ruido de `vitest`

**Qué:** `npm run test:unit` da `Test Files 7 passed (7)` y `Tests 16 passed (16)`, y
*después* de terminar escupe:

```
AggregateError: connect ECONNREFUSED ::1:3000 / 127.0.0.1:3000
Error: socket hang up
```

Algo intenta conectarse a un servidor en el puerto 3000 que no está levantado, una vez
acabada la ejecución.

### ⚠️ Corrección: no rompía el CI

Aquí se afirmó que «el comando sale con código distinto de cero» y que estaría marcando la
build en rojo. **Era falso.** Comprobado: `npm run test:unit` siempre salió con **código 0**.
El volcado se imprime después del resumen y no afecta al resultado.

Se anota el error en vez de borrarlo: una deuda mal diagnosticada hace perder el tiempo a
quien la recoge, y esta habría mandado a alguien a mirar la configuración del CI.

### Lo que sí era, y sí importaba

**Los tests de componente hacían peticiones de red de verdad.** `CurrencyPriceDisplay` pide
la tasa activa al montarse cuando no se la pasan por prop (hallazgo G13), y `ProductCard` lo
renderiza sin simularla. `happy-dom` sirve `http://localhost:3000` como URL por defecto, así
que esa llamada relativa intentaba conectarse a ese puerto de verdad.

Los tests pasaban porque el fallo se tragaba en silencio, pero eran más lentos, no
deterministas, y **si algo llegara a escuchar en el 3000 los tests hablarían con ello**.

**Cerrado** simulando `ExchangeRateServices` en `tests/Frontend/setup.ts` — no en
`ProductCard.test.tsx`. Cualquier componente que muestre un precio hereda esa petición, así
que arreglarlo en un solo test dejaría al siguiente con el mismo problema.

Resultado: 7 ficheros, 16 tests, cero `ECONNREFUSED`.

---

## 3. ✅ RESUELTO (23/08/2026) — Los dos logins llevaban credenciales precargadas

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

**Resuelto** al cerrar S3 de la auditoría de `signup/**`, donde apareció el mismo problema
en el alta pública de comercios — y allí era peor, porque un clic distraído creaba una tienda
y su base de datos.

Se vacían **siempre**, sin excepción para desarrollo: menos condicionales y ninguna
posibilidad de que la excepción acabe en una build de producción.


---

## 4. T6 — la facturación de una tienda es legible sin autenticarse

**Qué:** en el dominio de cualquier tienda, estos dos GET responden sin sesión:

```
GET /monetization/summary      → tarifa de comisión y resumen de monetización
GET /monetization/settlements  → historial de liquidaciones
```

**Cómo apareció:** cerrando T5, que era el mismo fichero de rutas sin ningún middleware.
Los POST se cerraron; estos dos no.

**Por qué no se cerró a la vez:** es una fuga de datos de negocio, no un cambio de estado.
Protegerlos exige montar sesión de usuario de inquilino en los tests que los usan, y eso es
otro cambio — meterlo en la cola de T5 habría ampliado el alcance de un arreglo de seguridad
sin necesidad.

**Riesgo si está mal:** un competidor que pase por el escaparate ve qué comisión paga esa
tienda y su historial de liquidaciones.

**Cómo cerrarlo:** `auth` + `tenant_can:manage_billing`, el mismo par que ya lleva
`/settlements/pay`, y actualizar los tests que los llaman para que autentiquen.
