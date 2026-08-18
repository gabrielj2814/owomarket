import React from 'react';
import { CartProvider } from '@/contexts/CartContext';
import MiniCartDrawer from '@/components/ui/storefront/MiniCartDrawer';
import StorefrontNavbar from '@/components/ui/storefront/StorefrontNavbar';
import StorefrontFooter from '@/components/ui/storefront/StorefrontFooter';
import { Head } from '@inertiajs/react';

export interface StorefrontLayoutProps {
    children?: React.ReactNode;
    title?: string;
    domain?: string;
    storeSettings?: {
        store_name?: string;
        logo_url?: string;
        contact_phone?: string;
        store_email?: string;
        currency?: string;
        address?: string;
        social_facebook?: string;
        social_instagram?: string;
        social_whatsapp?: string;
        social_twitter?: string;
        seo_title?: string;
        seo_description?: string;
    };
    categories?: Array<{ id: string; name: string; slug: string }>;
    authUser?: { id: string; name: string; email: string } | null;
}

export default function StorefrontLayout({
    children,
    title,
    domain = 'localhost',
    storeSettings,
    categories = [],
    authUser = null,
}: StorefrontLayoutProps) {
    const storeName = storeSettings?.store_name || 'Mi Tienda Online';
    const currency = storeSettings?.currency || 'USD';
    const pageTitle = title ? `${title} | ${storeName}` : storeSettings?.seo_title || storeName;

    return (
        <CartProvider currency={currency} domain={domain}>
            <Head>
                <title>{pageTitle}</title>
                {storeSettings?.seo_description && (
                    <meta name="description" content={storeSettings.seo_description} />
                )}
            </Head>

            <div className="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-800 dark:text-gray-200 flex flex-col font-sans antialiased">
                {/* Navbar */}
                <StorefrontNavbar
                    storeName={storeName}
                    logoUrl={storeSettings?.logo_url}
                    contactPhone={storeSettings?.contact_phone}
                    storeEmail={storeSettings?.store_email}
                    currency={currency}
                    socialFacebook={storeSettings?.social_facebook}
                    socialInstagram={storeSettings?.social_instagram}
                    socialWhatsapp={storeSettings?.social_whatsapp}
                    categories={categories}
                    authUser={authUser}
                />

                {/* Sliding Mini-Cart Drawer */}
                <MiniCartDrawer />

                {/* Main Content Viewport */}
                <main className="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                    {children}
                </main>

                {/* Footer */}
                <StorefrontFooter
                    storeName={storeName}
                    storeEmail={storeSettings?.store_email}
                    contactPhone={storeSettings?.contact_phone}
                    address={storeSettings?.address}
                    socialFacebook={storeSettings?.social_facebook}
                    socialInstagram={storeSettings?.social_instagram}
                    socialWhatsapp={storeSettings?.social_whatsapp}
                    socialTwitter={storeSettings?.social_twitter}
                    categories={categories}
                />
            </div>
        </CartProvider>
    );
}
