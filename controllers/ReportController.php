<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Account;

class ReportController extends Controller {
    
    /**
     * Toon rapportage pagina
     */
    public function index() {
        $userId = $this->requireLogin();
        
        // Verkrijg filters, standaard is huidige maand
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $period = $_GET['period'] ?? 'month';
        
        // Bepaal begin en einddatum op basis van periode
        if ($period === 'month') {
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            
            // Nederlandse maandnamen
            $maandNamen = [
                '01' => 'Januari', '02' => 'Februari', '03' => 'Maart', 
                '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                '07' => 'Juli', '08' => 'Augustus', '09' => 'September', 
                '10' => 'Oktober', '11' => 'November', '12' => 'December'
            ];
            
            $periodTitle = $maandNamen[$month] . ' ' . $year;
        } elseif ($period === 'year') {
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";
            $periodTitle = $year;
        } elseif ($period === 'quarter') {
            $quarter = ceil((int)$month / 3);
            $startMonth = (($quarter - 1) * 3) + 1;
            $endMonth = $quarter * 3;
            $startDate = "$year-" . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . "-01";
            $endDate = date('Y-m-t', strtotime("$year-" . str_pad($endMonth, 2, '0', STR_PAD_LEFT) . "-01"));
            $periodTitle = "Kwartaal $quarter $year";
        } else {
            // Custom periode
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-t');
            
            $startDateObj = \DateTime::createFromFormat('Y-m-d', $startDate);
            $endDateObj = \DateTime::createFromFormat('Y-m-d', $endDate);
            
            if ($startDateObj && $endDateObj) {
                // Nederlandse datumnotatie
                setlocale(LC_TIME, 'nl_NL');
                $periodTitle = $startDateObj->format('d') . ' ' . 
                               strftime('%B', $startDateObj->getTimestamp()) . ' ' . 
                               $startDateObj->format('Y') . ' - ' . 
                               $endDateObj->format('d') . ' ' . 
                               strftime('%B', $endDateObj->getTimestamp()) . ' ' . 
                               $endDateObj->format('Y');
            } else {
                $periodTitle = "Aangepaste periode";
            }
        }
        
        // Haal transacties op
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate
        ];
        $transactions = Transaction::getAllByUser($userId, $filters);
        
        // Debug informatie
        echo "<!-- Debug info: 
             Periode: $period
             Datum range: $startDate tot $endDate
             Aantal transacties gevonden: " . count($transactions) . "
             
             Details:";
             
        foreach($transactions as $index => $t) {
            echo "
             Transactie $index:
               - amount: " . ($t['amount'] ?? 'undefined') . "
               - category_id: " . ($t['category_id'] ?? 'undefined') . "
               - category_name: " . ($t['category_name'] ?? 'undefined') . "
               - category_type: " . ($t['category_type'] ?? 'undefined') . "
               - date: " . ($t['date'] ?? 'undefined');
        }
        
        echo " -->";
        
        // Haal rekeningen en categorieën op
        $accounts = Account::getAllByUser($userId);
        $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
        $incomeCategories = Category::getAllByUserAndType($userId, 'income');
        
        // Bereken totalen
        $totalIncome = 0;
        $totalExpense = 0;
        
        // Organiseer transacties per categorie
        $categoryTotals = [];
        $accountTotals = [];
        
        foreach ($transactions as $transaction) {
            $amount = $transaction['amount'];
            $categoryId = $transaction['category_id'];
            $accountId = $transaction['account_id'];
            
            // Bijwerken van categorie totalen
            if (!isset($categoryTotals[$categoryId])) {
                $categoryTotals[$categoryId] = [
                    'total' => 0,
                    'name' => $transaction['category_name'] ?? 'Onbekend',
                    'type' => $transaction['category_type'] ?? ($amount < 0 ? 'expense' : 'income')
                ];
            }
            $categoryTotals[$categoryId]['total'] += $amount;
            
            // Bijwerken van rekening totalen
            if (!isset($accountTotals[$accountId])) {
                $accountTotals[$accountId] = [
                    'total' => 0,
                    'name' => $transaction['account_name']
                ];
            }
            $accountTotals[$accountId]['total'] += $amount;
            
            // Bijwerken van inkomsten/uitgaven totalen
            if ($amount > 0) {
                $totalIncome += $amount;
            } else {
                $totalExpense += abs($amount);
            }
        }
        
