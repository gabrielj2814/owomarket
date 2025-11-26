<?php


namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\Repositories\LoginWebRepositoryInterface;

class LogoutWebUseCase {

    public function __construct(
        protected LoginWebRepositoryInterface $loginWebRepository
    ){}

    public function execute(): void{
        $this->loginWebRepository->logoutWebUser();
    }

}






?>
