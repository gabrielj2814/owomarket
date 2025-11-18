<?php

namespace Src\Authentication\Infrastructure\Services;



class ApiGateway {

    public function __construct(
        private UserApiClient $userApiClient
    ) {}

    public function users(): UserApiClient {
        return $this->userApiClient;
    }
}


?>
