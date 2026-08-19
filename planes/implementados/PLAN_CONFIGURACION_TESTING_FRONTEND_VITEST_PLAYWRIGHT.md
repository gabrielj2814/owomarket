# 📋 Plan Maestro: Configuración de Testing Frontend (Vitest + React Testing Library + Playwright)

> **Estado:** Pendiente de Aprobación  
> **Rama:** `moduleProduct`  
> **Área:** Frontend (`resources/js/`), Configuración de Testing (`tests/Frontend/`)

---

## 1. 🎯 Objetivo

Implementar y configurar una infraestructura de testing automatizado de primer nivel para el Frontend (React 19 + TypeScript + Inertia.js v2 + Vite 7), estableciendo tanto pruebas unitarias/componentes con **Vitest + React Testing Library** como pruebas End-to-End (E2E) con **Playwright**, integrando la regla obligatoria de testing frontend para cada nueva interfaz o funcionalidad que se desarrolle en el proyecto.

---

## 2. 🏗️ Arquitectura de Testing Frontend

```
tests/Frontend/
├── setup.ts                                # Setup global de Vitest, jest-dom matchers y mocks de Inertia
├── Components/                             # Tests de Componentes React (Vitest + Testing Library)
│   ├── ProductCard.test.tsx                # Renderizado, variantes, stock y botón agregar
│   ├── ModalAlertConfirmation.test.tsx     # Modales de confirmación y eventos de acción
│   ├── PaginationNavigationCustom.test.tsx # Paginación y navegación
│   └── HeaderToasts.test.tsx               # Notificaciones flash
├── Unit/                                   # Tests unitarios de utilidades y hooks
│   └── formatters.test.ts                  # Helpers de moneda, formato y utilidades
└── E2E/                                    # Tests End-to-End en navegador real (Playwright)
    ├── storefront-browsing.spec.ts         # Catálogo de storefront, variantes y filtros
    ├── storefront-cart.spec.ts             # Carrito de compras, MiniCartDrawer y cupones
    └── customer-auth-flow.spec.ts          # Modal de login/registro en storefront y checkout gate
```

---

## 3. 📦 Dependencias a Instalar

```bash
npm install -D vitest @testing-library/react @testing-library/jest-dom @testing-library/user-event jsdom @playwright/test
npx playwright install chromium --with-deps
```

| Paquete | Rol |
| :--- | :--- |
| `vitest` | Test runner ultrarrápido nativo para Vite (sin problemas de ESM/TypeScript). |
| `@testing-library/react` | Renderizado y consultas de accesibilidad/DOM para componentes React 19. |
| `@testing-library/jest-dom` | Assertions semánticas (`toBeInTheDocument`, `toHaveTextContent`). |
| `@testing-library/user-event` | Simulación realista de clicks, teclado y eventos del navegador. |
| `jsdom` | Entorno DOM virtual en memoria para pruebas de componentes y hooks. |
| `@playwright/test` | Motor E2E para ejecución en navegadores reales con soporte Multi-Tenant wildcard. |

---

## 4. ⚙️ Archivos de Configuración

### 4.1 — `vitest.config.ts` (Raíz del proyecto)
Configuración dedicada para Vitest que hereda los plugins de React y Tailwind de Vite, configurando `jsdom` como entorno de pruebas y `tests/Frontend/setup.ts` como setup inicial.

### 4.2 — `playwright.config.ts` (Raíz del proyecto)
Configuración de Playwright orientada al entorno Multi-Tenant:
- `baseURL`: `http://127.0.0.1:8000` (o host configurado en desarrollo).
- Navegadores: Chromium (rápido y estándar).
- Manejo de capturas de pantalla y traces en caso de fallo.
- Servidor web automático (`webServer`) para levantar el backend en testing si no está activo.

### 4.3 — `tests/Frontend/setup.ts`
- Importa `@testing-library/jest-dom/vitest`.
- Configura mocks automáticos de `@inertiajs/react` (`router.visit`, `router.post`, `usePage`, `Link`) para renderizar componentes de Inertia de manera aislada sin requerir el servidor de Laravel activo durante las pruebas unitarias.

### 4.4 — Scripts en `package.json`
```json
"scripts": {
    "test:unit": "vitest run",
    "test:unit:watch": "vitest",
    "test:e2e": "playwright test",
    "test:e2e:ui": "playwright test --ui",
    "test:frontend": "vitest run && playwright test"
}
```

---

## 5. 🧪 Suites de Pruebas Iniciales

### 5.1 — Pruebas de Componentes con Vitest:
1. `tests/Frontend/Components/ProductCard.test.tsx`:
   - Valida el renderizado correcto del título, precio formateado y badge de descuento.
   - Valida la selección de variantes y el estado "Agotado" vs "Disponible".
   - Valida la emisión del evento de agregar al carrito.
2. `tests/Frontend/Components/ModalAlertConfirmation.test.tsx`:
   - Valida la apertura/cierre del modal, textos de advertencia y callbacks de confirmación.
3. `tests/Frontend/Components/PaginationNavigationCustom.test.tsx`:
   - Valida el cálculo de páginas, deshabilitación de anterior/siguiente en límites.
4. `tests/Frontend/Components/HeaderToasts.test.tsx`:
   - Valida la visualización de mensajes flash (éxito, error, advertencia) provenientes de Inertia.

### 5.2 — Pruebas E2E con Playwright:
1. `tests/Frontend/E2E/storefront-browsing.spec.ts`:
   - Carga la página principal del storefront del tenant.
   - Navega al catálogo, interactúa con el buscador y filtros de categorías.
   - Accede al detalle de producto y verifica la reactividad de variantes.
2. `tests/Frontend/E2E/storefront-cart.spec.ts`:
   - Agrega productos al carrito, abre el `MiniCartDrawer` y verifica el subtotal reactivo.
   - Aplica un cupón de descuento y valida el recálculo en tiempo real.

---

## 6. 📜 Actualización de Reglas del Proyecto

Actualizar `.agents/AGENTS.md` y `reglas.md` incorporando formalmente la regla:
> **Regla de Testing Integral Frontend & Backend:**
> Cada vez que se desarrolle o modifique una interfaz de usuario, componente, modal, formulario o flujo de navegación en el frontend, se deben crear y validar sus pruebas unitarias/de componente correspondientes con Vitest (`npm run test:unit`) y sus pruebas de integración/E2E con Playwright (`npm run test:e2e`), garantizando 0 errores en TypeScript (`npm run types`) y 100% de tests pasando antes de realizar el commit y push.

---

## 7. 🚀 Plan de Ejecución Paso a Paso

1. Instalar dependencias npm (`vitest`, `@testing-library/react`, `@testing-library/jest-dom`, `@testing-library/user-event`, `jsdom`, `@playwright/test`).
2. Instalar los binarios de Playwright Chromium (`npx playwright install chromium`).
3. Crear `vitest.config.ts` y `playwright.config.ts`.
4. Crear `tests/Frontend/setup.ts` con los mocks de Inertia y matchers de DOM.
5. Implementar los tests de componentes React en `tests/Frontend/Components/`.
6. Implementar los tests E2E en `tests/Frontend/E2E/`.
7. Actualizar `package.json` con los comandos de testing.
8. Actualizar `.agents/AGENTS.md` y `reglas.md`.
9. Ejecutar `npm run test:unit`, `npm run test:e2e`, `npm run types` y `php artisan test`.
10. Crear commit convencional y sincronizar con `git push origin moduleProduct`.
