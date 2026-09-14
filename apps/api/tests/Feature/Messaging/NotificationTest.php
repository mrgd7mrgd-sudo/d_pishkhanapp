<?php

declare(strict_types=1);

namespace Tests\Feature\Messaging;

use App\Integration\Sms\Drivers\FakeDriver;
use App\Integration\Sms\SmsGateway;
use App\Modules\Identity\Domain\Enums\CitizenTier;
use App\Modules\Identity\Domain\Enums\OtpPurpose;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Messaging\Domain\Models\Notification;
use App\Modules\Messaging\Domain\Models\NotificationPreference;
use App\Modules\Messaging\Infrastructure\Push\WebPushSender;
use App\Modules\Messaging\Jobs\SendCaseNotificationJob;
use App\Modules\Messaging\Jobs\SendPushJob;
use App\Modules\Messaging\Jobs\SendSmsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('fa');

    $this->citizen = new Citizen;
    $this->citizen->national_id = '0010350801';
    $this->citizen->mobile = '09121112233';
    $this->citizen->full_name = 'محمد رضایی';
    $this->citizen->tier = CitizenTier::BRONZE;
    $this->citizen->save();

    WebPushSender::clearSentPushes();
});

test('all 7 SMS templates from Architecture §8.3 render valid Persian text with parameters (TASK-073-T)', function (): void {
    // 1. otp-login
    $msg1 = __('sms.otp-login', ['code' => '54321']);
    expect($msg1)->toBe('کد ورود شما به سامانه پیشخوان: 54321');

    // 2. case-assigned
    $msg2 = __('sms.case-assigned', ['tracking' => 'CS-12345', 'office' => 'ولیعصر']);
    expect($msg2)->toBe('پرونده CS-12345 به دفتر ولیعصر اختصاص یافت.');

    // 3. case-returned
    $msg3 = __('sms.case-returned', ['tracking' => 'CS-12345', 'hours' => '72']);
    expect($msg3)->toBe('⚠️ پرونده CS-12345 نیاز به اصلاح مدرک دارد. مهلت: 72 ساعت');

    // 4. case-ready
    $msg4 = __('sms.case-ready', ['tracking' => 'CS-12345']);
    expect($msg4)->toBe('مدرک پرونده CS-12345 آماده تحویل است.');

    // 5. delivery-otp
    $msg5 = __('sms.delivery-otp', ['tracking' => 'CS-12345', 'code' => '9876']);
    expect($msg5)->toBe('کد تحویل مرسوله CS-12345: 9876 — فقط به مأمور تحویل اعلام کنید.');

    // 6. appointment-reminder
    $msg6 = __('sms.appointment-reminder', ['date' => '1405/06/20', 'time' => '10:30', 'office' => 'بهشتی']);
    expect($msg6)->toBe('یادآوری نوبت 1405/06/20 ساعت 10:30 در دفتر بهشتی');

    // 7. deadline-warning
    $msg7 = __('sms.deadline-warning', ['tracking' => 'CS-12345']);
    expect($msg7)->toBe('۲۴ ساعت تا پایان مهلت اصلاح پرونده CS-12345');
});

test('case return event sends both SMS and Push and creates in-app notification (§8.3, TASK-073-T)', function (): void {
    Queue::fake([SendSmsJob::class, SendPushJob::class]);

    $job = new SendCaseNotificationJob(
        citizenId: $this->citizen->id,
        type: 'case-returned',
        title: 'نقص مدرک پرونده',
        body: 'پرونده شما نیاز به اصلاح مدرک دارد.',
        smsTemplate: 'case-returned',
        smsParams: ['tracking' => 'CS-TEST-99', 'hours' => 72],
        payload: ['tracking_code' => 'CS-TEST-99']
    );

    $job->handle();

    // 1. Check in-app notification record was created
    $notification = Notification::where('citizen_id', $this->citizen->id)
        ->where('type', 'case-returned')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('نقص مدرک پرونده')
        ->and($notification->body)->toBe('پرونده شما نیاز به اصلاح مدرک دارد.');

    // 2. Check Push job dispatched
    Queue::assertPushed(SendPushJob::class, function (SendPushJob $push): bool {
        return $push->citizenId === $this->citizen->id
            && $push->title === 'نقص مدرک پرونده';
    });

    // 3. Check SMS job dispatched with mobile and params
    Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $sms): bool {
        return $sms->mobile === '09121112233'
            && $sms->template === 'case-returned'
            && $sms->params['tracking'] === 'CS-TEST-99';
    });
});

