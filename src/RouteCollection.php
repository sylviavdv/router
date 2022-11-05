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

    public function sort(): void
    {
        usort ($this->routes, function(Route $a, Route $b) {
            if ($a->priority === $b->priority) {
                return $a->path <=> $b->path;
            }

            return -1 * ($a->priority <=> $b->priority);
        });
    }

    public static function __set_state(array $values)
    {
        $routeCollection = new RouteCollection();
        $routeCollection->routes = $values['routes'];

        return $routeCollection;
    }
}