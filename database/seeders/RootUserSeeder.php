<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Src\Admin\Domain\Shared\Security\PasswordHasher;
use Src\Admin\Domain\Shared\Security\PasswordValidator;
use Src\Admin\Domain\ValueObjects\Password;

class RootUserSeeder extends Seeder
{

    public function __construct(
        protected PasswordValidator $validator,
        protected PasswordHasher $hasher
    ){}

    public function run(): void
    {
        $password = env('USER_PASSWORD_DEV', '12345678');

        // root@owomarket.local
        User::updateOrCreate(
            ['email' => 'root@owomarket.local'],
            [
                'id' =>   Str::uuid()->toString(),
                'name' => 'Root',
                'type' => 'super_admin',
                'avatar' => 'https://i.pinimg.com/736x/d4/e7/55/d4e755d2cf5476ef130b7bdc1d78de4e.jpg',
                'password' => Password::fromPlainText($password, $this->validator, $this->hasher)->getHash(),
            ]
        );
    }
}