test('user with disabled notification preference does not receive SMS or Push (TASK-073-T)', function (): void {
    Queue::fake([SendSmsJob::class, SendPushJob::class]);

    // Disable both SMS and Push for 'case-assigned'
    NotificationPreference::create([
        'id' => (string) Str::uuid(),
        'citizen_id' => $this->citizen->id,
        'notification_type' => 'case-assigned',
        'sms_enabled' => false,
        'push_enabled' => false,
    ]);

    $job = new SendCaseNotificationJob(
        citizenId: $this->citizen->id,
        type: 'case-assigned',
        title: 'اختصاص پرونده',
        body: 'پرونده شما به دفتر اختصاص یافت.',
        smsTemplate: 'case-assigned',
        smsParams: ['tracking' => 'CS-ASSIGN-1', 'office' => 'دفتر تست']
    );

    $job->handle();

    // In-app notification still created
    $notification = Notification::where('citizen_id', $this->citizen->id)
        ->where('type', 'case-assigned')
        ->first();
    expect($notification)->not->toBeNull();

    // Neither SMS nor Push dispatched
    Queue::assertNotPushed(SendSmsJob::class);
    Queue::assertNotPushed(SendPushJob::class);
});

test('SendSmsJob executes successfully via FakeDriver and handles delivery (TASK-073-T)', function (): void {
    $smsGateway = new FakeDriver;
    $job = new SendSmsJob(
        mobile: '09121112233',
        template: 'case-ready',
        params: ['tracking' => 'CS-READY-1']
    );

    // Assert job does not throw exception
    $job->handle($smsGateway);
    expect(true)->toBeTrue();
});

test('SendPushJob executes successfully via WebPushSender (TASK-073-T)', function (): void {
    $pushSender = new WebPushSender;
    $job = new SendPushJob(
        citizenId: $this->citizen->id,
        title: 'اعلان تست',
        body: 'متن تست وب پوش',
        payload: ['action' => 'open_case']
    );

    $job->handle($pushSender);

    $sent = WebPushSender::getSentPushes();
    expect($sent)->toHaveCount(1)
        ->and($sent[0]['citizen_id'])->toBe($this->citizen->id)
        ->and($sent[0]['title'])->toBe('اعلان تست');
});

test('queue priority: notification jobs run on notifications queue (priority 2) (TASK-073-T)', function (): void {
    $caseJob = new SendCaseNotificationJob(
        citizenId: $this->citizen->id,
        type: 'system',
        title: 'تست اولویت صف',
        body: 'متن'
    );
    expect($caseJob->queue)->toBe('notifications');

    $smsJob = new SendSmsJob('09121112233', 'case-ready');
    expect($smsJob->queue)->toBe('notifications');

    $pushJob = new SendPushJob($this->citizen->id, 'تست', 'متن');
    expect($pushJob->queue)->toBe('notifications');
});

test('OTP SMS delivery SLO <30s via SmsGateway (TASK-073-T, §8.3, §9.5)', function (): void {
    $gateway = app(SmsGateway::class);

    $startTime = microtime(true);
    $result = $gateway->sendOtp('09121112233', '12345', OtpPurpose::LOGIN);
    $duration = microtime(true) - $startTime;

    expect($result->isSuccess)->toBeTrue()
        ->and($duration)->toBeLessThan(1.0); // Simulator/Fake returns in <1s (SLO target <30s)
});
