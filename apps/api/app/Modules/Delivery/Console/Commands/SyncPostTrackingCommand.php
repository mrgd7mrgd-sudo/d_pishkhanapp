<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Console\Commands;

use App\Integration\Government\PostalClient;
use App\Modules\CaseWorkflow\Domain\CaseStateMachine;
use App\Modules\CaseWorkflow\Domain\Enums\CaseStatus;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineActorType;
use App\Modules\CaseWorkflow\Domain\Enums\TimelineStepStatus;
use App\Modules\CaseWorkflow\Domain\TransitionContext;
use App\Modules\Delivery\Domain\Enums\CourierType;
use App\Modules\Delivery\Domain\Enums\DeliveryStatus;
use App\Modules\Delivery\Domain\Models\DeliveryRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Hourly command to sync tracking status from National Postal Company (Architecture §8.5, §5.9, TASK-098).
 */
final class SyncPostTrackingCommand extends Command
{
    protected $signature = 'delivery:sync-post-tracking';

    protected $description = 'همگام‌سازی وضعیت رهگیری مرسولات پستی از شرکت ملی پست';

    public function handle(PostalClient $postalClient, CaseStateMachine $stateMachine): int
    {
        $this->info('Starting postal tracking synchronization...');

        $postDeliveries = DeliveryRequest::query()
            ->with(['case', 'events'])
            ->where('delivery_status', DeliveryStatus::IN_TRANSIT)
            ->whereIn('courier_type', [CourierType::SPECIAL_POST, CourierType::REGISTERED_POST])
            ->get();

        $count = $postDeliveries->count();
        $this->info("Found {$count} in-transit postal deliveries to check.");

        $synced = 0;
        foreach ($postDeliveries as $delivery) {
            try {
                $tracking = $postalClient->trackShipment($delivery->tracking_barcode);

                $occurredAt = $tracking->lastEventTime ? Carbon::parse($tracking->lastEventTime) : Carbon::now();

                // Create a milestone event for the tracking update
                $delivery->events()->create([
                    'event' => $tracking->status,
                    'location' => $tracking->lastLocation ?? 'شبکه پستی کشور',
                    'note' => "همگام‌سازی خودکار پستی: وضعیت [{$tracking->status}]",
                    'occurred_at' => $occurredAt,
                ]);

                if ($tracking->status === 'delivered') {
                    $this->markDelivered($delivery, $stateMachine);
                } elseif ($tracking->status === 'failed' || $tracking->status === 'returned') {
                    $this->markFailed($delivery, $stateMachine);
                }

                $synced++;
            } catch (\Throwable $e) {
                Log::error("Failed syncing tracking for delivery [{$delivery->id}]: {$e->getMessage()}");
                $this->warn("Error syncing barcode {$delivery->tracking_barcode}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully synchronized {$synced} / {$count} postal deliveries.");

        return Command::SUCCESS;
    }

    private function markDelivered(DeliveryRequest $delivery, CaseStateMachine $stateMachine): void
    {
        $delivery->delivery_status = DeliveryStatus::DELIVERED;
        $delivery->delivered_at = Carbon::now();
        $delivery->otp_hash = null;
        $delivery->otp_expires_at = null;
        $delivery->save();

        $case = $delivery->case;
        if ($case !== null && $case->status === CaseStatus::DELIVERING) {
            $ctx = new TransitionContext(
                title: 'تحویل مرسوله پستی و تکمیل پرونده',
                description: 'مرسوله توسط شرکت ملی پست به گیرنده تحویل داده شد و پرونده نهایی گردید.',
                stepStatus: TimelineStepStatus::DONE,
                actorType: TimelineActorType::SYSTEM,
                reasonCode: 'POSTAL_DELIVERED'
            );
            $stateMachine->transition($case, CaseStatus::COMPLETED, $ctx);
        }
    }

    private function markFailed(DeliveryRequest $delivery, CaseStateMachine $stateMachine): void
    {
        $delivery->delivery_status = DeliveryStatus::FAILED;
        $delivery->security_note = ($delivery->security_note ? $delivery->security_note.' | ' : '').'برگشت از پست به دفتر مبدأ';
        $delivery->otp_hash = null;
        $delivery->otp_expires_at = null;
        $delivery->save();

        $case = $delivery->case;
        if ($case !== null && $case->status === CaseStatus::DELIVERING) {
            $ctx = new TransitionContext(
                title: 'برگشت مرسوله پستی به دفتر',
                description: 'مرسوله به دلیل عدم امکان تحویل توسط پست، به دفتر پیشخوان مبدأ بازگردانده شد.',
                stepStatus: TimelineStepStatus::FAILED,
                actorType: TimelineActorType::SYSTEM,
                reasonCode: 'POSTAL_RETURNED'
            );
            $stateMachine->transition($case, CaseStatus::READY_FOR_ISSUE, $ctx);
        }
    }
}
