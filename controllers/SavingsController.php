<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\Account;

class SavingsController {
    
    /**
     * Toon overzicht van spaardoelen
     */
    public function index() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal alle spaardoelen op
        $includeCompleted = isset($_GET['show_completed']) && $_GET['show_completed'] == 1;
        $savingsGoals = SavingsGoal::getAllByUser($userId, $includeCompleted);
        
        // Bereken statistieken voor elk spaardoel
        foreach ($savingsGoals as &$goal) {
            $goal['stats'] = SavingsGoal::calculateStats($goal);
        }
        
        // Bereken totalen
        $totalTarget = array_reduce($savingsGoals, function($total, $goal) {
            return $total + $goal['target_amount'];
        }, 0);
        
        $totalCurrent = array_reduce($savingsGoals, function($total, $goal) {
            return $total + $goal['current_amount'];
        }, 0);
        
        $totalProgress = $totalTarget > 0 ? ($totalCurrent / $totalTarget) * 100 : 0;
        
        // Toon de view
        $this->renderSavingsGoalsList($savingsGoals, [
            'total_target' => $totalTarget,
            'total_current' => $totalCurrent,
            'total_progress' => $totalProgress,
            'include_completed' => $includeCompleted
        ]);
    }
    
    /**
     * Toon formulier voor nieuw spaardoel
     */
    public function create() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        // Toon het formulier
        $this->renderSavingsGoalForm();
    }
    
    /**
     * Sla een nieuw spaardoel op
     */
    public function store() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Valideer input
        $name = $_POST['name'] ?? '';
        $targetAmount = $_POST['target_amount'] ?? '';
        $startDate = $_POST['start_date'] ?? date('Y-m-d');
        $targetDate = $_POST['target_date'] ?? '';
        $description = $_POST['description'] ?? '';
        $color = $_POST['color'] ?? '#4CAF50';
        
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Voer een naam in voor het spaardoel';
        }
        
        if (empty($targetAmount) || !is_numeric($targetAmount) || $targetAmount <= 0) {
            $errors['target_amount'] = 'Voer een geldig doelbedrag in';
        }
        
        if (empty($targetDate) || !strtotime($targetDate)) {
            $errors['target_date'] = 'Voer een geldige doeldatum in';
        }
        
        if (strtotime($targetDate) < strtotime($startDate)) {
            $errors['target_date'] = 'De doeldatum moet na de startdatum liggen';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $this->renderSavingsGoalForm(null, $errors, $_POST);
            return;
        }
        
        // Sla het spaardoel op
        $goalData = [
            'user_id' => $userId,
            'name' => $name,
            'target_amount' => $targetAmount,
            'current_amount' => 0, // Begin met 0
            'start_date' => $startDate,
            'target_date' => $targetDate,
            'description' => $description,
            'color' => $color,
            'is_completed' => 0
        ];
        
        $goalId = SavingsGoal::create($goalData);
        
        // Redirect naar overzicht
        header('Location: /savings');
        exit;
    }
    
    /**
     * Toon details van een spaardoel
     */
    public function show() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $goalId = $_GET['id'] ?? null;
        
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
        
        // Bereken statistieken
        $stats = SavingsGoal::calculateStats($savingsGoal);
        
        // Haal transacties op voor dit spaardoel
        $transactions = SavingsGoal::getTransactions($goalId, $userId);
        
        // Haal rekeningen op voor het overschrijvingsformulier
        $accounts = Account::getAllByUser($userId);
        
        // Toon de view
        $this->renderSavingsGoalDetail($savingsGoal, $stats, $transactions, $accounts);
    }
    
    /**
     * Toon formulier voor bewerken spaardoel
     */
    public function edit() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $goalId = $_GET['id'] ?? null;
        
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
        
        // Toon het formulier
        $this->renderSavingsGoalForm($savingsGoal);
    }
    
    /**
     * Update een bestaand spaardoel
     */
    public function update() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $goalId = $_POST['id'] ?? null;
        
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
        
        // Valideer input (zelfde als bij store)
        $name = $_POST['name'] ?? '';
        $targetAmount = $_POST['target_amount'] ?? '';
        $startDate = $_POST['start_date'] ?? $savingsGoal['start_date'];
        $targetDate = $_POST['target_date'] ?? '';
        $description = $_POST['description'] ?? '';
        $color = $_POST['color'] ?? $savingsGoal['color'];
        
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Voer een naam in voor het spaardoel';
        }
        
        if (empty($targetAmount) || !is_numeric($targetAmount) || $targetAmount <= 0) {
            $errors['target_amount'] = 'Voer een geldig doelbedrag in';
        }
        
        if (empty($targetDate) || !strtotime($targetDate)) {
            $errors['target_date'] = 'Voer een geldige doeldatum in';
        }
        
        if (strtotime($targetDate) < strtotime($startDate)) {
            $errors['target_date'] = 'De doeldatum moet na de startdatum liggen';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $this->renderSavingsGoalForm($savingsGoal, $errors, $_POST);
            return;
        }
        
        // Update het spaardoel
        $goalData = [
            'name' => $name,
            'target_amount' => $targetAmount,
            'start_date' => $startDate,
            'target_date' => $targetDate,
            'description' => $description,
            'color' => $color
        ];
        
        // Controleer of het doel nu is voltooid
        if ($savingsGoal['current_amount'] >= $targetAmount) {
            $goalData['is_completed'] = 1;
        } else {
            $goalData['is_completed'] = 0;
        }
        
        SavingsGoal::update($goalId, $goalData, $userId);
        
        // Redirect naar detail pagina
        header('Location: /savings/show?id=' . $goalId);
        exit;
    }
    
    /**
     * Verwijder een spaardoel
     */
    public function delete() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $goalId = $_GET['id'] ?? null;
        
        if (!$goalId) {
            header('Location: /savings');
            exit;
        }
        
        // Verwijder het spaardoel
        SavingsGoal::delete($goalId, $userId);
        
        // Redirect naar overzicht
        header('Location: /savings');
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
     * Render het overzicht van spaardoelen
     */
    private function renderSavingsGoalsList($savingsGoals, $totals) {
        // Definieer de huidige pagina
        $currentPage = 'savings';
        
        // Begin output
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Spaardoelen</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>";
        
        // Include het navigatiecomponent
        include_once __DIR__ . '/../views/components/navigation.php';
        
        // Hervat de output
        echo "
            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <h1 class='text-2xl font-bold'>Spaardoelen</h1>
                    <div class='mt-4 md:mt-0'>
                        <a href='/savings/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Nieuw spaardoel
                        </a>
                    </div>
                </div>
                
                <div class='bg-white rounded-lg shadow-md p-6 mb-8'>
                    <h2 class='text-xl font-bold mb-4'>Totale voortgang</h2>
                    <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-4'>
                        <div>
                            <div class='text-sm text-gray-500'>Totaal gespaard</div>
                            <div class='text-2xl font-bold text-green-600'>€" . number_format($totals['total_current'], 2, ',', '.') . "</div>
                        </div>
                        <div>
                            <div class='text-sm text-gray-500'>Totaal doelbedrag</div>
                            <div class='text-2xl font-bold text-blue-600'>€" . number_format($totals['total_target'], 2, ',', '.') . "</div>
                        </div>
                        <div>
                            <div class='text-sm text-gray-500'>Nog te sparen</div>
                            <div class='text-2xl font-bold text-gray-800'>€" . number_format(max(0, $totals['total_target'] - $totals['total_current']), 2, ',', '.') . "</div>
                        </div>
                    </div>
                    <div class='w-full h-4 bg-gray-200 rounded-full overflow-hidden'>
                        <div class='h-full bg-green-500' style='width: " . min(100, $totals['total_progress']) . "%'></div>
                    </div>
                    <div class='text-right mt-1 text-sm text-gray-600'>" . number_format($totals['total_progress'], 1) . "%</div>
                </div>
                
                <div class='bg-white rounded-lg shadow-md p-6 mb-4'>
                    <div class='flex justify-between items-center mb-4'>
                        <h2 class='text-xl font-bold'>Jouw spaardoelen</h2>
                        <form method='get' action='/savings' class='flex items-center'>
                            <label class='inline-flex items-center mr-4'>
                                <input type='checkbox' name='show_completed' value='1' " . ($totals['include_completed'] ? 'checked' : '') . " class='form-checkbox h-4 w-4 text-blue-600'>
                                <span class='ml-2 text-sm text-gray-700'>Toon voltooide doelen</span>
                            </label>
                            <button type='submit' class='text-sm text-blue-600 hover:text-blue-800 focus:outline-none'>
                                Toepassen
                            </button>
                        </form>
                    </div>";
                    
        if (empty($savingsGoals)) {
            echo "<p class='text-gray-500 text-center py-8'>Je hebt nog geen spaardoelen. Maak er een aan om te beginnen met sparen!</p>";
        } else {
            echo "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'>";
            
            foreach ($savingsGoals as $goal) {
                $progressBarColor = $goal['stats']['on_track'] ? 'bg-green-500' : 'bg-yellow-500';
                $progressClass = $goal['is_completed'] ? 'bg-blue-500' : $progressBarColor;
                $statusText = $goal['is_completed'] ? 
                    "<span class='px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium'>Voltooid</span>" : 
                    ($goal['stats']['on_track'] ? 
                        "<span class='px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium'>Op schema</span>" : 
                        "<span class='px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium'>Achter op schema</span>");
                
                echo "
                <div class='bg-white border rounded-lg overflow-hidden shadow hover:shadow-md transition-shadow duration-300'>
                    <div class='p-4 border-l-4' style='border-color: " . htmlspecialchars($goal['color']) . "'>
                        <div class='flex justify-between items-start'>
                            <h3 class='font-semibold text-lg'>" . htmlspecialchars($goal['name']) . "</h3>
                            {$statusText}
                        </div>
                        <div class='flex justify-between items-center mt-2 text-sm text-gray-500'>
                            <div>
                                <span>Doel: </span>
                                <span class='font-medium'>€" . number_format($goal['target_amount'], 2, ',', '.') . "</span>
                            </div>
                            <div>
                                <span>Datum: </span>
                                <span class='font-medium'>" . date('d-m-Y', strtotime($goal['target_date'])) . "</span>
                            </div>
                        </div>
                        <div class='mt-4'>
                            <div class='flex justify-between items-center mb-1 text-sm'>
                                <span>Voortgang</span>
                                <span>€" . number_format($goal['current_amount'], 2, ',', '.') . " / €" . number_format($goal['target_amount'], 2, ',', '.') . "</span>
                            </div>
                            <div class='w-full h-2 bg-gray-200 rounded-full overflow-hidden'>
                                <div class='h-full {$progressClass}' style='width: " . min(100, $goal['stats']['progress']) . "%'></div>
                            </div>
                        </div>";
                        
                if (!$goal['is_completed']) {
                    echo "
                        <div class='mt-2'>
                            <div class='flex justify-between items-center mb-1 text-xs text-gray-500'>
                                <span>Tijd</span>
                                <span>" . $goal['stats']['days_remaining'] . " dagen over</span>
                            </div>
                            <div class='w-full h-1 bg-gray-200 rounded-full overflow-hidden'>
                                <div class='h-full bg-gray-500' style='width: " . min(100, $goal['stats']['time_progress']) . "%'></div>
                            </div>
                        </div>";
                }
                
                echo "
                        <div class='mt-4 flex justify-between items-center'>
                            <a href='/savings/show?id=" . $goal['id'] . "' class='text-blue-600 hover:text-blue-800 text-sm'>
                                Details bekijken
                            </a>
                            <div>
                                <a href='/savings/edit?id=" . $goal['id'] . "' class='text-blue-600 hover:text-blue-800 mr-2 text-sm'>
                                    Bewerken
                                </a>
                                <a href='/savings/delete?id=" . $goal['id'] . "' class='text-red-600 hover:text-red-800 text-sm' onclick='return confirm(\"Weet je zeker dat je dit spaardoel wilt verwijderen?\")'>
                                    Verwijderen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>";
            }
            
            echo "</div>";
        }
                
        echo "
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Render het formulier voor spaardoelen (nieuw/bewerken)
     */
    private function renderSavingsGoalForm($savingsGoal = null, $errors = [], $oldInput = []) {
        $isEdit = $savingsGoal !== null;
        $title = $isEdit ? 'Spaardoel bewerken' : 'Nieuw spaardoel';
        $action = $isEdit ? '/savings/update' : '/savings/store';
        
        // Bepaal de waarden voor het formulier
        $nameValue = $isEdit ? $savingsGoal['name'] : ($oldInput['name'] ?? '');
        $targetAmountValue = $isEdit ? $savingsGoal['target_amount'] : ($oldInput['target_amount'] ?? '');
        $startDateValue = $isEdit ? $savingsGoal['start_date'] : ($oldInput['start_date'] ?? date('Y-m-d'));
        $targetDateValue = $isEdit ? $savingsGoal['target_date'] : ($oldInput['target_date'] ?? '');
        $descriptionValue = $isEdit ? $savingsGoal['description'] : ($oldInput['description'] ?? '');
        $colorValue = $isEdit ? $savingsGoal['color'] : ($oldInput['color'] ?? '#4CAF50');
        
        // Definieer de huidige pagina
        $currentPage = 'savings';
        
        // Begin output
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - {$title}</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>";
        
        // Include het navigatiecomponent
        include_once __DIR__ . '/../views/components/navigation.php';
        
        // Hervat de output
        echo "
            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <div class='flex items-center'>
                        <a href='/savings' class='mr-4 text-blue-600 hover:text-blue-800'>
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' viewBox='0 0 20 20' fill='currentColor'>
                                <path fill-rule='evenodd' d='M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 010 2H5.414l4.293 4.293a1 1 0 010 1.414z' clip-rule='evenodd' />
                            </svg>
                        </a>
                        <h1 class='text-2xl font-bold'>{$title}</h1>
                    </div>
                </div>
                
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <form action='{$action}' method='POST' class='space-y-6'>
                        " . ($isEdit ? "<input type='hidden' name='id' value='{$savingsGoal['id']}'>" : "") . "
                        
                        <div>
                            <label for='name' class='block text-sm font-medium text-gray-700'>Naam</label>
                            <input type='text' id='name' name='name' required
                                class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                value='" . htmlspecialchars($nameValue) . "'>
                            " . (!empty($errors['name']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['name']}</p>" : "") . "
                            <p class='mt-1 text-sm text-gray-500'>Geef je spaardoel een duidelijke naam (bijv. 'Vakantie Italië')</p>
                        </div>
                        
                        <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                            <div>
                                <label for='target_amount' class='block text-sm font-medium text-gray-700'>Doelbedrag</label>
                                <div class='mt-1 relative rounded-md shadow-sm'>
                                    <div class='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                                        <span class='text-gray-500 sm:text-sm'>€</span>
                                    </div>
                                    <input type='number' id='target_amount' name='target_amount' step='0.01' min='0.01' required
                                        class='block w-full pl-7 pr-12 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 p-2 border'
                                        placeholder='0,00'
                                        value='" . htmlspecialchars($targetAmountValue) . "'>
                                </div>
                                " . (!empty($errors['target_amount']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['target_amount']}</p>" : "") . "
                                <p class='mt-1 text-sm text-gray-500'>Het totale bedrag dat je wilt sparen</p>
                            </div>
                            
                            <div>
                                <label for='color' class='block text-sm font-medium text-gray-700'>Kleur</label>
                                <div class='mt-1 flex items-center'>
                                    <input type='color' id='color' name='color'
                                        class='h-8 w-8 rounded border-gray-300'
                                        value='" . htmlspecialchars($colorValue) . "'>
                                    <span class='ml-2 text-xs text-gray-500' id='color-value'>" . htmlspecialchars($colorValue) . "</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                            <div>
                                <label for='start_date' class='block text-sm font-medium text-gray-700'>Startdatum</label>
                                <input type='date' id='start_date' name='start_date'
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                    value='" . htmlspecialchars($startDateValue) . "'>
                            </div>
                            
                            <div>
                                <label for='target_date' class='block text-sm font-medium text-gray-700'>Doeldatum</label>
                                <input type='date' id='target_date' name='target_date' required
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                    value='" . htmlspecialchars($targetDateValue) . "'>
                                " . (!empty($errors['target_date']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['target_date']}</p>" : "") . "
                                <p class='mt-1 text-sm text-gray-500'>De datum waarop je je doel wilt bereiken</p>
                            </div>
                        </div>
                        
                        <div>
                            <label for='description' class='block text-sm font-medium text-gray-700'>Beschrijving (optioneel)</label>
                            <textarea id='description' name='description' rows='3'
                                class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                placeholder='Voeg een beschrijving toe voor je spaardoel'>" . htmlspecialchars($descriptionValue) . "</textarea>
                        </div>
                        
                        <div class='flex justify-end space-x-3 mt-6'>
                            <a href='/savings' class='py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
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
                const colorInput = document.getElementById('color');
                const colorValue = document.getElementById('color-value');
                
                colorInput.addEventListener('input', function() {
                    colorValue.textContent = this.value;
                });
                
                // Controleer target date
                const startDateInput = document.getElementById('start_date');
                const targetDateInput = document.getElementById('target_date');
                
                targetDateInput.addEventListener('change', function() {
                    const startDate = new Date(startDateInput.value);
                    const targetDate = new Date(this.value);
                    
                    if (targetDate < startDate) {
                        alert('De doeldatum moet na de startdatum liggen');
                        this.value = '';
                    }
                });
            });
            </script>
        </body>
        </html>";
    }
    
    /**
     * Render de detailpagina van een spaardoel
     */
    private function renderSavingsGoalDetail($savingsGoal, $stats, $transactions, $accounts) {
        // Definieer de huidige pagina
        $currentPage = 'savings';
        
        // Begin output
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Spaardoel: " . htmlspecialchars($savingsGoal['name']) . "</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>";
        
        // Include het navigatiecomponent
        include_once __DIR__ . '/../views/components/navigation.php';
        
        // Hervat de output
        echo "
            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <div class='flex items-center'>
                        <a href='/savings' class='mr-4 text-blue-600 hover:text-blue-800'>
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' viewBox='0 0 20 20' fill='currentColor'>
                                <path fill-rule='evenodd' d='M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 010 2H5.414l4.293 4.293a1 1 0 010 1.414z' clip-rule='evenodd' />
                            </svg>
                        </a>
                        <h1 class='text-2xl font-bold'>" . htmlspecialchars($savingsGoal['name']) . "</h1>
                    </div>
                    
                    <div>
                        <a href='/savings/edit?id=" . $savingsGoal['id'] . "' class='inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Bewerken
                        </a>
                    </div>
                </div>
                
                <!-- Success message (if applicable) -->
                " . (isset($_GET['success']) ? 
                    "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6'>
                        <p>" . ($_GET['success'] === 'contribution_added' ? 'Bijdrage succesvol toegevoegd!' : 'Bijdrage succesvol verwijderd!') . "</p>
                    </div>" : "") . "
                
                <!-- Error message (if applicable) -->
                " . (isset($_GET['error']) ? 
                    "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6'>
                        <p>" . ($_GET['error'] === 'invalid_input' ? 'Er zijn fouten in het formulier. Controleer de invoer.' : 'Er is een fout opgetreden.') . "</p>
                    </div>" : "") . "
                
                <div class='grid grid-cols-1 lg:grid-cols-3 gap-6'>
                    <!-- Left column: Goal details -->
                    <div class='lg:col-span-2 space-y-6'>
                        <!-- Progress card -->
                        <div class='bg-white rounded-lg shadow-md p-6 border-l-4' style='border-color: " . htmlspecialchars($savingsGoal['color']) . "'>
                            <div class='flex justify-between items-center mb-4'>
                                <div>
                                    <h2 class='text-xl font-bold'>Voortgang</h2>
                                    <p class='text-gray-500'>" . ($savingsGoal['is_completed'] ? 'Doel bereikt!' : $stats['days_remaining'] . ' dagen over') . "</p>
                                </div>
                                <div class='text-right'>
                                    <div class='text-sm text-gray-500'>Einddatum</div>
                                    <div class='font-semibold'>" . date('d-m-Y', strtotime($savingsGoal['target_date'])) . "</div>
                                </div>
                            </div>
                            
                            <div class='grid grid-cols-3 gap-4 mb-4'>
                                <div>
                                    <div class='text-sm text-gray-500'>Gespaard</div>
                                    <div class='text-xl font-bold text-green-600'>€" . number_format($savingsGoal['current_amount'], 2, ',', '.') . "</div>
                                </div>
                                <div>
                                    <div class='text-sm text-gray-500'>Doel</div>
                                    <div class='text-xl font-bold text-blue-600'>€" . number_format($savingsGoal['target_amount'], 2, ',', '.') . "</div>
                                </div>
                                <div>
                                    <div class='text-sm text-gray-500'>Nog te sparen</div>
                                    <div class='text-xl font-bold'>" . ($savingsGoal['is_completed'] ? '€0,00' : '€' . number_format(max(0, $savingsGoal['target_amount'] - $savingsGoal['current_amount']), 2, ',', '.')) . "</div>
                                </div>
                            </div>
                            
                            <div class='mb-2'>
                                <div class='flex justify-between items-center mb-1 text-sm'>
                                    <span>Voortgang</span>
                                    <span>" . number_format($stats['progress'], 1) . "%</span>
                                </div>
                                <div class='w-full h-4 bg-gray-200 rounded-full overflow-hidden'>
                                    <div class='h-full " . ($savingsGoal['is_completed'] ? 'bg-blue-500' : ($stats['on_track'] ? 'bg-green-500' : 'bg-yellow-500')) . "' style='width: " . min(100, $stats['progress']) . "%'></div>
                                </div>
                            </div>";
                            
            if (!$savingsGoal['is_completed']) {
                echo "
                            <div class='mb-4'>
                                <div class='flex justify-between items-center mb-1 text-sm'>
                                    <span>Tijdsverloop</span>
                                    <span>" . number_format($stats['time_progress'], 1) . "%</span>
                                </div>
                                <div class='w-full h-2 bg-gray-200 rounded-full overflow-hidden'>
                                    <div class='h-full bg-gray-500' style='width: " . min(100, $stats['time_progress']) . "%'></div>
                                </div>
                            </div>
                            
                            <div class='bg-gray-50 p-4 rounded-lg'>
                                <div class='text-sm font-medium mb-2'>Om je doel te halen:</div>
                                <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                                    <div>
                                        <div class='text-xs text-gray-500'>Je moet nog sparen:</div>
                                        <div class='font-semibold'>€" . number_format($stats['remaining_amount'], 2, ',', '.') . "</div>
                                    </div>
                                    <div>
                                        <div class='text-xs text-gray-500'>Per dag sparen:</div>
                                        <div class='font-semibold'>€" . number_format($stats['amount_per_day'], 2, ',', '.') . "</div>
                                    </div>
                                </div>
                            </div>";
            }
                
                echo "
                        </div>
                        
                        <!-- Description -->
                        " . (!empty($savingsGoal['description']) ? "
                        <div class='bg-white rounded-lg shadow-md p-6'>
                            <h2 class='text-lg font-semibold mb-2'>Beschrijving</h2>
                            <p class='text-gray-700'>" . nl2br(htmlspecialchars($savingsGoal['description'])) . "</p>
                        </div>
                        " : "") . "
                        
                        <!-- Transactions -->
                        <div class='bg-white rounded-lg shadow-md p-6'>
                            <h2 class='text-lg font-semibold mb-4'>Transacties</h2>";
                            
            if (empty($transactions)) {
                echo "<p class='text-gray-500 py-4 text-center'>Nog geen bijdragen aan dit spaardoel.</p>";
            } else {
                echo "
                            <div class='overflow-x-auto'>
                                <table class='min-w-full divide-y divide-gray-200'>
                                    <thead class='bg-gray-50'>
                                        <tr>
                                            <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Datum</th>
                                            <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Bedrag</th>
                                            <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Notitie</th>
                                            <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>Acties</th>
                                        </tr>
                                    </thead>
                                    <tbody class='bg-white divide-y divide-gray-200'>";
                                    
                foreach ($transactions as $transaction) {
                    echo "
                                        <tr>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . date('d-m-Y', strtotime($transaction['date'])) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600'>€" . number_format($transaction['amount'], 2, ',', '.') . "</td>
                                            <td class='px-6 py-4 text-sm text-gray-900'>" . (empty($transaction['note']) ? '-' : htmlspecialchars($transaction['note'])) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                                                <a href='/savings/remove-contribution?goal_id=" . $savingsGoal['id'] . "&transaction_id=" . $transaction['id'] . "' class='text-red-600 hover:text-red-900' onclick='return confirm(\"Weet je zeker dat je deze bijdrage wilt verwijderen?\")'>
                                                    Verwijderen
                                                </a>
                                            </td>
                                        </tr>";
                }
                                    
                echo "
                                    </tbody>
                                </table>
                            </div>";
            }
                            
                echo "
                        </div>
                    </div>
                    
                    <!-- Right column: Add contribution -->
                    <div class='lg:col-span-1 space-y-6'>
                        <div class='bg-white rounded-lg shadow-md p-6'>
                            <h2 class='text-lg font-semibold mb-4'>Bijdrage toevoegen</h2>
                            <form action='/savings/add-contribution' method='POST' class='space-y-4'>
                                <input type='hidden' name='savings_goal_id' value='" . $savingsGoal['id'] . "'>
                                
                                <div>
                                    <label for='amount' class='block text-sm font-medium text-gray-700'>Bedrag</label>
                                    <div class='mt-1 relative rounded-md shadow-sm'>
                                        <div class='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                                            <span class='text-gray-500 sm:text-sm'>€</span>
                                        </div>
                                        <input type='number' id='amount' name='amount' step='0.01' min='0.01' required
                                            class='block w-full pl-7 pr-12 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 p-2 border'
                                            placeholder='0,00'>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for='date' class='block text-sm font-medium text-gray-700'>Datum</label>
                                    <input type='date' id='date' name='date' required
                                        class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                        value='" . date('Y-m-d') . "'>
                                </div>
                                
                                <div>
                                    <label for='note' class='block text-sm font-medium text-gray-700'>Notitie (optioneel)</label>
                                    <input type='text' id='note' name='note'
                                        class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                        placeholder='Bijv. Salaris maart'>
                                </div>
                                
                                <div class='mt-3'>
                                    <div class='relative flex items-start'>
                                        <div class='flex items-center h-5'>
                                            <input id='create_transaction' name='create_transaction' type='checkbox' value='1'
                                                class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded'>
                                        </div>
                                        <div class='ml-3 text-sm'>
                                            <label for='create_transaction' class='font-medium text-gray-700'>Maak transactie aan</label>
                                            <p class='text-gray-500'>Registreer dit ook als uitgave in je transacties</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id='account_selection' class='hidden'>
                                    <label for='account_id' class='block text-sm font-medium text-gray-700'>Van rekening</label>
                                    <select id='account_id' name='account_id' 
                                        class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                        <option value=''>Selecteer rekening</option>";
                                        
            foreach ($accounts as $account) {
                echo "<option value='" . $account['id'] . "'>" . htmlspecialchars($account['name']) . " (€" . number_format($account['balance'], 2, ',', '.') . ")</option>";
            }
                                        
                echo "
                                    </select>
                                </div>
                                
                                <div class='pt-4'>
                                    <button type='submit' class='w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                        Bijdrage toevoegen
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const createTransactionCheckbox = document.getElementById('create_transaction');
                    const accountSelection = document.getElementById('account_selection');
                    
                    createTransactionCheckbox.addEventListener('change', function() {
                        if (this.checked) {
                            accountSelection.classList.remove('hidden');
                        } else {
                            accountSelection.classList.add('hidden');
                        }
                    });
                });
            </script>
        </body>
        </html>";
    }
}