import Dashboard from '@/components/layouts/Dashboard';
import BrandServices from '@/Services/BrandServices';
import CategoryServices from '@/Services/CategoryServices';
import ProductServices, { ProductFilterParams } from '@/Services/ProductServices';
import { Brand } from '@/types/models/Brand';
import { Category } from '@/types/models/Category';
import { Product } from '@/types/models/Product';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Pagination,
    Select,
    Spinner,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    TextInput,
    ToggleSwitch,
} from 'flowbite-react';
import { FC, useEffect, useState } from 'react';
import { HiEye, HiHome, HiPencil, HiPlus, HiRefresh, HiSearch, HiTrash } from 'react-icons/hi';
import { LuBoxes, LuCheck, LuLayers, LuPackage, LuTag } from 'react-icons/lu';

interface ProductIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const ProductIndexPage: FC<ProductIndexPageProps> = ({ user_id, title, host, user_name }) => {
    // Data states
    const [products, setProducts] = useState<Product[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [brands, setBrands] = useState<Brand[]>([]);
    const [loading, setLoading] = useState<boolean>(true);

    // Filter states
    const [search, setSearch] = useState<string>('');
    const [categoryId, setCategoryId] = useState<string>('');
    const [brandId, setBrandId] = useState<string>('');
    const [isVisibleFilter, setIsVisibleFilter] = useState<string>('');
    const [inStockFilter, setInStockFilter] = useState<string>('');

    // Pagination states
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(10);

    // Modal states
    const [deleteModalOpen, setDeleteModalOpen] = useState<boolean>(false);
    const [productToDelete, setProductToDelete] = useState<Product | null>(null);
    const [actionLoading, setActionLoading] = useState<boolean>(false);

    const [stockModalOpen, setStockModalOpen] = useState<boolean>(false);
    const [productToStock, setProductToStock] = useState<Product | null>(null);
    const [newStockQty, setNewStockQty] = useState<number>(0);

    const [previewModalOpen, setPreviewModalOpen] = useState<boolean>(false);
    const [productToPreview, setProductToPreview] = useState<Product | null>(null);

    // Load Categories and Brands on mount
    useEffect(() => {
        const loadDependencies = async () => {
            try {
                const [catsRes, brandsRes] = await Promise.all([CategoryServices.tree(), BrandServices.listActive()]);
                const catsData = Array.isArray(catsRes.data) ? catsRes.data : [];
                if (catsData.length > 0) {
                    setCategories(catsData);
                }
                const brandsData = Array.isArray(brandsRes.data) ? brandsRes.data : [];
                if (brandsData.length > 0) {
                    setBrands(brandsData);
                }
            } catch (err) {
                console.error('Error cargando dependencias', err);
            }
        };

        loadDependencies();
    }, []);

    // Load Products
    const fetchProducts = async (page = currentPage) => {
        setLoading(true);
        try {
            const params: ProductFilterParams = {
                search: search.trim() !== '' ? search.trim() : null,
                category_id: categoryId ? parseInt(categoryId, 10) : null,
                brand_id: brandId ? parseInt(brandId, 10) : null,
                is_visible: isVisibleFilter !== '' ? isVisibleFilter === 'true' : null,
                in_stock: inStockFilter !== '' ? inStockFilter === 'true' : null,
                page,
                per_page: perPage,
                sort_by: 'created_at',
                sort_direction: 'desc',
            };

            const response = await ProductServices.filtrar(params);
            const items = Array.isArray(response.data) ? response.data : [];
            setProducts(items);

            const pagination = (response as any)?.pagination || (response as any)?.data?.pagination;
            if (pagination) {
                setCurrentPage(pagination.current_page);
                setTotalPages(pagination.last_page);
                setTotalItems(pagination.total);
            }
        } catch (error) {
            console.error('Error al filtrar productos', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchProducts(1);
    }, [categoryId, brandId, isVisibleFilter, inStockFilter, perPage]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchProducts(1);
    };

    const handlePageChange = (page: number) => {
        setCurrentPage(page);
        fetchProducts(page);
    };

    const handleToggleVisibility = async (product: Product) => {
        try {
            const res = await ProductServices.toggleVisibility(product.id, !product.is_visible);
            if (res.code === 200 || res.status === 'success') {
                setProducts((prev) => prev.map((p) => (p.id === product.id ? { ...p, is_visible: !p.is_visible } : p)));
            }
        } catch (err) {
            console.error('Error cambiando visibilidad', err);
        }
    };

    const handleOpenDelete = (product: Product) => {
        setProductToDelete(product);
        setDeleteModalOpen(true);
    };

    const confirmDelete = async () => {
        if (!productToDelete) return;
        setActionLoading(true);
        try {
            const res = await ProductServices.delete(productToDelete.id);
            if (res.code === 200 || res.status === 'success') {
                setDeleteModalOpen(false);
                setProductToDelete(null);
                fetchProducts(currentPage);
            }
        } catch (err) {
            console.error('Error eliminando producto', err);
        } finally {
            setActionLoading(false);
        }
    };

    const handleOpenStock = (product: Product) => {
        setProductToStock(product);
        setNewStockQty(product.quantity);
        setStockModalOpen(true);
    };

    const confirmStockUpdate = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!productToStock) return;
        setActionLoading(true);
        try {
            const res = await ProductServices.updateStock(productToStock.id, newStockQty);
            if (res.code === 200 || res.status === 'success') {
                setStockModalOpen(false);
                setProductToStock(null);
                setProducts((prev) =>
                    prev.map((p) => (p.id === productToStock.id ? { ...p, quantity: newStockQty, is_in_stock: newStockQty > 0 } : p)),
                );
            }
        } catch (err) {
            console.error('Error actualizando stock', err);
        } finally {
            setActionLoading(false);
        }
    };

