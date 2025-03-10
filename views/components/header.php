<?php
/**
 * Header component voor alle pagina's
 * 
 * Te gebruiken samen met de sidebar voor een complete navigatie
 */

use App\Core\Auth;
use App\Models\Notification;

// Haal gebruikersnaam op als deze beschikbaar is
$username = '';
if (isset($user) && isset($user['username'])) {
    $username = $user['username'];
} else if (class_exists('\\App\\Core\\Auth') && method_exists('\\App\\Core\\Auth', 'user')) {
    $currentUser = \App\Core\Auth::user();
    if ($currentUser && isset($currentUser['username'])) {
        $username = $currentUser['username'];
    }
}

// Haal aantal ongelezen notificaties op
$unreadNotificationCount = 0;
if (class_exists('\\App\\Models\\Notification') && method_exists('\\App\\Models\\Notification', 'getUnreadCount') && Auth::check()) {
    $unreadNotificationCount = Notification::getUnreadCount(Auth::id());
}

// Pagina titel bepalen
$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';
?>

<!-- Header -->
<header class="bg-white shadow-sm z-20 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 md:ml-64">
        <div class="flex justify-between items-center">
            <!-- Page Title -->
            <h1 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($pageTitle) ?></h1>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-4">
                <!-- Snelle acties dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="p-2 rounded-full hover:bg-gray-100 focus:outline-none">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10" style="display: none;">
                        <a href="/transactions/create" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Nieuwe transactie
                        </a>
                        <a href="/budgets/create" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Nieuw budget
                        </a>
                        <a href="/savings/create" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Nieuw spaardoel
                        </a>
                        <a href="/recurring/create" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Nieuwe periodieke transactie
                        </a>
                    </div>
                </div>
                
                <!-- Notificaties -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="p-2 rounded-full hover:bg-gray-100 focus:outline-none relative">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <?php if ($unreadNotificationCount > 0): ?>
                            <span class="absolute top-0 right-0 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                                <?= $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg py-1 z-10" style="display: none;">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Notificaties</span>
                                <a href="/notifications" class="text-xs text-blue-500 hover:text-blue-700">Alles bekijken</a>
                            </div>
                        </div>
                        
                        <?php
                        // Toon laatste 3 notificaties (dummy data voor nu)
                        // In een echte implementatie zou je hier de laatste notificaties uit de database halen
                        $notifications = [];
                        
                        if (class_exists('\\App\\Models\\Notification') && method_exists('\\App\\Models\\Notification', 'getLatest') && Auth::check()) {
                            try {
                                $notifications = \App\Models\Notification::getLatest(Auth::id(), 3);
                            } catch (Exception $e) {
                                // Fallback naar dummy data als er een fout optreedt
                                $notifications = [
                                    ['title' => 'Budget overschreden', 'message' => 'Eten & Drinken is met €25,50 overschreden', 'time' => '2 uur geleden', 'type' => 'warning'],
                                    ['title' => 'Nieuwe functie', 'message' => 'Je kunt nu je wachtwoord wijzigen in je profiel', 'time' => '1 dag geleden', 'type' => 'info'],
                                    ['title' => 'Aankomende betaling', 'message' => 'Huur €1200,00 wordt morgen afgeschreven', 'time' => '2 dagen geleden', 'type' => 'reminder']
                                ];
                            }
                        }
                        
                        foreach ($notifications as $notification):
                            $bgColor = 'bg-gray-50';
                            $iconColor = 'text-blue-500';
                            
                            if (isset($notification['type'])) {
                                if ($notification['type'] === 'warning') {
                                    $iconColor = 'text-yellow-500';
                                } elseif ($notification['type'] === 'error') {
                                    $iconColor = 'text-red-500';
                                } elseif ($notification['type'] === 'success') {
                                    $iconColor = 'text-green-500';
                                }
                            }
                        ?>
                        <a href="/notifications" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                            <div class="flex">
                                <div class="<?= $iconColor ?> mr-3">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($notification['title'] ?? '') ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($notification['message'] ?? '') ?></p>
                                    <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($notification['time'] ?? '') ?></p>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        
                        <?php if (empty($notifications)): ?>
                            <div class="px-4 py-3 text-sm text-gray-500 text-center">
                                Geen nieuwe notificaties
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Gebruikersmenu -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-2 hover:bg-gray-100 p-2 rounded-full focus:outline-none">
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                            <?= !empty($username) ? strtoupper(substr($username, 0, 1)) : 'G' ?>
                        </div>
                    </button>
                    
                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10" style="display: none;">
                        <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Profiel
                        </a>
                        <a href="/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Uitloggen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header> 