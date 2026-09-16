<?php

declare(strict_types=1);

namespace App\Modules\AiAssistance\Application;

use App\Modules\CaseWorkflow\Domain\Models\CaseRequest;
use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\OfficeNetwork\Application\Queries\OfficeFinder;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\ServiceCatalog\Domain\Models\Service;
use Illuminate\Support\Collection;
use Throwable;

final class ContextRetriever
{
    public function __construct(
        private readonly ?OfficeFinder $officeFinder = null
    ) {}

    /**
     * Retrieves grounding context from PostgreSQL FTS, PostGIS and Case records (§5.6 #10).
     *
     * @param  array<string, mixed>  $context
     * @return array{
     *     services: Collection<int, Service>,
     *     offices: array<int, mixed>,
     *     cases: Collection<int, CaseRequest>,
     *     grounding_text: string,
     *     citations: list<array{type: string, id: string}>,
     *     suggested_actions: list<array{type: string, label: string, payload: array<string, mixed>}>
     * }
     */
    public function retrieve(string $query, array $context = [], ?Citizen $citizen = null): array
    {
        $services = $this->findServices($query);
        $offices = $this->findOffices($context);
        $cases = $this->findCases($query, $context, $citizen);

        $groundingText = $this->formatGroundingText($services, $offices, $cases);
        $citations = $this->buildCitations($services, $offices);
        $suggestedActions = $this->buildSuggestedActions($services, $offices, $cases);

        return [
            'services' => $services,
            'offices' => $offices,
            'cases' => $cases,
            'grounding_text' => $groundingText,
            'citations' => $citations,
            'suggested_actions' => $suggestedActions,
        ];
    }

