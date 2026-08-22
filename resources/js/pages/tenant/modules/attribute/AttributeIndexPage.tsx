import Dashboard from '@/components/layouts/Dashboard';
import AttributeServices from '@/Services/AttributeServices';
import { ErrorsFormAttribute } from '@/types/ErrorsFormAttribute';
import { FormAttribute, FormAttributeValue } from '@/types/FormAttribute';
import { ProductAttribute } from '@/types/models/ProductAttribute';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Checkbox,
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
    TextInput,
} from 'flowbite-react';
import { FC, useEffect, useState } from 'react';
import { HiAdjustments, HiHome, HiPlus, HiRefresh, HiSearch, HiTrash, HiX } from 'react-icons/hi';
import { LuLayers } from 'react-icons/lu';

interface AttributeIndexPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
}

const initialFormAttribute: FormAttribute = {
    name: '',
    slug: '',
    type: 'select',
    is_filterable: true,
    is_visible: true,
    position: 0,
    values: [],
};

const AttributeIndexPage: FC<AttributeIndexPageProps> = ({ user_id, title, host, user_name }) => {
    const [attributes, setAttributes] = useState<ProductAttribute[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [search, setSearch] = useState<string>('');
    const [currentPage, setCurrentPage] = useState<number>(1);
    const [totalPages, setTotalPages] = useState<number>(1);
    const [totalItems, setTotalItems] = useState<number>(0);
    const [perPage, setPerPage] = useState<number>(10);

    // Create Modal State
    const [createModalOpen, setCreateModalOpen] = useState<boolean>(false);
    const [formData, setFormData] = useState<FormAttribute>(initialFormAttribute);
    const [formErrors, setFormErrors] = useState<ErrorsFormAttribute>({});
    const [newValueText, setNewValueText] = useState<string>('');
    const [newValueColor, setNewValueColor] = useState<string>('#3b82f6');

    // Delete Modal State
    const [deleteModalOpen, setDeleteModalOpen] = useState<boolean>(false);
    const [attributeToDelete, setAttributeToDelete] = useState<ProductAttribute | null>(null);
    const [actionLoading, setActionLoading] = useState<boolean>(false);

    const fetchAttributes = async (page = currentPage) => {
        setLoading(true);
        try {
            const res = await AttributeServices.filtrar(search.trim() !== '' ? search.trim() : null, null, null, null, perPage, page);
            const items = Array.isArray(res.data) ? res.data : [];
            setAttributes(items);

            const pagination = res.pagination;
            if (pagination) {
                setCurrentPage(pagination.current_page);
                setTotalPages(pagination.last_page);
                setTotalItems(pagination.total);
            }
        } catch (err) {
            console.error('Error al cargar atributos', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchAttributes(1);
    }, [perPage]);

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        fetchAttributes(1);
    };

    const handleOpenCreate = () => {
        setFormData(initialFormAttribute);
        setFormErrors({});
        setNewValueText('');
        setNewValueColor('#3b82f6');
        setCreateModalOpen(true);
    };

    const handleNameChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const name = e.target.value;
        const slug = name
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');

        setFormData((prev) => ({
            ...prev,
            name,
            slug: prev.slug === '' || prev.slug === prev.name.toLowerCase() ? slug : prev.slug,
        }));
    };

    const handleAddValue = () => {
        if (!newValueText.trim()) return;
        const newVal: FormAttributeValue = {
            value: newValueText.trim(),
            color: formData.type === 'color' ? newValueColor : null,
            position: (formData.values || []).length + 1,
        };
        setFormData((prev) => ({
            ...prev,
            values: [...(prev.values || []), newVal],
        }));
        setNewValueText('');
    };

    const handleRemoveValue = (index: number) => {
        setFormData((prev) => ({
            ...prev,
            values: (prev.values || []).filter((_, i) => i !== index),
        }));
    };

    const handleCreateSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setActionLoading(true);
        setFormErrors({});

        try {
            const res = await AttributeServices.create(formData);
            if (res.code === 201 || res.code === 200 || res.status === 'success') {
                setCreateModalOpen(false);
                setFormData(initialFormAttribute);
                fetchAttributes(1);
            } else if (res.errors) {
                setFormErrors(res.errors);
            }
        } catch (err: any) {
            if (err.response?.data?.errors) {
                setFormErrors(err.response.data.errors);
            }
        } finally {
            setActionLoading(false);
        }
    };

    const confirmDelete = async () => {
        if (!attributeToDelete) return;
        setActionLoading(true);
        try {
            const res = await AttributeServices.delete(attributeToDelete.id);
            if (res.code === 200 || res.status === 'success') {
                setDeleteModalOpen(false);
                setAttributeToDelete(null);
                fetchAttributes(currentPage);
            }
        } catch (err) {
            console.error('Error eliminando atributo', err);
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
                    <BreadcrumbItem>Atributos</BreadcrumbItem>
                </Breadcrumb>

                <div className="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                            <HiAdjustments className="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                            Atributos y Variantes
                        </h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Define características como Color, Talla, Material o Capacidad
                        </p>
                    </div>
                    <div className="flex w-full items-center gap-3 sm:w-auto">
                        <Button color="gray" onClick={() => fetchAttributes(currentPage)} disabled={loading}>
                            <HiRefresh className={`mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                        <Button color="indigo" onClick={handleOpenCreate}>
                            <HiPlus className="mr-2 h-4 w-4" />
                            Crear Atributo
                        </Button>
                    </div>
                </div>

                <Card className="mb-6 border-gray-100 shadow-sm dark:border-gray-700">
                    <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2">
                        <div>
                            <label className="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Buscar por Nombre o Slug</label>
                            <TextInput
                                icon={HiSearch}
                                placeholder="Ej: Talla, Color, Memoria..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </form>
                </Card>

                <Card className="overflow-hidden border-gray-100 shadow-sm dark:border-gray-700">
                    <div className="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                        <Table hoverable>
                            <TableHead className="bg-gray-50 text-xs tracking-wider uppercase dark:bg-gray-700">
                                <TableHeadCell>Atributo</TableHeadCell>
                                <TableHeadCell>Slug</TableHeadCell>
                                <TableHeadCell>Tipo</TableHeadCell>
                                <TableHeadCell>Valores Configurados</TableHeadCell>
                                <TableHeadCell className="text-right">Acciones</TableHeadCell>
                            </TableHead>
                            <TableBody className="divide-y divide-gray-100 dark:divide-gray-700">
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-12 text-center">
                                            <Spinner size="xl" />
                                        </TableCell>
                                    </TableRow>
                                ) : attributes.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-12 text-center">
                                            <LuLayers className="mx-auto mb-2 h-12 w-12 text-gray-300 dark:text-gray-600" />
                                            <p className="text-base font-semibold text-gray-700 dark:text-gray-300">No se encontraron atributos</p>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    attributes.map((a) => (
                                        <TableRow key={a.id} className="bg-white dark:bg-gray-800">
                                            <TableCell className="font-semibold text-gray-900 dark:text-white">{a.name}</TableCell>
                                            <TableCell className="font-mono text-xs text-gray-500">/{a.slug}</TableCell>
                                            <TableCell>
                                                <Badge color="info" size="sm">
                                                    {a.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {a.values && a.values.length > 0 ? (
                                                        a.values.map((val) => (
                                                            <Badge
                                                                key={val.id || val.value}
                                                                color="gray"
                                                                size="xs"
                                                                className="flex items-center gap-1"
                                                            >
                                                                {val.color && (
                                                                    <span
                                                                        className="mr-1 inline-block h-2.5 w-2.5 rounded-full border border-gray-300"
                                                                        style={{ backgroundColor: val.color }}
                                                                    />
                                                                )}
                                                                {val.value}
                                                            </Badge>
                                                        ))
                                                    ) : (
                                                        <span className="text-xs text-gray-400">Sin valores</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <button
                                                    onClick={() => {
                                                        setAttributeToDelete(a);
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
                                    fetchAttributes(p);
                                }}
                                showIcons
                            />
                        </div>
                    )}
                </Card>

                {/* Create Attribute Modal */}
                <Modal show={createModalOpen} onClose={() => setCreateModalOpen(false)} size="lg">
                    <ModalHeader>
                        <span className="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
                            <HiAdjustments className="h-5 w-5 text-indigo-600" />
                            Crear Nuevo Atributo
                        </span>
                    </ModalHeader>
                    <ModalBody>
                        <form onSubmit={handleCreateSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="name">Nombre del Atributo *</Label>
                                    </div>
                                    <TextInput
                                        id="name"
                                        placeholder="Ej: Talla, Color, Memoria..."
                                        value={formData.name}
                                        onChange={handleNameChange}
                                        required
                                    />
                                    {formErrors.name && (
                                        <span className="mt-1 block text-xs text-red-600 dark:text-red-400">{formErrors.name[0]}</span>
                                    )}
                                </div>

                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="slug">Slug *</Label>
                                    </div>
                                    <TextInput
                                        id="slug"
                                        placeholder="ej: talla, color, memoria"
                                        value={formData.slug || ''}
                                        onChange={(e) => setFormData((prev) => ({ ...prev, slug: e.target.value }))}
                                        required
                                    />
                                    {formErrors.slug && (
                                        <span className="mt-1 block text-xs text-red-600 dark:text-red-400">{formErrors.slug[0]}</span>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="type">Tipo de Visualización</Label>
                                    </div>
                                    <Select
                                        id="type"
                                        value={formData.type}
                                        onChange={(e) => setFormData((prev) => ({ ...prev, type: e.target.value as any }))}
                                    >
                                        <option value="select">Lista Desplegable (Select)</option>
                                        <option value="color">Muestra de Color (Color Swatch)</option>
                                        <option value="button">Botones (Pills/Buttons)</option>
                                        <option value="radio">Radio Buttons</option>
                                    </Select>
                                </div>

                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="position">Posición / Orden</Label>
                                    </div>
                                    <TextInput
                                        id="position"
                                        type="number"
                                        value={formData.position ?? 0}
                                        onChange={(e) => setFormData((prev) => ({ ...prev, position: parseInt(e.target.value, 10) || 0 }))}
                                    />
                                </div>
                            </div>

                            <div className="flex gap-6 py-2">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="is_filterable"
                                        checked={formData.is_filterable ?? true}
                                        onChange={(e) => setFormData((prev) => ({ ...prev, is_filterable: e.target.checked }))}
                                    />
                                    <Label htmlFor="is_filterable" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Usar en filtros de tienda
                                    </Label>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="is_visible"
                                        checked={formData.is_visible ?? true}
                                        onChange={(e) => setFormData((prev) => ({ ...prev, is_visible: e.target.checked }))}
                                    />
                                    <Label htmlFor="is_visible" className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Visible en ficha de producto
                                    </Label>
                                </div>
                            </div>

                            {/* Values section */}
                            <div className="border-t border-gray-200 pt-4 dark:border-gray-700">
                                <Label className="mb-2 block text-sm font-bold text-gray-800 dark:text-gray-200">Valores del Atributo</Label>

                                <div className="mb-3 flex gap-2">
                                    <TextInput
                                        placeholder={formData.type === 'color' ? 'Ej: Rojo, Azul...' : 'Ej: S, M, L, XL, 128GB...'}
                                        value={newValueText}
                                        onChange={(e) => setNewValueText(e.target.value)}
                                        className="flex-1"
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                handleAddValue();
                                            }
                                        }}
                                    />

                                    {formData.type === 'color' && (
                                        <input
                                            type="color"
                                            value={newValueColor}
                                            onChange={(e) => setNewValueColor(e.target.value)}
                                            className="h-10 w-12 cursor-pointer rounded border border-gray-300"
                                            title="Seleccionar color"
                                        />
                                    )}

                                    <Button type="button" color="gray" onClick={handleAddValue}>
                                        <HiPlus className="mr-1 h-4 w-4" />
                                        Añadir
                                    </Button>
                                </div>

                                <div className="flex min-h-[40px] flex-wrap gap-2 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-800">
                                    {formData.values && formData.values.length > 0 ? (
                                        formData.values.map((v, idx) => (
                                            <span
                                                key={idx}
                                                className="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-800 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                                            >
                                                {v.color && (
                                                    <span
                                                        className="inline-block h-3 w-3 rounded-full border border-gray-300"
                                                        style={{ backgroundColor: v.color }}
                                                    />
                                                )}
                                                {v.value}
                                                <button
                                                    type="button"
                                                    onClick={() => handleRemoveValue(idx)}
                                                    className="ml-1 text-gray-400 hover:text-red-500"
                                                >
                                                    <HiX className="h-3.5 w-3.5" />
                                                </button>
                                            </span>
                                        ))
                                    ) : (
                                        <span className="m-auto text-xs text-gray-400">
                                            Añade los valores iniciales para este atributo (ej: Rojo, Azul, Verde)
                                        </span>
                                    )}
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <Button color="gray" onClick={() => setCreateModalOpen(false)}>
                                    Cancelar
                                </Button>
                                <Button type="submit" color="indigo" disabled={actionLoading}>
                                    {actionLoading ? <Spinner size="sm" className="mr-2" /> : null}
                                    Guardar Atributo
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
                            <h3 className="mb-2 text-lg font-bold text-gray-900 dark:text-white">¿Eliminar Atributo?</h3>
                            <p className="mb-5 text-sm text-gray-500">
                                ¿Deseas eliminar el atributo <strong>"{attributeToDelete?.name}"</strong> y todos sus valores?
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

export default AttributeIndexPage;
