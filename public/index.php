<?php
// Alleen fouten loggen, niet tonen
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Pad naar bestanden
define('ROOT_PATH', dirname(__DIR__));

// Laad autoloader
require_once ROOT_PATH . '/vendor/autoload.php';

// Handmatig klassen laden (als fallback)
require_once ROOT_PATH . '/core/Session.php';
require_once ROOT_PATH . '/core/Router.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/models/User.php';
require_once ROOT_PATH . '/models/Account.php';
require_once ROOT_PATH . '/models/AccountType.php';
require_once ROOT_PATH . '/models/Category.php';
require_once ROOT_PATH . '/models/Transaction.php';
require_once ROOT_PATH . '/models/Budget.php';
require_once ROOT_PATH . '/models/SavingsGoal.php';
require_once ROOT_PATH . '/controllers/AuthController.php';
require_once ROOT_PATH . '/controllers/DashboardController.php';
require_once ROOT_PATH . '/controllers/TransactionController.php';
require_once ROOT_PATH . '/controllers/AccountController.php';
require_once ROOT_PATH . '/controllers/CategoryController.php';
require_once ROOT_PATH . '/controllers/BudgetController.php';
require_once ROOT_PATH . '/controllers/ReportController.php';
require_once ROOT_PATH . '/controllers/SavingsController.php';
require_once ROOT_PATH . '/controllers/ExportController.php';
require_once ROOT_PATH . '/services/ExportService.php';

use App\Core\Router;
use App\Core\Session;

// Start sessie
Session::start();

// Bepaal de huidige URI
$uri = $_SERVER['REQUEST_URI'];
$uri = strtok($uri, '?'); // Verwijder query parameters

// Eenvoudige routing
if ($uri == '/') {
    $controller = new App\Controllers\DashboardController();
    $controller->index();
} elseif ($uri == '/login') {
    $controller = new App\Controllers\AuthController();
    $controller->login();
} elseif ($uri == '/register') {
    $controller = new App\Controllers\AuthController();
    $controller->register();
} elseif ($uri == '/logout') {
    $controller = new App\Controllers\AuthController();
    $controller->logout();
} elseif ($uri == '/transactions') {
    $controller = new App\Controllers\TransactionController();
    $controller->index();
} elseif ($uri == '/transactions/create') {
    $controller = new App\Controllers\TransactionController();
    $controller->create();
} elseif ($uri == '/transactions/store') {
    $controller = new App\Controllers\TransactionController();
    $controller->store();
} elseif ($uri == '/transactions/edit') {
    $controller = new App\Controllers\TransactionController();
    $controller->edit();
} elseif ($uri == '/transactions/update') {
    $controller = new App\Controllers\TransactionController();
    $controller->update();
} elseif ($uri == '/transactions/delete') {
    $controller = new App\Controllers\TransactionController();
    $controller->delete();
} elseif ($uri == '/accounts') {
    $controller = new App\Controllers\AccountController();
    $controller->index();
} elseif ($uri == '/accounts/create') {
    $controller = new App\Controllers\AccountController();
    $controller->create();
} elseif ($uri == '/accounts/store') {
    $controller = new App\Controllers\AccountController();
    $controller->store();
} elseif ($uri == '/accounts/edit') {
    $controller = new App\Controllers\AccountController();
    $controller->edit();
} elseif ($uri == '/accounts/update') {
    $controller = new App\Controllers\AccountController();
    $controller->update();
} elseif ($uri == '/accounts/delete') {
    $controller = new App\Controllers\AccountController();
    $controller->delete();
} elseif ($uri == '/categories') {
    $controller = new App\Controllers\CategoryController();
    $controller->index();
} elseif ($uri == '/categories/create') {
    $controller = new App\Controllers\CategoryController();
    $controller->create();
} elseif ($uri == '/categories/store') {
    $controller = new App\Controllers\CategoryController();
    $controller->store();
} elseif ($uri == '/categories/edit') {
    $controller = new App\Controllers\CategoryController();
    $controller->edit();
} elseif ($uri == '/categories/update') {
    $controller = new App\Controllers\CategoryController();
    $controller->update();
} elseif ($uri == '/categories/delete') {
    $controller = new App\Controllers\CategoryController();
    $controller->delete();
} elseif ($uri == '/budgets') {
    $controller = new App\Controllers\BudgetController();
    $controller->index();
} elseif ($uri == '/budgets/create') {
    $controller = new App\Controllers\BudgetController();
    $controller->create();
} elseif ($uri == '/budgets/store') {
    $controller = new App\Controllers\BudgetController();
    $controller->store();
} elseif ($uri == '/budgets/edit') {
    $controller = new App\Controllers\BudgetController();
    $controller->edit();
} elseif ($uri == '/budgets/update') {
    $controller = new App\Controllers\BudgetController();
    $controller->update();
} elseif ($uri == '/budgets/delete') {
    $controller = new App\Controllers\BudgetController();
    $controller->delete();
} elseif ($uri == '/reports') {
    $controller = new App\Controllers\ReportController();
    $controller->index();
} elseif ($uri == '/reports/category') {
    $controller = new App\Controllers\ReportController();
    $controller->categoryDetail();
} 
// Routes voor Spaardoelen
elseif ($uri == '/savings') {
    $controller = new App\Controllers\SavingsController();
    $controller->index();
} elseif ($uri == '/savings/create') {
    $controller = new App\Controllers\SavingsController();
    $controller->create();
} elseif ($uri == '/savings/store') {
    $controller = new App\Controllers\SavingsController();
    $controller->store();
} elseif ($uri == '/savings/show') {
    $controller = new App\Controllers\SavingsController();
    $controller->show();
} elseif ($uri == '/savings/edit') {
    $controller = new App\Controllers\SavingsController();
    $controller->edit();
} elseif ($uri == '/savings/update') {
    $controller = new App\Controllers\SavingsController();
    $controller->update();
} elseif ($uri == '/savings/delete') {
    $controller = new App\Controllers\SavingsController();
    $controller->delete();
} elseif ($uri == '/savings/add-contribution') {
    $controller = new App\Controllers\SavingsController();
    $controller->addContribution();
} elseif ($uri == '/savings/remove-contribution') {
    $controller = new App\Controllers\SavingsController();
    $controller->removeContribution();
}
// Routes voor Export functionaliteit
elseif ($uri == '/export') {
    $controller = new App\Controllers\ExportController();
    $controller->index();
} elseif ($uri == '/export/transactions') {
    $controller = new App\Controllers\ExportController();
    $controller->exportTransactions();
} elseif ($uri == '/export/budgets') {
    $controller = new App\Controllers\ExportController();
    $controller->exportBudgets();
} elseif ($uri == '/export/accounts') {
    $controller = new App\Controllers\ExportController();
    $controller->exportAccounts();
} elseif ($uri == '/export/download') {
    $controller = new App\Controllers\ExportController();
    $controller->download();
} else {
    // 404 pagina
    http_response_code(404);
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
}
