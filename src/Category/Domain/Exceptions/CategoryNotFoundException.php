<?php

declare(strict_types=1);

namespace Src\Category\Domain\Exceptions;

use Exception;

class CategoryNotFoundException extends Exception
{
    public function __construct(string $message = 'Category not found', int $code = 404)
    {
        parent::__construct($message, $code);
    }

    public static function withId(int $id): self
    {
        return new self("Category with ID {$id} was not found", 404);
    }
}
