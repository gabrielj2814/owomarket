import Dashboard from '@/components/layouts/Dashboard';
import CategoryServices from '@/Services/CategoryServices';
import { FormCategory } from '@/types/FormCategory';
import { Category } from '@/types/models/Category';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
    Modal,
    ModalBody,
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
    Textarea,
    TextInput,
    ToggleSwitch,
} from 'flowbite-react';
import { FC, useEffect, useState } from 'react';
import { HiHome, HiPlus, HiRefresh, HiSearch, HiTrash } from 'react-icons/hi';
import { LuFolderTree, LuLayers } from 'react-icons/lu';

interface CategoryIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const initialFormCategory: FormCategory = {
    name: '',
    slug: '',
    description: '',
    image: '',
    parent_id: null,
    is_active: true,
    position: 0,
};

const CategoryIndexPage: FC<CategoryIndexPageProps> = ({ user_id, title, host, user_name }) => {
    const [categories, setCategories] = useState<Category[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [search, setSearch] = useState<string>('');
    const [isActiveFilter, setIsActiveFilter] = useState<string>('');
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(10);

    // Create Modal State
    const [createModalOpen, setCreateModalOpen] = useState<boolean>(false);
    const [formData, setFormData] = useState<FormCategory>(initialFormCategory);

    // Delete Modal State
    const [deleteModalOpen, setDeleteModalOpen] = useState<boolean>(false);
    const [categoryToDelete, setCategoryToDelete] = useState<Category | null>(null);
    const [actionLoading, setActionLoading] = useState<boolean>(false);

    // Sync State
    const [syncLoading, setSyncLoading] = useState<boolean>(false);
    const [syncMessage, setSyncMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const fetchCategories = async (page = currentPage) => {
        setLoading(true);
        try {
            const res = await CategoryServices.filtrar(
                search.trim() !== '' ? search.trim() : null,
                isActiveFilter !== '' ? isActiveFilter === 'true' : null,
                null,
                null,
                null,
                perPage,
                page,
            );
            const items = Array.isArray(res?.data) ? res.data : Array.isArray(res?.data?.data) ? res.data.data : [];
            setCategories(items);

            const pagination = (res as any)?.pagination || (res as any)?.data?.pagination;
            if (pagination) {
                setCurrentPage(pagination.current_page);
                setTotalPages(pagination.last_page);
                setTotalItems(pagination.total);
            }
        } catch (err) {
            console.error('Error al cargar categorías', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCategories(1);
    }, [isActiveFilter, perPage]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchCategories(1);
    };

    const handleSyncCentral = async () => {
        setSyncLoading(true);
        setSyncMessage(null);
        try {
            const res = await CategoryServices.syncCentral();
            if (res?.code === 200 || res?.status === 'success') {
                setSyncMessage({
                    type: 'success',
                    text: res.message || 'Categorías sincronizadas exitosamente desde el Catálogo Central',
                });
                fetchCategories(1);
            } else {
                setSyncMessage({
                    type: 'error',
                    text: res?.message || 'No se pudieron sincronizar las categorías',
                });
            }
        } catch (err) {
            setSyncMessage({
                type: 'error',
                text: 'Error de conexión al sincronizar categorías maestras',
            });
        } finally {
            setSyncLoading(false);
            setTimeout(() => setSyncMessage(null), 6000);
        }
    };

    const handleOpenCreate = () => {
        setFormData(initialFormCategory);
        setCreateModalOpen(true);
    };

    const handleCreateSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading(true);
        try {
            const payload: FormCategory = {
                ...formData,
                name: formData.name.trim(),
                slug: formData.slug?.trim() || undefined,
                position: Number(formData.position || 0),
            };

            const res = await CategoryServices.create(payload);
            if (res?.status === 201 || res?.status === 200 || (res as any)?.code === 201 || (res as any)?.code === 200) {
                setCreateModalOpen(false);
                fetchCategories(1);
            }
        } catch (err) {
            console.error('Error creando categoría', err);
        } finally {
            setActionLoading(false);
        }
    };

    const confirmDelete = async () => {
        if (!categoryToDelete) return;
        setActionLoading(true);
        try {
            const res = await CategoryServices.delete(categoryToDelete.id);
            if ((res as any)?.code === 200 || res?.status === 200) {
                setDeleteModalOpen(false);
                setCategoryToDelete(null);
                fetchCategories(currentPage);
            }
        } catch (err) {
            console.error('Error eliminando categoría', err);
        } finally {
            setActionLoading(false);
        }
    };

    return (
        <>
            <Head>
                <title>{title}</title>
            </Head>
            <Dashboard user_uuid={user_id}>
                <Breadcrumb
                    aria-label="Navegación"
                    className="mb-5 hidden rounded-lg border border-gray-100 bg-gray-50 px-5 py-3 lg:block dark:border-gray-700 dark:bg-gray-800"
                >
                    <BreadcrumbItem icon={HiHome} href={`/tenant/backoffice/${user_id}/dashboard`}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem href={`/product/backoffice/${user_id}/module`}>Catálogo</BreadcrumbItem>
                    <BreadcrumbItem>Categorías</BreadcrumbItem>
                </Breadcrumb>

                {syncMessage && (
                    <div
                        className={`mb-5 flex items-center justify-between rounded-lg p-4 text-sm font-medium ${
                            syncMessage.type === 'success'
                                ? 'border border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-gray-800 dark:text-green-400'
                                : 'border border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-gray-800 dark:text-red-400'
                        }`}
                    >
                        <span>{syncMessage.text}</span>
                        <button onClick={() => setSyncMessage(null)} className="ml-3 font-bold text-gray-400 hover:text-gray-600">
                            ✕
                        </button>
                    </div>
                )}

                <div className="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                            <LuFolderTree className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                            Categorías del Catálogo
                        </h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Organiza tus productos en categorías jerárquicas y menús de navegación
                        </p>
                    </div>
                    <div className="flex w-full flex-wrap items-center gap-3 sm:w-auto">
                        <Button color="gray" onClick={() => fetchCategories(currentPage)} disabled={loading || syncLoading}>
                            <HiRefresh className={`mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                        <Button color="blue" onClick={handleSyncCentral} disabled={loading || syncLoading}>
                            <HiRefresh className={`mr-2 h-4 w-4 ${syncLoading ? 'animate-spin' : ''}`} />
                            {syncLoading ? 'Sincronizando...' : 'Sincronizar Catálogo Central'}
                        </Button>
                        <Button color="purple" onClick={handleOpenCreate}>
                            <HiPlus className="mr-2 h-4 w-4" />
                            Crear Categoría
                        </Button>
                    </div>
                </div>

                <Card className="mb-6 border-gray-100 shadow-sm dark:border-gray-700">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 items-end gap-3 sm:grid-cols-3">
                        <div className="sm:col-span-2">
                            <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Buscar por Nombre o Slug</label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="Ej: Electrónica, Ropa..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Estado</label>
                            <Select value={isActiveFilter} onChange={(e) => setIsActiveFilter(e.target.value)}>
                                <option value="">Todos los Estados</option>
                                <option value="true">Activas</option>
                                <option value="false">Inactivas</option>
                            </Select>
                        </div>
                    </form>
                </Card>

                <Card className="overflow-hidden border-gray-100 shadow-sm dark:border-gray-700">
                    <div className="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                        <Table hoverable>
                            <TableHead className="bg-gray-50 text-xs tracking-wider uppercase dark:bg-gray-700">
                                <TableHeadCell>Categoría</TableHeadCell>
                                <TableHeadCell>Slug</TableHeadCell>
                                <TableHeadCell>Posición</TableHeadCell>
                                <TableHeadCell>Estado</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-100 dark:divide-gray-700">
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-12 text-center">
                                            <Spinner size="xl" />
                                        </TableCell>
                                    </TableRow>
                                ) : categories.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-12 text-center">
                                            <LuLayers className="mx-auto mb-2 h-12 w-12 text-gray-300 dark:text-gray-600" />
                                            <p className="text-base font-semibold text-gray-700 dark:text-gray-300">No se encontraron categorías</p>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    categories.map((c) => (
                                        <TableRow key={c.id} className="bg-white dark:bg-gray-800">
                                            <TableCell className="font-semibold text-gray-900 dark:text-white">
                                                <div className="flex items-center gap-3">
                                                    {c.image ? (
                                                        <img src={c.image} alt={c.name} className="h-9 w-9 rounded-lg object-cover" />
                                                    ) : (
                                                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-gray-700">
                                                            <LuFolderTree className="h-5 w-5" />
                                                        </div>
                                                    )}
                                                    <span>{c.name}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-mono text-xs text-gray-500">/{c.slug}</TableCell>
                                            <TableCell>
                                                <Badge color="gray" size="sm">
                                                    {c.position}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge color={c.is_active ? 'success' : 'failure'} size="sm">
                                                    {c.is_active ? 'Activa' : 'Inactiva'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <button
                                                    onClick={() => {
                                                        setCategoryToDelete(c);
                                                        setDeleteModalOpen(true);
                                                    }}
                                                    className="rounded-lg p-1.5 text-gray-500 hover:text-red-600"
                                                    title="Eliminar"
                                                >
                                                    <HiTrash className="h-4 w-4" />
                                                </button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {totalPages > 1 && (
                        <div className="mt-6 flex justify-center">
                            <Pagination
                                currentPage={currentPage}
                                totalPages={totalPages}
                                onPageChange={(p) => {
                                    setCurrentPage(p);
                                    fetchCategories(p);
                                }}
                                showIcons
                            />
                        </div>
                    )}
                </Card>

                {/* Create Category Modal */}
                <Modal show={createModalOpen} onClose={() => setCreateModalOpen(false)} size="md">
                    <ModalHeader>
                        <span className="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                            <LuFolderTree className="h-6 w-6 text-blue-600" />
                            Crear Nueva Categoría
                        </span>
                    </ModalHeader>
                    <ModalBody>
                        <form onSubmit={handleCreateSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="cat-name">Nombre de la Categoría *</Label>
                                <TextInput
                                    id="cat-name"
                                    placeholder="Ej: Smartphones, Zapatillas..."
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    required
                                />
                            </div>

                            <div>
                                <Label htmlFor="cat-slug">Slug (Opcional)</Label>
                                <TextInput
                                    id="cat-slug"
                                    placeholder="Dejar vacío para autogenerar"
                                    value={formData.slug ?? ''}
                                    onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                                />
                            </div>

                            <div>
                                <Label htmlFor="cat-image">URL de Imagen / Icono (Opcional)</Label>
                                <TextInput
                                    id="cat-image"
                                    placeholder="https://..."
                                    value={formData.image ?? ''}
                                    onChange={(e) => setFormData({ ...formData, image: e.target.value })}
                                />
                            </div>

                            <div>
                                <Label htmlFor="cat-desc">Descripción (Opcional)</Label>
                                <Textarea
                                    id="cat-desc"
                                    rows={2}
                                    placeholder="Descripción de la categoría..."
                                    value={formData.description ?? ''}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                />
                            </div>

                            <div>
                                <Label htmlFor="cat-pos">Posición / Orden</Label>
                                <TextInput
                                    id="cat-pos"
                                    type="number"
                                    min="0"
                                    value={formData.position ?? 0}
                                    onChange={(e) => setFormData({ ...formData, position: Number(e.target.value) })}
                                />
                            </div>

                            <div className="pt-2">
                                <ToggleSwitch
                                    checked={formData.is_active ?? true}
                                    label="Categoría Activa en el Menú"
                                    onChange={(checked) => setFormData({ ...formData, is_active: checked })}
                                />
                            </div>

                            <div className="flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                                <Button color="gray" onClick={() => setCreateModalOpen(false)}>
                                    Cancelar
                                </Button>
                                <Button color="purple" type="submit" disabled={actionLoading}>
                                    {actionLoading ? <Spinner size="sm" className="mr-2" /> : null}
                                    Guardar Categoría
                                </Button>
                            </div>
                        </form>
                    </ModalBody>
                </Modal>

                {/* Delete Modal */}
                <Modal show={deleteModalOpen} onClose={() => setDeleteModalOpen(false)} size="md" popup>
                    <ModalHeader />
                    <ModalBody>
                        <div className="text-center">
                            <HiTrash className="mx-auto mb-4 h-14 w-14 text-red-500" />
                            <h3 className="mb-2 text-lg font-bold text-gray-900 dark:text-white">¿Eliminar Categoría?</h3>
                            <p className="mb-5 text-sm text-gray-500">
                                ¿Deseas eliminar la categoría <strong>"{categoryToDelete?.name}"</strong>?
                            </p>
                            <div className="flex justify-center gap-3">
                                <Button color="gray" onClick={() => setDeleteModalOpen(false)}>
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
            </Dashboard>
        </>
    );
};

export default CategoryIndexPage;
