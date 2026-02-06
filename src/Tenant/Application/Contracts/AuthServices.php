<?php

namespace Src\Tenant\Application\Contracts;

use Src\Tenant\Domain\ValuesObjects\Uuid;

interface AuthServices {

    public function consultAuthUserByUuid(Uuid $uuid, string $baseUrl=""): array;

}

?>
