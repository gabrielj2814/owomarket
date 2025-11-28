<?php

namespace Src\Admin\Application\Contracts;

use Src\Admin\Domain\ValueObjects\Uuid;

interface AuthServices {

    public function consultAuthUserByUuid(Uuid $uuid): array;

}

?>
