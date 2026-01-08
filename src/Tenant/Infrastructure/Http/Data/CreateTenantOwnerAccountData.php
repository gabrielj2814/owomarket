<?php


namespace Src\Tenant\Infrastructure\Http\Data;

use Spatie\LaravelData\Data;

class CreateTenantOwnerAccountData extends Data {

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $phone,
        public readonly string $store_name,
        public readonly string $tenant_name,
    ) {}

}


?>
