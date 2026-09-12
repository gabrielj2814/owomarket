import StorefrontOrderServices from '@/Services/StorefrontOrderServices';
import { MOTIVOS_DE_RECLAMACION } from '@/utils/claims';
import { Button, Label, Modal, ModalBody, ModalFooter, ModalHeader, Select, Textarea } from 'flowbite-react';
import React, { useEffect, useState } from 'react';

/**
 * Abrir una reclamación sobre un artículo comprado en esta tienda (fase 2 del escaparate).
 *
 * ## Por qué no reutiliza el formulario del portal
 *
 * El del portal empieza por elegir un pedido y luego un producto de ese pedido. Aquí las dos
 * cosas ya están decididas: se llega desde el artículo concreto. Compartir el componente
 * obligaría a que el del portal supiera vivir sin sus dos selectores, que es más enredo del
 * que ahorra.
 *
 * Lo que sí se comparte es lo que **no puede divergir**: los motivos y las frases de estado,
 * que viven en `utils/claims` porque las dos pantallas pintan la misma fila de la misma tabla.
 *
 * ## El aspecto
 *
 * Componentes de Flowbite a secas: lo pone `storefrontTheme` desde `StorefrontLayout`. Si algo
 * necesitara un `className` para parecerse a sus hermanas, el sitio de arreglarlo es el tema.
 */
interface StorefrontClaimModalProps {
    open: boolean;
    orderId: string;
    productId: string;
    productName: string;
    onClose: () => void;
    onCreated: () => void;
}

export default function StorefrontClaimModal({
    open,
    orderId,
    productId,
    productName,
    onClose,
    onCreated,
}: StorefrontClaimModalProps) {
    const [reason, setReason] = useState<string>(MOTIVOS_DE_RECLAMACION[0]);
    const [description, setDescription] = useState('');
    const [enviando, setEnviando] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // Cada apertura empieza en limpio. Sin esto, el motivo y el texto de la reclamación
    // anterior reaparecen sobre OTRO artículo, y quien no se fije reclama lo que no quería.
    useEffect(() => {
        if (open) {
            setReason(MOTIVOS_DE_RECLAMACION[0]);
            setDescription('');
            setError(null);
        }
    }, [open, productId]);

    const enviar = async (e: React.FormEvent) => {
        e.preventDefault();
        setError(null);
        setEnviando(true);

        try {
            await StorefrontOrderServices.crearReclamacion({
                order_id: orderId,
                product_id: productId,
                reason,
                description,
            });
            onCreated();
            onClose();
        } catch (err: unknown) {
            /*
             * El mensaje del servidor se enseña tal cual cuando lo hay: es el que explica la
             * causa concreta --sin cédula, fuera de plazo, ya hay una reclamación viva--. Una
             * frase genérica aquí obligaría al comprador a adivinar cuál de las tres es.
             */
            const mensaje = (err as { response?: { data?: { message?: string } } }).response?.data?.message;
            setError(mensaje || 'No pudimos registrar tu reclamación. Vuelve a intentarlo.');
        } finally {
            setEnviando(false);
        }
    };

    return (
        <Modal show={open} onClose={onClose} size="lg">
            <form onSubmit={enviar}>
                <ModalHeader>Reclamar «{productName}»</ModalHeader>
                <ModalBody className="space-y-4">
                    {error && (
                        <p data-testid="reclamacion-error" role="alert" className="text-sm font-semibold text-red-600">
                            {error}
                        </p>
                    )}

                    <div>
                        <Label htmlFor="reclamacion-motivo">Motivo</Label>
                        <Select
                            id="reclamacion-motivo"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            required
                        >
                            {MOTIVOS_DE_RECLAMACION.map((m) => (
                                <option key={m} value={m}>
                                    {m}
                                </option>
                            ))}
                        </Select>
                    </div>

                    <div>
                        <Label htmlFor="reclamacion-descripcion">Qué pasó</Label>
                        <Textarea
                            id="reclamacion-descripcion"
                            rows={4}
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                            placeholder="Cuenta con detalle qué problema tiene el producto."
                            required
                            maxLength={1000}
                        />
                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            La tienda tiene un plazo para responder. Si no responde, se resuelve a tu favor.
                        </p>
                    </div>
                </ModalBody>
                <ModalFooter>
                    {/*
                      * El `<form>` envuelve ModalBody Y ModalFooter: el botón de enviar vive en
                      * el pie y necesita estar dentro del formulario para que `type="submit"`
                      * funcione.
                      */}
                    <Button type="submit" color="primary" disabled={enviando}>
                        {enviando ? 'Enviando…' : 'Enviar reclamación'}
                    </Button>
                    <Button type="button" color="subtle" onClick={onClose} disabled={enviando}>
                        Cancelar
                    </Button>
                </ModalFooter>
            </form>
        </Modal>
    );
}
