<?php

declare(strict_types=1);

namespace App\Tests\Unit\Router;

use App\Router\RouteMatch;
use App\Router\Router;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Router class.
 * Tests route matching, parameter extraction, and HTTP method handling.
 */
final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    // ========== HAPPY PATH TESTS ==========

    public function testAddRouteRegistersRoute(): void
    {
        $handler = fn() => 'test';
        $this->router->addRoute('GET', '/test', $handler);

        $match = $this->router->dispatch('GET', '/test');

        $this->assertNotNull($match);
        $this->assertSame($handler, $match->getHandler());
    }

    public function testDispatchReturnsRouteMatchForExactPath(): void
    {
        $handler = fn() => 'response';
        $this->router->get('/api/links', $handler);

        $match = $this->router->dispatch('GET', '/api/links');

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertSame($handler, $match->getHandler());
        $this->assertEquals([], $match->getParams());
    }

    public function testDispatchExtractsSingleParameter(): void
    {
        $this->router->get('/links/{id}', fn() => 'link');

        $match = $this->router->dispatch('GET', '/links/abc123');

        $this->assertNotNull($match);
        $this->assertEquals(['id' => 'abc123'], $match->getParams());
    }

    public function testDispatchExtractsMultipleParameters(): void
    {
        $this->router->get('/users/{userId}/links/{linkId}', fn() => 'link');

        $match = $this->router->dispatch('GET', '/users/42/links/abc123');

        $this->assertNotNull($match);
        $this->assertEquals([
            'userId' => '42',
            'linkId' => 'abc123',
        ], $match->getParams());
    }

    public function testDispatchMatchesDifferentHttpMethods(): void
    {
        $getHandler = fn() => 'GET';
        $postHandler = fn() => 'POST';
        $deleteHandler = fn() => 'DELETE';

        $this->router->get('/resource', $getHandler);
        $this->router->post('/resource', $postHandler);
        $this->router->delete('/resource', $deleteHandler);

        $this->assertSame($getHandler, $this->router->dispatch('GET', '/resource')?->getHandler());
        $this->assertSame($postHandler, $this->router->dispatch('POST', '/resource')?->getHandler());
        $this->assertSame($deleteHandler, $this->router->dispatch('DELETE', '/resource')?->getHandler());
    }

    public function testDispatchNormalizesPath(): void
    {
        $this->router->get('/links', fn() => 'links');

        // Path without leading slash
        $match1 = $this->router->dispatch('GET', 'links');
        $this->assertNotNull($match1);

        // Path with trailing slash
        $match2 = $this->router->dispatch('GET', '/links/');
        $this->assertNotNull($match2);

        // Path with query string
        $match3 = $this->router->dispatch('GET', '/links?foo=bar');
        $this->assertNotNull($match3);
    }

    public function testDispatchIsCaseInsensitiveForMethod(): void
    {
        $this->router->get('/test', fn() => 'test');

        $match = $this->router->dispatch('get', '/test');

        $this->assertNotNull($match);
    }

    // ========== ADVERSARIAL TESTS ==========

    public function testDispatchReturnsNullForNonExistentRoute(): void
    {
        $match = $this->router->dispatch('GET', '/nonexistent');

        $this->assertNull($match);
    }

    public function testDispatchReturnsNullForWrongMethod(): void
    {
        $this->router->get('/test', fn() => 'test');

        $match = $this->router->dispatch('POST', '/test');

        $this->assertNull($match);
    }

    public function testDispatchReturnsNullForPartialMatch(): void
    {
        $this->router->get('/links', fn() => 'links');

        // Partial path should not match
        $match = $this->router->dispatch('GET', '/links/extra');

        $this->assertNull($match);
    }

    public function testDispatchHandlesEmptyPath(): void
    {
        $this->router->get('/', fn() => 'root');

        $match = $this->router->dispatch('GET', '');

        $this->assertNotNull($match);
    }

    public function testDispatchHandlesSpecialCharactersInParams(): void
    {
        $this->router->get('/links/{code}', fn() => 'link');

        // Base62 characters (valid short codes)
        $match = $this->router->dispatch('GET', '/links/AbC123xYz');
        $this->assertNotNull($match);
        $this->assertEquals('AbC123xYz', $match->getParam('code'));
    }

    public function testDispatchDoesNotMatchSlashesInParams(): void
    {
        $this->router->get('/links/{code}', fn() => 'link');

        // Slashes should not be captured in params
        $match = $this->router->dispatch('GET', '/links/abc/def');

        $this->assertNull($match);
    }

    public function testOptionsMethodIsSupported(): void
    {
        $this->router->options('/test', fn() => 'options');

        $match = $this->router->dispatch('OPTIONS', '/test');

        $this->assertNotNull($match);
    }

    public function testRouteMatchGetParamReturnsDefaultForMissingKey(): void
    {
        $this->router->get('/test', fn() => 'test');

        $match = $this->router->dispatch('GET', '/test');

        $this->assertEquals('default', $match?->getParam('nonexistent', 'default'));
    }

    public function testMultipleRoutesWithSamePatternButDifferentMethods(): void
    {
        $getHandler = fn() => 'GET';
        $postHandler = fn() => 'POST';

        $this->router->get('/resource', $getHandler);
        $this->router->post('/resource', $postHandler);

        $this->assertSame($getHandler, $this->router->dispatch('GET', '/resource')?->getHandler());
        $this->assertSame($postHandler, $this->router->dispatch('POST', '/resource')?->getHandler());
    }
}
