<?php
// models/RecurringTransaction.php
namespace App\Models;

use App\Core\Database;

class RecurringTransaction {
    protected static $table = 'recurring_transactions';
    
    /**
     * Haalt een terugkerende transactie op op basis van ID
     *
     * @param int $id De transactie-ID
     * @param int $userId De gebruikers-ID
     * @return array|null De transactie
     */
    public static function getById($id, $userId) {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT t.*, c.name as category_name, a.name as account_name 
             FROM " . self::$table . " t 
             LEFT JOIN categories c ON t.category_id = c.id 
             LEFT JOIN accounts a ON t.account_id = a.id 
             WHERE t.id = ? AND t.user_id = ?", 
            [$id, $userId]
        );
    }
    
    /**
     * Haalt alle terugkerende transacties op voor een gebruiker
     *
     * @param int $userId De gebruikers-ID
     * @param bool $onlyActive Alleen actieve transacties ophalen
     * @return array De transacties
     */
    public static function getAllByUser($userId, $onlyActive = true) {
        $db = Database::getInstance();
        
        $sql = "SELECT t.*, c.name as category_name, c.color, a.name as account_name 
                FROM " . self::$table . " t 
                LEFT JOIN categories c ON t.category_id = c.id 
                LEFT JOIN accounts a ON t.account_id = a.id 
                WHERE t.user_id = ?";
                
        $params = [$userId];
        
        if ($onlyActive) {
            $sql .= " AND t.is_active = 1";
        }
        
        $sql .= " ORDER BY t.next_due_date ASC";
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Maakt een nieuwe terugkerende transactie aan
     *
     * @param array $data De transactiedata
     * @return int De transactie-ID
     */
    public static function create($data) {
        $db = Database::getInstance();
        return $db->insert(self::$table, $data);
    }
    
    /**
     * Update een terugkerende transactie
     *
     * @param int $id De transactie-ID
     * @param array $data De transactiedata
     * @param int $userId De gebruikers-ID
     * @return bool Succes
     */
    public static function update($id, $data, $userId) {
        $db = Database::getInstance();
        $db->update(self::$table, $data, "id = ? AND user_id = ?", [$id, $userId]);
        
        return true;
    }
    
    /**
     * Verwijder een terugkerende transactie
     *
     * @param int $id De transactie-ID
     * @param int $userId De gebruikers-ID
     * @return bool Succes
     */
    public static function delete($id, $userId) {
        $db = Database::getInstance();
        $db->delete(self::$table, "id = ? AND user_id = ?", [$id, $userId]);
        
        return true;
    }
    
    /**
     * Bereken de volgende datum voor een terugkerende transactie
     *
     * @param string $startDate De startdatum
     * @param string $frequency De frequentie (daily, weekly, monthly, quarterly, yearly)
     * @param string|null $lastDate De laatste datum (om vanaf te berekenen)
     * @return string De nieuwe datum
     */
    public static function calculateNextDueDate($startDate, $frequency, $lastDate = null) {
        $date = new \DateTime($lastDate ?? $startDate);
        
        switch ($frequency) {
            case 'daily':
                $date->add(new \DateInterval('P1D'));
                break;
            case 'weekly':
                $date->add(new \DateInterval('P1W'));
                break;
            case 'monthly':
                $date->add(new \DateInterval('P1M'));
                break;
            case 'quarterly':
                $date->add(new \DateInterval('P3M'));
                break;
            case 'yearly':
                $date->add(new \DateInterval('P1Y'));
                break;
        }
        
        return $date->format('Y-m-d');
    }
    
    /**
     * Processen van terugkerende transacties die uitgevoerd moeten worden
     *
     * @return void
     */
    public static function processRecurringTransactions() {
        $db = Database::getInstance();
        
        // Haal alle actieve terugkerende transacties op die vandaag of eerder moeten worden uitgevoerd
        $sql = "SELECT * FROM " . self::$table . " 
                WHERE is_active = 1 
                AND next_due_date <= CURDATE()
                AND (end_date IS NULL OR end_date >= CURDATE())";
        
        $transactions = $db->fetchAll($sql);
        
        foreach ($transactions as $transaction) {
            // Maak een nieuwe transactie aan
            $transactionData = [
                'user_id' => $transaction['user_id'],
                'account_id' => $transaction['account_id'],
                'category_id' => $transaction['category_id'],
                'amount' => $transaction['amount'],
                'type' => $transaction['type'],
                'description' => $transaction['description'] . ' (Automatisch)',
                'date' => $transaction['next_due_date'],
                'is_recurring' => 1
            ];
            
            // Maak de transactie aan
            $transactionId = Transaction::create($transactionData);
            
            // Update de terugkerende transactie met de nieuwe volgende datum
            $nextDueDate = self::calculateNextDueDate($transaction['start_date'], $transaction['frequency'], $transaction['next_due_date']);
            
            // Update de terugkerende transactie
            self::update($transaction['id'], [
                'next_due_date' => $nextDueDate,
                'last_generated_date' => $transaction['next_due_date']
            ], $transaction['user_id']);
            
            // Maak een notificatie aan
            $notificationService = new \App\Services\NotificationService();
            $notificationService->createNotification(
                $transaction['user_id'],
                'Terugkerende transactie uitgevoerd',
                'De volgende terugkerende ' . ($transaction['type'] === 'expense' ? 'uitgave' : 'inkomst') . 
                ' is automatisch uitgevoerd: ' . $transaction['description'] . ' ter waarde van €' . 
                number_format($transaction['amount'], 2, ',', '.'),
                'info',
                'recurring_transaction',
                $transaction['id']
            );
        }
    }
}