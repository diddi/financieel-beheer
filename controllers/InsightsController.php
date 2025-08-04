<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Account;
use App\Helpers\Charts;

class InsightsController extends Controller {
    
    /**
     * Toon het financiële inzichten dashboard
     */
    public function index() {
        $user_id = $this->requireLogin();
        
        // Huidige maand gegevens
        $currentMonth = date('m');
        $currentYear = date('Y');
        $daysInMonth = date('t');
        $currentDay = date('d');
        
        // Ophalen van transacties van de huidige maand
        $currentMonthTransactions = Transaction::getAllByUser($user_id, [
            'date_from' => "$currentYear-$currentMonth-01",
            'date_to' => "$currentYear-$currentMonth-" . date('t', strtotime("$currentYear-$currentMonth-01"))
        ]);
        
        // Ophalen van transacties van de vorige maand
        $previousMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
        $previousYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;
        
        $previousMonthTransactions = Transaction::getAllByUser($user_id, [
            'date_from' => "$previousYear-$previousMonth-01",
            'date_to' => "$previousYear-$previousMonth-" . date('t', strtotime("$previousYear-$previousMonth-01"))
        ]);
        
        // Bereken totale inkomsten en uitgaven voor de huidige maand
        $currentMonthIncomes = 0;
        $currentMonthExpenses = 0;
        
        foreach ($currentMonthTransactions as $transaction) {
            if ($transaction['type'] === 'income') {
                $currentMonthIncomes += $transaction['amount'];
            } else {
                $currentMonthExpenses += $transaction['amount'];
            }
        }
        
        // Bereken totale inkomsten en uitgaven voor de vorige maand
        $previousMonthIncomes = 0;
        $previousMonthExpenses = 0;
        
        foreach ($previousMonthTransactions as $transaction) {
            if ($transaction['type'] === 'income') {
                $previousMonthIncomes += $transaction['amount'];
            } else {
                $previousMonthExpenses += $transaction['amount'];
            }
        }
        
        // Berekenen uitgaven per categorie voor taartdiagram
        $categories = Category::getAllByUser($user_id);
        $categoryExpenses = [];
        $categoryColors = [];
        
        foreach ($categories as $category) {
            $amount = 0;
            
            foreach ($currentMonthTransactions as $transaction) {
                if ($transaction['category_id'] == $category['id'] && $transaction['type'] === 'expense') {
                    $amount += $transaction['amount'];
                }
            }
            
            if ($amount > 0) {
                $categoryExpenses[$category['name']] = $amount;
                $categoryColors[] = Charts::getCategoryColor($category['id']);
            }
        }
        
        // Top 5 uitgaven identificeren
        $expenseTransactions = array_filter($currentMonthTransactions, function($t) {
            return $t['type'] === 'expense';
        });
        
        usort($expenseTransactions, function($a, $b) {
            return $b['amount'] - $a['amount'];
        });
        
        $topExpenses = array_slice($expenseTransactions, 0, 5);
        
        // Budget vooruitgang berekenen
        $budgets = Budget::getAllByUser($user_id, [
            'active' => true
        ]);
        
        $budgetProgress = [];
        
        foreach ($budgets as $budget) {
            $spent = 0;
            
            foreach ($currentMonthTransactions as $transaction) {
                if ($transaction['category_id'] == $budget['category_id'] && $transaction['type'] === 'expense') {
                    $spent += $transaction['amount'];
                }
            }
            
            $percentage = $budget['amount'] > 0 ? min(100, round(($spent / $budget['amount']) * 100)) : 0;
            $category = Category::getById($budget['category_id']);
            
            $budgetProgress[] = [
                'name' => $category['name'],
                'budget' => $budget['amount'],
                'spent' => $spent,
                'percentage' => $percentage,
                'color' => Charts::getBudgetStatusColor($percentage)
            ];
        }
        
        // Sorteren op hoogste percentage
        usort($budgetProgress, function($a, $b) {
            return $b['percentage'] - $a['percentage'];
        });
        
        // Genereer data voor uitgaven trendgrafiek (laatste 30 dagen)
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        
        $dailyTransactions = Transaction::getAllByUser($user_id, [
            'date_from' => $startDate,
            'date_to' => $endDate,
            'type' => 'expense'
        ]);
        
        // Maak een array met alle dagen als sleutels
        $dateRange = [];
        $currentDate = strtotime($startDate);
        while ($currentDate <= strtotime($endDate)) {
            $dateKey = date('Y-m-d', $currentDate);
            $dateRange[$dateKey] = 0;
            $currentDate = strtotime('+1 day', $currentDate);
        }
        
        // Vul de data in per dag
        foreach ($dailyTransactions as $transaction) {
            $dateKey = $transaction['date'];
            if (isset($dateRange[$dateKey])) {
                $dateRange[$dateKey] += $transaction['amount'];
            }
        }
        
        // Formatteren voor chart.js
        $labels = array_keys($dateRange);
        $data = array_values($dateRange);
        
        // Berekenen vergelijking met vorige maand
        $monthlyComparison = [
            'income' => [
                'current' => $currentMonthIncomes,
                'previous' => $previousMonthIncomes,
                'percentage' => $previousMonthIncomes > 0 
                    ? round((($currentMonthIncomes - $previousMonthIncomes) / $previousMonthIncomes) * 100, 1)
                    : 100
            ],
            'expenses' => [
                'current' => $currentMonthExpenses,
                'previous' => $previousMonthExpenses,
                'percentage' => $previousMonthExpenses > 0 
                    ? round((($currentMonthExpenses - $previousMonthExpenses) / $previousMonthExpenses) * 100, 1)
                    : 100
            ]
        ];
        
        // Start buffering voor de pagina
        $render = $this->startBuffering('Financiële Inzichten');
        
        // De view variabelen beschikbaar stellen
        $currentMonthName = date('F');
        $previousMonthName = date('F', mktime(0, 0, 0, $previousMonth, 1, $previousYear));
        $monthProgress = round(($currentDay / $daysInMonth) * 100);
        $incomes = $currentMonthIncomes;
        $expenses = $currentMonthExpenses;
        $trendLabels = json_encode($labels);
        $trendData = json_encode($data);
        
        // Include de view
        include(ROOT_PATH . '/views/insights/index.php');
        
        // Render de pagina met de app layout
        $render();
    }
} 