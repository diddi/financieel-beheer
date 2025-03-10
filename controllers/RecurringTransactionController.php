<?php
// controllers/RecurringTransactionController.php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\RecurringTransaction;
use App\Models\Account;
use App\Models\Category;

class RecurringTransactionController extends Controller {
    
    /**
     * Toon het overzicht van terugkerende transacties
     */
    public function index() {
        $userId = $this->requireLogin();
        
        // Haal terugkerende transacties op
        $transactions = RecurringTransaction::getAllByUser($userId);
        
        // Bereid de render functie voor
        $render = $this->startBuffering('Terugkerende transacties');
        
        // Begin HTML output
        echo "<div class='max-w-6xl mx-auto'>";
        
        // Header sectie
        echo "
            <div class='flex justify-between items-center mb-6'>
                <h1 class='text-2xl font-bold'>Terugkerende transacties</h1>
                <a href='/recurring-transactions/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                    <i class='material-icons mr-1 text-sm'>add</i> Nieuwe terugkerende transactie
                </a>
            </div>";
        
        // Hoofdinhoud
        if (empty($transactions)) {
            echo "
                <div class='bg-white rounded-lg shadow-md p-8 text-center'>
                    <i class='material-icons text-gray-400 text-6xl mb-4'>repeat</i>
                    <h2 class='text-xl font-semibold mb-2'>Geen terugkerende transacties</h2>
                    <p class='text-gray-500 mb-6'>
                        Je hebt nog geen terugkerende transacties ingesteld. Voeg er een toe om automatisch inkomsten of uitgaven bij te houden.
                    </p>
                    <a href='/recurring-transactions/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                        <i class='material-icons mr-1 text-sm'>add</i> Nieuwe terugkerende transactie
                    </a>
                </div>";
        } else {
            echo "
                <div class='bg-white rounded-lg shadow-md overflow-hidden'>
                    <div class='overflow-x-auto'>
                        <table class='min-w-full divide-y divide-gray-200'>
                            <thead class='bg-gray-50'>
                                <tr>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Beschrijving</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Bedrag</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Rekening</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Frequentie</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Volgende datum</th>
                                    <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>Acties</th>
                                </tr>
                            </thead>
                            <tbody class='bg-white divide-y divide-gray-200'>";
            
            foreach ($transactions as $transaction) {
                $typeClass = $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600';
                $amountPrefix = $transaction['type'] === 'income' ? '+' : '-';
                $amount = number_format(abs($transaction['amount']), 2, ',', '.');
                
                // Frequentie vertalen
                $frequencyLabels = [
                    'daily' => 'Dagelijks',
                    'weekly' => 'Wekelijks',
                    'monthly' => 'Maandelijks',
                    'quarterly' => 'Elk kwartaal',
                    'yearly' => 'Jaarlijks'
                ];
                $frequency = $frequencyLabels[$transaction['frequency']] ?? $transaction['frequency'];
                
                echo "
                    <tr>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='flex items-center'>
                                <div class='flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center' style='background-color: " . ($transaction['color'] ?? '#e5e7eb') . "20'>
                                    <i class='material-icons text-sm' style='color: " . ($transaction['color'] ?? '#374151') . "'>" . ($transaction['type'] === 'income' ? 'trending_up' : 'trending_down') . "</i>
                                </div>
                                <div class='ml-4'>
                                    <div class='text-sm font-medium text-gray-900'>" . htmlspecialchars($transaction['description']) . "</div>
                                    <div class='text-xs text-gray-500'>" . ($transaction['category_name'] ?? 'Geen categorie') . "</div>
                                </div>
                            </div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='text-sm font-medium {$typeClass}'>{$amountPrefix}€{$amount}</div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='text-sm text-gray-900'>" . htmlspecialchars($transaction['account_name']) . "</div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='text-sm text-gray-900'>{$frequency}</div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='text-sm text-gray-900'>" . date('d-m-Y', strtotime($transaction['next_due_date'])) . "</div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                            <a href='/recurring-transactions/edit?id=" . $transaction['id'] . "' class='text-blue-600 hover:text-blue-900 mr-3'>
                                <i class='material-icons text-sm align-middle'>edit</i>
                            </a>
                            <a href='/recurring-transactions/delete?id=" . $transaction['id'] . "' onclick='return confirm(\"Weet je zeker dat je deze terugkerende transactie wilt verwijderen?\")' class='text-red-600 hover:text-red-900'>
                                <i class='material-icons text-sm align-middle'>delete</i>
                            </a>
                        </td>
                    </tr>";
            }
            
            echo "
                            </tbody>
                        </table>
                    </div>
                </div>";
        }
        
        echo "</div>";
        
        // Render de pagina
        $render();
    }
    
