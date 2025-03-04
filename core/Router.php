<?php
namespace App\Core;

class Router {
    private $routes = [];
    
    public function __construct() {
        // Definieer routes
        $this->routes = [
            '/' => ['controller' => 'DashboardController', 'action' => 'index'],
            '/login' => ['controller' => 'AuthController', 'action' => 'login'],
            '/register' => ['controller' => 'AuthController', 'action' => 'register'],
            '/logout' => ['controller' => 'AuthController', 'action' => 'logout'],
            '/transactions' => ['controller' => 'TransactionController', 'action' => 'index'],
            '/transactions/create' => ['controller' => 'TransactionController', 'action' => 'create'],
            '/transactions/store' => ['controller' => 'TransactionController', 'action' => 'store', 'method' => 'POST'],
            '/transactions/edit' => ['controller' => 'TransactionController', 'action' => 'edit'],
            '/transactions/update' => ['controller' => 'TransactionController', 'action' => 'update', 'method' => 'POST'],
            '/transactions/delete' => ['controller' => 'TransactionController', 'action' => 'delete'],
        ];
    }
    
    public function dispatch() {
        // Bepaal de huidige URI
        $uri = $_SERVER['REQUEST_URI'];
        $uri = strtok($uri, '?'); // Verwijder query parameters
        
        // Check of route bestaat
        if (isset($this->routes[$uri])) {
            $route = $this->routes[$uri];
            
            // Check request methode indien gespecificeerd
            if (isset($route['method']) && $_SERVER['REQUEST_METHOD'] !== $route['method']) {
                $this->error(405, 'Method Not Allowed');
                return;
            }
            
            // Laad de juiste controller
            $controllerName = 'App\\Controllers\\' . $route['controller'];
            
            if (class_exists($controllerName)) {
                $controller = new $controllerName();
                $action = $route['action'];
                
                if (method_exists($controller, $action)) {
                    $controller->$action();
                    return;
                }
            }
        }
        
        // Geen route gevonden
        $this->error(404, 'Not Found');
    }
    
    private function error($code, $message) {
        http_response_code($code);
        echo "<h1>Error $code: $message</h1>";
    }
}
