<?php

declare(strict_types=1);

namespace App\Shared\Http\Middleware;

use App\Modules\Identity\Domain\Models\Operator;
use App\Modules\OfficeNetwork\Domain\Models\Office;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureOfficeScope
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user instanceof Operator) {
            abort(Response::HTTP_NOT_FOUND, 'Resource not found.');
        }

        if ($user->hasRole('system_admin')) {
            return $next($request);
        }

        if ($user->office_id === null) {
            abort(Response::HTTP_NOT_FOUND, 'Office not found.');
        }

        $this->verifyOfficeParam($request, $user);
        $this->verifyOperatorParam($request, $user);
        $this->verifyHeader($request, $user);

        return $next($request);
    }

    private function verifyOfficeParam(Request $request, Operator $user): void
    {
        $officeParam = $request->route('office') ?? $request->route('office_id');

        if ($officeParam instanceof Office) {
            if ($officeParam->id !== $user->office_id) {
                abort(Response::HTTP_NOT_FOUND, 'Office not found.');
            }
        } elseif (is_string($officeParam) && $officeParam !== '' && $officeParam !== $user->office_id) {
            abort(Response::HTTP_NOT_FOUND, 'Office not found.');
        }
    }

    private function verifyOperatorParam(Request $request, Operator $user): void
    {
        $operatorParam = $request->route('operator');

        if ($operatorParam instanceof Operator) {
            if ($operatorParam->office_id !== $user->office_id) {
                abort(Response::HTTP_NOT_FOUND, 'Operator not found.');
            }
        } elseif (is_string($operatorParam) && $operatorParam !== '') {
            $target = Operator::withoutGlobalScopes()->find($operatorParam);
            if ($target !== null && $target->office_id !== $user->office_id) {
                abort(Response::HTTP_NOT_FOUND, 'Operator not found.');
            }
        }
    }

    private function verifyHeader(Request $request, Operator $user): void
    {
        $headerOfficeId = $request->header('X-Office-Id');

        if ($headerOfficeId !== null && $headerOfficeId !== '' && $headerOfficeId !== $user->office_id) {
            abort(Response::HTTP_NOT_FOUND, 'Office not found.');
        }
    }
}
