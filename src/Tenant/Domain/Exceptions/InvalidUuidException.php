<?php

namespace Src\Tenant\Domain\Exceptions;

use DomainException;

class InvalidUuidException extends DomainException {
   protected $message = 'The provided UUID is invalid.';

    public function __construct(?string $message = null, ?string $uuid = null) {
        if ($message) {
            $this->message = $message;
        } elseif ($uuid) {
            $this->message = "The provided UUID '{$uuid}' is invalid.";
        }

        parent::__construct($this->message, 400);
    }

}


?>
