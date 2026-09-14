<?php
// app/Core/Router.php

require_once __DIR__ . '/App.php';

class Router
{
    private $routes = ['GET' => [], 'POST' => []];

    public function get($uri, $action, $roles = [])
    {
        $this->routes['GET'][$uri] = ['action' => $action, 'roles' => $roles];
    }

    public function post($uri, $action, $roles = [])
    {
        $this->routes['POST'][$uri] = ['action' => $action, 'roles' => $roles];
    }

    public function dispatch($requestUri)
    {
        $path     = parse_url($requestUri, PHP_URL_PATH);
        $basePath = App::getBasePath();

        if ($basePath && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        if ($path === '' || $path === '/') {
            $path = '/';
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $route  = $this->routes[$method][$path] ?? null;
        $params = [];

        if (!$route) {
            foreach ($this->routes[$method] as $routePath => $routeData) {
                if (str_contains($routePath, '{')) {
                    $pattern = preg_replace('#\{[a-zA-Z0-9_]+\}#', '([a-zA-Z0-9\-]+)', $routePath);
                    $pattern = "#^" . $pattern . "$#";
                    if (preg_match($pattern, $path, $matches)) {
                        array_shift($matches);
                        $route  = $routeData;
                        $params = $matches;
                        break;
                    }
                }
            }
        }

        if (!$route) {
            http_response_code(404);
            echo "<h1>404 - Rota não encontrada</h1>";
            return;
        }

        $action = $route['action'];
        $roles  = $route['roles'];

        // --- Role check (aceita string) ---
        if (!empty($roles)) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $usuario = $_SESSION['usuario'] ?? null;
            if (!$usuario) {
                header('Location: ' . App::getBasePath() . '/login');
                exit;
            }
            $roleUsuario    = (string) ($usuario['role'] ?? '');
            $rolesPermitidos = array_map('strval', $roles);
            if (!in_array($roleUsuario, $rolesPermitidos, true)) {
                http_response_code(403);
                echo "<h1>403 - Acesso Negado</h1>";
                exit;
            }
        }

                // --- Bloqueios automáticos de sessão ---
        if (isset($_SESSION['usuario'])) {
            $rotasLivres = [
                '/logout',
                '/primeiro-acesso',
                '/auth/confirmar-email',
                '/auth/reenviar-confirmacao',
                '/auth/confirmar-email-pendente',
            ];

            // Email não confirmado → página de confirmação
            if (empty($_SESSION['usuario']['email_confirmado']) && !in_array($path, $rotasLivres, true)) {
                header('Location: ' . App::getBasePath() . '/auth/confirmar-email-pendente');
                exit;
            }

            // Primeiro login → tela bloqueante de primeiro acesso
            if (!empty($_SESSION['usuario']['primeiro_login']) && !in_array($path, $rotasLivres, true)) {
                header('Location: ' . App::getBasePath() . '/primeiro-acesso');
                exit;
            }
        }

        if (!str_contains($action, '@')) {
            http_response_code(500);
            echo "<h1>Rota inválida</h1>";
            return;
        }

        [$controller, $methodName] = explode('@', $action);
        $controllerPath = dirname(__DIR__) . "/Controllers/{$controller}.php";

        if (!file_exists($controllerPath)) {
            http_response_code(500);
            echo "<h1>Controller não encontrado: {$controller}</h1>";
            return;
        }

        require_once $controllerPath;
        $controllerInstance = new $controller();

        if (!method_exists($controllerInstance, $methodName)) {
            http_response_code(500);
            echo "<h1>Método não encontrado: {$methodName}</h1>";
            return;
        }

        $controllerInstance->$methodName(...$params);
    }
}