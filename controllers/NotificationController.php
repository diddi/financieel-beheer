<?php
// controllers/NotificationController.php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Notification;

class NotificationController {
    
    /**
     * Toon het notificatie-overzicht
     */
    public function index() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal notificaties op
        $notifications = Notification::getByUser($userId, false, 50);
        
        // Toon view
        include __DIR__ . '/../views/notifications/index.php';
    }
    
    /**
     * Markeer een notificatie als gelezen (AJAX-endpoint)
     */
    public function markAsRead() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $userId = Auth::id();
        $notificationId = $_POST['id'] ?? null;
        
        if (!$notificationId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Notification ID required']);
            exit;
        }
        
        // Markeer als gelezen
        $success = Notification::markAsRead($notificationId, $userId);
        
        // Stuur response
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }
    
    /**
     * Markeer alle notificaties als gelezen (AJAX-endpoint)
     */
    public function markAllAsRead() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $userId = Auth::id();
        
        // Markeer alle als gelezen
        $success = Notification::markAllAsRead($userId);
        
        // Stuur response
        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }
    
    /**
     * Haal het aantal ongelezen notificaties op (AJAX-endpoint)
     */
    public function getUnreadCount() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal aantal op
        $count = Notification::getUnreadCount($userId);
        
        // Stuur response
        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
    }
}