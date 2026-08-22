<?php

declare(strict_types=1);

namespace Src\Coupon\Application\UseCase;

use Exception;
use Src\Coupon\Application\Contracts\CouponRepositoryInterface;
use Src\Coupon\Application\DTOs\ValidateCouponResult;
use Src\Coupon\Domain\ValueObjects\CouponCode;
use Src\Order\Infrastructure\Eloquent\Models\Order;

final class ValidateCouponUseCase
{
    public function __construct(
        private readonly CouponRepositoryInterface $repository,
        /**
         * Hallazgo Auditoria #4: zona en la que se decide el DIA de calendario del cupon.
         *
         * Se inyecta en vez de leerse con `config()` aqui dentro por dos motivos. Uno, que
         * los tests unitarios instancian este caso de uso sin aplicacion levantada. Y dos,
         * mas serio: el `try/catch` de abajo convertia el fallo de `config()` en «cupon
         * invalido», asi que un error de arranque se habria mostrado al comprador como un
         * cupon que no vale. El valor real lo inyecta CouponServiceProvider.
         */
        private readonly string $businessTimezone = 'America/Caracas'
    ) {}

    /**
     * @param  string|null  $customerId  Necesario para aplicar `usage_limit_per_customer`
     *                                   (hallazgo N27). Si no se pasa, ese limite no se
     *                                   comprueba: el endpoint publico de validacion del
     *                                   carrito no siempre sabe quien es el comprador.
     */
    public function execute(string $code, float $orderSubtotal, string $currentDate = 'now', ?string $customerId = null): ValidateCouponResult
    {
        $couponCode = CouponCode::fromString($code);
        $coupon = $this->repository->findByCode($couponCode);

        if ($coupon === null) {
            return new ValidateCouponResult(
                isValid: false,
                discountAmount: 0.0,
                finalTotal: $orderSubtotal,
                message: sprintf('El cupón "%s" no existe.', $code)
            );
        }

        try {
            // Hallazgo Auditoria #4: el dominio compara dias de calendario, y con la zona
            // por defecto de PHP (UTC) el dia cambiaba a las 20:00 de Caracas — un cupon
            // valido «hasta el 21» caducaba esa tarde.
            $coupon->validateUsability($orderSubtotal, $currentDate, $this->businessTimezone);

            // Hallazgo N27: `usage_limit_per_customer` existia en la tabla desde el
            // principio y no lo miraba nadie. `validateUsability()` solo comprueba el
            // limite global, asi que un cupon de «uno por cliente» se podia usar N veces.
            $limitePorCliente = $coupon->usageLimitPerCustomer()->value();

            if ($limitePorCliente !== null && $customerId !== null) {
                $usados = Order::where('customer_id', $customerId)
                    ->where('coupon_code', $coupon->code()->value())
                    ->whereNotIn('status', ['cancelled', 'refunded'])
                    ->count();

                if ($usados >= $limitePorCliente) {
                    return new ValidateCouponResult(
                        isValid: false,
                        discountAmount: 0.0,
                        finalTotal: $orderSubtotal,
                        message: sprintf('Ya has usado el cupón "%s" el máximo de veces permitido.', $code),
                        coupon: $coupon
                    );
                }
            }

            $discount = $coupon->calculateDiscount($orderSubtotal);
            $finalTotal = round(max(0.0, $orderSubtotal - $discount), 2);

            return new ValidateCouponResult(
                isValid: true,
                discountAmount: $discount,
                finalTotal: $finalTotal,
                message: 'Cupón aplicado exitosamente.',
                coupon: $coupon
            );
        } catch (Exception $e) {
            return new ValidateCouponResult(
                isValid: false,
                discountAmount: 0.0,
                finalTotal: $orderSubtotal,
                message: $e->getMessage(),
                coupon: $coupon
            );
        }
    }
}
