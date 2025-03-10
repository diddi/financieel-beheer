<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\RecurringTransaction;

class DashboardController extends Controller {
    public function index() {
        $userId = $this->requireLogin();
        
        // Haal accountgegevens op
        $accounts = Account::getAllByUser($userId);
        
        // Haal recente transacties op
        $recentTransactions = Transaction::getAllByUser($userId, [
            'limit' => 5,
            'order_by' => 'date DESC'
        ]);
        
        // Bereken maandelijkse inkomsten/uitgaven
        $firstDayOfMonth = date('Y-m-01');
        $lastDayOfMonth = date('Y-m-t');
        
        $monthTransactions = Transaction::getAllByUser($userId, [
            'date_from' => $firstDayOfMonth,
            'date_to' => $lastDayOfMonth
        ]);
        
        $monthIncome = 0;
        $monthExpenses = 0;
        
        foreach ($monthTransactions as $transaction) {
            if ($transaction['type'] === 'income') {
                $monthIncome += $transaction['amount'];
            } else {
                $monthExpenses += $transaction['amount'];
            }
        }
        
        // Haal budgetstatus op
        $budgetStatus = Budget::getBudgetStatus($userId);
        
        // Haal aankomende terugkerende transacties op
        $upcomingRecurring = RecurringTransaction::getUpcoming($userId, [
            'limit' => 5
        ]);
        
        // Bereken totaal saldo
        $totalBalance = 0;
        foreach ($accounts as $account) {
            $totalBalance += $account['balance'];
        }
        
        // Bereid grafiekdata voor
        $chartData = $this->prepareChartData($userId);
        
        // Start buffering voor de pagina
        $render = $this->startBuffering('Dashboard');
        
        // Begin HTML output
        echo "<div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6'>";
        
        // Welkomstbericht met datum
        echo "
            <div class='mb-6'>
                <h1 class='text-2xl font-bold text-gray-900'>Welkom bij Financieel Beheer</h1>
                <p class='text-gray-500'>" . date('l j F Y') . "</p>
            </div>";
        
        // Samenvatting cards
        echo "
            <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-8'>
                <!-- Totaal saldo -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='flex flex-col'>
                        <div class='text-sm font-medium text-gray-500 mb-1'>Totaal saldo</div>
                        <div class='text-2xl font-bold'>€" . number_format($totalBalance, 2, ',', '.') . "</div>
                        <div class='flex items-center mt-2'>
                            <span class='text-xs text-gray-500'>Verdeeld over " . count($accounts) . " rekeningen</span>
                        </div>
                    </div>
                </div>
                
                <!-- Inkomsten deze maand -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='flex flex-col'>
                        <div class='text-sm font-medium text-gray-500 mb-1'>Inkomsten deze maand</div>
                        <div class='text-2xl font-bold text-green-600'>€" . number_format($monthIncome, 2, ',', '.') . "</div>
                        <div class='flex items-center mt-2'>
                            <span class='text-xs text-gray-500'>" . date('F Y') . "</span>
                        </div>
                    </div>
                </div>
                
                <!-- Uitgaven deze maand -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='flex flex-col'>
                        <div class='text-sm font-medium text-gray-500 mb-1'>Uitgaven deze maand</div>
                        <div class='text-2xl font-bold text-red-600'>€" . number_format($monthExpenses, 2, ',', '.') . "</div>
                        <div class='flex items-center mt-2'>
                            <span class='text-xs text-gray-500'>" . date('F Y') . "</span>
                        </div>
                    </div>
                </div>
            </div>";
        
        // Grafiek en recente transacties
        echo "
            <div class='grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8'>
                <!-- Grafiek -->
                <div class='lg:col-span-2 bg-white rounded-lg shadow-md p-6'>
                    <h2 class='text-lg font-semibold mb-4'>Uitgaven per categorie</h2>
                    <div style='height: 250px; position: relative;'>
                        <canvas id='expenses-chart'></canvas>
                    </div>
                </div>
                
                <!-- Recente transacties -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='flex justify-between items-center mb-4'>
                        <h2 class='text-lg font-semibold'>Recente transacties</h2>
                        <a href='/transactions' class='text-blue-600 hover:text-blue-800 text-sm'>Alle transacties</a>
                    </div>";
        
        if (empty($recentTransactions)) {
            echo "<p class='text-gray-500 text-center py-4'>Geen recente transacties</p>";
        } else {
            echo "<div class='space-y-3'>";
            
            foreach ($recentTransactions as $transaction) {
                $typeClass = $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600';
                $amountPrefix = $transaction['type'] === 'income' ? '+' : '-';
                $amount = number_format(abs($transaction['amount']), 2, ',', '.');
                
                echo "
                    <div class='flex items-center justify-between p-2 hover:bg-gray-50 rounded'>
                        <div class='flex items-center'>
                            <div class='flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center' style='background-color: " . ($transaction['color'] ?? '#e5e7eb') . "20'>
                                <i class='material-icons text-sm' style='color: " . ($transaction['color'] ?? '#374151') . "'>" . ($transaction['category_icon'] ?? ($transaction['type'] === 'income' ? 'trending_up' : 'trending_down')) . "</i>
                            </div>
                            <div class='ml-3'>
                                <p class='text-sm font-medium text-gray-900'>" . htmlspecialchars($transaction['description']) . "</p>
                                <p class='text-xs text-gray-500'>" . date('d-m-Y', strtotime($transaction['date'])) . " • " . ($transaction['category_name'] ?? 'Geen categorie') . "</p>
                            </div>
                        </div>
                        <span class='text-sm font-medium {$typeClass}'>{$amountPrefix}€{$amount}</span>
                    </div>";
            }
            
            echo "</div>";
        }
        
        echo "
                </div>
            </div>";
        
        // Budget en aankomende transacties
        echo "
            <div class='grid grid-cols-1 lg:grid-cols-3 gap-6'>
                <!-- Budget overzicht -->
                <div class='lg:col-span-2 bg-white rounded-lg shadow-md p-6'>
                    <div class='flex justify-between items-center mb-4'>
                        <h2 class='text-lg font-semibold'>Budget overzicht</h2>
                        <a href='/budgets' class='text-blue-600 hover:text-blue-800 text-sm'>Alle budgetten</a>
                    </div>";
        
        if (empty($budgetStatus)) {
            echo "<p class='text-gray-500 text-center py-4'>Geen actieve budgetten</p>";
        } else {
            echo "<div class='space-y-4'>";
            
            foreach ($budgetStatus as $budget) {
                $percentage = ($budget['amount'] > 0) ? min(100, ($budget['spent'] / $budget['amount']) * 100) : 0;
                $barColor = $percentage > 90 ? 'bg-red-500' : ($percentage > 75 ? 'bg-yellow-500' : 'bg-green-500');
                
                echo "
                    <div>
                        <div class='flex justify-between items-center mb-1'>
                            <div class='flex items-center'>
                                <div class='flex-shrink-0 h-6 w-6 rounded-full flex items-center justify-center' style='background-color: " . ($budget['color'] ?? '#e5e7eb') . "20'>
                                    <i class='material-icons text-xs' style='color: " . ($budget['color'] ?? '#374151') . "'>" . ($budget['icon'] ?? 'attach_money') . "</i>
                                </div>
                                <span class='ml-2 text-sm font-medium text-gray-900'>" . htmlspecialchars($budget['category_name'] ?? 'Budget') . "</span>
                            </div>
                            <div class='text-sm text-gray-900'>
                                €" . number_format($budget['spent'], 2, ',', '.') . " / €" . number_format($budget['amount'], 2, ',', '.') . "
                            </div>
                        </div>
                        <div class='w-full h-2 bg-gray-200 rounded'>
                            <div class='{$barColor} h-2 rounded' style='width: {$percentage}%'></div>
                        </div>
                    </div>";
            }
            
            echo "</div>";
        }
        
        echo "
                </div>
                
                <!-- Aankomende terugkerende transacties -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='flex justify-between items-center mb-4'>
                        <h2 class='text-lg font-semibold'>Aankomende transacties</h2>
                        <a href='/recurring-transactions' class='text-blue-600 hover:text-blue-800 text-sm'>Alle terugkerende</a>
                    </div>";
        
        if (empty($upcomingRecurring)) {
            echo "<p class='text-gray-500 text-center py-4'>Geen aankomende transacties</p>";
        } else {
            echo "<div class='space-y-3'>";
            
            foreach ($upcomingRecurring as $recurring) {
                $typeClass = $recurring['type'] === 'income' ? 'text-green-600' : 'text-red-600';
                $amountPrefix = $recurring['type'] === 'income' ? '+' : '-';
                $amount = number_format(abs($recurring['amount']), 2, ',', '.');
                $daysUntil = $recurring['days_until_due'];
                $daysLabel = $daysUntil === 0 ? 'Vandaag' : ($daysUntil === 1 ? 'Morgen' : "Over {$daysUntil} dagen");
                
                echo "
                    <div class='flex items-center justify-between p-2 hover:bg-gray-50 rounded'>
                        <div class='flex items-center'>
                            <div class='flex-shrink-0 h-8 w-8 rounded-full flex items-center justify-center' style='background-color: " . ($recurring['color'] ?? '#e5e7eb') . "20'>
                                <i class='material-icons text-sm' style='color: " . ($recurring['color'] ?? '#374151') . "'>repeat</i>
                            </div>
                            <div class='ml-3'>
                                <p class='text-sm font-medium text-gray-900'>" . htmlspecialchars($recurring['description']) . "</p>
                                <p class='text-xs text-gray-500'>" . date('d-m-Y', strtotime($recurring['next_due_date'])) . " • {$daysLabel}</p>
                            </div>
                        </div>
                        <span class='text-sm font-medium {$typeClass}'>{$amountPrefix}€{$amount}</span>
                    </div>";
            }
            
            echo "</div>";
        }
        
        echo "
                </div>
            </div>";
        
        // Einde content div
        echo "</div>";
        
        // JavaScript voor de grafieken
        echo "
        <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Expenses chart
            const ctx = document.getElementById('expenses-chart').getContext('2d');
            
            const expensesChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: " . json_encode(array_column($chartData, 'label')) . ",
                    datasets: [{
                        data: " . json_encode(array_column($chartData, 'value')) . ",
                        backgroundColor: " . json_encode(array_column($chartData, 'color')) . ",
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        });
        </script>";
        
        // Render de pagina met de layout
        $render();
    }
    
