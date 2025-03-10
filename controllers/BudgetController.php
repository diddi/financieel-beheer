<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;

class BudgetController extends Controller {
    
    public function index() {
        $userId = $this->requireLogin();
        
        // Haal alle budgetten op
        $budgets = Budget::getAllByUser($userId);
        
        // Bereken budgetstatus
        $budgetStatus = Budget::getBudgetStatus($userId);
        
        // Render de pagina
        $render = $this->startBuffering('Budgetten');
        
        // Render de lijst van budgetten
        $this->renderBudgetsList($budgets, $budgetStatus);
        
        // Render de pagina
        $render();
    }
    
    public function create() {
        $userId = $this->requireLogin();
        
        // Haal categorieën op
        $categories = Category::getAllByUser($userId);
        
        // Render de pagina
        $render = $this->startBuffering('Nieuw budget');
        
        // Render het formulier
        $this->renderBudgetForm($categories);
        
        // Render de pagina
        $render();
    }
    
    public function store() {
        $userId = $this->requireLogin();
        
        // Valideer de invoer
        $errors = [];
        
        if (empty($_POST['category_id'])) {
            $errors['category_id'] = 'Categorie is verplicht';
        }
        
        if (empty($_POST['amount']) || !is_numeric($_POST['amount']) || floatval($_POST['amount']) <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in (groter dan 0)';
        }
        
        if (empty($_POST['start_date']) || !strtotime($_POST['start_date'])) {
            $errors['start_date'] = 'Voer een geldige startdatum in';
        }
        
        if (empty($_POST['end_date']) || !strtotime($_POST['end_date'])) {
            $errors['end_date'] = 'Voer een geldige einddatum in';
        }
        
        if (!empty($_POST['start_date']) && !empty($_POST['end_date']) 
            && strtotime($_POST['start_date']) > strtotime($_POST['end_date'])) {
            $errors['end_date'] = 'Einddatum moet na startdatum liggen';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $categories = Category::getAllByUser($userId);
            
            $render = $this->startBuffering('Nieuw budget');
            $this->renderBudgetForm($categories, null, $errors, $_POST);
            $render();
            return;
        }
        
        // Bereid gegevens voor
        $budgetData = [
            'user_id' => $userId,
            'category_id' => $_POST['category_id'],
            'amount' => floatval($_POST['amount']),
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Sla budget op
        $budgetId = Budget::create($budgetData);
        
        // Redirect naar overzicht
        header('Location: /budgets?message=Budget succesvol aangemaakt');
        exit;
    }
    
    public function edit($id = null) {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        $budgetId = $id ?? ($_GET['id'] ?? null);
        if (!$budgetId || !is_numeric($budgetId)) {
            header('Location: /budgets?error=Ongeldig budget ID');
            exit;
        }
        
        // Haal budget op
        $budget = Budget::getById($budgetId, $userId);
        
        // Controleer of het budget bestaat en van de ingelogde gebruiker is
        if (!$budget) {
            header('Location: /budgets?error=Budget niet gevonden');
            exit;
        }
        
        // Haal categorieën op
        $categories = Category::getAllByUser($userId);
        
        // Render de pagina
        $render = $this->startBuffering('Budget bewerken');
        
        // Render het formulier
        $this->renderBudgetForm($categories, $budget);
        
        // Render de pagina
        $render();
    }
    
    public function update() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: /budgets?error=Ongeldig budget ID');
            exit;
        }
        
        $budgetId = $_GET['id'];
        
        // Haal het budget op
        $budget = Budget::getById($budgetId, $userId);
        
        // Controleer of het budget bestaat en van de ingelogde gebruiker is
        if (!$budget) {
            header('Location: /budgets?error=Budget niet gevonden');
            exit;
        }
        
        // Valideer de invoer
        $errors = [];
        
        if (empty($_POST['category_id'])) {
            $errors['category_id'] = 'Categorie is verplicht';
        }
        
        if (empty($_POST['amount']) || !is_numeric($_POST['amount']) || floatval($_POST['amount']) <= 0) {
            $errors['amount'] = 'Voer een geldig bedrag in (groter dan 0)';
        }
        
