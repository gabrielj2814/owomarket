import Dashboard from "@/components/layouts/Dashboard";
import ProductImageDropzone from "@/components/ui/ProductImageDropzone";
import BrandServices from "@/Services/BrandServices";
import CategoryServices from "@/Services/CategoryServices";
import ProductServices from "@/Services/ProductServices";
import { ErrorsFormProduct } from "@/types/ErrorsFormProduct";
import { FormProduct } from "@/types/FormProduct";
import { Brand } from "@/types/models/Brand";
import { Category } from "@/types/models/Category";
import { Head } from "@inertiajs/react";
import {
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    HelperText,
    Label,
    Select,
    Spinner,
    Textarea,
    TextInput,
    ToggleSwitch,
} from "flowbite-react";
import { FC, useEffect, useState } from "react";
import { HiHome, HiPlus, HiTrash } from "react-icons/hi";
import {
    LuArrowBigLeft,
    LuBoxes,
    LuCheck,
    LuDollarSign,
    LuImage,
    LuLayers,
    LuPackage,
    LuRuler,
    LuSave,
    LuSaveOff,
    LuTag,
} from "react-icons/lu";

interface FormProductPageProps {
    user_id: string;
    title: string;
    host: string;
    user_name: string;
    record_uuid?: string | null;
}

const initialForm: FormProduct = {
    name: "",
    slug: "",
    sku: "",
    price: "",
    compare_price: "",
    cost_price: "",
    quantity: 0,
    min_quantity: 0,
    max_quantity: 1000,
    track_quantity: true,
    is_visible: true,
    is_featured: false,
    is_digital: false,
    description: "",
    short_description: "",
    barcode: "",
    weight: "",
    height: "",
    width: "",
    length: "",
    category_id: null,
    brand_id: null,
    images: [],
    variants: [],
};

