<?php

declare(strict_types=1);

namespace App\Integration\Government;

use App\Integration\Government\DTO\PostalAddress;
use App\Integration\Government\DTO\ShipmentRequest;
use App\Integration\Government\DTO\ShipmentResult;
use App\Integration\Government\DTO\TrackingResult;

/**
 * PostalClient Port (Architecture §8.5, TASK-075).
 * National Postal Company client for postal code validation and shipment tracking.
 */
interface PostalClient
{
    public function validatePostalCode(string $postalCode): PostalAddress;

    public function createShipment(ShipmentRequest $r): ShipmentResult;

    public function trackShipment(string $barcode): TrackingResult;
}
