<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;

class ReportController {
    
    public function index() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal filters op (standaard: huidige maand)
        $currentMonth = date('Y-m');
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        $accountId = isset($_GET['account_id']) ? $_GET['account_id'] : null;
        
        // Haal alle rekeningen op voor filtermogelijkheden
        $accounts = Account::getAllByUser($userId);
        
        // Haal categorieën op voor de grafiek
        $categories = Category::getAllByUser($userId);
        $expenseCategories = array_filter($categories, function($category) {
            return $category['type'] === 'expense';
        });
        $incomeCategories = array_filter($categories, function($category) {
            return $category['type'] === 'income';
        });
        
        // Data voor categoriegrafieken
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate
        ];
        
        if ($accountId) {
            $filters['account_id'] = $accountId;
        }
        
        // Haal transacties op voor de gekozen periode
        $transactions = Transaction::getAllByUser($userId, $filters);
        
        // Verwerk transactiedata voor grafieken
        $expensesPerCategory = [];
        $incomePerCategory = [];
        $timelineData = $this->prepareTimelineData($startDate, $endDate);
        
        foreach ($transactions as $transaction) {
            // Voor categoriegrafiek
            if ($transaction['type'] === 'expense' && isset($transaction['category_id'])) {
                $categoryId = $transaction['category_id'];
                $categoryName = $transaction['category_name'] ?? 'Onbekend';
                
                if (!isset($expensesPerCategory[$categoryId])) {
                    $expensesPerCategory[$categoryId] = [
                        'name' => $categoryName,
                        'amount' => 0,
                        'color' => $transaction['color'] ?? '#CCCCCC'
                    ];
                }
                
                $expensesPerCategory[$categoryId]['amount'] += $transaction['amount'];
            } elseif ($transaction['type'] === 'income' && isset($transaction['category_id'])) {
                $categoryId = $transaction['category_id'];
                $categoryName = $transaction['category_name'] ?? 'Onbekend';
                
                if (!isset($incomePerCategory[$categoryId])) {
                    $incomePerCategory[$categoryId] = [
                        'name' => $categoryName,
                        'amount' => 0,
                        'color' => $transaction['color'] ?? '#CCCCCC'
                    ];
                }
                
                $incomePerCategory[$categoryId]['amount'] += $transaction['amount'];
            }
            
            // Voor tijdlijn grafiek
            $transactionDate = substr($transaction['date'], 0, 10);
            $period = $this->getPeriodKeyForTimeline($transactionDate, $startDate, $endDate);
            
            if ($period) {
                if ($transaction['type'] === 'expense') {
                    $timelineData[$period]['expenses'] += $transaction['amount'];
                } elseif ($transaction['type'] === 'income') {
                    $timelineData[$period]['income'] += $transaction['amount'];
                }
            }
        }
        
        // Bereken totalen
        $totalExpenses = array_reduce($expensesPerCategory, function($total, $item) {
            return $total + $item['amount'];
        }, 0);
        
        $totalIncome = array_reduce($incomePerCategory, function($total, $item) {
            return $total + $item['amount'];
        }, 0);
        
        // Converteer tijdlijndata naar arrays voor Chart.js
        $timelineFormatted = [
            'labels' => array_keys($timelineData),
            'expenses' => array_map(function($item) { return $item['expenses']; }, $timelineData),
            'income' => array_map(function($item) { return $item['income']; }, $timelineData)
        ];
        
        // Bereken maandelijkse gemiddelden
        $periodDays = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
        $periodMonths = max(1, round($periodDays / 30, 1));
        
        $avgMonthlyExpense = $totalExpenses / $periodMonths;
        $avgMonthlyIncome = $totalIncome / $periodMonths;
        $avgMonthlySavings = $avgMonthlyIncome - $avgMonthlyExpense;
        
        // Geef de view weer
        $this->renderReportView([
            'expensesPerCategory' => array_values($expensesPerCategory),
            'incomePerCategory' => array_values($incomePerCategory),
            'timelineData' => $timelineFormatted,
            'totalExpenses' => $totalExpenses,
            'totalIncome' => $totalIncome,
            'avgMonthlyExpense' => $avgMonthlyExpense,
            'avgMonthlyIncome' => $avgMonthlyIncome,
            'avgMonthlySavings' => $avgMonthlySavings,
            'accounts' => $accounts,
            'selectedAccount' => $accountId,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
    
    /**
     * Bereid tijdlijndata voor op basis van start- en einddatum
     */
    private function prepareTimelineData($startDate, $endDate) {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $this->determineTimelineInterval($start, $end);
        
        $periods = [];
        
        // Maak verschillende soorten perioden afhankelijk van de tijdsduur
        if ($interval === 'day') {
            $period = new \DateInterval('P1D');
            $format = 'Y-m-d';
            $labelFormat = 'd M';
        } elseif ($interval === 'week') {
            $period = new \DateInterval('P7D');
            $format = 'Y-W';
            $labelFormat = 'W (M)';
        } else {
            $period = new \DateInterval('P1M');
            $format = 'Y-m';
            $labelFormat = 'M Y';
        }
        
        $current = clone $start;
        
        while ($current <= $end) {
            if ($interval === 'day') {
                $key = $current->format($format);
                $label = $current->format($labelFormat);
            } elseif ($interval === 'week') {
                $key = $current->format('Y-W');
                $weekNumber = $current->format('W');
                $month = $current->format('M');
                $label = "Week {$weekNumber} ({$month})";
            } else {
                $key = $current->format($format);
                $label = $current->format($labelFormat);
            }
            
            $periods[$label] = [
                'expenses' => 0,
                'income' => 0
            ];
            
            $current->add($period);
        }
        
        return $periods;
    }
    
    /**
     * Bepaal interval voor tijdlijnweergave op basis van datumbereik
     */
    private function determineTimelineInterval($start, $end) {
        $diff = $start->diff($end);
        $days = $diff->days;
        
        if ($days <= 31) {
            return 'day';
        } elseif ($days <= 90) {
            return 'week';
        } else {
            return 'month';
        }
    }
    
    /**
     * Bepaal de juiste periode-key voor een datum in de tijdlijn
     */
    private function getPeriodKeyForTimeline($date, $startDate, $endDate) {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $dateObj = new \DateTime($date);
        
        $interval = $this->determineTimelineInterval($start, $end);
        
        if ($interval === 'day') {
            return $dateObj->format('d M');
        } elseif ($interval === 'week') {
            $weekNumber = $dateObj->format('W');
            $month = $dateObj->format('M');
            return "Week {$weekNumber} ({$month})";
        } else {
            return $dateObj->format('M Y');
        }
    }
    
    /**
     * Genereer specifiek categorieoverzicht
     */
    public function categoryDetail() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $categoryId = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$categoryId) {
            header('Location: /reports');
            exit;
        }
        
        // Haal categorie op
        $category = Category::getById($categoryId, $userId);
        
        if (!$category) {
            header('Location: /reports');
            exit;
        }
        
        // Haal filters op
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01', strtotime('-6 months'));
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
        
        // Data voor maandelijks overzicht
        $monthlyData = $this->getCategoryMonthlyData($categoryId, $userId, $startDate, $endDate);
        
        // Haal recente transacties op voor deze categorie
        $transactions = Transaction::getAllByUser($userId, [
            'category_id' => $categoryId,
            'date_from' => $startDate,
            'date_to' => $endDate,
            'limit' => 50
        ]);
        
        // Bereken statistieken
        $totalAmount = array_reduce($monthlyData['amounts'], function($carry, $item) {
            return $carry + $item;
        }, 0);
        
        $avgMonthly = count($monthlyData['labels']) > 0 ? 
            $totalAmount / count($monthlyData['labels']) : 0;
        
        // Geef de view weer
        $this->renderCategoryDetailView([
            'category' => $category,
            'monthlyData' => $monthlyData,
            'transactions' => $transactions,
            'totalAmount' => $totalAmount,
            'avgMonthly' => $avgMonthly,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
    
    /**
     * Haal maandelijkse data op voor een categorie
     */
    private function getCategoryMonthlyData($categoryId, $userId, $startDate, $endDate) {
        $db = \App\Core\Database::getInstance();
        
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m') as month, 
                       SUM(amount) as total 
                FROM transactions 
                WHERE user_id = ? AND category_id = ? 
                  AND date BETWEEN ? AND ? 
                GROUP BY DATE_FORMAT(date, '%Y-%m') 
                ORDER BY month ASC";
        
        $results = $db->fetchAll($sql, [$userId, $categoryId, $startDate, $endDate]);
        
        // Maak arrays voor Chart.js
        $labels = [];
        $amounts = [];
        
        // Converteer databaseresultaten naar maandnamen en bedragen
        foreach ($results as $row) {
            $date = \DateTime::createFromFormat('Y-m', $row['month']);
            $labels[] = $date->format('M Y');
            $amounts[] = floatval($row['total']);
        }
        
        return [
            'labels' => $labels,
            'amounts' => $amounts
        ];
    }
    
    /**
     * Renderen van de rapportage-view
     */
    private function renderReportView($data) {
        extract($data);
        
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Rapportages</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
            <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>";
    
    // Sluit de echo, voeg het navigatiecomponent toe
    include_once __DIR__ . '/../views/components/navigation.php';
    
    // Hervat de echo voor de rest van de HTML
    echo "
            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <h1 class='text-2xl font-bold'>Financiële Rapportages</h1>
                </div>
                
                <!-- Filters -->
                <div class='bg-white rounded-lg shadow-md p-6 mb-6'>
                    <h2 class='text-lg font-semibold mb-4'>Filters</h2>
                    <form method='get' action='/reports'>
                        <div class='grid grid-cols-1 md:grid-cols-4 gap-4'>
                            <div>
                                <label for='start_date' class='block text-sm font-medium text-gray-700'>Begindatum</label>
                                <input type='date' id='start_date' name='start_date' value='{$startDate}' 
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                            </div>
                            <div>
                                <label for='end_date' class='block text-sm font-medium text-gray-700'>Einddatum</label>
                                <input type='date' id='end_date' name='end_date' value='{$endDate}' 
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                            </div>
                            <div>
                                <label for='account_id' class='block text-sm font-medium text-gray-700'>Rekening</label>
                                <select id='account_id' name='account_id' 
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                    <option value=''>Alle rekeningen</option>";
                                    
        foreach ($accounts as $account) {
            $selected = $selectedAccount == $account['id'] ? 'selected' : '';
            echo "<option value='{$account['id']}' {$selected}>" . htmlspecialchars($account['name']) . "</option>";
        }
                                    
        echo "              </select>
                            </div>
                            <div class='flex items-end'>
                                <button type='submit' class='w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                    Toepassen
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Overzicht -->
                <div class='bg-white rounded-lg shadow-md p-6 mb-6'>
                    <h2 class='text-lg font-semibold mb-4'>Financieel Overzicht</h2>
                    <div class='grid grid-cols-1 md:grid-cols-3 gap-6'>
                        <div class='bg-gray-100 p-4 rounded-lg'>
                            <div class='text-sm text-gray-500'>Totale Uitgaven</div>
                            <div class='text-2xl font-bold text-red-600'>€" . number_format($totalExpenses, 2, ',', '.') . "</div>
                            <div class='text-sm text-gray-500 mt-2'>Gem. Maandelijks</div>
                            <div class='text-lg font-semibold text-red-600'>€" . number_format($avgMonthlyExpense, 2, ',', '.') . "</div>
                        </div>
                        <div class='bg-gray-100 p-4 rounded-lg'>
                            <div class='text-sm text-gray-500'>Totale Inkomsten</div>
                            <div class='text-2xl font-bold text-green-600'>€" . number_format($totalIncome, 2, ',', '.') . "</div>
                            <div class='text-sm text-gray-500 mt-2'>Gem. Maandelijks</div>
                            <div class='text-lg font-semibold text-green-600'>€" . number_format($avgMonthlyIncome, 2, ',', '.') . "</div>
                        </div>
                        <div class='bg-gray-100 p-4 rounded-lg'>
                            <div class='text-sm text-gray-500'>Saldo</div>
                            <div class='text-2xl font-bold " . ($totalIncome - $totalExpenses >= 0 ? 'text-green-600' : 'text-red-600') . "'>
                                €" . number_format($totalIncome - $totalExpenses, 2, ',', '.') . "
                            </div>
                            <div class='text-sm text-gray-500 mt-2'>Gem. Maandelijkse Besparing</div>
                            <div class='text-lg font-semibold " . ($avgMonthlySavings >= 0 ? 'text-green-600' : 'text-red-600') . "'>
                                €" . number_format($avgMonthlySavings, 2, ',', '.') . "
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tijdlijn Grafiek -->
                <div class='bg-white rounded-lg shadow-md p-6 mb-6'>
                    <h2 class='text-lg font-semibold mb-4'>Inkomsten & Uitgaven over tijd</h2>
                    <div class='w-full h-80'>
                        <canvas id='timelineChart'></canvas>
                    </div>
                </div>
                
                <div class='grid grid-cols-1 md:grid-cols-2 gap-6 mb-6'>
                    <!-- Uitgaven per Categorie -->
                    <div class='bg-white rounded-lg shadow-md p-6'>
                        <h2 class='text-lg font-semibold mb-4'>Uitgaven per Categorie</h2>";
                        
        if (empty($expensesPerCategory)) {
            echo "<p class='text-gray-500 text-center py-4'>Geen uitgavendata beschikbaar</p>";
        } else {
            echo "<div class='w-full' style='height: 300px;'>
                        <canvas id='expensePieChart'></canvas>
                      </div>
                      <div class='mt-4'>
                        <table class='min-w-full divide-y divide-gray-200'>
                            <thead class='bg-gray-50'>
                                <tr>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Categorie</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Bedrag</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>%</th>
                                </tr>
                            </thead>
                            <tbody class='bg-white divide-y divide-gray-200'>";
                        
            foreach ($expensesPerCategory as $category) {
                $percentage = ($totalExpenses > 0) ? round(($category['amount'] / $totalExpenses) * 100, 1) : 0;
                echo "<tr>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='flex items-center'>
                                <div class='flex-shrink-0 h-4 w-4 rounded-full' style='background-color: " . htmlspecialchars($category['color']) . "'></div>
                                <div class='ml-4 text-sm font-medium text-gray-900'>" . htmlspecialchars($category['name']) . "</div>
                            </div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>€" . number_format($category['amount'], 2, ',', '.') . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . $percentage . "%</td>
                    </tr>";
            }
                        
            echo "      </tbody>
                        </table>
                      </div>";
        }
                        
        echo "    </div>
                    
                    <!-- Inkomsten per Categorie -->
                    <div class='bg-white rounded-lg shadow-md p-6'>
                        <h2 class='text-lg font-semibold mb-4'>Inkomsten per Categorie</h2>";
                        
        if (empty($incomePerCategory)) {
            echo "<p class='text-gray-500 text-center py-4'>Geen inkomstendata beschikbaar</p>";
        } else {
            echo "<div class='w-full' style='height: 300px;'>
                        <canvas id='incomePieChart'></canvas>
                      </div>
                      <div class='mt-4'>
                        <table class='min-w-full divide-y divide-gray-200'>
                            <thead class='bg-gray-50'>
                                <tr>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Categorie</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Bedrag</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>%</th>
                                </tr>
                            </thead>
                            <tbody class='bg-white divide-y divide-gray-200'>";
                        
            foreach ($incomePerCategory as $category) {
                $percentage = ($totalIncome > 0) ? round(($category['amount'] / $totalIncome) * 100, 1) : 0;
                echo "<tr>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='flex items-center'>
                                <div class='flex-shrink-0 h-4 w-4 rounded-full' style='background-color: " . htmlspecialchars($category['color']) . "'></div>
                                <div class='ml-4 text-sm font-medium text-gray-900'>" . htmlspecialchars($category['name']) . "</div>
                            </div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>€" . number_format($category['amount'], 2, ',', '.') . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . $percentage . "%</td>
                    </tr>";
            }
                        
            echo "      </tbody>
                        </table>
                      </div>";
        }
                        
        echo "    </div>
                </div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Tijdlijn Grafiek
                const timelineData = " . json_encode($timelineData) . ";
                const ctx = document.getElementById('timelineChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: timelineData.labels,
                        datasets: [
                            {
                                label: 'Inkomsten',
                                data: timelineData.income,
                                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                borderColor: 'rgb(16, 185, 129)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Uitgaven',
                                data: timelineData.expenses,
                                backgroundColor: 'rgba(239, 68, 68, 0.2)',
                                borderColor: 'rgb(239, 68, 68)',
                                borderWidth: 2,
                                tension: 0.3,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '€' + value.toLocaleString('nl-NL');
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Uitgaven Taartdiagram
                ";
                
        if (!empty($expensesPerCategory)) {
            echo "
                const expenseData = " . json_encode(array_map(function($item) {
                    return $item['amount'];
                }, $expensesPerCategory)) . ";
                const expenseLabels = " . json_encode(array_map(function($item) {
                    return $item['name'];
                }, $expensesPerCategory)) . ";
                const expenseColors = " . json_encode(array_map(function($item) {
                    return $item['color'];
                }, $expensesPerCategory)) . ";
                
                const expenseCtx = document.getElementById('expensePieChart').getContext('2d');
                new Chart(expenseCtx, {
                    type: 'doughnut',
                    data: {
                        labels: expenseLabels,
                        datasets: [{
                            data: expenseData,
                            backgroundColor: expenseColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += '€' + context.raw.toLocaleString('nl-NL', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                        label += ' (' + ((context.raw / " . $totalExpenses . ") * 100).toFixed(1) + '%)';
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });";
        }
                
        if (!empty($incomePerCategory)) {
            echo "
                // Inkomsten Taartdiagram
                const incomeData = " . json_encode(array_map(function($item) {
                    return $item['amount'];
                }, $incomePerCategory)) . ";
                const incomeLabels = " . json_encode(array_map(function($item) {
                    return $item['name'];
                }, $incomePerCategory)) . ";
                const incomeColors = " . json_encode(array_map(function($item) {
                    return $item['color'];
                }, $incomePerCategory)) . ";
                
                const incomeCtx = document.getElementById('incomePieChart').getContext('2d');
                new Chart(incomeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: incomeLabels,
                        datasets: [{
                            data: incomeData,
                            backgroundColor: incomeColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 15,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += '€' + context.raw.toLocaleString('nl-NL', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                        label += ' (' + ((context.raw / " . $totalIncome . ") * 100).toFixed(1) + '%)';
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });";
        }
                
        echo "
            });
            </script>
        </body>
        </html>
        ";
    }
    
    /**
     * Renderen van de categorie detail view
     */
    private function renderCategoryDetailView($data) {
        extract($data);
        
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Categorie Analyse</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
            <script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
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
                                <a href='/budgets' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Budgetten</a>
                                <a href='/reports' class='px-3 py-2 rounded-md text-sm font-medium bg-blue-700'>Rapportages</a>
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
                    <div class='flex items-center'>
                        <a href='/reports' class='mr-4 text-blue-600 hover:text-blue-800'>
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' viewBox='0 0 20 20' fill='currentColor'>
                                <path fill-rule='evenodd' d='M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 010 2H5.414l4.293 4.293a1 1 0 010 1.414z' clip-rule='evenodd' />
                            </svg>
                        </a>
                        <h1 class='text-2xl font-bold'>Categorie Analyse: " . htmlspecialchars($category['name']) . "</h1>
                    </div>
                    
                    <div class='flex items-center'>
                        <div class='w-6 h-6 rounded-full mr-2' style='background-color: " . htmlspecialchars($category['color']) . "'></div>
                        <span class='text-sm font-medium'>" . ($category['type'] === 'expense' ? 'Uitgaven' : 'Inkomsten') . "</span>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class='bg-white rounded-lg shadow-md p-6 mb-6'>
                    <h2 class='text-lg font-semibold mb-4'>Periode Selecteren</h2>
                    <form method='get' action='/reports/category'>
                        <input type='hidden' name='id' value='" . $category['id'] . "'>
                        <div class='grid grid-cols-1 md:grid-cols-3 gap-4'>
                            <div>
                                <label for='start_date' class='block text-sm font-medium text-gray-700'>Begindatum</label>
                                <input type='date' id='start_date' name='start_date' value='{$startDate}' 
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                            </div>
                            <div>
                                <label for='end_date' class='block text-sm font-medium text-gray-700'>Einddatum</label>
                                <input type='date' id='end_date' name='end_date' value='{$endDate}' 
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                            </div>
                            <div class='flex items-end'>
                                <button type='submit' class='w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                    Toepassen
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Overzicht -->
                <div class='bg-white rounded-lg shadow-md p-6 mb-6'>
                    <h2 class='text-lg font-semibold mb-4'>Statistieken</h2>
                    <div class='grid grid-cols-1 md:grid-cols-3 gap-6'>
                        <div class='bg-gray-100 p-4 rounded-lg'>
                            <div class='text-sm text-gray-500'>Totaal Bedrag</div>
                            <div class='text-2xl font-bold " . ($category['type'] === 'expense' ? 'text-red-600' : 'text-green-600') . "'>
                                €" . number_format($totalAmount, 2, ',', '.') . "
                            </div>
                        </div>
                        <div class='bg-gray-100 p-4 rounded-lg'>
                            <div class='text-sm text-gray-500'>Gemiddeld per Maand</div>
                            <div class='text-2xl font-bold " . ($category['type'] === 'expense' ? 'text-red-600' : 'text-green-600') . "'>
                                €" . number_format($avgMonthly, 2, ',', '.') . "
                            </div>
                        </div>
                        <div class='bg-gray-100 p-4 rounded-lg'>
                            <div class='text-sm text-gray-500'>Aantal Transacties</div>
                            <div class='text-2xl font-bold text-blue-600'>" . count($transactions) . "</div>
                        </div>
                    </div>
                </div>
                
                <!-- Maandelijks Overzicht -->
                <div class='bg-white rounded-lg shadow-md p-6 mb-6'>
                    <h2 class='text-lg font-semibold mb-4'>Maandelijks Overzicht</h2>
                    <div class='w-full h-80'>
                        <canvas id='monthlyChart'></canvas>
                    </div>
                </div>
                
                <!-- Recente Transacties -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <h2 class='text-lg font-semibold mb-4'>Recente Transacties</h2>";
                    
        if (empty($transactions)) {
            echo "<p class='text-gray-500 text-center py-4'>Geen transacties gevonden</p>";
        } else {
            echo "<div class='overflow-x-auto'>
                    <table class='min-w-full divide-y divide-gray-200'>
                        <thead class='bg-gray-50'>
                            <tr>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Datum</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Beschrijving</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Rekening</th>
                                <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>Bedrag</th>
                            </tr>
                        </thead>
                        <tbody class='bg-white divide-y divide-gray-200'>";
            
            foreach ($transactions as $transaction) {
                echo "<tr>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . date('d-m-Y', strtotime($transaction['date'])) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($transaction['description'] ?: '-') . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($transaction['account_name']) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-right " . ($category['type'] === 'expense' ? 'text-red-600' : 'text-green-600') . "'>
                            €" . number_format($transaction['amount'], 2, ',', '.') . "
                        </td>
                    </tr>";
            }
            
            echo "      </tbody>
                    </table>
                </div>";
        }
        
        echo "    </div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Maandelijks Overzicht Grafiek
                const monthlyData = " . json_encode($monthlyData) . ";
                const chartColor = '" . ($category['type'] === 'expense' ? 'rgb(239, 68, 68)' : 'rgb(16, 185, 129)') . "';
                const chartBgColor = '" . ($category['type'] === 'expense' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(16, 185, 129, 0.2)') . "';
                
                const ctx = document.getElementById('monthlyChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: monthlyData.labels,
                        datasets: [{
                            label: '" . ($category['type'] === 'expense' ? 'Uitgaven' : 'Inkomsten') . "',
                            data: monthlyData.amounts,
                            backgroundColor: chartBgColor,
                            borderColor: chartColor,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '€' + value.toLocaleString('nl-NL');
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
}
