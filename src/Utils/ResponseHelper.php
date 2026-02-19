<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Фабрика для стандартизированных HTTP ответов.
 * Обеспечивает консистентный формат JSON ответов.
 */
final class ResponseHelper
{
    public static function json(array $data, int $status = 200): void
    {
        // START_CONTRACT_json
        // Intent: Отправить JSON ответ клиенту
        // Input: data (array), status (HTTP код)
        // Output: void (отправляет HTTP ответ)
        // END_CONTRACT_json
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public static function success(array $data = []): void
    {
        self::json(['success' => true, 'data' => $data]);
    }

    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        // START_CONTRACT_error
        // Intent: Отправить ошибку клиенту (без внутренних деталей)
        // Input: message, status, errors
        // Output: void (отправляет HTTP ответ с ошибкой)
        // END_CONTRACT_error
        $response = [
            'success' => false,
            'error' => $message,
        ];
        if ($errors !== []) {
            $response['errors'] = $errors;
        }
        self::json($response, $status);
    }

    public static function redirect(string $url, int $code = 302): void
    {
        // START_CONTRACT_redirect
        // Intent: Выполнить HTTP редирект
        // Input: url (целевой URL), code (HTTP код редиректа)
        // Output: void (отправляет Location header)
        // END_CONTRACT_redirect
        http_response_code($code);
        header("Location: {$url}");
    }

    public static function notFound(string $message = 'Not Found'): void
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    public static function tooManyRequests(int $retryAfter = 60): void
    {
        header("Retry-After: {$retryAfter}");
        self::error('Too Many Requests', 429);
    }

    public static function image(string $data, string $contentType = 'image/png'): void
    {
        http_response_code(200);
        header("Content-Type: {$contentType}");
        header('Content-Length: ' . strlen($data));
        echo $data;
    }
}
