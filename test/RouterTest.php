<?php

namespace Sylviavdv\Router\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylviavdv\Router\Attributes\Route;
use Sylviavdv\Router\Loader\RouteLoader;
use Sylviavdv\Router\RouteCollection;
use Sylviavdv\Router\Router;

class RouterTest extends TestCase
{
    public function testEnableCache(): void
    {
        $file = __DIR__ . '/tmp/routes.php';
        $this->assertFileDoesNotExist($file);
        $routes = RouteLoader::load(__DIR__ . '/Controller',  $file);
        $this->assertFileExists($file);

        require_once $file;
        /** @var RouteCollection $routeCollection */
        $this->assertNotEmpty($routeCollection->getRoutes());

        unlink($file);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function routesProvider(): array
    {
        return [
            // Positive cases                    uri                  method   expected            postParams                    getParams
            'index - GET /index'              => ['/index',           'GET',  'index',           null,                         null],
            'users - GET /users'              => ['/users',           'GET',  'users',           null,                         null],
            'show - GET /users/{id}'          => ['/users/42',        'GET',  'show 42',         null,                         null],
            'withParams - digit id'           => ['/test/123',        'GET',  'withParams 123',  null,                         null],
            'create - POST /users'            => ['/users',           'POST', 'create',          null,                         null],
            'tag - without lang prefix'       => ['/blog/tag/php',    'GET',  'tag php',         null,                         null],
            'tag - with lang prefix'          => ['/en/blog/tag/php', 'GET',  'tag php',         null,                         null],
            'home - root'                     => ['/',                 'GET',  'home default',    null,                         null],
            'home - /home'                    => ['/home',            'GET',  'home home',       null,                         null],
            'file - high priority over slug'  => ['/files/my-photo',  'GET',  'file',            null,                         null],
            'page - catch-all slug'           => ['/about',           'GET',  'page',            null,                         null],
            'search - with q param'           => ['/search',          'GET',  'search hello',    null,                         ['q' => 'hello']],
            'login - valid password'          => ['/login',           'POST', 'login',           ['password' => 'secretpass'], null],

            // Negative cases (no output, 404)
            'withParams - non-digit id'       => ['/test/abc',        'GET',  '',           null,                         null],
            'show - wrong method'             => ['/users/42',        'POST', '',           null,                         null],
            'login - missing password'        => ['/login',           'POST', '',           null,                         null],
            'login - password too short'      => ['/login',           'POST', '',           ['password' => 'short'],      null],
            'no match - multi-segment path'   => ['/a/b/c',           'GET',  '',           null,                         null],
        ];
    }

    /**
     * @param array<string, mixed> $postParams
     * @param array<string, string> $getParams
     */
    #[DataProvider('routesProvider')]
    public function testRouter(string $uri, string $method, string $expectedOutput, ?array $postParams, ?array $getParams): void
    {
        $_SERVER['REQUEST_URI']    = $uri;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_POST = $postParams ?? [];
        $_GET  = $getParams  ?? [];

        $routes = RouteLoader::load(__DIR__ . '/Controller', disableCache: true);
        $router = new Router($routes);

        ob_start();
        $router->handleRequest();
        $output = ob_get_clean();

        $this->assertSame($expectedOutput, $output);
    }

    public function testFilter(): void
    {
        $_SERVER['REQUEST_URI']    = '/view';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
        $_GET  = [];

        $routes = RouteLoader::load(__DIR__ . '/Controller', disableCache: true);
        $router = new Router($routes);
        $route = $router->findRoute(function($route) {
            return isset($route->meta['for_admins']) && $route->meta['for_admins'] === true;
        });
        ob_start();
        $route->process();
        $output = ob_get_clean();
        $this->assertSame('adminView', $output);

        $route = $router->findRoute(function($route) {
            return isset($route->meta['for_admins']) && $route->meta['for_admins'] === false;
        });
        ob_start();
        $route->process();
        $output = ob_get_clean();
        $this->assertSame('userView', $output);
    }
}
