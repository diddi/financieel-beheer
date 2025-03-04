<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;

class BudgetController {
    
    public function index() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal budgetstatus op
        $budgetStatus = Budget::getBudgetStatus($userId);
        
        // Haal alle budgetten op
        $budgets = Budget::getAllByUser($userId);
        
        // Geef de view weer
        $this->renderBudgetsList($budgets, $budgetStatus);
    }
    
    public function create() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal uitgavencategorieën op
        $categories = Category::getAllByUserAndType($userId, 'expense');
        
        // Geef het formulier weer
        $this->renderBudgetForm(null, $categories);
    }
    
    public function store() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Valideer input
        $categoryId = $_POST['category_id'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $period = $_POST['period'] ?? 'monthly';
        $alertThreshold = $_POST['alert_threshold'] ?? 80;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $errors = [];
        
        if (empty($categoryId)) {
            $errors['category_id'] = 'Selecteer een categorie';
        }
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in';
        }
        
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
            $errors['period'] = 'Selecteer een geldige periode';
        }
        
        if (!is_numeric($alertThreshold) || $alertThreshold < 0 || $alertThreshold > 100) {
            $errors['alert_threshold'] = 'Drempel moet tussen 0 en 100 procent zijn';
        }
        
        // Controleer of er al een budget bestaat voor deze categorie
        $existingBudget = Budget::getActiveByUser($userId, $categoryId);
        if (!empty($existingBudget)) {
            $errors['category_id'] = 'Er bestaat al een budget voor deze categorie';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $categories = Category::getAllByUserAndType($userId, 'expense');
            $this->renderBudgetForm(null, $categories, $errors, $_POST);
            return;
        }
        
        // Bereken begin- en einddatum
        $startDate = date('Y-m-d'); // Vandaag
        
        // Sla het budget op
        $budgetData = [
            'user_id' => $userId,
            'category_id' => $categoryId,
            'amount' => $amount,
            'period' => $period,
            'start_date' => $startDate,
            'is_active' => $isActive,
            'alert_threshold' => $alertThreshold
        ];
        
        $budgetId = Budget::create($budgetData);
        
        // Redirect naar budgetten overzicht
        header('Location: /budgets');
        exit;
    }
    
    public function edit($id = null) {
        // ID uit URL halen als het niet als parameter is doorgegeven
        if ($id === null) {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
        }
        
        if (!$id) {
            header('Location: /budgets');
            exit;
        }
        
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal budget op
        $budget = Budget::getById($id, $userId);
        
        if (!$budget) {
            header('Location: /budgets');
            exit;
        }
        
        // Haal uitgavencategorieën op
        $categories = Category::getAllByUserAndType($userId, 'expense');
        
        // Geef het formulier weer
        $this->renderBudgetForm($budget, $categories);
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
            header('Location: /budgets');
            exit;
        }
        
        // Valideer input (zelfde als bij store)
        $categoryId = $_POST['category_id'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $period = $_POST['period'] ?? 'monthly';
        $alertThreshold = $_POST['alert_threshold'] ?? 80;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $errors = [];
        
        if (empty($categoryId)) {
            $errors['category_id'] = 'Selecteer een categorie';
        }
        
        if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in';
        }
        
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
            $errors['period'] = 'Selecteer een geldige periode';
        }
        
        if (!is_numeric($alertThreshold) || $alertThreshold < 0 || $alertThreshold > 100) {
            $errors['alert_threshold'] = 'Drempel moet tussen 0 en 100 procent zijn';
        }
        
        // Haal het huidige budget op
        $budget = Budget::getById($id, $userId);
        
        if (!$budget) {
            header('Location: /budgets');
            exit;
        }
        
        // Controleer of er al een budget bestaat voor deze categorie (als categorie wordt gewijzigd)
        if ($budget['category_id'] != $categoryId) {
            $existingBudget = Budget::getActiveByUser($userId, $categoryId);
            if (!empty($existingBudget)) {
                $errors['category_id'] = 'Er bestaat al een budget voor deze categorie';
            }
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $categories = Category::getAllByUserAndType($userId, 'expense');
            $this->renderBudgetForm($budget, $categories, $errors, $_POST);
            return;
        }
        
        // Update het budget
        $budgetData = [
            'category_id' => $categoryId,
            'amount' => $amount,
            'period' => $period,
            'is_active' => $isActive,
            'alert_threshold' => $alertThreshold
        ];
        
        Budget::update($id, $budgetData, $userId);
        
        // Redirect naar budgetten overzicht
        header('Location: /budgets');
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
            header('Location: /budgets');
            exit;
        }
        
        // Verwijder het budget
        Budget::delete($id, $userId);
        
        // Redirect naar budgetten overzicht
        header('Location: /budgets');
        exit;
    }
    
    // Hulpmethoden voor het weergeven van views
    
    private function renderBudgetsList($budgets, $budgetStatus) {
        // Bereken procentueel budgetgebruik voor de huidige maand
        $monthlyUsagePercentage = 0;
        $totalMonthlyBudget = 0;
        $totalMonthlySpent = 0;
        
        foreach ($budgetStatus as $status) {
            if ($status['period'] === 'monthly') {
                $totalMonthlyBudget += $status['amount'];
                $totalMonthlySpent += $status['spent'];
            }
        }
        
        if ($totalMonthlyBudget > 0) {
            $monthlyUsagePercentage = ($totalMonthlySpent / $totalMonthlyBudget) * 100;
        }
        
        // Genereer de kleur voor de voortgangsbalk
        $progressColor = 'bg-green-500';
        if ($monthlyUsagePercentage >= 90) {
            $progressColor = 'bg-red-500';
        } elseif ($monthlyUsagePercentage >= 75) {
            $progressColor = 'bg-yellow-500';
        }
        
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Budgetten</title>
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
                                <a href='/transactions' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Transacties</a>
                                <a href='/accounts' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Rekeningen</a>
                                <a href='/categories' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Categorieën</a>
                                <a href='/budgets' class='px-3 py-2 rounded-md text-sm font-medium bg-blue-700'>Budgetten</a>
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
                    <h1 class='text-2xl font-bold'>Budgetten</h1>
                    <div class='mt-4 md:mt-0'>
                        <a href='/budgets/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Nieuw budget
                        </a>
                    </div>
                </div>
                
                <div class='bg-white rounded-lg shadow-md p-6 mb-8'>
                    <h2 class='text-xl font-bold mb-4'>Maandelijks budget overzicht</h2>
                    <div class='flex justify-between items-center mb-2'>
                        <div>
                            <span class='font-semibold'>Totaal budget: </span>
                            <span class='font-bold'>€" . number_format($totalMonthlyBudget, 2, ',', '.') . "</span>
                        </div>
                        <div>
                            <span class='font-semibold'>Uitgegeven: </span>
                            <span class='font-bold'>€" . number_format($totalMonthlySpent, 2, ',', '.') . "</span>
                        </div>
                        <div>
                            <span class='font-semibold'>Percentage: </span>
                            <span class='font-bold'>" . number_format($monthlyUsagePercentage, 1) . "%</span>
                        </div>
                    </div>
                    <div class='w-full bg-gray-200 rounded-full h-4'>
                        <div class='h-4 rounded-full {$progressColor}' style='width: " . min(100, $monthlyUsagePercentage) . "%'></div>
                    </div>
                </div>";
                
        if (empty($budgetStatus)) {
            echo "<div class='bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6'>
                    <div class='flex'>
                        <div class='ml-3'>
                            <p class='text-sm text-yellow-700'>
                                Je hebt nog geen budgetten ingesteld. Maak een budget aan om je uitgaven te beheren.
                            </p>
                        </div>
                    </div>
                </div>";
        } else {
            echo "<div class='mb-8'>
                    <h2 class='text-xl font-bold mb-4'>Actieve budgetten</h2>
                    <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>";
            
            foreach ($budgetStatus as $status) {
                $progressColor = 'bg-green-500';
                
                if ($status['is_exceeded']) {
                    $progressColor = 'bg-red-500';
                } elseif ($status['is_warning']) {
                    $progressColor = 'bg-yellow-500';
                }
                
                $periodText = '';
                switch ($status['period']) {
                    case 'daily':
                        $periodText = 'Dagelijks';
                        break;
                    case 'weekly':
                        $periodText = 'Wekelijks';
                        break;
                    case 'monthly':
                        $periodText = 'Maandelijks';
                        break;
                    case 'yearly':
                        $periodText = 'Jaarlijks';
                        break;
                }
                
                echo "<div class='bg-white rounded-lg shadow p-6 border-l-4' style='border-color: " . htmlspecialchars($status['color']) . "'>
                        <div class='flex justify-between items-center mb-2'>
                            <div>
                                <h3 class='font-semibold text-lg'>" . htmlspecialchars($status['category_name']) . "</h3>
                                <p class='text-sm text-gray-500'>{$periodText} budget</p>
                            </div>
                            <div class='text-right'>
                                <div class='font-bold'>" . number_format($status['percentage'], 1) . "%</div>
                                <div class='text-sm text-gray-500'>
                                    €" . number_format($status['spent'], 2, ',', '.') . " / €" . number_format($status['amount'], 2, ',', '.') . "
                                </div>
                            </div>
                        </div>
                        <div class='w-full bg-gray-200 rounded-full h-2.5'>
                            <div class='h-2.5 rounded-full {$progressColor}' style='width: " . min(100, $status['percentage']) . "%'></div>
                        </div>
                        <div class='flex justify-end mt-2'>
                            <a href='/budgets/edit?id=" . $status['id'] . "' class='text-blue-600 hover:underline text-sm'>
                                Bewerken
                            </a>
                        </div>
                    </div>";
            }
            
            echo "  </div>
                </div>";
        }
                
        echo "<div class='bg-white shadow-md rounded-lg overflow-hidden'>
                    <table class='min-w-full divide-y divide-gray-200'>
                        <thead class='bg-gray-50'>
                            <tr>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Categorie</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Periode</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Bedrag</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Drempel</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Status</th>
                                <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='bg-white divide-y divide-gray-200'>";
        
        if (empty($budgets)) {
            echo "<tr><td colspan='6' class='px-6 py-4 text-center text-gray-500'>Geen budgetten gevonden</td></tr>";
        } else {
            foreach ($budgets as $budget) {
                $periodText = '';
                switch ($budget['period']) {
                    case 'daily':
                        $periodText = 'Dagelijks';
                        break;
                    case 'weekly':
                        $periodText = 'Wekelijks';
                        break;
                    case 'monthly':
                        $periodText = 'Maandelijks';
                        break;
                    case 'yearly':
                        $periodText = 'Jaarlijks';
                        break;
                }
                
                $statusText = $budget['is_active'] ? 'Actief' : 'Inactief';
                $statusClass = $budget['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                
                echo "<tr>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='flex items-center'>
                                <div class='flex-shrink-0 h-4 w-4 rounded-full' style='background-color: " . htmlspecialchars($budget['color']) . "'></div>
                                <div class='ml-4 text-sm font-medium text-gray-900'>" . htmlspecialchars($budget['category_name']) . "</div>
                            </div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . $periodText . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>€" . number_format($budget['amount'], 2, ',', '.') . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . $budget['alert_threshold'] . "%</td>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full {$statusClass}'>
                                {$statusText}
                            </span>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                            <a href='/budgets/edit?id=" . $budget['id'] . "' class='text-blue-600 hover:text-blue-900 mr-3'>Bewerken</a>
                            <a href='/budgets/delete?id=" . $budget['id'] . "' class='text-red-600 hover:text-red-900' onclick='return confirm(\"Weet je zeker dat je dit budget wilt verwijderen?\")'>Verwijderen</a>
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
    
    private function renderBudgetForm($budget = null, $categories, $errors = [], $oldInput = []) {
        $isEdit = $budget !== null;
        $title = $isEdit ? 'Budget bewerken' : 'Nieuw budget';
        $action = $isEdit ? '/budgets/update' : '/budgets/store';
        
        // Bepaal de waarden voor het formulier
        $categoryIdValue = $isEdit ? $budget['category_id'] : ($oldInput['category_id'] ?? '');
        $amountValue = $isEdit ? $budget['amount'] : ($oldInput['amount'] ?? '');
        $periodValue = $isEdit ? $budget['period'] : ($oldInput['period'] ?? 'monthly');
        $alertThresholdValue = $isEdit ? $budget['alert_threshold'] : ($oldInput['alert_threshold'] ?? '80');
        $isActiveValue = $isEdit ? $budget['is_active'] : (isset($oldInput['is_active']) ? $oldInput['is_active'] : true);
        
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
                                <a href='/transactions' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Transacties</a>
                                <a href='/accounts' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Rekeningen</a>
                                <a href='/categories' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Categorieën</a>
                                <a href='/budgets' class='px-3 py-2 rounded-md text-sm font-medium bg-blue-700'>Budgetten</a>
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
                        " . ($isEdit ? "<input type='hidden' name='id' value='{$budget['id']}'>" : "") . "
                        
                        <div>
                            <label for='category_id' class='block text-sm font-medium text-gray-700'>Categorie</label>
                            <select id='category_id' name='category_id' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                <option value=''>Selecteer categorie</option>";
        
        foreach ($categories as $category) {
            $selected = $categoryIdValue == $category['id'] ? 'selected' : '';
            echo "<option value='{$category['id']}' {$selected}>" . htmlspecialchars($category['name']) . "</option>";
        }
        
        echo "              </select>
                            " . (!empty($errors['category_id']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['category_id']}</p>" : "") . "
                        </div>
                        
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
                            <label for='period' class='block text-sm font-medium text-gray-700'>Periode</label>
                            <select id='period' name='period' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                <option value='daily' " . ($periodValue === 'daily' ? 'selected' : '') . ">Dagelijks</option>
                                <option value='weekly' " . ($periodValue === 'weekly' ? 'selected' : '') . ">Wekelijks</option>
                                <option value='monthly' " . ($periodValue === 'monthly' ? 'selected' : '') . ">Maandelijks</option>
                                <option value='yearly' " . ($periodValue === 'yearly' ? 'selected' : '') . ">Jaarlijks</option>
                            </select>
                            " . (!empty($errors['period']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['period']}</p>" : "") . "
                        </div>
                        
                        <div>
                            <label for='alert_threshold' class='block text-sm font-medium text-gray-700'>Waarschuwingsdrempel (%)</label>
                            <input type='number' id='alert_threshold' name='alert_threshold' min='0' max='100' required
                                class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                value='" . htmlspecialchars($alertThresholdValue) . "'>
                            <p class='mt-1 text-xs text-gray-500'>Je krijgt een waarschuwing wanneer het budget dit percentage bereikt</p>
                            " . (!empty($errors['alert_threshold']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['alert_threshold']}</p>" : "") . "
                        </div>
                        
                        <div class='relative flex items-start'>
                            <div class='flex items-center h-5'>
                                <input id='is_active' name='is_active' type='checkbox' 
                                    class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded'
                                    " . ($isActiveValue ? 'checked' : '') . ">
                            </div>
                            <div class='ml-3 text-sm'>
                                <label for='is_active' class='font-medium text-gray-700'>Actief</label>
                                <p class='text-gray-500'>Budget wordt meegenomen in berekeningen en overzichten</p>
                            </div>
                        </div>
                        
                        <div class='flex justify-end space-x-3 mt-6'>
                            <a href='/budgets' class='py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                Annuleren
                            </a>
                            <button type='submit' class='py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                " . ($isEdit ? 'Bijwerken' : 'Opslaan') . "
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
