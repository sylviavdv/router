<?php

namespace Sylviavdv\Router;

use Sylviavdv\Router\Attributes\Route;

class Router
{
    public function __construct(protected RouteCollection $routeCollection)
    {
    }

    public function handleRequest()
    {
        $route = $this->findRoute();
        if (is_null($route)) {
            http_response_code(404);
            return;
        }

        $route->process();
    }

    public function findRoute(?callable $filterCallback = null): ?Route
    {
        $baseDir = dirname($_SERVER['PHP_SELF']);
        if (strlen($baseDir) === 1) {
            $baseDir = '';
        }
        
        $uri = str_replace($baseDir, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $method = $_SERVER['REQUEST_METHOD'];

        $routes = $this->routeCollection->getRoutes();
        if (!is_null($filterCallback)) {
            $routes = array_filter($routes, $filterCallback);
        }

        foreach ($routes as $route) {
            if ($route->isMatch($uri, $method)) {
                return $route;
            }
        }

        return null;
    }
}
