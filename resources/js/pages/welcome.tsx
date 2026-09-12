import Test from '@/components/test';
import storage from '@/routes/storage';
import { Head } from '@inertiajs/react';
import React from 'react';

/*
 * Pagina de andamiaje de Laravel. No se migra porque no hay nada que migrar: un titulo, una
 * imagen y un parrafo. Flowbite no tiene --ni necesita-- un componente para eso.
 */


interface props {
    domain?: string;
}


const Welcome:React.FC<props> = ({domain= null}) => {

    return (
        <>
            <Head title="Welcome" />
            <div className="container mx-auto p-4">
                <img className=' d-block ml-auto mr-auto' src={storage.local.get("images/503.jpg").url}  />
                <div className=' text-center'>
                    <h1 className="text-4xl font-bold mb-4">Welcome to Our Application</h1>
                    {domain && <p className="mb-4">You are visiting from the domain: <strong>{domain}</strong></p>}
                    <p className="mb-4">This is the welcome page of our multi-tenant application.</p>
                    <p className="mb-4">Estamos en contrucción</p>
                </div>
                {/* <Test /> */}
            </div>
        </>
    );
}

export default Welcome;
