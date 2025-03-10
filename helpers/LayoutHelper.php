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
    
    // Zet de variabelen die de layout nodig heeft
    $_pageTitle = $pageTitle;
    $_content = $content;
    
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