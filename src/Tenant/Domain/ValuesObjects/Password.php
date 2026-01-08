<?php

namespace Src\Tenant\Domain\ValuesObjects;

use InvalidArgumentException;
use Src\Tenant\Domain\Shared\Security\PasswordHasher;
use Src\Tenant\Domain\Shared\Security\PasswordValidator;

final class Password
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 72;

    private string $hash;

    // Constructor PRIVADO - inmutabilidad
    private function __construct(string $hash)
    {
        $this->hash = $hash;
    }

    // Factory method con dependencias inyectadas
    public static function fromPlainText(
        string $plainPassword,
        PasswordValidator $validator,
        PasswordHasher $hasher
    ): self {
        $validator->validate($plainPassword);
        $hash = $hasher->hash($plainPassword);

        return new self($hash);
    }

    // Factory method para reconstruir desde hash
    public static function fromHash(string $hash): self
    {
        if (!self::isValidHash($hash)) {
            throw new InvalidArgumentException('Hash inválido');
        }

        return new self($hash);
    }

    // Método de instancia con dependencia
    public function verify(string $plainPassword, PasswordHasher $hasher): bool
    {
        return $hasher->verify($plainPassword, $this->hash);
    }

    public function needsRehash(PasswordHasheR $hasher): bool
    {
        return $hasher->needsRehash($this->hash);
    }

    // Validación básica de formato (esto SÍ puede estar en dominio)
    private static function isValidHash(string $hash): bool
    {
        return preg_match('/^\$2[aby]\$|\$argon2i\$|\$argon2id\$/', $hash) === 1;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->hash, $other->hash);
    }

    public function __toString(): string
    {
        return '[HASH PROTEGIDO]'; // Por seguridad
    }
}

// use Illuminate\Config\Repository;
// use Illuminate\Hashing\HashManager;
// use Illuminate\Container\Container;
// use InvalidArgumentException;

// /**
//  * Value Object para manejar contraseñas de forma segura
//  *
//  * Esta clase encapsula toda la lógica relacionada con contraseñas, incluyendo
//  * validación, hashing, verificación y gestión de hashes.
//  * Implementa el patrón Value Object garantizando inmutabilidad y validación.
//  */
// final class Password
// {
//     /**
//      * Longitud mínima permitida para contraseñas
//      * @var int
//      */
//     private const MIN_LENGTH = 8;

//     /**
//      * Longitud máxima permitida para contraseñas
//      * BCrypt tiene un límite de 72 bytes para la entrada
//      * @var int
//      */
//     private const MAX_LENGTH = 72;

//     /**
//      * Hash de la contraseña almacenado
//      * @var string
//      */
//     private string $hash;

//     /**
//      * Instancia estática del gestor de hashing
//      * Se comparte entre todas las instancias de Password
//      * @var HashManager
//      */
//     private static HashManager $hashManager;

//     /**
//      * Constructor privado para garantizar la inmutabilidad
//      * Solo se puede crear mediante los métodos factory
//      *
//      * @param string $hash Hash de la contraseña
//      */
//     private function __construct(string $hash)
//     {
//         $this->hash = $hash;
//     }

//     /**
//      * Configura el gestor de hashing para toda la clase
//      *
//      * Este método permite inyectar una instancia configurada de HashManager
//      * que se usará para todas las operaciones de hashing en la clase.
//      * Útil para testing o cuando se quiere usar una configuración específica.
//      *
//      * @param HashManager $hashManager Instancia configurada del gestor de hashing
//      * @return void
//      */
//     public static function setHashManager(HashManager $hashManager): void
//     {
//         self::$hashManager = $hashManager;
//     }

//     /**
//      * Obtiene la instancia del gestor de hashing
//      *
//      * Si no se ha configurado un HashManager, crea uno por defecto
//      * con configuración estándar (BCrypt con 12 rounds).
//      * Implementa el patrón Singleton para el HashManager.
//      *
//      * @return HashManager Instancia del gestor de hashing
//      */
//     private static function getHashManager(): HashManager
//     {
//         if (!isset(self::$hashManager)) {
            // // Configuración por defecto del hashing
            // $config = new Repository([
            //     'hashing' => [
            //         'driver' => 'bcrypt',  // Algoritmo por defecto
            //         'bcrypt' => [
            //             'rounds' => 12,    // Coste computacional (12 es seguro)
            //         ],
            //         'argon' => [           // Configuración alternativa para Argon2
            //             'memory' => 1024,  // Memoria en KB
            //             'threads' => 2,    // Número de hilos
            //             'time' => 2,       // Coste en tiempo
            //         ],
            //     ],
            // ]);

            // // Configurar el contenedor de dependencias
            // $container = new Container;
            // $container->instance('config', $config);

            // // Crear el gestor de hashing
            // self::$hashManager = new HashManager($container);
//         }
//         return self::$hashManager;
//     }

//     /**
//      * Factory method para crear Password desde texto plano
//      *
//      * Crea una nueva instancia de Password a partir de una contraseña en texto plano.
//      * Valida la contraseña según las reglas de negocio y luego genera el hash.
//      * Este es el método principal para crear nuevas contraseñas.
//      *
//      * @param string $password Contraseña en texto plano
//      * @return self Nueva instancia de Password
//      * @throws InvalidArgumentException Si la contraseña no cumple las validaciones
//      */
//     public static function fromPlainText(string $password): self
//     {
//         // Validar reglas de negocio de la contraseña
//         self::validate($password);

