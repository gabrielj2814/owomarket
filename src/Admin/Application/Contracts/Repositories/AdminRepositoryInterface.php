<?php

namespace Src\Admin\Application\Contracts\Repositories;

use Src\Admin\Domain\Entities\Admin;
use Src\Admin\Domain\ValueObjects\UserStatus;
use Src\Shared\Collection\Pagination;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\Uuid;

interface AdminRepositoryInterface
{
    /**
     * Método create.
     */
    public function create(Admin $admin): ?Admin;

    /**
     * Método consultByUuid.
     */
    public function consultByUuid(Uuid $uuid): ?Admin;

    /**
     * Método consultByEmail.
     */
    public function consultByEmail(UserEmail $email): ?Admin;

    /**
     * Método editar.
     */
    public function editar(Admin $admin): ?Admin;

    /**
     * Método saveProfile (actualiza perfil, avatar, pin y contraseña de cualquier tipo de admin).
     */
    public function saveProfile(Admin $admin): ?Admin;

    /**
     * Método filter.
     */
    public function filter(
        ?string $search,
        ?string $fechaDesdeUTC,
        ?string $fechaHastaUTC,
        ?bool $status,
        int $prePage = 50
    ): Pagination;

    /**
     * Método eliminar.
     */
    public function eliminar(Uuid $uuid): void;

    /**
     * Método changeStatu.
     */
    public function changeStatu(Uuid $uuid, UserStatus $statu): void;
}
