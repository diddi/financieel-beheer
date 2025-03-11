<?php
/**
 * Hoofd layout voor de applicatie
 */

// Controleer of de gebruiker is ingelogd
use App\Core\Auth;

// Verbeterde redirectlogica om oneindige redirects te voorkomen
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$authPaths = ['/login', '/register', '/forgot-password', '/reset-password'];

if (!Auth::check() && !in_array($currentPath, $authPaths)) {
    header('Location: /login');
    exit;
}

// Bepaal de paginatitel (kan worden overschreven door de controller)
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Financieel Beheer">
    <meta name="theme-color" content="#2563EB">
    <title>Financieel Beheer - <?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Verhinder dat telefoonnummers automatisch worden herkend -->
    <meta name="format-detection" content="telephone=no">
    
    <!-- App icon voor iOS -->
    <link rel="apple-touch-icon" href="/assets/images/app-icon.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js (voor grafieken) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Custom styling -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #666;
        }
        
        /* Dashboard card hover effect */
        .dashboard-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Material icons styling */
        .material-icons {
            font-size: 24px;
            line-height: 1;
            vertical-align: middle;
        }
        
        /* iOS specifieke styling */
        @supports (-webkit-touch-callout: none) {
            /* Voorkom dat iOS elementen een highlight flash geeft bij het tikken */
            a, button {
                -webkit-tap-highlight-color: transparent;
            }
            
            /* Voorkom dat formulierelementen op iOS gekke styling krijgen */
            input, select, textarea {
                -webkit-appearance: none;
                border-radius: 4px;
            }
            
            /* Fix voor position:fixed elementen op iOS */
            .ios-fixed {
                -webkit-transform: translateZ(0);
            }
        }
    </style>
</head>
<body class="antialiased bg-gray-100 text-gray-900">
    <div class="min-h-screen flex flex-col">
        
        <?php 
        // Include de navigatie balk
        include_once(dirname(__DIR__) . '/components/navigation.php'); 
        ?>
        
        <?php if (!empty($authError)): ?>
        <div class="w-full bg-red-600 p-4 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="h-6 w-6 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><?= htmlspecialchars($authError) ?></span>
                </div>
                <a href="/login" class="bg-white text-red-600 hover:bg-red-50 px-4 py-2 rounded text-sm font-medium">Opnieuw inloggen</a>
            </div>
        </div>
        <?php endif; ?>
        
        <main class="flex-1">
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <?php if (isset($content)): ?>
                    <?= $content ?>
                <?php else: ?>
                    <!-- Fallback content -->
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-5" role="alert">
                        <p class="font-bold">Let op</p>
                        <p>De opgevraagde inhoud kon niet worden geladen.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4 md:ml-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        &copy; <?= date('Y') ?> Financieel Beheer
                    </div>
                    <div class="text-sm text-gray-500">
                        <a href="#" class="hover:text-gray-700">Privacy</a>
                        <span class="mx-2">|</span>
                        <a href="#" class="hover:text-gray-700">Voorwaarden</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Flash messages -->
    <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
    <div id="flash-message" class="fixed bottom-4 right-4 max-w-sm z-50">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-2 rounded shadow-md">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-2 rounded shadow-md">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-hide flash messages after 5 seconds
        setTimeout(function() {
            const flashMessage = document.getElementById('flash-message');
            if (flashMessage) {
                flashMessage.style.opacity = '0';
                flashMessage.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    flashMessage.remove();
                }, 500);
            }
        }, 5000);
    </script>
    <?php endif; ?>
</body>
</html> 