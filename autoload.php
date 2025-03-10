<?php
/**
 * Financieel Beheer Autoloader
 * 
 * Dit bestand wordt gebruikt om automatisch alle benodigde klassen te laden
 * zonder handmatig require statements te gebruiken.
 */

// Composer autoloader laden indien beschikbaar
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Core klassen
$coreClasses = [
    'Session', 'Router', 'Database', 'Auth'
];

foreach ($coreClasses as $class) {
    $file = __DIR__ . "/core/{$class}.php";
    if (file_exists($file)) {
        require_once $file;
    }
}

// Model klassen
$modelClasses = [
    'User', 'Account', 'AccountType', 'Category', 'Transaction', 
    'Budget', 'SavingsGoal', 'RecurringTransaction', 'Notification'
];

foreach ($modelClasses as $class) {
    $file = __DIR__ . "/models/{$class}.php";
    if (file_exists($file)) {
        require_once $file;
    }
}

// Controller klassen
$controllerClasses = [
    'AuthController', 'DashboardController', 'TransactionController',
    'AccountController', 'CategoryController', 'BudgetController', 
    'ReportController', 'SavingsController', 'ExportController',
    'NotificationController', 'RecurringTransactionController'
];

foreach ($controllerClasses as $class) {
    $file = __DIR__ . "/controllers/{$class}.php";
    if (file_exists($file)) {
        require_once $file;
    }
}

// Service klassen
$serviceClasses = ['ExportService', 'NotificationService'];

foreach ($serviceClasses as $class) {
    $file = __DIR__ . "/services/{$class}.php";
    if (file_exists($file)) {
        require_once $file;
    }
}

// Helper klassen
$helperFiles = glob(__DIR__ . '/helpers/*.php');
foreach ($helperFiles as $file) {
    require_once $file;
}

/**
 * Automatische class loader functie
 * 
 * Probeert een klasse te laden op basis van de namespace
 * 
 * @param string $className De naam van de klasse
 * @return void
 */
spl_autoload_register(function ($className) {
    // Converteer namespace naar bestandspad
    $path = str_replace(['App\\', '\\'], ['', '/'], $className);
    $file = __DIR__ . '/' . $path . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
}); 