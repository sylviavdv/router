<?php

namespace Sylviavdv\Router\Attributes;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class Route
{
    protected string $compiledPath;

    public readonly string $controllerName;

    protected object $controllerObject;

    protected string $controllerMethod;

    protected array $methodParams = [];

    public function __construct(public readonly string $path, public readonly string $method = "", protected array $params = [], protected array $postRequirements = [], protected array $getRequirements = [], public readonly int $priority = 1)
    {
    }

    public function configure(string $controllerMethod, string $controllerName, array $methodParams)
    {
         $this->controllerMethod = $controllerMethod;
         $this->controllerName = $controllerName;
         $this->methodParams = array_fill_keys($methodParams, null);

         $this->compilePath();
    }

    public function isMatch(string $uri, string $method): bool
    {
        if(!empty($this->method) && strtoupper($this->method) !== $method) {
            return false;
        }

        if (!preg_match('/'.$this->compiledPath.'/i', $uri, $urlParams)) {
            return false;
        }

        foreach ($this->postRequirements as $key => $regex) {
            if (!array_key_exists($key, $_POST) || !preg_match('/^'.$regex.'$/', $_POST[$key])) {
                return false;
            }
        }

        foreach ($this->getRequirements as $key => $regex) {
            if (!array_key_exists($key, $_GET) || !preg_match('/^'.$regex.'$/', $_GET[$key])) {
                return false;
            }
        }

        $variables = array_keys($this->methodParams);
        foreach ($variables as $variable) {
            if (isset($urlParams[$variable])) {
                $this->methodParams[$variable] = $urlParams[$variable];
            } elseif (isset($_POST[$variable])) {
               $this->methodParams[$variable] = $_POST[$variable];
            }  elseif (isset($_GET[$variable])) {
               $this->methodParams[$variable] = $_GET[$variable];
            }
        }

        return true;
    }

    public function process() : void
    {
        [$this->getControllerObject(), $this->controllerMethod](...$this->methodParams);
    }

    public function getControllerObject(): object
    {
        if (!isset($this->controllerObject)) {
            $this->controllerObject = new ($this->controllerName)();
        }

        return $this->controllerObject;
    }


    public static function __set_state(array $values)
    {
        $route = new Route($values['path'], $values['method'], $values['params'], $values['postRequirements'], $values['getRequirements']);
        $route->compiledPath = $values['compiledPath'];
        $route->controllerName = $values['controllerName'];
        $route->controllerMethod = $values['controllerMethod'];
        $route->methodParams = $values['methodParams'];

        return $route;
    }

    protected function compilePath(): void
    {
        $this->compiledPath = $this->path;
        $regex = '#\{([a-z_-]+)\}#i';
        preg_match_all($regex, $this->path, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $str = $match[0];
            $name = $match[1];
            if (isset($this->params[$name])) {
                $regexPart = $this->params[$name];
            } else {
                $regexPart = '[a-z0-9_-]+';
            }
            $regex = '(?<'.$name.'>'.$regexPart.')';
            $this->compiledPath = str_replace($str, $regex, $this->compiledPath);
        }

        if (!str_starts_with('/', $this->compiledPath)) {
            $this->compiledPath = '/'.$this->compiledPath;
        }
        $this->compiledPath = str_replace('/','\/',$this->compiledPath);
        $this->compiledPath = '^'.$this->compiledPath.'$';
    }
}