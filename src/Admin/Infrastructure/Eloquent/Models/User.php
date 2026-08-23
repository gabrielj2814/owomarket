<?php

namespace Src\Admin\Infrastructure\Eloquent\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * Hallazgo P3: esto era `protected $connection = 'central';`, que fija la conexion a
     * mano mientras los otros 22 modelos centrales leen el config. Eran dos respuestas para
     * la misma pregunta, y hoy coinciden solo porque `DB_DATABASE` y `CENTRAL_DB_DATABASE`
     * apuntan al mismo sitio.
     *
     * Ademas hacia INTESTABLE todo lo que pasara por aqui: en pruebas iba a MySQL mientras
     * el resto de la suite corre en sqlite. Por eso ninguna prueba cubria las rutas de
     * perfil de administrador, y por eso el hallazgo P2 sobrevivio dos auditorias.
     */
    public function getConnectionName()
    {
        if (app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    public $table = 'users';

    public $primaryKey = 'id';

    public $incrementing = false;

    protected $ketType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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

    // public function tenants(): BelongsToMany
    // {
    //     return $this->belongsToMany(Tenant::class, 'tenant_users')
    //         ->withPivot(['role', 'permissions'])
    //         ->withTimestamps();
    // }

    // public function tenantUsers()
    // {
    //     return $this->hasMany(TenantUser::class);
    // }
}
