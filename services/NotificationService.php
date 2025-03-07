<?php
// services/NotificationService.php
namespace App\Services;

use App\Core\Database;

class NotificationService {
    
    /**
     * Maakt een nieuwe notificatie aan
     *
     * @param int $userId De gebruikers-ID
     * @param string $title Titel van de notificatie
     * @param string $message Bericht van de notificatie
     * @param string $type Type notificatie (info, warning, danger, success)
     * @param string|null $relatedEntity Gerelateerde entiteit (budget, transaction, etc.)
     * @param int|null $entityId ID van de gerelateerde entiteit
     * @return int De ID van de aangemaakte notificatie
     */
    public function createNotification($userId, $title, $message, $type = 'info', $relatedEntity = null, $entityId = null) {
        $db = Database::getInstance();
        
        $data = [
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_entity' => $relatedEntity,
            'entity_id' => $entityId
        ];
        
        return $db->insert('notifications', $data);
    }
    
    /**
     * Haalt alle notificaties op voor een gebruiker
     *
     * @param int $userId De gebruikers-ID
     * @param bool $onlyUnread Alleen ongelezen notificaties ophalen
     * @param int $limit Maximum aantal notificaties
     * @return array De notificaties
     */
    public function getNotifications($userId, $onlyUnread = false, $limit = 10) {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$userId];
        
        if ($onlyUnread) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Markeert een notificatie als gelezen
     *
     * @param int $notificationId De notificatie-ID
     * @param int $userId De gebruikers-ID
     * @return bool Succes
     */
    public function markAsRead($notificationId, $userId) {
        $db = Database::getInstance();
        
        $db->update('notifications', ['is_read' => 1], "id = ? AND user_id = ?", [$notificationId, $userId]);
        
        return true;
    }
    
    /**
     * Markeert alle notificaties van een gebruiker als gelezen
     *
     * @param int $userId De gebruikers-ID
     * @return bool Succes
     */
    public function markAllAsRead($userId) {
        $db = Database::getInstance();
        
        $db->update('notifications', ['is_read' => 1], "user_id = ?", [$userId]);
        
        return true;
    }
    
    /**
     * Controleert budget limieten en maakt notificaties aan indien nodig
     *
     * @param int $userId De gebruikers-ID
     * @param int|null $categoryId Optionele categorie-ID om alleen specifieke budgetten te controleren
     * @param string|null $transactionType Type transactie ('expense', 'income')
     * @return void
     */
    public function checkBudgetLimits($userId, $categoryId = null, $transactionType = null) {
        // Alleen uitgaven controleren
        if ($transactionType !== null && $transactionType !== 'expense') {
            return;
        }
        
        // Haal actieve budgetten op
        require_once __DIR__ . '/../models/Budget.php';
        $budgetStatus = \App\Models\Budget::getBudgetStatus($userId);
        
        foreach ($budgetStatus as $budget) {
            // Als we een specifieke categorie controleren, sla andere over
            if ($categoryId !== null && $budget['category_id'] != $categoryId) {
                continue;
            }
            
            // Als het budget is overschreden
            if ($budget['is_exceeded'] && !$this->hasRecentBudgetNotification($userId, $budget['id'], 'exceeded')) {
                $this->createNotification(
                    $userId,
                    'Budget overschreden',
                    'Je budget voor ' . $budget['category_name'] . ' is overschreden. Je hebt €' . 
                    number_format($budget['spent'], 2, ',', '.') . ' uitgegeven van de €' . 
                    number_format($budget['amount'], 2, ',', '.') . '.',
                    'danger',
                    'budget',
                    $budget['id']
                );
            }
            // Als het budget bijna is bereikt (waarschuwing)
            else if ($budget['is_warning'] && !$this->hasRecentBudgetNotification($userId, $budget['id'], 'warning')) {
                $this->createNotification(
                    $userId,
                    'Budget bijna bereikt',
                    'Je budget voor ' . $budget['category_name'] . ' is bijna bereikt. Je hebt €' . 
                    number_format($budget['spent'], 2, ',', '.') . ' uitgegeven van de €' . 
                    number_format($budget['amount'], 2, ',', '.') . '.',
                    'warning',
                    'budget',
                    $budget['id']
                );
            }
        }
    }
    
