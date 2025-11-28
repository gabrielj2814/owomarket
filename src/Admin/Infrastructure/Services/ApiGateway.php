<?php

namespace Src\Admin\Infrastructure\Services;



class ApiGateway {

    public function __construct(
        private AuthWebClient $authWebClient
    ) {}

    public function auth(): AuthWebClient {
        return $this->authWebClient;
    }
}


?>