const FormProductPage: FC<FormProductPageProps> = ({
    user_id,
    title,
    host,
    user_name,
    record_uuid,
}) => {
    const isEdit = Boolean(record_uuid);
    const [form, setForm] = useState<FormProduct>(initialForm);
    const [errors, setErrors] = useState<ErrorsFormProduct>({});
    const [categories, setCategories] = useState<Category[]>([]);
    const [brands, setBrands] = useState<Brand[]>([]);
    const [loadingData, setLoadingData] = useState<boolean>(isEdit);
    const [saving, setSaving] = useState<boolean>(false);
    const [autoSlug, setAutoSlug] = useState<boolean>(!isEdit);

    // Image URL state for adding images
    const [newImageUrl, setNewImageUrl] = useState<string>("");

    // Load categories and brands
    useEffect(() => {
        const loadMeta = async () => {
            try {
                const [catsRes, brandsRes] = await Promise.all([
                    CategoryServices.tree(),
                    BrandServices.listActive(),
                ]);
                if (catsRes?.data?.data && Array.isArray(catsRes.data.data)) {
                    setCategories(catsRes.data.data);
                }
                if (brandsRes?.data?.data && Array.isArray(brandsRes.data.data)) {
                    setBrands(brandsRes.data.data);
                }
            } catch (err) {
                console.error("Error cargando categorías y marcas", err);
            }
        };
        loadMeta();
    }, []);

    // Load existing product if in Edit mode
    useEffect(() => {
        if (!record_uuid) return;
        const loadProduct = async () => {
            setLoadingData(true);
            try {
                const res = await ProductServices.consultById(record_uuid);
                if (res?.data?.data) {
                    const p = res.data.data;
                    setForm({
                        id: p.id,
                        name: p.name,
                        slug: p.slug,
                        sku: p.sku,
                        price: p.price,
                        compare_price: p.compare_price ?? "",
                        cost_price: p.cost_price ?? "",
                        quantity: p.quantity,
                        min_quantity: p.min_quantity,
                        max_quantity: p.max_quantity,
                        track_quantity: p.track_quantity,
                        is_visible: p.is_visible,
                        is_featured: p.is_featured,
                        is_digital: p.is_digital,
                        description: p.description ?? "",
                        short_description: p.short_description ?? "",
                        barcode: p.barcode ?? "",
                        weight: p.weight ?? "",
                        height: p.height ?? "",
                        width: p.width ?? "",
                        length: p.length ?? "",
                        category_id: p.category_id,
                        brand_id: p.brand_id,
                        images: p.images ?? [],
                        variants: p.variants ?? [],
                    });
                }
            } catch (err) {
                console.error("Error cargando producto", err);
            } finally {
                setLoadingData(false);
            }
        };
        loadProduct();
    }, [record_uuid]);

    const generateSlug = (val: string): string => {
        return val
            .toLowerCase()
            .trim()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9\s-]/g, "")
            .replace(/[\s_-]+/g, "-")
            .replace(/^-+|-+$/g, "");
    };

    const handleNameChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const nameVal = e.target.value;
        setForm((prev) => ({
            ...prev,
            name: nameVal,
            slug: autoSlug ? generateSlug(nameVal) : prev.slug,
        }));
    };

    const handleAddImage = () => {
        if (!newImageUrl.trim()) return;
        setForm((prev) => ({
            ...prev,
            images: [
                ...(prev.images || []),
                {
                    image_path: newImageUrl.trim(),
                    is_default: (prev.images || []).length === 0,
                    order: (prev.images || []).length,
                },
            ],
        }));
        setNewImageUrl("");
    };

    const handleRemoveImage = (index: number) => {
        setForm((prev) => ({
            ...prev,
            images: (prev.images || []).filter((_, i) => i !== index),
        }));
    };

    const handleSetDefaultImage = (index: number) => {
        setForm((prev) => ({
            ...prev,
            images: (prev.images || []).map((img, i) => ({
                ...img,
                is_default: i === index,
            })),
        }));
    };

    const handleAddVariant = () => {
        const nextIdx = (form.variants || []).length + 1;
        setForm((prev) => ({
            ...prev,
            variants: [
                ...(prev.variants || []),
                {
                    sku: `${prev.sku || "PROD"}-VAR-${nextIdx}`,
                    price: typeof prev.price === "number" ? prev.price : parseFloat(prev.price as string) || 0,
                    quantity: 10,
                    attributes: {},
                },
            ],
        }));
    };

    const handleRemoveVariant = (index: number) => {
        setForm((prev) => ({
            ...prev,
            variants: (prev.variants || []).filter((_, i) => i !== index),
        }));
    };

    const handleVariantChange = (index: number, field: string, value: any) => {
        setForm((prev) => {
            const nextVariants = [...(prev.variants || [])];
            nextVariants[index] = { ...nextVariants[index], [field]: value };
            return { ...prev, variants: nextVariants };
        });
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});

        try {
            const payload: FormProduct = {
                ...form,
                price: parseFloat(form.price as string) || 0,
                compare_price: form.compare_price ? parseFloat(form.compare_price as string) : null,
                cost_price: form.cost_price ? parseFloat(form.cost_price as string) : null,
                quantity: parseInt(form.quantity as string, 10) || 0,
                min_quantity: parseInt(form.min_quantity as string, 10) || 0,
                max_quantity: parseInt(form.max_quantity as string, 10) || 1000,
                weight: form.weight ? parseFloat(form.weight as string) : null,
                height: form.height ? parseFloat(form.height as string) : null,
                width: form.width ? parseFloat(form.width as string) : null,
                length: form.length ? parseFloat(form.length as string) : null,
            };

            let res;
            if (isEdit && record_uuid) {
                res = await ProductServices.update(record_uuid, payload);
            } else {
                res = await ProductServices.create(payload);
            }

            if ((res as any)?.code === 200 || (res as any)?.code === 201 || (res as any)?.status === "success" || res?.data?.code === 200 || res?.data?.code === 201) {
                window.location.href = `/product/backoffice/${user_id}/module`;
            } else if ((res as any)?.errors || (res as any)?.data?.errors) {
                setErrors((res as any)?.errors || (res as any)?.data?.errors);
            }
        } catch (err) {
            console.error("Error guardando producto", err);
        } finally {
            setSaving(false);
        }
    };

    const regresar = () => {
        window.location.href = `/product/backoffice/${user_id}/module`;
    };

    return (
        <>
            <Head>
                <title>{title}</title>
            </Head>
            <Dashboard user_uuid={user_id}>
                {/* Breadcrumb */}
                <Breadcrumb
                    aria-label="Navegación"
                    className="hidden lg:block bg-gray-50 px-5 py-3 rounded-lg dark:bg-gray-800 mb-5 border border-gray-100 dark:border-gray-700"
                >
                    <BreadcrumbItem icon={HiHome} href={`/tenant/backoffice/${user_id}/dashboard`}>
                        Inicio
                    </BreadcrumbItem>
                    <BreadcrumbItem href={`/product/backoffice/${user_id}/module`}>
                        Catálogo
                    </BreadcrumbItem>
                    <BreadcrumbItem href={`/product/backoffice/${user_id}/module`}>
                        Productos
                    </BreadcrumbItem>
                    <BreadcrumbItem>{isEdit ? "Editar Producto" : "Nuevo Producto"}</BreadcrumbItem>
                </Breadcrumb>

                {/* Header Title and Back Button */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            <LuPackage className="w-8 h-8 text-blue-600 dark:text-blue-400" />
                            {isEdit ? "Editar Producto" : "Registrar Nuevo Producto"}
                        </h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {isEdit
                                ? `Actualizando la información y stock de "${form.name || "Producto"}"`
                                : "Ingresa las especificaciones, precios, variantes e imágenes"}
                        </p>
                    </div>
                    <Button color="gray" onClick={regresar} className="shadow-sm">
                        <LuArrowBigLeft className="w-5 h-5 mr-1" />
                        Regresar
                    </Button>
                </div>

                {loadingData ? (
                    <Card className="text-center py-16">
                        <Spinner size="xl" />
                        <p className="mt-3 text-sm text-gray-500">Cargando datos del producto...</p>
                    </Card>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* 1. Información Principal */}
                        <Card className="shadow-sm border-gray-100 dark:border-gray-700">
                            <h2 className="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <LuTag className="w-5 h-5 text-blue-500" />
                                1. Identificación y Precios Principales
                            </h2>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="name">
                                            Nombre del Producto <span className="text-red-500">*</span>
                                        </Label>
                                    </div>
                                    <TextInput
                                        id="name"
                                        type="text"
                                        placeholder="Ej: Auriculares Bluetooth Pro"
                                        value={form.name}
                                        onChange={handleNameChange}
                                        required
                                        color={errors.name ? "failure" : undefined}
                                    />
                                    {errors.name && <HelperText color="failure">{errors.name}</HelperText>}
                                </div>

                                <div>
                                    <div className="mb-1 block flex justify-between">
                                        <Label htmlFor="slug">
                                            Slug URL <span className="text-red-500">*</span>
                                        </Label>
                                        <span
                                            onClick={() => setAutoSlug(!autoSlug)}
                                            className="text-xs text-blue-500 cursor-pointer select-none hover:underline"
                                        >
                                            {autoSlug ? "Modo Manual" : "Auto-Generar"}
                                        </span>
                                    </div>
                                    <TextInput
                                        id="slug"
                                        type="text"
                                        placeholder="ej: auriculares-bluetooth-pro"
                                        value={form.slug}
                                        onChange={(e) => {
                                            setAutoSlug(false);
                                            setForm({ ...form, slug: e.target.value });
                                        }}
                                        required
                                        color={errors.slug ? "failure" : undefined}
                                    />
                                    {errors.slug && <HelperText color="failure">{errors.slug}</HelperText>}
                                </div>

                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="sku">
                                            SKU (Código Único) <span className="text-red-500">*</span>
                                        </Label>
                                    </div>
                                    <TextInput
                                        id="sku"
                                        type="text"
                                        placeholder="Ej: AUD-BT-001"
                                        value={form.sku}
                                        onChange={(e) => setForm({ ...form, sku: e.target.value.toUpperCase() })}
                                        required
                                        color={errors.sku ? "failure" : undefined}
                                    />
                                    {errors.sku && <HelperText color="failure">{errors.sku}</HelperText>}
                                </div>

                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="price">
                                            Precio de Venta ($) <span className="text-red-500">*</span>
                                        </Label>
                                    </div>
                                    <TextInput
                                        id="price"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        value={form.price}
                                        onChange={(e) => setForm({ ...form, price: e.target.value })}
                                        required
                                        color={errors.price ? "failure" : undefined}
                                    />
                                    {errors.price && <HelperText color="failure">{errors.price}</HelperText>}
                                </div>
                            </div>
                        </Card>

                        {/* 2. Categoría, Marca y Clasificación */}
                        <Card className="shadow-sm border-gray-100 dark:border-gray-700">
                            <h2 className="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <LuLayers className="w-5 h-5 text-purple-500" />
                                2. Clasificación y Visibilidad
                            </h2>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="category_id">Categoría</Label>
                                    </div>
                                    <Select
                                        id="category_id"
                                        value={form.category_id ?? ""}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                category_id: e.target.value ? parseInt(e.target.value, 10) : null,
                                            })
                                        }
                                    >
                                        <option value="">Seleccionar Categoría...</option>
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name}
                                            </option>
                                        ))}
                                    </Select>
                                </div>

                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="brand_id">Marca</Label>
                                    </div>
                                    <Select
                                        id="brand_id"
                                        value={form.brand_id ?? ""}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                brand_id: e.target.value ? parseInt(e.target.value, 10) : null,
                                            })
                                        }
                                    >
                                        <option value="">Seleccionar Marca...</option>
                                        {brands.map((b) => (
                                            <option key={b.id} value={b.id}>
                                                {b.name}
                                            </option>
                                        ))}
                                    </Select>
                                </div>

                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="compare_price">Precio Comparativo / Antes ($)</Label>
                                    </div>
                                    <TextInput
                                        id="compare_price"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        value={form.compare_price ?? ""}
                                        onChange={(e) => setForm({ ...form, compare_price: e.target.value })}
                                    />
                                </div>

                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="cost_price">Costo del Producto ($)</Label>
                                    </div>
                                    <TextInput
                                        id="cost_price"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        value={form.cost_price ?? ""}
                                        onChange={(e) => setForm({ ...form, cost_price: e.target.value })}
                                    />
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-6 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <ToggleSwitch
                                    checked={form.is_visible ?? true}
                                    label="Visible en Tienda"
                                    onChange={(val) => setForm({ ...form, is_visible: val })}
                                />
                                <ToggleSwitch
                                    checked={form.is_featured ?? false}
                                    label="Producto Destacado"
                                    onChange={(val) => setForm({ ...form, is_featured: val })}
                                />
                                <ToggleSwitch
                                    checked={form.track_quantity ?? true}
                                    label="Rastrear Inventario"
                                    onChange={(val) => setForm({ ...form, track_quantity: val })}
                                />
                            </div>
                        </Card>

                        {/* 3. Inventario y Descripciones */}
                        <Card className="shadow-sm border-gray-100 dark:border-gray-700">
                            <h2 className="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <LuBoxes className="w-5 h-5 text-emerald-500" />
                                3. Inventario y Descripciones
                            </h2>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="quantity">Cantidad en Stock</Label>
                                    </div>
                                    <TextInput
                                        id="quantity"
                                        type="number"
                                        min={0}
                                        value={form.quantity}
                                        onChange={(e) => setForm({ ...form, quantity: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="min_quantity">Cantidad Mínima</Label>
                                    </div>
                                    <TextInput
                                        id="min_quantity"
                                        type="number"
                                        min={0}
                                        value={form.min_quantity ?? 0}
                                        onChange={(e) => setForm({ ...form, min_quantity: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="barcode">Código de Barras (EAN / UPC)</Label>
                                    </div>
                                    <TextInput
                                        id="barcode"
                                        type="text"
                                        placeholder="750123456789"
                                        value={form.barcode ?? ""}
                                        onChange={(e) => setForm({ ...form, barcode: e.target.value })}
                                    />
                                </div>
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="short_description">Descripción Corta / Resumen</Label>
                                    </div>
                                    <TextInput
                                        id="short_description"
                                        placeholder="Breve resumen destacado en tarjetas de producto"
                                        value={form.short_description ?? ""}
                                        onChange={(e) => setForm({ ...form, short_description: e.target.value })}
                                    />
                                </div>

                                <div>
                                    <div className="mb-1 block">
                                        <Label htmlFor="description">Descripción Completa</Label>
                                    </div>
                                    <Textarea
                                        id="description"
                                        rows={4}
                                        placeholder="Describe todas las características y beneficios del producto..."
                                        value={form.description ?? ""}
                                        onChange={(e) => setForm({ ...form, description: e.target.value })}
                                    />
                                </div>
                            </div>
                        </Card>

                        {/* 4. Galería de Imágenes y Media Upload */}
                        <Card className="shadow-sm border-gray-100 dark:border-gray-700">
                            <div className="flex justify-between items-center mb-1">
                                <h2 className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <LuImage className="w-5 h-5 text-cyan-500" />
                                    4. Galería de Imágenes
                                </h2>
                                <span className="text-xs text-gray-500">
                                    {(form.images || []).length} / 8 imágenes cargadas
                                </span>
                            </div>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                Sube fotos directamente al almacenamiento de tu tienda o arrastra los archivos. La imagen con la etiqueta "Portada" se mostrará como imagen principal en el catálogo.
                            </p>
                            <ProductImageDropzone
                                images={form.images || []}
                                onChange={(updatedImages) => setForm({ ...form, images: updatedImages })}
                                maxFiles={8}
                            />
                        </Card>

                        {/* 5. Variantes de Producto */}
                        <Card className="shadow-sm border-gray-100 dark:border-gray-700">
                            <div className="flex justify-between items-center mb-3">
                                <h2 className="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <LuLayers className="w-5 h-5 text-indigo-500" />
                                    5. Variantes del Producto (Opcional)
                                </h2>
                                <Button size="xs" color="gray" onClick={handleAddVariant} type="button">
                                    <HiPlus className="w-3.5 h-3.5 mr-1" />
                                    Añadir Variante
                                </Button>
                            </div>

                            {form.variants && form.variants.length > 0 ? (
                                <div className="space-y-3">
                                    {form.variants.map((v, i) => (
                                        <div
                                            key={i}
                                            className="grid grid-cols-1 sm:grid-cols-4 gap-3 p-3 rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800 items-end"
                                        >
                                            <div>
                                                <Label className="text-xs">SKU Variante</Label>
                                                <TextInput
                                                    sizing="sm"
                                                    value={v.sku}
                                                    onChange={(e) => handleVariantChange(i, "sku", e.target.value.toUpperCase())}
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <Label className="text-xs">Precio ($)</Label>
                                                <TextInput
                                                    sizing="sm"
                                                    type="number"
                                                    step="0.01"
                                                    value={v.price}
                                                    onChange={(e) =>
                                                        handleVariantChange(i, "price", parseFloat(e.target.value) || 0)
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div>
                                                <Label className="text-xs">Stock</Label>
                                                <TextInput
                                                    sizing="sm"
                                                    type="number"
                                                    min={0}
                                                    value={v.quantity}
                                                    onChange={(e) =>
                                                        handleVariantChange(i, "quantity", parseInt(e.target.value, 10) || 0)
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="flex justify-end">
                                                <Button
                                                    size="xs"
                                                    color="failure"
                                                    onClick={() => handleRemoveVariant(i)}
                                                    type="button"
                                                >
                                                    <HiTrash className="w-4 h-4 mr-1" />
                                                    Quitar
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl text-gray-400 text-sm">
                                    Sin variantes. Este producto se venderá como producto simple único.
                                </div>
                            )}
                        </Card>

                        {/* 6. Dimensiones y Peso */}
                        <Card className="shadow-sm border-gray-100 dark:border-gray-700">
                            <h2 className="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <LuRuler className="w-5 h-5 text-amber-500" />
                                6. Dimensiones y Logística
                            </h2>
                            <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                <div>
                                    <Label htmlFor="weight" className="text-xs">
                                        Peso (kg)
                                    </Label>
                                    <TextInput
                                        id="weight"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.5"
                                        value={form.weight ?? ""}
                                        onChange={(e) => setForm({ ...form, weight: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="height" className="text-xs">
                                        Altura (cm)
                                    </Label>
                                    <TextInput
                                        id="height"
                                        type="number"
                                        step="0.01"
                                        placeholder="10"
                                        value={form.height ?? ""}
                                        onChange={(e) => setForm({ ...form, height: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="width" className="text-xs">
                                        Ancho (cm)
                                    </Label>
                                    <TextInput
                                        id="width"
                                        type="number"
                                        step="0.01"
                                        placeholder="15"
                                        value={form.width ?? ""}
                                        onChange={(e) => setForm({ ...form, width: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="length" className="text-xs">
                                        Largo (cm)
                                    </Label>
                                    <TextInput
                                        id="length"
                                        type="number"
                                        step="0.01"
                                        placeholder="20"
                                        value={form.length ?? ""}
                                        onChange={(e) => setForm({ ...form, length: e.target.value })}
                                    />
                                </div>
                            </div>
                        </Card>

                        {/* Botones de Acción */}
                        <div className="flex flex-col sm:flex-row justify-end gap-3 pt-4">
                            <Button color="gray" onClick={regresar} disabled={saving}>
                                <LuSaveOff className="w-5 h-5 mr-1" />
                                Cancelar
                            </Button>
                            <Button color="blue" type="submit" disabled={saving} className="shadow-md">
                                {saving ? (
                                    <Spinner size="sm" className="mr-2" />
                                ) : (
                                    <LuSave className="w-5 h-5 mr-1" />
                                )}
                                {isEdit ? "Guardar Cambios" : "Crear Producto"}
                            </Button>
                        </div>
                    </form>
                )}
            </Dashboard>
        </>
    );
};

export default FormProductPage;
