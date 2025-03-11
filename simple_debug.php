<?php
// Foutrapportage inschakelen voor debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', __DIR__);

echo "<h1>Simple Debug Controller Test</h1>";

// Eenvoudige Router class
class SimpleRouter {
    private $routes = [];
    
    public function register($uri, $handler, $method = null) {
        $this->routes[$uri] = [
            'controller' => $handler['controller'],
            'action' => $handler['action'],
            'method' => $method
        ];
        echo "<p>Route geregistreerd: $uri -> {$handler['controller']}::{$handler['action']}</p>";
    }
    
    public function dispatch() {
        $uri = $_SERVER['REQUEST_URI'];
        $uri = strtok($uri, '?'); // Verwijder query parameters
        
        echo "<p>Dispatching URI: $uri</p>";
        
        if (isset($this->routes[$uri])) {
            $route = $this->routes[$uri];
            $controller = $route['controller'];
            $action = $route['action'];
            
            echo "<p>Route gevonden: $controller::$action</p>";
            
            // Controleer of method bestaat
            if ($route['method'] && $_SERVER['REQUEST_METHOD'] != $route['method']) {
                echo "<p>Error: Methode komt niet overeen</p>";
                return;
            }
            
            // Controleer of controller bestaat
            if (!class_exists($controller)) {
                echo "<p>Error: Controller '$controller' niet gevonden</p>";
                return;
            }
            
            // Maak controller instantie
            $controllerInstance = new $controller();
            
            // Controleer of action bestaat
            if (!method_exists($controllerInstance, $action)) {
                echo "<p>Error: Action '$action' niet gevonden in controller '$controller'</p>";
                return;
            }
            
            echo "<p>Success: Controller en action gevonden, wordt uitgevoerd...</p>";
            
            // Voer action uit
            $controllerInstance->$action();
        } else {
            echo "<p>Geen route gevonden voor: $uri</p>";
            echo "<p>Beschikbare routes:</p><ul>";
            foreach ($this->routes as $routeUri => $routeData) {
                echo "<li>$routeUri -> {$routeData['controller']}::{$routeData['action']}</li>";
            }
            echo "</ul>";
        }
    }
}

// Eenvoudige Session class
class SimpleSession {
    public static function start() {
        session_start();
        echo "<p>Sessie gestart</p>";
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
}

// Eenvoudige Controller class
class SimpleController {
    public function render($view, $data = []) {
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0;'>";
        echo "<h3>View: $view</h3>";
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        echo "</div>";
    }
}

// Test controllers
class TestController extends SimpleController {
    public function index() {
        echo "<p>TestController::index aangeroepen</p>";
        $this->render('test/index', ['message' => 'Dit is een test']);
    }
}

class AuthController extends SimpleController {
    public function login() {
        echo "<p>AuthController::login aangeroepen</p>";
        $this->render('auth/login', ['title' => 'Login Pagina']);
    }
    
    public function profile() {
        echo "<p>AuthController::profile aangeroepen</p>";
        $this->render('auth/profile', ['user' => ['name' => 'Test Gebruiker', 'email' => 'test@example.com']]);
    }
}

try {
    // Test SimpleRouter functionaliteit
    SimpleSession::start();
    
    $router = new SimpleRouter();
    
    // Registreer wat routes
    $router->register('/', ['controller' => 'TestController', 'action' => 'index']);
    $router->register('/login', ['controller' => 'AuthController', 'action' => 'login']);
    $router->register('/profile', ['controller' => 'AuthController', 'action' => 'profile']);
    
    // Dispatch de route
    $router->dispatch();
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} 