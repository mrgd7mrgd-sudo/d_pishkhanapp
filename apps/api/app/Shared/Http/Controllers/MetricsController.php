<?php

declare(strict_types=1);

namespace App\Shared\Http\Controllers;

use App\Shared\Metrics\DomainMetrics;
use Illuminate\Http\Response;

final class MetricsController
{
    public function __invoke(): Response
    {
        $metrics = DomainMetrics::render();

        return new Response($metrics, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }
}
