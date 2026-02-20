import CentralLayout from '@/components/layouts/CentralLayout';
import storage from '@/routes/storage';
import { Head } from '@inertiajs/react';
import React from 'react';


interface props {
    domain?: String;
}


const centralHomePage:React.FC<props> = ({domain= null}) => {

    return (
        <>
            <CentralLayout>
                <Head title="Welcome" />
                    <div className="container mx-auto p-4">
                        <img className=' d-block ml-auto mr-auto rounded-2xl' src={storage.local.get("images/503.jpg").url}  />
                        <div className=' text-center'>
                            <h1 className="text-4xl font-bold mb-4">🚧OwOMarket🚧</h1>
                            <p className="mb-4">
                                Welcome to owomarket! We are currently under construction but will open soon. Thank you for your visit 😊.
                            </p>
                        </div>
                    </div>
            </CentralLayout>
        </>
    );
}

export default centralHomePage;
