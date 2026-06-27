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
    public static function load(string $path, string $cacheFile = 'var/cache/routes.php', bool $disableCache = false): RouteCollection
    {
        if (empty($cacheFile)) {
            throw new \InvalidArgumentException('Cache file path cannot be empty.');
        }
        if (file_exists($cacheFile) && !$disableCache) {
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
                    $route = self::configureRoute($attribute, $method, $class);
                    $routeCollection->addRoute($route);
                }
            }
        }

        $routeCollection->sort();

        if (!$disableCache) {
            self::writeCollection($cacheFile, $routeCollection);
        }

        return $routeCollection;
    }

    protected static function writeCollection(string $path, RouteCollection $routeCollection): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $fp = fopen($path, 'w');
        fwrite($fp, '<?php  $routeCollection = '.var_export($routeCollection, true).';');
        fclose($fp);
    }

    /**
     * @param ReflectionAttribute<Route> $attribute
     */
    protected static function configureRoute(ReflectionAttribute $attribute, ReflectionMethod $method, string $controller): Route
    {
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