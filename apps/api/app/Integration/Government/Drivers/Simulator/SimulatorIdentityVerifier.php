<?php

declare(strict_types=1);

namespace App\Integration\Government\Drivers\Simulator;

use App\Integration\Government\DTO\ShahkarResult;
use App\Integration\Government\IdentityVerifier;
use RuntimeException;

/**
 * Simulator for Shahkar Identity Verification (Architecture §8.5, TASK-075).
 * Deterministic behavior based on the last digit of national ID.
 */
final class SimulatorIdentityVerifier implements IdentityVerifier
{
    public function verifyMobileOwnership(string $nationalId, string $mobile): ShahkarResult
    {
        $lastChar = substr(trim($nationalId), -1);

        switch ($lastChar) {
            case '0':
                return new ShahkarResult(
                    isMatched: true,
                    nationalId: $nationalId,
                    mobile: $mobile,
                    trackingNumber: 'SHK-'.time()
                );

            case '1':
                return new ShahkarResult(
                    isMatched: false,
                    nationalId: $nationalId,
                    mobile: $mobile,
                    errorMessage: 'INQUIRY_MISMATCH'
                );

            case '2':
                if (! (bool) env('SIMULATOR_FAST_TEST', false)) {
                    sleep(30);
                }

                return new ShahkarResult(
                    isMatched: true,
                    nationalId: $nationalId,
                    mobile: $mobile,
                    trackingNumber: 'SHK-DELAY-'.time()
                );

            case '3':
                throw new RuntimeException('خطای ۵۰۰ سرور مرجع دولتی: شاهکار در دسترس نیست.', 500);
            case '4':
                return new ShahkarResult(
                    isMatched: false,
                    nationalId: $nationalId,
                    mobile: $mobile,
                    errorMessage: 'ELIGIBILITY_FAIL'
                );

            default:
                return new ShahkarResult(
                    isMatched: true,
                    nationalId: $nationalId,
                    mobile: $mobile,
                    trackingNumber: 'SHK-'.time()
                );
        }
    }
}
