<?php
// Laad autoloader en klassen
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Account.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Transaction.php';
require_once __DIR__ . '/models/Budget.php';
require_once __DIR__ . '/models/RecurringTransaction.php';
require_once __DIR__ . '/models/SavingsGoal.php';
require_once __DIR__ . '/models/Notification.php';

use App\Core\Database;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\RecurringTransaction;
use App\Models\SavingsGoal;

echo "Start het aanmaken van demo-data...\n";

// Verbind met de database
$db = Database::getInstance();

// Controleer of er al een demo-gebruiker bestaat
$existingUser = $db->fetch("SELECT * FROM users WHERE username = ?", ['demo']);
if ($existingUser) {
    echo "Er bestaat al een demo-gebruiker. Deze wordt verwijderd en opnieuw aangemaakt.\n";
    
    // Verwijder alle gerelateerde gegevens
    $userId = $existingUser['id'];
    $db->query("DELETE FROM transactions WHERE user_id = ?", [$userId]);
    $db->query("DELETE FROM recurring_transactions WHERE user_id = ?", [$userId]);
    $db->query("DELETE FROM accounts WHERE user_id = ?", [$userId]);
    $db->query("DELETE FROM categories WHERE user_id = ?", [$userId]);
    $db->query("DELETE FROM budgets WHERE user_id = ?", [$userId]);
    $db->query("DELETE FROM savings_goals WHERE user_id = ?", [$userId]);
    $db->query("DELETE FROM savings_transactions WHERE user_id = ?", [$userId]);
    $db->query("DELETE FROM notifications WHERE user_id = ?", [$userId]);
    $db->query("DELETE FROM users WHERE id = ?", [$userId]);
}

// Maak demo-gebruiker
$userData = [
    'username' => 'demo',
    'email' => 'demo@example.com',
    'password' => password_hash('demo', PASSWORD_DEFAULT)
];

$userId = $db->insert('users', $userData);
echo "Demo-gebruiker aangemaakt (ID: $userId)\n";

// Maak rekeningen aan
$accountTypes = $db->fetchAll("SELECT * FROM account_types");
$accountTypeMap = [];
foreach ($accountTypes as $type) {
    $accountTypeMap[$type['name']] = $type['id'];
}

// Bankrekening
$accountId1 = $db->insert('accounts', [
    'user_id' => $userId,
    'account_type_id' => $accountTypeMap['Bankrekening'],
    'name' => 'Betaalrekening',
    'balance' => 2463.78,
    'currency' => 'EUR'
]);

// Spaarrekening
$accountId2 = $db->insert('accounts', [
    'user_id' => $userId,
    'account_type_id' => $accountTypeMap['Spaarrekening'],
    'name' => 'Spaarrekening',
    'balance' => 12875.50,
    'currency' => 'EUR'
]);

// Creditcard
$accountId3 = $db->insert('accounts', [
    'user_id' => $userId,
    'account_type_id' => $accountTypeMap['Kredietkaart'],
    'name' => 'Creditcard',
    'balance' => -456.87,
    'currency' => 'EUR'
]);

echo "Rekeningen aangemaakt\n";

// Maak uitgavencategorieën aan
$expenseCategories = [
    ['name' => 'Boodschappen', 'color' => '#4CAF50', 'budget' => 400],
    ['name' => 'Restaurants', 'color' => '#FF9800', 'budget' => 200],
    ['name' => 'Transport', 'color' => '#2196F3', 'budget' => 150],
    ['name' => 'Huisvesting', 'color' => '#9C27B0', 'budget' => 1200],
    ['name' => 'Nutsvoorzieningen', 'color' => '#F44336', 'budget' => 180],
    ['name' => 'Entertainment', 'color' => '#E91E63', 'budget' => 100],
    ['name' => 'Gezondheidszorg', 'color' => '#00BCD4', 'budget' => 75],
    ['name' => 'Kleding', 'color' => '#FFEB3B', 'budget' => 120],
    ['name' => 'Persoonlijke verzorging', 'color' => '#FF5722', 'budget' => 60],
];