    /**
     * Toon formulier voor het aanmaken van een terugkerende transactie
     */
    public function create() {
        $userId = $this->requireLogin();
        
        // Haal rekeningen en categorieën op
        $accounts = Account::getAllByUser($userId);
        $categories = Category::getAllByUser($userId);
        
        // Bereid de render functie voor
        $render = $this->startBuffering('Nieuwe terugkerende transactie');
        
        // Render het formulier
        $this->renderTransactionForm(null, [], [], $accounts, $categories);
        
        // Render de pagina
        $render();
    }
    
    /**
     * Verwerk het aanmaken van een terugkerende transactie
     */
    public function store() {
        $userId = $this->requireLogin();
        
        // Valideer de invoer
        $errors = [];
        
        // Beschrijving validatie
        if (empty($_POST['description'])) {
            $errors['description'] = 'Beschrijving is verplicht';
        }
        
        // Bedrag validatie
        if (empty($_POST['amount']) || !is_numeric($_POST['amount']) || floatval($_POST['amount']) <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in (groter dan 0)';
        }
        
        // Rekening validatie
        if (empty($_POST['account_id']) || !is_numeric($_POST['account_id'])) {
            $errors['account_id'] = 'Selecteer een geldige rekening';
        } else {
            // Controleer of de rekening van de ingelogde gebruiker is
            $account = Account::getById($_POST['account_id']);
            if (!$account || $account['user_id'] != $userId) {
                $errors['account_id'] = 'Ongeldige rekening geselecteerd';
            }
        }
        
        // Type validatie
        if (empty($_POST['type']) || !in_array($_POST['type'], ['income', 'expense'])) {
            $errors['type'] = 'Selecteer een geldig type (inkomst of uitgave)';
        }
        
        // Frequentie validatie
        if (empty($_POST['frequency']) || !in_array($_POST['frequency'], ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])) {
            $errors['frequency'] = 'Selecteer een geldige frequentie';
        }
        
        // Startdatum validatie
        if (empty($_POST['start_date']) || !strtotime($_POST['start_date'])) {
            $errors['start_date'] = 'Voer een geldige startdatum in';
        }
        
        // Volgende datum validatie
        if (empty($_POST['next_due_date']) || !strtotime($_POST['next_due_date'])) {
            $errors['next_due_date'] = 'Voer een geldige volgende datum in';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $accounts = Account::getAllByUser($userId);
            $categories = Category::getAllByUser($userId);
            
            $render = $this->startBuffering('Nieuwe terugkerende transactie');
            $this->renderTransactionForm(null, $errors, $_POST, $accounts, $categories);
            $render();
            return;
        }
        
        // Bereid gegevens voor
        $transactionData = [
            'user_id' => $userId,
            'description' => $_POST['description'],
            'amount' => floatval($_POST['amount']),
            'account_id' => $_POST['account_id'],
            'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            'type' => $_POST['type'],
            'frequency' => $_POST['frequency'],
            'start_date' => $_POST['start_date'],
            'next_due_date' => $_POST['next_due_date'],
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Sla terugkerende transactie op
        $transactionId = RecurringTransaction::create($transactionData);
        
        // Redirect naar overzicht
        header('Location: /recurring-transactions?message=Terugkerende transactie succesvol aangemaakt');
        exit;
    }
    
    /**
     * Toon formulier voor het bewerken van een terugkerende transactie
     */
    public function edit($id = null) {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        $transactionId = $id ?? ($_GET['id'] ?? null);
        if (!$transactionId || !is_numeric($transactionId)) {
            header('Location: /recurring-transactions?error=Ongeldig ID');
            exit;
        }
        
        // Haal de transactie op
        $transaction = RecurringTransaction::getById($transactionId, $userId);
        
        // Controleer of de transactie bestaat en van de ingelogde gebruiker is
        if (!$transaction) {
            header('Location: /recurring-transactions?error=Transactie niet gevonden');
            exit;
        }
        
        // Haal rekeningen en categorieën op
        $accounts = Account::getAllByUser($userId);
        $categories = Category::getAllByUser($userId);
        
        // Bereid de render functie voor
        $render = $this->startBuffering('Terugkerende transactie bewerken');
        
        // Render het formulier
        $this->renderTransactionForm($transaction, [], [], $accounts, $categories);
        
        // Render de pagina
        $render();
    }
    
