<?php

namespace Src\Product\Infrastructure\Http\Services;

class ApiGateway
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        private AuthTenantApiClient $authApiTenantClient,
        // private AuthCentralApiClient $authApiCentralClient,

    ) {}

    // Método authCentral.
    // public function authCentral(): AuthCentralApiClient {
    //     return $this->authApiCentralClient;
    // }

    /**
     * Método authTenant.
     */
    public function authTenant(): AuthTenantApiClient
    {
        return $this->authApiTenantClient;
    }
}
