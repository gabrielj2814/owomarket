<?php

namespace Src\Product\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class);
    }
}

