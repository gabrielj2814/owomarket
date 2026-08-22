<?php

declare(strict_types=1);

namespace Src\Tenant\Infrastructure\Eloquent\Models;

use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * Dominio de una tienda (pendiente P2 / hallazgo N23).
 *
 * La columna `domains.id` es un `uuid`, pero el modelo de Stancl usa los valores por
 * defecto de Eloquent: `$incrementing = true` y `$keyType = 'int'`. Eloquent anade la
 * clave primaria a los casts cuando es autoincremental, asi que **`$domain->id` devolvia
 * siempre `0`**:
 *
 *     raw en BD:  a1b2c3d4-1111-4222-8333-444455556666
 *     id leido:   0   (integer)
 *
 * Con la mayoria de UUID el fallo era silencioso. Pero cuando el UUID empieza por digitos
 * seguidos de `e` —«26e63005-...», ~6% de los casos— PHP lo lee como notacion cientifica,
 * emite «The float-string ... is not representable as an int», Laravel lo convierte en
 * excepcion y **la peticion devuelve 500**. De ahi el test intermitente
 * `AdminPhaseTwoOperationsTest`.
 *
 * Ademas, con la clave a 0, `$domain->save()` sobre un dominio existente generaba
 * `WHERE id = 0`: no afectaba a ninguna fila y no avisaba.
 */
class Domain extends BaseDomain
{
    public $incrementing = false;

    protected $keyType = 'string';
}
