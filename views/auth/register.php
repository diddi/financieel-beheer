<?php ob_start(); ?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Maak een nieuw account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Of
                <a href="/login" class="font-medium text-blue-600 hover:text-blue-500">
                    log in op je bestaande account
                </a>
            </p>
        </div>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($errors['general']) ?></span>
            </div>
        <?php endif; ?>
        
        <form class="mt-8 space-y-6" action="/register" method="POST">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="username" class="sr-only">Gebruikersnaam</label>
                    <input id="username" name="username" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Gebruikersnaam" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <?php if (!empty($errors['username'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['username']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="email" class="sr-only">E-mailadres</label>
                    <input id="email" name="email" type="email" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="E-mailadres" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="password" class="sr-only">Wachtwoord</label>
                    <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Wachtwoord">
                    <?php if (!empty($errors['password'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['password']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="password_confirm" class="sr-only">Bevestig wachtwoord</label>
                    <input id="password_confirm" name="password_confirm" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" placeholder="Bevestig wachtwoord">
                    <?php if (!empty($errors['password_confirm'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['password_confirm']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Registreren
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean();
include __DIR__ . '/../layouts/auth.php';
?>
