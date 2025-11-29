import AuthServices from '@/Services/AuthServices';
import { initialStateBase } from '@/types/contexts/initialStateBase';
import { ApiResponse } from '@/types/ResponseApi';
import React, { createContext, FC, useCallback, useContext, useEffect, useReducer } from 'react';

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
        consultAuthUser(): void;
    };
}


const DashboardContext = createContext<DashboardContextType | undefined>(undefined);


function dashboardReducer(state: initialStateBase, action: any): initialStateBase {
    switch (action.type) {
        case 'SET_USER_UUID':
            return { ...state, user_uuid: action.payload };
        case 'SET_LOGOUT':
            return { ...state, logout: true };
        case 'SET_LOADING':
            return { ...state, loading: action.payload };
        case 'SET_AUTH_USER':
            return {
                 ...state, authUser:{ // action.payload
                    user_id: action.payload.user_id || "",
                    user_name: action.payload.user_name || "",
                    user_email: action.payload.user_email || "",
                    user_type: action.payload.user_type || "",
                    user_avatar: action.payload.user_avatar || ""
                 }
                };
        default:
            return state;
    }
}

export const DashboardProvider: FC<DashboardProviderProps> = ({ children, user_uuid }) => {

    const initialState: initialStateBase = {
        user_uuid: user_uuid,
        loading: false,
        logout: false,
        authUser: {
            user_id:         "",
            user_name:       "",
            user_email:      "",
            user_type:       "",
            user_avatar:     ""
        }
    };


    const [state, dispatch] = useReducer(dashboardReducer, initialState);


    // ======= useCallback
     const consultAuthUser= useCallback(async () => {
        load(true)
        console.log("uuid => ",state.user_uuid)
        const respuestaAuthUser= await AuthServices.authUser(state.user_uuid)
        if(respuestaAuthUser.data.code!=200){
            return
        }
        dispatch({ type: "SET_AUTH_USER", payload: respuestaAuthUser.data.data })
        console.log("data respondese =>", respuestaAuthUser.data.data )
        load(false)
    },[user_uuid])

    // ======= useCallback
    // ======= useEffect

    useEffect(() => {
        consultAuthUser()
    }, [consultAuthUser])

    // ======= useEffect

    // ======= functions



    const logout = async (): Promise<ApiResponse<boolean>> => {
        const respuesta = await AuthServices.logout(state.user_uuid)
        dispatch({ type: "SET_LOGOUT" })
        return respuesta
    }

    const load = (statu: boolean) => {
        dispatch({ type: "SET_LOADING" , payload: statu })
    }

    // ======= functions

    const value: DashboardContextType = {
        state,
        actions: {
            logout,
            load,
            consultAuthUser,
        }
    };

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
