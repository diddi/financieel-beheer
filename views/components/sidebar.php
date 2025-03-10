<?php
/**
 * Sidebar navigatiecomponent voor alle pagina's
 * 
 * @param string $currentPage De huidige pagina om de juiste menu-item te highlighten
 */

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
    } elseif (strpos($base, '/profile') === 0) {
        $currentPage = 'profile';
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

<!-- Sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 w-64 h-full bg-white text-gray-800 shadow-lg transition-all duration-300 z-30">
    <!-- Logo -->
    <div class="h-16 flex items-center justify-center border-b border-gray-200">
        <a href="/" class="text-xl font-bold text-blue-600">Financieel Beheer</a>
    </div>

    <!-- User Profile Section -->
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-lg">
                <?= !empty($username) ? strtoupper(substr($username, 0, 1)) : 'G' ?>
            </div>
            <div class="ml-3">
                <div class="font-medium"><?= htmlspecialchars($username ?: 'Gast') ?></div>
                <a href="/profile" class="text-xs text-blue-500 hover:text-blue-700">Profiel bekijken</a>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="py-4 px-4 overflow-y-auto h-[calc(100%-180px)]">
        <ul>
            <!-- Dashboard -->
            <li class="mb-1">
                <a href="/" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'dashboard' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Financiën Section -->
            <li class="mb-1">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider pl-4 mt-4 mb-2">Financiën</div>
                
                <a href="/transactions" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'transactions' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Transacties</span>
                </a>
            </li>
            
            <li class="mb-1">
                <a href="/accounts" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'accounts' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span>Rekeningen</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="/categories" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'categories' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>Categorieën</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="/recurring" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'recurring' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Terugkerend</span>
                </a>
            </li>

            <!-- Planning Section -->
            <li class="mb-1">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider pl-4 mt-4 mb-2">Planning</div>
                
                <a href="/budgets" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'budgets' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Budgetten</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="/savings" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'savings' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                    <span>Spaardoelen</span>
                </a>
            </li>

            <!-- Analyse & Rapporten -->
            <li class="mb-1">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider pl-4 mt-4 mb-2">Analyse</div>
                
                <a href="/reports" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'reports' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Rapportages</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="/export" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'export' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Exporteren</span>
                </a>
            </li>

            <!-- Notificaties -->
            <li class="mb-1">
                <a href="/notifications" class="flex items-center py-2 px-4 rounded-md <?= $currentPage === 'notifications' ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                    <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="relative">
                        Notificaties
                        <?php if ($unreadNotificationCount > 0): ?>
                            <span class="absolute -right-2 -top-1 w-5 h-5 flex items-center justify-center bg-red-500 text-white text-xs rounded-full">
                                <?= $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Logout button at bottom -->
    <div class="absolute bottom-0 w-full border-t border-gray-200">
        <a href="/logout" class="flex items-center py-4 px-6 text-gray-700 hover:bg-gray-100">
            <svg class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span>Uitloggen</span>
        </a>
    </div>
</aside>

<!-- Mobile sidebar toggle button and overlay -->
<div class="md:hidden fixed top-4 left-4 z-40 ios-fixed">
    <button id="sidebar-toggle" class="bg-blue-600 text-white p-2 rounded-md shadow-lg">
        <svg id="sidebar-open-icon" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <svg id="sidebar-close-icon" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>

<div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-0 pointer-events-none transition-opacity duration-300 md:hidden z-20 ios-fixed"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarOpenIcon = document.getElementById('sidebar-open-icon');
        const sidebarCloseIcon = document.getElementById('sidebar-close-icon');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        // On mobile, start with sidebar hidden
        if (window.innerWidth < 768) {
            sidebar.classList.add('-translate-x-full');
        }
        
        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOpenIcon.classList.toggle('hidden');
            sidebarCloseIcon.classList.toggle('hidden');
            
            // Toggle overlay
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebarOverlay.classList.add('opacity-0', 'pointer-events-none');
                sidebarOverlay.classList.remove('opacity-50', 'pointer-events-auto');
            } else {
                sidebarOverlay.classList.add('opacity-50', 'pointer-events-auto');
                sidebarOverlay.classList.remove('opacity-0', 'pointer-events-none');
            }
        }
        
        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.add('opacity-0', 'pointer-events-none');
                sidebarOverlay.classList.remove('opacity-50', 'pointer-events-auto');
            } else if (!sidebarCloseIcon.classList.contains('hidden')) {
                sidebar.classList.add('-translate-x-full');
            }
        });
    });
</script> 