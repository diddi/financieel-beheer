<?php
// controllers/RecurringTransactionController.php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\RecurringTransaction;
use App\Models\Account;
use App\Models\Category;

class RecurringTransactionController {
    
    /**
     * Toon het overzicht van terugkerende transacties
     */
    public function index() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal terugkerende transacties op
        $recurringTransactions = RecurringTransaction::getAllByUser($userId);
        
        // Toon view
        include __DIR__ . '/../views/recurring/index.php';
    }
    
    /**
     * Toon formulier voor nieuwe terugkerende transactie
     */
    public function create() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal rekeningen en categorieën op
        $accounts = Account::getAllByUser($userId);
        $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
        $incomeCategories = Category::getAllByUserAndType($userId, 'income');
        
        // Toon view
        include __DIR__ . '/../views/recurring/create.php';
    }
    
    /**
     * Sla een nieuwe terugkerende transactie op
     */
    public function store() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Valideer input
        $type = $_POST['type'] ?? '';
        $accountId = $_POST['account_id'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $frequency = $_POST['frequency'] ?? '';
        $categoryId = $_POST['category_id'] ?? null;
        $description = $_POST['description'] ?? '';
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        
        $errors = [];
        
        if (empty($type) || !in_array($type, ['expense', 'income'])) {
            $errors['type'] = 'Selecteer een geldig type transactie';
        }
        
        if (empty($accountId)) {
            $errors['account_id'] = 'Selecteer een rekening';
        }
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in';
        }
        
        if (empty($startDate) || !strtotime($startDate)) {
            $errors['start_date'] = 'Voer een geldige startdatum in';
        }
        
        if (empty($frequency) || !in_array($frequency, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])) {
            $errors['frequency'] = 'Selecteer een geldige frequentie';
        }
        
        if ($endDate && (!strtotime($endDate) || strtotime($endDate) < strtotime($startDate))) {
            $errors['end_date'] = 'Einddatum moet na startdatum liggen';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $accounts = Account::getAllByUser($userId);
            $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
            $incomeCategories = Category::getAllByUserAndType($userId, 'income');
            
            // Toon view met fouten
            include __DIR__ . '/../views/recurring/create.php';
            return;
        }
        
        // Bereken volgende datum
        $nextDueDate = RecurringTransaction::calculateNextDueDate($startDate, $frequency);
        
        // Sla terugkerende transactie op
        $data = [
            'user_id' => $userId,
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_due_date' => $nextDueDate,
            'is_active' => 1
        ];
        
        $id = RecurringTransaction::create($data);
        
        // Redirect naar overzicht
        header('Location: /recurring');
        exit;
    }
    
    /**
     * Toon bewerkformulier voor terugkerende transactie
     */
    public function edit($id = null) {
        // ID uit URL halen als het niet als parameter is doorgegeven
        if ($id === null) {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
        }
        
        if (!$id) {
            header('Location: /recurring');
            exit;
        }
        
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal terugkerende transactie op
        $recurringTransaction = RecurringTransaction::getById($id, $userId);
        
        if (!$recurringTransaction) {
            header('Location: /recurring');
            exit;
        }
        
        // Haal rekeningen en categorieën op
        $accounts = Account::getAllByUser($userId);
        $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
        $incomeCategories = Category::getAllByUserAndType($userId, 'income');
        
        // Toon view
        include __DIR__ . '/../views/recurring/edit.php';
    }
    
    /**
     * Update een terugkerende transactie
     */
    public function update() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            header('Location: /recurring');
            exit;
        }
        
        // Haal bestaande transactie op
        $recurringTransaction = RecurringTransaction::getById($id, $userId);
        
        if (!$recurringTransaction) {
            header('Location: /recurring');
            exit;
        }
        
        // Valideer input (zelfde als bij store)
        $type = $_POST['type'] ?? '';
        $accountId = $_POST['account_id'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $frequency = $_POST['frequency'] ?? '';
        $categoryId = $_POST['category_id'] ?? null;
        $description = $_POST['description'] ?? '';
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $errors = [];
        
        if (empty($type) || !in_array($type, ['expense', 'income'])) {
            $errors['type'] = 'Selecteer een geldig type transactie';
        }
        
        if (empty($accountId)) {
            $errors['account_id'] = 'Selecteer een rekening';
        }
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in';
        }
        
        if (empty($frequency) || !in_array($frequency, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])) {
            $errors['frequency'] = 'Selecteer een geldige frequentie';
        }
        
        if ($endDate && (!strtotime($endDate) || strtotime($endDate) < strtotime($recurringTransaction['start_date']))) {
            $errors['end_date'] = 'Einddatum moet na startdatum liggen';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $accounts = Account::getAllByUser($userId);
            $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
            $incomeCategories = Category::getAllByUserAndType($userId, 'income');
            
            // Toon view met fouten
            include __DIR__ . '/../views/recurring/edit.php';
            return;
        }
        
        // Bereken volgende datum opnieuw als de frequentie is gewijzigd
        $nextDueDate = $recurringTransaction['next_due_date'];
        if ($frequency !== $recurringTransaction['frequency']) {
            $nextDueDate = RecurringTransaction::calculateNextDueDate($recurringTransaction['start_date'], $frequency);
        }
        
        // Update terugkerende transactie
        $data = [
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'frequency' => $frequency,
            'end_date' => $endDate,
            'next_due_date' => $nextDueDate,
            'is_active' => $isActive
        ];
        
        RecurringTransaction::update($id, $data, $userId);
        
        // Redirect naar overzicht
        header('Location: /recurring');
        exit;
    }
    
    /**
     * Verwijder een terugkerende transactie
     */
    public function delete() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            header('Location: /recurring');
            exit;
        }
        
        // Verwijder transactie
        RecurringTransaction::delete($id, $userId);
        
        // Redirect naar overzicht
        header('Location: /recurring');
        exit;
    }
}