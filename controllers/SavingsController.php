<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\SavingsTransaction;
use DateTime;

class SavingsController extends Controller {
    
    /**
     * Toon overzicht van spaardoelen
     */
    public function index() {
        // Controleer of gebruiker is ingelogd en haal user ID op
        $userId = $this->requireLogin();
        
        // Haal alle spaardoelen op
        $includeCompleted = isset($_GET['show_completed']) && $_GET['show_completed'] == 1;
        $savingsGoals = SavingsGoal::getAllByUser($userId, $includeCompleted);
        
        // Bereken statistieken voor elk spaardoel
        foreach ($savingsGoals as &$goal) {
            $goal['stats'] = SavingsGoal::calculateStats($goal);
        }
        
        // Bereken totalen
        $totals = [
            'current' => array_sum(array_column($savingsGoals, 'current_amount')),
            'target' => array_sum(array_column($savingsGoals, 'target_amount')),
            'remaining' => 0,
            'percentage' => 0
        ];
        
        if ($totals['target'] > 0) {
            $totals['remaining'] = $totals['target'] - $totals['current'];
            $totals['percentage'] = min(100, round(($totals['current'] / $totals['target']) * 100));
        }
        
        // Start buffering met paginatitel
        $render = $this->startBuffering('Spaardoelen');
        
        // Toon de spaardoelen pagina
        $this->renderSavingsGoalsList($savingsGoals, $totals);
        
        // Render de pagina met de app layout
        $render();
    }
    
    /**
     * Toon formulier om een nieuw spaardoel aan te maken
     */
    public function create() {
        // Controleer of gebruiker is ingelogd
        $userId = $this->requireLogin();
        
        // Haal rekeningnummers op voor het formulier
        $accounts = Account::getAllByUser($userId);
        
        // Start buffering met paginatitel
        $render = $this->startBuffering('Nieuw Spaardoel');
        
        // Toon het formulier
        $this->renderSavingsGoalForm(null, [], [], $accounts);
        
        // Render de pagina met de app layout
        $render();
    }
    
