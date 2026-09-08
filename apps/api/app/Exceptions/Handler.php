<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Shared\Errors\ErrorCode;
use App\Shared\Http\Middleware\RequestId;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class Handler
{
    /**
     * Render an exception into an RFC 7807 Problem Details HTTP response.
     */
    public static function render(Throwable $e, Request $request): JsonResponse
    {
        $requestId = (string) $request->header(RequestId::HEADER_NAME, 'req_unknown');
        $instance = $request->path();

        if ($e instanceof ValidationException) {
            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                $errors[$field] = $messages;
            }

            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/problems/validation-error',
                'title' => 'خطای اعتبارسنجی داده‌های ورودی',
                'status' => 422,
                'code' => 'VALIDATION_ERROR',
                'detail' => 'داده‌های ارسال‌شده با قوانین سامانه مطابقت ندارند.',
                'instance' => $instance,
                'request_id' => $requestId,
                'errors' => $errors,
            ], 422);
        }

        if ($e instanceof AuthenticationException) {
            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/problems/unauthenticated',
                'title' => 'احراز هویت انجام نشده است',
                'status' => 401,
                'code' => 'AUTH_UNAUTHENTICATED',
                'detail' => 'برای دسترسی به این بخش، ابتدا باید وارد حساب کاربری خود شوید.',
                'instance' => $instance,
                'request_id' => $requestId,
                'errors' => null,
            ], 401);
        }

        $status = 500;
        $title = 'خطای داخلی سرور';
        $code = 'INTERNAL_SERVER_ERROR';
        $detail = 'متأسفانه خطایی در پردازش درخواست شما رخ داد.';

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            if ($status === 404) {
                $title = 'منبع مورد نظر یافت نشد';
                $code = 'RESOURCE_NOT_FOUND';
                $detail = 'شناسه یا مسیر درخواستی در سامانه وجود ندارد.';
            } elseif ($status === 403) {
                $title = 'دسترسی غیرمجاز';
                $code = 'FORBIDDEN';
                $detail = 'شما مجوز لازم برای انجام این عملیات را ندارید.';
            } elseif ($status === 429) {
                $title = ErrorCode::RATE_LIMITED->title();
                $code = ErrorCode::RATE_LIMITED->value;
                $detail = ErrorCode::RATE_LIMITED->defaultDetail();
            }
        }

        return new JsonResponse([
            'type' => 'https://api.pishkhan.ir/problems/'.strtolower(str_replace('_', '-', $code)),
            'title' => $title,
            'status' => $status,
            'code' => $code,
            'detail' => $detail,
            'instance' => $instance,
            'request_id' => $requestId,
            'errors' => null,
        ], $status);
    }
}
