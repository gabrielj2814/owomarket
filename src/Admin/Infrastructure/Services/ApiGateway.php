<?php

namespace Src\Admin\Infrastructure\Services;



class ApiGateway {

    /**
     * Constructor de la clase.
     */

    public function __construct(
        private AuthApiClient $authApiClient
    ) {}

    /**
     * Método auth.
     */

    public function auth(): AuthApiClient {
        return $this->authApiClient;
    }
}


?>
