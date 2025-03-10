<?php
/**
 * Gemeenschappelijke navigatiecomponent voor alle pagina's
 * 
 * @param string $currentPage De huidige pagina om de juiste menu-item te highlighten
 */

// Import the Auth class with its namespace
use App\Core\Auth;
use App\Models\Notification;

// Bepaal huidige pagina als deze niet is doorgegeven
if (!isset($currentPage)) {
    $uri = $_SERVER['REQUEST_URI'];
    $base = strtok($uri, '?');
    
    if ($base === '/' || $base === '/dashboard') {
        $currentPage = 'dashboard';
    } elseif (strpos($base, '/transactions') === 0) {
        $currentPage = 'transactions';
    } elseif (strpos($base, '/accounts') === 0) {
        $currentPage = 'accounts';
    } elseif (strpos($base, '/categories') === 0) {
        $currentPage = 'categories';
    } elseif (strpos($base, '/budgets') === 0) {
        $currentPage = 'budgets';
    } elseif (strpos($base, '/reports') === 0) {
        $currentPage = 'reports';
    } elseif (strpos($base, '/savings') === 0) {
        $currentPage = 'savings';
    } elseif (strpos($base, '/export') === 0) {
        $currentPage = 'export';
    } elseif (strpos($base, '/notifications') === 0) {
        $currentPage = 'notifications';
    } elseif (strpos($base, '/recurring') === 0) {
        $currentPage = 'recurring';
    } else {
        $currentPage = '';
    }
}

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
?>