        if (empty($_POST['start_date']) || !strtotime($_POST['start_date'])) {
            $errors['start_date'] = 'Voer een geldige startdatum in';
        }
        
        if (empty($_POST['end_date']) || !strtotime($_POST['end_date'])) {
            $errors['end_date'] = 'Voer een geldige einddatum in';
        }
        
        if (!empty($_POST['start_date']) && !empty($_POST['end_date']) 
            && strtotime($_POST['start_date']) > strtotime($_POST['end_date'])) {
            $errors['end_date'] = 'Einddatum moet na startdatum liggen';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $categories = Category::getAllByUser($userId);
            
            $render = $this->startBuffering('Budget bewerken');
            $this->renderBudgetForm($categories, $budget, $errors, $_POST);
            $render();
            return;
        }
        
        // Bereid gegevens voor
        $budgetData = [
            'category_id' => $_POST['category_id'],
            'amount' => floatval($_POST['amount']),
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Update budget
        Budget::update($budgetId, $budgetData, $userId);
        
        // Redirect naar overzicht
        header('Location: /budgets?message=Budget succesvol bijgewerkt');
        exit;
    }
    
    public function delete() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is opgegeven
        if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: /budgets?error=Ongeldig budget ID');
            exit;
        }
        
        $budgetId = $_GET['id'];
        
        // Haal het budget op
        $budget = Budget::getById($budgetId, $userId);
        
        // Controleer of het budget bestaat en van de ingelogde gebruiker is
        if (!$budget) {
            header('Location: /budgets?error=Budget niet gevonden');
            exit;
        }
        
        // Verwijder het budget
        Budget::delete($budgetId, $userId);
        
        // Redirect naar overzicht
        header('Location: /budgets?message=Budget succesvol verwijderd');
        exit;
    }
    
    private function renderBudgetsList($budgets, $budgetStatus) {
        // Begin HTML output
        echo "<div class='max-w-7xl mx-auto'>";
        
        // Header sectie
        echo "
            <div class='flex justify-between items-center mb-6'>
                <h1 class='text-2xl font-bold'>Budgetten</h1>
                <a href='/budgets/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                    <i class='material-icons mr-1 text-sm'>add</i> Nieuw budget
                </a>
            </div>";
        
        // Statuskaarten
        if (!empty($budgetStatus)) {
            echo "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8'>";
            
            // Totaal budget
            $totalBudget = array_sum(array_column($budgetStatus, 'amount'));
            $totalSpent = array_sum(array_column($budgetStatus, 'spent'));
            $percentageSpent = $totalBudget > 0 ? min(100, round(($totalSpent / $totalBudget) * 100)) : 0;
            
            echo "
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <h2 class='text-lg font-semibold mb-2'>Totaal budget</h2>
                    <div class='flex justify-between items-center mb-2'>
                        <span class='text-gray-500'>Uitgegeven</span>
                        <span class='font-medium'>€" . number_format($totalSpent, 2, ',', '.') . " / €" . number_format($totalBudget, 2, ',', '.') . "</span>
                    </div>
                    <div class='w-full bg-gray-200 rounded-full h-2.5 mb-1'>
                        <div class='bg-blue-600 h-2.5 rounded-full' style='width: " . $percentageSpent . "%'></div>
                    </div>
                    <div class='text-right text-sm text-gray-500'>" . $percentageSpent . "% uitgegeven</div>
                </div>";
            
            // Resterende dagen
            $currentDate = new \DateTime();
            $endDate = new \DateTime(end($budgetStatus)['end_date']);
            $daysRemaining = $currentDate->diff($endDate)->days;
            $totalDays = (new \DateTime($budgetStatus[0]['start_date']))->diff($endDate)->days;
            $percentageDays = $totalDays > 0 ? min(100, round((($totalDays - $daysRemaining) / $totalDays) * 100)) : 0;
            
            echo "
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <h2 class='text-lg font-semibold mb-2'>Periode</h2>
                    <div class='flex justify-between items-center mb-2'>
                        <span class='text-gray-500'>Resterende dagen</span>
                        <span class='font-medium'>" . $daysRemaining . " / " . $totalDays . " dagen</span>
                    </div>
                    <div class='w-full bg-gray-200 rounded-full h-2.5 mb-1'>
                        <div class='bg-green-600 h-2.5 rounded-full' style='width: " . $percentageDays . "%'></div>
                    </div>
                    <div class='text-right text-sm text-gray-500'>" . $percentageDays . "% van de periode verstreken</div>
                </div>";
            
            // Beschikbare budget
            $remaining = $totalBudget - $totalSpent;
            $daily = $daysRemaining > 0 ? $remaining / $daysRemaining : 0;
            
            echo "
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <h2 class='text-lg font-semibold mb-2'>Beschikbaar budget</h2>
                    <div class='flex justify-between items-center mb-2'>
                        <span class='text-gray-500'>Resterend</span>
                        <span class='font-medium'>€" . number_format($remaining, 2, ',', '.') . "</span>
                    </div>
                    <div class='flex justify-between items-center'>
                        <span class='text-gray-500'>Per dag</span>
                        <span class='font-medium'>€" . number_format($daily, 2, ',', '.') . "</span>
                    </div>
                </div>";
            
            echo "</div>";
        }
        
        // Hoofdinhoud
        if (empty($budgets)) {
            echo "
                <div class='bg-white rounded-lg shadow-md p-8 text-center'>
                    <i class='material-icons text-gray-400 text-6xl mb-4'>account_balance_wallet</i>
                    <h2 class='text-xl font-semibold mb-2'>Geen budgetten</h2>
                    <p class='text-gray-500 mb-6'>
                        Je hebt nog geen budgetten ingesteld. Maak een budget aan om je uitgaven bij te houden.
                    </p>
                    <a href='/budgets/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                        <i class='material-icons mr-1 text-sm'>add</i> Nieuw budget
                    </a>
                </div>";
        } else {
            echo "<div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'>";
            
            foreach ($budgets as $budget) {
                $startDate = date('d-m-Y', strtotime($budget['start_date']));
                $endDate = date('d-m-Y', strtotime($budget['end_date']));
                
                // Zoek bijbehorende status
                $status = null;
                foreach ($budgetStatus as $stat) {
                    if ($stat['id'] == $budget['id']) {
                        $status = $stat;
                        break;
                    }
                }
                
                $spent = $status ? $status['spent'] : 0;
                $percentage = $budget['amount'] > 0 ? min(100, round(($spent / $budget['amount']) * 100)) : 0;
                
                // Bepaal kleur op basis van percentage
                $barColor = 'bg-green-500';
                if ($percentage >= 90) {
                    $barColor = 'bg-red-500';
                } elseif ($percentage >= 75) {
                    $barColor = 'bg-yellow-500';
                }
                
                echo "
                    <div class='bg-white rounded-lg shadow-md overflow-hidden'>
                        <div class='p-6'>
                            <div class='flex justify-between items-start mb-4'>
                                <h3 class='text-lg font-semibold'>" . htmlspecialchars($budget['category_name'] ?? 'Onbekende categorie') . "</h3>
                                <div class='flex space-x-2'>
                                    <a href='/budgets/edit?id=" . $budget['id'] . "' class='text-blue-600 hover:text-blue-800'>
                                        <i class='material-icons text-sm'>edit</i>
                                    </a>
                                    <a href='/budgets/delete?id=" . $budget['id'] . "' onclick='return confirm(\"Weet je zeker dat je dit budget wilt verwijderen?\")' class='text-red-600 hover:text-red-800'>
                                        <i class='material-icons text-sm'>delete</i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class='mb-4'>
                                <div class='flex justify-between items-center mb-1 text-sm'>
                                    <span class='text-gray-500'>Uitgegeven</span>
                                    <span>€" . number_format($spent, 2, ',', '.') . " / €" . number_format($budget['amount'], 2, ',', '.') . "</span>
                                </div>
                                <div class='w-full bg-gray-200 rounded-full h-2'>
                                    <div class='" . $barColor . " h-2 rounded-full' style='width: " . $percentage . "%'></div>
                                </div>
                                <div class='text-right text-xs text-gray-500 mt-1'>" . $percentage . "% uitgegeven</div>
                            </div>
                            
                            <div class='text-sm text-gray-500'>
                                <div class='flex justify-between mb-2'>
                                    <span>Periode:</span>
                                    <span>" . $startDate . " t/m " . $endDate . "</span>
                                </div>";
                
                if (!empty($budget['description'])) {
                    echo "
                                <div class='mb-2'>
                                    <span class='block mb-1'>Beschrijving:</span>
                                    <span class='text-gray-700'>" . nl2br(htmlspecialchars($budget['description'])) . "</span>
                                </div>";
                }
                
                echo "
                            </div>
                        </div>";
                
                echo "
                    </div>";
            }
            
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    private function renderBudgetForm($categories, $budget = null, $errors = [], $oldInput = []) {
        $isEdit = $budget !== null;
        $title = $isEdit ? 'Budget bewerken' : 'Nieuw budget';
        
        // Begin HTML output
        echo "<div class='max-w-4xl mx-auto'>";
        
        // Header met titel en terug-link
        echo "
            <div class='flex justify-between items-center mb-6'>
                <h1 class='text-2xl font-bold'>" . $title . "</h1>
                <a href='/budgets' class='text-blue-600 hover:text-blue-800'>
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
                echo "<li>" . $error . "</li>";
            }
            
            echo "</ul>";
            echo "</div>";
        }
        
        // Formulier
        echo "<form method='post' action='" . ($isEdit ? "/budgets/update?id=" . $budget['id'] : "/budgets/store") . "' class='space-y-6'>";
        
        // Categorie selectie
        $selectedCategoryId = $oldInput['category_id'] ?? ($isEdit ? $budget['category_id'] : '');
        echo "
            <div>
                <label for='category_id' class='block text-sm font-medium text-gray-700'>Categorie</label>
                <select id='category_id' name='category_id' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                    <option value=''>Selecteer een categorie</option>";
                    
        foreach ($categories as $category) {
            $selected = ($selectedCategoryId == $category['id']) ? 'selected' : '';
            $categoryName = htmlspecialchars($category['name']);
            echo "<option value='{$category['id']}' $selected>{$categoryName}</option>";
        }
                    
        echo "
                </select>
            </div>";
        
        // Bedrag
        $amount = htmlspecialchars($oldInput['amount'] ?? ($isEdit ? $budget['amount'] : ''));
        echo "
            <div>
                <label for='amount' class='block text-sm font-medium text-gray-700'>Bedrag</label>
                <div class='mt-1 relative rounded-md shadow-sm'>
                    <div class='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                        <span class='text-gray-500 sm:text-sm'>€</span>
                    </div>
                    <input type='number' id='amount' name='amount' min='0.01' step='0.01' value='" . $amount . "' required class='pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                </div>
            </div>";
        
        // Periode (start en einddatum)
        $startDate = $oldInput['start_date'] ?? ($isEdit ? $budget['start_date'] : date('Y-m-01')); // Eerste dag van de maand
        $endDate = $oldInput['end_date'] ?? ($isEdit ? $budget['end_date'] : date('Y-m-t')); // Laatste dag van de maand
        
        echo "
            <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                <div>
                    <label for='start_date' class='block text-sm font-medium text-gray-700'>Startdatum</label>
                    <input type='date' id='start_date' name='start_date' value='" . $startDate . "' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                </div>
                <div>
                    <label for='end_date' class='block text-sm font-medium text-gray-700'>Einddatum</label>
                    <input type='date' id='end_date' name='end_date' value='" . $endDate . "' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                </div>
            </div>";
        
        // Knoppen
        echo "
            <div class='flex justify-end space-x-3 pt-4'>
                <a href='/budgets' class='inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
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
        
        // JavaScript voor datumvalidatie
        echo "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            // Valideer einddatum als die verandert
            endDateInput.addEventListener('change', function() {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(this.value);
                
                if (endDate < startDate) {
                    alert('De einddatum moet na de startdatum liggen');
                    this.value = '';
                }
            });
            
            // Valideer ook de startdatum
            startDateInput.addEventListener('change', function() {
                const startDate = new Date(this.value);
                const endDate = new Date(endDateInput.value);
                
                if (endDateInput.value && endDate < startDate) {
                    alert('De startdatum moet voor de einddatum liggen');
                    this.value = '';
                }
            });
        });
        </script>";
        
        echo "</div>";
    }
}