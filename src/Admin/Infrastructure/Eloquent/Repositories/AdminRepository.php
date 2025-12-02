<?php


namespace Src\Admin\Infrastructure\Eloquent\Repositories;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Etities\Admin;
use Src\Admin\Infrastructure\Eloquent\Models\User as AdminModel;

class AdminRepository implements AdminRepositoryInterface {



    public function create(Admin $admin): ?Admin
    {
        $reacord= new AdminModel();
        $reacord->id=$admin->getId()->value();
        $reacord->name=$admin->getName()->value();
        $reacord->email=$admin->getEmail()->value();
        $reacord->password=$admin->getPassword()->getHash();
        $reacord->type=$admin->getType()->value();
        $reacord->phone=$admin->getPhone()->value();
        $reacord->avatar=$admin->getAvatar()->value();
        $reacord->is_active=$admin->isActive();
        $reacord->created_at=$admin->getCreatedAt()->value();
        $reacord->updated_at=$admin->getUpdatedAt()->value();
        $reacord->save();

        return $admin;
    }


}



?>
