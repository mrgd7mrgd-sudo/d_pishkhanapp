<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Controllers;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Application\Actions\BookAppointmentAction;
use App\Modules\OfficeNetwork\Application\Actions\CancelAppointmentAction;
use App\Modules\OfficeNetwork\Domain\AppointmentSlotManager;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentReminderType;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Http\Requests\BookAppointmentRequest;
use App\Modules\OfficeNetwork\Http\Requests\GetSlotsRequest;
use App\Modules\OfficeNetwork\Http\Resources\AppointmentResource;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class AppointmentController
{
    public function slots(GetSlotsRequest $request, string $id): JsonResponse
    {
        $office = Office::findOrFail($id);

        /** @var string $dateStr */
        $dateStr = $request->input('date', Carbon::tomorrow('Asia/Tehran')->format('Y-m-d'));

        $isOpen = AppointmentSlotManager::isOfficeOpenOnDate($office, $dateStr);
        $slots = AppointmentSlotManager::generateSlots($office, $dateStr);

        return new JsonResponse([
            'data' => [
                'office_id' => $office->id,
                'office_name' => $office->name,
                'date' => $dateStr,
                'is_open' => $isOpen,
                'capacity_per_slot' => AppointmentSlotManager::getSlotCapacity($office),
                'slots' => $slots,
            ],
        ]);
    }

    public function store(BookAppointmentRequest $request, BookAppointmentAction $action): JsonResponse
    {
        Gate::authorize('create', Appointment::class);

        /** @var Citizen $citizen */
        $citizen = $request->user();

        $reminderTypeStr = (string) $request->input('reminder_type', 'all');
        $reminderType = AppointmentReminderType::tryFrom($reminderTypeStr) ?? AppointmentReminderType::All;

        $appointment = $action->execute(
            citizen: $citizen,
            officeId: (string) $request->input('office_id'),
            serviceId: (string) $request->input('service_id'),
            appointmentDate: (string) $request->input('appointment_date'),
            timeSlot: (string) $request->input('time_slot'),
            reminderType: $reminderType,
            reminderEnabled: (bool) $request->input('reminder_enabled', true)
        );

        $appointment->load(['office', 'service']);

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Appointment::class);

        /** @var Citizen $citizen */
        $citizen = $request->user();

        $appointments = Appointment::with(['office', 'service'])
            ->where('citizen_id', $citizen->id)
            ->orderByDesc('appointment_date')
            ->orderBy('time_slot')
            ->paginate(15);

        return AppointmentResource::collection($appointments);
    }

    public function show(Request $request, string $id): AppointmentResource
    {
        $appointment = Appointment::with(['office', 'service'])->findOrFail($id);
        Gate::authorize('view', $appointment);

        return new AppointmentResource($appointment);
    }

    public function destroy(Request $request, string $id, CancelAppointmentAction $action): AppointmentResource
    {
        $appointment = Appointment::findOrFail($id);
        Gate::authorize('cancel', $appointment);

        /** @var Authenticatable $actor */
        $actor = $request->user();

        $reason = $request->input('reason');
        $updated = $action->execute(
            appointment: $appointment,
            actor: $actor,
            reason: is_string($reason) ? $reason : null
        );

        $updated->load(['office', 'service']);

        return new AppointmentResource($updated);
    }
}
