<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Controllers;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Application\Actions\SetAttendanceAction;
use App\Modules\OfficeNetwork\Application\Actions\SetCompletionAction;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentAttendance;
use App\Modules\OfficeNetwork\Domain\Enums\AppointmentCompletion;
use App\Modules\OfficeNetwork\Domain\Models\Appointment;
use App\Modules\OfficeNetwork\Http\Requests\SetAttendanceRequest;
use App\Modules\OfficeNetwork\Http\Requests\SetCompletionRequest;
use App\Modules\OfficeNetwork\Http\Resources\AppointmentResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * DeskAppointmentController (§5.3, §6.1, TASK-100).
 * Handles operator desk in-person appointments: listing, inspection, attendance marking, and completion.
 */
final class DeskAppointmentController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $operator = $this->resolveOperator($request);

        $query = Appointment::query()
            ->with(['citizen', 'service.category', 'office'])
            ->where('office_id', $operator->office_id)
            ->orderByDesc('appointment_date')
            ->orderBy('time_slot')
            ->orderBy('queue_number');

        if ($request->filled('date')) {
            $query->whereDate('appointment_date', (string) $request->query('date'));
        }

        if ($request->filled('attendance')) {
            $query->where('attendance', (string) $request->query('attendance'));
        }

        if ($request->filled('completion')) {
            $query->where('completion', (string) $request->query('completion'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->query('status'));
        }

        $search = $request->query('q') ?? $request->query('search');
        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('tracking_code', 'like', "%{$search}%")
                    ->orWhere('queue_number', 'like', "%{$search}%");
            });
        }

        $perPage = min(50, max(1, $request->integer('per_page', 15)));
        $appointments = $query->paginate($perPage);

        return AppointmentResource::collection($appointments);
    }

    public function show(Request $request, string $id): AppointmentResource
    {
        $operator = $this->resolveOperator($request);

        /** @var Appointment|null $appointment */
        $appointment = Appointment::query()
            ->with(['citizen', 'service.category', 'office'])
            ->where('id', $id)
            ->where('office_id', $operator->office_id)
            ->first();

        if ($appointment === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'نوبت یافت نشد.',
            ], 404));
        }

        return new AppointmentResource($appointment);
    }

    public function setAttendance(
        SetAttendanceRequest $request,
        string $id,
        SetAttendanceAction $action
    ): AppointmentResource {
        $operator = $this->resolveOperator($request);

        /** @var Appointment|null $appointment */
        $appointment = Appointment::query()
            ->where('id', $id)
            ->where('office_id', $operator->office_id)
            ->first();

        if ($appointment === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'نوبت یافت نشد.',
            ], 404));
        }

        $attendance = AppointmentAttendance::from((string) $request->validated('attendance'));
        $counterNumber = $request->validated('counter_number') !== null
            ? (int) $request->validated('counter_number')
            : null;

        $updated = $action->execute($operator, $appointment, $attendance, $counterNumber);
        $updated->load(['citizen', 'service.category', 'office']);

        return new AppointmentResource($updated);
    }

    public function setCompletion(
        SetCompletionRequest $request,
        string $id,
        SetCompletionAction $action
    ): AppointmentResource {
        $operator = $this->resolveOperator($request);

        /** @var Appointment|null $appointment */
        $appointment = Appointment::query()
            ->where('id', $id)
            ->where('office_id', $operator->office_id)
            ->first();

        if ($appointment === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'نوبت یافت نشد.',
            ], 404));
        }

        $completion = AppointmentCompletion::from((string) $request->validated('completion'));
        $completionReason = $request->validated('completion_reason');

        $updated = $action->execute(
            operator: $operator,
            appointment: $appointment,
            completion: $completion,
            completionReason: is_string($completionReason) ? $completionReason : null
        );
        $updated->load(['citizen', 'service.category', 'office']);

        return new AppointmentResource($updated);
    }

    private function resolveOperator(Request $request): Operator
    {
        $operator = $request->user();
        if (! $operator instanceof Operator || $operator->office_id === null) {
            throw new HttpResponseException(new JsonResponse([
                'status' => 404,
                'detail' => 'دسترسی غیرمجاز.',
            ], 404));
        }

        return $operator;
    }
}
