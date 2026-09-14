<?php

declare(strict_types=1);

namespace App\Integration\Government\Drivers\Simulator;

use App\Integration\Government\DTO\PostalAddress;
use App\Integration\Government\DTO\ShipmentRequest;
use App\Integration\Government\DTO\ShipmentResult;
use App\Integration\Government\DTO\TrackingResult;
use App\Integration\Government\PostalClient;
use RuntimeException;

/**
 * Simulator for Postal Service (Architecture §8.5, TASK-075).
 * Deterministic behavior based on the last digit of postal code.
 */
final class SimulatorPostalClient implements PostalClient
{
    public function validatePostalCode(string $postalCode): PostalAddress
    {
        $lastChar = substr(trim($postalCode), -1);

        switch ($lastChar) {
            case '0':
                return new PostalAddress(
                    isValid: true,
                    postalCode: $postalCode,
                    province: 'تهران',
                    city: 'تهران',
                    address: 'تهران، خیابان شریعتی، بالاتر از میرداماد، پلاک ۱۰۰',
                    buildingNumber: '100'
                );

            case '1':
                return new PostalAddress(
                    isValid: false,
                    postalCode: $postalCode,
                    province: '',
                    city: '',
                    address: '',
                    buildingNumber: null
                );

            case '2':
                if (! (bool) env('SIMULATOR_FAST_TEST', false)) {
                    sleep(30);
                }

                return new PostalAddress(
                    isValid: true,
                    postalCode: $postalCode,
                    province: 'تهران',
                    city: 'تهران',
                    address: 'تهران، بلوار کشاورز، پلاک ۵۰',
                    buildingNumber: '50'
                );

            case '3':
                throw new RuntimeException('خطای ۵۰۰ سرور مرجع دولتی: سامانه پست در دسترس نیست.', 500);
            case '4':
                return new PostalAddress(
                    isValid: false,
                    postalCode: $postalCode,
                    province: '',
                    city: '',
                    address: '',
                    buildingNumber: null
                );

            default:
                return new PostalAddress(
                    isValid: true,
                    postalCode: $postalCode,
                    province: 'تهران',
                    city: 'تهران',
                    address: 'تهران، خیابان ولیعصر، پلاک ۲۰۰',
                    buildingNumber: '200'
                );
        }
    }

    public function createShipment(ShipmentRequest $r): ShipmentResult
    {
        $barcode = '1098'.str_pad((string) random_int(10000000, 99999999), 12, '0', STR_PAD_LEFT);

        return new ShipmentResult(
            isSuccess: true,
            barcode: $barcode,
            trackingUrl: "https://tracking.post.ir/?id={$barcode}"
        );
    }

    public function trackShipment(string $barcode): TrackingResult
    {
        return new TrackingResult(
            barcode: $barcode,
            status: 'in_transit',
            lastLocation: 'مرکز تجزیه و مبادلات پستی تهران',
            lastEventTime: now()->toISOString(),
            history: [
                ['status' => 'received', 'location' => 'دفتر پستی مبدأ', 'time' => now()->subDay()->toISOString()],
                ['status' => 'in_transit', 'location' => 'مرکز تجزیه و مبادلات', 'time' => now()->toISOString()],
            ]
        );
    }
}
