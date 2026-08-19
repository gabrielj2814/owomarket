<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Src\ExchangeRate\Application\UseCase\SyncBcvExchangeRateUseCase;

class SyncBcvExchangeRateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange-rate:sync-bcv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrae y actualiza la tasa oficial del dólar estadounidense (USD a VES) desde el portal del BCV';

    /**
     * Execute the console command.
     */
    public function handle(SyncBcvExchangeRateUseCase $useCase): int
    {
        $this->info('Iniciando sincronización de tasa de cambio oficial del BCV...');

        try {
            $exchangeRate = $useCase->execute();

            $this->info('✅ Tasa BCV actualizada exitosamente.');
            $this->table(
                ['Moneda Base', 'Moneda Destino', 'Tasa (VES/USD)', 'Origen', 'Fecha Valor'],
                [[
                    $exchangeRate->getBaseCurrency()->value(),
                    $exchangeRate->getTargetCurrency()->value(),
                    $exchangeRate->getRate()->format(6),
                    $exchangeRate->getSource()->value(),
                    $exchangeRate->getRateDate()->format('d/m/Y'),
                ]]
            );

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error("❌ Error al sincronizar con el BCV: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
