## Simple PHP Router

Route incoming HTTP requests to controller methods using PHP 8 attributes. 
Use regex, parameter requirements, and HTTP method filtering to define your routes.   
The router automatically scans your controller classes caches the results for fast dispatching.


## Requirements

- PHP 8.1+

## Installation

```bash
composer require sylviavdv/router
```

## Web server setup

Rewrite all requests that you want the router to handle to a single file  (eg. `index.php`).  

For **Apache**, place an `.htaccess` file in your document root.

```apacheconf
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^.*$ index.php [L]
```

For **Nginx**, add the following to your server block.

```nginx
location / {
    try_files $uri $uri/ /index.php;
}
```

## Bootstrapping the router

Create an `index.php` in your document root.

```php
<?php

use Sylviavdv\Router\Loader\RouteLoader;
use Sylviavdv\Router\Router;

require_once 'vendor/autoload.php';

$routes = RouteLoader::load(__DIR__ . '/src/Controller');
$router = new Router($routes);

$router->handleRequest();
```

`RouteLoader::load()` accepts:

| Parameter | Type     | Default                | Description                                             |
|----------|----------|------------------------|---------------------------------------------------------|
| `$path`  | `string` | —                      | Directory to scan for controller classes                |
| `$cacheFile` | `string` | `var/cache/routes.php` | Path to the route cache file                            |
| `$disableCache`       | `bool`   | `false`                | Disable the caching mechanism (e.g. during development) |

On first request the loader scans all classes in `$path` for `#[Route]` attributes. Subsequent requests load directly from that cache file, skipping reflection entirely.

## Defining routes

Add a `#[Route]` attribute to any public method in a class inside your controller directory.

```php
<?php

namespace App\Controller;

use Sylviavdv\Router\Attributes\Route;

class UserController
{
    #[Route('/users')]
    public function index(): void
    {
        // GET /users
    }

    #[Route('/users/{id}', method: 'GET')]
    public function show(int $id): void
    {
        // GET /users/42
    }

    #[Route('/users', method: 'POST')]
    public function create(): void
    {
        // POST /users
    }
}
```

### Route attribute parameters

| Parameter          | Type     | Default | Description                                                                                             |
|--------------------|----------|---|---------------------------------------------------------------------------------------------------------|
| `path`             | `string` | — | URI pattern, e.g. `/users/{id}`.                                                                        |
| `method`           | `string` | `''` | HTTP method (`GET`, `POST`, `PUT`, `DELETE`, …). Empty string matches any method.                       |
| `params`           | `array`  | `[]` | Custom regex per URL placeholder: `['id' => '\d+']`                                                     |
| `postRequirements` | `array`  | `[]` | Required `$_POST` keys and their value regex: `['email' => '.+@.+']`                                    |
| `getRequirements`  | `array`  | `[]` | Required `$_GET` keys and their value regex: `['q' => '.+']`                                            |
| `priority`         | `int`    | `1` | Higher priority routes are matched first. Routes with equal priority are sorted alphabetically by path. |
| `name`             | `string` | `''` | Optional name for the route.                                                                            |
| `meta`             | `array`  | `''` | Any optional metadata you might want to pass.                                                           |

### URL parameters and path parsing

Placeholders in curly braces are captured and passed as arguments to the controller method by matching the placeholder name to the parameter name.
If a pattern is defined for a placeholder, the captured value must match that pattern or the route will not match.

After expanding the placeholders, the path is treated as a regex pattern, so you can use regex features like optional segments, character classes, and quantifiers.

```php
#[Route('/articles/{slug}')]
public function show(string $slug): void
{
    // $slug contains the captured value
}

// matches /blog/tag/php, /en/blog/tag/php, /fr/blog/tag/php, etc. without capturing the locale
#[Route('([a-z]{2}/)?blog/tag/{tag}', method: 'get', params: ['tag' => '[a-zA-Z0-9_-]+'])]
public function tag(string $tag): void { }
```

By default a placeholder matches `[a-z0-9_-]+`. Override this per-placeholder with the `params` option.


```php
#[Route('/users/{id}', params: ['id' => '\d+'])]
public function show(string $id): void { }
```

### POST and GET requirements

A route only matches when all listed keys are present in `$_POST` / `$_GET` and their values satisfy the given regex.

```php
#[Route('/search', getRequirements: ['q' => '.+'])]
public function search(string $q): void { }

// if the password is not at least 8 characters long, the route will not match and a 404 response will be sent. Note that this is not a replacement for proper validation and error handling in your controller method.
#[Route('/login', method: 'POST', postRequirements: ['password' => '.{8,}'])]
public function login(): void { }
```

### Multiple routes on one method and accessing the matched route

The `Route` attribute is repeatable, so a single method can handle multiple routes.  
If your controller method declares a parameter named `$route`, it receives the matched `Route` object.

```php
#[Route('/', name: 'default')]
#[Route('/home', name: 'home')]
public function home(Route $route): void {
    echo "Matched route: " . $route->name;
}
```

### Priority

Routes with a higher `priority` value are evaluated before routes with a lower value. This is useful when a specific route and a catch-all pattern overlap.  
When two routes have the same priority, they are sorted alphabetically by path, not by the order in which they are defined.

```php
#[Route('/files/{name}', priority: 10)]
public function file(string $name): void { }

#[Route('/{slug}', priority: 1)]
public function page(string $slug): void { }
```

## Advanced: manual dispatching

`Router::handleRequest()` finds and dispatches the route in one call, sending a 404 response code when nothing matches.  
You can also find the route manually. This is useful if you want to inspect the route or controller and take additional actions before dispatching.

```php
$route = $router->findRoute();

if ($route === null) {
    http_response_code(404);
    exit;
}

$controller = $route->getControllerObject();
if ($controller instanceof SomeController) {
    // do something with the controller
}

// dispatch the route
$route->process();
```

`findRoute()` accepts an optional filter callback. Only routes for which the callback returns `true` are considered.
Use this with the optional metadata if you want more control over which routes are matched. 


```php
#[Route('view', method: 'GET', meta: ['for_admins' => true])]
public function adminView(int $id): void { }

#[Route('view', method: 'GET', meta: ['for_admins' => false])]
public function userView(int $id): void { }
```


```php
$user = getCurrentUser(); // returns an object with a `isAdmin()` method (example only, not part of the router)

$route = $router->findRoute(function ($route) use ($user) {
    return isset($route->meta['for_admins']) && $route->meta['for_admins'] === false;
});
$route->process();
```
