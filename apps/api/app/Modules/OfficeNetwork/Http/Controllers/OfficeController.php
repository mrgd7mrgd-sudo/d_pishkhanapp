<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Controllers;

use App\Modules\OfficeNetwork\Application\Queries\GetOfficesQuery;
use App\Modules\OfficeNetwork\Application\Queries\OfficeFinder;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use App\Modules\OfficeNetwork\Http\Resources\OfficeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

final class OfficeController
{
    public function __construct(
        private readonly GetOfficesQuery $getOfficesQuery,
        private readonly OfficeFinder $officeFinder
    ) {}

    /**
     * List offices with filters, sorting, and cursor pagination (§5.6 #4, TASK-043).
     */
    public function index(Request $request): JsonResponse|Response
    {
        $onlyOnline = $request->boolean('only_online');
        $provinceCode = $request->string('province_code')->trim()->value() ?: null;
        $categoryId = $request->input('category_id');

        $result = $this->getOfficesQuery->execute([
            'only_online' => $onlyOnline,
            'province_code' => $provinceCode,
            'category_id' => is_string($categoryId) ? $categoryId : null,
            'sort' => $request->string('sort')->trim()->value() ?: null,
            'limit' => $request->has('limit') ? $request->integer('limit') : null,
            'cursor' => $request->string('cursor')->trim()->value() ?: null,
        ]);

        $paginator = $result['paginator'];

        $payload = [
            'data' => OfficeResource::collection($paginator->items())->resolve(),
            'meta' => [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'total_estimate' => $result['total_estimate'],
            ],
        ];

        return $this->buildCachedResponse($request, $payload);
    }

    /**
     * Find nearby offices using PostGIS smart scoring formula (§5.6 #4, TASK-043).
     */
    public function nearby(Request $request): JsonResponse|Response
    {
        $validator = Validator::make($request->all(), [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'category_id' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'type' => 'https://tools.ietf.org/html/rfc7231#section-6.5.1',
                'title' => 'مختصات جغرافیایی نامعتبر است',
                'status' => 422,
                'detail' => 'پارامترهای lat و lng باید اعداد معتبر باشند.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $radiusKm = (float) ($request->input('radius_km') ?? 10.0);
        $categoryId = $request->string('category_id')->trim()->value() ?: null;
        $limit = (int) ($request->input('limit') ?? 20);

        $results = $this->officeFinder->findNearby($lat, $lng, $radiusKm, $categoryId, $limit);

        $data = [];
        foreach ($results as $item) {
            /** @var Office $office */
            $office = $item['office'];
            $resource = (new OfficeResource($office))->withContext([
                'distance_km' => $item['distance_km'],
                'smart_score' => $item['smart_score'],
                'coords' => $item['coords'],
            ]);
            $data[] = $resource->resolve();
        }

        $payload = [
            'data' => $data,
            'meta' => [
                'center' => ['lat' => $lat, 'lng' => $lng],
                'radius_km' => $radiusKm,
                'count' => count($data),
            ],
        ];

        return $this->buildCachedResponse($request, $payload);
    }

    /**
     * Retrieve single office details by UUID or 4-digit code (§5.6 #4, TASK-043).
     */
    public function show(Request $request, string $id): JsonResponse|Response
    {
        $office = Office::query()
            ->with(['specialties', 'medals', 'serviceCoverages'])
            ->where('id', $id)
            ->orWhere('code', $id)
            ->first();

        if ($office === null) {
            return new JsonResponse([
                'type' => 'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                'title' => 'دفتر پیشخوان یافت نشد',
                'status' => 404,
                'detail' => "دفتر پیشخوانی با شناسه '{$id}' در سامانه یافت نشد.",
                'code' => 'OFFICE_NOT_FOUND',
            ], 404);
        }

        $payload = [
            'data' => (new OfficeResource($office))->resolve(),
        ];

        return $this->buildCachedResponse($request, $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildCachedResponse(Request $request, array $data, int $status = 200): JsonResponse|Response
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $etag = '"'.md5($encoded !== false ? $encoded : '').'"';

        $headers = [
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=300, stale-while-revalidate=86400',
        ];

        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch !== null && trim($ifNoneMatch) === $etag) {
            return new Response('', 304, $headers);
        }

        return new JsonResponse($data, $status, $headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
