<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Src\Product\Infrastructure\Http\Request\CreateProductFormRequest;
use Src\Product\Infrastructure\Http\Request\EditProductFormRequest;

/**
 * Subsistema 2: el plazo de garantia entra por un formulario, asi que la regla que lo filtra
 * es la que decide si el fondo de garantia (subsistema 4) recibira un numero con sentido o
 * basura.
 *
 * Lo que se protege aqui es sobre todo **que `0` no pase**. Cero dias de garantia es no tener
 * garantia, igual que `null`, y en cuanto existan dos formas de decir lo mismo alguien
 * comparara con `> 0` en un sitio y con `!== null` en otro. Es el patron que este repositorio
 * ya ha pagado tres veces.
 */
function validarGarantia(mixed $valor, string $request = CreateProductFormRequest::class): bool
{
    $reglas = (new $request)->rules();

    return ! Validator::make(['warranty_days' => $valor], [
        'warranty_days' => $reglas['warranty_days'],
    ])->fails();
}

test('un producto puede no tener garantía', function () {
    expect(validarGarantia(null))->toBeTrue();
});

test('acepta un plazo de garantía normal', function () {
    expect(validarGarantia(90))->toBeTrue();
    expect(validarGarantia(365))->toBeTrue();
});

test('rechaza cero días: eso es no tener garantía, y ya se dice con null', function () {
    expect(validarGarantia(0))->toBeFalse();
});

test('rechaza un plazo negativo', function () {
    expect(validarGarantia(-30))->toBeFalse();
});

test('rechaza un plazo absurdo', function () {
    // Diez años es el tope. Sin el, un dedo torpe puede dejar el dinero de una venta retenido
    // durante siglos cuando el subsistema 4 lea este campo.
    expect(validarGarantia(3651))->toBeFalse();
});

test('rechaza texto', function () {
    expect(validarGarantia('para siempre'))->toBeFalse();
});

test('la regla es la misma al crear que al editar', function () {
    // Dos reglas distintas para el mismo campo es como se cuela por la puerta de atras lo que
    // se bloqueo en la principal.
    foreach ([null, 90, 0, -30, 3651] as $valor) {
        expect(validarGarantia($valor, EditProductFormRequest::class))
            ->toBe(validarGarantia($valor, CreateProductFormRequest::class));
    }
});
