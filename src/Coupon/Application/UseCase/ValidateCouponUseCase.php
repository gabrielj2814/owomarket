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
        private readonly CouponRepositoryInterface $repository
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
            $coupon->validateUsability($orderSubtotal, $currentDate);

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
