<?php

declare(strict_types=1);

namespace App\Integration\Government\Drivers\Simulator;

use App\Integration\Government\CivilRegistryClient;
use App\Integration\Government\DTO\DocumentVerification;
use App\Integration\Government\DTO\PersonSummary;
use RuntimeException;

/**
 * Simulator for Civil Registry (Architecture §8.5, TASK-075).
 * Deterministic behavior based on the last digit of national ID.
 */
final class SimulatorCivilRegistryClient implements CivilRegistryClient
{
    public function getPersonSummary(string $nationalId, string $birthDate): PersonSummary
    {
        $lastChar = substr(trim($nationalId), -1);

        switch ($lastChar) {
            case '0':
                return new PersonSummary(
                    isAlive: true,
                    nationalId: $nationalId,
                    firstName: 'علی',
                    lastName: 'محمدی',
                    fatherName: 'رضا',
                    gender: 'male',
                    birthDate: $birthDate,
                    isEligible: true
                );

            case '1':
                return new PersonSummary(
                    isAlive: true,
                    nationalId: $nationalId,
                    firstName: 'مغایر',
                    lastName: 'عدم تطابق',
                    fatherName: 'مغایر',
                    gender: 'male',
                    birthDate: $birthDate,
                    isEligible: false,
                    ineligibleReason: 'INQUIRY_MISMATCH'
                );

            case '2':
                if (! (bool) env('SIMULATOR_FAST_TEST', false)) {
                    sleep(30);
                }

                return new PersonSummary(
                    isAlive: true,
                    nationalId: $nationalId,
                    firstName: 'تأخیر',
                    lastName: 'زمانی',
                    fatherName: 'حسن',
                    gender: 'male',
                    birthDate: $birthDate,
                    isEligible: true
                );

            case '3':
                throw new RuntimeException('خطای ۵۰۰ سرور مرجع دولتی: سامانه ثبت احوال با خطا مواجه شد.', 500);
            case '4':
                return new PersonSummary(
                    isAlive: true,
                    nationalId: $nationalId,
                    firstName: 'عدم',
                    lastName: 'احراز',
                    fatherName: 'جواد',
                    gender: 'male',
                    birthDate: $birthDate,
                    isEligible: false,
                    ineligibleReason: 'ELIGIBILITY_FAIL'
                );

            default:
                return new PersonSummary(
                    isAlive: true,
                    nationalId: $nationalId,
                    firstName: 'شهروند',
                    lastName: 'معتبر',
                    fatherName: 'احمد',
                    gender: 'male',
                    birthDate: $birthDate,
                    isEligible: true
                );
        }
    }

    public function verifyBirthCertificate(string $nationalId, string $serial): DocumentVerification
    {
        $lastChar = substr(trim($nationalId), -1);

        switch ($lastChar) {
            case '0':
                return new DocumentVerification(
                    isValid: true,
                    nationalId: $nationalId,
                    serial: $serial,
                    status: 'VERIFIED',
                    message: 'شناسنامه معتبر و فعال است.'
                );

            case '1':
                return new DocumentVerification(
                    isValid: false,
                    nationalId: $nationalId,
                    serial: $serial,
                    status: 'INQUIRY_MISMATCH',
                    message: 'سریال شناسنامه با اطلاعات سجلی مغایرت دارد.'
                );

            case '2':
                if (! (bool) env('SIMULATOR_FAST_TEST', false)) {
                    sleep(30);
                }

                return new DocumentVerification(
                    isValid: true,
                    nationalId: $nationalId,
                    serial: $serial,
                    status: 'VERIFIED',
                    message: 'شناسنامه تأیید گردید.'
                );

            case '3':
                throw new RuntimeException('خطای ۵۰۰ سرور مرجع دولتی: استعلام شناسنامه ناموفق.', 500);
            case '4':
                return new DocumentVerification(
                    isValid: false,
                    nationalId: $nationalId,
                    serial: $serial,
                    status: 'ELIGIBILITY_FAIL',
                    message: 'عدم احراز شرایط قانونی اعتبار شناسنامه.'
                );

            default:
                return new DocumentVerification(
                    isValid: true,
                    nationalId: $nationalId,
                    serial: $serial,
                    status: 'VERIFIED',
                    message: 'شناسنامه معتبر است.'
                );
        }
    }
}