$expenseCategoryIds = [];
foreach ($expenseCategories as $category) {
    $catId = $db->insert('categories', [
        'user_id' => $userId,
        'name' => $category['name'],
        'type' => 'expense',
        'color' => $category['color']
    ]);
    $expenseCategoryIds[$category['name']] = $catId;
    
    // Maak budget aan voor deze categorie
    $db->insert('budgets', [
        'user_id' => $userId,
        'category_id' => $catId,
        'amount' => $category['budget'],
        'period' => 'monthly',
        'start_date' => date('Y-m-01'),
        'is_active' => 1,
        'alert_threshold' => 80
    ]);
}

// Maak inkomstencategorieën aan
$incomeCategories = [
    ['name' => 'Salaris', 'color' => '#4CAF50'],
    ['name' => 'Freelance', 'color' => '#2196F3'],
    ['name' => 'Rente', 'color' => '#9C27B0'],
    ['name' => 'Terugbetaling', 'color' => '#FF9800'],
];

$incomeCategoryIds = [];
foreach ($incomeCategories as $category) {
    $catId = $db->insert('categories', [
        'user_id' => $userId,
        'name' => $category['name'],
        'type' => 'income',
        'color' => $category['color']
    ]);
    $incomeCategoryIds[$category['name']] = $catId;
}

echo "Categorieën en budgetten aangemaakt\n";

// Maak transacties aan voor de laatste 3 maanden
$startDate = new DateTime(date('Y-m-d', strtotime('-3 months')));
$endDate = new DateTime();
$interval = new DateInterval('P1D');
$dateRange = new DatePeriod($startDate, $interval, $endDate);

