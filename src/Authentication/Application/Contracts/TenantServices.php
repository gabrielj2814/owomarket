<?php


namespace Src\Authentication\Application\Contracts;

interface TenantServices {

    public function consultTenantLoginIsActive(string $slug, string $domain):array;


}



?>
