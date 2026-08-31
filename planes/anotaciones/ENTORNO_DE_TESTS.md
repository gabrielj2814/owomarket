# El entorno de los tests no era el que declaraba `phpunit.xml`

> **Resuelto el 30/08/2026.** De 21 tests en rojo a 0, con un cambio de tres líneas.

## El síntoma

Veintiún tests llevaban en rojo desde antes de esta tanda de trabajo, repartidos en cuatro
ficheros y con pinta de cuatro problemas distintos:

| Fichero | Fallos | Cómo se veía |
| :--- | :--- | :--- |
| `MultiStoreCentralCheckoutTest` | 11 | Comisiones y pedidos de tienda que no aparecían |
| `CentralCatalogSyncTest` | 8 | `Attempt to read property "quantity" on null` |
| `ProductMarketplacePublicationTest` | 1 | Igual |
| `StaleRateAlertTest` | 1 | El aviso se repetía |

Los ocho de `CentralCatalogSyncTest` eran en realidad **uno**: `centralRowFor()` devolvía null
porque el producto nunca se proyectaba al catálogo central.

## La causa

PHPUnit escribe sus `<env force="true">` en `$_ENV` y en `putenv()`, **pero no en `$_SERVER`**.
Y `vlucas/phpdotenv` —de donde lee el `env()` de Laravel— consulta **`$_SERVER` antes que
`$_ENV`**.

Así que cualquier variable que `docker-compose` exporte al contenedor gana en silencio, por
mucho que `phpunit.xml` diga lo contrario y lo diga con `force`. Tres colisionaban:

| Variable | `phpunit.xml` | Contenedor | Efecto |
| :--- | :--- | :--- | :--- |
| `QUEUE_CONNECTION` | `sync` | `redis` | Los jobs se encolaban en un redis real en vez de ejecutarse |
| `CACHE_STORE` | `array` | `redis` | Caché compartida entre ejecuciones |
| `SESSION_DRIVER` | `array` | `redis` | Sesiones compartidas entre ejecuciones |

La de la cola es la que rompía. Todo lo que dependía de un job —la proyección del catálogo al
marketplace central, el despacho de un pedido central a sus tiendas— simplemente **no ocurría
durante el test**.

### Por qué costó encontrarlo

Dos capas lo escondían:

1. **`ProductObserver` envuelve el encolado en un `catch (Throwable)`** que registra y sigue.
   Es correcto en producción —abortar una venta porque el marketplace no responde sería peor—
   pero en el test no quedaba ni un error visible: la fila central no aparecía y el test decía
   «esperaba no-null».
2. **El log estaba contaminado.** El contenedor de Horizon corre a la vez y escribe en el mismo
   `storage/logs/laravel.log`, así que los errores que se veían al mirar el log eran del worker
   y no del test. Mandaron a investigar la tenancy de la cola, que no tenía nada que ver.

Lo que lo resolvió fue un test de diagnóstico desechable que imprimía `$_ENV`, `$_SERVER`,
`getenv()` y `env()` de la misma variable, y enseñó los cuatro valores discrepando.

## El arreglo

En `tests/TestCase.php`, antes de que `parent::setUp()` construya la aplicación:

```php
foreach ($_ENV as $clave => $valor) {
    $_SERVER[$clave] = $valor;
}
```

`phpunit.xml` vuelve a mandar, que es lo que todo el mundo creía que pasaba.

## Lo que queda de aviso

**Una variable nueva en `docker-compose` puede volver a pisar `phpunit.xml`** — ahora ya no,
pero el mecanismo que lo permitía sigue siendo el mismo y conviene saberlo antes de perder
otra tarde.

Y el `catch (Throwable)` de `ProductObserver` sigue ahí, con razón. Si algo vuelve a no
sincronizarse sin dar error, ése es el primer sitio donde mirar.

## Lo que había estado pasando por defecto

Con la cola apuntando a un redis real, **los dos jobs del proyecto no se ejecutaban nunca
durante los tests**. Todo lo que dependía de ellos pasaba porque no llegaba a comprobarse.
Arreglado el entorno, se les hizo una pasada propia y aparecieron dos cosas:

**Un fallo real.** `exchange_rate` no estaba en el `$fillable` de `CentralOrder`, así que la
tasa del pedido central se descartaba en silencio. Lo cazó el primer test de la herencia de
tasas en el despacho.

**Seis huecos de cobertura.** Los tests que existían ejercitaban los **casos de uso**, no el
`handle()` de los jobs, que tiene lógica propia:

| Job | Qué no probaba nadie |
| :--- | :--- |
| `DispatchCentralOrderJob` | El `throw` que hace que la cola reintente un despacho parcial |
| | Que **no** lance si la tienda ya agotó sus intentos |
| | Que salga sin gastar reintentos si el pedido central ya no existe |
| `SyncProductToCentralCatalogJob` | Que no reviente si el producto ya no existe al llegarle el turno |
| | Que no reviente si la tienda ya no existe |
| | Que devuelva la tenancy activa a quien lo llamó |

El primero es el que más importa: sin ese `throw`, **un despacho parcial parecería exitoso y
no se reintentaría nunca** — un pedido cobrado que no llega a su tienda, que es exactamente lo
que el docblock del job dice que no puede pasar. Se prueba sin dobles: la tienda se queda sin
existencias entre el pedido y el reintento, que es un motivo de fallo que ocurre de verdad.

El último también es sutil: en modo `sync` el job corre **dentro** de la petición que lo
encoló, así que un `tenancy()->end()` al salir mandaría las escrituras siguientes de esa misma
petición a la base central. Escribiría, pero en el sitio equivocado, y sin dar error.

## Un test propio que se cayó de rebote

`CommissionExchangeRateTest` mezclaba `now()` —zona del servidor— con `RateDate::today()`
—zona de Caracas—, así que «ayer» y «hoy» podían caer en el mismo día y las dos tasas
empataban. **Fallaba o pasaba según la hora a la que se ejecutara.** Ahora usa fechas escritas
a mano.