    /**
     * Verwerk het bijwerken van een terugkerende transactie
     */
    public function update() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: /recurring-transactions?error=Ongeldig ID');
            exit;
        }
        
        $transactionId = $_GET['id'];
        
        // Haal de transactie op
        $transaction = RecurringTransaction::getById($transactionId, $userId);
        
        // Controleer of de transactie bestaat en van de ingelogde gebruiker is
        if (!$transaction) {
            header('Location: /recurring-transactions?error=Transactie niet gevonden');
            exit;
        }
        
        // Valideer de invoer (zelfde als store methode)
        $errors = [];
        
        // Beschrijving validatie
        if (empty($_POST['description'])) {
            $errors['description'] = 'Beschrijving is verplicht';
        }
        
        // Bedrag validatie
        if (empty($_POST['amount']) || !is_numeric($_POST['amount']) || floatval($_POST['amount']) <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in (groter dan 0)';
        }
        
        // Rekening validatie
        if (empty($_POST['account_id']) || !is_numeric($_POST['account_id'])) {
            $errors['account_id'] = 'Selecteer een geldige rekening';
        } else {
            // Controleer of de rekening van de ingelogde gebruiker is
            $account = Account::getById($_POST['account_id']);
            if (!$account || $account['user_id'] != $userId) {
                $errors['account_id'] = 'Ongeldige rekening geselecteerd';
            }
        }
        
        // Type validatie
        if (empty($_POST['type']) || !in_array($_POST['type'], ['income', 'expense'])) {
            $errors['type'] = 'Selecteer een geldig type (inkomst of uitgave)';
        }
        
        // Frequentie validatie
        if (empty($_POST['frequency']) || !in_array($_POST['frequency'], ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])) {
            $errors['frequency'] = 'Selecteer een geldige frequentie';
        }
        
        // Startdatum validatie
        if (empty($_POST['start_date']) || !strtotime($_POST['start_date'])) {
            $errors['start_date'] = 'Voer een geldige startdatum in';
        }
        
        // Volgende datum validatie
        if (empty($_POST['next_due_date']) || !strtotime($_POST['next_due_date'])) {
            $errors['next_due_date'] = 'Voer een geldige volgende datum in';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $accounts = Account::getAllByUser($userId);
            $categories = Category::getAllByUser($userId);
            
            $render = $this->startBuffering('Terugkerende transactie bewerken');
            $this->renderTransactionForm($transaction, $errors, $_POST, $accounts, $categories);
            $render();
            return;
        }
        
        // Bereid gegevens voor
        $transactionData = [
            'description' => $_POST['description'],
            'amount' => floatval($_POST['amount']),
            'account_id' => $_POST['account_id'],
            'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            'type' => $_POST['type'],
            'frequency' => $_POST['frequency'],
            'start_date' => $_POST['start_date'],
            'next_due_date' => $_POST['next_due_date'],
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Update terugkerende transactie
        RecurringTransaction::update($transactionId, $transactionData, $userId);
        
        // Redirect naar overzicht
        header('Location: /recurring-transactions?message=Terugkerende transactie succesvol bijgewerkt');
        exit;
    }
    
    /**
     * Verwijder een terugkerende transactie
     */
    public function delete() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: /recurring-transactions?error=Ongeldig ID');
            exit;
        }
        
        $transactionId = $_GET['id'];
        
        // Haal de transactie op
        $transaction = RecurringTransaction::getById($transactionId, $userId);
        
        // Controleer of de transactie bestaat en van de ingelogde gebruiker is
        if (!$transaction) {
            header('Location: /recurring-transactions?error=Transactie niet gevonden');
            exit;
        }
        
        // Verwijder de transactie
        RecurringTransaction::delete($transactionId, $userId);
        
        // Redirect naar overzicht
        header('Location: /recurring-transactions?message=Terugkerende transactie succesvol verwijderd');
        exit;
    }
    
    /**
     * Render het formulier voor een terugkerende transactie
     */
    private function renderTransactionForm($transaction = null, $errors = [], $oldInput = [], $accounts = [], $categories = []) {
        $isEdit = $transaction !== null;
        $title = $isEdit ? 'Terugkerende transactie bewerken' : 'Nieuwe terugkerende transactie';
        
        // Begin de HTML-output
        echo "<div class='max-w-4xl mx-auto'>";
        
        // Header met titel en terug-link
        echo "
            <div class='flex justify-between items-center mb-6'>
                <h1 class='text-2xl font-bold'>{$title}</h1>
                <a href='/recurring-transactions' class='text-blue-600 hover:text-blue-800'>
                    ← Terug naar overzicht
                </a>
            </div>";
        
        // Formulier container
        echo "<div class='bg-white rounded-lg shadow-md p-6 mb-6'>";
        
        // Toon eventuele fouten
        if (!empty($errors)) {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6'>";
            echo "<p class='font-bold'>Let op</p>";
            echo "<ul>";
            
            foreach ($errors as $error) {
                echo "<li>{$error}</li>";
            }
            
            echo "</ul>";
            echo "</div>";
        }
        
        // Formulier begin
        echo "<form method='post' action='" . ($isEdit ? "/recurring-transactions/update?id={$transaction['id']}" : "/recurring-transactions/store") . "' class='space-y-6'>";
        
        // Type (inkomst/uitgave)
        $typeIncome = (isset($oldInput['type']) ? $oldInput['type'] === 'income' : ($isEdit ? $transaction['type'] === 'income' : false)) ? 'checked' : '';
        $typeExpense = (isset($oldInput['type']) ? $oldInput['type'] === 'expense' : ($isEdit ? $transaction['type'] === 'expense' : true)) ? 'checked' : '';
        
        echo "
            <div>
                <label class='block text-sm font-medium text-gray-700 mb-1'>Type transactie</label>
                <div class='flex space-x-4'>
                    <div class='flex items-center'>
                        <input type='radio' id='type_expense' name='type' value='expense' {$typeExpense} class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                        <label for='type_expense' class='ml-2 block text-sm text-gray-700'>Uitgave</label>
                    </div>
                    <div class='flex items-center'>
                        <input type='radio' id='type_income' name='type' value='income' {$typeIncome} class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                        <label for='type_income' class='ml-2 block text-sm text-gray-700'>Inkomst</label>
                    </div>
                </div>
            </div>";
        
        // Beschrijving
        $description = htmlspecialchars($oldInput['description'] ?? ($isEdit ? $transaction['description'] : ''));
        echo "
            <div>
                <label for='description' class='block text-sm font-medium text-gray-700'>Beschrijving</label>
                <input type='text' id='description' name='description' value='{$description}' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
            </div>";
        
        // Bedrag
        $amount = htmlspecialchars($oldInput['amount'] ?? ($isEdit ? $transaction['amount'] : ''));
        echo "
            <div>
                <label for='amount' class='block text-sm font-medium text-gray-700'>Bedrag</label>
                <div class='mt-1 relative rounded-md shadow-sm'>
                    <div class='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                        <span class='text-gray-500 sm:text-sm'>€</span>
                    </div>
                    <input type='number' id='amount' name='amount' min='0.01' step='0.01' value='{$amount}' required class='pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                </div>
            </div>";
        
        // Rekening
        echo "
            <div>
                <label for='account_id' class='block text-sm font-medium text-gray-700'>Rekening</label>
                <select id='account_id' name='account_id' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>";
        
        foreach ($accounts as $account) {
            $selected = ($oldInput['account_id'] ?? ($isEdit ? $transaction['account_id'] : '')) == $account['id'] ? 'selected' : '';
            echo "<option value='{$account['id']}' {$selected}>" . htmlspecialchars($account['name']) . "</option>";
        }
        
        echo "
                </select>
            </div>";
        
        // Categorie
        echo "
            <div>
                <label for='category_id' class='block text-sm font-medium text-gray-700'>Categorie (optioneel)</label>
                <select id='category_id' name='category_id' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                    <option value=''>Selecteer categorie</option>";
        
        foreach ($categories as $category) {
            $selected = ($oldInput['category_id'] ?? ($isEdit ? $transaction['category_id'] : '')) == $category['id'] ? 'selected' : '';
            echo "<option value='{$category['id']}' {$selected}>" . htmlspecialchars($category['name']) . "</option>";
        }
        
        echo "
                </select>
            </div>";
        
        // Frequentie
        $frequencies = [
            'daily' => 'Dagelijks',
            'weekly' => 'Wekelijks',
            'monthly' => 'Maandelijks',
            'quarterly' => 'Elk kwartaal',
            'yearly' => 'Jaarlijks'
        ];
        
        echo "
            <div>
                <label for='frequency' class='block text-sm font-medium text-gray-700'>Frequentie</label>
                <select id='frequency' name='frequency' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>";
        
        foreach ($frequencies as $value => $label) {
            $selected = ($oldInput['frequency'] ?? ($isEdit ? $transaction['frequency'] : '')) === $value ? 'selected' : '';
            echo "<option value='{$value}' {$selected}>{$label}</option>";
        }
        
        echo "
                </select>
            </div>";
        
        // Startdatum
        $startDate = $oldInput['start_date'] ?? ($isEdit ? $transaction['start_date'] : date('Y-m-d'));
        echo "
            <div>
                <label for='start_date' class='block text-sm font-medium text-gray-700'>Startdatum</label>
                <input type='date' id='start_date' name='start_date' value='{$startDate}' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
            </div>";
        
        // Volgende datum
        $nextDueDate = $oldInput['next_due_date'] ?? ($isEdit ? $transaction['next_due_date'] : date('Y-m-d'));
        echo "
            <div>
                <label for='next_due_date' class='block text-sm font-medium text-gray-700'>Volgende datum</label>
                <input type='date' id='next_due_date' name='next_due_date' value='{$nextDueDate}' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
            </div>";
        
        // Einddatum (optioneel)
        $endDate = $oldInput['end_date'] ?? ($isEdit ? $transaction['end_date'] : '');
        echo "
            <div>
                <label for='end_date' class='block text-sm font-medium text-gray-700'>Einddatum (optioneel)</label>
                <input type='date' id='end_date' name='end_date' value='{$endDate}' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                <p class='mt-1 text-sm text-gray-500'>Laat leeg als de transactie geen einddatum heeft</p>
            </div>";
        
        // Actief
        $isActive = isset($oldInput['is_active']) ? !empty($oldInput['is_active']) : ($isEdit ? !empty($transaction['is_active']) : true);
        $isActiveChecked = $isActive ? 'checked' : '';
        
        echo "
            <div class='flex items-center'>
                <input type='checkbox' id='is_active' name='is_active' value='1' {$isActiveChecked} class='h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded'>
                <label for='is_active' class='ml-2 block text-sm text-gray-700'>Actief</label>
            </div>";
        
        // Knoppen
        echo "
            <div class='flex justify-end space-x-3 pt-4'>
                <a href='/recurring-transactions' class='inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                    Annuleren
                </a>
                <button type='submit' class='inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                    " . ($isEdit ? 'Bijwerken' : 'Aanmaken') . "
                </button>
            </div>";
        
        // Formulier einde
        echo "</form>";
        
        // Container einde
        echo "</div>";
        
        // JavaScript voor UI interacties
        echo "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update volgende datum bij wijzigen frequentie en startdatum
            const frequencySelect = document.getElementById('frequency');
            const startDateInput = document.getElementById('start_date');
            const nextDueDateInput = document.getElementById('next_due_date');
            
            // Functie om volgende datum te berekenen op basis van frequentie en startdatum
            function updateNextDueDate() {
                const startDate = new Date(startDateInput.value);
                const frequency = frequencySelect.value;
                
                if (!startDate || !frequency) return;
                
                let nextDate = new Date(startDate);
                
                switch (frequency) {
                    case 'daily':
                        nextDate.setDate(nextDate.getDate() + 1);
                        break;
                    case 'weekly':
                        nextDate.setDate(nextDate.getDate() + 7);
                        break;
                    case 'monthly':
                        nextDate.setMonth(nextDate.getMonth() + 1);
                        break;
                    case 'quarterly':
                        nextDate.setMonth(nextDate.getMonth() + 3);
                        break;
                    case 'yearly':
                        nextDate.setFullYear(nextDate.getFullYear() + 1);
                        break;
                }
                
                // Format date to YYYY-MM-DD
                const year = nextDate.getFullYear();
                const month = String(nextDate.getMonth() + 1).padStart(2, '0');
                const day = String(nextDate.getDate()).padStart(2, '0');
                
                nextDueDateInput.value = `${year}-${month}-${day}`;
            }
            
            // Update volgende datum bij wijzigen frequentie of startdatum
            frequencySelect.addEventListener('change', updateNextDueDate);
            startDateInput.addEventListener('change', updateNextDueDate);
        });
        </script>";
        
        echo "</div>";
    }
}