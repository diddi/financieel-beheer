<?php
namespace App\Core;

/**
 * Basis Controller class waar andere controllers van kunnen erven
 */
class Controller {
    /**
     * Controleer of gebruiker is ingelogd, zo niet, redirect naar login pagina
     */
    protected function requireLogin() {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        return Auth::id();
    }
    
    /**
     * Start output buffering en geeft een callback functie die de layout rendert
     * 
     * @param string $pageTitle De titel van de pagina
     * @param bool $isAuth Of dit een auth pagina is (true) of een normale pagina (false)
     * @return callable Een functie die aangeroepen kan worden met de gebufferde content
     */
    protected function startBuffering($pageTitle, $isAuth = false) {
        ob_start();
        
        return function($content = null) use ($pageTitle, $isAuth) {
            // Als content niet is gegeven, neem de inhoud van de buffer
            if ($content === null) {
                $content = ob_get_clean();
            }
            
            // Render met de juiste layout
            if ($isAuth) {
                renderWithAuthLayout($content, $pageTitle);
            } else {
                renderWithAppLayout($content, $pageTitle);
            }
        };
    }
} 