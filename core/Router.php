<?php
namespace App\Core;

class Router {
    private $routes = [];
    
    /**
     * Register a route
     *
     * @param string $uri URI path
     * @param array $handler Controller and action
     * @param string $method HTTP method (default: any)
     * @return void
     */
    public function register($uri, $handler, $method = null) {
        $this->routes[$uri] = [
            'controller' => $handler['controller'],
            'action' => $handler['action'],
            'method' => $method
        ];
    }
    
    /**
     * Dispatch the request to the appropriate controller
     *
     * @return void
     */
    public function dispatch() {
        // Determine the current URI
        $uri = $_SERVER['REQUEST_URI'];
        $uri = strtok($uri, '?'); // Remove query parameters
        
        // Check if route exists
        if (isset($this->routes[$uri])) {
            $route = $this->routes[$uri];
            
            // Check request method if specified
            if (isset($route['method']) && $_SERVER['REQUEST_METHOD'] !== $route['method']) {
                $this->error(405, 'Method Not Allowed');
                return;
            }
            
            // Load the appropriate controller
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
        
        // No route found - 404
        $this->error(404, 'Not Found');
    }
    
    /**
     * Display an error page
     *
     * @param int $code HTTP status code
     * @param string $message Error message
     * @return void
     */
    private function error($code, $message) {
        http_response_code($code);
        
        if ($code === 404) {
            // Show a more user-friendly 404 page
            echo "
            <html>
            <head>
                <title>Pagina niet gevonden</title>
                <script src='https://cdn.tailwindcss.com'></script>
            </head>
            <body class='bg-gray-100 min-h-screen flex items-center justify-center'>
                <div class='max-w-md w-full bg-white rounded-lg shadow-md p-8 text-center'>
                    <h1 class='text-3xl font-bold mb-4 text-red-500'>404</h1>
                    <h2 class='text-2xl font-semibold mb-4'>Pagina niet gevonden</h2>
                    <p class='mb-6 text-gray-600'>De opgevraagde pagina kon niet worden gevonden.</p>
                    <div>
                        <a href='/' class='inline-block bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded'>
                            Terug naar home
                        </a>
                    </div>
                </div>
            </body>
            </html>
            ";
        } else {
            echo "<h1>Error $code: $message</h1>";
        }
    }
}