<?php

namespace Src\Tenant\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    use HasFactory;

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

    public $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
