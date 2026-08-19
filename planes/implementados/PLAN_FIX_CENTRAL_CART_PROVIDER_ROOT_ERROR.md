# Plan: Proveedor Global CentralCartProvider en Inertia App (Fix Uncaught Error useCentralCart) [IMPLEMENTADO AL 100%]

## 1. Diagnóstico del Error
Al ingresar a:
- `http://owomarket.local/account/orders`
- `http://owomarket.local/account/wishlist`

Se producía el siguiente error en la consola:
`Uncaught Error: useCentralCart must be used within a CentralCartProvider`

### Causa Técnica:
Las páginas `CustomerOrdersPage.tsx` (para la función de *"Volver a Comprar en 1 Clic"*) y `CustomerWishlistPage.tsx` (para la función de *"Mover Favorito al Carrito"*) invocaban el hook `useCentralCart()` directamente en el cuerpo del componente antes de montar el JSX donde se ubicaba `<CentralLayout>`.

---

## 2. Solución Implementada

1. **Envolver la Aplicación Globalmente con `CentralCartProvider` en `resources/js/app.tsx`:**
   - Se envolvió el componente raíz `<App {...props} />` dentro de `<CentralCartProvider>` junto a `<CustomerAuthProvider>`.
   ```tsx
   root.render(
       <CustomerAuthProvider>
           <CentralCartProvider>
               <App {...props} />
           </CentralCartProvider>
       </CustomerAuthProvider>
   );
   ```
   - Esto garantiza que el carrito centralizado multi-tienda y sus funciones de agregar productos estén permanentemente disponibles para cualquier página, modal, botón de reordenar o wishlist en todo el marketplace.

---

## 3. Resultados de Verificación
- **TypeScript (`npm run types`):** 0 errores.
- **Frontend Tests (`npm run test:unit`):** 14/14 tests pasando al 100%.
- **Vite Production Build (`npm run build`):** Compilación exitosa de todos los chunks y assets.
