<?php

namespace Sylviavdv\Router;

use Sylviavdv\Router\Attributes\Route;

class RouteCollection
{
    /**
     * @var array<Route>
     */
    protected array $routes = [];

    public function addRoute(Route $route)
    {
        $this->routes[] = $route;
    }

    /**
     * @return array<Route>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public static function __set_state(array $values)
    {
        $routeCollection = new RouteCollection();
        $routeCollection->routes = $values['routes'];

        return $routeCollection;
    }
}