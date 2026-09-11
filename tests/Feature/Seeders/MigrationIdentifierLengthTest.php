<?php

declare(strict_types=1);

/**
 * Que ninguna migracion genere un identificador de mas de 64 caracteres.
 *
 * **Esto ya ha roto el proyecto dos veces**, y las dos de la misma forma
 * desagradable: MySQL rechaza el `ALTER TABLE ... ADD INDEX` con el error 1059
 * («Identifier name is too long»), pero como el DDL de MySQL **no es
 * transaccional**, la tabla se queda creada y la migracion sin registrar. El
 * siguiente intento choca con «Table already exists» y esconde el error real, asi
 * que se depura el sintoma equivocado.
 *
 * Y no lo detecta nada: **la suite corre sobre SQLite, que no tiene ese limite.**
 * Una migracion puede pasar los 765 tests y reventar la primera vez que alguien la
 * aplica de verdad. Este test es lo unico que cubre esa grieta.
 *
 * Casos conocidos:
 *   - `central_home_banners_position_type_is_active_order_position_index` (67)
 *   - `order_delivery_confirmations_declared_delivered_at_released_at_index` (68)
 *
 * El arreglo siempre es el mismo: pasarle un nombre corto explicito al indice,
 * `$table->index([...], 'idx_algo_corto')`.
 */
const LIMITE_MYSQL = 64;

/**
 * Nombre que Laravel generaria para un indice sin nombre explicito.
 *
 * Replica `Blueprint::createIndexName()`: tabla, columnas y tipo unidos por guion
 * bajo, todo en minusculas.
 *
 * @param  array<int, string>  $columnas
 */
function nombreAutogenerado(string $tabla, array $columnas, string $tipo): string
{
    return strtolower($tabla.'_'.implode('_', $columnas).'_'.$tipo);
}

/**
 * @return array<int, array{fichero: string, nombre: string, longitud: int}>
 */
function indicesDemasiadoLargos(): array
{
    $ficheros = array_merge(
        glob(base_path('database/migrations/*.php')) ?: [],
        glob(base_path('database/migrations/tenant/*.php')) ?: [],
    );

    $hallazgos = [];

    foreach ($ficheros as $fichero) {
        $codigo = file_get_contents($fichero);

        // `index([...])` y `unique([...])` SIN segundo argumento: son los unicos que
        // dejan que Laravel invente el nombre. Con nombre explicito no hay problema.
        preg_match_all(
            '/\$table->(index|unique)\(\s*\[([^\]]+)\]\s*\)/',
            $codigo,
            $coincidencias,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        foreach ($coincidencias as $coincidencia) {
            $posicion = $coincidencia[0][1];

            // La tabla es la del ultimo `Schema::create`/`Schema::table` que aparece
            // antes de este indice. Un fichero puede tocar varias tablas.
            preg_match_all(
                '/Schema::(?:create|table)\(\s*[\'"]([^\'"]+)[\'"]/',
                substr($codigo, 0, $posicion),
                $tablas
            );

            if ($tablas[1] === []) {
                continue;
            }

            $tabla = end($tablas[1]);
            $columnas = array_map(
                fn (string $c) => trim($c, " \t\n\r'\""),
                explode(',', $coincidencia[2][0])
            );

            $nombre = nombreAutogenerado($tabla, array_filter($columnas), $coincidencia[1][0]);

            if (strlen($nombre) > LIMITE_MYSQL) {
                $hallazgos[] = [
                    'fichero' => basename($fichero),
                    'nombre' => $nombre,
                    'longitud' => strlen($nombre),
                ];
            }
        }
    }

    return $hallazgos;
}

it('ninguna migración genera un identificador que MySQL rechace', function () {
    $largos = indicesDemasiadoLargos();

    $detalle = implode("\n", array_map(
        fn (array $h) => "  {$h['fichero']}: «{$h['nombre']}» ({$h['longitud']} caracteres)",
        $largos
    ));

    expect($largos)->toBe([], $largos === [] ? '' : sprintf(
        "Estos índices generarían un nombre de más de %d caracteres y MySQL los rechazará ".
        "con el error 1059:\n%s\n\nDales un nombre corto explícito: \$table->index([...], 'idx_corto').",
        LIMITE_MYSQL,
        $detalle
    ));
});

it('el detector reconoce un nombre demasiado largo', function () {
    // Sin esto, el test de arriba pasaria igual de verde si el detector estuviera roto y no
    // encontrara nunca nada -- que es la forma mas comun de que una guardia no guarde.
    $nombre = nombreAutogenerado(
        'order_delivery_confirmations',
        ['declared_delivered_at', 'released_at'],
        'index'
    );

    expect(strlen($nombre))->toBeGreaterThan(LIMITE_MYSQL);
});
