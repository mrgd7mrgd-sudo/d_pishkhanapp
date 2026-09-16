<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Shared\Errors\ErrorCode;
use App\Shared\Http\Middleware\RequestId;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
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
        if ($e instanceof HttpResponseException) {
            $response = $e->getResponse();
            if ($response instanceof JsonResponse) {
                return $response;
            }
        }

        $requestId = (string) $request->header(RequestId::HEADER_NAME, 'req_unknown');
        $instance = $request->path();

        if ($e instanceof ValidationException) {
            $errors = [];
            $hasNationalIdError = false;
            foreach ($e->errors() as $field => $messages) {
                $errors[$field] = $messages;
                if ($field === 'national_id') {
                    $hasNationalIdError = true;
                }
            }

            $code = $hasNationalIdError ? ErrorCode::AUTH_NATIONAL_ID_INVALID->value : 'VALIDATION_ERROR';
            $title = $hasNationalIdError ? ErrorCode::AUTH_NATIONAL_ID_INVALID->title() : 'خطای اعتبارسنجی داده‌های ورودی';
            $detail = $hasNationalIdError ? ErrorCode::AUTH_NATIONAL_ID_INVALID->defaultDetail() : 'داده‌های ارسال‌شده با قوانین سامانه مطابقت ندارند.';

            return new JsonResponse([
                'type' => 'https://api.pishkhan.ir/problems/'.strtolower(str_replace('_', '-', $code)),
                'title' => $title,
                'status' => 422,
                'code' => $code,
                'detail' => $detail,
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
        $headers = [];

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $headers = $e->getHeaders();
            if (method_exists($e, 'getErrorCode')) {
                /** @var mixed $errCode */
                $errCode = $e->getErrorCode();
                $code = $errCode instanceof ErrorCode ? $errCode->value : (string) $errCode;
                $title = $errCode instanceof ErrorCode ? $errCode->title() : 'خطای دامنه';
                $detail = $e->getMessage() ?: ($errCode instanceof ErrorCode ? $errCode->defaultDetail() : 'درخواست غیرمجاز است.');
            } elseif ($status === 404) {
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

        $payload = [
            'type' => 'https://api.pishkhan.ir/problems/'.strtolower(str_replace('_', '-', $code)),
            'title' => $title,
            'status' => $status,
            'code' => $code,
            'detail' => $detail,
            'instance' => $instance,
            'request_id' => $requestId,
            'errors' => null,
        ];

        if ($status === 429 && isset($headers['Retry-After'])) {
            $payload['retry_after'] = (int) $headers['Retry-After'];
        }

        return new JsonResponse($payload, $status, $headers);
    }
}
