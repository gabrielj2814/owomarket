<?php


namespace App\Modules\Core\Auth\Contracts;

use App\Modules\Core\Auth\Data\AurhCredencialesData;

interface Auth
{
    //

    public function login(AurhCredencialesData $credentials): bool;
    public function logout(): void;
    public function loginApi(AurhCredencialesData $credentials): ?string;
    public function logoutApi(string $token): void;
    // public function logout(): void;

}


?>
