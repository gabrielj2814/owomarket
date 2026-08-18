import React, { useState } from 'react';
import StorefrontLayout from '@/components/layouts/StorefrontLayout';
import ProductCard from '@/components/ui/storefront/ProductCard';
import { StorefrontCatalogPageProps } from '@/types/models/Storefront';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Drawer,
    Label,
    Select,
    TextInput,
} from 'flowbite-react';
import {
    HiAdjustments,
    HiCheck,
    HiFilter,
    HiHome,
    HiOutlineSearch,
    HiOutlineShoppingBag,
    HiRefresh,
    HiX,
} from 'react-icons/hi';

export default function TenantCatalogPage({
    domain,
    store_settings,
    categories = [],
    brands = [],
    price_bounds,
    products,
    filters,
    auth_user = null,
}: StorefrontCatalogPageProps) {
    const [search, setSearch] = useState<string>(filters.search || '');
    const [minPrice, setMinPrice] = useState<string>(
        filters.min_price !== undefined && filters.min_price !== null ? String(filters.min_price) : ''
    );
    const [maxPrice, setMaxPrice] = useState<string>(
        filters.max_price !== undefined && filters.max_price !== null ? String(filters.max_price) : ''
    );
    const [isMobileFiltersOpen, setIsMobileFiltersOpen] = useState<boolean>(false);

    // Apply URL filter change
    const applyFilters = (newParams: Record<string, string | undefined>) => {
        const queryParams = new URLSearchParams(window.location.search);

        Object.entries(newParams).forEach(([key, val]) => {
            if (val !== undefined && val !== null && val !== '') {
                queryParams.set(key, val);
            } else {
                queryParams.delete(key);
            }
        });

        // Reset page on filter change
        if (!newParams.page) {
            queryParams.delete('page');
        }

        window.location.href = `/catalog?${queryParams.toString()}`;
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters({ search: search.trim() });
    };

    const handlePriceSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters({
            min_price: minPrice.trim() !== '' ? minPrice.trim() : undefined,
            max_price: maxPrice.trim() !== '' ? maxPrice.trim() : undefined,
        });
    };

    const clearAllFilters = () => {
        window.location.href = '/catalog';
    };

    // Calculate active filters count
    const hasActiveFilters =
        Boolean(filters.search) ||
        Boolean(filters.category) ||
        Boolean(filters.brand) ||
        Boolean(filters.min_price) ||
        Boolean(filters.max_price) ||
        Boolean(filters.filter);

    // Filter sidebar content (reused in desktop sidebar & mobile drawer)
    const renderFilterContent = () => (
        <div className="space-y-6">
            {/* Search Input */}
            <div className="space-y-2">
                <Label htmlFor="catalog_search_sidebar" className="text-xs font-bold uppercase tracking-wider text-gray-500">
                    Buscar en Catálogo
                </Label>
                <form onSubmit={handleSearchSubmit} className="flex gap-2">
                    <TextInput
                        id="catalog_search_sidebar"
                        icon={HiOutlineSearch}
                        placeholder="Nombre, marca..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="flex-1"
                    />
                    <Button color="blue" size="sm" type="submit">
                        <HiOutlineSearch className="w-4 h-4" />
                    </Button>
                </form>
            </div>

            {/* Quick Filters */}
            <div className="space-y-2">
                <span className="text-xs font-bold uppercase tracking-wider text-gray-500 block">
                    Filtros Rápidos
                </span>
                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() =>
                            applyFilters({
                                filter: filters.filter === 'on_sale' ? undefined : 'on_sale',
                            })
                        }
                        className={`text-xs px-3 py-1.5 rounded-full font-semibold border transition-all ${
                            filters.filter === 'on_sale'
                                ? 'bg-red-600 text-white border-red-600 shadow-sm'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-red-400'
                        }`}
                    >
                        🔥 En Oferta
                    </button>
                    <button
                        type="button"
                        onClick={() =>
                            applyFilters({
                                filter: filters.filter === 'in_stock' ? undefined : 'in_stock',
                            })
                        }
                        className={`text-xs px-3 py-1.5 rounded-full font-semibold border transition-all ${
                            filters.filter === 'in_stock'
                                ? 'bg-green-600 text-white border-green-600 shadow-sm'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-green-400'
                        }`}
                    >
                        ✅ Con Stock
                    </button>
                </div>
            </div>

            {/* Categories */}
            {categories.length > 0 && (
                <div className="space-y-2 pt-2 border-t dark:border-gray-800">
                    <div className="flex justify-between items-center">
                        <span className="text-xs font-bold uppercase tracking-wider text-gray-500">
                            Categorías
                        </span>
                        {filters.category && (
                            <button
                                type="button"
                                onClick={() => applyFilters({ category: undefined })}
                                className="text-[11px] text-blue-600 hover:underline"
                            >
                                Todas
                            </button>
                        )}
                    </div>
                    <div className="space-y-1 max-h-56 overflow-y-auto pr-1">
                        {categories.map((cat) => {
                            const isSelected = filters.category === cat.slug;
                            return (
                                <button
                                    key={cat.id}
                                    type="button"
                                    onClick={() =>
                                        applyFilters({ category: isSelected ? undefined : cat.slug })
                                    }
                                    className={`w-full flex items-center justify-between text-xs px-2.5 py-1.5 rounded-lg text-left transition-colors ${
                                        isSelected
                                            ? 'bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-bold'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                    }`}
                                >
                                    <span className="truncate">{cat.name}</span>
                                    {cat.products_count !== undefined && (
                                        <span className="text-[10px] text-gray-400 font-medium">
                                            {cat.products_count}
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Brands */}
            {brands.length > 0 && (
                <div className="space-y-2 pt-2 border-t dark:border-gray-800">
                    <div className="flex justify-between items-center">
                        <span className="text-xs font-bold uppercase tracking-wider text-gray-500">
                            Marcas
                        </span>
                        {filters.brand && (
                            <button
                                type="button"
                                onClick={() => applyFilters({ brand: undefined })}
                                className="text-[11px] text-blue-600 hover:underline"
                            >
                                Todas
                            </button>
                        )}
                    </div>
                    <div className="space-y-1 max-h-48 overflow-y-auto pr-1">
                        {brands.map((brand) => {
                            const isSelected = filters.brand === brand.slug;
                            return (
                                <button
                                    key={brand.id}
                                    type="button"
                                    onClick={() =>
                                        applyFilters({ brand: isSelected ? undefined : brand.slug })
                                    }
                                    className={`w-full flex items-center justify-between text-xs px-2.5 py-1.5 rounded-lg text-left transition-colors ${
                                        isSelected
                                            ? 'bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 font-bold'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                    }`}
                                >
                                    <span className="truncate">{brand.name}</span>
                                    {brand.products_count !== undefined && (
                                        <span className="text-[10px] text-gray-400 font-medium">
                                            {brand.products_count}
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Price Range */}
            <div className="space-y-2 pt-2 border-t dark:border-gray-800">
                <span className="text-xs font-bold uppercase tracking-wider text-gray-500 block">
                    Rango de Precio
                </span>
                <form onSubmit={handlePriceSubmit} className="space-y-2">
                    <div className="grid grid-cols-2 gap-2">
                        <TextInput
                            type="number"
                            placeholder="Mín"
                            value={minPrice}
                            onChange={(e) => setMinPrice(e.target.value)}
                            sizing="sm"
                        />
                        <TextInput
                            type="number"
                            placeholder="Máx"
                            value={maxPrice}
                            onChange={(e) => setMaxPrice(e.target.value)}
                            sizing="sm"
                        />
                    </div>
                    <Button color="light" size="xs" type="submit" className="w-full">
                        Aplicar Rango
                    </Button>
                </form>
            </div>

            {/* Reset All */}
            {hasActiveFilters && (
                <Button
                    color="failure"
                    size="sm"
                    outline
                    onClick={clearAllFilters}
                    className="w-full"
                >
                    <HiRefresh className="mr-2 h-4 w-4" />
                    Limpiar Todos los Filtros
                </Button>
            )}
        </div>
    );

    return (
        <StorefrontLayout
            domain={domain}
            title="Catálogo de Productos"
            storeSettings={store_settings}
            categories={categories}
            authUser={auth_user}
        >
            <div className="space-y-6">
                {/* Breadcrumb */}
                <Breadcrumb>
                    <BreadcrumbItem href="/" icon={HiHome}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem>Catálogo</BreadcrumbItem>
                    {filters.category && (
                        <BreadcrumbItem>
                            {categories.find((c) => c.slug === filters.category)?.name || filters.category}
                        </BreadcrumbItem>
                    )}
                </Breadcrumb>

                {/* Header & Applied Filter Badges */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b dark:border-gray-800 pb-4">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">
                            Catálogo de Productos
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            Mostrando {products.data.length} de {products.total} productos encontrados
                        </p>
                    </div>

                    <div className="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                        {/* Mobile Filter Button */}
                        <Button
                            color="gray"
                            size="sm"
                            className="lg:hidden"
                            onClick={() => setIsMobileFiltersOpen(true)}
                        >
                            <HiFilter className="mr-1.5 h-4 w-4" />
                            Filtros {hasActiveFilters && '(Activos)'}
                        </Button>

                        {/* Sort Selector */}
                        <div className="flex items-center gap-2">
                            <span className="text-xs text-gray-500 font-semibold hidden sm:inline">
                                Ordenar:
                            </span>
                            <Select
                                value={filters.sort || 'latest'}
                                onChange={(e) => applyFilters({ sort: e.target.value })}
                                sizing="sm"
                                className="w-44"
                            >
                                <option value="latest">Más recientes</option>
                                <option value="price_asc">Menor precio</option>
                                <option value="price_desc">Mayor precio</option>
                                <option value="name_asc">Nombre (A - Z)</option>
                            </Select>
                        </div>
                    </div>
                </div>

                {/* Active Filter Badges */}
                {hasActiveFilters && (
                    <div className="flex flex-wrap items-center gap-2 pt-1">
                        <span className="text-xs font-semibold text-gray-500">Filtros aplicados:</span>
                        {filters.search && (
                            <Badge color="info">
                                Búsqueda: "{filters.search}"
                                <button
                                    type="button"
                                    onClick={() => applyFilters({ search: undefined })}
                                    className="ml-1 text-blue-800"
                                >
                                    ×
                                </button>
                            </Badge>
                        )}
                        {filters.category && (
                            <Badge color="indigo">
                                Categoría: {categories.find((c) => c.slug === filters.category)?.name || filters.category}
                                <button
                                    type="button"
                                    onClick={() => applyFilters({ category: undefined })}
                                    className="ml-1 text-indigo-800"
                                >
                                    ×
                                </button>
                            </Badge>
                        )}
                        {filters.brand && (
                            <Badge color="purple">
                                Marca: {brands.find((b) => b.slug === filters.brand)?.name || filters.brand}
                                <button
                                    type="button"
                                    onClick={() => applyFilters({ brand: undefined })}
                                    className="ml-1 text-purple-800"
                                >
                                    ×
                                </button>
                            </Badge>
                        )}
                        {filters.filter === 'on_sale' && (
                            <Badge color="failure">
                                En Oferta
                                <button
                                    type="button"
                                    onClick={() => applyFilters({ filter: undefined })}
                                    className="ml-1 text-red-800"
                                >
                                    ×
                                </button>
                            </Badge>
                        )}
                        {filters.filter === 'in_stock' && (
                            <Badge color="success">
                                Con Stock
                                <button
                                    type="button"
                                    onClick={() => applyFilters({ filter: undefined })}
                                    className="ml-1 text-green-800"
                                >
                                    ×
                                </button>
                            </Badge>
                        )}
                        <button
                            type="button"
                            onClick={clearAllFilters}
                            className="text-xs text-blue-600 dark:text-blue-400 hover:underline font-semibold ml-2"
                        >
                            Limpiar todo
                        </button>
                    </div>
                )}

                {/* Main 2-Column Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
                    {/* Desktop Sidebar (Left Column) */}
                    <div className="hidden lg:block lg:col-span-1 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-2xl p-5 shadow-sm sticky top-28">
                        {renderFilterContent()}
                    </div>

                    {/* Mobile Filter Drawer */}
                    {isMobileFiltersOpen && (
                        <div className="fixed inset-0 z-50 overflow-hidden lg:hidden">
                            <div
                                className="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"
                                onClick={() => setIsMobileFiltersOpen(false)}
                            />
                            <div className="fixed inset-y-0 left-0 max-w-full flex pr-10">
                                <div className="w-screen max-w-xs bg-white dark:bg-gray-900 shadow-xl p-5 overflow-y-auto">
                                    <div className="flex justify-between items-center mb-4 pb-2 border-b dark:border-gray-800">
                                        <h3 className="font-bold text-base text-gray-900 dark:text-white">
                                            Filtros
                                        </h3>
                                        <button
                                            type="button"
                                            onClick={() => setIsMobileFiltersOpen(false)}
                                            className="text-gray-400 p-1"
                                        >
                                            <HiX className="w-5 h-5" />
                                        </button>
                                    </div>
                                    {renderFilterContent()}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Product Grid (Right Column) */}
                    <div className="lg:col-span-3 space-y-8">
                        {products.data.length > 0 ? (
                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                                {products.data.map((product) => (
                                    <ProductCard key={product.id} product={product} />
                                ))}
                            </div>
                        ) : (
                            <div className="py-16 px-4 text-center bg-white dark:bg-gray-900 rounded-3xl border border-dashed dark:border-gray-800 space-y-4">
                                <div className="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 text-blue-500 rounded-full flex items-center justify-center mx-auto">
                                    <HiOutlineShoppingBag className="w-8 h-8" />
                                </div>
                                <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                    No se encontraron productos
                                </h3>
                                <p className="text-sm text-gray-500 max-w-md mx-auto">
                                    Intenta ajustar los términos de búsqueda o limpiar los filtros seleccionados para ver más resultados.
                                </p>
                                <Button
                                    color="blue"
                                    size="sm"
                                    onClick={clearAllFilters}
                                    className="mx-auto"
                                >
                                    Ver Todos los Productos
                                </Button>
                            </div>
                        )}

                        {/* Pagination Links */}
                        {products.last_page > 1 && (
                            <div className="flex justify-center items-center gap-1 pt-6 border-t dark:border-gray-800">
                                {products.links.map((link, index) => {
                                    if (!link.url) {
                                        return (
                                            <span
                                                key={index}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className="px-3 py-1.5 text-xs text-gray-400 border rounded-lg cursor-not-allowed"
                                            />
                                        );
                                    }
                                    return (
                                        <a
                                            key={index}
                                            href={link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1.5 text-xs rounded-lg font-semibold border transition-all ${
                                                link.active
                                                    ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                                                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                            }`}
                                        />
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </StorefrontLayout>
    );
}
