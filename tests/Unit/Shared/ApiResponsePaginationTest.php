<?php

declare(strict_types=1);

use Src\Shared\Helper\ApiResponse;

/*
|--------------------------------------------------------------------------
| Hallazgo N37 — un solo formato de paginacion
|--------------------------------------------------------------------------
|
| Antes convivian SEIS formas distintas en el cable y cada pagina del backoffice estaba
| escrita contra la suya. La deuda de tipos de N29 lo tapaba.
|
| Este test fija el contrato en un sitio. Si alguien lo cambia, se entera aqui y no seis
| meses despues, cuando una pantalla se quede vacia sin explicacion.
*/

test('la respuesta paginada tiene el formato canonico', function () {
    $respuesta = ApiResponse::paginated(
        data: [['id' => 1], ['id' => 2]],
        total: 7,
        currentPage: 2,
        perPage: 2,
        lastPage: 4,
        message: 'ok'
    );

    $cuerpo = json_decode($respuesta->getContent(), true);

    // `data` es SIEMPRE el payload, igual que en las respuestas sin paginar. Que aqui
    // significara otra cosa era la raiz del problema.
    expect($cuerpo['data'])->toBe([['id' => 1], ['id' => 2]]);

    // Los contadores viajan aparte, en la raiz, y con estas cuatro claves exactas.
    expect($cuerpo['pagination'])->toBe([
        'total' => 7,
        'current_page' => 2,
        'per_page' => 2,
        'last_page' => 4,
    ]);

    expect($cuerpo['status'])->toBe('success');
    expect($cuerpo['code'])->toBe(200);
});

test('el sobre paginado no anida la lista dentro de data', function () {
    // El sobre 2 de los que habia: `data: { data: [...], pagination: {...} }`. Volver a
    // caer en el rompe a cualquier consumidor escrito contra el formato canonico.
    $cuerpo = json_decode(
        ApiResponse::paginated(data: [['id' => 1]], total: 1, currentPage: 1, perPage: 10, lastPage: 1)->getContent(),
        true
    );

    expect($cuerpo['data'])->toBeArray()
        ->and($cuerpo['data'][0])->toBe(['id' => 1])
        ->and($cuerpo['data'])->not->toHaveKey('pagination');
});
