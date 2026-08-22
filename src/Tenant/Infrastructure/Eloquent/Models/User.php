<?php

namespace Src\Tenant\Infrastructure\Eloquent\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Hallazgo N19: sin `HasRoles` no habia forma de preguntarle a un usuario de tienda
     * que puede hacer, asi que `staff` y `owner` eran indistinguibles para la
     * autorizacion. Las tablas de Spatie viven en la base de CADA inquilino desde la
     * Fase 4.2 (hallazgo F5), asi que el trait resuelve contra la tienda activa sin
     * fijar conexion.
     */
    protected string $guard_name = 'web';

    public $table = 'users';

    public $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'type',
        'phone',
        'avatar',
        'is_active',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    // public function tenantUsers()
    // {
    //     return $this->hasMany(TenantUser::class);
    // }
}
