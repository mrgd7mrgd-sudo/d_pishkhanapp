<?php

declare(strict_types=1);

namespace Tests\Contract\Government;

use App\Integration\Government\CivilRegistryClient;
use App\Integration\Government\Drivers\Simulator\SimulatorCivilRegistryClient;
use App\Integration\Government\Drivers\Simulator\SimulatorIdentityVerifier;
use App\Integration\Government\Drivers\Simulator\SimulatorPostalClient;
use App\Integration\Government\DTO\DocumentVerification;
use App\Integration\Government\DTO\PersonSummary;
use App\Integration\Government\DTO\PostalAddress;
use App\Integration\Government\DTO\ShahkarResult;
use App\Integration\Government\DTO\ShipmentRequest;
use App\Integration\Government\DTO\ShipmentResult;
use App\Integration\Government\DTO\TrackingResult;
use App\Integration\Government\IdentityVerifier;
use App\Integration\Government\PostalClient;

beforeEach(function (): void {
    putenv('SIMULATOR_FAST_TEST=true');
});

it('ensures SimulatorIdentityVerifier strictly implements IdentityVerifier and returns ShahkarResult DTO', function (): void {
    $verifier = new SimulatorIdentityVerifier;
    expect($verifier)->toBeInstanceOf(IdentityVerifier::class);

    $result = $verifier->verifyMobileOwnership('0010350800', '09121112233');
    expect($result)->toBeInstanceOf(ShahkarResult::class)
        ->and($result->isMatched)->toBeTrue()
        ->and($result->nationalId)->toBe('0010350800')
        ->and($result->mobile)->toBe('09121112233')
        ->and($result->trackingNumber)->not->toBeNull();
});

it('ensures SimulatorCivilRegistryClient strictly implements CivilRegistryClient and returns PersonSummary and DocumentVerification DTOs', function (): void {
    $client = new SimulatorCivilRegistryClient;
    expect($client)->toBeInstanceOf(CivilRegistryClient::class);

    $summary = $client->getPersonSummary('0010350800', '1370-01-01');
    expect($summary)->toBeInstanceOf(PersonSummary::class)
        ->and($summary->isAlive)->toBeTrue()
        ->and($summary->nationalId)->toBe('0010350800')
        ->and($summary->firstName)->not->toBeEmpty()
        ->and($summary->lastName)->not->toBeEmpty()
        ->and($summary->isEligible)->toBeTrue();

    $doc = $client->verifyBirthCertificate('0010350800', 'A/12-345678');
    expect($doc)->toBeInstanceOf(DocumentVerification::class)
        ->and($doc->isValid)->toBeTrue()
        ->and($doc->nationalId)->toBe('0010350800')
        ->and($doc->serial)->toBe('A/12-345678')
        ->and($doc->status)->toBe('VERIFIED');
});

it('ensures SimulatorPostalClient strictly implements PostalClient and returns PostalAddress, ShipmentResult, and TrackingResult DTOs', function (): void {
    $postal = new SimulatorPostalClient;
    expect($postal)->toBeInstanceOf(PostalClient::class);

    $addr = $postal->validatePostalCode('1234567890');
    expect($addr)->toBeInstanceOf(PostalAddress::class)
        ->and($addr->isValid)->toBeTrue()
        ->and($addr->postalCode)->toBe('1234567890')
        ->and($addr->province)->not->toBeEmpty()
        ->and($addr->city)->not->toBeEmpty()
        ->and($addr->address)->not->toBeEmpty();

    $shipment = $postal->createShipment(new ShipmentRequest(
        caseId: 'test-case-id',
        originOfficeId: 'office-origin',
        destinationPostalCode: '1234567890',
        destinationAddress: 'تهران، ولیعصر',
        recipientName: 'احمد محمدی',
        recipientMobile: '09121112233'
    ));
    expect($shipment)->toBeInstanceOf(ShipmentResult::class)
        ->and($shipment->isSuccess)->toBeTrue()
        ->and($shipment->barcode)->not->toBeNull();

    $track = $postal->trackShipment($shipment->barcode);
    expect($track)->toBeInstanceOf(TrackingResult::class)
        ->and($track->barcode)->toBe($shipment->barcode)
        ->and($track->status)->toBe('in_transit')
        ->and($track->history)->toBeArray();
});
