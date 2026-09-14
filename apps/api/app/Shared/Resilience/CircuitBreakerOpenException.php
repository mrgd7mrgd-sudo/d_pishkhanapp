<?php

declare(strict_types=1);

namespace App\Shared\Resilience;

use RuntimeException;

final class CircuitBreakerOpenException extends RuntimeException {}
