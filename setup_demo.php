<?php
/**
 * Demo data setup script voor Financieel Beheer applicatie
 * 
 * Dit script maakt testdata aan voor een demo account:
 * - Gebruiker
 * - Rekeningen
 * - Categorieën
 * - Transacties
 * - Budgetten
 * - Spaardoelen
 * - Terugkerende transacties
 */

// Laad de geconsolideerde autoloader
require_once __DIR__ . '/autoload.php';

use App\Core\Database;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\RecurringTransaction;
use App\Models\SavingsGoal;
use App\Models\Notification;

echo "=== FINANCIEEL BEHEER DEMO DATA SETUP ===\n\n";

// Controleer of de database is ingesteld
try {
    $db = Database::getInstance();
    echo "✓ Database verbinding succesvol.\n";
} catch (Exception $e) {
    die("❌ Database verbinding mislukt. Voer eerst het setup.php script uit.\n" . $e->getMessage() . "\n");
}

// Variabelen voor de demo gebruiker
$demoEmail = 'demo@example.com';
$demoUsername = 'demo';
$demoPassword = 'welkom123';

// Verwijder bestaande demo gebruiker (indien aanwezig)
try {
    $existingUser = User::getByEmail($demoEmail);
    if ($existingUser) {
        echo "Bestaande demo gebruiker gevonden. Hergebruiken van gebruiker voor nieuwe testdata...\n";
        $userId = $existingUser['id'];
        
        // Verwijder eventuele bestaande testdata
        try {
            $db->query("DELETE FROM transactions WHERE user_id = ?", [$userId]);
            $db->query("DELETE FROM budgets WHERE user_id = ?", [$userId]);
            $db->query("DELETE FROM recurring_transactions WHERE user_id = ?", [$userId]);
            $db->query("DELETE FROM savings_goals WHERE user_id = ?", [$userId]);
            $db->query("DELETE FROM notifications WHERE user_id = ?", [$userId]);
            echo "✓ Bestaande testdata verwijderd voor gebruiker $demoUsername (ID: $userId)\n";
        } catch (Exception $e) {
            echo "⚠️ Waarschuwing bij verwijderen bestaande demo data: " . $e->getMessage() . "\n";
        }
    } else {
        // Maak een nieuwe demo gebruiker aan
        try {
            $userId = User::create([
                'username' => $demoUsername,
                'email' => $demoEmail,
                'password' => password_hash($demoPassword, PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            echo "✓ Demo gebruiker aangemaakt met ID: $userId\n";
        } catch (Exception $e) {
            die("❌ Fout bij aanmaken demo gebruiker: " . $e->getMessage() . "\n");
        }
    }
} catch (Exception $e) {
    echo "⚠️ Waarschuwing bij verwijderen bestaande demo data: {$e->getMessage()}\n";
}

// Maak test data aan
try {
    // Maak demo rekeningen
    $accountIds = createDemoAccounts($userId);
    echo "✓ Demo rekeningen aangemaakt.\n";
    
    // Maak demo categorieën
    $categoryIds = createDemoCategories($userId);
    echo "✓ Demo categorieën aangemaakt.\n";
    
    // Maak demo transacties
    $transactionsCount = createDemoTransactions($userId, $accountIds, $categoryIds);
    echo "✓ Demo transacties aangemaakt.\n";
    
    // Maak demo budgetten
    $budgetsCount = createDemoBudgets($userId, $categoryIds);
    echo "✓ Demo budgetten aangemaakt.\n";
    
    // Maak demo spaardoelen
    $savingsCount = createDemoSavings($userId, $accountIds);
    echo "✓ Demo spaardoelen aangemaakt.\n";
    
    // Maak demo terugkerende transacties
    $recurringCount = createDemoRecurringTransactions($userId, $accountIds, $categoryIds);
    echo "✓ Demo terugkerende transacties aangemaakt.\n";
    
    // Notificaties overslaan omdat Notification::create niet bestaat
    // $notificationsCount = createDemoNotifications($userId);
    // echo "✓ Demo notificaties aangemaakt.\n";
    
    echo "\n✓ Setup volledig voltooid met:\n";
    echo "  - $transactionsCount transacties\n";
    echo "  - $budgetsCount budgetten\n";
    echo "  - $savingsCount spaardoelen\n";
    echo "  - $recurringCount terugkerende transacties\n";
    
} catch (Exception $e) {
    echo "❌ Fout bij aanmaken demo data: " . $e->getMessage() . "\n";
}

echo "\n✅ Demo data installatie voltooid!\n";
echo "Inloggegevens:\n";
echo "  E-mail:    $demoEmail\n";
echo "  Wachtwoord: $demoPassword\n";

/**
 * Maak demo rekeningen aan
 */
function createDemoAccounts($userId) {
    $accountIds = [];
    
    // Standaard rekeningen
    $accounts = [
        [
            'account_type_id' => 1, // Bankrekening
            'name' => 'Betaalrekening',
            'balance' => 2450.75,
            'currency' => 'EUR'
        ],
        [
            'account_type_id' => 2, // Spaarrekening
            'name' => 'Spaarrekening',
            'balance' => 12000.00,
            'currency' => 'EUR'
        ],
        [
            'account_type_id' => 3, // Contant
            'name' => 'Contant',
            'balance' => 125.50,
            'currency' => 'EUR'
        ],
        [
            'account_type_id' => 4, // Kredietkaart
            'name' => 'Creditcard',
            'balance' => -350.25,
            'currency' => 'EUR'
        ]
    ];
    
    // Voeg rekeningen toe
    foreach ($accounts as $account) {
        $account['user_id'] = $userId;
        $accountIds[] = Account::create($account);
    }
    
    return $accountIds;
}

/**
 * Maak demo categorieën aan
 */
function createDemoCategories($userId) {
    $categoryIds = [];
    
    // Uitgave categorieën
    $expenseCategories = [
        ['name' => 'Boodschappen', 'type' => 'expense', 'color' => '#4CAF50'],
        ['name' => 'Wonen', 'type' => 'expense', 'color' => '#2196F3'],
        ['name' => 'Transport', 'type' => 'expense', 'color' => '#FF9800'],
        ['name' => 'Eten & Drinken', 'type' => 'expense', 'color' => '#F44336'],
        ['name' => 'Abonnementen', 'type' => 'expense', 'color' => '#9C27B0'],
        ['name' => 'Entertainment', 'type' => 'expense', 'color' => '#E91E63'],
        ['name' => 'Gezondheid', 'type' => 'expense', 'color' => '#00BCD4'],
        ['name' => 'Kleding', 'type' => 'expense', 'color' => '#3F51B5'],
        ['name' => 'Overige Uitgaven', 'type' => 'expense', 'color' => '#607D8B']
    ];
    
    // Inkomsten categorieën
    $incomeCategories = [
        ['name' => 'Salaris', 'type' => 'income', 'color' => '#4CAF50'],
        ['name' => 'Freelance', 'type' => 'income', 'color' => '#8BC34A'],
        ['name' => 'Giften', 'type' => 'income', 'color' => '#CDDC39'],
        ['name' => 'Overige Inkomsten', 'type' => 'income', 'color' => '#009688']
    ];
    
    // Voeg categorieën toe
    foreach (array_merge($expenseCategories, $incomeCategories) as $category) {
        $category['user_id'] = $userId;
        $categoryIds[$category['name']] = Category::create($category);
    }
    
    return $categoryIds;
}

/**
 * Maak demo transacties aan
 */
function createDemoTransactions($userId, $accountIds, $categoryIds) {
    $mainAccountId = $accountIds[0]; // Betaalrekening
    $creditCardId = $accountIds[3];  // Creditcard
    
    // Begin- en einddatum voor transacties (afgelopen 3 maanden)
    $startDate = new DateTime('-3 months');
    $endDate = new DateTime('now');
    
    // Willekeurige transacties
    $transactions = [];
    
    // Vaste inkomsten (salaris)
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        // Rond salaris af op de 25e van elke maand
        $salaryDate = clone $currentDate;
        $salaryDate->setDate(
            $salaryDate->format('Y'), 
            $salaryDate->format('m'), 
            25
        );
        
        if ($salaryDate <= $endDate && $salaryDate >= $startDate) {
            $transactions[] = [
                'account_id' => $mainAccountId,
                'category_id' => $categoryIds['Salaris'],
                'amount' => 2800.00,
                'type' => 'income',
                'description' => 'Salaris',
                'date' => $salaryDate->format('Y-m-d')
            ];
        }
        
        $currentDate->modify('+1 month');
    }
    
    // Vaste uitgaven
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        // Huur op de 1e
        $rentDate = clone $currentDate;
        $rentDate->setDate(
            $rentDate->format('Y'), 
            $rentDate->format('m'), 
            1
        );
        
        if ($rentDate <= $endDate && $rentDate >= $startDate) {
            $transactions[] = [
                'account_id' => $mainAccountId,
                'category_id' => $categoryIds['Wonen'],
                'amount' => 1200.00,
                'type' => 'expense',
                'description' => 'Huur',
                'date' => $rentDate->format('Y-m-d')
            ];
        }
        
        // Energie op de 5e
        $energyDate = clone $currentDate;
        $energyDate->setDate(
            $energyDate->format('Y'), 
            $energyDate->format('m'), 
            5
        );
        
        if ($energyDate <= $endDate && $energyDate >= $startDate) {
            $transactions[] = [
                'account_id' => $mainAccountId,
                'category_id' => $categoryIds['Wonen'],
                'amount' => 120.00,
                'type' => 'expense',
                'description' => 'Energie',
                'date' => $energyDate->format('Y-m-d')
            ];
        }
        
        // Internet op de 10e
        $internetDate = clone $currentDate;
        $internetDate->setDate(
            $internetDate->format('Y'), 
            $internetDate->format('m'), 
            10
        );
        
        if ($internetDate <= $endDate && $internetDate >= $startDate) {
            $transactions[] = [
                'account_id' => $mainAccountId,
                'category_id' => $categoryIds['Abonnementen'],
                'amount' => 45.00,
                'type' => 'expense',
                'description' => 'Internet',
                'date' => $internetDate->format('Y-m-d')
            ];
        }
        
        // Mobiel op de 15e
        $mobileDate = clone $currentDate;
        $mobileDate->setDate(
            $mobileDate->format('Y'), 
            $mobileDate->format('m'), 
            15
        );
        
        if ($mobileDate <= $endDate && $mobileDate >= $startDate) {
            $transactions[] = [
                'account_id' => $mainAccountId,
                'category_id' => $categoryIds['Abonnementen'],
                'amount' => 25.00,
                'type' => 'expense',
                'description' => 'Mobiele telefoon',
                'date' => $mobileDate->format('Y-m-d')
            ];
        }
        
        $currentDate->modify('+1 month');
    }
    
    // Willekeurige boodschappen transacties (2-3 per week)
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        // 2-3 keer per week boodschappen
        $numGroceries = rand(2, 3);
        
        for ($i = 0; $i < $numGroceries; $i++) {
            $groceryAmount = rand(2500, 10000) / 100; // 25-100 euro
            $daysToAdd = rand(0, 6); // Een willekeurige dag in de week
            $groceryDate = clone $currentDate;
            $groceryDate->modify("+$daysToAdd days");
            
            if ($groceryDate <= $endDate) {
                $transactions[] = [
                    'account_id' => $mainAccountId,
                    'category_id' => $categoryIds['Boodschappen'],
                    'amount' => $groceryAmount,
                    'type' => 'expense',
                    'description' => 'Supermarkt',
                    'date' => $groceryDate->format('Y-m-d')
                ];
            }
        }
        
        $currentDate->modify('+1 week');
    }
    
    // Restaurant bezoeken (1-2 per maand)
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        $numRestaurants = rand(1, 2);
        
        for ($i = 0; $i < $numRestaurants; $i++) {
            $restaurantAmount = rand(5000, 12000) / 100; // 50-120 euro
            $daysToAdd = rand(0, 30); // Een willekeurige dag in de maand
            $restaurantDate = clone $currentDate;
            $restaurantDate->modify("+$daysToAdd days");
            
            if ($restaurantDate <= $endDate) {
                $accountToUse = (rand(0, 1) == 0) ? $mainAccountId : $creditCardId; // 50% kans op creditcard
                
                $transactions[] = [
                    'account_id' => $accountToUse,
                    'category_id' => $categoryIds['Eten & Drinken'],
                    'amount' => $restaurantAmount,
                    'type' => 'expense',
                    'description' => 'Restaurant',
                    'date' => $restaurantDate->format('Y-m-d')
                ];
            }
        }
        
        $currentDate->modify('+1 month');
    }
    
    // Brandstof (elke 2 weken)
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        $fuelAmount = rand(6000, 9000) / 100; // 60-90 euro
        
        if ($currentDate <= $endDate) {
            $transactions[] = [
                'account_id' => $mainAccountId,
                'category_id' => $categoryIds['Transport'],
                'amount' => $fuelAmount,
                'type' => 'expense',
                'description' => 'Tankbeurt',
                'date' => $currentDate->format('Y-m-d')
            ];
        }
        
        $currentDate->modify('+14 days');
    }
    
    // Voeg transacties toe aan de database
    foreach ($transactions as $transaction) {
        $transaction['user_id'] = $userId;
        Transaction::create($transaction);
    }
    
    return count($transactions);
}

