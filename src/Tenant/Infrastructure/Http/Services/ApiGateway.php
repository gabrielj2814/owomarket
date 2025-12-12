<?php

namespace Src\Tenant\Infrastructure\Http\Services;



class ApiGateway {

    public function __construct(
        private AuthApiClient $authApiClient
    ) {}

    public function auth(): AuthApiClient {
        return $this->authApiClient;
    }
}


?>
