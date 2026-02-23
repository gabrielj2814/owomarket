import TenantLayout from '@/components/layouts/TenantLayout';
import Test from '@/components/test';
import storage from '@/routes/storage';
import { Head } from '@inertiajs/react';
import React from 'react';


interface tenantHomePageProps {
    domain?: String;
}


const tenantHomePage:React.FC<tenantHomePageProps> = ({domain= null}) => {

    return (
        <>
            <TenantLayout>
                <Head title="Welcome" />
                <div className="container mx-auto p-4">
                    <img className=' d-block ml-auto mr-auto rounded-2xl' src={storage.local.get("images/503.jpg").url}  />
                    <div className=' text-center'>
                        <p className="mb-4">
                            Welcome to my store. We apologize, we are currently undergoing maintenance. Thank you for your visit. 😊
                        </p>
                        {domain && <p className="mb-4">You are visiting from the domain: <strong>{domain}</strong></p>}
                    </div>
                </div>
            </TenantLayout>
        </>
    );
}

export default tenantHomePage;
