<?php

declare(strict_types=1);

use App\Config\Config;
use App\Controller\AuthController;
use App\Controller\LinkController;
use App\Controller\UserController;
use App\Middleware\CorsMiddleware;
use App\Router\Router;
use App\Utils\Logger;
use App\Utils\ResponseHelper;

// Autoload
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Start session
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict',
]);

// Initialize
$config = Config::getInstance();
$logger = Logger::getInstance();

// CORS Middleware
$corsMiddleware = new CorsMiddleware();
$corsMiddleware->handle($_SERVER, function () {});

// Router setup
$router = new Router();

// Controllers
$linkController = new LinkController();
$authController = new AuthController();
$userController = new UserController();

// Health check (must be before /{code} to avoid being captured)
$router->get('/health', function () {
    ResponseHelper::json(['status' => 'ok', 'timestamp' => date('c')]);
});

// Public routes
$router->post('/api/v1/links', [$linkController, 'create']);
$router->get('/api/v1/links/{code}', [$linkController, 'show']);
$router->get('/{code}', [$linkController, 'redirect']);
$router->get('/{code}/qr', [$linkController, 'qrCode']);

// Auth routes
$router->post('/api/v1/auth/register', [$authController, 'register']);
$router->post('/api/v1/auth/login', [$authController, 'login']);
$router->post('/api/v1/auth/logout', [$authController, 'logout']);
$router->get('/api/v1/auth/me', [$authController, 'me']);

// User routes (protected)
$router->get('/api/v1/user/links', [$userController, 'listLinks']);
$router->delete('/api/v1/user/links/{id}', [$userController, 'deleteLink']);
$router->get('/api/v1/user/links/{id}/stats', [$userController, 'getStats']);

// Dispatch
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = $_SERVER['REQUEST_URI'] ?? '/';

$match = $router->dispatch($method, $path);

if ($match === null) {
    $logger->debug('[FrontController] Route not found', ['method' => $method, 'path' => $path]);
    ResponseHelper::notFound('Route not found');
    exit;
}

$handler = $match->getHandler();
$params = $match->getParams();

try {
    if (is_callable($handler)) {
        if (is_array($handler)) {
            $controller = $handler[0];
            $method = $handler[1];
            $controller->$method($params, $_SERVER);
        } else {
            $handler();
        }
    }
} catch (Throwable $e) {
    $logger->error('[FrontController] Error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    
    $debug = $config->getBool('APP_DEBUG', false);
    if ($debug) {
        ResponseHelper::error($e->getMessage(), 500, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    } else {
        ResponseHelper::error('Internal server error', 500);
    }
}
