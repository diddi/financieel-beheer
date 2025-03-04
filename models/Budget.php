<?php
namespace App\Models;

use App\Core\Database;

class Budget {
    protected static $table = 'budgets';
    
    public static function getById($id, $userId) {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT b.*, c.name as category_name, c.color
             FROM " . self::$table . " b
             LEFT JOIN categories c ON b.category_id = c.id
             WHERE b.id = ? AND b.user_id = ?", 
            [$id, $userId]
        );
    }
    
    public static function getAllByUser($userId) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT b.*, c.name as category_name, c.color
             FROM " . self::$table . " b
             LEFT JOIN categories c ON b.category_id = c.id
             WHERE b.user_id = ? 
             ORDER BY b.period, c.name", 
            [$userId]
        );
    }
    
    public static function getActiveByUser($userId, $categoryId = null) {
        $db = Database::getInstance();
        
        $sql = "SELECT b.*, c.name as category_name, c.color
                FROM " . self::$table . " b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE b.user_id = ? AND b.is_active = 1";
        $params = [$userId];
                
        if ($categoryId) {
            $sql .= " AND b.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " ORDER BY b.period, c.name";
        
        return $db->fetchAll($sql, $params);
    }
    
    public static function create($data) {
        $db = Database::getInstance();
        return $db->insert(self::$table, $data);
    }
    
    public static function update($id, $data, $userId) {
        $db = Database::getInstance();
        $db->update(self::$table, $data, "id = ? AND user_id = ?", [$id, $userId]);
    }
    
    public static function delete($id, $userId) {
        $db = Database::getInstance();
        $db->delete(self::$table, "id = ? AND user_id = ?", [$id, $userId]);
    }
    
    /**
     * Controleer budgetstatus voor een gebruiker
     * 
     * @param int $userId
     * @return array Budgetstatus per categorie
     */
    public static function getBudgetStatus($userId) {
        $db = Database::getInstance();
        
        // Haal actieve budgetten op
        $budgets = self::getActiveByUser($userId);
        
        $result = [];
        
        foreach ($budgets as $budget) {
            // Bereken begindatum en einddatum voor huidige budget periode
            $currentPeriodDates = self::getCurrentPeriodDates($budget['period']);
            
            // Haal uitgaven op voor deze categorie in deze periode
            $sql = "SELECT SUM(amount) as total 
                   FROM transactions 
                   WHERE user_id = ? 
                   AND category_id = ? 
                   AND type = 'expense'
                   AND date BETWEEN ? AND ?";
            
            $spent = $db->fetch($sql, [
                $userId, 
                $budget['category_id'], 
                $currentPeriodDates['start'],
                $currentPeriodDates['end']
            ]);
            
            $spentAmount = $spent['total'] ?? 0;
            $percentage = $budget['amount'] > 0 ? ($spentAmount / $budget['amount']) * 100 : 0;
            
            $result[] = [
                'id' => $budget['id'],
                'category_id' => $budget['category_id'],
                'category_name' => $budget['category_name'],
                'color' => $budget['color'],
                'amount' => $budget['amount'],
                'spent' => $spentAmount,
                'remaining' => $budget['amount'] - $spentAmount,
                'percentage' => $percentage,
                'period' => $budget['period'],
                'period_dates' => $currentPeriodDates,
                'alert_threshold' => $budget['alert_threshold'],
                'is_exceeded' => $percentage >= 100,
                'is_warning' => $percentage >= $budget['alert_threshold'] && $percentage < 100
            ];
        }
        
        return $result;
    }
    
    /**
     * Bereken de begin- en einddatum voor de huidige periode
     * 
     * @param string $period (daily, weekly, monthly, yearly)
     * @return array Met begindatum en einddatum
     */
    public static function getCurrentPeriodDates($period) {
        $now = new \DateTime();
        $start = new \DateTime();
        $end = new \DateTime();
        
        switch ($period) {
            case 'daily':
                // Vandaag
                $start->setTime(0, 0, 0);
                $end->setTime(23, 59, 59);
                break;
                
            case 'weekly':
                // Huidige week (maandag tot zondag)
                $start->modify('monday this week')->setTime(0, 0, 0);
                $end->modify('sunday this week')->setTime(23, 59, 59);
                break;
                
            case 'monthly':
                // Huidige maand
                $start->modify('first day of this month')->setTime(0, 0, 0);
                $end->modify('last day of this month')->setTime(23, 59, 59);
                break;
                
            case 'yearly':
                // Huidig jaar
                $start->modify('first day of january this year')->setTime(0, 0, 0);
                $end->modify('last day of december this year')->setTime(23, 59, 59);
                break;
        }
        
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d')
        ];
    }
}
