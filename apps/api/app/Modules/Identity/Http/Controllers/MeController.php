<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Domain\Models\Citizen;
use App\Modules\Identity\Http\Resources\CitizenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController
{
    /**
     * Get the authenticated citizen's profile (§5.6, TASK-028).
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Citizen $citizen */
        $citizen = $request->user();

        return new JsonResponse([
            'data' => [
                'citizen' => new CitizenResource($citizen),
            ],
        ], 200);
    }
}
