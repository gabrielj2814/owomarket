<?php


namespace Src\Authentication\Application\Contracts;

interface TenantServices {

    public function consultTenantLoginIsActive(string $slug):array;


}



?>
