# Auditoría del entorno Docker

> ## 📌 ESTADO — 23/08/2026
>
> **D1 ✅ · D2 ✅ · D3 ✅ · D4 ✅ · T2 ✅. Entorno verificado ejecutando.**
>
> Origen: al preguntar si convenía trabajar en Docker para usar Redis y Horizon, se
> descubrió que **`docker compose up` nunca había funcionado**. El commit `47c4740`
> («Horizon sobre Redis en Docker y k8s, N40») se escribió sin haberlo levantado jamás.

---

## Resumen

Cuatro fallos encadenados en la infraestructura, tres de ellos bloqueantes, y uno de
configuración de la aplicación que **impedía crear tiendas en cualquier entorno**.

| # | Qué | Bloqueante |
| :--- | :--- | :--- |
| **D1** | El contenedor de MySQL se negaba a arrancar | 🔴 sí |
| **D2** | La contraseña de MySQL no coincidía con la de la aplicación | 🔴 sí |
| **D3** | PHP 8.3 contra un `composer.lock` de 8.4+ | 🔴 sí |
| **D4** | Recompilaba extensiones que la imagen ya trae | 🟡 no, pero 15 min por build |
| **T2** | La conexión `central` estaba escrita a medias: **crear una tienda era imposible** | 🔴 sí, y también fuera de Docker |

---

## D1. 🔴 El contenedor de MySQL se negaba a arrancar

```yaml
MYSQL_USER: ${DB_USERNAME:-owomarket}   # el .env dice DB_USERNAME=root
```

Se resolvía a `MYSQL_USER: root`, y la imagen oficial lo rechaza:

> *MYSQL_USER and MYSQL_PASSWORD are for configuring a regular user and cannot be used for
> the root user*

El contenedor moría en el arranque. **Demostrado:** `docker compose up -d db` y el log.

## D2. 🔴 Credenciales cruzadas

`MYSQL_ROOT_PASSWORD: ${DB_PASSWORD:-root}` se resolvía a `root` porque el `.env` trae la
contraseña vacía —la de Laragon—, mientras la aplicación seguía intentando entrar sin
contraseña. Aunque D1 no existiera, no habría conectado.

**Causa común de D1 y D2:** las credenciales *del contenedor de base de datos* se derivaban
de las *de la aplicación*. Son cosas distintas. Ahora se fijan en el servicio `db` y se le
pasan a la aplicación por `x-entorno-contenedor`, el mismo mecanismo que ya se usaba para
`DB_HOST` — así el `.env` no se toca y seguir con Laragon fuera de Docker sigue funcionando,
que era el objetivo declarado del diseño.

## D3. 🔴 PHP una versión por detrás

El Dockerfile usaba `php:8.3-fpm-alpine` y el `composer.lock` está resuelto contra 8.4+ (el
entorno de desarrollo va en 8.5). El contenedor **construía bien y moría en cada petición**
con `Your Composer dependencies require a PHP version ">= 8.4.0"`. La peor forma de estar
roto: parece que funciona hasta que sirve algo.

Ahora `php:8.5-fpm-alpine`, la versión real del entorno de desarrollo.

## D4. 🟡 Quince minutos de build para nada

`docker-php-ext-install` incluía `mbstring` y `opcache`. **La imagen oficial ya los trae
compilados** — se comprobó con `docker run --rm php:8.4-fpm-alpine php -m`. Cada build los
recompilaba desde el código fuente, y `mbstring` con toda `libmbfl` es con diferencia el más
largo de los ocho. Esa sola línea era la razón de que construir tardara un cuarto de hora.

Y una menor: `dockerfile: Dockerfile` cuando el fichero se llama `dockerfile`. En Windows da
igual; en un CI con Linux revienta.

---

## T2. 🔴 La conexión `central` estaba escrita a medias

**Dónde:** `config/database.php`

```php
'password' => env('CENTRAL_DB_PASSWORD', ''),
// ... resto de configuración
],
```

Un comentario de relleno en lugar del resto de la conexión. Faltaban `charset`, `collation`,
`prefix`, `strict`, `engine` y `options`.

**No era cosmético.** `stancl/tenancy` lee el juego de caracteres de esta conexión para crear
la base de cada tienda, así que emitía:

```sql
CREATE DATABASE `tenant_...` CHARACTER SET `` COLLATE ``
```

que MySQL rechaza con `Unknown character set: ''`.

### Lo que significa

**Crear una tienda era imposible.** El alta de comerciante falla entera y hace rollback. No
es un problema de Docker: la conexión `central` es la misma en Laragon.

Encaja con **S1**, el hallazgo de que el alta redirige a una URL que no existe: esa
redirección nunca se había ejercitado porque **nadie llegaba tan lejos**.

### Demostrado

Antes: `Unknown character set: ''`. Después de completar la conexión, una tienda creada de
verdad, con su base `tenant_..._tenant` y su dominio registrado, y su subdominio sirviendo
**HTTP 200**.

---

## Lo que se verificó ejecutando

| Qué | Resultado |
| :--- | :--- |
| Los cinco contenedores | ✅ `app`, `web`, `db` (sano), `redis`, `horizon` |
| PHP y extensiones | ✅ 8.5.9 · las diez presentes (OPcache se lista como `Zend OPcache`) |
| Base de datos y Redis | ✅ `owomarket_dev` en el host `db` · Redis responde `PONG` |
| Migraciones | ✅ completas |
| **Horizon procesando** | ✅ encolado en `app` (`90909c9c40b6`), **ejecutado en `horizon`** (`5a417b71ddaf`) |
| Dominio central | ✅ HTTP 200 en `/` y en `/auth/login` |
| Creación de tienda | ✅ tenant + base propia + dominio registrado |
| **Subdominio de tienda** | ✅ HTTP 200 |
| Suite completa | ✅ **661 tests** dentro del contenedor |

---

## Correr los tests en Docker: hay una trampa

```bash
docker compose exec -e QUEUE_CONNECTION=sync -e CACHE_STORE=array -e SESSION_DRIVER=array app php artisan test
```

Sin esas tres variables fallan siete tests de `MultiStoreCentralCheckoutTest` **dentro de
Docker y sólo dentro de Docker**, lo cual invita a buscar el fallo en el código, donde no
está.

`phpunit.xml` ya declara esos valores, incluso con `force="true"`. No basta: PHPUnit escribe
en `$_ENV` y con `putenv()`, **no en `$_SERVER`**, y `docker-compose` sí exporta ahí. El
repositorio de variables de Laravel consulta `$_SERVER` primero.

**Y no se puede arreglar desde el código del test.** Se intentó forzar la configuración en
`TestCase::setUp()` y falla por orden de arranque: `RouteServiceProvider::boot()` llama a
`RateLimiter::for()` **durante la creación de la aplicación**, así que el limitador captura
su almacén de caché antes de que ningún `setUp()` pueda cambiarlo. Cambiarlo después deja el
contador vivo en el almacén anterior y aparecen 429 sueltos por toda la suite. El intento
está revertido; queda documentado para que nadie lo repita.

---

## Sobre Kubernetes

**No hace falta para dominios y subdominios**, que era la duda. Docker Compose los sirve por
cabecera `Host` y el nginx que ya había cubre `*.owomarket.local`; el 200 del subdominio de
tienda lo demuestra. Quien resuelve el nombre es el fichero `hosts` de la máquina.

**Los manifiestos de k8s siguen sin verificar**, y conviene asumir que están en el mismo
estado en que estaba el Compose: escritos, no ejecutados. No se tocaron.
