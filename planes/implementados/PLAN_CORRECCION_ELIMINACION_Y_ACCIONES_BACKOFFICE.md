# 📋 Plan: Corrección de Eliminación y Acciones en Vistas de Backoffice

## 1. 🔍 Diagnóstico y Causa Raíz

En los servicios de frontend (`resources/js/Services/*`), los métodos `delete`, `updateStock`, `toggleVisibility`, etc., retornan directamente `response.data` (la carga JSON devuelta por el helper `ApiResponse::success`):
```typescript
{
  status: "success",
  code: 200,
  message: "Marca eliminada exitosamente",
  data: null
}
```

### El Problema:
En varias páginas de React en `resources/js/pages/tenant/modules/`:
```typescript
const res = await BrandServices.delete(brandToDelete.id);
if (res?.data?.code === 200) { // ❌ BUG: res.data es null, por lo que res.data.code es undefined
    setDeleteModalOpen(false);
    setBrandToDelete(null);
    fetchBrands(currentPage);
}
```
Al evaluar `res?.data?.code === 200`, la condición resulta `false`. La eliminación en backend se ejecuta correctamente, pero en frontend el modal no se cierra, el estado no se limpia y la tabla nunca se recarga.

### Módulos y Archivos Afectados:
1. **Marcas**: [BrandIndexPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/brand/BrandIndexPage.tsx) (`confirmDelete`)
2. **Cupones**: [CouponIndexPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/coupon/CouponIndexPage.tsx) (`confirmDelete`)
3. **Productos**: [ProductIndexPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/product/ProductIndexPage.tsx) (`handleToggleVisibility`, `confirmDelete`, `handleSaveStock`)
4. **Impuestos**: [TaxIndexPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/tax/TaxIndexPage.tsx) (`confirmDelete`)
5. **Envíos**: [ShippingIndexPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/shipping/ShippingIndexPage.tsx) (`confirmDelete`)
6. **Formulario de Producto**: [FormProductPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/product/FormProductPage.tsx) (`handleSubmit`)
7. **Facturación**: [BillingIndexPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/billing/BillingIndexPage.tsx), [BillingSettingsPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/billing/BillingSettingsPage.tsx), [ShowInvoiceDetailPage.tsx](file:///c:/laragon/www/owomarket/resources/js/pages/tenant/modules/billing/ShowInvoiceDetailPage.tsx)

---

## 2. 🛠️ Solución Propuesta

Normalizar la verificación en todas las páginas de backoffice con comprobación resiliente:
```typescript
if ((res as any)?.code === 200 || (res as any)?.code === 201 || (res as any)?.status === "success" || (res as any)?.data?.code === 200) {
    // Cerrar modal, limpiar estado y recargar tabla
}
```

---

## 3. 🧪 Plan de Verificación
1. Ejecutar `php artisan test` (365+ pruebas al 100%).
2. Ejecutar `npm run types` (0 errores de TypeScript).
3. Crear commit con Conventional Commits: `fix(backoffice): fix delete, stock update and visibility action response checks across all tenant modules` y `git push origin moduleProduct`.
