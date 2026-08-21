import React, { createContext, useContext, useEffect, useState } from 'react';
import CustomerAuthServices, {
    CentralCustomerData,
    LoginCustomerPayload,
    RegisterCustomerPayload,
    isCentralDomain,
} from '@/Services/CustomerAuthServices';

export interface CustomerAddress {
    id: string;
    label: string;
    address: string;
    city: string;
    state?: string | null;
    zip_code?: string | null;
    country: string;
    is_default: boolean;
}

export interface CustomerAuthContextType {
    customer: any | null;
    centralCustomer: CentralCustomerData | null;
    addresses: CustomerAddress[];
    isAuthenticated: boolean;
    loading: boolean;
    isAuthModalOpen: boolean;
    authModalTab: 'login' | 'register';
    openAuthModal: (tab?: 'login' | 'register') => void;
    closeAuthModal: () => void;
    login: (payload: LoginCustomerPayload) => Promise<{ success: boolean; message?: string }>;
    register: (payload: RegisterCustomerPayload) => Promise<{ success: boolean; message?: string }>;
    logout: () => Promise<void>;
    refreshSession: () => Promise<void>;
}

const CustomerAuthContext = createContext<CustomerAuthContextType | undefined>(undefined);

export const CustomerAuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [customer, setCustomer] = useState<any | null>(null);
    const [centralCustomer, setCentralCustomer] = useState<CentralCustomerData | null>(null);
    const [addresses, setAddresses] = useState<CustomerAddress[]>([]);
    const [loading, setLoading] = useState<boolean>(true);
    const [isAuthModalOpen, setIsAuthModalOpen] = useState<boolean>(false);
    const [authModalTab, setAuthModalTab] = useState<'login' | 'register'>('login');

    const openAuthModal = (tab: 'login' | 'register' = 'login') => {
        setAuthModalTab(tab);
        setIsAuthModalOpen(true);
    };

    const closeAuthModal = () => {
        setIsAuthModalOpen(false);
    };

    const refreshSession = async () => {
        setLoading(true);
        try {
            // 1. Check cached central customer in localStorage
            const cachedCustomer = localStorage.getItem('owo_central_customer');
            const cachedAddresses = localStorage.getItem('owo_customer_addresses');

            if (cachedCustomer) {
                try {
                    const parsedCust = JSON.parse(cachedCustomer);
                    setCustomer(parsedCust);
                    setCentralCustomer(parsedCust);
                } catch {}
            }

            if (cachedAddresses) {
                try {
                    setAddresses(JSON.parse(cachedAddresses));
                } catch {}
            }

            // 2. If on tenant storefront, sync with tenant session
            if (!isCentralDomain()) {
                const res = await CustomerAuthServices.getTenantSession();
                if (res && res.code === 200 && res.data?.authenticated && res.data?.customer) {
                    setCustomer(res.data.customer);
                }
            }
        } catch {
            // keep state
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        refreshSession();
    }, []);

    const login = async (payload: LoginCustomerPayload): Promise<{ success: boolean; message?: string }> => {
        try {
            // 1. Authenticate with central API
            const loginRes = await CustomerAuthServices.loginCentral(payload);
            if (loginRes.code !== 200 || !loginRes.data?.customer) {
                return {
                    success: false,
                    message: loginRes.message || 'Credenciales incorrectas.',
                };
            }

            const centralCust = loginRes.data.customer;

            // 2. If on central marketplace domain, complete login immediately
            if (isCentralDomain()) {
                setCustomer(centralCust);
                setCentralCustomer(centralCust);
                localStorage.setItem('owo_central_customer', JSON.stringify(centralCust));

                if (centralCust.addresses && Array.isArray(centralCust.addresses)) {
                    setAddresses(centralCust.addresses);
                    localStorage.setItem('owo_customer_addresses', JSON.stringify(centralCust.addresses));
                }

                closeAuthModal();
                return { success: true, message: '¡Bienvenido de nuevo!' };
            }

            // 3. If on tenant storefront, perform SSO exchange
            try {
                const ssoRes = await CustomerAuthServices.generateSsoToken(centralCust.id);
                if (ssoRes.code === 200 && ssoRes.data?.token) {
                    const consumeRes = await CustomerAuthServices.consumeSsoToken(ssoRes.data.token);
                    if (consumeRes.code === 200 && consumeRes.data) {
                        setCustomer(consumeRes.data.customer);
                        setCentralCustomer(consumeRes.data.central_customer || centralCust);
                        if (consumeRes.data.addresses) {
                            setAddresses(consumeRes.data.addresses);
                            localStorage.setItem('owo_customer_addresses', JSON.stringify(consumeRes.data.addresses));
                        }
                        localStorage.setItem('owo_central_customer', JSON.stringify(centralCust));
                        closeAuthModal();
                        return { success: true, message: '¡Bienvenido de nuevo!' };
                    }
                }
            } catch {
                // Fallback to central customer if SSO consume fails
            }

            // Graceful fallback
            setCustomer(centralCust);
            setCentralCustomer(centralCust);
            localStorage.setItem('owo_central_customer', JSON.stringify(centralCust));
            if (centralCust.addresses) {
                setAddresses(centralCust.addresses);
                localStorage.setItem('owo_customer_addresses', JSON.stringify(centralCust.addresses));
            }

            closeAuthModal();
            return { success: true, message: '¡Bienvenido de nuevo!' };
        } catch (error: any) {
            return {
                success: false,
                message: error.message || 'Error de conexión durante el inicio de sesión.',
            };
        }
    };

    const register = async (payload: RegisterCustomerPayload): Promise<{ success: boolean; message?: string }> => {
        try {
            // 1. Register with central
            const regRes = await CustomerAuthServices.registerCentral(payload);
            if (regRes.code !== 201 || !regRes.data?.customer) {
                return {
                    success: false,
                    message: regRes.message || 'No se pudo crear la cuenta.',
                };
            }

            // 2. Auto-login with the created credentials
            return await login({
                email: payload.email,
                password: payload.password,
            });
        } catch (error: any) {
            return {
                success: false,
                message: error.message || 'Error al procesar el registro.',
            };
        }
    };

    const logout = async (): Promise<void> => {
        try {
            if (isCentralDomain()) {
                await CustomerAuthServices.logoutCentral();
            } else {
                await CustomerAuthServices.logoutTenant();
            }
        } finally {
            setCustomer(null);
            setCentralCustomer(null);
            setAddresses([]);
            localStorage.removeItem('owo_central_customer');
            localStorage.removeItem('owo_customer_addresses');
        }
    };

    return (
        <CustomerAuthContext.Provider
            value={{
                customer,
                centralCustomer,
                addresses,
                isAuthenticated: !!customer,
                loading,
                isAuthModalOpen,
                authModalTab,
                openAuthModal,
                closeAuthModal,
                login,
                register,
                logout,
                refreshSession,
            }}
        >
            {children}
        </CustomerAuthContext.Provider>
    );
};

export const useCustomerAuth = (): CustomerAuthContextType => {
    const context = useContext(CustomerAuthContext);
    if (!context) {
        throw new Error('useCustomerAuth must be used within a CustomerAuthProvider');
    }
    return context;
};

export default CustomerAuthContext;