        // Debug info voor categorieën
        echo "<!-- Debug categorie totalen:";
        foreach($categoryTotals as $catId => $cat) {
            echo "
             Categorie $catId:
               - name: " . ($cat['name'] ?? 'undefined') . "
               - type: " . ($cat['type'] ?? 'undefined') . "
               - total: " . ($cat['total'] ?? 'undefined');
        }
        echo " -->";
        
        // Begin met renderen van de pagina
        $render = $this->startBuffering('Rapportage');
        
        // Begin HTML output
        echo "<div class='max-w-7xl mx-auto'>";
        
        // Header sectie
        echo "
            <div class='mb-6'>
                <h1 class='text-2xl font-bold'>Financiële Rapportage</h1>
                <p class='text-gray-500 mt-1'>Financieel overzicht voor $periodTitle</p>
            </div>
            
            <!-- Periode kiezer -->
            <div class='bg-white rounded-lg shadow-md p-4 mb-6'>
                <form action='/reports' method='get' class='flex flex-wrap items-end gap-4'>
                    <div>
                        <label for='period' class='block text-sm font-medium text-gray-700 mb-1'>Periode</label>
                        <select id='period' name='period' class='block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border' onchange='toggleDateInputs()'>
                            <option value='month'" . ($period == 'month' ? ' selected' : '') . ">Maand</option>
                            <option value='quarter'" . ($period == 'quarter' ? ' selected' : '') . ">Kwartaal</option>
                            <option value='year'" . ($period == 'year' ? ' selected' : '') . ">Jaar</option>
                            <option value='custom'" . ($period == 'custom' ? ' selected' : '') . ">Aangepast</option>
                        </select>
                    </div>
                    
                    <div id='month-selector' class='" . ($period != 'month' && $period != 'quarter' ? 'hidden' : '') . "'>
                        <label for='month' class='block text-sm font-medium text-gray-700 mb-1'>Maand</label>
                        <select id='month' name='month' class='block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>";
                    
