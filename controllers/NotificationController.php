<?php
// controllers/NotificationController.php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Notification;

class NotificationController extends Controller {
    
    /**
     * Toon het notificatie-overzicht
     */
    public function index() {
        $userId = $this->requireLogin();
        
        // Haal notificaties op
        $notifications = Notification::getByUser($userId, false, 50);
        
        // Bereid de render functie voor
        $render = $this->startBuffering('Notificaties');
        
        // Begin HTML output
        echo "<div class='max-w-7xl mx-auto'>";
        
        // Titel en overzicht
        echo "
            <div class='mb-6'>
                <h1 class='text-2xl font-bold'>Notificaties</h1>
                <p class='text-gray-500 mt-1'>Bekijk en beheer je notificaties</p>
            </div>";
        
        // Toon markeer alles als gelezen knop als er notificaties zijn
        if (count($notifications) > 0) {
            echo "
                <div class='mb-4 flex justify-end'>
                    <form action='/notifications/mark-all-read' method='post'>
                        <button type='submit' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            <i class='material-icons mr-2'>done_all</i>
                            Alles als gelezen markeren
                        </button>
                    </form>
                </div>";
        }
        
        // Notificaties lijst
        if (count($notifications) > 0) {
            echo "<div class='bg-white shadow rounded-lg overflow-hidden'>";
            
            foreach ($notifications as $index => $notification) {
                $isRead = (int)$notification['is_read'] === 1;
                $bgClass = $isRead ? 'bg-white' : 'bg-blue-50';
                $borderClass = $index !== 0 ? 'border-t border-gray-200' : '';
                
                echo "
                    <div class='p-4 {$bgClass} {$borderClass}'>
                        <div class='flex items-start'>
                            <div class='flex-shrink-0 pt-0.5'>
                                <i class='material-icons text-blue-600'>" . htmlspecialchars($notification['icon'] ?? 'notification_important') . "</i>
                            </div>
                            <div class='ml-3 flex-1'>
                                <div class='flex items-center justify-between'>
                                    <p class='text-sm font-medium text-gray-900'>" . htmlspecialchars($notification['title']) . "</p>
                                    <div class='ml-2 flex-shrink-0 flex'>
                                        <p class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800'>
                                            " . date('d M Y H:i', strtotime($notification['created_at'])) . "
                                        </p>
                                    </div>
                                </div>
                                <p class='text-sm text-gray-500 mt-1'>" . htmlspecialchars($notification['message']) . "</p>
                                
                                <div class='mt-2 flex'>
                                    <a href='" . htmlspecialchars($notification['action_url'] ?? '#') . "' class='text-sm text-blue-600 hover:text-blue-800 mr-4'>
                                        <i class='material-icons align-text-bottom text-sm'>visibility</i>
                                        Bekijken
                                    </a>";
                                    
                if (!$isRead) {
                    echo "
                                    <form action='/notifications/mark-read' method='post' class='inline'>
                                        <input type='hidden' name='notification_id' value='" . $notification['id'] . "'>
                                        <button type='submit' class='text-sm text-gray-600 hover:text-gray-800'>
                                            <i class='material-icons align-text-bottom text-sm'>done</i>
                                            Markeer als gelezen
                                        </button>
                                    </form>";
                }
                
                echo "
                                </div>
                            </div>
                        </div>
                    </div>";
            }
            
            echo "</div>";
        } else {
            echo "
                <div class='bg-white shadow rounded-lg p-6 text-center'>
                    <i class='material-icons text-gray-400 text-5xl mb-4'>notifications_none</i>
                    <h3 class='text-lg font-medium text-gray-900 mb-1'>Geen notificaties</h3>
                    <p class='text-gray-500'>Je hebt momenteel geen notificaties om te bekijken.</p>
                </div>";
        }
        
        echo "</div>"; // Sluit max-w-7xl div
        
        // Render de pagina
        $render();
    }
    
    /**
     * Markeer een notificatie als gelezen
     */
    public function markAsRead() {
        $userId = $this->requireLogin();
        
        // Controleer of er een ID is
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Ongeldige notificatie ID']);
            exit;
        }
        
        $notificationId = $_GET['id'];
        
        // Markeer als gelezen
        $success = Notification::markAsRead($notificationId, $userId);
        
        // Stuur JSON response
        echo json_encode(['success' => $success]);
        exit;
    }
    
    /**
     * Markeer alle notificaties als gelezen
     */
    public function markAllAsRead() {
        $userId = $this->requireLogin();
        
        // Markeer alle notificaties als gelezen
        $success = Notification::markAllAsRead($userId);
        
        // Stuur JSON response
        echo json_encode(['success' => $success]);
        exit;
    }
    
    /**
     * Haal het aantal ongelezen notificaties op
     */
    public function getUnreadCount() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            echo json_encode(['count' => 0]);
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal aantal ongelezen notificaties op
        $count = Notification::getUnreadCount($userId);
        
        // Stuur JSON response
        echo json_encode(['count' => $count]);
        exit;
    }
}