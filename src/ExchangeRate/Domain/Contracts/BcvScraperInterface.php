<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Domain\Contracts;

interface BcvScraperInterface
{
    /**
     * Extrae la tasa oficial del dólar y fecha valor desde el portal del BCV.
     *
     * @return array{
     *     rate: float,
     *     rate_date: string,
     *     raw_html: ?string,
     *     success: bool,
     *     error_message: ?string
     * }
     */
    public function fetchUsdRate(): array;
}
