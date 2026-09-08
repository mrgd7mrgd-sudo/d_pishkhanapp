<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Shared\Crypto\EnvelopeEncryptor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('storing citizen records encrypted PII as unreadable ciphertext in database', function () {
    $citizen = new Citizen;
    $citizen->national_id = '0012345678';
    $citizen->mobile = '09123456789';
    $citizen->full_name = 'سهراب سپهری';
    $citizen->tier = CitizenTier::GOLD;
    $citizen->save();

    // Direct database inspection: raw column must NOT contain plaintext PII
    $rawRecord = DB::table('citizens')->where('id', $citizen->id)->first();
    expect($rawRecord)->not->toBeNull();

    // Verification 1: national_id_encrypted contains JSON envelope, not plaintext
    expect($rawRecord->national_id_encrypted)->not->toContain('0012345678');
    expect($rawRecord->mobile_encrypted)->not->toContain('09123456789');

    // Verification 2: JSON envelope structure is present
    $envelope = json_decode((string) $rawRecord->national_id_encrypted, true);
    expect($envelope)->toHaveKeys(['ciphertext', 'iv', 'auth_tag', 'encrypted_dek', 'dek_iv', 'dek_tag', 'kek_version']);

    // Verification 3: Model transparently decrypts PII
    $fetched = Citizen::query()->findOrFail($citizen->id);
    expect($fetched->national_id)->toBe('0012345678');
    expect($fetched->mobile)->toBe('09123456789');
    expect($fetched->full_name)->toBe('سهراب سپهری');
    expect($fetched->tier)->toBe(CitizenTier::GOLD);
});

test('searching citizen by blind index hash succeeds', function () {
    $citizen = new Citizen;
    $citizen->national_id = '0087654321';
    $citizen->mobile = '09187654321';
    $citizen->full_name = 'پروین اعتصامی';
    $citizen->save();

    $encryptor = app(EnvelopeEncryptor::class);
    $searchHash = $encryptor->hashIndex('0087654321');

    $found = Citizen::query()->where('national_id_hash', $searchHash)->first();
    expect($found)->not->toBeNull();
    expect($found->id)->toBe($citizen->id);
    expect($found->national_id)->toBe('0087654321');
});

test('duplicate national id hash violates unique index constraint', function () {
    $c1 = new Citizen;
    $c1->national_id = '0099999999';
    $c1->mobile = '09199999991';
    $c1->full_name = 'کاربر اول';
    $c1->save();

    $c2 = new Citizen;
    $c2->national_id = '0099999999';
    $c2->mobile = '09199999992';
    $c2->full_name = 'کاربر دوم با کد ملی یکسان';

    expect(fn () => $c2->save())->toThrow(QueryException::class);
});
