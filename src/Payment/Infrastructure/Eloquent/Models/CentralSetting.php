<?php

declare(strict_types=1);

namespace Src\Payment\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Ajustes del dominio central. La tabla existia desde 2025 y **no la usaba nadie**.
 */
class CentralSetting extends Model
{
    use HasUuids;

    protected $table = 'central_settings';

    protected $fillable = ['id', 'key', 'value', 'type', 'group'];

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }
}