    /**
     * Verwerk het aanmaken van een nieuw spaardoel
     */
    public function store() {
        $userId = $this->requireLogin();
        
        // Valideer de invoer
        $errors = [];
        
        if (empty($_POST['name'])) {
            $errors['name'] = 'Naam is verplicht';
        }
        
        if (empty($_POST['target_amount']) || !is_numeric($_POST['target_amount']) || $_POST['target_amount'] <= 0) {
            $errors['target_amount'] = 'Voer een geldig doelbedrag in (groter dan 0)';
        }
        
        if (empty($_POST['account_id']) || !is_numeric($_POST['account_id'])) {
            $errors['account_id'] = 'Selecteer een geldige rekening';
        } else {
            // Controleer of de rekening van de ingelogde gebruiker is
            $account = Account::getById($_POST['account_id']);
            if (!$account || $account['user_id'] != $userId) {
                $errors['account_id'] = 'Ongeldige rekening geselecteerd';
            }
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $render = $this->startBuffering('Nieuw spaardoel');
            $accounts = Account::getAllByUser($userId);
            $this->renderSavingsGoalForm(null, $errors, $_POST, $accounts);
            $render();
            return;
        }
        
        // Bereid de gegevens voor
        $savingsGoal = [
            'user_id' => $userId,
            'name' => $_POST['name'],
            'target_amount' => $_POST['target_amount'],
            'current_amount' => $_POST['current_amount'] ?? 0,
            'account_id' => $_POST['account_id'],
            'description' => $_POST['description'] ?? '',
            'icon' => $_POST['icon'] ?? 'savings',
            'target_date' => $_POST['target_date'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Sla het spaardoel op
        $savingsGoalId = SavingsGoal::create($savingsGoal);
        
        // Doorverwijzen naar de overzichtspagina
        header('Location: /savings?message=Spaardoel succesvol aangemaakt');
        exit;
    }
    
    /**
     * Toon de details van een individueel spaardoel
     */
    public function show() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: /savings?error=Ongeldig spaardoel ID');
            exit;
        }
        
        // Haal het spaardoel op
        $savingsGoal = SavingsGoal::getById($_GET['id']);
        
        // Controleer of het spaardoel bestaat en van de ingelogde gebruiker is
        if (!$savingsGoal || $savingsGoal['user_id'] != $userId) {
            header('Location: /savings?error=Spaardoel niet gevonden');
            exit;
        }
        
        // Haal transacties voor dit spaardoel op
        $transactions = SavingsTransaction::getAllBySavingsGoal($savingsGoal['id']);
        
        // Render de pagina
        $render = $this->startBuffering($savingsGoal['name']);
        
        // Begin de output
        echo "<div class='max-w-4xl mx-auto'>";
        
        // Header met titel en acties
        echo "
            <div class='flex flex-col md:flex-row justify-between items-start md:items-center mb-6'>
                <div class='flex items-center mb-4 md:mb-0'>
                    <a href='/savings' class='mr-4 text-blue-600 hover:text-blue-800'>
                        ← Terug
                    </a>
                    <h1 class='text-2xl font-bold'>" . htmlspecialchars($savingsGoal['name']) . "</h1>
                </div>
                <div class='flex space-x-2'>
                    <a href='/savings/deposit?id=" . $savingsGoal['id'] . "' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500'>
                        <i class='material-icons mr-1 text-sm'>add</i> Bedrag toevoegen
                    </a>
                    <a href='/savings/edit?id=" . $savingsGoal['id'] . "' class='inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                        <i class='material-icons mr-1 text-sm'>edit</i> Bewerken
                    </a>
                    <a href='/savings/delete?id=" . $savingsGoal['id'] . "' onclick='return confirm(\"Weet je zeker dat je dit spaardoel wilt verwijderen?\")' class='inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500'>
                        <i class='material-icons mr-1 text-sm'>delete</i>
                    </a>
                </div>
            </div>";
        
        // Spaardoel details kaart
        $progress = $savingsGoal['target_amount'] > 0 ? ($savingsGoal['current_amount'] / $savingsGoal['target_amount']) * 100 : 0;
        $formattedProgress = number_format($progress, 1, ',', '.');
        $formattedCurrentAmount = number_format($savingsGoal['current_amount'], 2, ',', '.');
        $formattedTargetAmount = number_format($savingsGoal['target_amount'], 2, ',', '.');
        $remaining = $savingsGoal['target_amount'] - $savingsGoal['current_amount'];
        $formattedRemaining = number_format($remaining, 2, ',', '.');
        
        // Bereken dagen resterend
        $daysRemaining = '';
        if (!empty($savingsGoal['target_date'])) {
            $targetDate = new DateTime($savingsGoal['target_date']);
            $today = new DateTime();
            $interval = $today->diff($targetDate);
            
            if ($targetDate < $today) {
                $daysRemaining = "<span class='text-red-600'>Termijn verstreken</span>";
            } else {
                $daysRemaining = $interval->format('%a dagen');
            }
        }
        
        echo "
            <div class='bg-white rounded-lg shadow-md p-6 mb-8'>
                <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                    <div>
                        <h2 class='text-lg font-semibold mb-4'>Details</h2>
                        <dl class='space-y-2'>
                            <div class='flex justify-between'>
                                <dt class='text-sm font-medium text-gray-500'>Gespaard bedrag:</dt>
                                <dd class='text-sm font-medium'>€{$formattedCurrentAmount}</dd>
                            </div>
                            <div class='flex justify-between'>
                                <dt class='text-sm font-medium text-gray-500'>Doelbedrag:</dt>
                                <dd class='text-sm font-medium'>€{$formattedTargetAmount}</dd>
                            </div>
                            <div class='flex justify-between'>
                                <dt class='text-sm font-medium text-gray-500'>Nog te sparen:</dt>
                                <dd class='text-sm font-medium'>€{$formattedRemaining}</dd>
                            </div>
                            " . (!empty($savingsGoal['target_date']) ? "
                            <div class='flex justify-between'>
                                <dt class='text-sm font-medium text-gray-500'>Doeldatum:</dt>
                                <dd class='text-sm font-medium'>" . date('d-m-Y', strtotime($savingsGoal['target_date'])) . "</dd>
                            </div>
                            <div class='flex justify-between'>
                                <dt class='text-sm font-medium text-gray-500'>Resterend:</dt>
                                <dd class='text-sm font-medium'>{$daysRemaining}</dd>
                            </div>" : "") . "
                        </dl>
                    </div>
                    <div>
                        <h2 class='text-lg font-semibold mb-4'>Voortgang</h2>
                        <div class='w-full bg-gray-200 rounded-full h-3 mb-2'>
                            <div class='bg-blue-600 h-3 rounded-full' style='width: " . min(100, $progress) . "%'></div>
                        </div>
                        <p class='text-right text-sm font-medium'>{$formattedProgress}%</p>
                        
                        " . (!empty($savingsGoal['description']) ? "
                        <div class='mt-4'>
                            <h3 class='text-sm font-medium text-gray-500 mb-2'>Beschrijving:</h3>
                            <p class='text-sm'>" . nl2br(htmlspecialchars($savingsGoal['description'])) . "</p>
                        </div>" : "") . "
                    </div>
                </div>
            </div>";
        
        // Transacties tabel
        echo "
            <div class='bg-white rounded-lg shadow-md p-6'>
                <div class='flex justify-between items-center mb-6'>
                    <h2 class='text-lg font-semibold'>Transacties</h2>
                    <a href='/savings/deposit?id=" . $savingsGoal['id'] . "' class='text-blue-600 hover:text-blue-800'>
                        <i class='material-icons align-middle text-sm'>add</i> Bedrag toevoegen
                    </a>
                </div>";
        
        if (!empty($transactions)) {
            echo "
                <div class='overflow-x-auto'>
                    <table class='min-w-full divide-y divide-gray-200'>
                        <thead class='bg-gray-50'>
                            <tr>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Datum</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Bedrag</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Beschrijving</th>
                            </tr>
                        </thead>
                        <tbody class='bg-white divide-y divide-gray-200'>";
            
            foreach ($transactions as $transaction) {
                $formattedAmount = number_format($transaction['amount'], 2, ',', '.');
                $transactionClass = $transaction['amount'] > 0 ? 'text-green-600' : 'text-red-600';
                $transactionSign = $transaction['amount'] > 0 ? '+' : '';
                
                echo "
                            <tr>
                                <td class='px-6 py-4 whitespace-nowrap text-sm'>" . date('d-m-Y', strtotime($transaction['date'])) . "</td>
                                <td class='px-6 py-4 whitespace-nowrap text-sm {$transactionClass}'>{$transactionSign}€{$formattedAmount}</td>
                                <td class='px-6 py-4 whitespace-nowrap text-sm'>" . htmlspecialchars($transaction['description']) . "</td>
                            </tr>";
            }
            
            echo "
                        </tbody>
                    </table>
                </div>";
        } else {
            echo "
                <div class='text-center py-8'>
                    <p class='text-gray-500'>Er zijn nog geen transacties voor dit spaardoel.</p>
                    <a href='/savings/deposit?id=" . $savingsGoal['id'] . "' class='mt-4 inline-block text-blue-600 hover:text-blue-800'>
                        Begin met sparen
                    </a>
                </div>";
        }
        
        echo "
            </div>
        </div>";
        
        // Render de pagina met layout
        $render();
    }
    
