<?php
// models/Notification.php
namespace App\Models;

use App\Core\Database;

class Notification {
    protected static $table = 'notifications';
    
    /**
     * Haalt alle notificaties op voor een gebruiker
     *
     * @param int $userId De gebruikers-ID
     * @param bool $onlyUnread Alleen ongelezen notificaties ophalen
     * @param int $limit Maximum aantal notificaties
     * @return array De notificaties
     */
    public static function getByUser($userId, $onlyUnread = false, $limit = 10) {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM " . self::$table . " WHERE user_id = ?";
        $params = [$userId];
        
        if ($onlyUnread) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Haalt het aantal ongelezen notificaties op voor een gebruiker
     *
     * @param int $userId De gebruikers-ID
     * @return int Aantal ongelezen notificaties
     */
    public static function getUnreadCount($userId) {
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(*) as count FROM " . self::$table . " WHERE user_id = ? AND is_read = 0";
        $result = $db->fetch($sql, [$userId]);
        
        return $result['count'];
    }
    
    /**
     * Markeert een notificatie als gelezen
     *
     * @param int $id De notificatie-ID
     * @param int $userId De gebruikers-ID
     * @return bool Succes
     */
    public static function markAsRead($id, $userId) {
        $db = Database::getInstance();
        
        $db->update(self::$table, ['is_read' => 1], "id = ? AND user_id = ?", [$id, $userId]);
        
        return true;
    }
    
    /**
     * Markeert alle notificaties van een gebruiker als gelezen
     *
     * @param int $userId De gebruikers-ID
     * @return bool Succes
     */
    public static function markAllAsRead($userId) {
        $db = Database::getInstance();
        
        $db->update(self::$table, ['is_read' => 1], "user_id = ?", [$userId]);
        
        return true;
    }
    
    /**
     * Verwijdert oude notificaties
     *
     * @param int $userId De gebruikers-ID
     * @param int $days Aantal dagen oud
     * @return bool Succes
     */
    public static function cleanupOldNotifications($userId, $days = 30) {
        $db = Database::getInstance();
        
        $sql = "DELETE FROM " . self::$table . " 
                WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $db->query($sql, [$userId, $days]);
        
        return true;
    }
}