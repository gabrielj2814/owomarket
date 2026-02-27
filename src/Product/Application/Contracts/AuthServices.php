<?php

namespace Src\Product\Application\Contracts;

use Src\Product\Domain\ValueObjects\Uuid;

interface AuthServices {

    public function consultAuthUserByUuid(Uuid $uuid, string $baseUrl=""): array;

}

?>
