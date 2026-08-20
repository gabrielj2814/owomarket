import React from 'react';
import { Link } from '@inertiajs/react';
import {
    HiOutlineBuildingStorefront,
    HiOutlineCreditCard,
    HiOutlineCurrencyDollar,
    HiOutlineCube,
    HiOutlineChatBubbleLeftRight,
} from 'react-icons/hi2';

interface TenantOwnerNavTabsProps {
    userId: string;
    activeTab: 'dashboard' | 'wallet' | 'catalog' | 'billing' | 'support';
}

export const TenantOwnerNavTabs: React.FC<TenantOwnerNavTabsProps> = ({ userId, activeTab }) => {
    const tabs = [
        {
            id: 'dashboard',
            label: 'Mis Tiendas & Sucursales',
            href: `/tenant/owner/backoffice/${userId}/dashboard`,
            icon: HiOutlineBuildingStorefront,
        },
        {
            id: 'wallet',
            label: 'Billetera & Liquidaciones',
            href: `/tenant/owner/backoffice/${userId}/wallet`,
            icon: HiOutlineCurrencyDollar,
        },
        {
            id: 'catalog',
            label: 'Catálogo & Marketplace Central',
            href: `/tenant/owner/backoffice/${userId}/catalog`,
            icon: HiOutlineCube,
        },
        {
            id: 'billing',
            label: 'Suscripciones & Facturas B2B',
            href: `/tenant/owner/backoffice/${userId}/billing`,
            icon: HiOutlineCreditCard,
        },
        {
            id: 'support',
            label: 'Centro de Soporte & Reportes',
            href: `/tenant/owner/backoffice/${userId}/support`,
            icon: HiOutlineChatBubbleLeftRight,
        },
    ];

    return (
        <div className="flex flex-wrap items-center gap-2 mb-6 border-b border-gray-200 dark:border-gray-800 pb-3">
            {tabs.map((tab) => {
                const Icon = tab.icon;
                const isActive = activeTab === tab.id;

                return (
                    <Link
                        key={tab.id}
                        href={tab.href}
                        className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl font-bold text-xs transition ${
                            isActive
                                ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60 border border-gray-200 dark:border-gray-700'
                        }`}
                    >
                        <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-blue-600 dark:text-blue-400'}`} />
                        <span>{tab.label}</span>
                    </Link>
                );
            })}
        </div>
    );
};

export default TenantOwnerNavTabs;
