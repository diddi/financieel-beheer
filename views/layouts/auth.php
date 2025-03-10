<?php
/**
 * Layout voor authenticatie pagina's (login, registratie, wachtwoord vergeten)
 */

// Bepaal de paginatitel (kan worden overschreven door de controller)
$pageTitle = $pageTitle ?? 'Login';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financieel Beheer - <?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        /* Custom styling voor authenticatie pagina's */
        .auth-bg-pattern {
            background-color: #f9fafb;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%23d1d5db' fill-opacity='0.2'%3E%3Cpath opacity='.5' d='M96 95h4v1h-4v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4h-9v4h-1v-4H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15v-9H0v-1h15V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h9V0h1v15h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9h4v1h-4v9zm-1 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm9-10v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-10 0v-9h-9v9h9zm-9-10h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9zm10 0h9v-9h-9v9z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .auth-card {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .auth-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.2), 0 10px 15px -5px rgba(0, 0, 0, 0.1);
        }
        
        /* Input focus effect */
        .auth-input:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        /* Fix voor Alpine.js */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="auth-bg-pattern min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Logo/branding -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-blue-600">Financieel Beheer</h1>
            <p class="mt-2 text-sm text-gray-600">
                Beheer al je financiën op één plek
            </p>
        </div>

        <!-- Main auth card with content -->
        <div class="bg-white rounded-lg p-8 auth-card">
            <!-- Page content -->
            <?php if (isset($content)): ?>
                <?= $content ?>
            <?php else: ?>
                <!-- Fallback content als er geen content is ingesteld -->
                <div class="text-center py-6">
                    <div class="text-xl font-medium text-gray-900 mb-2">
                        <?= htmlspecialchars($pageTitle) ?>
                    </div>
                    <p class="text-gray-500">
                        Er is geen inhoud beschikbaar op dit moment.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer links -->
        <div class="text-center text-sm text-gray-500">
            <a href="/privacy" class="hover:text-blue-600">Privacy</a>
            <span class="mx-2">&middot;</span>
            <a href="/terms" class="hover:text-blue-600">Voorwaarden</a>
            <span class="mx-2">&middot;</span>
            <a href="/help" class="hover:text-blue-600">Help</a>
        </div>
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
