<?php

declare(strict_types=1);

namespace App\Modules\Payments\Domain;

use InvalidArgumentException;

/**
 * FeeSplitCalculator (Architecture §8.2, TASK-089).
 * Exact integer split calculations guaranteeing SUM(shares) === total_fee with zero rounding loss.
 */
final class FeeSplitCalculator
{
    /**
     * Calculate office and platform shares for a service case fee.
     * Default office share is 50.0% unless specified otherwise per service (§8.2).
     *
     * @return array{office_share_rials: int, platform_share_rials: int}
     */
    public function calculateServiceFeeSplit(int $feeRials, float $officeSharePercent = 50.0): array
    {
        if ($feeRials < 0) {
            throw new InvalidArgumentException('هزینه خدمت نمی‌تواند منفی باشد.');
        }

        if ($officeSharePercent < 0.0 || $officeSharePercent > 100.0) {
            throw new InvalidArgumentException('درصد سهم دفتر باید بین ۰ تا ۱۰۰ باشد.');
        }

        if ($feeRials === 0) {
            return [
                'office_share_rials' => 0,
                'platform_share_rials' => 0,
            ];
        }

        $officeShareRials = (int) round($feeRials * ($officeSharePercent / 100.0));
        $platformShareRials = $feeRials - $officeShareRials;

        return [
            'office_share_rials' => $officeShareRials,
            'platform_share_rials' => $platformShareRials,
        ];
    }

    /**
     * Calculate consultation fee split: 80% to consultant, 20% to platform (§8.2).
     *
     * @return array{consultant_share_rials: int, platform_share_rials: int}
     */
    public function calculateConsultationSplit(int $consultationFeeRials): array
    {
        if ($consultationFeeRials < 0) {
            throw new InvalidArgumentException('هزینه مشاوره نمی‌تواند منفی باشد.');
        }

        if ($consultationFeeRials === 0) {
            return [
                'consultant_share_rials' => 0,
                'platform_share_rials' => 0,
            ];
        }

        $consultantShareRials = (int) round($consultationFeeRials * 0.8);
        $platformShareRials = $consultationFeeRials - $consultantShareRials;

        return [
            'consultant_share_rials' => $consultantShareRials,
            'platform_share_rials' => $platformShareRials,
        ];
    }

    /**
     * Shipping fee goes 100% to the courier/postal operator (§8.2).
     *
     * @return array{courier_share_rials: int}
     */
    public function calculateDeliverySplit(int $shippingFeeRials): array
    {
        if ($shippingFeeRials < 0) {
            throw new InvalidArgumentException('هزینه ارسال نمی‌تواند منفی باشد.');
        }

        return [
            'courier_share_rials' => $shippingFeeRials,
        ];
    }
}
