<?php

declare(strict_types=1);

namespace App\Modules\OfficeNetwork\Http\Controllers;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use Illuminate\Http\JsonResponse;

final class DeskOfficeController
{
    public function show(Office $office): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'id' => $office->id,
                'code' => $office->code,
                'name' => $office->name,
                'is_online' => $office->is_online,
            ],
        ]);
    }

    public function operators(Office $office): JsonResponse
    {
        $operators = $office->operators()
            ->get(['id', 'office_id', 'username', 'full_name', 'counter_number', 'is_active']);

        return new JsonResponse([
            'status' => 'success',
            'data' => $operators,
        ]);
    }

    public function showOperator(Operator $operator): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'id' => $operator->id,
                'office_id' => $operator->office_id,
                'username' => $operator->username,
                'full_name' => $operator->full_name,
                'counter_number' => $operator->counter_number,
                'is_active' => $operator->is_active,
            ],
        ]);
    }
}
