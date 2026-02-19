<?php

declare(strict_types=1);

namespace App\Router;

/**
 * Маршрутизатор HTTP запросов.
 * Сопоставляет URL паттерны с обработчиками.
 */
final class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $pattern, mixed $handler): void
    {
        // START_CONTRACT_addRoute
        // Intent: Зарегистрировать маршрут
        // Input: method (GET|POST|DELETE), pattern (URL паттерн), handler (callable)
        // Output: void
        // END_CONTRACT_addRoute
        $key = strtoupper($method) . ':' . $pattern;
        $this->routes[$key] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'regex' => $this->patternToRegex($pattern),
        ];
    }

    public function dispatch(string $method, string $path): ?RouteMatch
    {
        // START_CONTRACT_dispatch
        // Intent: Найти подходящий маршрут и извлечь параметры
        // Input: method (HTTP метод), path (URL путь)
        // Output: RouteMatch или null если маршрут не найден
        // END_CONTRACT_dispatch
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches)) {
                $params = array_filter(
                    $matches,
                    fn($key) => is_string($key),
                    ARRAY_FILTER_USE_KEY
                );
                return new RouteMatch($route['handler'], $params);
            }
        }

        return null;
    }

    public function get(string $pattern, mixed $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, mixed $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function delete(string $pattern, mixed $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    public function options(string $pattern, mixed $handler): void
    {
        $this->addRoute('OPTIONS', $pattern, $handler);
    }

    private function patternToRegex(string $pattern): string
    {
        // Конвертируем {param} в именованные группы regex
        $regex = preg_replace(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            '(?P<$1>[^/]+)',
            $pattern
        );
        return '#^' . $regex . '$#';
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? '/';
        return '/' . trim($path, '/');
    }
}
