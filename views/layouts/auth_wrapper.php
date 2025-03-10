<?php
/**
 * Auth Layout Wrapper
 * 
 * Dit bestand wordt gebruikt door de renderWithAuthLayout helper
 * Het zorgt ervoor dat de juiste variabelen beschikbaar zijn voor de layout
 */

// De $_pageTitle en $_content variabelen worden gezet door de helper functie

// Gebruik deze variabelen voor de auth layout
$pageTitle = $_pageTitle ?? 'Login';
$content = $_content ?? '';

// Include de auth layout
require_once(dirname(__FILE__) . '/auth.php'); 