import React from 'react';
import {
    HiOutlineDocumentText,
    HiOutlineCreditCard,
    HiOutlineArchiveBox,
    HiOutlineTruck,
    HiOutlineCheckCircle,
    HiOutlineClipboardDocumentCheck,
    HiOutlineArrowTopRightOnSquare,
} from 'react-icons/hi2';

export interface TrackingStep {
    step: number;
    key: string;
    title: string;
    description: string;
    timestamp?: string | null;
    is_completed: boolean;
    is_current: boolean;
}

interface OrderTrackingTimelineProps {
    currentStep: number;
    courier?: string | null;
    trackingNumber?: string | null;
    trackingUrl?: string | null;
    timeline: TrackingStep[];
}

export const OrderTrackingTimeline: React.FC<OrderTrackingTimelineProps> = ({
    currentStep,
    courier,
    trackingNumber,
    trackingUrl,
    timeline,
}) => {
    const stepIcons = [
        HiOutlineDocumentText,
        HiOutlineCreditCard,
        HiOutlineArchiveBox,
        HiOutlineTruck,
        HiOutlineCheckCircle,
    ];

    const copyTracking = () => {
        if (trackingNumber) {
            navigator.clipboard.writeText(trackingNumber);
            alert('Número de guía copiado al portapapeles.');
        }
    };

    return (
        <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-gray-200/80 dark:border-gray-800/80">
            {/* Courier & Tracking Header */}
            {trackingNumber && (
                <div className="mb-6 p-4 rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/40 dark:to-indigo-950/40 border border-blue-200/80 dark:border-blue-900/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                            <HiOutlineTruck className="w-6 h-6" />
                        </div>
                        <div>
                            <span className="text-xs text-gray-500 dark:text-gray-400 font-medium block">
                                Empresa de Encomienda: <strong className="text-gray-900 dark:text-white font-bold">{courier || 'MRW / Zoom'}</strong>
                            </span>
                            <span className="text-sm text-gray-900 dark:text-white font-black tracking-wide">
                                Guía: {trackingNumber}
                            </span>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={copyTracking}
                            className="px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-1.5 transition"
                        >
                            <HiOutlineClipboardDocumentCheck className="w-4 h-4 text-blue-600" />
                            Copiar Guía
                        </button>
                        {trackingUrl && (
                            <a
                                href={trackingUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm shadow-blue-500/20 flex items-center gap-1.5 transition"
                            >
                                <HiOutlineArrowTopRightOnSquare className="w-4 h-4" />
                                Rastrear en Vivo
                            </a>
                        )}
                    </div>
                </div>
            )}

            {/* Step Progress Line */}
            <div className="relative">
                {/* Horizontal Progress Bar for Desktop */}
                <div className="hidden md:block absolute top-6 left-8 right-8 h-1 bg-gray-200 dark:bg-gray-800 -z-0">
                    <div
                        className="h-full bg-gradient-to-r from-blue-600 to-indigo-600 transition-all duration-500"
                        style={{ width: `${Math.max(0, Math.min(100, ((currentStep - 1) / (timeline.length - 1)) * 100))}%` }}
                    />
                </div>

                {/* Steps List */}
                <div className="grid grid-cols-1 md:grid-cols-5 gap-6 relative z-10">
                    {timeline.map((step, idx) => {
                        const Icon = stepIcons[idx] || HiOutlineCheckCircle;
                        const isDone = step.is_completed;
                        const isCurrent = step.is_current;

                        return (
                            <div key={step.key} className="flex md:flex-col items-start md:items-center gap-4 md:gap-2 text-left md:text-center">
                                {/* Circle Icon */}
                                <div
                                    className={`w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 transition shadow-md ${
                                        isCurrent
                                            ? 'bg-blue-600 text-white ring-4 ring-blue-500/20 shadow-blue-500/30 scale-110'
                                            : isDone
                                            ? 'bg-gradient-to-tr from-green-500 to-emerald-600 text-white shadow-green-500/20'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600'
                                    }`}
                                >
                                    <Icon className="w-6 h-6" />
                                </div>

                                {/* Text Content */}
                                <div>
                                    <h4 className={`text-xs font-black leading-tight ${isCurrent ? 'text-blue-600 dark:text-blue-400' : isDone ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'}`}>
                                        {step.title}
                                    </h4>
                                    <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-1 leading-snug">
                                        {step.description}
                                    </p>
                                    {step.timestamp && (
                                        <span className="inline-block mt-1 text-[10px] font-semibold text-gray-400 dark:text-gray-500">
                                            {step.timestamp.substring(0, 10)}
                                        </span>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
};

export default OrderTrackingTimeline;
