<?php

declare(strict_types=1);

namespace Src\Category\Domain\Exceptions;

use Exception;

class InvalidCategorySlugException extends Exception
{
    public function __construct(string $slug)
    {
        parent::__construct("Invalid category slug format: '{$slug}'", 400);
    }
}
