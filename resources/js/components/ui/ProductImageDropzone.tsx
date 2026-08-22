import ProductServices from "@/Services/ProductServices";
import { ProductImage } from "@/types/models/ProductImage";
import { Button, Spinner } from "flowbite-react";
import React, { FC, useRef, useState } from "react";
import { HiCheckCircle, HiPhotograph, HiPlus, HiTrash, HiUpload } from "react-icons/hi";

interface ProductImageDropzoneProps {
    images: ProductImage[];
    onChange: (images: ProductImage[]) => void;
    maxFiles?: number;
}

export const ProductImageDropzone: FC<ProductImageDropzoneProps> = ({
    images = [],
    onChange,
    maxFiles = 8,
}) => {
    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const [uploading, setUploading] = useState<boolean>(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [dragActive, setDragActive] = useState<boolean>(false);

    const handleFiles = async (files: FileList | File[]) => {
        if (!files || files.length === 0) return;
        setErrorMessage(null);
        setUploading(true);

        const currentImages = [...images];
        const newImagesList: ProductImage[] = [];

        for (let i = 0; i < files.length; i++) {
            const file = files[i];

            // Client-side validations
            if (!file.type.startsWith("image/")) {
                setErrorMessage(`El archivo "${file.name}" no es una imagen válida.`);
                continue;
            }

            if (file.size > 5 * 1024 * 1024) {
                setErrorMessage(`La imagen "${file.name}" supera el límite de 5MB.`);
                continue;
            }

            try {
                const response = await ProductServices.uploadImage(file);
                const mediaData = response?.data?.data;
                if (mediaData?.image_path) {
                    const isFirst = currentImages.length === 0 && newImagesList.length === 0;
                    newImagesList.push({
                        image_path: mediaData.image_path,
                        alt_text: file.name.replace(/\.[^/.]+$/, ""),
                        is_default: isFirst,
                        order: currentImages.length + newImagesList.length,
                    });
                } else {
                    setErrorMessage(response?.data?.message || "Error al subir la imagen.");
                }
            } catch (err: any) {
                setErrorMessage("Error de conexión durante la subida.");
            }
        }

        if (newImagesList.length > 0) {
            onChange([...currentImages, ...newImagesList]);
        }

        setUploading(false);
    };

    const handleFileInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            handleFiles(e.target.files);
        }
    };

    const handleDrag = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === "dragenter" || e.type === "dragover") {
            setDragActive(true);
        } else if (e.type === "dragleave") {
            setDragActive(false);
        }
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleFiles(e.dataTransfer.files);
        }
    };

    const handleSetDefault = (index: number) => {
        const updated = images.map((img, i) => ({
            ...img,
            is_default: i === index,
        }));
        onChange(updated);
    };

    const handleDelete = async (index: number) => {
        const targetImg = images[index];
        if (targetImg?.image_path) {
            try {
                await ProductServices.deleteImage(targetImg.image_path);
            } catch (err) {
                console.error("Error al eliminar del storage", err);
            }
        }

        const updated = images.filter((_, i) => i !== index);
        // If we deleted the default image, set the first one as default
        if (targetImg?.is_default && updated.length > 0) {
            updated[0] = { ...updated[0], is_default: true };
        }
        onChange(updated);
    };

    return (
        <div className="space-y-4">
            {/* Dropzone Area */}
            <div
                onDragEnter={handleDrag}
                onDragLeave={handleDrag}
                onDragOver={handleDrag}
                onDrop={handleDrop}
                onClick={() => fileInputRef.current?.click()}
                className={`border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition-all duration-200 ${
                    dragActive
                        ? "border-blue-500 bg-blue-50/50 dark:bg-blue-900/20"
                        : "border-gray-300 dark:border-gray-700 hover:border-blue-400 bg-gray-50/50 dark:bg-gray-800/50"
                }`}
            >
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    accept="image/jpeg,image/png,image/jpg,image/webp"
                    className="hidden"
                    onChange={handleFileInputChange}
                    disabled={uploading || images.length >= maxFiles}
                />

                <div className="flex flex-col items-center justify-center space-y-2">
                    <div className="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        {uploading ? <Spinner size="md" /> : <HiPhotograph className="w-6 h-6" />}
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {uploading
                                ? "Subiendo imágenes al servidor..."
                                : "Haz clic para subir o arrastra tus imágenes aquí"}
                        </p>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            PNG, JPG, JPEG o WEBP (Máximo 5MB por foto. Hasta {maxFiles} imágenes)
                        </p>
                    </div>
                </div>
            </div>

            {/* Error Feedback */}
            {errorMessage && (
                <div className="text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2.5 rounded-lg border border-red-200 dark:border-red-800">
                    {errorMessage}
                </div>
            )}

            {/* Uploaded Images Grid */}
            {images.length > 0 && (
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 pt-2">
                    {images.map((img, index) => (
                        <div
                            key={index}
                            className={`group relative rounded-xl border overflow-hidden bg-white dark:bg-gray-800 transition-all duration-200 shadow-sm ${
                                img.is_default
                                    ? "border-blue-500 ring-2 ring-blue-400/50"
                                    : "border-gray-200 dark:border-gray-700 hover:border-gray-400"
                            }`}
                        >
                            {/* Image Thumbnail */}
                            <img
                                src={img.image_path}
                                alt={img.alt_text || `Foto ${index + 1}`}
                                className="w-full h-28 object-cover group-hover:scale-105 transition-transform duration-200"
                            />

                            {/* Badge if Cover */}
                            {img.is_default && (
                                <span className="absolute top-1.5 left-1.5 bg-blue-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded shadow flex items-center gap-1">
                                    <HiCheckCircle className="w-3 h-3" />
                                    Portada
                                </span>
                            )}

                            {/* Actions Overlay */}
                            <div className="p-1.5 flex items-center justify-between bg-gray-50 dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 text-xs">
                                {!img.is_default ? (
                                    <button
                                        type="button"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleSetDefault(index);
                                        }}
                                        className="text-[11px] font-medium text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                    >
                                        Hacer Portada
                                    </button>
                                ) : (
                                    <span className="text-[11px] font-bold text-blue-600 dark:text-blue-400">
                                        Principal
                                    </span>
                                )}

                                <button
                                    type="button"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handleDelete(index);
                                    }}
                                    className="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition-colors"
                                    title="Eliminar foto"
                                >
                                    <HiTrash className="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default ProductImageDropzone;
