<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'support_tickets';

    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'ticket_number',
        'requester_type',
        'user_id',
        'tenant_id',
        'category',
        'priority',
        'status',
        'subject',
        'description',
        'attachments',
        'metadata',
        'last_reply_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'last_reply_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('created_at', 'asc');
    }
}
