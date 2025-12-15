<?php

declare(strict_types=1);

namespace Src\Shared\ValuesObjects;

use InvalidArgumentException;

/**
 * Currency Value Object
 *
 * Representa una moneda válida según el estándar ISO 4217.
 * Garantiza que todas las monedas en el sistema sean válidas y proporciona
 * métodos útiles para operaciones con monedas en un marketplace multitienda.
 *
 * @package Src\Shared\ValuesObjects;
 * @version 1.0.0
 */
class Currency
{
    /**
     * Constructor privado para forzar uso del método factory.
     *
     * @param string $code Código ISO 4217 de la moneda (3 letras)
     * @param string $name Nombre completo de la moneda
     * @param string $symbol Símbolo de la moneda (ej: '$', '€')
     * @param int $decimals Número de decimales estándar
     *
     * @internal Usar Currency::make() o métodos estáticos específicos
     */
    private function __construct(
        private string $code,
        private string $name,
        private string $symbol,
        private int $decimals
    ) {}

    /**
     * Factory method principal para crear una moneda por su código ISO 4217.
     *
     * @param string $currencyCode Código de moneda ISO 4217 (ej: 'USD', 'EUR')
     * @return self Instancia de Currency
     *
     * @throws InvalidArgumentException Si el código de moneda no es válido
     *
     * @example
     * $usd = Currency::make('USD');
     * $eur = Currency::make('EUR');
     */
    public static function make(string $currencyCode): self
    {
        $currencyCode = strtoupper(trim($currencyCode));
        self::ensureIsValidCurrency($currencyCode);

        $currencyData = self::getCurrencyData($currencyCode);

        return new self(
            $currencyCode,
            $currencyData['name'],
            $currencyData['symbol'],
            $currencyData['decimals']
        );
    }

    /**
     * Crea una instancia de Currency para Dólar Estadounidense.
     *
     * @return self Moneda USD
     *
     * @example
     * $usd = Currency::usd();
     */
    public static function usd(): self
    {
        return self::make('USD');
    }

    /**
     * Crea una instancia de Currency para Euro.
     *
     * @return self Moneda EUR
     *
     * @example
     * $eur = Currency::eur();
     */
    public static function eur(): self
    {
        return self::make('EUR');
    }

    /**
     * Crea una instancia de Currency para Bolívar Venezolano.
     *
     * @return self Moneda VES
     *
     * @example
     * $ves = Currency::ves();
     */
    public static function ves(): self
    {
        return self::make('VES');
    }

    /**
     * Crea una instancia de Currency para Peso Mexicano.
     *
     * @return self Moneda MXN
     *
     * @example
     * $mxn = Currency::mxn();
     */
    public static function mxn(): self
    {
        return self::make('MXN');
    }

    /**
     * Crea una instancia de Currency para Peso Colombiano.
     *
     * @return self Moneda COP
     *
     * @example
     * $cop = Currency::cop();
     */
    public static function cop(): self
    {
        return self::make('COP');
    }

    /**
     * Crea una instancia de Currency para Real Brasileño.
     *
     * @return self Moneda BRL
     *
     * @example
     * $brl = Currency::brl();
     */
    public static function brl(): self
    {
        return self::make('BRL');
    }

    /**
     * Crea una instancia de Currency para Sol Peruano.
     *
     * @return self Moneda PEN
     *
     * @example
     * $pen = Currency::pen();
     */
    public static function pen(): self
    {
        return self::make('PEN');
    }

    /**
     * Crea una instancia de Currency para Peso Argentino.
     *
     * @return self Moneda ARS
     *
     * @example
     * $ars = Currency::ars();
     */
    public static function ars(): self
    {
        return self::make('ARS');
    }

    /**
     * Crea una instancia de Currency para Peso Chileno.
     *
     * @return self Moneda CLP
     *
     * @example
     * $clp = Currency::clp();
     */
    public static function clp(): self
    {
        return self::make('CLP');
    }

