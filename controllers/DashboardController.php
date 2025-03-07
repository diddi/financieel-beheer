<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\RecurringTransaction;

class DashboardController {
    public function index() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $user = Auth::user();
        
        // Haal accounts op
        $accounts = Account::getAllByUser($userId);
        
        // Haal recente transacties op
        $recentTransactions = Transaction::getAllByUser($userId, [
            'limit' => 5
        ]);
        
        // Bereken maandtotalen
        $currentMonth = date('Y-m-01');
        $nextMonth = date('Y-m-01', strtotime('+1 month'));
        
        $monthIncome = 0;
        $monthExpenses = 0;
        
        // Haal transacties van de huidige maand op
        $monthTransactions = Transaction::getAllByUser($userId, [
            'date_from' => $currentMonth,
            'date_to' => date('Y-m-t') // laatste dag van de maand
        ]);
        
        // Bereken totalen
        foreach ($monthTransactions as $transaction) {
            if ($transaction['type'] === 'income') {
                $monthIncome += $transaction['amount'];
            } elseif ($transaction['type'] === 'expense') {
                $monthExpenses += $transaction['amount'];
            }
        }
        
        // Totaal saldo van alle rekeningen
        $totalBalance = array_reduce($accounts, function($total, $account) {
            return $total + $account['balance'];
        }, 0);
        
        // Haal budgetstatus op voor weergave op dashboard
        $budgets = Budget::getBudgetStatus($userId);
        
        // Bereid gegevens voor de grafiek voor
        $chartData = $this->prepareChartData($userId);
        
        // Haal aankomende terugkerende transacties op
        if (class_exists('App\\Models\\RecurringTransaction') && method_exists('App\\Models\\RecurringTransaction', 'getAllByUser')) {
            try {
                $upcomingRecurring = RecurringTransaction::getAllByUser($userId, true);
                $upcomingRecurring = array_filter($upcomingRecurring, function($transaction) {
                    return strtotime($transaction['next_due_date']) <= strtotime('+7 days'); // Komende 7 dagen
                });
                // Beperk tot de eerste 5
                $upcomingRecurring = array_slice($upcomingRecurring, 0, 5);
            } catch (\Exception $e) {
                // Als er een fout optreedt, maak een lege array
                $upcomingRecurring = [];
            }
        } else {
            // Als de functionaliteit nog niet beschikbaar is, maak een lege array
            $upcomingRecurring = [];
        }
        
        // Maak dashboard
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Dashboard</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
            <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>";
    
    // Sluit de echo, voeg het navigatiecomponent toe
    include_once __DIR__ . '/../views/components/navigation.php';
    
    // Hervat de echo voor de rest van de HTML
    echo " <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <h1 class='text-2xl font-bold'>Dashboard</h1>
                    <div class='mt-4 md:mt-0'>
                        <a href='/transactions/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Nieuwe transactie
                        </a>
                    </div>
                </div>
                
                <div class='bg-white rounded-lg shadow-md p-6 mb-8'>
                    <h2 class='text-xl font-bold mb-4'>Totaal saldo</h2>
                    <div class='text-3xl font-bold " . ($totalBalance >= 0 ? 'text-green-600' : 'text-red-600') . "'>
                        €" . number_format($totalBalance, 2, ',', '.') . "
                    </div>
                </div>
                
                <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-8'>";
        
        // Toon rekeningen
        foreach ($accounts as $account) {
            $colorClass = $account['balance'] >= 0 ? 'border-green-500' : 'border-red-500';
            $textClass = $account['balance'] >= 0 ? 'text-green-600' : 'text-red-600';
            
            echo "
                <div class='bg-white rounded-lg shadow p-6 border-l-4 {$colorClass}'>
                    <h3 class='font-semibold text-lg'>" . htmlspecialchars($account['name']) . "</h3>
                    <div class='text-sm text-gray-500'>" . htmlspecialchars($account['type_name']) . "</div>
                    <div class='mt-2'>
                        <span class='text-2xl font-bold {$textClass}'>
                            €" . number_format($account['balance'], 2, ',', '.') . "
                        </span>
                    </div>
                </div>";
        }
        
        echo "  </div>
                
