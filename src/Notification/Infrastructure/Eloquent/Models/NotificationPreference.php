<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Si una persona quiere recibir también por correo.
 *
 * Vive en la base central, igual que el buzón y que las tres audiencias que lo usan.
 */
class NotificationPreference extends Model
{
    use HasUuids;

    protected $table = 'notification_preferences';

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'notifiable_type',
        'notifiable_id',
        'email_enabled',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
    ];
}
