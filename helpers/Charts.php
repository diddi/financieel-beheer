<?php

namespace App\Helpers;

class Charts {
    /**
     * Genereer kleuren voor een categorie op basis van de category_id
     * 
     * @param int $categoryId
     * @return string De hex kleurcode
     */
    public static function getCategoryColor($categoryId) {
        $colors = [
            '#4CAF50', '#2196F3', '#FF9800', '#E91E63', '#9C27B0',
            '#3F51B5', '#00BCD4', '#009688', '#8BC34A', '#FFEB3B',
            '#FF5722', '#795548', '#607D8B', '#F44336', '#673AB7'
        ];
        
        return $colors[$categoryId % count($colors)];
    }
    
    /**
     * Bepaal de kleur voor een budget op basis van het percentage
     * 
     * @param float $percentage Het percentage van het budget dat gebruikt is
     * @return string De kleur naam (green, orange, red)
     */
    public static function getBudgetStatusColor($percentage) {
        if ($percentage < 70) {
            return 'green';
        } elseif ($percentage < 90) {
            return 'orange';
        } else {
            return 'red';
        }
    }
    
    /**
     * Format data voor een trendgrafiek
     * 
     * @param array $transactions De transacties data
     * @param string $startDate De startdatum in Y-m-d formaat
     * @param string $endDate De einddatum in Y-m-d formaat
     * @param string $type Het type waarde om te plotten (amount, count, etc)
     * @return array Array met labels en data voor chart.js
     */
    public static function formatTrendData($transactions, $startDate, $endDate, $type = 'amount') {
        $dateRange = [];
        $currentDate = strtotime($startDate);
        
        // Maak een array met alle datums als sleutels
        while ($currentDate <= strtotime($endDate)) {
            $dateKey = date('Y-m-d', $currentDate);
            $dateRange[$dateKey] = 0;
            $currentDate = strtotime('+1 day', $currentDate);
        }
        
        // Vul de data in voor elke datum
        foreach ($transactions as $transaction) {
            $date = $transaction['date'];
            if (isset($dateRange[$date])) {
                if ($type === 'amount') {
                    // Voor uitgaven, maak het bedrag positief voor de grafiek
                    $dateRange[$date] += abs($transaction['amount']);
                } elseif ($type === 'count') {
                    // Tel het aantal transacties
                    $dateRange[$date]++;
                }
            }
        }
        
        return [
            'labels' => array_keys($dateRange),
            'data' => array_values($dateRange)
        ];
    }
    
    /**
     * Genereer labels voor een tijdsperiode
     * 
     * @param string $period Het type periode (daily, weekly, monthly, yearly)
     * @param int $count Het aantal periodelabels om te genereren
     * @param string $startDate De startdatum (optioneel)
     * @return array Array met labels
     */
    public static function generateTimeLabels($period, $count, $startDate = null) {
        $labels = [];
        
        if ($startDate === null) {
            $startDate = date('Y-m-d');
        }
        
        $currentDate = strtotime($startDate);
        
        for ($i = 0; $i < $count; $i++) {
            switch ($period) {
                case 'daily':
                    $labels[] = date('d-m', $currentDate);
                    $currentDate = strtotime('-1 day', $currentDate);
                    break;
                case 'weekly':
                    $labels[] = 'Week ' . date('W', $currentDate);
                    $currentDate = strtotime('-1 week', $currentDate);
                    break;
                case 'monthly':
                    $labels[] = date('M Y', $currentDate);
                    $currentDate = strtotime('-1 month', $currentDate);
                    break;
                case 'yearly':
                    $labels[] = date('Y', $currentDate);
                    $currentDate = strtotime('-1 year', $currentDate);
                    break;
            }
        }
        
        return array_reverse($labels);
    }
} 