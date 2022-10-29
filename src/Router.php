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

    public function findRoute(): ?Route
    {
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routeCollection->getRoutes() as $route) {
            if ($route->isMatch($uri, $method)) {
                return $route;
            }
        }

        return null;
    }
}