    private function prepareChartData($userId) {
        // Haal uitgaven per categorie op voor de huidige maand
        $firstDayOfMonth = date('Y-m-01');
        $lastDayOfMonth = date('Y-m-t');
        
        $transactions = Transaction::getAllByUser($userId, [
            'date_from' => $firstDayOfMonth,
            'date_to' => $lastDayOfMonth,
            'type' => 'expense'
        ]);
        
        // Groepeer uitgaven per categorie
        $categories = [];
        $noCategory = ['id' => 0, 'name' => 'Geen categorie', 'color' => '#A9A9A9'];
        
        foreach ($transactions as $transaction) {
            $categoryId = $transaction['category_id'] ?? 0;
            $categoryName = $transaction['category_name'] ?? $noCategory['name'];
            $categoryColor = $transaction['color'] ?? $noCategory['color'];
            
            if (!isset($categories[$categoryId])) {
                $categories[$categoryId] = [
                    'id' => $categoryId,
                    'name' => $categoryName,
                    'color' => $categoryColor,
                    'total' => 0
                ];
            }
            
            $categories[$categoryId]['total'] += $transaction['amount'];
        }
        
        // Sorteer op totaalbedrag (hoogste eerst)
        usort($categories, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });
        
        // Beperk tot top 5 categorieën en voeg "overig" toe indien nodig
        $chartData = [];
        $otherTotal = 0;
        
        foreach ($categories as $index => $category) {
            if ($index < 5) {
                $chartData[] = [
                    'label' => $category['name'],
                    'value' => $category['total'],
                    'color' => $category['color']
                ];
            } else {
                $otherTotal += $category['total'];
            }
        }
        
        // Voeg "Overig" toe als er meer dan 5 categorieën zijn
        if ($otherTotal > 0) {
            $chartData[] = [
                'label' => 'Overig',
                'value' => $otherTotal,
                'color' => '#9CA3AF'
            ];
        }
        
        // Als er geen data is, toon een placeholder
        if (empty($chartData)) {
            $chartData[] = [
                'label' => 'Geen uitgaven',
                'value' => 1,
                'color' => '#E5E7EB'
            ];
        }
        
        return $chartData;
    }
}