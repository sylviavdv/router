<?php

namespace Sylviavdv\Router\Loader;

use Composer\ClassMapGenerator\ClassMapGenerator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Sylviavdv\Router\Attributes\Route;
use Sylviavdv\Router\RouteCollection;

class RouteLoader
{
    public static function load(string $path, string $cacheFile = 'routes.php',  string $prefix = ''): RouteCollection
    {
        if (!empty($cacheFile) && file_exists($cacheFile) && (!defined('FORCE_LOAD_ROUTES') || !FORCE_LOAD_ROUTES)) {
            require_once $cacheFile;

            /** @var RouteCollection $routeCollection */
            return $routeCollection;
        }

        $routeCollection = new RouteCollection();
        $map = ClassMapGenerator::createMap($path);
        $classes = array_keys($map);
        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getMethods() as $method) {
                $attributes = $method->getAttributes(Route::class);
                if (empty($attributes)) {
                    continue;
                }
                foreach ($attributes as $attribute) {
                    $route = self::configureRoute($attribute, $method, $class, $prefix);
                    $routeCollection->addRoute($route);
                }
            }
        }

        $routeCollection->sort();

        if (!empty($cacheFile)) {
            self::writeCollection($cacheFile, $routeCollection);
        }

        return $routeCollection;
    }

    protected static function writeCollection(string $path, RouteCollection $routeCollection) {
        $fp = fopen($path, 'w');
        fwrite($fp, '<?php  $routeCollection = '.var_export($routeCollection, true).';');
        fclose($fp);
    }

    protected static function configureRoute(ReflectionAttribute $attribute, ReflectionMethod $method, string $controller, string $prefix): Route
    {
        /** @var Route $route */
        $route = $attribute->newInstance();
        $parameters = array_column($method->getParameters(), 'name');
        $route->configure(
            controllerMethod: $method->getName(),
            controllerName: $controller,
            methodParams: $parameters
        );

        return $route;
    }
}