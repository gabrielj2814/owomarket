import { useIsCentralDomain } from '@/hooks/useIsCentralDomain';
import CustomerAuthServices, { CentralCustomerData, LoginCustomerPayload, RegisterCustomerPayload } from '@/Services/CustomerAuthServices';
import CustomerPortalServices from '@/Services/CustomerPortalServices';
import React, { createContext, useContext, useEffect, useState } from 'react';

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
    // Hallazgo G7: lo decide el servidor, no el numero de puntos del hostname.
    const isCentral = useIsCentralDomain();
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
            // 1. Recuperar la identidad guardada.
            //
            // Hallazgo B4: aqui se cacheaba el PERFIL ENTERO en `localStorage` —nombre,
            // email, telefono, documento y direcciones—, al alcance de cualquier XSS. El
            // backend dejo de confiar en ese cache en la Fase 0.3-D, asi que ya solo era
            // exposicion sin contrapartida.
            //
            // Ahora solo se guarda el **id**, que es lo unico que hace falta para saber a
            // quien rehacerle la sesion. Los datos personales los da el servidor.
            const cachedId = localStorage.getItem('owo_central_customer_id');

            if (cachedId) {
                const perfil = await CustomerPortalServices.getProfile(cachedId).catch(() => null);

                if (perfil?.data) {
                    setCustomer(perfil.data);
                    setCentralCustomer(perfil.data);
                    setAddresses(perfil.data.addresses ?? []);
                }
            }

            // 2. En el storefront de una tienda, contrastar con la sesion real.
            //
            // Hallazgo G10: si la tienda respondia «no autenticado», esta rama no hacia
            // NADA: ni reintentaba el SSO ni borraba el cache. El cliente que volvia al dia
            // siguiente, con la cookie de la tienda ya expirada, veia la navbar y el
            // checkout como si siguiera dentro, pasaba la puerta de autenticacion del paso
            // 3 y confirmaba el pedido; el backend lo trataba como invitado o devolvia 401,
            // y los `.catch(() => {})` del portal mostraban listas vacias en lugar de
            // «sesion expirada».
            if (!isCentral) {
                const res = await CustomerAuthServices.getTenantSession();
                const autenticado = res && res.code === 200 && res.data?.authenticated && res.data?.customer;

                if (autenticado) {
                    setCustomer(res.data!.customer);
                } else {
                    // La cookie de la tienda no vale. Si el cliente sigue con sesion en el
                    // dominio central, se rehace el SSO en silencio; es exactamente lo que
                    // hace `login()` tras autenticar.
                    const restablecida = await tryRestoreTenantSession();

                    if (!restablecida) {
                        // Ni tienda ni central: se limpia todo en vez de dejar al usuario
                        // creyendo que sigue dentro.
                        clearCachedSession();
                    }
                }
            }
        } catch {
            // keep state
        } finally {
            setLoading(false);
        }
    };

    /**
     * Rehace el intercambio SSO con la tienda a partir del cliente central cacheado.
     * Devuelve `false` si no hay con que rehacerlo, que es la senal para limpiar.
     */
    const tryRestoreTenantSession = async (): Promise<boolean> => {
        const cachedId = localStorage.getItem('owo_central_customer_id');
        if (!cachedId) return false;

        try {
            const ssoRes = await CustomerAuthServices.generateSsoToken(cachedId);
            if (ssoRes.code !== 200 || !ssoRes.data?.token) return false;

            const consumeRes = await CustomerAuthServices.consumeSsoToken(ssoRes.data.token);
            if (consumeRes.code !== 200 || !consumeRes.data) return false;

            setCustomer(consumeRes.data.customer);
            setCentralCustomer(consumeRes.data.central_customer ?? null);
            if (consumeRes.data.addresses) {
                setAddresses(consumeRes.data.addresses);
                // Hallazgo B4: las direcciones ya no se cachean; las da el servidor.
            }

            return true;
        } catch {
            return false;
        }
    };

    /** Deja el estado y el cache como los de un visitante anonimo. */
    const clearCachedSession = () => {
        localStorage.removeItem('owo_central_customer_id');
        setCustomer(null);
        setCentralCustomer(null);
        setAddresses([]);
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
            if (isCentral) {
                setCustomer(centralCust);
                setCentralCustomer(centralCust);
                localStorage.setItem('owo_central_customer_id', centralCust.id);

                if (centralCust.addresses && Array.isArray(centralCust.addresses)) {
                    setAddresses(centralCust.addresses);
                    // Hallazgo B4: las direcciones ya no se cachean; las da el servidor.
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
                            // Hallazgo B4: las direcciones ya no se cachean; las da el servidor.
                        }
                        localStorage.setItem('owo_central_customer_id', centralCust.id);
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
            localStorage.setItem('owo_central_customer_id', centralCust.id);
            if (centralCust.addresses) {
                setAddresses(centralCust.addresses);
                // Hallazgo B4: las direcciones ya no se cachean; las da el servidor.
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
            if (isCentral) {
                await CustomerAuthServices.logoutCentral();
            } else {
                await CustomerAuthServices.logoutTenant();
            }
        } finally {
            setCustomer(null);
            setCentralCustomer(null);
            setAddresses([]);
            localStorage.removeItem('owo_central_customer_id');
            // Se limpian tambien las claves viejas: quien tenga el perfil cacheado de una
            // version anterior deja de tenerlo en cuanto cierre sesion (hallazgo B4).
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
