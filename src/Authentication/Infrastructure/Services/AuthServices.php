<?php


namespace Src\Authentication\Infrastructure\Services;

use Illuminate\Support\Facades\Log;
use Src\Authentication\Application\Contracts\AuthServices as ContractsAuthServices;
use Src\Authentication\Application\Contracts\Repositories\UserRepositoryInterface;
use Src\Authentication\Domain\Entities\User;
use Src\Authentication\Domain\ValueObjects\UserEmail;

class AuthServices implements ContractsAuthServices {


    public function __construct( protected UserRepositoryInterface $userRepository){}

    public function consultUserByEmail(UserEmail $email): ?User
    {
        return $this->userRepository->consultarPorMail($email);
    }


}



?>