// Voorbeeldtransacties
$transactionTemplates = [
    // Uitgaven
    ['description' => 'Albert Heijn', 'category' => 'Boodschappen', 'minAmount' => 10, 'maxAmount' => 80, 'frequency' => 0.3, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Jumbo', 'category' => 'Boodschappen', 'minAmount' => 15, 'maxAmount' => 65, 'frequency' => 0.2, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Lidl', 'category' => 'Boodschappen', 'minAmount' => 20, 'maxAmount' => 60, 'frequency' => 0.15, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Restaurant Bella Italia', 'category' => 'Restaurants', 'minAmount' => 35, 'maxAmount' => 85, 'frequency' => 0.1, 'type' => 'expense', 'account' => $accountId3],
    ['description' => 'Subway', 'category' => 'Restaurants', 'minAmount' => 8, 'maxAmount' => 15, 'frequency' => 0.15, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'NS Treinkaartje', 'category' => 'Transport', 'minAmount' => 12, 'maxAmount' => 35, 'frequency' => 0.15, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Tankbeurt', 'category' => 'Transport', 'minAmount' => 50, 'maxAmount' => 75, 'frequency' => 0.1, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Netflix', 'category' => 'Entertainment', 'minAmount' => 11.99, 'maxAmount' => 11.99, 'frequency' => 0.03, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Spotify', 'category' => 'Entertainment', 'minAmount' => 9.99, 'maxAmount' => 9.99, 'frequency' => 0.03, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Hypotheek', 'category' => 'Huisvesting', 'minAmount' => 980, 'maxAmount' => 980, 'frequency' => 0.03, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Energie', 'category' => 'Nutsvoorzieningen', 'minAmount' => 120, 'maxAmount' => 160, 'frequency' => 0.03, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Water', 'category' => 'Nutsvoorzieningen', 'minAmount' => 20, 'maxAmount' => 30, 'frequency' => 0.03, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Internet & TV', 'category' => 'Nutsvoorzieningen', 'minAmount' => 55, 'maxAmount' => 55, 'frequency' => 0.03, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'Apotheek', 'category' => 'Gezondheidszorg', 'minAmount' => 5, 'maxAmount' => 45, 'frequency' => 0.08, 'type' => 'expense', 'account' => $accountId1],
    ['description' => 'H&M', 'category' => 'Kleding', 'minAmount' => 20, 'maxAmount' => 80, 'frequency' => 0.08, 'type' => 'expense', 'account' => $accountId3],
    ['description' => 'Drogist', 'category' => 'Persoonlijke verzorging', 'minAmount' => 10, 'maxAmount' => 25, 'frequency' => 0.1, 'type' => 'expense', 'account' => $accountId1],
    
    // Inkomsten
    ['description' => 'Salaris', 'category' => 'Salaris', 'minAmount' => 2800, 'maxAmount' => 2800, 'frequency' => 0.03, 'type' => 'income', 'account' => $accountId1],
    ['description' => 'Freelance opdracht', 'category' => 'Freelance', 'minAmount' => 250, 'maxAmount' => 800, 'frequency' => 0.05, 'type' => 'income', 'account' => $accountId1],
    ['description' => 'Rente spaarrekening', 'category' => 'Rente', 'minAmount' => 12, 'maxAmount' => 12, 'frequency' => 0.03, 'type' => 'income', 'account' => $accountId2],
];

// Vaste terugkerende transacties
$recurringTransactions = [
    ['description' => 'Hypotheek', 'category' => 'Huisvesting', 'amount' => 980, 'type' => 'expense', 'account' => $accountId1, 'frequency' => 'monthly'],
    ['description' => 'Netflix', 'category' => 'Entertainment', 'amount' => 11.99, 'type' => 'expense', 'account' => $accountId1, 'frequency' => 'monthly'],
    ['description' => 'Spotify', 'category' => 'Entertainment', 'amount' => 9.99, 'type' => 'expense', 'account' => $accountId1, 'frequency' => 'monthly'],
    ['description' => 'Ziektekostenverzekering', 'category' => 'Gezondheidszorg', 'amount' => 125, 'type' => 'expense', 'account' => $accountId1, 'frequency' => 'monthly'],
    ['description' => 'Internet & TV', 'category' => 'Nutsvoorzieningen', 'amount' => 55, 'type' => 'expense', 'account' => $accountId1, 'frequency' => 'monthly'],
    ['description' => 'Energie', 'category' => 'Nutsvoorzieningen', 'amount' => 140, 'type' => 'expense', 'account' => $accountId1, 'frequency' => 'monthly'],
    ['description' => 'Salaris', 'category' => 'Salaris', 'amount' => 2800, 'type' => 'income', 'account' => $accountId1, 'frequency' => 'monthly'],
];

// Maak terugkerende transacties aan
foreach ($recurringTransactions as $rt) {
    $categoryId = ($rt['type'] === 'expense') 
        ? $expenseCategoryIds[$rt['category']] 
        : $incomeCategoryIds[$rt['category']];
    
    // Bereken volgende datum
    $nextDate = new DateTime();
    $nextDate->modify('+1 month');
    $nextDate->setDate($nextDate->format('Y'), $nextDate->format('m'), 1); // Eerste van de volgende maand
    
    $db->insert('recurring_transactions', [
        'user_id' => $userId,
        'account_id' => $rt['account'],
        'category_id' => $categoryId,
        'amount' => $rt['amount'],
        'type' => $rt['type'],
        'description' => $rt['description'],
        'frequency' => $rt['frequency'],
        'start_date' => date('Y-m-01'),
        'next_due_date' => $nextDate->format('Y-m-d'),
        'is_active' => 1
    ]);
}

// Genereer willekeurige transacties
$transactionCount = 0;
foreach ($dateRange as $date) {
    $currentDate = $date->format('Y-m-d');
    
    // Loop door transactiesjablonen
    foreach ($transactionTemplates as $template) {
        // Bepaal of deze transactie op deze datum wordt gegenereerd (op basis van frequentie)
        if (mt_rand(1, 100) / 100 <= $template['frequency']) {
            // Bereken bedrag
            $amount = mt_rand($template['minAmount'] * 100, $template['maxAmount'] * 100) / 100;
            
            // Bepaal categorie-ID
            $categoryId = null;
            if ($template['type'] === 'expense') {
                $categoryId = $expenseCategoryIds[$template['category']];
            } else {
                $categoryId = $incomeCategoryIds[$template['category']];
            }
            
            // Voeg transactie toe
            $db->insert('transactions', [
                'user_id' => $userId,
                'account_id' => $template['account'],
                'category_id' => $categoryId,
                'amount' => $amount,
                'type' => $template['type'],
                'description' => $template['description'],
                'date' => $currentDate
            ]);
            
            $transactionCount++;
        }
    }
}

echo "Transacties en terugkerende transacties aangemaakt ($transactionCount transacties)\n";

// Maak spaardoelen aan
$savingsGoals = [
    ['name' => 'Vakantie Italië', 'target_amount' => 1500, 'current_amount' => 850, 'target_date' => date('Y-m-d', strtotime('+6 months')), 'color' => '#2196F3'],
    ['name' => 'Nieuwe auto', 'target_amount' => 12000, 'current_amount' => 3500, 'target_date' => date('Y-m-d', strtotime('+2 years')), 'color' => '#FF5722'],
    ['name' => 'Noodfonds', 'target_amount' => 5000, 'current_amount' => 4200, 'target_date' => date('Y-m-d', strtotime('+8 months')), 'color' => '#4CAF50']
];

foreach ($savingsGoals as $goal) {
    $goalId = $db->insert('savings_goals', [
        'user_id' => $userId,
        'name' => $goal['name'],
        'target_amount' => $goal['target_amount'],
        'current_amount' => $goal['current_amount'],
        'start_date' => date('Y-m-d', strtotime('-6 months')),
        'target_date' => $goal['target_date'],
        'color' => $goal['color'],
        'is_completed' => 0
    ]);
    
    // Voeg enkele bijdragen toe
    $numberOfContributions = mt_rand(3, 8);
    $totalAmount = $goal['current_amount'];
    $amountPerContribution = $totalAmount / $numberOfContributions;
    
    for ($i = 0; $i < $numberOfContributions; $i++) {
        $contributionDate = date('Y-m-d', strtotime('-' . mt_rand(1, 180) . ' days'));
        $contributionAmount = round($amountPerContribution * (mt_rand(80, 120) / 100), 2);
        
        $db->insert('savings_transactions', [
            'user_id' => $userId,
            'savings_goal_id' => $goalId,
            'amount' => $contributionAmount,
            'date' => $contributionDate,
            'note' => 'Bijdrage aan ' . $goal['name']
        ]);
    }
}

echo "Spaardoelen en bijdragen aangemaakt\n";

// Maak enkele notificaties aan
$notifications = [
    ['title' => 'Budget bijna bereikt', 'message' => 'Je budget voor Boodschappen is voor 85% gebruikt deze maand.', 'type' => 'warning'],
    ['title' => 'Terugkerende transactie', 'message' => 'Morgen wordt je hypotheekbetaling automatisch uitgevoerd.', 'type' => 'info'],
    ['title' => 'Spaar mijlpaal', 'message' => 'Je hebt 70% van je doel voor "Vakantie Italië" bereikt!', 'type' => 'success'],
];

foreach ($notifications as $notification) {
    $db->insert('notifications', [
        'user_id' => $userId,
        'title' => $notification['title'],
        'message' => $notification['message'],
        'type' => $notification['type'],
        'is_read' => mt_rand(0, 1)
    ]);
}

echo "Notificaties aangemaakt\n";
echo "Demo-data succesvol aangemaakt! Je kunt nu inloggen met:\n";
echo "Gebruikersnaam: demo\n";
echo "Wachtwoord: demo\n";