        // Nederlandse maandnamen voor de dropdown
        $maandNamen = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maart', 
            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Augustus', '09' => 'September', 
            '10' => 'Oktober', '11' => 'November', '12' => 'December'
        ];
        
        for ($i = 1; $i <= 12; $i++) {
            $monthValue = str_pad($i, 2, '0', STR_PAD_LEFT);
            $isSelected = ($month == $monthValue) ? ' selected' : '';
            echo "<option value='$monthValue'$isSelected>{$maandNamen[$monthValue]}</option>";
        }
                    
        echo "
                        </select>
                    </div>
                    
                    <div>
                        <label for='year' class='block text-sm font-medium text-gray-700 mb-1'>Jaar</label>
                        <select id='year' name='year' class='block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>";
                    
        $currentYear = date('Y');
        for ($y = $currentYear - 3; $y <= $currentYear + 1; $y++) {
            echo "<option value='$y'" . ($year == $y ? ' selected' : '') . ">$y</option>";
        }
                    
        echo "
                        </select>
                    </div>
                    
                    <div id='custom-dates' class='" . ($period != 'custom' ? 'hidden' : '') . " flex gap-2'>
                        <div>
                            <label for='start_date' class='block text-sm font-medium text-gray-700 mb-1'>Startdatum</label>
                            <input type='date' id='start_date' name='start_date' value='" . ($startDate ?? '') . "' class='block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                        </div>
                        
                        <div>
                            <label for='end_date' class='block text-sm font-medium text-gray-700 mb-1'>Einddatum</label>
                            <input type='date' id='end_date' name='end_date' value='" . ($endDate ?? '') . "' class='block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                        </div>
                    </div>
                    
                    <div>
                        <button type='submit' class='inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Toepassen
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Overzicht kaarten -->
            <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-6'>
                <!-- Inkomsten kaart -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='mb-2 flex items-center'>
                        <div class='p-2 rounded-full bg-green-100 mr-3'>
                            <i class='material-icons text-green-600'>trending_up</i>
                        </div>
                        <h2 class='text-lg font-semibold'>Inkomsten</h2>
                    </div>
                    <div class='mt-4'>
                        <span class='text-2xl font-bold text-green-600'>€" . number_format($totalIncome, 2, ',', '.') . "</span>
                    </div>
                </div>
                
                <!-- Uitgaven kaart -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='mb-2 flex items-center'>
                        <div class='p-2 rounded-full bg-red-100 mr-3'>
                            <i class='material-icons text-red-600'>trending_down</i>
                        </div>
                        <h2 class='text-lg font-semibold'>Uitgaven</h2>
                    </div>
                    <div class='mt-4'>
                        <span class='text-2xl font-bold text-red-600'>€" . number_format($totalExpense, 2, ',', '.') . "</span>
                    </div>
                </div>
                
                <!-- Balans kaart -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='mb-2 flex items-center'>
                        <div class='p-2 rounded-full bg-blue-100 mr-3'>
                            <i class='material-icons text-blue-600'>account_balance</i>
                        </div>
                        <h2 class='text-lg font-semibold'>Balans</h2>
                    </div>
                    <div class='mt-4'>";
        
        $balance = $totalIncome - $totalExpense;
        $textColor = $balance >= 0 ? 'text-green-600' : 'text-red-600';
        
        echo "
                        <span class='text-2xl font-bold $textColor'>€" . number_format($balance, 2, ',', '.') . "</span>
                    </div>
                </div>
            </div>
            
            <!-- Grafieken -->
            <div class='grid grid-cols-1 md:grid-cols-2 gap-6 mb-6'>
                <!-- Uitgaven per categorie -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <h2 class='text-lg font-semibold mb-4'>Uitgaven per Categorie</h2>
                    <div id='expense-chart' style='height: 300px; position: relative;'></div>";
        
        // Sorteer categorieën op uitgavenbedrag
        $expenseTotals = array_filter($categoryTotals, function($cat) {
            // Een categorie telt als uitgavencategorie als het type 'expense' is
            return $cat['type'] === 'expense';
        });
        usort($expenseTotals, function($a, $b) {
            return $b['total'] - $a['total'];
        });
        
        // Debug info voor expense totals
        echo "<!-- DEBUG EXPENSE TOTALS:";
        $debugCounter = 0;
        foreach($expenseTotals as $cat) {
            echo "
             Expense Categorie $debugCounter:
               - name: " . ($cat['name'] ?? 'undefined') . "
               - type: " . ($cat['type'] ?? 'undefined') . "
               - total: " . ($cat['total'] ?? 'undefined');
            $debugCounter++;
        }
        echo " -->";
        
        // Genereer data voor pie chart - uitgaven
        $expenseData = [];
        foreach ($expenseTotals as $cat) {
            $expenseData[] = [
                'name' => $cat['name'],
                'value' => $cat['total'] // Uitgaven hebben positieve waardes in dit systeem
            ];
        }
        
        // Als er geen uitgaven zijn
        if (empty($expenseData)) {
            echo "<div class='text-center text-gray-500 py-12'>Geen uitgaven gevonden voor deze periode</div>";
        }
        
        echo "
                </div>
                
                <!-- Inkomsten per categorie -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <h2 class='text-lg font-semibold mb-4'>Inkomsten per Categorie</h2>
                    <div id='income-chart' style='height: 300px; position: relative;'></div>";
        
        // Sorteer categorieën op inkomstenbedrag
        $incomeTotals = array_filter($categoryTotals, function($cat) {
            // Een categorie telt als inkomstencategorie als het type 'income' is
            return $cat['type'] === 'income';
        });
        usort($incomeTotals, function($a, $b) {
            return $b['total'] - $a['total'];
        });
        
        // Debug info voor income totals
        echo "<!-- DEBUG INCOME TOTALS:";
        $debugCounter = 0;
        foreach($incomeTotals as $cat) {
            echo "
             Income Categorie $debugCounter:
               - name: " . ($cat['name'] ?? 'undefined') . "
               - type: " . ($cat['type'] ?? 'undefined') . "
               - total: " . ($cat['total'] ?? 'undefined');
            $debugCounter++;
        }
        echo " -->";
        
        // Genereer data voor pie chart - inkomsten
        $incomeData = [];
        foreach ($incomeTotals as $cat) {
            $incomeData[] = [
                'name' => $cat['name'],
                'value' => $cat['total']
            ];
        }
        
        // Als er geen inkomsten zijn
        if (empty($incomeData)) {
            echo "<div class='text-center text-gray-500 py-12'>Geen inkomsten gevonden voor deze periode</div>";
        }
        
        echo "
                </div>
            </div>
            
            <!-- Tabellen -->
            <div class='grid grid-cols-1 gap-6 mb-6'>
                <!-- Transacties per categorie -->
                <div class='bg-white rounded-lg shadow-md overflow-hidden'>
                    <div class='border-b border-gray-200 px-6 py-4'>
                        <h2 class='text-lg font-semibold'>Transacties per Categorie</h2>
                    </div>
                    <div class='overflow-x-auto'>
                        <table class='min-w-full divide-y divide-gray-200'>
                            <thead class='bg-gray-50'>
                                <tr>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Categorie</th>
                                    <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>Bedrag</th>
                                    <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>% van Totaal</th>
                                </tr>
                            </thead>
                            <tbody class='bg-white divide-y divide-gray-200'>";
        
        // Uitgaven categorieën
        if (!empty($expenseTotals)) {
            echo "<tr><td colspan='3' class='px-6 py-3 bg-gray-100 font-medium'>Uitgaven</td></tr>";
            
            foreach ($expenseTotals as $cat) {
                $amount = $cat['total'];
                $percentage = $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0;
                
                echo "
                    <tr>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>{$cat['name']}</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-right text-red-600'>€" . number_format($amount, 2, ',', '.') . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-right'>{$percentage}%</td>
                    </tr>";
            }
            
            // Totaal uitgaven
            echo "
                <tr class='bg-gray-50'>
                    <td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>Totaal Uitgaven</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium'>€" . number_format($totalExpense, 2, ',', '.') . "</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-right font-medium'>100%</td>
                </tr>";
        } else {
            echo "<tr><td colspan='3' class='px-6 py-4 text-center text-gray-500'>Geen uitgaven gevonden voor deze periode</td></tr>";
        }
        
        // Inkomsten categorieën
        if (!empty($incomeTotals)) {
            echo "<tr><td colspan='3' class='px-6 py-3 bg-gray-100 font-medium'>Inkomsten</td></tr>";
            
            foreach ($incomeTotals as $cat) {
                $amount = $cat['total'];
                $percentage = $totalIncome > 0 ? round(($amount / $totalIncome) * 100, 1) : 0;
                
                echo "
                    <tr>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>{$cat['name']}</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-right text-green-600'>€" . number_format($amount, 2, ',', '.') . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-right'>{$percentage}%</td>
                    </tr>";
            }
            
            // Totaal inkomsten
            echo "
                <tr class='bg-gray-50'>
                    <td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>Totaal Inkomsten</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium'>€" . number_format($totalIncome, 2, ',', '.') . "</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-right font-medium'>100%</td>
                </tr>";
        } else {
            echo "<tr><td colspan='3' class='px-6 py-4 text-center text-gray-500'>Geen inkomsten gevonden voor deze periode</td></tr>";
        }
        
        // Balans
        echo "
                <tr class='bg-gray-100'>
                    <td class='px-6 py-4 whitespace-nowrap text-sm font-bold'>Balans</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-right font-bold $textColor'>€" . number_format($balance, 2, ',', '.') . "</td>
                    <td class='px-6 py-4 whitespace-nowrap text-sm text-right'>-</td>
                </tr>";
        
        echo "
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        ";
        
        // Voeg scripts toe voor de grafieken
        echo "
        <script src='https://cdn.jsdelivr.net/npm/apexcharts'></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Toggle datum inputs op basis van geselecteerde periode
                window.toggleDateInputs = function() {
                    const periodSelect = document.getElementById('period');
                    const monthSelector = document.getElementById('month-selector');
                    const customDates = document.getElementById('custom-dates');
                    
                    if (periodSelect.value === 'custom') {
                        customDates.classList.remove('hidden');
                        monthSelector.classList.add('hidden');
                    } else if (periodSelect.value === 'year') {
                        customDates.classList.add('hidden');
                        monthSelector.classList.add('hidden');
                    } else {
                        customDates.classList.add('hidden');
                        monthSelector.classList.remove('hidden');
                    }
                };
                
                // Uitgaven grafiek
                const expenseData = " . json_encode($expenseData) . ";
                if (expenseData.length > 0) {
                    const expenseOptions = {
                        series: expenseData.map(item => item.value),
                        chart: {
                            type: 'donut',
                            height: 300,
                            // Responsive opties voor mobiel
                            responsive: [{
                                breakpoint: 480,
                                options: {
                                    chart: {
                                        height: 250
                                    },
                                    legend: {
                                        position: 'bottom',
                                        offsetY: 0,
                                        height: 100
                                    }
                                }
                            }]
                        },
                        labels: expenseData.map(item => item.name),
                        colors: ['#f87171', '#fb7185', '#f43f5e', '#e11d48', '#be123c', '#9f1239', '#881337', '#4c0519', '#3f0814', '#fca5a5', '#fda4af', '#fed7aa', '#d8b4fe', '#c4b5fd'],
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            y: {
                                formatter: function(value) {
                                    return '€' + value.toFixed(2).replace('.', ',');
                                }
                            }
                        },
                        dataLabels: {
                            formatter: function(val, opts) {
                                return Math.round(val) + '%';
                            }
                        }
                    };
                    
                    const expenseChart = new ApexCharts(document.getElementById('expense-chart'), expenseOptions);
                    expenseChart.render();
                }
                
                // Inkomsten grafiek
                const incomeData = " . json_encode($incomeData) . ";
                if (incomeData.length > 0) {
                    const incomeOptions = {
                        series: incomeData.map(item => item.value),
                        chart: {
                            type: 'donut',
                            height: 300,
                            // Responsive opties voor mobiel
                            responsive: [{
                                breakpoint: 480,
                                options: {
                                    chart: {
                                        height: 250
                                    },
                                    legend: {
                                        position: 'bottom',
                                        offsetY: 0,
                                        height: 100
                                    }
                                }
                            }]
                        },
                        labels: incomeData.map(item => item.name),
                        colors: ['#4ade80', '#34d399', '#10b981', '#059669', '#047857', '#065f46', '#064e3b', '#022c22', '#bbf7d0', '#86efac', '#a7f3d0', '#6ee7b7', '#d1fae5'],
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            y: {
                                formatter: function(value) {
                                    return '€' + value.toFixed(2).replace('.', ',');
                                }
                            }
                        },
                        dataLabels: {
                            formatter: function(val, opts) {
                                return Math.round(val) + '%';
                            }
                        }
                    };
                    
                    const incomeChart = new ApexCharts(document.getElementById('income-chart'), incomeOptions);
                    incomeChart.render();
                }
            });
        </script>";
        
        // Einde content div
        echo "</div>";
        
        // Render de pagina
        $render();
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
