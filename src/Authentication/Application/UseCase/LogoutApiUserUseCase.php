<?php


namespace Src\Authentication\Application\UseCase;

use Src\Authentication\Application\Contracts\Repositories\PersonalAccessTokenRepositoryInterface;

class LogoutApiUserUseCase {


    public function __construct(protected PersonalAccessTokenRepositoryInterface $personalAccessTokenRepository){}

    public function execute(string $tokenId): void {

        $this->personalAccessTokenRepository->deleteToken($tokenId);

    }


}


?>
