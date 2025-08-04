<?php
// Debug informatie toevoegen
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bepaal huidige pagina
$currentPage = '';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$base = strtok($uri, '?');

// Schrijf debug informatie naar een logbestand
file_put_contents('/Users/dimitry/Projecten/financieel-beheer/public/debug.log', 
                 "URI: $uri, Base: $base, Time: " . date('Y-m-d H:i:s') . "\n", 
                 FILE_APPEND);

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
} elseif (strpos($base, '/insights') === 0) {
    $currentPage = 'insights';
} else {
    $currentPage = 'dashboard'; // Standaard naar dashboard als geen match
}

// Debug informatie toevoegen aan logbestand
file_put_contents('/Users/dimitry/Projecten/financieel-beheer/public/debug.log', 
                 "Current Page: $currentPage\n", 
                 FILE_APPEND);

// Importeer benodigde namespaces
use App\Core\Auth;
use App\Models\Notification;

// Haal aantal ongelezen notificaties op
$unreadNotificationCount = 0;
if (class_exists('\\App\\Models\\Notification') && method_exists('\\App\\Models\\Notification', 'getUnreadCount') && Auth::check()) {
    $unreadNotificationCount = Notification::getUnreadCount(Auth::id());
    
    // Debug informatie toevoegen aan logbestand
    file_put_contents('/Users/dimitry/Projecten/financieel-beheer/public/debug.log', 
                     "Notification count: $unreadNotificationCount\n", 
                     FILE_APPEND);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Financieel Beheer' ?></title>
    <!-- Voorkom 404 favicon.ico fouten -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💰</text></svg>">
    <!-- Voeg Tailwind CSS toe via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Als backup ook de CSS direct laden -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Material Icons - Belangrijk voor de icoontjes -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Zorg ervoor dat alle styles goed worden toegepast -->
    <style>
        .sidebar-active {
            background-color: #EBF5FF;
            color: #3B82F6;
        }
        .md\:flex {
            display: flex !important;
        }
        @media (min-width: 768px) {
            .md\:flex {
                display: flex !important;
            }
            .md\:hidden {
                display: none !important;
            }
        }
        /* Material icons styling */
        .material-icons {
            font-size: 24px;
            line-height: 1;
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="flex h-screen bg-gray-100">
    <!-- Zijbalk navigatie -->
    <div class="hidden md:flex md:flex-shrink-0">
        <div class="flex flex-col w-64 bg-white border-r">
            <div class="flex items-center justify-center h-16 px-4 bg-blue-600">
                <span class="text-xl font-semibold text-white">Financieel Beheer</span>
            </div>
            <div class="flex flex-col flex-grow px-4 py-4 overflow-y-auto">
                <nav class="flex-1 space-y-2">
                    <!-- Dashboard -->
                    <a href="/" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'dashboard' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        Dashboard
                    </a>

                    <!-- Inzichten -->
                    <a href="/insights" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'insights' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Inzichten
                    </a>

                    <!-- Transacties -->
                    <a href="/transactions" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'transactions' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Transacties
                    </a>

                    <!-- Accounts/Rekeningen -->
                    <a href="/accounts" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'accounts' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        Rekeningen
                    </a>

                    <!-- Categorieën -->
                    <a href="/categories" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'categories' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Categorieën
                    </a>

                    <!-- Budgetten -->
                    <a href="/budgets" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'budgets' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Budgetten
                    </a>

                    <!-- Spaardoelen -->
                    <a href="/savings" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'savings' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Spaardoelen
                    </a>

                    <!-- Terugkerende Transacties -->
                    <a href="/recurring" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'recurring' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Terugkerende
                    </a>

                    <!-- Rapporten -->
                    <a href="/reports" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'reports' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Rapporten
                    </a>

                    <!-- Exporteren -->
                    <a href="/export" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'export' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Exporteren
                    </a>

                    <!-- Scheidingslijn -->
                    <div class="border-t border-gray-200 my-2"></div>

                    <!-- Notificaties -->
                    <a href="/notifications" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'notifications' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        Notificaties
                        <?php if ($unreadNotificationCount > 0): ?>
                        <span class="ml-auto inline-flex items-center justify-center h-5 w-5 text-xs font-medium text-white bg-red-500 rounded-full"><?= $unreadNotificationCount ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Gebruikersprofiel -->
                    <a href="/profile" class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-100 <?= $currentPage === 'profile' ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Mijn profiel
                    </a>

                    <!-- Uitloggen (onderaan de sidebar) -->
                    <div class="mt-auto pt-4">
                        <a href="/logout" class="flex items-center px-4 py-2 text-sm font-medium text-red-600 rounded-md hover:bg-red-50">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Uitloggen
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </div>

    <!-- Mobiel menu (hamburger) -->
    <div class="md:hidden bg-white border-b border-gray-200">
        <div class="flex items-center justify-between h-16 px-4">
            <div class="flex items-center">
                <button id="sidebar-toggle" class="mr-2 text-gray-500 hover:text-gray-600 focus:outline-none focus:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <span class="text-lg font-semibold text-blue-600">Financieel Beheer</span>
            </div>
            <div class="flex items-center">
                <a href="/notifications" class="p-2 text-gray-400 hover:text-gray-500 relative">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <?php if ($unreadNotificationCount > 0): ?>
                    <span class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-red-500 text-white text-xs text-center"><?= $unreadNotificationCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobiel menu dropdown (verborgen standaard) -->
    <div id="mobile-menu" class="hidden md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-b border-gray-200">
            <a href="/" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'dashboard' ? 'bg-blue-50 text-blue-600' : '' ?>">Dashboard</a>
            <a href="/transactions" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'transactions' ? 'bg-blue-50 text-blue-600' : '' ?>">Transacties</a>
            <a href="/accounts" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'accounts' ? 'bg-blue-50 text-blue-600' : '' ?>">Rekeningen</a>
            <a href="/categories" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'categories' ? 'bg-blue-50 text-blue-600' : '' ?>">Categorieën</a>
            <a href="/budgets" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'budgets' ? 'bg-blue-50 text-blue-600' : '' ?>">Budgetten</a>
            <a href="/savings" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'savings' ? 'bg-blue-50 text-blue-600' : '' ?>">Spaardoelen</a>
            <a href="/recurring" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'recurring' ? 'bg-blue-50 text-blue-600' : '' ?>">Terugkerende Transacties</a>
            <a href="/reports" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'reports' ? 'bg-blue-50 text-blue-600' : '' ?>">Rapporten</a>
            <a href="/export" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'export' ? 'bg-blue-50 text-blue-600' : '' ?>">Exporteren</a>
            <a href="/profile" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 <?= $currentPage === 'profile' ? 'bg-blue-50 text-blue-600' : '' ?>">Mijn profiel</a>
            <a href="/logout" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-red-50">Uitloggen</a>
        </div>
    </div>

    <!-- Hoofdinhoud -->
    <div class="flex flex-col flex-1 overflow-hidden">
        <main class="flex-1 overflow-y-auto focus:outline-none">
            <div class="py-6">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                    <?= $content ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Toggle mobiel menu
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.remove('hidden');
        } else {
            mobileMenu.classList.add('hidden');
        }
    });

    // Extra JavaScript om de zijbalk te forceren
    document.addEventListener('DOMContentLoaded', function() {
        // Zorg ervoor dat de zijbalk altijd zichtbaar is op grotere schermen
        const sidebar = document.querySelector('.md\\:flex.md\\:flex-shrink-0');
        if (sidebar) {
            if (window.innerWidth >= 768) {
                sidebar.style.display = 'flex';
                sidebar.classList.remove('hidden');
            }
        }

        // Actieve menu-item highlighten
        const currentPath = window.location.pathname;
        const menuItems = document.querySelectorAll('a[href]');
        menuItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href === currentPath || 
                (href === '/' && currentPath === '/dashboard') ||
                (href !== '/' && currentPath.startsWith(href))) {
                item.classList.add('sidebar-active');
            }
        });
    });
</script>
</body>
</html> 