/**
 * Maak demo budgetten aan
 */
function createDemoBudgets($userId, $categoryIds) {
    // Huidige datum en begindatum van de maand
    $currentDate = date('Y-m-d');
    $startOfMonth = date('Y-m-01');
    $endOfMonth = date('Y-m-t');
    
    // Budgetten voor de huidige maand
    $budgets = [
        [
            'category_id' => $categoryIds['Boodschappen'],
            'amount' => 500.00,
            'period' => 'monthly',
            'start_date' => $startOfMonth,
            'end_date' => $endOfMonth,
            'is_active' => true,
            'alert_threshold' => 80
        ],
        [
            'category_id' => $categoryIds['Wonen'],
            'amount' => 1400.00,
            'period' => 'monthly',
            'start_date' => $startOfMonth,
            'end_date' => $endOfMonth,
            'is_active' => true,
            'alert_threshold' => 80
        ],
        [
            'category_id' => $categoryIds['Transport'],
            'amount' => 200.00,
            'period' => 'monthly',
            'start_date' => $startOfMonth,
            'end_date' => $endOfMonth,
            'is_active' => true,
            'alert_threshold' => 80
        ],
        [
            'category_id' => $categoryIds['Eten & Drinken'],
            'amount' => 300.00,
            'period' => 'monthly',
            'start_date' => $startOfMonth,
            'end_date' => $endOfMonth,
            'is_active' => true,
            'alert_threshold' => 80
        ],
        [
            'category_id' => $categoryIds['Abonnementen'],
            'amount' => 100.00,
            'period' => 'monthly',
            'start_date' => $startOfMonth,
            'end_date' => $endOfMonth,
            'is_active' => true,
            'alert_threshold' => 80
        ]
    ];
    
    // Voeg budgetten toe
    foreach ($budgets as $budget) {
        $budget['user_id'] = $userId;
        Budget::create($budget);
    }
    
    return count($budgets);
}

