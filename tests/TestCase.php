<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Las variables de `phpunit.xml` no llegaban a la aplicación.
     *
     * PHPUnit escribe sus `<env force="true">` en `$_ENV` y en `putenv()`, pero **no en
     * `$_SERVER`**. Y `vlucas/phpdotenv` —que es de donde lee el `env()` de Laravel—
     * consulta `$_SERVER` **antes** que `$_ENV`. Así que cualquier variable exportada por
     * `docker-compose` al contenedor ganaba en silencio, aunque `phpunit.xml` dijera lo
     * contrario y lo dijera con `force`.
     *
     * Tres colisionaban, y las tres importan:
     *
     * | Variable | `phpunit.xml` | Contenedor |
     * | :--- | :--- | :--- |
     * | `QUEUE_CONNECTION` | `sync` | `redis` |
     * | `CACHE_STORE` | `array` | `redis` |
     * | `SESSION_DRIVER` | `array` | `redis` |
     *
     * La de la cola es la que rompía cosas: los jobs se encolaban en un redis real en vez
     * de ejecutarse en el acto, así que todo lo que dependía de un job —la proyección del
     * catálogo al marketplace central, el despacho de un pedido central a sus tiendas—
     * simplemente no ocurría durante el test. Y como `ProductObserver` envuelve el encolado
     * en un `catch (Throwable)`, no quedaba ni un error visible: la fila central no aparecía
     * y el test decía «esperaba no-null».
     *
     * Las otras dos son peores de otra manera: caché y sesión compartidas en un redis real
     * entre ejecuciones, que es una fuente de fallos que aparecen y desaparecen solos.
     *
     * Copiar `$_ENV` sobre `$_SERVER` antes de arrancar la aplicación hace que `phpunit.xml`
     * mande, que es lo que se creía que pasaba. Va aquí y no en un bootstrap propio porque
     * `parent::setUp()` es quien construye la aplicación, y la configuración se lee ahí.
     */
    protected function setUp(): void
    {
        foreach ($_ENV as $clave => $valor) {
            $_SERVER[$clave] = $valor;
        }

        parent::setUp();
    }
}
