<?php



namespace Src\Admin\Infrastructure\Http\Data;

use Spatie\LaravelData\Data;

class ChangeStatuAdminData extends Data {


    public function __construct(
        public string $id,
        public bool   $statu,
    ){}


}



?>
