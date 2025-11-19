<?php


namespace Src\Authentication\Domain\Exceptions;

use DomainException;

class InvalidIdException extends DomainException {
    protected $message = 'The provided ID is invalid.';

    public function __construct(?string $message = null) {
        if ($message) {
            $this->message = $message;
        }
        parent::__construct($this->message);
    }
}


?>
