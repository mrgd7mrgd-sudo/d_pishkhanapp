<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Resources;

use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Domain\Models\OfficeMedal;
use App\Modules\OfficeNetwork\Domain\Models\OfficeServiceCoverage;
use App\Modules\OfficeNetwork\Domain\Models\OfficeSpecialty;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @mixin Office
 */
final class OfficeResource extends JsonResource
{
    /**
     * Optional contextual calculations from query runner.
     */
    private ?float $distanceKm = null;

    private ?float $smartScore = null;

    /** @var array{lat: float, lng: float}|null */
    private ?array $coords = null;

    /**
     * @param  array{distance_km?: float|null, smart_score?: float|null, coords?: array{lat: float, lng: float}|null}  $context
     */
    public function withContext(array $context): self
    {
        $this->distanceKm = $context['distance_km'] ?? null;
        $this->smartScore = $context['smart_score'] ?? null;
        $this->coords = $context['coords'] ?? null;

        return $this;
    }

    /**
     * Transform the resource into an array strictly matching Architecture §5.6 Example 4 (TASK-043).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $workingHours = is_array($this->working_hours) ? $this->working_hours : [];
        $isOpenNow = self::calculateIsOpenNow($workingHours);
        $workingHoursLabel = (string) ($workingHours['label'] ?? $workingHours['text'] ?? 'شنبه تا چهارشنبه ۰۸:۰۰–۱۸:۰۰، پنج‌شنبه‌ها تا ۱۳:۳۰');

        $activeCounters = max(1, (int) $this->active_counters);
        $queue = max(0, (int) $this->current_waiting_queue);
        $estimatedWait = (int) ceil(($queue * 4.0) / $activeCounters);

        $medals = $this->relationLoaded('medals')
            ? $this->medals->map(fn (OfficeMedal $m) => $m->title)->values()->all()
            : $this->medals()->pluck('title')->values()->all();

        $specialties = $this->relationLoaded('specialties')
            ? $this->specialties->map(fn (OfficeSpecialty $s) => $s->title)->values()->all()
            : $this->specialties()->pluck('title')->values()->all();

        $supportedCategories = $this->relationLoaded('serviceCoverages')
            ? $this->serviceCoverages->map(fn (OfficeServiceCoverage $c) => $c->category_id)->values()->all()
            : $this->serviceCoverages()->pluck('category_id')->values()->all();

        $coords = $this->coords ?? $this->resolveCoordinates();

        return [
            'id' => $this->id,
            'code' => trim($this->code),
            'name' => $this->name,
            'manager_name' => $this->manager_name,
            'membership_status' => $this->membership_status->value,
            'is_online' => (bool) $this->is_online,
            'rating' => (float) $this->rating,
            'review_count' => (int) $this->review_count,
            'medals' => $medals,
            'specialties' => $specialties,
            'address' => $this->address ?? '',
            'province_code' => $this->province_code,
            'city' => 'تهران',
            'region' => null,
            'coords' => $coords,
            'distance_km' => $this->distanceKm !== null ? round($this->distanceKm, 2) : null,
            'phone' => $this->phone ?? '',
            'working_hours' => [
                'label' => $workingHoursLabel,
                'is_open_now' => $isOpenNow,
            ],
            'active_counters' => $activeCounters,
            'current_waiting_queue' => $queue,
            'estimated_wait_minutes' => $estimatedWait,
            'supported_category_ids' => array_values(array_unique($supportedCategories)),
            'smart_score' => $this->smartScore !== null ? round($this->smartScore, 2) : null,
        ];
    }

    /**
     * Determine if office is currently open based on Iran timezone (§5.6 #4, TASK-043).
     *
     * @param  array<string, mixed>  $workingHours
     */
    public static function calculateIsOpenNow(array $workingHours): bool
    {
        $now = Carbon::now('Asia/Tehran');
        $dayOfWeek = $now->dayOfWeek;

        // Friday is weekend in Iran
        if ($dayOfWeek === Carbon::FRIDAY) {
            return false;
        }

        $currentTime = $now->format('H:i');

        // Thursday is half day
        if ($dayOfWeek === Carbon::THURSDAY) {
            return $currentTime >= '08:00' && $currentTime <= '13:30';
        }

        $start = (string) ($workingHours['start'] ?? '07:30');
        $end = (string) ($workingHours['end'] ?? '19:30');

        return $currentTime >= $start && $currentTime <= $end;
    }

    /**
     * @return array{lat: float, lng: float}
     */
    private function resolveCoordinates(): array
    {
        if (! empty($this->location) && is_string($this->location) && str_contains($this->location, ',')) {
            $parts = explode(',', $this->location);
            if (count($parts) === 2) {
                return [
                    'lat' => round((float) $parts[0], 4),
                    'lng' => round((float) $parts[1], 4),
                ];
            }
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $point = DB::selectOne(
                'SELECT ST_X(location::geometry) as lng, ST_Y(location::geometry) as lat FROM offices WHERE id = ?',
                [$this->id]
            );
            if ($point !== null) {
                return [
                    'lat' => round((float) $point->lat, 4),
                    'lng' => round((float) $point->lng, 4),
                ];
            }
        }

        return ['lat' => 35.7592, 'lng' => 51.4083];
    }
}
