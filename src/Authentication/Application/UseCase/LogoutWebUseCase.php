<?php


namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\Repositories\LoginWebRepositoryInterface;
use Src\Authentication\Domain\ValueObjects\Uuid;

class LogoutWebUseCase {

    public function __construct(
        protected LoginWebRepositoryInterface $loginWebRepository
    ){}

    public function execute(Uuid $uuid): bool{
        return $this->loginWebRepository->logoutWebUser($uuid);
    }

}






?>
