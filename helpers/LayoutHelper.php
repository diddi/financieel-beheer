<?php
/**
 * Layout Helper Functions
 * 
 * Functies voor het consistent laden van layouts in verschillende controllers
 */

/**
 * Render een view met de app layout
 * 
 * @param string $content De content om te tonen
 * @param string $pageTitle De titel van de pagina
 * @return void
 */
function renderWithAppLayout($content, $pageTitle = 'Dashboard') {
    // Bepaal het root path voor het laden van views
    $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(__FILE__));
    
    // Controleer of er auth foutmeldingen zijn
    $authError = '';
    if (class_exists('\\App\\Core\\Session') && method_exists('\\App\\Core\\Session', 'has') && method_exists('\\App\\Core\\Session', 'get')) {
        if (\App\Core\Session::has('auth_error')) {
            $authError = \App\Core\Session::get('auth_error');
            \App\Core\Session::forget('auth_error');
        }
    }
    
    // Zet de variabelen die de layout nodig heeft
    $_pageTitle = $pageTitle;
    $_content = $content;
    $_authError = $authError;
    
    // Include de app layout
    include_once($rootPath . '/views/layouts/app_wrapper.php');
}

/**
 * Render een view met de auth layout
 * 
 * @param string $content De content om te tonen
 * @param string $pageTitle De titel van de pagina
 * @return void
 */
function renderWithAuthLayout($content, $pageTitle = 'Login') {
    // Bepaal het root path voor het laden van views
    $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(__FILE__));
    
    // Zet de variabelen die de layout nodig heeft
    $_pageTitle = $pageTitle;
    $_content = $content;
    
    // Include de auth layout
    include_once($rootPath . '/views/layouts/auth_wrapper.php');
}

/**
 * Render de 404 pagina niet gevonden
 * 
 * @return void
 */
function render404Page() {
    // HTTP status code instellen
    http_response_code(404);
    
    // Bepaal het root path voor het laden van views
    $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(__FILE__));
    
    // Laad de 404 view en gebruik de app layout
    ob_start();
    include_once($rootPath . '/views/errors/404.php');
    $content = ob_get_clean();
    
    // Render met de app layout
    renderWithAppLayout($content, 'Pagina niet gevonden');
    
    // Stop de verdere uitvoering
    exit;
} 