    const handleOpenPreview = (product: Product) => {
        setProductToPreview(product);
        setPreviewModalOpen(true);
    };

    const irAFormularioCrear = () => {
        window.location.href = `/product/backoffice/${user_id}/module/record`;
    };

    const irAFormularioEditar = (productId: string) => {
        window.location.href = `/product/backoffice/${user_id}/module/record/${productId}`;
    };

    return (
        <>
            <Head>
                <title>{title}</title>
            </Head>
            <Dashboard user_uuid={user_id}>
                {/* Breadcrumb */}
                <Breadcrumb
                    aria-label="Navegación del catálogo"
                    className="mb-5 hidden rounded-lg border border-gray-100 bg-gray-50 px-5 py-3 lg:block dark:border-gray-700 dark:bg-gray-800"
                >
                    <BreadcrumbItem icon={HiHome} href={`/tenant/backoffice/${user_id}/dashboard`}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem href={`/product/backoffice/${user_id}/module`}>Catálogo</BreadcrumbItem>
                    <BreadcrumbItem>Productos</BreadcrumbItem>
                </Breadcrumb>

                {/* Header Title and Actions */}
                <div className="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                            <LuPackage className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                            Catálogo de Productos
                        </h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Administra el inventario, precios, variantes e imágenes de tu tienda
                        </p>
                    </div>
                    <div className="flex w-full items-center gap-3 sm:w-auto">
                        <Button color="gray" onClick={() => fetchProducts(currentPage)} disabled={loading} className="shadow-sm">
                            <HiRefresh className={`mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                        <Button color="blue" onClick={irAFormularioCrear} className="shadow-md">
                            <HiPlus className="mr-1 h-5 w-5" />
                            Nuevo Producto
                        </Button>
                    </div>
                </div>

                {/* Filters Section */}
                <Card className="mb-6 border-gray-100 shadow-sm dark:border-gray-700">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                        <div className="lg:col-span-2">
                            <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Buscar por Nombre, SKU o Slug</label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="Ej: Laptop, AUD-001..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Categoría</label>
                            <Select value={categoryId} onChange={(e) => setCategoryId(e.target.value)}>
                                <option value="">Todas las Categorías</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Marca</label>
                            <Select value={brandId} onChange={(e) => setBrandId(e.target.value)}>
                                <option value="">Todas las Marcas</option>
                                {brands.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        {b.name}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Estado</label>
                            <Select value={isVisibleFilter} onChange={(e) => setIsVisibleFilter(e.target.value)}>
                                <option value="">Todos los Estados</option>
                                <option value="true">Activos / Visibles</option>
                                <option value="false">Ocultos / Inactivos</option>
                            </Select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Inventario</label>
                            <Select value={inStockFilter} onChange={(e) => setInStockFilter(e.target.value)}>
                                <option value="">Todo el Stock</option>
                                <option value="true">En Stock</option>
                                <option value="false">Agotado</option>
                            </Select>
                        </div>
                    </form>
                </Card>

                {/* Products Table Card */}
                <Card className="overflow-hidden border-gray-100 shadow-sm dark:border-gray-700">
                    <div className="mb-4 flex items-center justify-between">
                        <div className="text-sm text-gray-500 dark:text-gray-400">
                            Mostrando <span className="font-bold text-gray-900 dark:text-white">{products.length}</span> de{' '}
                            <span className="font-bold text-gray-900 dark:text-white">{totalItems}</span> productos registrados
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-xs text-gray-500 dark:text-gray-400">Filas:</span>
                            <Select value={perPage} onChange={(e) => setPerPage(parseInt(e.target.value, 10))} className="w-20" sizing="sm">
                                <option value={10}>10</option>
                                <option value={25}>25</option>
                                <option value={50}>50</option>
                            </Select>
                        </div>
                    </div>

                    <div className="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                        <Table hoverable>
                            <TableHead className="bg-gray-50 text-xs tracking-wider uppercase dark:bg-gray-700">
                                <TableHeadCell>Producto</TableHeadCell>
                                <TableHeadCell>SKU</TableHeadCell>
                                <TableHeadCell>Categoría / Marca</TableHeadCell>
                                <TableHeadCell>Precio</TableHeadCell>
                                <TableHeadCell>Inventario</TableHeadCell>
                                <TableHeadCell>Visibilidad</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-100 dark:divide-gray-700">
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="py-12 text-center">
                                            <Spinner size="xl" />
                                            <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">Cargando catálogo de productos...</p>
                                        </TableCell>
                                    </TableRow>
                                ) : products.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="py-12 text-center">
                                            <LuPackage className="mx-auto mb-2 h-12 w-12 text-gray-300 dark:text-gray-600" />
                                            <p className="text-base font-semibold text-gray-700 dark:text-gray-300">No se encontraron productos</p>
                                            <p className="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                                Intenta ajustar los filtros de búsqueda o registra un nuevo producto
                                            </p>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    products.map((p) => {
                                        const defaultImage = p.images && p.images.length > 0 ? p.images[0].image_path : null;

                                        return (
                                            <TableRow
                                                key={p.id}
                                                className="bg-white transition-colors hover:bg-gray-50/75 dark:bg-gray-800 dark:hover:bg-gray-700/50"
                                            >
                                                <TableCell className="font-medium text-gray-900 dark:text-white">
                                                    <div className="flex items-center gap-3">
                                                        {defaultImage ? (
                                                            <img
                                                                src={defaultImage}
                                                                alt={p.name}
                                                                className="h-11 w-11 shrink-0 rounded-lg border border-gray-200 object-cover dark:border-gray-700"
                                                            />
                                                        ) : (
                                                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                                                <LuPackage className="h-5 w-5" />
                                                            </div>
                                                        )}
                                                        <div>
                                                            <div className="line-clamp-1 text-sm font-semibold text-gray-900 dark:text-white">
                                                                {p.name}
                                                            </div>
                                                            <div className="mt-0.5 flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                                                <span>/{p.slug}</span>
                                                                {p.is_featured && (
                                                                    <Badge color="warning" size="xs">
                                                                        Destacado
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </TableCell>

                                                <TableCell className="font-mono text-xs text-gray-700 dark:text-gray-300">{p.sku}</TableCell>

                                                <TableCell>
                                                    <div className="text-xs">
                                                        <div className="flex items-center gap-1 font-medium text-gray-800 dark:text-gray-200">
                                                            <LuLayers className="h-3.5 w-3.5 text-blue-500" />
                                                            {p.category_name || 'Sin Categoría'}
                                                        </div>
                                                        <div className="mt-0.5 flex items-center gap-1 text-gray-400 dark:text-gray-500">
                                                            <LuTag className="h-3 w-3 text-purple-500" />
                                                            {p.brand_name || 'Sin Marca'}
                                                        </div>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <div className="text-sm font-bold text-gray-900 dark:text-white">${p.price.toFixed(2)}</div>
                                                    {p.compare_price && p.compare_price > p.price && (
                                                        <div className="text-xs text-red-400 line-through">${p.compare_price.toFixed(2)}</div>
                                                    )}
                                                </TableCell>

                                                <TableCell>
                                                    <button
                                                        onClick={() => handleOpenStock(p)}
                                                        className="group flex items-center gap-1.5 focus:outline-none"
                                                        title="Click para ajustar stock rápido"
                                                    >
                                                        <Badge
                                                            color={p.quantity > 5 ? 'success' : p.quantity > 0 ? 'warning' : 'failure'}
                                                            size="sm"
                                                            className="cursor-pointer transition-transform group-hover:scale-105"
                                                        >
                                                            <LuBoxes className="mr-1 inline h-3 w-3" />
                                                            {p.quantity} unid.
                                                        </Badge>
                                                    </button>
                                                </TableCell>

                                                <TableCell>
                                                    <ToggleSwitch
                                                        checked={p.is_visible}
                                                        onChange={() => handleToggleVisibility(p)}
                                                        className="scale-90"
                                                    />
                                                </TableCell>

                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1.5">
                                                        <button
                                                            onClick={() => handleOpenPreview(p)}
                                                            className="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-blue-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-blue-400"
                                                            title="Ver detalles rápidos"
                                                        >
                                                            <HiEye className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => irAFormularioEditar(p.id)}
                                                            className="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                                                            title="Editar producto"
                                                        >
                                                            <HiPencil className="h-4 w-4" />
                                                        </button>
                                                        <button
                                                            onClick={() => handleOpenDelete(p)}
                                                            className="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-red-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-red-400"
                                                            title="Eliminar producto"
                                                        >
                                                            <HiTrash className="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {totalPages > 1 && (
                        <div className="mt-6 flex justify-center">
                            <Pagination
                                currentPage={currentPage}
                                totalPages={totalPages}
                                onPageChange={handlePageChange}
                                showIcons
                                previousLabel="Anterior"
                                nextLabel="Siguiente"
                            />
                        </div>
                    )}
                </Card>

                {/* Modal: Confirm Delete */}
                <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} size="md" popup>
                    <ModalHeader />
                    <ModalBody>
                        <div className="text-center">
                            <HiTrash className="mx-auto mb-4 h-14 w-14 text-red-500 dark:text-red-400" />
                            <h3 className="mb-2 text-lg font-bold text-gray-900 dark:text-white">¿Eliminar Producto?</h3>
                            <p className="mb-5 text-sm text-gray-500 dark:text-gray-400">
                                ¿Estás seguro de eliminar el producto{' '}
                                <strong className="text-gray-900 dark:text-white">"{productToDelete?.name}"</strong>? Esta acción aplicará borrado
                                lógico y podrá recuperarse.
                            </p>
                            <div className="flex justify-center gap-3">
                                <Button color="gray" onClick={() => setDeleteModalOpen(false)} disabled={actionLoading}>
                                    Cancelar
                                </Button>
                                <Button color="failure" onClick={confirmDelete} disabled={actionLoading}>
                                    {actionLoading ? <Spinner size="sm" className="mr-2" /> : null}
                                    Sí, eliminar
                                </Button>
                            </div>
                        </div>
                    </ModalBody>
                </Modal>

                {/* Modal: Quick Stock Update */}
                <Modal show={stockModalOpen} onClose={() => setStockModalOpen(false)} size="md">
                    <ModalHeader>Ajuste Rápido de Inventario</ModalHeader>
                    <form onSubmit={confirmStockUpdate}>
                        <ModalBody>
                            <div className="space-y-4">
                                <div>
                                    <div className="text-sm font-semibold text-gray-900 dark:text-white">{productToStock?.name}</div>
                                    <div className="font-mono text-xs text-gray-500 dark:text-gray-400">SKU: {productToStock?.sku}</div>
                                </div>
                                <div>
                                    <label className="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Nueva Cantidad en Stock:
                                    </label>
                                    <TextInput
                                        type="number"
                                        min={0}
                                        value={newStockQty}
                                        onChange={(e) => setNewStockQty(parseInt(e.target.value, 10) || 0)}
                                        required
                                    />
                                </div>
                            </div>
                        </ModalBody>
                        <ModalFooter>
                            <Button type="submit" color="blue" disabled={actionLoading}>
                                {actionLoading ? <Spinner size="sm" className="mr-2" /> : <LuCheck className="mr-1 h-4 w-4" />}
                                Guardar Stock
                            </Button>
                            <Button color="gray" onClick={() => setStockModalOpen(false)}>
                                Cancelar
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>

                {/* Modal: Preview Product */}
                <Modal show={previewModalOpen} onClose={() => setPreviewModalOpen(false)} size="xl">
                    <ModalHeader>Detalles del Producto</ModalHeader>
                    <ModalBody>
                        {productToPreview && (
                            <div className="space-y-4">
                                <div className="flex flex-col items-start gap-4 sm:flex-row">
                                    {productToPreview.images && productToPreview.images.length > 0 ? (
                                        <img
                                            src={productToPreview.images[0].image_path}
                                            alt={productToPreview.name}
                                            className="h-32 w-32 shrink-0 rounded-xl border border-gray-200 object-cover dark:border-gray-700"
                                        />
                                    ) : (
                                        <div className="flex h-32 w-32 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-400 dark:bg-gray-700">
                                            <LuPackage className="h-10 w-10" />
                                        </div>
                                    )}
                                    <div className="flex-1 space-y-1">
                                        <h4 className="text-xl font-bold text-gray-900 dark:text-white">{productToPreview.name}</h4>
                                        <div className="flex flex-wrap gap-2 text-xs">
                                            <Badge color="info">SKU: {productToPreview.sku}</Badge>
                                            <Badge color="purple">Cat: {productToPreview.category_name || 'N/A'}</Badge>
                                            <Badge color="gray">Marca: {productToPreview.brand_name || 'N/A'}</Badge>
                                            <Badge color={productToPreview.is_visible ? 'success' : 'failure'}>
                                                {productToPreview.is_visible ? 'Visible' : 'Oculto'}
                                            </Badge>
                                        </div>
                                        <div className="pt-2 text-lg font-extrabold text-blue-600 dark:text-blue-400">
                                            ${productToPreview.price.toFixed(2)}
                                            {productToPreview.compare_price && (
                                                <span className="ml-2 text-xs text-gray-400 line-through">
                                                    ${productToPreview.compare_price.toFixed(2)}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {productToPreview.description && (
                                    <div>
                                        <h5 className="text-xs font-bold text-gray-600 uppercase dark:text-gray-300">Descripción:</h5>
                                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{productToPreview.description}</p>
                                    </div>
                                )}

                                {productToPreview.variants && productToPreview.variants.length > 0 && (
                                    <div>
                                        <h5 className="mb-2 text-xs font-bold text-gray-600 uppercase dark:text-gray-300">
                                            Variantes ({productToPreview.variants.length}):
                                        </h5>
                                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            {productToPreview.variants.map((v, i) => (
                                                <div
                                                    key={i}
                                                    className="rounded-lg border border-gray-100 bg-gray-50/50 p-2.5 text-xs dark:border-gray-700 dark:bg-gray-800"
                                                >
                                                    <div className="font-mono font-bold text-gray-900 dark:text-white">{v.sku}</div>
                                                    <div className="text-gray-500 dark:text-gray-400">
                                                        Precio: ${v.price.toFixed(2)} | Stock: {v.quantity} unid.
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </ModalBody>
                    <ModalFooter>
                        <Button color="gray" onClick={() => setPreviewModalOpen(false)}>
                            Cerrar
                        </Button>
                    </ModalFooter>
                </Modal>
            </Dashboard>
        </>
    );
};

export default ProductIndexPage;
