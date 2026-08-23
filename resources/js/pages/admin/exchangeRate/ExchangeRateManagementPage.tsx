import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import Dashboard from '@/components/layouts/Dashboard';
import {
    Badge,
    Breadcrumb,
    BreadcrumbItem,
    Button,
    Card,
    Label,
    Modal,
    ModalBody,
    ModalFooter,
    ModalHeader,
    Spinner,
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeadCell,
    TableRow,
    TextInput,
    Textarea,
} from 'flowbite-react';
import {
    HiArrowPath,
    HiCalculator,
    HiCheckCircle,
    HiClock,
    HiCurrencyDollar,
    HiDocumentText,
    HiHome,
    HiInformationCircle,
    HiPencilSquare,
    HiPlus,
    HiShieldCheck,
} from 'react-icons/hi2';
import {
    createManualRate,
    syncBcvRate,
} from '@/Services/ExchangeRateServices';
import { ExchangeRateItem } from '@/types/models/ExchangeRate';

interface ExchangeRateManagementPageProps {
    title?: string;
    user_id: string;
    user_name?: string;
    user_email?: string;
    active_rate: ExchangeRateItem | null;
    history: {
        data: ExchangeRateItem[];
        total: number;
        current_page: number;
        per_page: number;
        last_page: number;
    };
}