//         // Generar hash seguro de la contraseña
//         $hash = self::getHashManager()->make($password);

//         return new self($hash);
//     }

//     /**
//      * Factory method para crear Password desde un hash existente
//      *
//      * Útil para cuando se carga una contraseña desde la base de datos
//      * y se quiere crear el Value Object con el hash almacenado.
//      * Valida que el hash tenga un formato correcto antes de crear la instancia.
//      *
//      * @param string $hash Hash de contraseña previamente generado
//      * @return self Nueva instancia de Password
//      * @throws InvalidArgumentException Si el hash no tiene formato válido
//      */
//     public static function fromHash(string $hash): self
//     {
//         if (!self::isValidHash($hash)) {
//             throw new InvalidArgumentException('El hash proporcionado no es válido');
//         }
//         return new self($hash);
//     }

//     /**
//      * Valida una contraseña según las reglas de negocio
//      *
//      * Realiza las siguientes validaciones:
//      * - Longitud mínima y máxima
//      * - Presencia de mayúsculas
//      * - Presencia de minúsculas
//      * - Presencia de números
//      * - Presencia de caracteres especiales
//      *
//      * @param string $password Contraseña a validar
//      * @return void
//      * @throws InvalidArgumentException Si alguna validación falla
//      */
//     private static function validate(string $password): void
//     {
//         // Validar longitud mínima
//         if (strlen($password) < self::MIN_LENGTH) {
//             throw new InvalidArgumentException(
//                 sprintf('La contraseña debe tener al menos %d caracteres', self::MIN_LENGTH)
//             );
//         }

//         // Validar longitud máxima (límite de BCrypt)
//         if (strlen($password) > self::MAX_LENGTH) {
//             throw new InvalidArgumentException(
//                 sprintf('La contraseña no puede tener más de %d caracteres', self::MAX_LENGTH)
//             );
//         }

//         // Reglas de complejidad de contraseña
//         $rules = [
//             'mayúscula' => '/[A-Z]/',                      // Al menos una letra mayúscula
//             'minúscula' => '/[a-z]/',                      // Al menos una letra minúscula
//             'número' => '/[0-9]/',                         // Al menos un número
//             'carácter especial' => '/[!@#$%^&*()\-_=+{};:,<.>]/' // Al menos un carácter especial
//         ];

//         // Aplicar cada regla de validación
//         foreach ($rules as $tipo => $patron) {
//             if (!preg_match($patron, $password)) {
//                 throw new InvalidArgumentException(
//                     "La contraseña debe contener al menos un $tipo"
//                 );
//             }
//         }
//     }

//     /**
//      * Verifica si una contraseña en texto plano coincide con el hash almacenado
//      *
//      * Utiliza verificación segura contra timing attacks.
//      * Este método es usado típicamente en procesos de login.
//      *
//      * @param string $plainPassword Contraseña en texto plano a verificar
//      * @return bool True si la contraseña coincide, false en caso contrario
//      */
//     public function verify(string $plainPassword): bool
//     {
//         return self::getHashManager()->check($plainPassword, $this->hash);
//     }

//     /**
//      * Determina si el hash necesita ser regenerado
//      *
//      * Esto puede ocurrir si:
//      * - Cambió el algoritmo de hashing por defecto
//      * - Cambiaron los parámetros de coste (como rounds de BCrypt)
//      * - El hash fue creado con un algoritmo obsoleto
//      *
//      * @return bool True si el hash necesita ser actualizado
//      */
//     public function needsRehash(): bool
//     {
//         return self::getHashManager()->needsRehash($this->hash);
//     }

//     /**
//      * Obtiene el hash de la contraseña
//      *
//      * Este método es usado para persistir el hash en la base de datos.
//      * Nunca expone la contraseña en texto plano.
//      *
//      * @return string Hash de la contraseña
//      */
//     public function getHash(): string
//     {
//         return $this->hash;
//     }

//     /**
//      * Valida el formato de un hash
//      *
//      * Verifica que el hash tenga un formato reconocido y válido.
//      * Soporta los siguientes formatos:
//      * - BCrypt: $2a$, $2b$, $2y$
//      * - Argon2i: $argon2i$
//      * - Argon2id: $argon2id$
//      *
//      * @param string $hash Hash a validar
//      * @return bool True si el hash tiene formato válido
//      */
//     private static function isValidHash(string $hash): bool
//     {
//         // Patrón que reconoce múltiples tipos de hash seguros
//         return preg_match('/^\$2[aby]\$|\$argon2i\$|\$argon2id\$/', $hash) === 1;
//     }

//     /**
//      * Representación en string del objeto Password
//      *
//      * Por seguridad, solo devuelve el hash, nunca la contraseña original.
//      * Útil para logging o cuando se necesita representación string.
//      *
//      * @return string Hash de la contraseña
//      */
//     public function __toString(): string
//     {
//         return $this->hash;
//     }
// }
