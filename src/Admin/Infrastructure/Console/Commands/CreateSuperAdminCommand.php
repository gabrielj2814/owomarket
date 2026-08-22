<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\ValueObjects\Password;
use Src\User\Infrastructure\Eloquent\Models\User;
use Throwable;

/**
 * Crea el primer superadministrador de la plataforma (pendiente P1 / hallazgo N22).
 *
 * La Fase 2.1 (hallazgo F6) veto `RootUserSeeder` fuera de local y testing, porque creaba
 * `root@owomarket.local` con la contrasena de desarrollo y ademas se la reseteaba si el
 * usuario ya existia. Eso cerraba el agujero, pero dejaba a una **instalacion nueva sin
 * ningun camino** para crear el superadmin inicial.
 *
 * Este comando es ese camino. Deliberadamente:
 *
 *   - La contrasena se pide por consola en modo oculto y NO se acepta por argumento: un
 *     `--password=` acabaria en el historial del shell y en los logs de despliegue.
 *   - Se valida con `PasswordValidator`, el mismo del resto del sistema.
 *   - **Se niega a sobrescribir un usuario existente.** Resetear contrasenas ajenas era
 *     justo lo que hacia mal el seeder.
 */
final class CreateSuperAdminCommand extends Command
{
    protected $signature = 'admin:create-super
                            {--name= : Nombre del administrador}
                            {--email= : Correo del administrador}';

    protected $description = 'Crea el primer superadministrador de la plataforma de forma interactiva';

    public function handle(PasswordValidator $validator, PasswordHasher $hasher): int
    {
        $name = (string) ($this->option('name') ?: $this->ask('Nombre del administrador'));
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Correo electrónico'))));

        if ($name === '' || $email === '') {
            $this->error('El nombre y el correo son obligatorios.');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("«{$email}» no es un correo electrónico válido.");

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("Ya existe un usuario con el correo «{$email}».");
            $this->line('Este comando no sobrescribe cuentas existentes a propósito: cambiar la contraseña');
            $this->line('de otra persona desde la consola era justo lo que hacía mal el seeder de desarrollo.');

            return self::FAILURE;
        }

        $plainPassword = (string) $this->secret('Contraseña');

        if ($plainPassword !== (string) $this->secret('Confirma la contraseña')) {
            $this->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        try {
            $password = Password::fromPlainText($plainPassword, $validator, $hasher);
        } catch (Throwable $e) {
            $this->error('La contraseña no cumple los requisitos: '.$e->getMessage());

            return self::FAILURE;
        }

        // `type` se asigna aparte porque ya no es asignable en masa (tarea 3 de la
        // auditoria). Aqui es donde se decide que alguien es superadministrador, y conviene
        // que se vea.
        $user = new User([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'email' => $email,
            'is_active' => true,
            'password' => $password->getHash(),
        ]);
        $user->type = 'super_admin';
        $user->save();

        $this->info("✅ Superadministrador «{$name}» creado con el correo {$email}.");

        return self::SUCCESS;
    }
}
