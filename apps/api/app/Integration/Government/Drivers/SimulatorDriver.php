<?php

declare(strict_types=1);

namespace App\Integration\Government\Drivers;

use App\Integration\Government\Drivers\Simulator\SimulatorPostalClient;
use App\Integration\Government\DTO\PostalAddress;
use App\Integration\Government\DTO\ShipmentRequest;
use App\Integration\Government\DTO\ShipmentResult;
use App\Integration\Government\DTO\TrackingResult;
use App\Integration\Government\PostalClient;

/**
 * Simulator Driver delegator for PostalClient (Architecture §8.5, TASK-098).
 */
final class SimulatorDriver implements PostalClient
{
    private readonly SimulatorPostalClient $client;

    public function __construct()
    {
        $this->client = new SimulatorPostalClient;
    }

    public function validatePostalCode(string $postalCode): PostalAddress
    {
        return $this->client->validatePostalCode($postalCode);
    }

    public function createShipment(ShipmentRequest $r): ShipmentResult
    {
        return $this->client->createShipment($r);
    }

    public function trackShipment(string $barcode): TrackingResult
    {
        return $this->client->trackShipment($barcode);
    }
}
