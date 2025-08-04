<?php
/**
 * Demo data setup voor Render.com deployment
 */

require_once __DIR__ . '/autoload.php';

use App\Core\Database;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\SavingsGoal;

echo "=== RENDER DEMO DATA SETUP ===\n\n";

try {
    $db = Database::getInstance();
    echo "✓ Database verbinding succesvol.\n";
} catch (Exception $e) {
    die("❌ Database verbinding mislukt: " . $e->getMessage() . "\n");
}

// Demo gebruiker gegevens
$demoEmail = 'demo@example.com';
$demoUsername = 'demo';
$demoPassword = 'welkom123';

try {
    // Check if demo user already exists
    $existingUser = User::getByEmail($demoEmail);
    
    if ($existingUser) {
        echo "Demo gebruiker bestaat al, gebruik bestaande gebruiker.\n";
        $userId = $existingUser['id'];
    } else {
        // Create demo user
        $userId = User::create([
            'username' => $demoUsername,
            'email' => $demoEmail,
            'password' => password_hash($demoPassword, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        echo "✓ Demo gebruiker aangemaakt met ID: $userId\n";
    }

    // Create demo accounts
    $accountIds = [];
    $accounts = [
        ['account_type_id' => 1, 'name' => 'Betaalrekening', 'balance' => 2450.75, 'currency' => 'EUR'],
        ['account_type_id' => 2, 'name' => 'Spaarrekening', 'balance' => 12000.00, 'currency' => 'EUR'],
        ['account_type_id' => 3, 'name' => 'Contant', 'balance' => 125.50, 'currency' => 'EUR'],
        ['account_type_id' => 4, 'name' => 'Creditcard', 'balance' => -350.25, 'currency' => 'EUR']
    ];
    
    foreach ($accounts as $account) {
        $account['user_id'] = $userId;
        $accountIds[] = Account::create($account);
    }
    echo "✓ Demo rekeningen aangemaakt.\n";

    // Create demo categories
    $categoryIds = [];
    $categories = [
        // Expense categories
        ['name' => 'Boodschappen', 'type' => 'expense', 'color' => '#4CAF50'],
        ['name' => 'Wonen', 'type' => 'expense', 'color' => '#2196F3'],
        ['name' => 'Transport', 'type' => 'expense', 'color' => '#FF9800'],
        ['name' => 'Eten & Drinken', 'type' => 'expense', 'color' => '#F44336'],
        ['name' => 'Abonnementen', 'type' => 'expense', 'color' => '#9C27B0'],
        ['name' => 'Entertainment', 'type' => 'expense', 'color' => '#E91E63'],
        ['name' => 'Gezondheid', 'type' => 'expense', 'color' => '#00BCD4'],
        ['name' => 'Kleding', 'type' => 'expense', 'color' => '#3F51B5'],
        ['name' => 'Overige Uitgaven', 'type' => 'expense', 'color' => '#607D8B'],
        // Income categories
        ['name' => 'Salaris', 'type' => 'income', 'color' => '#4CAF50'],
        ['name' => 'Freelance', 'type' => 'income', 'color' => '#8BC34A'],
        ['name' => 'Giften', 'type' => 'income', 'color' => '#CDDC39'],
        ['name' => 'Overige Inkomsten', 'type' => 'income', 'color' => '#009688']
    ];
    
    foreach ($categories as $category) {
        $category['user_id'] = $userId;
        $categoryIds[$category['name']] = Category::create($category);
    }
    echo "✓ Demo categorieën aangemaakt.\n";

    // Create demo savings goals
    $goals = [
        [
            'name' => 'Vakantie',
            'target_amount' => 2500.00,
            'current_amount' => 750.00,
            'start_date' => date('Y-m-d'),
            'target_date' => date('Y-m-d', strtotime('+6 months')),
            'description' => 'Zomervakantie naar Italië',
            'icon' => 'beach_access',
            'color' => '#2196F3'
        ],
        [
            'name' => 'Nieuwe laptop',
            'target_amount' => 1200.00,
            'current_amount' => 900.00,
            'start_date' => date('Y-m-d'),
            'target_date' => date('Y-m-d', strtotime('+2 months')),
            'description' => 'Professionele laptop voor werk',
            'icon' => 'laptop',
            'color' => '#9C27B0'
        ]
    ];
    
    foreach ($goals as $goal) {
        $goal['user_id'] = $userId;
        SavingsGoal::create($goal);
    }
    echo "✓ Demo spaardoelen aangemaakt.\n";

    echo "\n✅ Demo data installatie voltooid!\n";
    echo "Inloggegevens:\n";
    echo "  E-mail:    $demoEmail\n";
    echo "  Wachtwoord: $demoPassword\n";

} catch (Exception $e) {
    echo "❌ Fout bij aanmaken demo data: " . $e->getMessage() . "\n";
}