    /**
     * Controleert of er al een recente notificatie is voor een budget
     *
     * @param int $userId De gebruikers-ID
     * @param int $budgetId De budget-ID
     * @param string $type Type notificatie ('warning', 'exceeded')
     * @return bool Bestaat er al een recente notificatie?
     */
    private function hasRecentBudgetNotification($userId, $budgetId, $type) {
        $db = Database::getInstance();
        
        // Controleer op notificaties in de afgelopen 24 uur
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND related_entity = 'budget' AND entity_id = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
                
        if ($type === 'exceeded') {
            $sql .= " AND type = 'danger'";
        } else if ($type === 'warning') {
            $sql .= " AND type = 'warning'";
        }
        
        $result = $db->fetch($sql, [$userId, $budgetId]);
        
        return $result['count'] > 0;
    }
    
    /**
     * Controleert op grote uitgaven en maakt notificaties aan indien nodig
     *
     * @param int $userId De gebruikers-ID
     * @param float $amount Het bedrag van de uitgave
     * @param int|null $categoryId Optionele categorie-ID 
     * @return void
     */
    public function checkLargeExpense($userId, $amount, $categoryId = null) {
        // Bereken drempelwaarde (bijv. gemiddelde transactie * 2)
        $db = Database::getInstance();
        
        $sql = "SELECT AVG(amount) as avg_amount FROM transactions 
                WHERE user_id = ? AND type = 'expense'";
        
        if ($categoryId) {
            $sql .= " AND category_id = ?";
            $params = [$userId, $categoryId];
        } else {
            $params = [$userId];
        }
        
        $result = $db->fetch($sql, $params);
        
        if ($result && isset($result['avg_amount'])) {
            $avgAmount = floatval($result['avg_amount']);
            $threshold = max(50, $avgAmount * 2); // Minimum threshold is €50
            
            // Als het bedrag groter is dan de drempelwaarde
            if ($amount > $threshold) {
                // Haal categorie naam op als die beschikbaar is
                $categoryName = '';
                if ($categoryId) {
                    $category = $db->fetch("SELECT name FROM categories WHERE id = ?", [$categoryId]);
                    $categoryName = $category ? $category['name'] : '';
                }
                
                $this->createNotification(
                    $userId,
                    'Grote uitgave gedetecteerd',
                    'Je hebt zojuist €' . number_format($amount, 2, ',', '.') . 
                    ' uitgegeven' . ($categoryName ? ' in de categorie ' . $categoryName : '') . 
                    '. Dit is significant hoger dan je gemiddelde uitgave.',
                    'warning',
                    'transaction',
                    null
                );
            }
        }
    }
    
    /**
     * Controleert op terugkerende transacties die bijna moeten worden betaald
     * 
     * @param int $userId De gebruikers-ID
     * @return void
     */
    public function checkRecurringTransactions($userId) {
        $db = Database::getInstance();
        
        // Haal terugkerende transacties op die binnen 3 dagen betaald moeten worden
        $sql = "SELECT * FROM recurring_transactions 
                WHERE user_id = ? AND next_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                AND (end_date IS NULL OR end_date >= CURDATE())";
        
        $recurringTransactions = $db->fetchAll($sql, [$userId]);
        
        foreach ($recurringTransactions as $transaction) {
            // Controleer of er al een notificatie is verzonden
            $sql = "SELECT COUNT(*) as count FROM notifications 
                    WHERE user_id = ? AND related_entity = 'recurring_transaction' AND entity_id = ?
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
            
            $result = $db->fetch($sql, [$userId, $transaction['id']]);
            
            if ($result['count'] === 0) {
                // Maak een notificatie aan
                $daysUntilDue = (strtotime($transaction['next_due_date']) - time()) / (60 * 60 * 24);
                $daysText = $daysUntilDue < 1 ? 'vandaag' : 'over ' . round($daysUntilDue) . ' dagen';
                
                $this->createNotification(
                    $userId,
                    'Terugkerende transactie herinnering',
                    'Je hebt een terugkerende ' . ($transaction['type'] === 'expense' ? 'uitgave' : 'inkomst') . 
                    ' van €' . number_format($transaction['amount'], 2, ',', '.') . 
                    ' die ' . $daysText . ' verschuldigd is: ' . $transaction['description'],
                    'info',
                    'recurring_transaction',
                    $transaction['id']
                );
            }
        }
    }
}