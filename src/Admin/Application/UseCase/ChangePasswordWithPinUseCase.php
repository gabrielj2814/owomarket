<?php

namespace Src\Admin\Application\UseCase;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Shared\Domain\Contracts\PasswordHasher;
use Src\Shared\Domain\Contracts\PasswordValidator;
use Src\Shared\Domain\ValueObjects\Password;
use Src\Shared\Domain\ValueObjects\Uuid;

class ChangePasswordWithPinUseCase
{
    private AdminRepositoryInterface $repository;

    private PasswordValidator $validator;

    private PasswordHasher $hasher;

    public function __construct(
        AdminRepositoryInterface $repository,
        PasswordValidator $validator,
        PasswordHasher $hasher
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->hasher = $hasher;
    }

    /**
     * Numero de PIN erroneos que queman el PIN y obligan a pedir uno nuevo.
     */
    private const MAX_FAILED_ATTEMPTS = 3;

    /**
     * @param  string  $uuid  UUID del administrador **en sesion**. El controlador ya no
     *                        pasa el de la URL: ese era el hallazgo A7.
     */
    public function execute(string $uuid, string $pinInput, string $newPassword, string $newPasswordConfirmation): void
    {
        if ($newPassword !== $newPasswordConfirmation) {
            throw new InvalidArgumentException('La confirmación de la contraseña no coincide');
        }

        $adminUuid = Uuid::make($uuid);
        $admin = $this->repository->consultByUuid($adminUuid);

        if (! $admin) {
            throw new InvalidArgumentException('Administrador no encontrado');
        }

        if (! $admin->verifySecurityPin($pinInput)) {
            // Hallazgo A7: un PIN erroneo no invalidaba nada ni incrementaba ningun
            // contador, asi que se podia probar una combinacion tras otra sobre el mismo
            // PIN durante los 15 minutos de validez. A los tres fallos se quema.
            $intentos = $this->registerFailedAttempt($adminUuid->value());

            if ($intentos >= self::MAX_FAILED_ATTEMPTS) {
                $admin->clearSecurityPin();
                $this->repository->saveProfile($admin);
                $this->forgetFailedAttempts($adminUuid->value());

                throw new InvalidArgumentException(
                    'Demasiados intentos fallidos. El PIN ha sido invalidado; solicita uno nuevo.'
                );
            }

            throw new InvalidArgumentException('El PIN de seguridad es incorrecto o ha expirado');
        }

        $this->forgetFailedAttempts($adminUuid->value());

        $passwordVO = Password::fromPlainText(
            $newPassword,
            $this->validator,
            $this->hasher
        );

        $admin->changePassword($passwordVO);
        $admin->clearSecurityPin();

        $savedAdmin = $this->repository->saveProfile($admin);

        if (! $savedAdmin) {
            throw new InvalidArgumentException('Error al actualizar la contraseña');
        }
    }

    /**
     * El contador vive en cache y no en una columna: el PIN caduca a los 15 minutos, asi
     * que el contador tampoco tiene por que sobrevivir mas.
     */
    private function registerFailedAttempt(string $adminId): int
    {
        $key = $this->attemptsKey($adminId);
        $intentos = ((int) Cache::get($key, 0)) + 1;

        Cache::put($key, $intentos, now()->addMinutes(15));

        return $intentos;
    }

    private function forgetFailedAttempts(string $adminId): void
    {
        Cache::forget($this->attemptsKey($adminId));
    }

    private function attemptsKey(string $adminId): string
    {
        return "admin_pin_attempts:{$adminId}";
    }
}
