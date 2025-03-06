<?php
/**
 * Gemeenschappelijke navigatiecomponent voor alle pagina's
 * 
 * @param string $currentPage De huidige pagina om de juiste menu-item te highlighten
 */

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
?>

<nav class='bg-blue-600 text-white shadow-lg'>
    <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
        <div class='flex justify-between h-16'>
            <div class='flex'>
                <div class='flex-shrink-0 flex items-center'>
                    <a href='/' class='text-xl font-bold'>Financieel Beheer</a>
                </div>
                <div class='ml-6 flex items-center space-x-4'>
                    <a href='/' class='px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'dashboard' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>'>Dashboard</a>
                    <a href='/transactions' class='px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'transactions' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>'>Transacties</a>
                    <a href='/accounts' class='px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'accounts' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>'>Rekeningen</a>
                    <a href='/categories' class='px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'categories' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>'>Categorieën</a>
                    <a href='/budgets' class='px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'budgets' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>'>Budgetten</a>
                    <a href='/reports' class='px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'reports' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>'>Rapportages</a>
                    <a href='/savings' class='px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'savings' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>'>Spaardoelen</a>
                    <a href='/export' class='px-3 py-2 rounded-md text-sm font-medium <?= $currentPage === 'export' ? 'bg-blue-700' : 'hover:bg-blue-700' ?>'>Exporteren</a>
                </div>
            </div>
            <div class='flex items-center'>
                <?php if (!empty($username)): ?>
                    <span class='mr-4 text-sm'><?= htmlspecialchars($username) ?></span>
                <?php endif; ?>
                <a href='/logout' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Uitloggen</a>
            </div>
        </div>
    </div>
</nav>
