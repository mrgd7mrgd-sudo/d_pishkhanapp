<?php

declare(strict_types=1);

use App\Shared\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Architecture §5.2)
|--------------------------------------------------------------------------
|
| Module routes are automatically discovered and registered by ModuleServiceProvider
| under the /api/v1 prefix. Global fallback/meta routes can be defined here.
|
*/

Route::get('/', function () {
    return response()->json([
        'name' => 'Pishkhan Citizen & Smart Office API',
        'version' => 'v1',
        'status' => 'active',
    ]);
});

Route::get('/v1/health', HealthController::class);

Route::post('/test/validation', function (Request $request) {
    $validated = $request->validate([
        'national_id' => ['required', 'string', 'digits:10'],
    ]);

    return response()->json(['success' => true, 'data' => $validated]);
});

Route::middleware('idempotent')->post('/test/payment', function (Request $request) {
    static $executionCount = 0;
    $executionCount++;

    return response()->json([
        'status' => 'paid',
        'execution_count' => $executionCount,
        'amount_rials' => $request->input('amount_rials', 100000),
    ]);
});
