<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;

class AuthController extends Controller {
    public function login() {
        // Als gebruiker al is ingelogd, doorverwijzen naar dashboard
        if (Auth::check()) {
            header('Location: /');
            exit;
        }
        
        $error = '';
        
        // Verwerk login formulier
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Vul je e-mailadres en wachtwoord in';
            } else {
                $success = Auth::attempt($email, $password);
                
                if ($success) {
                    // Doorverwijzen naar dashboard na succesvolle login
                    $redirectUrl = $_SESSION['redirect_url'] ?? '/';
                    unset($_SESSION['redirect_url']);
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $error = 'Ongeldige inloggegevens';
                }
            }
        }
        
        // Render login pagina
        $render = $this->startBuffering('Inloggen', true);
        
        // Begin HTML output
        echo "
        <div class='flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8'>
            <div class='sm:mx-auto sm:w-full sm:max-w-md'>
                <h2 class='mt-6 text-center text-3xl font-bold tracking-tight text-gray-900'>
                    Inloggen bij Financieel Beheer
                </h2>
                <p class='mt-2 text-center text-sm text-gray-600'>
                    Of <a href='/register' class='font-medium text-blue-600 hover:text-blue-500'>maak een nieuw account aan</a>
                </p>
            </div>

            <div class='mt-8 sm:mx-auto sm:w-full sm:max-w-md'>
                <div class='bg-white px-4 py-8 shadow sm:rounded-lg sm:px-10'>
                    " . (!empty($error) ? "<div class='rounded-md bg-red-50 p-4 mb-4'>
                        <div class='flex'>
                            <div class='ml-3'>
                                <h3 class='text-sm font-medium text-red-800'>
                                    {$error}
                                </h3>
                            </div>
                        </div>
                    </div>" : "") . "
                
                    <form class='space-y-6' action='/login' method='POST'>
                        <div>
                            <label for='email' class='block text-sm font-medium text-gray-700'>
                                E-mailadres
                            </label>
                            <div class='mt-1'>
                                <input id='email' name='email' type='email' autocomplete='email' required
                                    class='block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm'
                                    value='" . htmlspecialchars($_POST['email'] ?? '') . "'>
                            </div>
                        </div>

                        <div>
                            <label for='password' class='block text-sm font-medium text-gray-700'>
                                Wachtwoord
                            </label>
                            <div class='mt-1'>
                                <input id='password' name='password' type='password' autocomplete='current-password' required
                                    class='block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm'>
                            </div>
                        </div>

                        <div class='flex items-center justify-between'>
                            <div class='flex items-center'>
                                <input id='remember_me' name='remember_me' type='checkbox'
                                    class='h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500'>
                                <label for='remember_me' class='ml-2 block text-sm text-gray-900'>
                                    Onthoud mij
                                </label>
                            </div>

                            <div class='text-sm'>
                                <a href='/forgot-password' class='font-medium text-blue-600 hover:text-blue-500'>
                                    Wachtwoord vergeten?
                                </a>
                            </div>
                        </div>

                        <div>
                            <button type='submit'
                                class='flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'>
                                Inloggen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>";
        
        // Render de pagina met auth layout
        $render();
    }
    
    public function register() {
        $errors = [];
        
        // Verwerk registratie formulier
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            
            // Validatie
            if (empty($username)) {
                $errors['username'] = 'Gebruikersnaam is verplicht';
            }
            
            if (empty($email)) {
                $errors['email'] = 'E-mailadres is verplicht';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Ongeldig e-mailadres';
            }
            
            if (empty($password)) {
                $errors['password'] = 'Wachtwoord is verplicht';
            } elseif (strlen($password) < 8) {
                $errors['password'] = 'Wachtwoord moet minimaal 8 tekens bevatten';
            }
            
            if ($password !== $passwordConfirm) {
                $errors['password_confirm'] = 'Wachtwoorden komen niet overeen';
            }
            
            // Als er geen fouten zijn, registreer de gebruiker
            if (empty($errors)) {
                try {
                    $userId = Auth::register([
                        'username' => $username,
                        'email' => $email,
                        'password' => $password
                    ]);
                    
                    // Maak standaard rekeningen en categorieën aan
                    Account::createDefaultAccounts($userId);
                    Category::createDefaultCategories($userId);
                    
                    // Automatisch inloggen
                    Auth::attempt($email, $password);
                    
                    // Redirect naar dashboard
                    header('Location: /');
                    exit;
                } catch (\Exception $e) {
                    $errors['general'] = $e->getMessage();
                }
            }
        }
        
        // Paginatitel instellen
        $pageTitle = 'Registreren';
        
        // Start output buffering
        ob_start();
        
        // Toon registratie formulier
        ?>
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Account aanmaken</h2>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?= $errors['general'] ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="/register" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Gebruikersnaam</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 auth-input p-2 border" required>
                <?php if (!empty($errors['username'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $errors['username'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">E-mailadres</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 auth-input p-2 border" required>
                <?php if (!empty($errors['email'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $errors['email'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Wachtwoord</label>
                <input type="password" id="password" name="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 auth-input p-2 border" required>
                <?php if (!empty($errors['password'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $errors['password'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="password_confirm" class="block text-sm font-medium text-gray-700">Bevestig wachtwoord</label>
                <input type="password" id="password_confirm" name="password_confirm" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 auth-input p-2 border" required>
                <?php if (!empty($errors['password_confirm'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $errors['password_confirm'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Account aanmaken
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Heb je al een account? 
                <a href="/login" class="font-medium text-blue-600 hover:text-blue-500">
                    Inloggen
                </a>
            </p>
        </div>
        <?php
        
        // Vang de content op
        $content = ob_get_clean();
        
        // Toon de view met auth layout via de helper functie
        renderWithAuthLayout($content, 'Registreren');
    }
    
    public function logout() {
        Auth::logout();
        header('Location: /login');
        exit;
    }
    
    /**
     * Toon profielpagina
     */
    public function profile() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        // Haal gebruikersgegevens op
        $user = Auth::user();
        
        // Controleer of gebruikersgegevens geldig zijn
        if (!$user || !is_array($user)) {
            // Log de fout voor debugging
            error_log('Fout bij ophalen gebruikersprofiel: gebruikersgegevens niet beschikbaar');
            
            // Toon een foutmelding aan de gebruiker
            Session::set('auth_error', 'Er is een probleem opgetreden bij het ophalen van je profielgegevens. Probeer opnieuw in te loggen.');
            header('Location: /logout');
            exit;
        }
        
        // Start output buffering
        ob_start();
        
        // Toon profielpagina
        ?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h1 class="text-2xl font-bold mb-6">Mijn Profiel</h1>
                
                <?php if (Session::has('profile_success')): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <p><?= Session::get('profile_success') ?></p>
                    </div>
                    <?php Session::forget('profile_success'); ?>
                <?php endif; ?>
                
                <?php if (Session::has('password_success')): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <p><?= Session::get('password_success') ?></p>
                    </div>
                    <?php Session::forget('password_success'); ?>
                <?php endif; ?>
                
                <!-- Profiel bijwerken formulier -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">Profiel bijwerken</h2>
                    
                    <?php if (Session::has('profile_errors') && isset(Session::get('profile_errors')['general'])): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p><?= Session::get('profile_errors')['general'] ?></p>
                        </div>
                        <?php Session::forget('profile_errors'); ?>
                    <?php endif; ?>
                    
                    <form method="post" action="/profile/update" class="space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Gebruikersnaam</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <?php if (Session::has('profile_errors') && isset(Session::get('profile_errors')['username'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?= Session::get('profile_errors')['username'] ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">E-mailadres</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <?php if (Session::has('profile_errors') && isset(Session::get('profile_errors')['email'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?= Session::get('profile_errors')['email'] ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Profiel bijwerken
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Wachtwoord wijzigen formulier -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">Wachtwoord wijzigen</h2>
                    
                    <?php if (Session::has('password_errors') && isset(Session::get('password_errors')['general'])): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p><?= Session::get('password_errors')['general'] ?></p>
                        </div>
                        <?php Session::forget('password_errors'); ?>
                    <?php endif; ?>
                    
                    <form method="post" action="/profile/change-password" class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Huidig wachtwoord</label>
                            <input type="password" id="current_password" name="current_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <?php if (Session::has('password_errors') && isset(Session::get('password_errors')['current_password'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?= Session::get('password_errors')['current_password'] ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">Nieuw wachtwoord</label>
                            <input type="password" id="new_password" name="new_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <?php if (Session::has('password_errors') && isset(Session::get('password_errors')['new_password'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?= Session::get('password_errors')['new_password'] ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Bevestig nieuw wachtwoord</label>
                            <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <?php if (Session::has('password_errors') && isset(Session::get('password_errors')['confirm_password'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?= Session::get('password_errors')['confirm_password'] ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Wachtwoord wijzigen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        
        // Vang de gebufferde content
        $content = ob_get_clean();
        
        // Render the view met onze nieuwe app layout via de helper functie
        renderWithAppLayout($content, 'Mijn profiel');
    }
    
    /**
     * Verwerk profielupdate
     */
    public function updateProfile() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        // Controleer of het een POST-verzoek is
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile');
            exit;
        }
        
        $userId = Auth::id();
        if (!$userId) {
            // Log de fout voor debugging
            error_log('Fout bij profiel bijwerken: gebruikers-ID niet beschikbaar');
            
            // Toon een foutmelding aan de gebruiker
            Session::set('auth_error', 'Er is een probleem opgetreden bij het ophalen van je gebruikersgegevens. Probeer opnieuw in te loggen.');
            header('Location: /logout');
            exit;
        }
        
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $errors = [];
        
        // Validatie
        if (empty($username)) {
            $errors['username'] = 'Gebruikersnaam is verplicht';
        }
        
        if (empty($email)) {
            $errors['email'] = 'E-mailadres is verplicht';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ongeldig e-mailadres';
        }
        
        // Controleer of e-mailadres al in gebruik is door een andere gebruiker
        $existingUser = User::getByEmail($email);
        if ($existingUser && $existingUser['id'] != $userId) {
            $errors['email'] = 'E-mailadres is al in gebruik';
        }
        
        // Controleer of gebruikersnaam al in gebruik is door een andere gebruiker
        $existingUser = User::getByUsername($username);
        if ($existingUser && $existingUser['id'] != $userId) {
            $errors['username'] = 'Gebruikersnaam is al in gebruik';
        }
        
        // Als er fouten zijn, toon ze en redirect terug
        if (!empty($errors)) {
            Session::set('profile_errors', $errors);
            Session::set('profile_old_input', $_POST);
            header('Location: /profile');
            exit;
        }
        
        // Probeer gebruikersgegevens bij te werken
        try {
            User::update($userId, [
                'username' => $username,
                'email' => $email
            ]);
            
            // Succesbericht instellen
            Session::set('profile_success', 'Je profiel is bijgewerkt');
        } catch (\Exception $e) {
            // Log de fout voor debugging
            error_log('Fout bij profiel bijwerken: ' . $e->getMessage());
            
            // Stel foutbericht in
            Session::set('profile_errors', ['general' => 'Er is een fout opgetreden bij het bijwerken van je profiel']);
        }
        
        // Redirect terug naar profiel
        header('Location: /profile');
        exit;
    }
    
    /**
     * Verwerk wachtwoordwijziging
     */
    public function changePassword() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        // Controleer of het een POST-verzoek is
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile');
            exit;
        }
        
        $userId = Auth::id();
        if (!$userId) {
            // Log de fout voor debugging
            error_log('Fout bij wachtwoord wijzigen: gebruikers-ID niet beschikbaar');
            
            // Toon een foutmelding aan de gebruiker
            Session::set('auth_error', 'Er is een probleem opgetreden bij het ophalen van je gebruikersgegevens. Probeer opnieuw in te loggen.');
            header('Location: /logout');
            exit;
        }
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $errors = [];
        
        // Validatie
        if (empty($currentPassword)) {
            $errors['current_password'] = 'Huidig wachtwoord is verplicht';
        }
        
        if (empty($newPassword)) {
            $errors['new_password'] = 'Nieuw wachtwoord is verplicht';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'] = 'Wachtwoord moet minimaal 8 tekens bevatten';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Wachtwoorden komen niet overeen';
        }
        
        // Controleer of het huidige wachtwoord correct is
        $user = Auth::user();
        
        // Controleer of gebruikersgegevens geldig zijn
        if (!$user || !is_array($user) || !isset($user['password'])) {
            // Log de fout voor debugging
            error_log('Fout bij wachtwoord wijzigen: gebruikersgegevens niet beschikbaar');
            
            // Toon een foutmelding aan de gebruiker
            Session::set('auth_error', 'Er is een probleem opgetreden bij het ophalen van je profielgegevens. Probeer opnieuw in te loggen.');
            header('Location: /logout');
            exit;
        }
        
        if (!password_verify($currentPassword, $user['password'])) {
            $errors['current_password'] = 'Huidig wachtwoord is onjuist';
        }
        
        // Als er fouten zijn, toon ze en redirect terug
        if (!empty($errors)) {
            Session::set('password_errors', $errors);
            header('Location: /profile');
            exit;
        }
        
        // Update wachtwoord
        try {
            User::update($userId, [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT)
            ]);
            
            // Succesbericht instellen
            Session::set('password_success', 'Je wachtwoord is gewijzigd');
        } catch (\Exception $e) {
            // Log de fout voor debugging
            error_log('Fout bij wachtwoord wijzigen: ' . $e->getMessage());
            
            // Stel foutbericht in
            Session::set('password_errors', ['general' => 'Er is een fout opgetreden bij het wijzigen van je wachtwoord']);
        }
        
        // Redirect terug naar profiel
        header('Location: /profile');
        exit;
    }
    
    /**
     * Toon wachtwoord vergeten pagina
     */
    public function forgotPassword() {
        $error = '';
        $success = '';
        
        // Verwerk wachtwoord vergeten formulier
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            
            if (empty($email)) {
                $error = 'Vul je e-mailadres in';
            } else {
                // Controleer of het e-mailadres bestaat
                $user = User::getByEmail($email);
                
                if ($user) {
                    // Genereer reset token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Sla token op in database
                    User::update($user['id'], [
                        'reset_token' => $token,
                        'reset_expires' => $expires
                    ]);
                    
                    // Stuur reset e-mail (in een echte applicatie)
                    // Voor deze demo tonen we gewoon het token
                    $success = 'Een e-mail met instructies is verzonden naar je e-mailadres.<br>
                               <small>Reset link: /reset-password?token=' . $token . '</small>';
                } else {
                    $error = 'Geen account gevonden met dit e-mailadres';
                }
            }
        }
        
        // Paginatitel instellen
        $pageTitle = 'Wachtwoord vergeten';
        
        // Start output buffering
        ob_start();
        
        // Toon wachtwoord vergeten formulier
        ?>
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Wachtwoord vergeten</h2>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?= $error ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?= $success ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (empty($success)): ?>
            <p class="text-gray-600 mb-6">
                Vul je e-mailadres in en we sturen je een link om je wachtwoord te herstellen.
            </p>
            
            <form method="post" action="/forgot-password" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mailadres</label>
                    <input type="email" id="email" name="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 auth-input p-2 border" required>
                </div>
                
                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Verstuur herstel link
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                <a href="/login" class="font-medium text-blue-600 hover:text-blue-500">
                    Terug naar inloggen
                </a>
            </p>
        </div>
        <?php
        
        // Vang de content op
        $content = ob_get_clean();
        
        // Toon de view met auth layout via de helper functie
        renderWithAuthLayout($content, 'Wachtwoord vergeten');
    }
    
    /**
     * Verwerk wachtwoord reset
     */
    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        $error = '';
        $success = '';
        
        if (empty($token)) {
            header('Location: /login');
            exit;
        }
        
        // Zoek gebruiker met dit token
        $user = User::getByToken($token);
        
        if (!$user) {
            $error = 'Ongeldige of verlopen reset link';
        } else {
            // Controleer of het token nog geldig is
            $expires = new \DateTime($user['reset_expires']);
            $now = new \DateTime();
            
            if ($now > $expires) {
                $error = 'Deze reset link is verlopen. Vraag een nieuwe aan.';
            } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Verwerk wachtwoord reset
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['password_confirm'] ?? '';
                
                if (empty($password)) {
                    $error = 'Wachtwoord is verplicht';
                } elseif (strlen($password) < 8) {
                    $error = 'Wachtwoord moet minimaal 8 tekens bevatten';
                } elseif ($password !== $confirmPassword) {
                    $error = 'Wachtwoorden komen niet overeen';
                } else {
                    // Update wachtwoord en verwijder token
                    User::update($user['id'], [
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'reset_token' => null,
                        'reset_expires' => null
                    ]);
                    
                    $success = 'Je wachtwoord is gewijzigd. Je kunt nu inloggen met je nieuwe wachtwoord.';
                }
            }
        }
        
        // Paginatitel instellen
        $pageTitle = 'Wachtwoord herstellen';
        
        // Start output buffering
        ob_start();
        
        // Toon wachtwoord reset formulier
        ?>
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Nieuw wachtwoord instellen</h2>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?= $error ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?= $success ?></p>
                <div class="mt-4">
                    <a href="/login" class="inline-block bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                        Naar inloggen
                    </a>
                </div>
            </div>
        <?php elseif (empty($error) || $error !== 'Ongeldige of verlopen reset link' && $error !== 'Deze reset link is verlopen. Vraag een nieuwe aan.'): ?>
            <form method="post" action="/reset-password?token=<?= htmlspecialchars($token) ?>" class="space-y-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Nieuw wachtwoord</label>
                    <input type="password" id="password" name="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 auth-input p-2 border" required>
                </div>
                
                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700">Bevestig wachtwoord</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 auth-input p-2 border" required>
                </div>
                
                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Wachtwoord wijzigen
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="mt-4 text-center">
                <a href="/forgot-password" class="inline-block bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                    Nieuwe reset link aanvragen
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (empty($success)): ?>
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    <a href="/login" class="font-medium text-blue-600 hover:text-blue-500">
                        Terug naar inloggen
                    </a>
                </p>
            </div>
        <?php endif; ?>
        <?php
        
        // Vang de content op
        $content = ob_get_clean();
        
        // Toon de view met auth layout via de helper functie
        renderWithAuthLayout($content, 'Wachtwoord herstellen');
    }
}