/**
 * Maak demo spaardoelen aan
 */
function createDemoSavings($userId, $accountIds) {
    // Spaardoelen
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
        ],
        [
            'name' => 'Noodfonds',
            'target_amount' => 5000.00,
            'current_amount' => 3500.00,
            'start_date' => date('Y-m-d'),
            'target_date' => date('Y-m-d', strtotime('+12 months')),
            'description' => 'Voor onverwachte uitgaven',
            'icon' => 'savings',
            'color' => '#4CAF50'
        ]
    ];
    
    // Voeg spaardoelen toe
    foreach ($goals as $goal) {
        $goal['user_id'] = $userId;
        SavingsGoal::create($goal);
    }
    
    return count($goals);
}

/**
 * Maak demo terugkerende transacties aan
 */
function createDemoRecurringTransactions($userId, $accountIds, $categoryIds) {
    $mainAccountId = $accountIds[0]; // Betaalrekening
    
    // Terugkerende transacties
    $recurringTransactions = [
        [
            'account_id' => $mainAccountId,
            'category_id' => $categoryIds['Salaris'],
            'amount' => 2800.00,
            'type' => 'income',
            'description' => 'Salaris',
            'frequency' => 'monthly',
            'start_date' => date('Y-m-d', strtotime('first day of this month')),
            'end_date' => null,
            'next_due_date' => date('Y-m-d', strtotime('25th day of this month')),
            'is_active' => 1
        ],
        [
            'account_id' => $mainAccountId,
            'category_id' => $categoryIds['Wonen'],
            'amount' => 1200.00,
            'type' => 'expense',
            'description' => 'Huur',
            'frequency' => 'monthly',
            'start_date' => date('Y-m-d', strtotime('first day of this month')),
            'end_date' => null,
            'next_due_date' => date('Y-m-d', strtotime('first day of next month')),
            'is_active' => 1
        ],
        [
            'account_id' => $mainAccountId,
            'category_id' => $categoryIds['Abonnementen'],
            'amount' => 14.99,
            'type' => 'expense',
            'description' => 'Netflix',
            'frequency' => 'monthly',
            'start_date' => date('Y-m-d', strtotime('-2 months')),
            'end_date' => null,
            'next_due_date' => date('Y-m-d', strtotime('15th day of this month')),
            'is_active' => 1
        ],
        [
            'account_id' => $mainAccountId,
            'category_id' => $categoryIds['Abonnementen'],
            'amount' => 9.99,
            'type' => 'expense',
            'description' => 'Spotify',
            'frequency' => 'monthly',
            'start_date' => date('Y-m-d', strtotime('-3 months')),
            'end_date' => null,
            'next_due_date' => date('Y-m-d', strtotime('20th day of this month')),
            'is_active' => 1
        ],
        [
            'account_id' => $mainAccountId,
            'category_id' => $categoryIds['Wonen'],
            'amount' => 120.00,
            'type' => 'expense',
            'description' => 'Energie',
            'frequency' => 'monthly',
            'start_date' => date('Y-m-d', strtotime('-2 months')),
            'end_date' => null,
            'next_due_date' => date('Y-m-d', strtotime('5th day of next month')),
            'is_active' => 1
        ]
    ];
    
    // Voeg terugkerende transacties toe
    foreach ($recurringTransactions as $transaction) {
        $transaction['user_id'] = $userId;
        RecurringTransaction::create($transaction);
    }
    
    return count($recurringTransactions);
}

/**
 * Maak demo notificaties aan
 */
function createDemoNotifications($userId) {
    $notifications = [
        [
            'user_id' => $userId,
            'title' => 'Budget overschreden',
            'message' => 'Je budget voor Eten & Drinken is overschreden met €25,50.',
            'type' => 'warning',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'user_id' => $userId,
            'title' => 'Nieuwe functie beschikbaar',
            'message' => 'Je kunt nu je wachtwoord wijzigen in je profiel.',
            'type' => 'info',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'user_id' => $userId,
            'title' => 'Aankomende betaling',
            'message' => 'Je hebt een aankomende betaling: Huur €1200,00 op ' . date('d-m-Y', strtotime('first day of next month')) . '.',
            'type' => 'reminder',
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 week'))
        ],
        [
            'user_id' => $userId,
            'title' => 'Spaardoel bijna bereikt',
            'message' => 'Je hebt 75% van je spaardoel "Nieuwe laptop" bereikt!',
            'type' => 'success',
            'is_read' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 weeks'))
        ]
    ];
    
    // Voeg notificaties toe
    foreach ($notifications as $notification) {
        Notification::create($notification);
    }
    
    return count($notifications);
} 