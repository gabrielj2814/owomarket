# 📋 Plan: Corrección de CartProvider y Hooks de Storefront en Páginas del Marketplace

## 1. 🔍 Diagnóstico y Causa Raíz

Al ingresar a la vista de detalle de producto (`/product/{slug}`), se produce el error en consola:
```
Uncaught Error: useCart must be used within a CartProvider
    at useCart (CartContext.tsx:210)
    at TenantProductDetailPage (TenantProductDetailPage.tsx:51)
```

### Causa:
En `StorefrontLayout.tsx`, el proveedor `<CartProvider>` está declarado en el retorno de `StorefrontLayout`:
```tsx
export default function StorefrontLayout({ children, ... }) {
    return (
        <CartProvider currency={currency} domain={domain}>
            ...
            {children}
            ...
        </CartProvider>
    );
}
```

En las páginas de marketplace que consumen `useCart`:
- [TenantProductDetailPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/marketplace/product/TenantProductDetailPage.tsx)
- [TenantCartPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/marketplace/cart/TenantCartPage.tsx)
- [TenantCheckoutPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/marketplace/checkout/TenantCheckoutPage.tsx)
- [TenantOrderConfirmationPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/marketplace/checkout/TenantOrderConfirmationPage.tsx)

El hook `useCart()` se ejecutaba en el cuerpo del componente principal de la página, **antes** de que `<StorefrontLayout>` (y por ende `<CartProvider>`) fuera renderizado por React.

---

## 2. 🛠️ Solución Propuesta

Separar cada página en dos componentes:
1. **Componente de Contenido Interno** (`*Content`): Donde se ejecutan los hooks (`useCart()`, `useState`, etc.) y se renderiza la UI.
2. **Componente Principal Exportado** (`Tenant*Page`): Que envuelve el contenido interno con `<StorefrontLayout>`, asegurando que `<CartProvider>` esté activo en todo el ciclo de vida del árbol de React.

### Archivos a Modificar:
1. [TenantProductDetailPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/marketplace/product/TenantProductDetailPage.tsx)
2. [TenantCartPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/marketplace/cart/TenantCartPage.tsx)
3. [TenantCheckoutPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/marketplace/checkout/TenantCheckoutPage.tsx)
4. [TenantOrderConfirmationPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/marketplace/checkout/TenantOrderConfirmationPage.tsx)

---

## 3. 🧪 Plan de Verificación
1. Validar tipos de TypeScript: `npm run types` (0 errores).
2. Validar suite de pruebas PHP: `php artisan test` (100% pasando).
3. Commit siguiendo Conventional Commits y push a `origin/moduleProduct`.
