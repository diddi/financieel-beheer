<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;

class AuthController {
    public function login() {
        $error = '';
        
        // Verwerk login formulier
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Vul je e-mailadres en wachtwoord in';
            } else {
                // Probeer in te loggen
                if (Auth::attempt($email, $password)) {
                    // Redirect naar dashboard
                    header('Location: /');
                    exit;
                } else {
                    $error = 'Ongeldige inloggegevens';
                }
            }
        }
        
        // Toon login-formulier
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Login</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen flex items-center justify-center'>
            <div class='max-w-md w-full bg-white rounded-lg shadow-md p-8'>
                <h1 class='text-2xl font-bold mb-6 text-center'>Inloggen</h1>";
        
        if (!empty($error)) {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6' role='alert'>
                    <p>{$error}</p>
                </div>";
        }
        
        echo "  <form method='post' action='/login' class='space-y-6'>
                    <div>
                        <label for='email' class='block text-sm font-medium text-gray-700'>E-mailadres</label>
                        <input type='email' id='email' name='email' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                    </div>
                    <div>
                        <label for='password' class='block text-sm font-medium text-gray-700'>Wachtwoord</label>
                        <input type='password' id='password' name='password' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                    </div>
                    <div>
                        <button type='submit' class='w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Inloggen
                        </button>
                    </div>
                </form>
                <div class='mt-6 text-center'>
                    <p class='text-sm text-gray-600'>
                        Nog geen account? 
                        <a href='/register' class='font-medium text-blue-600 hover:text-blue-500'>
                            Registreren
                        </a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
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
        
        // Toon registratie formulier
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Registreren</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen flex items-center justify-center'>
            <div class='max-w-md w-full bg-white rounded-lg shadow-md p-8'>
                <h1 class='text-2xl font-bold mb-6 text-center'>Registreren</h1>";
        
        if (!empty($errors['general'])) {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6' role='alert'>
                    <p>{$errors['general']}</p>
                </div>";
        }
        
        echo "  <form method='post' action='/register' class='space-y-6'>
                    <div>
                        <label for='username' class='block text-sm font-medium text-gray-700'>Gebruikersnaam</label>
                        <input type='text' id='username' name='username' value='" . htmlspecialchars($_POST['username'] ?? '') . "' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>";
        if (!empty($errors['username'])) {
            echo "<p class='mt-1 text-sm text-red-600'>{$errors['username']}</p>";
        }
        echo "      </div>
                    <div>
                        <label for='email' class='block text-sm font-medium text-gray-700'>E-mailadres</label>
                        <input type='email' id='email' name='email' value='" . htmlspecialchars($_POST['email'] ?? '') . "' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>";
        if (!empty($errors['email'])) {
            echo "<p class='mt-1 text-sm text-red-600'>{$errors['email']}</p>";
        }
        echo "      </div>
                    <div>
                        <label for='password' class='block text-sm font-medium text-gray-700'>Wachtwoord</label>
                        <input type='password' id='password' name='password' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>";
        if (!empty($errors['password'])) {
            echo "<p class='mt-1 text-sm text-red-600'>{$errors['password']}</p>";
        }
        echo "      </div>
                    <div>
                        <label for='password_confirm' class='block text-sm font-medium text-gray-700'>Bevestig wachtwoord</label>
                        <input type='password' id='password_confirm' name='password_confirm' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>";
        if (!empty($errors['password_confirm'])) {
            echo "<p class='mt-1 text-sm text-red-600'>{$errors['password_confirm']}</p>";
        }
        echo "      </div>
                    <div>
                        <button type='submit' class='w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Registreren
                        </button>
                    </div>
                </form>
                <div class='mt-6 text-center'>
                    <p class='text-sm text-gray-600'>
                        Heb je al een account? 
                        <a href='/login' class='font-medium text-blue-600 hover:text-blue-500'>
                            Inloggen
                        </a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    public function logout() {
        Auth::logout();
        header('Location: /login');
        exit;
    }
}
