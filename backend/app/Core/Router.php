<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private \Closure|array|string|null $notFoundCallback = null;
    private array $middlewares = [];

    public function get(string $path, callable|string|array $handler): self
    {
        return $this->register('GET', $path, $handler);
    }

    public function post(string $path, callable|string|array $handler): self
    {
        return $this->register('POST', $path, $handler);
    }

    public function put(string $path, callable|string|array $handler): self
    {
        return $this->register('PUT', $path, $handler);
    }

    public function delete(string $path, callable|string|array $handler): self
    {
        return $this->register('DELETE', $path, $handler);
    }

    public function patch(string $path, callable|string|array $handler): self
    {
        return $this->register('PATCH', $path, $handler);
    }

    private function register(string $method, string $path, callable|string|array $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'regex' => $this->pathToRegex($path),
            'middlewares' => []
        ];
        return $this;
    }

    public function groupWithMiddleware(string $prefix, callable $callback, callable|array $middlewares): self
    {
        $previousRoutes = $this->routes;
        $this->routes = [];

        // let callback register routes on $this
        call_user_func($callback, $this);

        $newRoutes = $this->routes;
        $this->routes = $previousRoutes;

        // normalize middlewares to array of callables
        $mwArray = is_array($middlewares) ? $middlewares : [$middlewares];

        foreach ($newRoutes as $route) {
            $route['path'] = $prefix . $route['path'];
            $route['regex'] = $this->pathToRegex($route['path']);
            $route['middlewares'] = $mwArray;
            $this->routes[] = $route;
        }

        return $this;
    }

    public function group(string $prefix, callable $callback): self
    {
        $previousRoutes = $this->routes;
        $this->routes = [];

        call_user_func($callback, $this);

        $newRoutes = $this->routes;
        $this->routes = $previousRoutes;

        foreach ($newRoutes as $route) {
            $route['path'] = $prefix . $route['path'];
            $route['regex'] = $this->pathToRegex($route['path']);
            $this->routes[] = $route;
        }

        return $this;
    }

    public function notFound(callable|string|array $handler): self
    {
        $this->notFoundCallback = $handler;
        return $this;
    }

    public function middleware(callable $handler): self
    {
        $this->middlewares[] = $handler;
        return $this;
    }

    public function dispatch(string $uri = '', string $method = ''): void
    {
        $uri = $uri ?: $this->getUri();
        $method = $method ?: $_SERVER['REQUEST_METHOD'];

        // Run middlewares
        foreach ($this->middlewares as $middleware) {
            call_user_func($middleware);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $uri, $matches)) {
                $handler = $route['handler'];
                $params = array_slice($matches, 1);
                // Filter to only numeric keys (positional arguments)
                $params = array_filter($params, 'is_numeric', ARRAY_FILTER_USE_KEY);
                $params = array_values($params);

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;

                    if (is_string($class) && class_exists($class)) {
                        $instance = new $class();
                        // run route-specific middlewares if any
                        if (!empty($route['middlewares'])) {
                            foreach ($route['middlewares'] as $m) {
                                call_user_func($m);
                            }
                        }

                        call_user_func_array([$instance, $action], $params);
                    } else {
                        http_response_code(500);
                        echo "Controller not found";
                    }
                } elseif (is_callable($handler)) {
                    // run route-specific middlewares if any
                    if (!empty($route['middlewares'])) {
                        foreach ($route['middlewares'] as $m) {
                            call_user_func($m);
                        }
                    }

                    call_user_func_array($handler, $params);
                } elseif (is_string($handler)) {
                    // run route-specific middlewares if any
                    if (!empty($route['middlewares'])) {
                        foreach ($route['middlewares'] as $m) {
                            call_user_func($m);
                        }
                    }

                    $this->callControllerAction($handler, $params);
                }
                return;
            }
        }

        // Handle 404
        if ($this->notFoundCallback) {
            $handler = $this->notFoundCallback;
            if (is_callable($handler)) {
                call_user_func($handler);
            } elseif (is_string($handler)) {
                $this->callControllerAction($handler);
            }
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }

    private function callControllerAction(string $handler, array $params = []): void
    {
        [$controller, $action] = explode('@', $handler);
        $controllerClass = "App\\Controllers\\{$controller}";

        if (!class_exists($controllerClass)) {
            http_response_code(500);
            echo "Controller not found: {$controllerClass}";
            return;
        }

        $instance = new $controllerClass();
        if (!method_exists($instance, $action)) {
            http_response_code(500);
            echo "Action not found: {$action}";
            return;
        }

        call_user_func_array([$instance, $action], $params);
    }

    private function pathToRegex(string $path): string
    {
        $path = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $path . '$#';
    }

    private function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }

    private function getUri(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $path = substr($path, strlen($basePath));
        }
        return '/' . trim($path, '/');
    }
}
