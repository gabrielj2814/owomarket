<?php

namespace Src\Shared\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;

final class Password
{
    private string $hash;

    private function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    public static function fromPlainText(
        string $plainPassword,
        PasswordValidator $validator,
        PasswordHasher $hasher
    ): self {
        $validator->validate($plainPassword);
        $hash = $hasher->hash($plainPassword);

        return new self($hash);
    }

    public static function fromHash(string $hash): self
    {
        if (! self::isValidHash($hash)) {
            throw new InvalidArgumentException('Hash inválido', 400);
        }

        return new self($hash);
    }

    public function verify(string $plainPassword, PasswordHasher $hasher): bool
    {
        return $hasher->verify($plainPassword, $this->hash);
    }

    public function needsRehash(PasswordHasher $hasher): bool
    {
        return $hasher->needsRehash($this->hash);
    }

    private static function isValidHash(string $hash): bool
    {
        return preg_match('/^\$2[aby]\$|\$argon2i\$|\$argon2id\$/', $hash) === 1;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->hash, $other->hash);
    }

    public function __toString(): string
    {
        return '[HASH PROTEGIDO]';
    }
}
