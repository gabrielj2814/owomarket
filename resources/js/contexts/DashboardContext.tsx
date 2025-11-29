import AuthServices from '@/Services/AuthServices';
import { initialStateBase } from '@/types/contexts/initialStateBase';
import { ApiResponse } from '@/types/ResponseApi';
import React, { createContext, FC, useContext, useEffect, useReducer } from 'react';

interface DashboardProviderProps {
    user_uuid: string
    children?: React.ReactNode;
}

// Define la interfaz para el Context
interface DashboardContextType {
    state: initialStateBase;
    actions: {
        logout(): Promise<ApiResponse<boolean>>;
        load(statu: boolean): void;
    };
}


const DashboardContext = createContext<DashboardContextType | undefined>(undefined);

const initialState: initialStateBase = {
    user_uuid: "",
    loading: false,
    logout: false,
};

function dashboardReducer(state: initialStateBase, action: any): initialStateBase {
    switch (action.type) {
        case 'SET_USER_UUID':
            return { ...state, user_uuid: action.payload };
        case 'SET_LOGOUT':
            return { ...state, logout: true };
        case 'SET_LOADING':
            return { ...state, loading: action.payload };
        default:
            return state;
    }
}

export const DashboardProvider: FC<DashboardProviderProps> = ({ children, user_uuid }) => {
    const [state, dispatch] = useReducer(dashboardReducer, initialState);

    const logout = async (): Promise<ApiResponse<boolean>> => {
        const respuesta = await AuthServices.logout(state.user_uuid)
        dispatch({ type: "SET_LOGOUT" })
        return respuesta
    }

    const load = (statu: boolean) => {
        dispatch({ type: "SET_LOADING" , payload: statu })
    }


    const value: DashboardContextType = {
        state,
        actions: {
            logout,
            load,
        }
    };

    useEffect(() => {
        dispatch({ type: "SET_USER_UUID", payload: user_uuid })
    }, [])

    return (
        <DashboardContext.Provider value={value}>
            {children}
        </DashboardContext.Provider>
    );
};

export const useDashboard = (): DashboardContextType => {
    const context = useContext(DashboardContext);
    if (!context) {
        throw new Error('useDashboard must be used within DashboardProvider');
    }
    return context;
};