    /**
     * Obtiene el código ISO 4217 de la moneda.
     *
     * @return string Código de 3 letras (ej: 'USD', 'EUR')
     *
     * @example
     * $eur = Currency::make('EUR');
     * echo $eur->code(); // 'EUR'
     */
    public function code(): string
    {
        return $this->code;
    }

    /**
     * Obtiene el nombre completo de la moneda.
     *
     * @return string Nombre de la moneda
     *
     * @example
     * $usd = Currency::make('USD');
     * echo $usd->name(); // 'United States Dollar'
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Obtiene el símbolo de la moneda.
     *
     * @return string Símbolo (ej: '$', '€', '¥')
     *
     * @example
     * $eur = Currency::make('EUR');
     * echo $eur->symbol(); // '€'
     */
    public function symbol(): string
    {
        return $this->symbol;
    }

    /**
     * Obtiene el número de decimales estándar para esta moneda.
     *
     * @return int Número de decimales (generalmente 2, pero hay excepciones)
     *
     * @example
     * $jpy = Currency::make('JPY');
     * echo $jpy->decimals(); // 0 (el Yen no tiene decimales)
     */
    public function decimals(): int
    {
        return $this->decimals;
    }

    /**
     * Formatea un monto según las convenciones de esta moneda.
     *
     * @param float|int|string $amount Monto a formatear
     * @param bool $showSymbol Si se debe mostrar el símbolo
     * @param string|null $locale Locale para formato (ej: 'en_US', 'es_ES')
     * @return string Monto formateado
     *
     * @example
     * $usd = Currency::make('USD');
     * echo $usd->format(1234.56); // '$1,234.56'
     * echo $usd->format(1234.56, false); // '1,234.56'
     */
    public function format(
        float|int|string $amount,
        bool $showSymbol = true,
        ?string $locale = null
    ): string {
        $amount = $this->normalizeAmount($amount);
        $locale = $locale ?? 'en_US';

        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        if (!$showSymbol) {
            $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, '');
        }

