<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Scrapers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\ExchangeRate\Domain\Contracts\BcvScraperInterface;

final class BcvWebScraper implements BcvScraperInterface
{
    private const BCV_URL = 'https://www.bcv.org.ve/';

    private const DEFAULT_TIMEOUT = 15;

    public function __construct(
        private readonly ?string $targetUrl = null
    ) {}

    public function fetchUsdRate(): array
    {
        $url = $this->targetUrl ?? self::BCV_URL;

        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => self::DEFAULT_TIMEOUT,
            ])->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
            ])->get($url);

            if (! $response->successful()) {
                throw new Exception("El portal del BCV respondió con código HTTP {$response->status()}");
            }

            $html = $response->body();

            return $this->parseHtml($html);
        } catch (Exception $e) {
            Log::error('Error al realizar scraping de tasa oficial del BCV: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return [
                'rate' => 0.0,
                'rate_date' => date('Y-m-d'),
                'raw_html' => null,
                'success' => false,
                'error_message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parsea el HTML del BCV extrayendo el contenedor #dolar y la fecha valor.
     *
     * @return array{
     *     rate: float,
     *     rate_date: string,
     *     raw_html: ?string,
     *     success: bool,
     *     error_message: ?string
     * }
     */
    public function parseHtml(string $html): array
    {
        // 1. Selector primario: <div id="dolar"...>...<strong...>775,33560000</strong>...</div>
        $dolarContainerPattern = '/<div[^>]*id=["\']dolar["\'][^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/si';
        $dolarBlock = $html;

        if (preg_match($dolarContainerPattern, $html, $containerMatches)) {
            $dolarBlock = $containerMatches[1];
        }

        // Buscar el valor dentro de <strong ...>...</strong> dentro del bloque del dólar
        $ratePattern = '/<strong[^>]*>([\d\.,\s]+)<\/strong>/si';

        if (! preg_match($ratePattern, $dolarBlock, $rateMatches)) {
            // Intento secundario sobre todo el HTML si el contenedor div varió
            if (! preg_match('/id=["\']dolar["\'].*?<strong[^>]*>([\d\.,\s]+)<\/strong>/si', $html, $rateMatches)) {
                return [
                    'rate' => 0.0,
                    'rate_date' => date('Y-m-d'),
                    'raw_html' => $dolarBlock,
                    'success' => false,
                    'error_message' => 'No se encontró la etiqueta <strong class="strong-tb"> con la cotización del dólar en el HTML del BCV.',
                ];
            }
        }

        $rawRate = trim($rateMatches[1]);
        // Limpiar espacios internos y sustituir coma por punto
        $cleanedRate = str_replace([' ', "\r", "\n", "\t"], '', $rawRate);
        $normalizedRate = str_replace(',', '.', $cleanedRate);

        if (! is_numeric($normalizedRate) || (float) $normalizedRate <= 0) {
            return [
                'rate' => 0.0,
                'rate_date' => date('Y-m-d'),
                'raw_html' => $rawRate,
                'success' => false,
                'error_message' => "El valor extraído no es numérico válido: '{$rawRate}'",
            ];
        }

        $rateValue = (float) $normalizedRate;

        // 2. Extraer fecha valor (ej: "Fecha Valor: Miércoles, 19 Agosto 2026" o span con fecha)
        $rateDate = $this->extractRateDate($html);

        return [
            'rate' => round($rateValue, 6),
            'rate_date' => $rateDate,
            'raw_html' => $rateMatches[0],
            'success' => true,
            'error_message' => null,
        ];
    }

    private function extractRateDate(string $html): string
    {
        // Buscar patrón de fecha valor en formato: Fecha Valor: ... o <span class="date-display-single" ...>
        if (preg_match('/<span[^>]*class=["\'][^"\']*date-display-single[^"\']*["\'][^>]*content=["\']([^"\']+)["\']/si', $html, $dateMatch)) {
            $date = date_create_immutable($dateMatch[1]);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        if (preg_match('/Fecha Valor:\s*<span[^>]*>([^<]+)<\/span>/si', $html, $dateMatch)) {
            $parsed = $this->parseSpanishDate(trim($dateMatch[1]));
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return date('Y-m-d');
    }

    private function parseSpanishDate(string $rawDate): ?string
    {
        $months = [
            'enero' => '01',
            'febrero' => '02',
            'marzo' => '03',
            'abril' => '04',
            'mayo' => '05',
            'junio' => '06',
            'julio' => '07',
            'agosto' => '08',
            'septiembre' => '09',
            'octubre' => '10',
            'noviembre' => '11',
            'diciembre' => '12',
        ];

        // Formato: Miércoles, 19 Agosto 2026 o 19 Agosto 2026
        if (preg_match('/(\d{1,2})\s+([a-zA-ZáéíóúÁÉÍÓÚ]+)\s+(\d{4})/u', strtolower($rawDate), $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $monthName = strtolower(trim($m[2]));
            $month = $months[$monthName] ?? '01';
            $year = $m[3];

            return "{$year}-{$month}-{$day}";
        }

        return null;
    }
}
