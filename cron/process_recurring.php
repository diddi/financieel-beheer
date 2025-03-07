<?php
// cron/process_recurring.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\RecurringTransaction;
use App\Services\NotificationService;

// Verwerk terugkerende transacties
RecurringTransaction::processRecurringTransactions();

// Controleer aankomende terugkerende transacties voor herinneringen
$notificationService = new NotificationService();

// Haal alle gebruikers op
$db = \App\Core\Database::getInstance();
$users = $db->fetchAll("SELECT id FROM users");

foreach ($users as $user) {
    // Controleer terugkerende transacties voor herinneringen
    $notificationService->checkRecurringTransactions($user['id']);
}

echo "Verwerking terugkerende transacties voltooid.\n";