export const ExchangeRateManagementPage: React.FC<ExchangeRateManagementPageProps> = ({
    title = 'Gestión de Tasa de Cambio (BCV) - OwOMarket',
    user_id,
    active_rate: initialActiveRate,
    history: initialHistory,
}) => {
    const [activeRate, setActiveRate] = useState<ExchangeRateItem | null>(initialActiveRate);
    const [history, setHistory] = useState(initialHistory);

    // Syncing state
    const [isSyncing, setIsSyncing] = useState(false);
    const [feedbackMsg, setFeedbackMsg] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    // Manual Rate Modal
    const [isManualModalOpen, setIsManualModalOpen] = useState(false);
    const [manualRate, setManualRate] = useState<string>('');
    const [manualDate, setManualDate] = useState<string>(new Date().toISOString().split('T')[0]);
    const [manualNote, setManualNote] = useState<string>('');
    const [isSavingManual, setIsSavingManual] = useState(false);

    // Live Test Calculator
    const [calcUsd, setCalcUsd] = useState<number>(100);

    const currentRateVal = activeRate?.rate || 775.3356;
    const calcVes = calcUsd * currentRateVal;

    const handleSyncBcv = async () => {
        setIsSyncing(true);
        setFeedbackMsg(null);
        try {
            const res = await syncBcvRate();
            if (res.success) {
                setFeedbackMsg({
                    type: 'success',
                    text: `Tasa oficial del BCV sincronizada con éxito: Bs. ${res.data?.rate || ''}/USD`,
                });
                router.reload();
            } else {
                setFeedbackMsg({
                    type: 'error',
                    text: res.message || 'No se pudo sincronizar la tasa del BCV.',
                });
            }
        } catch (error: any) {
            setFeedbackMsg({
                type: 'error',
                text: error?.response?.data?.message || 'Error de comunicación al sincronizar con el portal BCV.',
            });
        } finally {
            setIsSyncing(false);
        }
    };

    const handleSaveManualRate = async (e: React.FormEvent) => {
        e.preventDefault();
        setFeedbackMsg(null);

        const numericVal = parseFloat(manualRate);
        if (isNaN(numericVal) || numericVal <= 0) {
            // Esta pagina ya tenia `feedbackMsg` y lo usaba para el resto de avisos; solo
            // esta rama se habia quedado en alert(). Se corrige la incoherencia, no se
            // inventa un patron.
            setFeedbackMsg({ type: 'error', text: 'Introduce una tasa numérica válida mayor a 0.' });
            return;
        }

        setIsSavingManual(true);
        try {
            const res = await createManualRate({
                rate: numericVal,
                rate_date: manualDate,
                note: manualNote,
            });

            if (res.success) {
                setFeedbackMsg({
                    type: 'success',
                    text: `Tasa manual fijada correctamente: Bs. ${numericVal}/USD`,
                });
                setIsManualModalOpen(false);
                setManualRate('');
                setManualNote('');
                router.reload();
            } else {
                setFeedbackMsg({
                    type: 'error',
                    text: res.message || 'Error al guardar la tasa manual.',
                });
            }
        } catch (error: any) {
            setFeedbackMsg({
                type: 'error',
                text: error?.response?.data?.message || 'Error al registrar la tasa manual.',
            });
        } finally {
            setIsSavingManual(false);
        }
    };

    return (
        <>
            <Head>
                <title>{title}</title>
            </Head>

            <Dashboard user_uuid={user_id}>
                {/* Breadcrumbs */}
                <Breadcrumb className="hidden lg:block bg-gray-50 px-5 py-3 rounded dark:bg-gray-800 mb-5">
                    <BreadcrumbItem href={`/admin/backoffice/${user_id}/dashboard`} icon={HiHome}>
                        Panel Principal
                    </BreadcrumbItem>
                    <BreadcrumbItem>Tasa de Cambio BCV y Moneda Dual</BreadcrumbItem>
                </Breadcrumb>

                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2.5">
                                <HiCurrencyDollar className="w-8 h-8 text-blue-600 dark:text-blue-400" />
                                Tasa de Cambio Oficial (BCV) y Paridad
                            </h1>
                            <p className="text-xs text-gray-500 mt-1">
                                Control centralizado de conversión de divisas, cotización oficial del Banco Central de Venezuela y liquidaciones.
                            </p>
                        </div>

                        <div className="flex items-center gap-2">
                            <Button
                                color="light"
                                size="sm"
                                onClick={() => setIsManualModalOpen(true)}
                                className="font-bold"
                            >
                                <HiPencilSquare className="w-4 h-4 mr-1.5 text-gray-600" />
                                Fijar Tasa Manual
                            </Button>

                            <Button
                                color="blue"
                                size="sm"
                                disabled={isSyncing}
                                onClick={handleSyncBcv}
                                className="font-bold shadow-md shadow-blue-500/20"
                            >
                                {isSyncing ? (
                                    <>
                                        <Spinner size="sm" className="mr-2" />
                                        Sincronizando con BCV...
                                    </>
                                ) : (
                                    <>
                                        <HiArrowPath className="w-4 h-4 mr-1.5" />
                                        Sincronizar BCV Ahora
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>

                    {/* Feedback Alert */}
                    {feedbackMsg && (
                        <div
                            className={`p-4 rounded-xl text-xs font-semibold flex items-center gap-2 ${
                                feedbackMsg.type === 'success'
                                    ? 'bg-green-50 text-green-800 dark:bg-green-950/40 dark:text-green-300 border border-green-200 dark:border-green-800'
                                    : 'bg-red-50 text-red-800 dark:bg-red-950/40 dark:text-red-300 border border-red-200 dark:border-red-800'
                            }`}
                        >
                            {feedbackMsg.type === 'success' ? (
                                <HiCheckCircle className="w-5 h-5 flex-shrink-0" />
                            ) : (
                                <HiInformationCircle className="w-5 h-5 flex-shrink-0" />
                            )}
                            <span>{feedbackMsg.text}</span>
                        </div>
                    )}

                    {/* 2-Columns Grid: Active Rate Card & Live Simulator */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Active Rate Card (2 cols) */}
                        <Card className="lg:col-span-2 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800">
                            <div className="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                                <div className="flex items-center gap-2">
                                    <span className="w-3 h-3 rounded-full bg-green-500 animate-pulse" />
                                    <h2 className="text-base font-bold text-gray-900 dark:text-white">
                                        Tasa de Cambio Vigente en la Plataforma
                                    </h2>
                                </div>

                                <Badge color={activeRate?.source === 'BCV_SCRAPING' ? 'info' : 'warning'}>
                                    {activeRate?.source === 'BCV_SCRAPING' ? 'Oficial BCV' : 'Ajuste Manual'}
                                </Badge>
                            </div>

                            <div className="py-2 flex flex-col sm:flex-row items-baseline justify-between gap-4">
                                <div>
                                    <span className="text-xs text-gray-400 font-bold uppercase tracking-wider block">
                                        Paridad Base USD / VES
                                    </span>
                                    <div className="flex items-baseline gap-2 mt-1">
                                        <span className="text-4xl sm:text-5xl font-black text-blue-600 dark:text-blue-400 tracking-tight" data-testid="active-rate-value">
                                            Bs. {activeRate?.rate ? activeRate.rate.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 4 }) : '775,3356'}
                                        </span>
                                        <span className="text-base font-bold text-gray-500">/ 1.00 USD</span>
                                    </div>
                                </div>

                                <div className="space-y-1 sm:text-right text-xs text-gray-500 dark:text-gray-400">
                                    <div>
                                        <span className="font-semibold text-gray-700 dark:text-gray-300">Fecha Valor: </span>
                                        <span className="font-mono">{activeRate?.rate_date || new Date().toISOString().split('T')[0]}</span>
                                    </div>
                                    <div>
                                        <span className="font-semibold text-gray-700 dark:text-gray-300">Última Actualización: </span>
                                        <span>{activeRate?.created_at ? new Date(activeRate.created_at).toLocaleString('es-VE') : 'Reciente'}</span>
                                    </div>
                                    <div className="text-[11px] text-gray-400">
                                        Aplicada en Checkout, Catálogo y Facturación Electrónica
                                    </div>
                                </div>
                            </div>

                            <div className="p-3 bg-blue-50/60 dark:bg-blue-950/30 rounded-xl border border-blue-100 dark:border-blue-900/40 flex items-center justify-between text-xs text-blue-900 dark:text-blue-300">
                                <div className="flex items-center gap-2">
                                    <HiShieldCheck className="w-5 h-5 text-blue-600 flex-shrink-0" />
                                    <span>
                                        Sincronización automática activa programada en días hábiles bancarios (09:00, 13:00 y 17:30 VET).
                                    </span>
                                </div>
                            </div>
                        </Card>

                        {/* Simulator Calculator (1 col) */}
                        <Card className="shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800">
                            <div className="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-3">
                                <HiCalculator className="w-5 h-5 text-purple-600" />
                                <h3 className="text-sm font-bold text-gray-900 dark:text-white">
                                    Simulador de Conversión
                                </h3>
                            </div>

                            <div className="space-y-3 text-xs">
                                <div>
                                    <Label className="text-xs font-semibold text-gray-600">Monto Base ($ USD):</Label>
                                    <TextInput
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={calcUsd}
                                        onChange={(e) => setCalcUsd(parseFloat(e.target.value) || 0)}
                                        className="text-xs font-bold mt-1"
                                    />
                                </div>

                                <div className="p-3 bg-gray-50 dark:bg-gray-800/80 rounded-xl border dark:border-gray-700 space-y-1.5">
                                    <div className="flex justify-between text-gray-500">
                                        <span>Equivalente USDT:</span>
                                        <span className="font-bold text-emerald-600">{calcUsd.toFixed(2)} USDT</span>
                                    </div>
                                    <div className="flex justify-between text-gray-500">
                                        <span>Tasa Aplicada:</span>
                                        <span className="font-mono">Bs. {currentRateVal.toFixed(2)}</span>
                                    </div>
                                    <div className="pt-2 border-t dark:border-gray-700 flex justify-between items-baseline">
                                        <span className="font-bold text-gray-900 dark:text-white">Total Bolívares:</span>
                                        <span className="text-base font-black text-blue-600 dark:text-blue-400">
                                            Bs. {calcVes.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>

                    {/* Historical Table */}
                    <Card className="shadow-sm rounded-2xl border border-gray-100 dark:border-gray-800">
                        <div className="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                            <div className="flex items-center gap-2">
                                <HiClock className="w-5 h-5 text-gray-500" />
                                <h3 className="text-base font-bold text-gray-900 dark:text-white">
                                    Historial de Cotizaciones Registradas
                                </h3>
                            </div>
                            <span className="text-xs text-gray-400">
                                Total: {history.total} registros
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <Table hoverable>
                                <TableHead>
                                    <TableHeadCell>Fecha Valor</TableHeadCell>
                                    <TableHeadCell>Cotización (VES/USD)</TableHeadCell>
                                    <TableHeadCell>Fuente</TableHeadCell>
                                    <TableHeadCell>Estado</TableHeadCell>
                                    <TableHeadCell>Registrado En</TableHeadCell>
                                </TableHead>
                                <TableBody className="divide-y text-xs">
                                    {history.data.length > 0 ? (
                                        history.data.map((item) => (
                                            <TableRow key={item.id} className="bg-white dark:bg-gray-800">
                                                <TableCell className="font-bold text-gray-900 dark:text-white font-mono">
                                                    {item.rate_date}
                                                </TableCell>
                                                <TableCell className="font-black text-sm text-gray-900 dark:text-white">
                                                    Bs. {item.rate.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 4 })}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        color={
                                                            item.source === 'BCV_SCRAPING'
                                                                ? 'info'
                                                                : item.source === 'MANUAL_ADMIN'
                                                                ? 'warning'
                                                                : 'gray'
                                                        }
                                                    >
                                                        {item.source === 'BCV_SCRAPING'
                                                            ? 'Portal BCV'
                                                            : item.source === 'MANUAL_ADMIN'
                                                            ? 'Ajuste Manual'
                                                            : item.source}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {item.is_active ? (
                                                        <Badge color="success">Activa</Badge>
                                                    ) : (
                                                        <span className="text-gray-400">Histórica</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-gray-500">
                                                    {item.created_at ? new Date(item.created_at).toLocaleString('es-VE') : '-'}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={5} className="text-center py-6 text-gray-400">
                                                No hay tasas de cambio registradas aún.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </Card>
                </div>

                {/* Modal: Fijar Tasa Manual */}
                <Modal
                    show={isManualModalOpen}
                    onClose={() => setIsManualModalOpen(false)}
                    size="md"
                >
                    <ModalHeader>Fijar Tasa de Cambio Manual</ModalHeader>
                    <form onSubmit={handleSaveManualRate}>
                        <ModalBody className="space-y-4 text-xs">
                            <p className="text-gray-500 leading-relaxed">
                                Use esta opción si el portal del BCV presenta interrupciones o requiere aplicar una tasa de contingencia autorizada.
                            </p>

                            <div className="space-y-1">
                                <Label className="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    Cotización en Bolívares (VES por 1 USD) *
                                </Label>
                                <TextInput
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    required
                                    placeholder="Ej: 780.5000"
                                    value={manualRate}
                                    onChange={(e) => setManualRate(e.target.value)}
                                    className="text-xs font-bold"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    Fecha Valor *
                                </Label>
                                <TextInput
                                    type="date"
                                    required
                                    value={manualDate}
                                    onChange={(e) => setManualDate(e.target.value)}
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    Motivo o Nota de Auditoría (Opcional)
                                </Label>
                                <Textarea
                                    rows={2}
                                    placeholder="Ej: Mantenimiento del portal BCV - Cotización circular bancaria"
                                    value={manualNote}
                                    onChange={(e) => setManualNote(e.target.value)}
                                    className="text-xs"
                                />
                            </div>
                        </ModalBody>

                        <ModalFooter className="flex justify-end gap-2">
                            <Button
                                color="light"
                                size="sm"
                                type="button"
                                onClick={() => setIsManualModalOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button
                                color="blue"
                                size="sm"
                                type="submit"
                                disabled={isSavingManual}
                            >
                                {isSavingManual ? (
                                    <>
                                        <Spinner size="sm" className="mr-2" />
                                        Guardando...
                                    </>
                                ) : (
                                    'Guardar y Activar Tasa'
                                )}
                            </Button>
                        </ModalFooter>
                    </form>
                </Modal>
            </Dashboard>
        </>
    );
};

export default ExchangeRateManagementPage;