                <div class='bg-white rounded-lg shadow p-6 mb-8'>
                    <h2 class='text-xl font-bold mb-4'>Maandoverzicht</h2>
                    <div class='flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0 mb-4'>
                        <div>
                            <span class='font-semibold text-gray-600'>Inkomsten</span>
                            <div class='text-xl font-bold text-green-600'>€" . number_format($monthIncome, 2, ',', '.') . "</div>
                        </div>
                        <div>
                            <span class='font-semibold text-gray-600'>Uitgaven</span>
                            <div class='text-xl font-bold text-red-600'>€" . number_format($monthExpenses, 2, ',', '.') . "</div>
                        </div>
                        <div>
                            <span class='font-semibold text-gray-600'>Balans</span>
                            <div class='text-xl font-bold " . ($monthIncome - $monthExpenses >= 0 ? 'text-green-600' : 'text-red-600') . "'>
                                €" . number_format($monthIncome - $monthExpenses, 2, ',', '.') . "
                            </div>
                        </div>
                    </div>
                    <div class='w-full h-64'>
                        <canvas id='monthlyChart'></canvas>
                    </div>
                </div>";
                
        // Toon aankomende terugkerende transacties als die er zijn
        if (!empty($upcomingRecurring)) {
            echo "<div class='bg-white rounded-lg shadow p-6 mb-8'>
                    <div class='flex justify-between items-center mb-4'>
                        <h2 class='text-xl font-bold'>Aankomende terugkerende transacties</h2>
                        <a href='/recurring' class='text-blue-600 hover:underline text-sm'>Alle bekijken →</a>
                    </div>
                    
                    <div class='divide-y'>";
            foreach ($upcomingRecurring as $transaction) {
                echo "<div class='py-3 flex justify-between items-center'>
                        <div class='flex items-center'>
                            <div class='w-2 h-8 rounded-full mr-3' style='background-color: " . htmlspecialchars($transaction['color'] ?? '#9E9E9E') . "'></div>
                            <div>
                                <div class='font-medium'>" . htmlspecialchars($transaction['description']) . "</div>
                                <div class='text-sm text-gray-500'>
                                    " . htmlspecialchars($transaction['account_name']) . " • 
                                    " . date('d-m-Y', strtotime($transaction['next_due_date'])) . "
                                    ";
                    $today = date('Y-m-d');
                    $daysUntil = (strtotime($transaction['next_due_date']) - strtotime($today)) / (60 * 60 * 24);
                    if ($daysUntil <= 0) {
                        echo '<span class="ml-2 px-2 py-0.5 bg-red-100 text-red-800 rounded-full text-xs">Vandaag</span>';
                    } elseif ($daysUntil <= 3) {
                        echo '<span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded-full text-xs">Binnen ' . ceil($daysUntil) . ' dagen</span>';
                    }
                echo "                </div>
                            </div>
                        </div>
                        <div class='font-bold " . ($transaction['type'] === 'expense' ? 'text-red-600' : 'text-green-600') . "'>
                            " . ($transaction['type'] === 'expense' ? '-' : '+') . "€" . number_format($transaction['amount'], 2, ',', '.') . "
                        </div>
                    </div>";
            }
            echo "    </div>
                </div>";
        }
                
        // Toon budget voortgang
        if (!empty($budgets)) {
            echo "<div class='bg-white rounded-lg shadow p-6 mb-8'>
                    <div class='flex justify-between items-center mb-4'>
                        <h2 class='text-xl font-bold'>Budget voortgang</h2>
                        <a href='/budgets' class='text-blue-600 hover:underline text-sm'>Alle budgetten →</a>
                    </div>";
            
            foreach ($budgets as $budget) {
                $progressColor = 'bg-green-500';
                
                if ($budget['is_exceeded']) {
                    $progressColor = 'bg-red-500';
                } elseif ($budget['is_warning']) {
                    $progressColor = 'bg-yellow-500';
                }
                
                echo "<div class='mb-4'>
                        <div class='flex justify-between items-center mb-1'>
                            <span class='font-medium'>" . htmlspecialchars($budget['category_name']) . "</span>
                            <span class='text-sm'>
                                €" . number_format($budget['spent'], 2, ',', '.') . " / 
                                €" . number_format($budget['amount'], 2, ',', '.') . "
                            </span>
                        </div>
                        <div class='w-full bg-gray-200 rounded-full h-2.5'>
                            <div class='h-2.5 rounded-full {$progressColor}' style='width: " . min(100, $budget['percentage']) . "%'></div>
                        </div>
                    </div>";
            }
            
            echo "</div>";
        }
                