        return $formatter->formatCurrency($amount, $this->code);
    }

    /**
     * Formatea un monto de manera simple (símbolo + número).
     *
     * @param float|int|string $amount Monto a formatear
     * @return string Monto formateado simplemente
     *
     * @example
     * $eur = Currency::make('EUR');
     * echo $eur->formatSimple(1234.56); // '€1,234.56'
     */
    public function formatSimple(float|int|string $amount): string
    {
        $amount = $this->normalizeAmount($amount);
        $formatted = number_format(
            $amount,
            $this->decimals,
            '.',
            ','
        );

        return $this->symbol . $formatted;
    }

    /**
     * Convierte un monto de esta moneda a otra moneda.
     *
     * @param float|int|string $amount Monto a convertir
     * @param Currency $targetCurrency Moneda destino
     * @param float $exchangeRate Tasa de cambio (1 unidad de esta moneda = X unidades de destino)
     * @return float Monto convertido
     *
     * @example
     * $usd = Currency::make('USD');
     * $eur = Currency::make('EUR');
     * $converted = $usd->convert(100, $eur, 0.85); // 85.00
     */
    public function convert(
        float|int|string $amount,
        Currency $targetCurrency,
        float $exchangeRate
    ): float {
        $amount = $this->normalizeAmount($amount);
        return round($amount * $exchangeRate, $targetCurrency->decimals());
    }

    /**
     * Verifica si esta moneda es la misma que otra.
     *
     * @param Currency $other Otra moneda a comparar
     * @return bool True si son la misma moneda, false en caso contrario
     *
     * @example
     * $usd1 = Currency::make('USD');
     * $usd2 = Currency::usd();
     * $eur = Currency::make('EUR');
     *
     * $usd1->equals($usd2); // true
     * $usd1->equals($eur);  // false
     */
    public function equals(Currency $other): bool
    {
        return $this->code === $other->code();
    }

    /**
     * Magic method para conversión a string.
     *
     * @return string Código de la moneda
     *
     * @example
     * $currency = Currency::make('EUR');
     * echo "Moneda: $currency"; // "Moneda: EUR"
     */
    public function __toString(): string
    {
        return $this->code;
    }

    /**
     * Obtiene todas las monedas soportadas por el sistema.
     *
     * @return array Lista de códigos ISO 4217 soportados
     *
     * @example
     * $supported = Currency::supportedCurrencies();
     * // ['USD', 'EUR', 'VES', 'MXN', 'COP', 'BRL', ...]
     */
    public static function supportedCurrencies(): array
    {
        return array_keys(self::currenciesData());
    }

    /**
     * Verifica si un código de moneda es válido.
     *
     * @param string $currencyCode Código de moneda a validar
     * @return bool True si es válido, false en caso contrario
     *
     * @example
     * Currency::isValid('USD'); // true
     * Currency::isValid('XYZ'); // false
     */
    public static function isValid(string $currencyCode): bool
    {
        $currencyCode = strtoupper(trim($currencyCode));

        try {
            self::ensureIsValidCurrency($currencyCode);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Obtiene monedas por región geográfica.
     *
     * @param string $region Región (america, europe, asia, etc.)
     * @return array Monedas de esa región
     *
     * @example
     * $americanCurrencies = Currency::byRegion('america');
     * // ['USD', 'CAD', 'MXN', 'BRL', 'ARS', ...]
     */
    public static function byRegion(string $region): array
    {
        $regions = [
            'america' => ['USD', 'CAD', 'MXN', 'BRL', 'ARS', 'CLP', 'COP', 'PEN', 'VES'],
            'europe' => ['EUR', 'GBP', 'CHF', 'NOK', 'SEK', 'DKK', 'PLN', 'CZK', 'HUF'],
            'asia' => ['JPY', 'CNY', 'INR', 'KRW', 'SGD', 'HKD', 'TWD', 'THB', 'MYR'],
            'middle_east' => ['AED', 'SAR', 'QAR', 'OMR', 'KWD', 'BHD', 'ILS'],
            'oceania' => ['AUD', 'NZD'],
            'africa' => ['ZAR', 'EGP', 'NGN', 'KES', 'GHS', 'MAD'],
        ];

        return $regions[$region] ?? [];
    }

    /**
     * Normaliza un monto a float.
     *
     * @param float|int|string $amount Monto a normalizar
     * @return float Monto normalizado
     *
     * @throws InvalidArgumentException Si el monto no es numérico
     *
     * @internal Método privado para uso interno
     */
    private function normalizeAmount(float|int|string $amount): float
    {
        if (is_string($amount)) {
            // Remover caracteres no numéricos excepto punto y coma
            $amount = preg_replace('/[^\d\.\-]/', '', $amount);

            // Reemplazar coma decimal por punto si es necesario
            $amount = str_replace(',', '.', $amount);
        }

        if (!is_numeric($amount)) {
            throw new InvalidArgumentException(
                "El monto debe ser numérico. Valor recibido: " . (string) $amount
            );
        }

        return (float) $amount;
    }

    /**
     * Valida que un código de moneda sea válido.
     *
     * @param string $currencyCode Código de moneda a validar
     * @return void
     *
     * @throws InvalidArgumentException Si el código de moneda no es válido
     *
     * @internal Método privado para uso interno
     */
    private static function ensureIsValidCurrency(string $currencyCode): void
    {
        if (strlen($currencyCode) !== 3) {
            throw new InvalidArgumentException(
                "El código de moneda debe tener 3 letras. Valor recibido: '{$currencyCode}'"
            );
        }

        if (!ctype_alpha($currencyCode)) {
            throw new InvalidArgumentException(
                "El código de moneda debe contener solo letras. Valor recibido: '{$currencyCode}'"
            );
        }

        $currencies = self::currenciesData();

        if (!isset($currencies[$currencyCode])) {
            $supported = implode(', ', array_slice(array_keys($currencies), 0, 10));
            throw new InvalidArgumentException(
                "El código de moneda '{$currencyCode}' no es válido o no está soportado. " .
                "Ejemplos válidos: {$supported}..."
            );
        }
    }

    /**
     * Obtiene los datos de una moneda específica.
     *
     * @param string $currencyCode Código de moneda
     * @return array Datos de la moneda
     *
     * @internal Método privado para uso interno
     */
    private static function getCurrencyData(string $currencyCode): array
    {
        $currencies = self::currenciesData();
        return $currencies[$currencyCode];
    }

    /**
     * Base de datos interna de monedas soportadas.
     *
     * @return array Array asociativo con datos de todas las monedas soportadas
     *
     * @internal Método privado para uso interno
     */
    private static function currenciesData(): array
    {
        return [
            'USD' => [
                'name' => 'United States Dollar',
                'symbol' => '$',
                'decimals' => 2,
                'country' => 'United States',
            ],
            'EUR' => [
                'name' => 'Euro',
                'symbol' => '€',
                'decimals' => 2,
                'country' => 'European Union',
            ],
            'VES' => [
                'name' => 'Venezuelan Bolívar',
                'symbol' => 'Bs.',
                'decimals' => 2,
                'country' => 'Venezuela',
            ],
            'MXN' => [
                'name' => 'Mexican Peso',
                'symbol' => '$',
                'decimals' => 2,
                'country' => 'Mexico',
            ],
            'COP' => [
                'name' => 'Colombian Peso',
                'symbol' => '$',
                'decimals' => 2,
                'country' => 'Colombia',
            ],
            'BRL' => [
                'name' => 'Brazilian Real',
                'symbol' => 'R$',
                'decimals' => 2,
                'country' => 'Brazil',
            ],
            'PEN' => [
                'name' => 'Peruvian Sol',
                'symbol' => 'S/',
                'decimals' => 2,
                'country' => 'Peru',
            ],
            'ARS' => [
                'name' => 'Argentine Peso',
                'symbol' => '$',
                'decimals' => 2,
                'country' => 'Argentina',
            ],
            'CLP' => [
                'name' => 'Chilean Peso',
                'symbol' => '$',
                'decimals' => 0, // El peso chileno no tiene centavos
                'country' => 'Chile',
            ],
            'GBP' => [
                'name' => 'British Pound Sterling',
                'symbol' => '£',
                'decimals' => 2,
                'country' => 'United Kingdom',
            ],
            'JPY' => [
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'decimals' => 0, // El yen no tiene decimales
                'country' => 'Japan',
            ],
            'CAD' => [
                'name' => 'Canadian Dollar',
                'symbol' => '$',
                'decimals' => 2,
                'country' => 'Canada',
            ],
            'AUD' => [
                'name' => 'Australian Dollar',
                'symbol' => '$',
                'decimals' => 2,
                'country' => 'Australia',
            ],
            'CHF' => [
                'name' => 'Swiss Franc',
                'symbol' => 'CHF',
                'decimals' => 2,
                'country' => 'Switzerland',
            ],
            'CNY' => [
                'name' => 'Chinese Yuan',
                'symbol' => '¥',
                'decimals' => 2,
                'country' => 'China',
            ],
            'INR' => [
                'name' => 'Indian Rupee',
                'symbol' => '₹',
                'decimals' => 2,
                'country' => 'India',
            ],
            'RUB' => [
                'name' => 'Russian Ruble',
                'symbol' => '₽',
                'decimals' => 2,
                'country' => 'Russia',
            ],
            'KRW' => [
                'name' => 'South Korean Won',
                'symbol' => '₩',
                'decimals' => 0, // El won no tiene decimales
                'country' => 'South Korea',
            ],
            'TRY' => [
                'name' => 'Turkish Lira',
                'symbol' => '₺',
                'decimals' => 2,
                'country' => 'Turkey',
            ],
            'AED' => [
                'name' => 'United Arab Emirates Dirham',
                'symbol' => 'د.إ',
                'decimals' => 2,
                'country' => 'United Arab Emirates',
            ],
            'SAR' => [
                'name' => 'Saudi Riyal',
                'symbol' => 'ر.س',
                'decimals' => 2,
                'country' => 'Saudi Arabia',
            ],
        ];
    }
}
