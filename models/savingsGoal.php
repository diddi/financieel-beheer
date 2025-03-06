<?php
namespace App\Models;

use App\Core\Database;

class SavingsGoal {
    protected static $table = 'savings_goals';
    protected static $transactionsTable = 'savings_transactions';
    
    /**
     * Haal een spaardoel op op basis van ID en gebruiker
     */
    public static function getById($id, $userId) {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT * FROM " . self::$table . " WHERE id = ? AND user_id = ?", 
            [$id, $userId]
        );
    }
    
    /**
     * Haal alle spaardoelen op voor een gebruiker
     */
    public static function getAllByUser($userId, $includeCompleted = true) {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM " . self::$table . " WHERE user_id = ?";
        $params = [$userId];
        
        if (!$includeCompleted) {
            $sql .= " AND is_completed = 0";
        }
        
        $sql .= " ORDER BY target_date ASC";
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Maak een nieuw spaardoel aan
     */
    public static function create($data) {
        $db = Database::getInstance();
        return $db->insert(self::$table, $data);
    }
    
    /**
     * Update een bestaand spaardoel
     */
    public static function update($id, $data, $userId) {
        $db = Database::getInstance();
        $db->update(self::$table, $data, "id = ? AND user_id = ?", [$id, $userId]);
    }
    
    /**
     * Verwijder een spaardoel
     */
    public static function delete($id, $userId) {
        $db = Database::getInstance();
        
        // Verwijder eerst alle gekoppelde transacties
        $db->delete(self::$transactionsTable, "savings_goal_id = ? AND user_id = ?", [$id, $userId]);
        
        // Verwijder daarna het spaardoel
        $db->delete(self::$table, "id = ? AND user_id = ?", [$id, $userId]);
    }
    
    /**
     * Voeg een transactie toe aan een spaardoel
     */
    public static function addTransaction($data) {
        $db = Database::getInstance();
        $transactionId = $db->insert(self::$transactionsTable, $data);
        
        // Update het huidige bedrag van het spaardoel
        $savingsGoal = self::getById($data['savings_goal_id'], $data['user_id']);
        $newAmount = $savingsGoal['current_amount'] + $data['amount'];
        
        // Check of het doel is bereikt
        $isCompleted = $newAmount >= $savingsGoal['target_amount'];
        
        // Update het spaardoel
        self::update($data['savings_goal_id'], [
            'current_amount' => $newAmount,
            'is_completed' => $isCompleted ? 1 : 0
        ], $data['user_id']);
        
        return $transactionId;
    }
    
    /**
     * Verwijder een transactie van een spaardoel
     */
    public static function removeTransaction($transactionId, $savingsGoalId, $userId) {
        $db = Database::getInstance();
        
        // Haal de transactie op
        $transaction = $db->fetch(
            "SELECT * FROM " . self::$transactionsTable . " WHERE id = ? AND savings_goal_id = ? AND user_id = ?",
            [$transactionId, $savingsGoalId, $userId]
        );
        
        if (!$transaction) {
            return false;
        }
        
        // Verwijder de transactie
        $db->delete(self::$transactionsTable, "id = ?", [$transactionId]);
        
        // Update het huidige bedrag van het spaardoel
        $savingsGoal = self::getById($savingsGoalId, $userId);
        $newAmount = $savingsGoal['current_amount'] - $transaction['amount'];
        
        // Zorg dat het bedrag niet negatief wordt
        $newAmount = max(0, $newAmount);
        
        // Update het spaardoel
        self::update($savingsGoalId, [
            'current_amount' => $newAmount,
            'is_completed' => $newAmount >= $savingsGoal['target_amount'] ? 1 : 0
        ], $userId);
        
        return true;
    }
    
    /**
     * Haal alle transacties op voor een spaardoel
     */
    public static function getTransactions($savingsGoalId, $userId) {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT st.*, t.description, t.type FROM " . self::$transactionsTable . " st 
            LEFT JOIN transactions t ON st.transaction_id = t.id
            WHERE st.savings_goal_id = ? AND st.user_id = ?
            ORDER BY st.date DESC",
            [$savingsGoalId, $userId]
        );
    }
    
    /**
     * Bereken statistieken voor een spaardoel
     */
    public static function calculateStats($savingsGoal) {
        // Bereken voortgang percentage
        $progress = $savingsGoal['target_amount'] > 0 ? 
            ($savingsGoal['current_amount'] / $savingsGoal['target_amount']) * 100 : 0;
        
        // Bereken resterende dagen
        $targetDate = new \DateTime($savingsGoal['target_date']);
        $today = new \DateTime();
        $daysRemaining = $today <= $targetDate ? $today->diff($targetDate)->days : 0;
        
        // Bereken benodigde bedrag per dag om het doel te halen
        $remainingAmount = $savingsGoal['target_amount'] - $savingsGoal['current_amount'];
        $amountPerDay = $daysRemaining > 0 ? $remainingAmount / $daysRemaining : 0;
        
        // Bereken totale aantal dagen
        $startDate = new \DateTime($savingsGoal['start_date']);
        $totalDays = $startDate->diff($targetDate)->days;
        
        // Bereken verlopen dagen
        $elapsedDays = $startDate->diff($today)->days;
        $elapsedDays = min($elapsedDays, $totalDays); // Begrens tot totaal aantal dagen
        
        // Bereken tijdsvoortgang percentage
        $timeProgress = $totalDays > 0 ? ($elapsedDays / $totalDays) * 100 : 0;
        
        // Bereken of we op schema liggen
        $expectedAmount = $totalDays > 0 ? 
            ($savingsGoal['target_amount'] * $elapsedDays / $totalDays) : 0;
        $onTrack = $savingsGoal['current_amount'] >= $expectedAmount;
        
        return [
            'progress' => min(100, $progress),
            'days_remaining' => $daysRemaining,
            'amount_per_day' => $amountPerDay,
            'remaining_amount' => $remainingAmount,
            'time_progress' => min(100, $timeProgress),
            'on_track' => $onTrack,
            'expected_amount' => $expectedAmount
        ];
    }
}