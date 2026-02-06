<?php

namespace Src\Tenant\Infrastructure\Http\Services;



class ApiGateway {

    public function __construct(
        private AuthTenantApiClient $authApiTenantClient,
        private AuthCentralApiClient $authApiCentralClient,

    ) {}

    public function authCentral(): AuthCentralApiClient {
        return $this->authApiCentralClient;
    }

    public function authTenant(): AuthTenantApiClient {
        return $this->authApiTenantClient;
    }
}


?>