    /**
     * Toon formulier om een bedrag toe te voegen aan een spaardoel
     */
    public function deposit() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: /savings?error=Ongeldig spaardoel ID');
            exit;
        }
        
        // Haal het spaardoel op
        $savingsGoal = SavingsGoal::getById($_GET['id']);
        
        // Controleer of het spaardoel bestaat en van de ingelogde gebruiker is
        if (!$savingsGoal || $savingsGoal['user_id'] != $userId) {
            header('Location: /savings?error=Spaardoel niet gevonden');
            exit;
        }
        
        $render = $this->startBuffering('Bedrag toevoegen - ' . $savingsGoal['name']);
        
        // Begin HTML output
        echo "<div class='max-w-4xl mx-auto'>";
        
        // Header met titel en terug-link
        echo "
            <div class='flex items-center mb-6'>
                <a href='/savings/show?id=" . $savingsGoal['id'] . "' class='mr-4 text-blue-600 hover:text-blue-800'>
                    ← Terug
                </a>
                <h1 class='text-2xl font-bold'>Bedrag toevoegen aan " . htmlspecialchars($savingsGoal['name']) . "</h1>
            </div>";
        
        // Spaardoel informatie
        $formattedCurrentAmount = number_format($savingsGoal['current_amount'], 2, ',', '.');
        $formattedTargetAmount = number_format($savingsGoal['target_amount'], 2, ',', '.');
        $remaining = $savingsGoal['target_amount'] - $savingsGoal['current_amount'];
        $formattedRemaining = number_format($remaining, 2, ',', '.');
        
        echo "
            <div class='bg-white rounded-lg shadow-md p-6 mb-6'>
                <div class='flex flex-col md:flex-row md:justify-between mb-4'>
                    <div class='mb-4 md:mb-0'>
                        <p class='text-sm text-gray-500'>Huidig spaarbedrag</p>
                        <p class='text-2xl font-bold'>€{$formattedCurrentAmount}</p>
                    </div>
                    <div class='mb-4 md:mb-0'>
                        <p class='text-sm text-gray-500'>Doelbedrag</p>
                        <p class='text-2xl font-bold'>€{$formattedTargetAmount}</p>
                    </div>
                    <div>
                        <p class='text-sm text-gray-500'>Nog te sparen</p>
                        <p class='text-2xl font-bold'>€{$formattedRemaining}</p>
                    </div>
                </div>
            </div>";
        
        // Formulier voor toevoegen bedrag
        echo "
            <div class='bg-white rounded-lg shadow-md p-6'>
                <form method='post' action='/savings/save-deposit' class='space-y-6'>
                    <input type='hidden' name='savings_goal_id' value='" . $savingsGoal['id'] . "'>
                    
                    <div>
                        <label for='amount' class='block text-sm font-medium text-gray-700'>Bedrag</label>
                        <div class='mt-1 relative rounded-md shadow-sm'>
                            <div class='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                                <span class='text-gray-500 sm:text-sm'>€</span>
                            </div>
                            <input type='number' name='amount' id='amount' required step='0.01' min='0.01'
                                class='pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                        </div>
                        <p class='mt-1 text-sm text-gray-500'>Voer het bedrag in dat je wilt toevoegen aan dit spaardoel</p>
                    </div>
                    
                    <div>
                        <label for='date' class='block text-sm font-medium text-gray-700'>Datum</label>
                        <input type='date' name='date' id='date' required value='" . date('Y-m-d') . "'
                            class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                    </div>
                    
                    <div>
                        <label for='description' class='block text-sm font-medium text-gray-700'>Beschrijving (optioneel)</label>
                        <textarea id='description' name='description' rows='3' 
                            class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                            placeholder='Bijvoorbeeld: Maandelijkse inleg, Bonus, etc.'></textarea>
                    </div>
                    
                    <div class='flex justify-end space-x-3'>
                        <a href='/savings/show?id=" . $savingsGoal['id'] . "' 
                            class='inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Annuleren
                        </a>
                        <button type='submit' 
                            class='inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Bedrag toevoegen
                        </button>
                    </div>
                </form>
            </div>
        </div>";
        
        $render();
    }
    
    /**
     * Verwerk een storting voor een spaardoel
     */
    public function saveDeposit() {
        $userId = $this->requireLogin();
        
        // Controleer of alle benodigde velden zijn ingevuld
        if (!isset($_POST['savings_goal_id']) || !isset($_POST['amount']) || !isset($_POST['date'])) {
            header('Location: /savings?error=Onvolledige gegevens');
            exit;
        }
        
        $savingsGoalId = $_POST['savings_goal_id'];
        $amount = floatval($_POST['amount']);
        $date = $_POST['date'];
        $description = $_POST['description'] ?? '';
        
        // Valideer de gegevens
        if (!is_numeric($savingsGoalId) || $amount <= 0 || empty($date)) {
            header('Location: /savings/deposit?id=' . $savingsGoalId . '&error=Ongeldige gegevens');
            exit;
        }
        
        // Haal het spaardoel op
        $savingsGoal = SavingsGoal::getById($savingsGoalId);
        
        // Controleer of het spaardoel bestaat en van de ingelogde gebruiker is
        if (!$savingsGoal || $savingsGoal['user_id'] != $userId) {
            header('Location: /savings?error=Spaardoel niet gevonden');
            exit;
        }
        
        // Maak een nieuwe transactie aan
        $transaction = [
            'savings_goal_id' => $savingsGoalId,
            'amount' => $amount,
            'date' => $date,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        SavingsTransaction::create($transaction);
        
        // Update het huidige saldo van het spaardoel
        $newAmount = $savingsGoal['current_amount'] + $amount;
        $updatedGoal = [
            'id' => $savingsGoalId,
            'current_amount' => $newAmount,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Controleer of het doel is bereikt
        if ($newAmount >= $savingsGoal['target_amount']) {
            $updatedGoal['completed_at'] = date('Y-m-d H:i:s');
        }
        
        SavingsGoal::update($updatedGoal);
        
        // Doorverwijzen naar de detail pagina
        header('Location: /savings/show?id=' . $savingsGoalId . '&message=Bedrag succesvol toegevoegd');
        exit;
    }
    
    /**
     * Toon formulier om een spaardoel te bewerken
     */
    public function edit() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: /savings?error=Ongeldig spaardoel ID');
            exit;
        }
        
        // Haal het spaardoel op
        $savingsGoal = SavingsGoal::getById($_GET['id']);
        
        // Controleer of het spaardoel bestaat en van de ingelogde gebruiker is
        if (!$savingsGoal || $savingsGoal['user_id'] != $userId) {
            header('Location: /savings?error=Spaardoel niet gevonden');
            exit;
        }
        
        // Accounts ophalen voor het formulier
        $accounts = Account::getAllByUser($userId);
        
        // Render het formulier
        $render = $this->startBuffering('Spaardoel bewerken');
        $this->renderSavingsGoalForm($savingsGoal, [], [], $accounts);
        $render();
    }
    
    /**
     * Verwerk het bijwerken van een spaardoel
     */
    public function update() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: /savings?error=Ongeldig spaardoel ID');
            exit;
        }
        
        // Haal het spaardoel op
        $savingsGoal = SavingsGoal::getById($_GET['id']);
        
        // Controleer of het spaardoel bestaat en van de ingelogde gebruiker is
        if (!$savingsGoal || $savingsGoal['user_id'] != $userId) {
            header('Location: /savings?error=Spaardoel niet gevonden');
            exit;
        }
        
        // Valideer de invoer
        $errors = [];
        
        if (empty($_POST['name'])) {
            $errors['name'] = 'Naam is verplicht';
        }
        
        if (empty($_POST['target_amount']) || !is_numeric($_POST['target_amount']) || $_POST['target_amount'] <= 0) {
            $errors['target_amount'] = 'Voer een geldig doelbedrag in (groter dan 0)';
        }
        
        if (empty($_POST['account_id']) || !is_numeric($_POST['account_id'])) {
            $errors['account_id'] = 'Selecteer een geldige rekening';
        } else {
            // Controleer of de rekening van de ingelogde gebruiker is
            $account = Account::getById($_POST['account_id']);
            if (!$account || $account['user_id'] != $userId) {
                $errors['account_id'] = 'Ongeldige rekening geselecteerd';
            }
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $render = $this->startBuffering('Spaardoel bewerken');
            $accounts = Account::getAllByUser($userId);
            $this->renderSavingsGoalForm($savingsGoal, $errors, $_POST, $accounts);
            $render();
            return;
        }
        
        // Bereid de gegevens voor
        $updatedSavingsGoal = [
            'id' => $savingsGoal['id'],
            'name' => $_POST['name'],
            'target_amount' => $_POST['target_amount'],
            'current_amount' => $_POST['current_amount'] ?? $savingsGoal['current_amount'],
            'account_id' => $_POST['account_id'],
            'description' => $_POST['description'] ?? '',
            'icon' => $_POST['icon'] ?? 'savings',
            'target_date' => $_POST['target_date'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Werk het spaardoel bij
        SavingsGoal::update($updatedSavingsGoal);
        
        // Doorverwijzen naar de overzichtspagina
        header('Location: /savings?message=Spaardoel succesvol bijgewerkt');
        exit;
    }
    
    /**
     * Verwijder een spaardoel
     */
    public function delete() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: /savings?error=Ongeldig spaardoel ID');
            exit;
        }
        
        // Haal het spaardoel op
        $savingsGoal = SavingsGoal::getById($_GET['id']);
        
        // Controleer of het spaardoel bestaat en van de ingelogde gebruiker is
        if (!$savingsGoal || $savingsGoal['user_id'] != $userId) {
            header('Location: /savings?error=Spaardoel niet gevonden');
            exit;
        }
        
        // Verwijder het spaardoel
        SavingsGoal::delete($_GET['id']);
        
        // Doorverwijzen naar de overzichtspagina
        header('Location: /savings?message=Spaardoel succesvol verwijderd');
        exit;
    }
    
    /**
     * Voeg een transactie toe aan een spaardoel
     */
    public function addContribution() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $goalId = $_POST['savings_goal_id'] ?? null;
        
        if (!$goalId) {
            header('Location: /savings');
            exit;
        }
        
        // Haal spaardoel op
        $savingsGoal = SavingsGoal::getById($goalId, $userId);
        
        if (!$savingsGoal) {
            header('Location: /savings');
            exit;
        }
        
        // Valideer input
        $amount = $_POST['amount'] ?? '';
        $date = $_POST['date'] ?? date('Y-m-d');
        $note = $_POST['note'] ?? '';
        $accountId = $_POST['account_id'] ?? null;
        $createTransaction = isset($_POST['create_transaction']) && $_POST['create_transaction'] == 1;
        
        $errors = [];
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in';
        }
        
        if (empty($date) || !strtotime($date)) {
            $errors['date'] = 'Voer een geldige datum in';
        }
        
        if ($createTransaction && empty($accountId)) {
            $errors['account_id'] = 'Selecteer een rekening';
        }
        
        // Als er fouten zijn, redirect terug
        if (!empty($errors)) {
            // Voor nu simpelweg redirecten, in de toekomst kan hier een betere foutafhandeling komen
            header('Location: /savings/show?id=' . $goalId . '&error=invalid_input');
            exit;
        }
        
        // Maak een transactie aan indien nodig
        $transactionId = null;
        if ($createTransaction) {
            $transactionData = [
                'user_id' => $userId,
                'account_id' => $accountId,
                'category_id' => null, // Eventueel een speciale spaarcategorie maken
                'amount' => $amount,
                'type' => 'expense', // Geld dat naar sparen gaat is een uitgave voor de reguliere rekening
                'description' => 'Bijdrage aan spaardoel: ' . $savingsGoal['name'],
                'date' => $date
            ];
            
            $transactionId = Transaction::create($transactionData);
        }
        
        // Voeg de bijdrage toe aan het spaardoel
        $contributionData = [
            'user_id' => $userId,
            'savings_goal_id' => $goalId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'date' => $date,
            'note' => $note
        ];
        
        SavingsGoal::addTransaction($contributionData);
        
        // Redirect naar detail pagina
        header('Location: /savings/show?id=' . $goalId . '&success=contribution_added');
        exit;
    }
    
    /**
     * Verwijder een transactie van een spaardoel
     */
    public function removeContribution() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $goalId = $_GET['goal_id'] ?? null;
        $transactionId = $_GET['transaction_id'] ?? null;
        
        if (!$goalId || !$transactionId) {
            header('Location: /savings');
            exit;
        }
        
        // Verwijder de transactie
        SavingsGoal::removeTransaction($transactionId, $goalId, $userId);
        
        // Redirect naar detail pagina
        header('Location: /savings/show?id=' . $goalId . '&success=contribution_removed');
        exit;
    }
    
    /**
     * Render een lijst van spaardoelen
     */
    private function renderSavingsGoalsList($savingsGoals, $totals) {
        // Controleer of er spaardoelen zijn
        $hasSavingGoals = !empty($savingsGoals);
        $includeCompleted = isset($_GET['show_completed']) && $_GET['show_completed'] == 1;

        // Begin de HTML-output
        echo "<div class='max-w-7xl mx-auto'>";
        
        // Toon header met filters en acties
        echo "
            <div class='flex flex-col md:flex-row justify-between items-start md:items-center mb-6'>
                <h1 class='text-2xl font-bold'>Spaardoelen</h1>
                <div class='mt-4 md:mt-0 flex flex-col sm:flex-row items-start sm:items-center space-y-3 sm:space-y-0 sm:space-x-3'>
                    <a href='/savings/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                        Nieuw spaardoel
                    </a>
                    <a href='/savings" . ($includeCompleted ? '' : '?show_completed=1') . "' class='text-sm text-blue-600 hover:underline'>
                        " . ($includeCompleted ? 'Verberg voltooide doelen' : 'Toon ook voltooide doelen') . "
                    </a>
                </div>
            </div>";
        
        // Toon totalen kaart
        if ($hasSavingGoals) {
            echo "
                <div class='bg-white rounded-lg shadow p-6 mb-8'>
                    <h2 class='text-xl font-semibold mb-4'>Totaal voortgang</h2>
                    <div class='grid grid-cols-1 md:grid-cols-3 gap-4 mb-4'>
                        <div>
                            <div class='text-sm text-gray-500'>Totaal gespaard</div>
                            <div class='text-2xl font-bold text-green-600'>€" . number_format($totals['current'], 2, ',', '.') . "</div>
                        </div>
                        <div>
                            <div class='text-sm text-gray-500'>Totaal doelbedrag</div>
                            <div class='text-2xl font-bold text-blue-600'>€" . number_format($totals['target'], 2, ',', '.') . "</div>
                        </div>
                        <div>
                            <div class='text-sm text-gray-500'>Nog te sparen</div>
                            <div class='text-2xl font-bold text-gray-800'>€" . number_format($totals['remaining'], 2, ',', '.') . "</div>
                        </div>
                    </div>
                    <div class='w-full h-4 bg-gray-200 rounded-full overflow-hidden'>
                        <div class='h-full bg-green-500' style='width: " . min(100, $totals['percentage']) . "%'></div>
                    </div>
                    <div class='text-right mt-1 text-sm text-gray-600'>" . number_format($totals['percentage'], 1) . "%</div>
                </div>
            ";
        }
        
        // Toon spaardoelen grid of placeholder
        if ($hasSavingGoals) {
            echo "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'>";
            
            foreach ($savingsGoals as $goal) {
                $stats = $goal['stats'];
                
                // Bepaal kleur op basis van voortgang of status
                $cardClass = '';
                $progressBarColor = 'bg-blue-500';
                
                if ($goal['current_amount'] >= $goal['target_amount']) {
                    $cardClass = 'border-green-500';
                    $progressBarColor = 'bg-green-500';
                } elseif ($stats['percentage'] >= 75) {
                    $cardClass = 'border-yellow-500';
                    $progressBarColor = 'bg-yellow-500';
                } elseif ($stats['is_overdue']) {
                    $cardClass = 'border-red-500';
                    $progressBarColor = 'bg-red-500';
                }
                
                // Bouw de kaart voor elk spaardoel
                echo "
                    <div class='bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 border-l-4 {$cardClass}'>
                        <div class='flex justify-between items-start'>
                            <h3 class='text-lg font-semibold'>" . htmlspecialchars($goal['name']) . "</h3>
                            <div class='flex space-x-2'>
                                <a href='/savings/show?id=" . $goal['id'] . "' class='text-blue-600 hover:text-blue-800'>
                                    <svg class='h-5 w-5' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z' />
                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' />
                                    </svg>
                                </a>
                            </div>
                        </div>
                        
                        <p class='text-gray-600 mt-1 text-sm'>" . htmlspecialchars($goal['description'] ?: 'Geen beschrijving') . "</p>
                        
                        <div class='mt-4'>
                            <div class='flex justify-between text-sm mb-1'>
                                <span>Voortgang: {$stats['percentage']}%</span>
                                <span>€" . number_format($goal['current_amount'], 2, ',', '.') . " / €" . number_format($goal['target_amount'], 2, ',', '.') . "</span>
                            </div>
                            <div class='w-full bg-gray-200 rounded-full h-2.5'>
                                <div class='h-full rounded-full {$progressBarColor}' style='width: {$stats['percentage']}%'></div>
                            </div>
                        </div>
                        
                        " . ($goal['target_date'] ? "
                        <div class='mt-4 text-sm'>
                            <span class='font-medium'>Streefdatum: </span>
                            <span class='" . ($stats['is_overdue'] ? 'text-red-600' : 'text-gray-600') . "'>" . date('d-m-Y', strtotime($goal['target_date'])) . "</span>
                            " . ($stats['days_left'] !== null ? "
                                <span class='ml-2 inline-block px-2 py-0.5 rounded-full text-xs " . ($stats['is_overdue'] ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') . "'>
                                    " . ($stats['is_overdue'] ? abs($stats['days_left']) . " dagen te laat" : $stats['days_left'] . " dagen over") . "
                                </span>
                            " : "") . "
                        </div>
                        " : "") . "
                    </div>
                ";
            }
            
            echo "</div>";
        } else {
            echo "
                <div class='bg-white shadow rounded-lg p-6 text-center'>
                    <svg class='h-16 w-16 text-gray-400 mx-auto mb-4' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7' />
                    </svg>
                    <h3 class='text-lg font-medium text-gray-900 mb-2'>Geen spaardoelen</h3>
                    <p class='text-gray-500 mb-6'>Je hebt nog geen spaardoelen aangemaakt.</p>
                    <a href='/savings/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700'>
                        Maak een spaardoel aan
                    </a>
                </div>
            ";
        }
        
        // Sluit de container af
        echo "</div>";
    }
    
    /**
     * Render het formulier voor spaardoelen (nieuw/bewerken)
     */
    private function renderSavingsGoalForm($savingsGoal = null, $errors = [], $oldInput = [], $accounts = []) {
        $isEdit = $savingsGoal !== null;
        $title = $isEdit ? 'Spaardoel bewerken' : 'Nieuw spaardoel';
        
        // Haal accountgegevens op als die niet zijn meegegeven
        if (empty($accounts) && Auth::check()) {
            $accounts = Account::getAllByUser(Auth::id());
        }
        
        // Begin de HTML-output
        echo "<div class='max-w-4xl mx-auto'>";
        
        // Header met titel en terug-link
        echo "
            <div class='flex justify-between items-center mb-6'>
                <h1 class='text-2xl font-bold'>{$title}</h1>
                <a href='/savings' class='text-blue-600 hover:text-blue-800'>
                    ← Terug naar overzicht
                </a>
            </div>";
        
        // Formulier container
        echo "<div class='bg-white rounded-lg shadow-md p-6'>";
        
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
        echo "<form method='post' action='" . ($isEdit ? "/savings/update?id={$savingsGoal['id']}" : "/savings/store") . "' class='space-y-6'>";
        
        // Naam veld
        echo "
            <div>
                <label for='name' class='block text-sm font-medium text-gray-700'>Naam spaardoel</label>
                <input type='text' id='name' name='name' value='" . htmlspecialchars($oldInput['name'] ?? ($savingsGoal['name'] ?? '')) . "' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
            </div>";
        
        // Doelbedrag veld
        echo "
            <div>
                <label for='target_amount' class='block text-sm font-medium text-gray-700'>Doelbedrag</label>
                <div class='mt-1 relative rounded-md shadow-sm'>
                    <div class='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                        <span class='text-gray-500 sm:text-sm'>€</span>
                    </div>
                    <input type='number' id='target_amount' name='target_amount' min='0.01' step='0.01' value='" . htmlspecialchars($oldInput['target_amount'] ?? ($savingsGoal['target_amount'] ?? '')) . "' required class='pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                </div>
            </div>";
        
        // Huidig bedrag veld
        echo "
            <div>
                <label for='current_amount' class='block text-sm font-medium text-gray-700'>Huidig gespaard bedrag</label>
                <div class='mt-1 relative rounded-md shadow-sm'>
                    <div class='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                        <span class='text-gray-500 sm:text-sm'>€</span>
                    </div>
                    <input type='number' id='current_amount' name='current_amount' min='0' step='0.01' value='" . htmlspecialchars($oldInput['current_amount'] ?? ($savingsGoal['current_amount'] ?? '0')) . "' class='pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                </div>
                <p class='mt-1 text-sm text-gray-500'>Laat op 0 als je nog niets gespaard hebt</p>
            </div>";
        
        // Streefdatum veld
        echo "
            <div>
                <label for='target_date' class='block text-sm font-medium text-gray-700'>Streefdatum (optioneel)</label>
                <input type='date' id='target_date' name='target_date' value='" . htmlspecialchars($oldInput['target_date'] ?? ($savingsGoal['target_date'] ?? '')) . "' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                <p class='mt-1 text-sm text-gray-500'>Datum waarop je dit bedrag gespaard wilt hebben</p>
            </div>";
        
        // Rekening selectie
        echo "
            <div>
                <label for='account_id' class='block text-sm font-medium text-gray-700'>Gekoppelde rekening</label>
                <select id='account_id' name='account_id' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>";
        
        foreach ($accounts as $account) {
            $selected = ($oldInput['account_id'] ?? ($savingsGoal['account_id'] ?? '')) == $account['id'] ? 'selected' : '';
            echo "<option value='{$account['id']}' {$selected}>" . htmlspecialchars($account['name']) . " (€" . number_format($account['balance'], 2, ',', '.') . ")</option>";
        }
        
        echo "
                </select>
                <p class='mt-1 text-sm text-gray-500'>De rekening waar dit spaargeld op staat</p>
            </div>";
        
        // Beschrijving veld
        echo "
            <div>
                <label for='description' class='block text-sm font-medium text-gray-700'>Beschrijving (optioneel)</label>
                <textarea id='description' name='description' rows='3' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>" . htmlspecialchars($oldInput['description'] ?? ($savingsGoal['description'] ?? '')) . "</textarea>
            </div>";
        
        // Icoon veld
        echo "
            <div>
                <label for='icon' class='block text-sm font-medium text-gray-700'>Icoon (optioneel)</label>
                <input type='text' id='icon' name='icon' value='" . htmlspecialchars($oldInput['icon'] ?? ($savingsGoal['icon'] ?? '')) . "' class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                <p class='mt-1 text-sm text-gray-500'>Materiaal design icoon naam, bijv: savings, beach_access, flight</p>
            </div>";
        
        // Knoppen
        echo "
            <div class='flex justify-end space-x-3'>
                <a href='/savings' class='inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
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
        echo "</div>";
    }
}