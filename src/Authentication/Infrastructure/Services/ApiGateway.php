<?php

namespace Src\Authentication\Infrastructure\Services;



class ApiGateway {

    public function __construct(
        private UserApiCentralClient $userApiCentralClient,
        private UserApiTenantClient $userApiTenantClient,
        private TenantApiCentralClient $tenantApiCentralClient
    ) {}

    public function usersCentrals(): UserApiCentralClient {
        return $this->userApiCentralClient;
    }

    public function usersTenants(): UserApiTenantClient {
        return $this->userApiTenantClient;
    }

    public function tenants(): TenantApiCentralClient {
        return $this->tenantApiCentralClient;
    }
}


?>