    /**
     * @return Collection<int, Service>
     */
    private function findServices(string $query): Collection
    {
        $keywords = $this->extractKeywords($query);

        $serviceQuery = Service::query()->where('is_active', true);

        if (! empty($keywords)) {
            $serviceQuery->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('title', 'LIKE', "%{$kw}%")
                        ->orWhere('description', 'LIKE', "%{$kw}%");
                }
            });
        }

        return $serviceQuery->with(['requiredDocs'])->limit(3)->get();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, mixed>
     */
    private function findOffices(array $context): array
    {
        $lat = $context['location']['lat'] ?? null;
        $lng = $context['location']['lng'] ?? null;

        if ($lat !== null && $lng !== null && $this->officeFinder !== null) {
            try {
                return $this->officeFinder->findNearby((float) $lat, (float) $lng, 15.0, null, 2);
            } catch (Throwable) {
                // fallback to active offices query
            }
        }

        return Office::query()
            ->where('is_online', true)
            ->orderByDesc('rating')
            ->limit(2)
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return Collection<int, CaseRequest>
     */
    private function findCases(string $query, array $context, ?Citizen $citizen): Collection
    {
        if (preg_match('/\b(?:CR|PK)-\d{4}-\d{5}\b/u', $query, $matches)) {
            return CaseRequest::query()
                ->where('tracking_code', $matches[0])
                ->with('service')
                ->limit(1)
                ->get();
        }

        if ($citizen !== null) {
            return CaseRequest::query()
                ->where('citizen_id', $citizen->id)
                ->with('service')
                ->latest()
                ->limit(2)
                ->get();
        }

        return collect();
    }

    /**
     * @param  Collection<int, Service>  $services
     * @param  array<int, mixed>  $offices
     * @param  Collection<int, CaseRequest>  $cases
     */
    private function formatGroundingText(Collection $services, array $offices, Collection $cases): string
    {
        $lines = ['زمینه مستندات و داده‌های واقعی سامانه:'];

        if ($services->isNotEmpty()) {
            $lines[] = "\n[خدمات مرتبط]:";
            foreach ($services as $svc) {
                $reqs = ! empty($svc->requirements) ? implode('، ', (array) $svc->requirements) : 'طبق دستورالعمل';
                $lines[] = "- خدمت «{$svc->title}» (شناسه: {$svc->id}) | توضیحات: {$svc->description} | مدارک لازم: {$reqs} | کارمزد: ".number_format($svc->fee_rials)." ریال | زمان تخمینی: {$svc->estimated_days_min} تا {$svc->estimated_days_max} روز.";
            }
        }

        if (! empty($offices)) {
            $lines[] = "\n[دفاتر پیشخوان در دسترس]:";
            foreach ($offices as $off) {
                $name = is_array($off) ? ($off['name'] ?? 'دفتر پیشخوان') : $off->name;
                $id = is_array($off) ? ($off['id'] ?? '') : $off->id;
                $address = is_array($off) ? ($off['address'] ?? '') : ($off->address ?? '');
                $dist = is_array($off) && isset($off['distance_km']) ? " (فاصله: {$off['distance_km']} کیلومتر)" : '';
                $lines[] = "- {$name} (شناسه: {$id}){$dist} | آدرس: {$address}";
            }
        }

        if ($cases->isNotEmpty()) {
            $lines[] = "\n[پرونده‌های کاربر]:";
            foreach ($cases as $c) {
                $svcTitle = $c->service?->title ?? 'خدمت';
                $lines[] = "- پرونده {$c->tracking_code} مربوط به «{$svcTitle}» در وضعیت {$c->status->value} است.";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  Collection<int, Service>  $services
     * @param  array<int, mixed>  $offices
     * @return list<array{type: string, id: string}>
     */
    private function buildCitations(Collection $services, array $offices): array
    {
        $citations = [];

        foreach ($services as $svc) {
            $citations[] = ['type' => 'service', 'id' => (string) $svc->id];
        }

        foreach ($offices as $off) {
            $id = is_array($off) ? ($off['id'] ?? null) : ($off->id ?? null);
            if ($id) {
                $citations[] = ['type' => 'office', 'id' => (string) $id];
            }
        }

        return $citations;
    }

    /**
     * @param  Collection<int, Service>  $services
     * @param  array<int, mixed>  $offices
     * @param  Collection<int, CaseRequest>  $cases
     * @return list<array{type: string, label: string, payload: array<string, mixed>}>
     */
    private function buildSuggestedActions(Collection $services, array $offices, Collection $cases): array
    {
        $actions = [];

        foreach ($services as $svc) {
            $actions[] = [
                'type' => 'open_service',
                'label' => "درخواست خدمت «{$svc->title}»",
                'payload' => ['service_id' => (string) $svc->id],
            ];
        }

        foreach ($offices as $off) {
            $id = is_array($off) ? ($off['id'] ?? null) : ($off->id ?? null);
            if ($id) {
                $actions[] = [
                    'type' => 'open_office',
                    'label' => 'مشاهده دفتر روی نقشه',
                    'payload' => ['office_id' => (string) $id],
                ];
            }
        }

        foreach ($cases as $c) {
            $actions[] = [
                'type' => 'track_case',
                'label' => "مشاهده وضعیت پرونده {$c->tracking_code}",
                'payload' => ['case_id' => (string) $c->id],
            ];
        }

        return $actions;
    }

    /**
     * @return list<string>
     */
    private function extractKeywords(string $query): array
    {
        $cleaned = preg_replace('/[؟?\.,!،؛\(\)\[\]\{\}]/u', ' ', $query) ?? $query;
        $words = preg_split('/\s+/u', trim($cleaned), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stopWords = ['برای', 'چه', 'است', 'و', 'یا', 'در', 'به', 'با', 'از', 'را', 'که', 'من', 'این', 'آن', 'کجاست', 'می‌خواهم', 'لطفا'];

        $filtered = array_filter($words, fn (string $w) => mb_strlen($w) >= 3 && ! in_array($w, $stopWords, true));

        return array_values(array_slice($filtered, 0, 5));
    }
}
