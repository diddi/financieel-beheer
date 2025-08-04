<?php
/**
 * App Layout Wrapper
 * 
 * Dit bestand wordt gebruikt door de renderWithAppLayout helper
 * Het zorgt ervoor dat de juiste variabelen beschikbaar zijn voor de layout
 */

// Debug informatie toevoegen
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log dat dit bestand wordt aangeroepen
file_put_contents('/Users/dimitry/Projecten/financieel-beheer/public/debug.log', 
                 "app_wrapper.php aangeroepen op " . date('Y-m-d H:i:s') . "\n", 
                 FILE_APPEND);

// De $_pageTitle en $_content variabelen worden gezet door de helper functie

// Gebruik deze variabelen voor de app layout
$pageTitle = $_pageTitle ?? 'Dashboard';
$content = $_content ?? '';
$authError = $_authError ?? '';

// Log de variabelen
file_put_contents('/Users/dimitry/Projecten/financieel-beheer/public/debug.log', 
                 "pageTitle: $pageTitle, authError: $authError\n", 
                 FILE_APPEND);

// Include de app layout
require_once(dirname(__FILE__) . '/app_layout.php'); 