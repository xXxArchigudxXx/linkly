<?php

declare(strict_types=1);

namespace App\Router;

/**
 * Результат сопоставления маршрута.
 * Содержит обработчик и извлечённые параметры.
 */
final class RouteMatch
{
    public function __construct(
        private readonly mixed $handler,
        private readonly array $params = []
    ) {
    }

    public function getHandler(): mixed
    {
        // START_CONTRACT_getHandler
        // Intent: Получить обработчик маршрута (callable или controller action)
        // Input: None
        // Output: mixed - обработчик
        // END_CONTRACT_getHandler
        return $this->handler;
    }

    public function getParams(): array
    {
        // START_CONTRACT_getParams
        // Intent: Получить параметры извлечённые из URL
        // Input: None
        // Output: array - параметры маршрута
        // END_CONTRACT_getParams
        return $this->params;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }
}