        echo "<div class='bg-white rounded-lg shadow p-6'>
                    <div class='flex justify-between items-center mb-4'>
                        <h2 class='text-xl font-bold'>Recente transacties</h2>
                        <a href='/transactions' class='text-blue-600 hover:underline text-sm'>Alle bekijken →</a>
                    </div>";
                    
        if (empty($recentTransactions)) {
            echo "<p class='text-gray-500 text-center py-4'>Geen recente transacties</p>";
        } else {
            echo "<div class='divide-y'>";
            
            foreach ($recentTransactions as $transaction) {
                $type = $transaction['type'];
                $amountClass = $type === 'expense' ? 'text-red-600' : 'text-green-600';
                $amountPrefix = $type === 'expense' ? '-' : '+';
                
                echo "
                <div class='py-3 flex justify-between items-center'>
                    <div class='flex items-center'>
                        <div class='w-2 h-8 rounded-full mr-3' style='background-color: " . htmlspecialchars($transaction['color'] ?? '#9E9E9E') . "'></div>
                        <div>
                            <div class='font-medium'>" . htmlspecialchars($transaction['description'] ?: ($transaction['category_name'] ?? 'Onbekende categorie')) . "</div>
                            <div class='text-sm text-gray-500'>" . htmlspecialchars($transaction['account_name']) . " • " . date('d-m-Y', strtotime($transaction['date'])) . "</div>
                        </div>
                    </div>
                    <div class='font-bold {$amountClass}'>
                        {$amountPrefix}€" . number_format($transaction['amount'], 2, ',', '.') . "
                    </div>
                </div>";
            }
            
            echo "</div>";
        }
                    
        echo "  </div>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Chart data
                    const chartData = " . json_encode($chartData) . ";
                    
                    // Monthly Chart
                    const ctx = document.getElementById('monthlyChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: chartData.labels,
                            datasets: [
                                {
                                    label: 'Inkomsten',
                                    data: chartData.income,
                                    backgroundColor: 'rgba(34, 197, 94, 0.5)',
                                    borderColor: 'rgb(34, 197, 94)',
                                    borderWidth: 1
                                },
                                {
                                    label: 'Uitgaven',
                                    data: chartData.expenses,
                                    backgroundColor: 'rgba(239, 68, 68, 0.5)',
                                    borderColor: 'rgb(239, 68, 68)',
                                    borderWidth: 1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '€' + value.toLocaleString('nl-NL');
                                        }
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            label += '€' + context.raw.toLocaleString('nl-NL', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            });
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
        </body>
        </html>
        ";
    }
    
    /**
     * Bereid gegevens voor de maandelijkse grafiek voor
     */
    private function prepareChartData($userId) {
        // Haal transacties op voor de laatste 6 maanden
        $startDate = date('Y-m-01', strtotime('-5 months'));
        $endDate = date('Y-m-t'); // laatste dag van de huidige maand
        
        $transactions = Transaction::getAllByUser($userId, [
            'date_from' => $startDate,
            'date_to' => $endDate
        ]);
        
        // Maak arrays voor elke maand
        $months = [];
        $current = new \DateTime($startDate);
        $last = new \DateTime($endDate);
        
        while ($current <= $last) {
            $yearMonth = $current->format('Y-m');
            $months[$yearMonth] = [
                'label' => $current->format('M Y'),
                'income' => 0,
                'expenses' => 0
            ];
            
            $current->modify('+1 month');
        }
        
        // Vul de data in
        foreach ($transactions as $transaction) {
            $yearMonth = substr($transaction['date'], 0, 7); // Format: YYYY-MM
            
            if (isset($months[$yearMonth])) {
                if ($transaction['type'] === 'income') {
                    $months[$yearMonth]['income'] += $transaction['amount'];
                } elseif ($transaction['type'] === 'expense') {
                    $months[$yearMonth]['expenses'] += $transaction['amount'];
                }
            }
        }
        
        // Maak arrays voor Chart.js
        $labels = [];
        $income = [];
        $expenses = [];
        
        foreach ($months as $yearMonth => $data) {
            $labels[] = $data['label'];
            $income[] = $data['income'];
            $expenses[] = $data['expenses'];
        }
        
        return [
            'labels' => $labels,
            'income' => $income,
            'expenses' => $expenses
        ];
    }
}