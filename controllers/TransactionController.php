<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;

class TransactionController {
    
    public function index() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Filters uit request
        $filters = [];
        if (isset($_GET['account_id']) && !empty($_GET['account_id'])) {
            $filters['account_id'] = $_GET['account_id'];
        }
        if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
            $filters['category_id'] = $_GET['category_id'];
        }
        if (isset($_GET['type']) && in_array($_GET['type'], ['expense', 'income', 'transfer'])) {
            $filters['type'] = $_GET['type'];
        }
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        // Haal transacties op
        $transactions = Transaction::getAllByUser($userId, $filters);
        
        // Geef de view weer
        $this->renderTransactionsList($transactions);
    }
    
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
        
        // Geef het formulier weer
        $this->renderTransactionForm(null, $accounts, $expenseCategories, $incomeCategories);
    }
    
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
        $date = $_POST['date'] ?? '';
        $categoryId = $_POST['category_id'] ?? null;
        $description = $_POST['description'] ?? '';
        
        $errors = [];
        
        if (empty($type) || !in_array($type, ['expense', 'income', 'transfer'])) {
            $errors['type'] = 'Selecteer een geldig type transactie';
        }
        
        if (empty($accountId)) {
            $errors['account_id'] = 'Selecteer een rekening';
        }
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in';
        }
        
        if (empty($date) || !strtotime($date)) {
            $errors['date'] = 'Voer een geldige datum in';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $accounts = Account::getAllByUser($userId);
            $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
            $incomeCategories = Category::getAllByUserAndType($userId, 'income');
            
            $this->renderTransactionForm(null, $accounts, $expenseCategories, $incomeCategories, $errors, $_POST);
            return;
        }
        
        // Sla de transactie op
        $transactionData = [
            'user_id' => $userId,
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'date' => $date
        ];
        
        $transactionId = Transaction::create($transactionData);
        
        // Redirect naar transactie overzicht
        header('Location: /transactions');
        exit;
    }
    
    public function edit($id = null) {
        // ID uit URL halen als het niet als parameter is doorgegeven
        if ($id === null) {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
        }
        
        if (!$id) {
            header('Location: /transactions');
            exit;
        }
        
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal transactie op
        $transaction = Transaction::getById($id, $userId);
        
        if (!$transaction) {
            header('Location: /transactions');
            exit;
        }
        
        // Haal rekeningen en categorieën op
        $accounts = Account::getAllByUser($userId);
        $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
        $incomeCategories = Category::getAllByUserAndType($userId, 'income');
        
        // Geef het formulier weer
        $this->renderTransactionForm($transaction, $accounts, $expenseCategories, $incomeCategories);
    }
    
    public function update() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            header('Location: /transactions');
            exit;
        }
        
        // Valideer input (zelfde als bij store)
        $type = $_POST['type'] ?? '';
        $accountId = $_POST['account_id'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $date = $_POST['date'] ?? '';
        $categoryId = $_POST['category_id'] ?? null;
        $description = $_POST['description'] ?? '';
        
        $errors = [];
        
        if (empty($type) || !in_array($type, ['expense', 'income', 'transfer'])) {
            $errors['type'] = 'Selecteer een geldig type transactie';
        }
        
        if (empty($accountId)) {
            $errors['account_id'] = 'Selecteer een rekening';
        }
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in';
        }
        
        if (empty($date) || !strtotime($date)) {
            $errors['date'] = 'Voer een geldige datum in';
        }
        
        // Haal de huidige transactie op
        $transaction = Transaction::getById($id, $userId);
        
        if (!$transaction) {
            header('Location: /transactions');
            exit;
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $accounts = Account::getAllByUser($userId);
            $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
            $incomeCategories = Category::getAllByUserAndType($userId, 'income');
            
            $this->renderTransactionForm($transaction, $accounts, $expenseCategories, $incomeCategories, $errors, $_POST);
            return;
        }
        
        // Update de transactie
        $transactionData = [
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'date' => $date
        ];
        
        Transaction::update($id, $transactionData, $userId);
        
        // Redirect naar transactie overzicht
        header('Location: /transactions');
        exit;
    }
    
    public function delete() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            header('Location: /transactions');
            exit;
        }
        
        // Verwijder de transactie
        Transaction::delete($id, $userId);
        
        // Redirect naar transactie overzicht
        header('Location: /transactions');
        exit;
    }
    
    // Hulpmethoden voor het weergeven van views
    
    private function renderTransactionsList($transactions) {
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Transacties</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>";
    
    // Sluit de echo, voeg het navigatiecomponent toe
    include_once __DIR__ . '/../views/components/navigation.php';
    
    // Hervat de echo voor de rest van de HTML
    echo "
            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <h1 class='text-2xl font-bold'>Transacties</h1>
                    <div class='mt-4 md:mt-0'>
                        <a href='/transactions/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Nieuwe transactie
                        </a>
                    </div>
                </div>
                
                <div class='bg-white shadow overflow-hidden sm:rounded-lg'>
                    <table class='min-w-full divide-y divide-gray-200'>
                        <thead class='bg-gray-50'>
                            <tr>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Datum</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Beschrijving</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Categorie</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Rekening</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Bedrag</th>
                                <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='bg-white divide-y divide-gray-200'>";
        
        if (empty($transactions)) {
            echo "<tr><td colspan='6' class='px-6 py-4 text-center text-gray-500'>Geen transacties gevonden</td></tr>";
        } else {
            foreach ($transactions as $transaction) {
                $type = $transaction['type'];
                $amountClass = $type === 'expense' ? 'text-red-600' : 'text-green-600';
                $amountPrefix = $type === 'expense' ? '-' : '+';
                
                echo "<tr>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . date('d-m-Y', strtotime($transaction['date'])) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($transaction['description']) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($transaction['category_name'] ?? '-') . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($transaction['account_name']) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm font-medium {$amountClass}'>{$amountPrefix}€" . number_format($transaction['amount'], 2, ',', '.') . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                            <a href='/transactions/edit?id=" . $transaction['id'] . "' class='text-blue-600 hover:text-blue-900 mr-3'>Bewerken</a>
                            <a href='/transactions/delete?id=" . $transaction['id'] . "' class='text-red-600 hover:text-red-900' onclick='return confirm(\"Weet je zeker dat je deze transactie wilt verwijderen?\")'>Verwijderen</a>
                        </td>
                    </tr>";
            }
        }
        
        echo "      </tbody>
                    </table>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function renderTransactionForm($transaction, $accounts, $expenseCategories, $incomeCategories, $errors = [], $oldInput = []) {
        $isEdit = $transaction !== null;
        $title = $isEdit ? 'Transactie bewerken' : 'Nieuwe transactie';
        $action = $isEdit ? '/transactions/update' : '/transactions/store';
        
        // Bepaal de waarden voor het formulier
        $typeValue = $isEdit ? $transaction['type'] : ($oldInput['type'] ?? 'expense');
        $accountIdValue = $isEdit ? $transaction['account_id'] : ($oldInput['account_id'] ?? '');
        $categoryIdValue = $isEdit ? $transaction['category_id'] : ($oldInput['category_id'] ?? '');
        $amountValue = $isEdit ? $transaction['amount'] : ($oldInput['amount'] ?? '');
        $dateValue = $isEdit ? $transaction['date'] : ($oldInput['date'] ?? date('Y-m-d'));
        $descriptionValue = $isEdit ? $transaction['description'] : ($oldInput['description'] ?? '');
        
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - {$title}</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>
            <nav class='bg-blue-600 text-white shadow-lg'>
                <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
                    <div class='flex justify-between h-16'>
                        <div class='flex'>
                            <div class='flex-shrink-0 flex items-center'>
                                <a href='/' class='text-xl font-bold'>Financieel Beheer</a>
                            </div>
                            <div class='ml-6 flex items-center space-x-4'>
                                <a href='/' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Dashboard</a>
                                <a href='/transactions' class='px-3 py-2 rounded-md text-sm font-medium bg-blue-700'>Transacties</a>
                                <a href='/accounts' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Rekeningen</a>
                                <a href='/categories' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Categorieën</a>
                                <a href='/budgets' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Budgetten</a>
                            </div>
                        </div>
                        <div class='flex items-center'>
                            <a href='/logout' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Uitloggen</a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <h1 class='text-2xl font-bold'>{$title}</h1>
                </div>
                
                <div class='bg-white shadow-md rounded-lg p-6'>
                    <form action='{$action}' method='POST' class='space-y-6'>
                        " . ($isEdit ? "<input type='hidden' name='id' value='{$transaction['id']}'>" : "") . "
                        
                        <div class='flex flex-wrap -mx-3 mb-4'>
                            <div class='w-full px-3'>
                                <div class='flex items-center space-x-6'>
                                    <label class='inline-flex items-center'>
                                        <input type='radio' name='type' value='expense' class='form-radio text-red-600' " . ($typeValue === 'expense' ? 'checked' : '') . ">
                                        <span class='ml-2'>Uitgave</span>
                                    </label>
                                    <label class='inline-flex items-center'>
                                        <input type='radio' name='type' value='income' class='form-radio text-green-600' " . ($typeValue === 'income' ? 'checked' : '') . ">
                                        <span class='ml-2'>Inkomst</span>
                                    </label>
                                    <label class='inline-flex items-center'>
                                        <input type='radio' name='type' value='transfer' class='form-radio text-blue-600' " . ($typeValue === 'transfer' ? 'checked' : '') . ">
                                        <span class='ml-2'>Overschrijving</span>
                                    </label>
                                </div>
                                " . (!empty($errors['type']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['type']}</p>" : "") . "
                            </div>
                        </div>
                        
                        <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                            <div>
                                <label for='account_id' class='block text-sm font-medium text-gray-700'>Rekening</label>
                                <select id='account_id' name='account_id' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                    <option value=''>Selecteer rekening</option>";
        
        foreach ($accounts as $account) {
            $selected = $accountIdValue == $account['id'] ? 'selected' : '';
            echo "<option value='{$account['id']}' {$selected}>" . htmlspecialchars($account['name']) . " (€" . number_format($account['balance'], 2, ',', '.') . ")</option>";
        }
        
        echo "              </select>
                                " . (!empty($errors['account_id']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['account_id']}</p>" : "") . "
                            </div>
                            
                            <div id='category_container'>
                                <label for='category_id' class='block text-sm font-medium text-gray-700'>Categorie</label>
                                <select id='category_id' name='category_id' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                    <option value=''>Selecteer categorie</option>
                                    <optgroup label='Uitgaven' id='expense_categories' class='" . ($typeValue !== 'expense' ? 'hidden' : '') . "'>";
        
        foreach ($expenseCategories as $category) {
            $selected = $categoryIdValue == $category['id'] && $typeValue === 'expense' ? 'selected' : '';
            echo "<option value='{$category['id']}' {$selected}>" . htmlspecialchars($category['name']) . "</option>";
        }
        
        echo "                  </optgroup>
                                    <optgroup label='Inkomsten' id='income_categories' class='" . ($typeValue !== 'income' ? 'hidden' : '') . "'>";
        
        foreach ($incomeCategories as $category) {
            $selected = $categoryIdValue == $category['id'] && $typeValue === 'income' ? 'selected' : '';
            echo "<option value='{$category['id']}' {$selected}>" . htmlspecialchars($category['name']) . "</option>";
        }
        
        echo "                  </optgroup>
                                </select>
                            </div>
                        </div>
                        
                        <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                            <div>
                                <label for='amount' class='block text-sm font-medium text-gray-700'>Bedrag</label>
                                <div class='mt-1 relative rounded-md shadow-sm'>
                                    <div class='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                                        <span class='text-gray-500 sm:text-sm'>€</span>
                                    </div>
                                    <input type='number' name='amount' id='amount' step='0.01' min='0.01' required
                                        class='block w-full pl-7 pr-12 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 p-2 border'
                                        placeholder='0,00'
                                        value='" . htmlspecialchars($amountValue) . "'>
                                </div>
                                " . (!empty($errors['amount']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['amount']}</p>" : "") . "
                            </div>
                            
                            <div>
                                <label for='date' class='block text-sm font-medium text-gray-700'>Datum</label>
                                <input type='date' name='date' id='date' required
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                    value='" . htmlspecialchars($dateValue) . "'>
                                " . (!empty($errors['date']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['date']}</p>" : "") . "
                            </div>
                        </div>
                        
                        <div>
                            <label for='description' class='block text-sm font-medium text-gray-700'>Beschrijving</label>
                            <textarea id='description' name='description' rows='2'
                                class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                placeholder='Voeg een beschrijving toe'>" . htmlspecialchars($descriptionValue) . "</textarea>
                        </div>
                        
                        <div class='flex justify-end space-x-3 mt-6'>
                            <a href='/transactions' class='py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                Annuleren
                            </a>
                            <button type='submit' class='py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                " . ($isEdit ? 'Bijwerken' : 'Opslaan') . "
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const typeRadios = document.querySelectorAll('input[name=\"type\"]');
                const categoryContainer = document.getElementById('category_container');
                const expenseCategories = document.getElementById('expense_categories');
                const incomeCategories = document.getElementById('income_categories');
                
                // Verander categorieën op basis van type
                typeRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.value === 'expense') {
                            categoryContainer.classList.remove('hidden');
                            expenseCategories.classList.remove('hidden');
                            incomeCategories.classList.add('hidden');
                        } else if (this.value === 'income') {
                            categoryContainer.classList.remove('hidden');
                            expenseCategories.classList.add('hidden');
                            incomeCategories.classList.remove('hidden');
                        } else if (this.value === 'transfer') {
                            categoryContainer.classList.add('hidden');
                        }
                    });
                });
            });
            </script>
        </body>
        </html>
        ";
    }
}
