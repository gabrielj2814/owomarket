<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature', 'Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Crea un usuario de tienda y lo autentica para el resto del test.
 *
 * Desde la Fase 0.3-E (hallazgo A5) `/api-tenant/*` exige sesion, asi que **33 archivos**
 * repetian el mismo bloque de ocho lineas en su `beforeEach`. Esto es ese bloque.
 *
 * Debe llamarse con la tenancy ya inicializada: el usuario vive en la base del inquilino.
 */
function actingAsTenantOwner(string $type = 'tenant_owner'): Src\Tenant\Infrastructure\Eloquent\Models\User
{
    $user = Src\Tenant\Infrastructure\Eloquent\Models\User::create([
        'id' => (string) Illuminate\Support\Str::uuid(),
        'name' => 'Store Staff',
        'email' => 'staff_'.bin2hex(random_bytes(5)).'@example.com',
        'password' => bcrypt('Password123!'),
        'type' => $type,
    ]);

    test()->actingAs($user);

    return $user;
}
