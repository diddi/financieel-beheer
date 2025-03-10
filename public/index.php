<?php
// Alleen fouten loggen, niet tonen
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Pad naar bestanden
define('ROOT_PATH', dirname(__DIR__));

// Gebruik de centrale autoloader
require_once ROOT_PATH . '/autoload.php';

use App\Core\Router;
use App\Core\Session;

// Start sessie
Session::start();

// Initialize the router
$router = new Router();

// Define routes
// Authentication routes
$router->register('/', ['controller' => 'DashboardController', 'action' => 'index']);
$router->register('/login', ['controller' => 'AuthController', 'action' => 'login']);
$router->register('/register', ['controller' => 'AuthController', 'action' => 'register']);
$router->register('/logout', ['controller' => 'AuthController', 'action' => 'logout']);
$router->register('/profile', ['controller' => 'AuthController', 'action' => 'profile']);
$router->register('/profile/update', ['controller' => 'AuthController', 'action' => 'updateProfile'], 'POST');
$router->register('/profile/change-password', ['controller' => 'AuthController', 'action' => 'changePassword'], 'POST');
$router->register('/forgot-password', ['controller' => 'AuthController', 'action' => 'forgotPassword']);
$router->register('/reset-password', ['controller' => 'AuthController', 'action' => 'resetPassword'], 'POST');

// Transaction routes
$router->register('/transactions', ['controller' => 'TransactionController', 'action' => 'index']);
$router->register('/transactions/create', ['controller' => 'TransactionController', 'action' => 'create']);
$router->register('/transactions/store', ['controller' => 'TransactionController', 'action' => 'store'], 'POST');
$router->register('/transactions/edit', ['controller' => 'TransactionController', 'action' => 'edit']);
$router->register('/transactions/update', ['controller' => 'TransactionController', 'action' => 'update'], 'POST');
$router->register('/transactions/delete', ['controller' => 'TransactionController', 'action' => 'delete']);

// Account routes
$router->register('/accounts', ['controller' => 'AccountController', 'action' => 'index']);
$router->register('/accounts/create', ['controller' => 'AccountController', 'action' => 'create']);
$router->register('/accounts/store', ['controller' => 'AccountController', 'action' => 'store'], 'POST');
$router->register('/accounts/edit', ['controller' => 'AccountController', 'action' => 'edit']);
$router->register('/accounts/update', ['controller' => 'AccountController', 'action' => 'update'], 'POST');
$router->register('/accounts/delete', ['controller' => 'AccountController', 'action' => 'delete']);

// Category routes
$router->register('/categories', ['controller' => 'CategoryController', 'action' => 'index']);
$router->register('/categories/create', ['controller' => 'CategoryController', 'action' => 'create']);
$router->register('/categories/store', ['controller' => 'CategoryController', 'action' => 'store'], 'POST');
$router->register('/categories/edit', ['controller' => 'CategoryController', 'action' => 'edit']);
$router->register('/categories/update', ['controller' => 'CategoryController', 'action' => 'update'], 'POST');
$router->register('/categories/delete', ['controller' => 'CategoryController', 'action' => 'delete']);

// Budget routes
$router->register('/budgets', ['controller' => 'BudgetController', 'action' => 'index']);
$router->register('/budgets/create', ['controller' => 'BudgetController', 'action' => 'create']);
$router->register('/budgets/store', ['controller' => 'BudgetController', 'action' => 'store'], 'POST');
$router->register('/budgets/edit', ['controller' => 'BudgetController', 'action' => 'edit']);
$router->register('/budgets/update', ['controller' => 'BudgetController', 'action' => 'update'], 'POST');
$router->register('/budgets/delete', ['controller' => 'BudgetController', 'action' => 'delete']);

// Report routes
$router->register('/reports', ['controller' => 'ReportController', 'action' => 'index']);
$router->register('/reports/category', ['controller' => 'ReportController', 'action' => 'categoryDetail']);

// Savings routes
$router->register('/savings', ['controller' => 'SavingsController', 'action' => 'index']);
$router->register('/savings/create', ['controller' => 'SavingsController', 'action' => 'create']);
$router->register('/savings/store', ['controller' => 'SavingsController', 'action' => 'store'], 'POST');
$router->register('/savings/show', ['controller' => 'SavingsController', 'action' => 'show']);
$router->register('/savings/edit', ['controller' => 'SavingsController', 'action' => 'edit']);
$router->register('/savings/update', ['controller' => 'SavingsController', 'action' => 'update'], 'POST');
$router->register('/savings/delete', ['controller' => 'SavingsController', 'action' => 'delete']);
$router->register('/savings/add-contribution', ['controller' => 'SavingsController', 'action' => 'addContribution'], 'POST');
$router->register('/savings/remove-contribution', ['controller' => 'SavingsController', 'action' => 'removeContribution']);

// Export routes
$router->register('/export', ['controller' => 'ExportController', 'action' => 'index']);
$router->register('/export/transactions', ['controller' => 'ExportController', 'action' => 'exportTransactions'], 'POST');
$router->register('/export/budgets', ['controller' => 'ExportController', 'action' => 'exportBudgets'], 'POST');
$router->register('/export/accounts', ['controller' => 'ExportController', 'action' => 'exportAccounts'], 'POST');
$router->register('/export/download', ['controller' => 'ExportController', 'action' => 'download']);

// Notification routes
$router->register('/notifications', ['controller' => 'NotificationController', 'action' => 'index']);
$router->register('/notifications/mark-read', ['controller' => 'NotificationController', 'action' => 'markAsRead'], 'POST');
$router->register('/notifications/mark-all-read', ['controller' => 'NotificationController', 'action' => 'markAllAsRead'], 'POST');
$router->register('/notifications/count', ['controller' => 'NotificationController', 'action' => 'getUnreadCount']);

// Recurring transaction routes
$router->register('/recurring', ['controller' => 'RecurringTransactionController', 'action' => 'index']);
$router->register('/recurring/create', ['controller' => 'RecurringTransactionController', 'action' => 'create']);
$router->register('/recurring/store', ['controller' => 'RecurringTransactionController', 'action' => 'store'], 'POST');
$router->register('/recurring/edit', ['controller' => 'RecurringTransactionController', 'action' => 'edit']);
$router->register('/recurring/update', ['controller' => 'RecurringTransactionController', 'action' => 'update'], 'POST');
$router->register('/recurring/delete', ['controller' => 'RecurringTransactionController', 'action' => 'delete']);

// Dispatch the request
$router->dispatch();