<?php
/**
 * App Layout Wrapper
 * 
 * Dit bestand wordt gebruikt door de renderWithAppLayout helper
 * Het zorgt ervoor dat de juiste variabelen beschikbaar zijn voor de layout
 */

// De $_pageTitle en $_content variabelen worden gezet door de helper functie

// Gebruik deze variabelen voor de app layout
$pageTitle = $_pageTitle ?? 'Dashboard';
$content = $_content ?? '';
$authError = $_authError ?? '';

// Include de app layout
require_once(dirname(__FILE__) . '/app.php'); 