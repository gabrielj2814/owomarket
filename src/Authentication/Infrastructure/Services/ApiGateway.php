<?php

namespace Src\Authentication\Infrastructure\Services;



class ApiGateway {

    /**
     * Constructor de la clase.
     */

    public function __construct(
        private UserApiCentralClient $userApiCentralClient,
        private UserApiTenantClient $userApiTenantClient,
        private TenantApiCentralClient $tenantApiCentralClient
    ) {}

    /**
     * Método usersCentrals.
     */

    public function usersCentrals(): UserApiCentralClient {
        return $this->userApiCentralClient;
    }

    /**
     * Método usersTenants.
     */

    public function usersTenants(): UserApiTenantClient {
        return $this->userApiTenantClient;
    }

    /**
     * Método tenants.
     */

    public function tenants(): TenantApiCentralClient {
        return $this->tenantApiCentralClient;
    }
}


?>