<nav class="bg-blue-600 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo & Mobiele menuknop -->
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <a href="/" class="text-xl font-bold">Financieel Beheer</a>
                </div>
                <!-- Hamburger menu knop voor mobiel -->
                <div class="flex md:hidden ml-4">
                    <button id="mobile-menu-button" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-blue-700 focus:outline-none" aria-expanded="false">
                        <span class="sr-only">Menu openen</span>
                        <!-- Icon wanneer menu gesloten is -->
                        <svg id="menu-closed-icon" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <!-- Icon wanneer menu open is -->
                        <svg id="menu-open-icon" class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Desktop navigatiemenu -->
            <div class="hidden md:flex md:items-center">
                <div class="flex space-x-2">
                    <a href="/" class="px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'dashboard' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>">Dashboard</a>
                    
                    <!-- Dropdown: Financiën -->
                    <div class="relative group">
                        <button class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 inline-flex items-center">
                            <span>Financiën</span>
                            <svg class="ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden group-hover:block z-10">
                            <div class="py-1">
                                <a href="/transactions" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?= $currentPage === 'transactions' ? 'bg-gray-100' : '' ?>">Transacties</a>
                                <a href="/accounts" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?= $currentPage === 'accounts' ? 'bg-gray-100' : '' ?>">Rekeningen</a>
                                <a href="/categories" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?= $currentPage === 'categories' ? 'bg-gray-100' : '' ?>">Categorieën</a>
                                <a href="/recurring" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?= $currentPage === 'recurring' ? 'bg-gray-100' : '' ?>">Terugkerend</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dropdown: Planning -->
                    <div class="relative group">
                        <button class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 inline-flex items-center">
                            <span>Planning</span>
                            <svg class="ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden group-hover:block z-10">
                            <div class="py-1">
                                <a href="/budgets" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?= $currentPage === 'budgets' ? 'bg-gray-100' : '' ?>">Budgetten</a>
                                <a href="/savings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?= $currentPage === 'savings' ? 'bg-gray-100' : '' ?>">Spaardoelen</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Directe links -->
                    <a href="/reports" class="px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'reports' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>">Rapportages</a>
                    <a href="/export" class="px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'export' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>">Exporteren</a>
                    
                    <!-- Notificaties -->
                    <a href="/notifications" class="px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'notifications' ? 'bg-blue-700' : 'hover:bg-blue-700' ?> relative">
                        <span>Notificaties</span>
                        <?php if ($unreadNotificationCount > 0): ?>
                            <span class="absolute top-1 right-1 inline-block w-4 h-4 text-xs bg-red-500 text-white rounded-full flex items-center justify-center">
                                <?= $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            
            <!-- Gebruikersmenu (desktop) -->
            <div class="hidden md:flex md:items-center">
                <?php if (!empty($username)): ?>
                    <div class="relative ml-3 group">
                        <button class="flex items-center text-sm font-medium text-white hover:bg-blue-700 px-3 py-2 rounded-md">
                            <span><?= htmlspecialchars($username) ?></span>
                            <svg class="ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden group-hover:block z-10">
                            <div class="py-1">
                                <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profiel</a>
                                <a href="/logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Uitloggen</a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700">Inloggen</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Mobiel menu -->
    <div id="mobile-menu" class="hidden md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="/" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'dashboard' ? 'bg-blue-700' : 'text-white hover:bg-blue-700' ?>">Dashboard</a>
            
            <!-- Financiën sectie -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="w-full flex justify-between items-center px-3 py-2 rounded-md text-base font-medium text-white hover:bg-blue-700">
                    <span>Financiën</span>
                    <svg :class="{'transform rotate-180': open}" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" class="px-4 py-2 space-y-1">
                    <a href="/transactions" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'transactions' ? 'bg-blue-700' : 'text-white hover:bg-blue-600' ?>">Transacties</a>
                    <a href="/accounts" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'accounts' ? 'bg-blue-700' : 'text-white hover:bg-blue-600' ?>">Rekeningen</a>
                    <a href="/categories" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'categories' ? 'bg-blue-700' : 'text-white hover:bg-blue-600' ?>">Categorieën</a>
                    <a href="/recurring" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'recurring' ? 'bg-blue-700' : 'text-white hover:bg-blue-600' ?>">Terugkerend</a>
                </div>
            </div>
            
            <!-- Planning sectie -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="w-full flex justify-between items-center px-3 py-2 rounded-md text-base font-medium text-white hover:bg-blue-700">
                    <span>Planning</span>
                    <svg :class="{'transform rotate-180': open}" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" class="px-4 py-2 space-y-1">
                    <a href="/budgets" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'budgets' ? 'bg-blue-700' : 'text-white hover:bg-blue-600' ?>">Budgetten</a>
                    <a href="/savings" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'savings' ? 'bg-blue-700' : 'text-white hover:bg-blue-600' ?>">Spaardoelen</a>
                </div>
            </div>
            
            <a href="/reports" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'reports' ? 'bg-blue-700' : 'text-white hover:bg-blue-700' ?>">Rapportages</a>
            <a href="/export" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'export' ? 'bg-blue-700' : 'text-white hover:bg-blue-700' ?>">Exporteren</a>
            <a href="/notifications" class="block px-3 py-2 rounded-md text-base font-medium <?= $currentPage === 'notifications' ? 'bg-blue-700' : 'text-white hover:bg-blue-700' ?> relative">
                Notificaties
                <?php if ($unreadNotificationCount > 0): ?>
                    <span class="inline-block ml-1 w-4 h-4 text-xs bg-red-500 text-white rounded-full flex items-center justify-center">
                        <?= $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount ?>
                    </span>
                <?php endif; ?>
            </a>
            
            <!-- Gebruiker opties (mobiel) -->
            <?php if (!empty($username)): ?>
                <div class="pt-4 pb-3 border-t border-blue-700">
                    <div class="px-3 py-2 text-white font-medium"><?= htmlspecialchars($username) ?></div>
                    <div class="px-2 space-y-1">
                        <a href="/profile" class="block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-blue-700">Profiel</a>
                        <a href="/logout" class="block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-blue-700">Uitloggen</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login" class="block px-3 py-2 rounded-md text-base font-medium text-white hover:bg-blue-700">Inloggen</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Alpine.js voor interactief mobiel menu -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
    // Mobiel menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuClosedIcon = document.getElementById('menu-closed-icon');
        const menuOpenIcon = document.getElementById('menu-open-icon');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
                menuClosedIcon.classList.toggle('hidden');
                menuOpenIcon.classList.toggle('hidden');
            });
        }
    });
</script>