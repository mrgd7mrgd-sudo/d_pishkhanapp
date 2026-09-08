<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Rules\ValidIranianNationalId;

// 20 mathematically verified valid Iranian National IDs
$validNationalIds = [
    '0010350802', '0010350810', '0010350829', '0010350837', '0010350845',
    '0010350853', '0010350861', '0010350871', '0010350888', '0010350896',
    '0010350901', '0010350918', '0010350926', '0010350934', '0010350942',
    '0010350950', '0010350969', '0010350977', '0010350985', '0010350993',
];

// 20 known invalid Iranian National IDs (repetitive, wrong check digit, invalid length, alpha)
$invalidNationalIds = [
    '0000000000', '1111111111', '2222222222', '3333333333', '4444444444',
    '5555555555', '6666666666', '7777777777', '8888888888', '9999999999',
    '0010350801', '0010350811', '1234567890', '0010350820', '0010350830',
    '12345', '00103508299', 'abcdefghij', '', ' 0010350820 ',
];

test('identifies all 20 valid national IDs correctly in PHP', function () use ($validNationalIds) {
    foreach ($validNationalIds as $nid) {
        expect(ValidIranianNationalId::isValid($nid))->toBeTrue("Expected {$nid} to be valid");
    }
});

test('identifies all 20 invalid national IDs correctly in PHP', function () use ($invalidNationalIds) {
    foreach ($invalidNationalIds as $nid) {
        expect(ValidIranianNationalId::isValid($nid))->toBeFalse("Expected {$nid} to be invalid");
    }
});

test('handles Persian and Arabic digits in national ID', function () {
    // 0010350829 in Persian
    $persian = '۰۰۱۰۳۵۰۸۲۹';
    expect(ValidIranianNationalId::isValid($persian))->toBeTrue();

    // 0010350829 in Arabic
    $arabic = '٠٠١٠٣٥٠٨٢٩';
    expect(ValidIranianNationalId::isValid($arabic))->toBeTrue();
});
