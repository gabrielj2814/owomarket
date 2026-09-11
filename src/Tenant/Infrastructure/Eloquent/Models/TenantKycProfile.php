<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * El expediente de identidad de una tienda (subsistema 1).
 *
 * **Este es el UNICO modelo que toca `tenant_kyc_profiles`, y tiene que seguir siendolo.** El
 * cifrado de `cedula` y `rif` se declara aqui, en `$casts`, y Eloquent lo aplica por modelo:
 * un segundo modelo sobre esta misma tabla escribiria texto plano en columnas que todo el
 * mundo da por cifradas. Es exactamente el fallo por el que estos campos no se reutilizaron de
 * `users`, donde ya hay dos modelos compitiendo.
 *
 * Los hashes no se asignan a mano: los mantiene el propio modelo en `saving`, para que sea
 * imposible guardar una cedula sin su hash y que la busqueda por identidad quede coja.
 */
class TenantKycProfile extends Model
{
    use HasUuids;

    protected $table = 'tenant_kyc_profiles';

    /**
     * Mismo valor que el `default` de la columna. Sin esto, un expediente recien creado sin
     * `status` explicito dice `null` en memoria y `pending` en la base: dos verdades sobre la
     * misma fila, que es como se acaba comprobando la equivocada.
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Central, como el resto de lo que vive fuera de la base de una tienda.
     */
    public function getConnectionName()
    {
        if (app()->runningUnitTests() || app()->environment('testing')) {
            return config('database.default');
        }

        return config('tenancy.database.central_connection') ?: 'central';
    }

    protected $fillable = [
        'id',
        'tenant_id',
        'user_id',
        'legal_name',
        'cedula',
        'nationality',
        'rif',
        'phone',
        'address',
        'document_path',
        'status',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
    ];

    /**
     * `cedula_hash` y `rif_hash` quedan FUERA de `$fillable` a proposito: son derivados, y
     * dejar que lleguen desde una peticion permitiria guardar un hash que no corresponde al
     * dato cifrado. Los pone `booted()`.
     */
    protected $casts = [
        // Cifrados en reposo: son los identificadores con los que se suplanta a una persona.
        'cedula' => 'encrypted',
        'rif' => 'encrypted',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Oculta los documentos al serializar.
     *
     * Sin esto, cualquier `return $perfil` de un controlador los devolveria descifrados en el
     * JSON — que es la forma mas tonta de deshacer el cifrado que acabamos de poner. Quien
     * necesite el numero lo pide explicitamente.
     */
    protected $hidden = [
        'cedula',
        'rif',
        'cedula_hash',
        'rif_hash',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $perfil): void {
            // El hash acompaña siempre al dato, o la busqueda por identidad --el bloqueo de
            // reaperturas, que es el valor principal del KYC-- se queda sin nada que mirar.
            $perfil->cedula_hash = self::hashDe($perfil->cedula);
            $perfil->rif_hash = self::hashDe($perfil->rif);
        });
    }

    /**
     * Hash determinista para poder buscar lo que esta cifrado.
     *
     * **Solo los digitos.** «V-12.345.678», «V12345678» y «12345678» son la misma persona, y
     * sin normalizar el bloqueo se esquiva escribiendo el numero de otra forma --que es
     * justamente lo que haria quien intenta reabrir tras una sancion--. La letra de
     * nacionalidad ya vive en su propia columna, asi que en el documento es redundante.
     *
     * Dos documentos de tipo distinto con los mismos digitos --«J-40123456» y «V-40123456»--
     * caen en el mismo hash. Se acepta a proposito: esto **informa** a quien revisa, no bloquea
     * a nadie, y en una ayuda a la decision quedarse corto es peor que pasarse.
     */
    public static function hashDe(?string $documento): ?string
    {
        if ($documento === null || trim($documento) === '') {
            return null;
        }

        $digitos = preg_replace('/\D/', '', $documento) ?? '';

        return $digitos === '' ? null : hash('sha256', $digitos);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
