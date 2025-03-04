<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Account;
use App\Models\AccountType;

class AccountController {
    
    public function index() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal rekeningen op
        $accounts = Account::getAllByUser($userId);
        
        // Bereken totaal saldo
        $totalBalance = array_reduce($accounts, function($total, $account) {
            return $total + $account['balance'];
        }, 0);
        
        // Geef de view weer
        $this->renderAccountsList($accounts, $totalBalance);
    }
    
    public function create() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        // Haal account typen op
        $accountTypes = AccountType::getAll();
        
        // Geef het formulier weer
        $this->renderAccountForm(null, $accountTypes);
    }
    
    public function store() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Valideer input
        $name = $_POST['name'] ?? '';
        $accountTypeId = $_POST['account_type_id'] ?? '';
        $balance = $_POST['balance'] ?? 0;
        $currency = $_POST['currency'] ?? 'EUR';
        
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Voer een naam in voor de rekening';
        }
        
        if (empty($accountTypeId)) {
            $errors['account_type_id'] = 'Selecteer een type rekening';
        }
        
        if (strlen($currency) != 3) {
            $errors['currency'] = 'Valuta moet bestaan uit 3 tekens (bijv. EUR, USD)';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $accountTypes = AccountType::getAll();
            $this->renderAccountForm(null, $accountTypes, $errors, $_POST);
            return;
        }
        
        // Sla de rekening op
        $accountData = [
            'user_id' => $userId,
            'account_type_id' => $accountTypeId,
            'name' => $name,
            'balance' => floatval($balance),
            'currency' => $currency
        ];
        
        $accountId = Account::create($accountData);
        
        // Redirect naar rekeningen overzicht
        header('Location: /accounts');
        exit;
    }
    
    public function edit($id = null) {
        // ID uit URL halen als het niet als parameter is doorgegeven
        if ($id === null) {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
        }
        
        if (!$id) {
            header('Location: /accounts');
            exit;
        }
        
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal rekening op
        $account = Account::getById($id, $userId);
        
        if (!$account) {
            header('Location: /accounts');
            exit;
        }
        
        // Haal account typen op
        $accountTypes = AccountType::getAll();
        
        // Geef het formulier weer
        $this->renderAccountForm($account, $accountTypes);
    }
    
    public function update() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            header('Location: /accounts');
            exit;
        }
        
        // Valideer input (zelfde als bij store)
        $name = $_POST['name'] ?? '';
        $accountTypeId = $_POST['account_type_id'] ?? '';
        $currency = $_POST['currency'] ?? 'EUR';
        
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Voer een naam in voor de rekening';
        }
        
        if (empty($accountTypeId)) {
            $errors['account_type_id'] = 'Selecteer een type rekening';
        }
        
        if (strlen($currency) != 3) {
            $errors['currency'] = 'Valuta moet bestaan uit 3 tekens (bijv. EUR, USD)';
        }
        
        // Haal de huidige rekening op
        $account = Account::getById($id, $userId);
        
        if (!$account) {
            header('Location: /accounts');
            exit;
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $accountTypes = AccountType::getAll();
            $this->renderAccountForm($account, $accountTypes, $errors, $_POST);
            return;
        }
        
        // Update de rekening (balance wordt niet direct gewijzigd)
        $accountData = [
            'account_type_id' => $accountTypeId,
            'name' => $name,
            'currency' => $currency
        ];
        
        Account::update($id, $accountData, $userId);
        
        // Redirect naar rekeningen overzicht
        header('Location: /accounts');
        exit;
    }
    
    public function delete() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            header('Location: /accounts');
            exit;
        }
        
        try {
            // Verwijder de rekening
            Account::delete($id, $userId);
            
            // Redirect naar rekeningen overzicht
            header('Location: /accounts');
            exit;
        } catch (\Exception $e) {
            // Als er een fout optreedt (bijv. rekening heeft nog transacties)
            $accounts = Account::getAllByUser($userId);
            $totalBalance = array_reduce($accounts, function($total, $account) {
                return $total + $account['balance'];
            }, 0);
            
            $this->renderAccountsList($accounts, $totalBalance, $e->getMessage());
        }
    }
    
    // Hulpmethoden voor het weergeven van views
    
    private function renderAccountsList($accounts, $totalBalance, $error = null) {
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Rekeningen</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>
            <nav class='bg-blue-600 text-white shadow-lg'>
                <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
                    <div class='flex justify-between h-16'>
                        <div class='flex'>
                            <div class='flex-shrink-0 flex items-center'>
                                <a href='/' class='text-xl font-bold'>Financieel Beheer</a>
                            </div>
                            <div class='ml-6 flex items-center space-x-4'>
                                <a href='/' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Dashboard</a>
                                <a href='/transactions' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Transacties</a>
                                <a href='/accounts' class='px-3 py-2 rounded-md text-sm font-medium bg-blue-700'>Rekeningen</a>
                            </div>
                        </div>
                        <div class='flex items-center'>
                            <a href='/logout' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Uitloggen</a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <h1 class='text-2xl font-bold'>Rekeningen</h1>
                    <div class='mt-4 md:mt-0'>
                        <a href='/accounts/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Nieuwe rekening
                        </a>
                    </div>
                </div>
                
                <div class='bg-white rounded-lg shadow-md p-6 mb-8'>
                    <h2 class='text-xl font-bold mb-4'>Totaal saldo</h2>
                    <div class='text-3xl font-bold " . ($totalBalance >= 0 ? 'text-green-600' : 'text-red-600') . "'>
                        €" . number_format($totalBalance, 2, ',', '.') . "
                    </div>
                </div>";
                
        if ($error) {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6' role='alert'>
                    <p>{$error}</p>
                </div>";
        }
                
        echo "<div class='bg-white shadow-md rounded-lg overflow-hidden'>
                    <table class='min-w-full divide-y divide-gray-200'>
                        <thead class='bg-gray-50'>
                            <tr>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Naam</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Type</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Saldo</th>
                                <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Valuta</th>
                                <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='bg-white divide-y divide-gray-200'>";
        
        if (empty($accounts)) {
            echo "<tr><td colspan='5' class='px-6 py-4 text-center text-gray-500'>Geen rekeningen gevonden</td></tr>";
        } else {
            foreach ($accounts as $account) {
                $balanceClass = $account['balance'] >= 0 ? 'text-green-600' : 'text-red-600';
                
                echo "<tr>
                        <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($account['name']) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($account['type_name']) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm font-medium {$balanceClass}'>€" . number_format($account['balance'], 2, ',', '.') . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . htmlspecialchars($account['currency']) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                            <a href='/accounts/edit?id=" . $account['id'] . "' class='text-blue-600 hover:text-blue-900 mr-3'>Bewerken</a>
                            <a href='/accounts/delete?id=" . $account['id'] . "' class='text-red-600 hover:text-red-900' onclick='return confirm(\"Weet je zeker dat je deze rekening wilt verwijderen?\")'>Verwijderen</a>
                        </td>
                    </tr>";
            }
        }
        
        echo "      </tbody>
                    </table>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function renderAccountForm($account, $accountTypes, $errors = [], $oldInput = []) {
        $isEdit = $account !== null;
        $title = $isEdit ? 'Rekening bewerken' : 'Nieuwe rekening';
        $action = $isEdit ? '/accounts/update' : '/accounts/store';
        
        // Bepaal de waarden voor het formulier
        $nameValue = $isEdit ? $account['name'] : ($oldInput['name'] ?? '');
        $accountTypeIdValue = $isEdit ? $account['account_type_id'] : ($oldInput['account_type_id'] ?? '');
        $balanceValue = $isEdit ? $account['balance'] : ($oldInput['balance'] ?? '0.00');
        $currencyValue = $isEdit ? $account['currency'] : ($oldInput['currency'] ?? 'EUR');
        
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - {$title}</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>
            <nav class='bg-blue-600 text-white shadow-lg'>
                <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
                    <div class='flex justify-between h-16'>
                        <div class='flex'>
                            <div class='flex-shrink-0 flex items-center'>
                                <a href='/' class='text-xl font-bold'>Financieel Beheer</a>
                            </div>
                            <div class='ml-6 flex items-center space-x-4'>
                                <a href='/' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Dashboard</a>
                                <a href='/transactions' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Transacties</a>
                                <a href='/accounts' class='px-3 py-2 rounded-md text-sm font-medium bg-blue-700'>Rekeningen</a>
                            </div>
                        </div>
                        <div class='flex items-center'>
                            <a href='/logout' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Uitloggen</a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <h1 class='text-2xl font-bold'>{$title}</h1>
                </div>
                
                <div class='bg-white shadow-md rounded-lg p-6'>
                    <form action='{$action}' method='POST' class='space-y-6'>
                        " . ($isEdit ? "<input type='hidden' name='id' value='{$account['id']}'>" : "") . "
                        
                        <div>
                            <label for='name' class='block text-sm font-medium text-gray-700'>Naam</label>
                            <input type='text' id='name' name='name' required
                                class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                value='" . htmlspecialchars($nameValue) . "'>
                            " . (!empty($errors['name']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['name']}</p>" : "") . "
                        </div>
                        
                        <div>
                            <label for='account_type_id' class='block text-sm font-medium text-gray-700'>Type rekening</label>
                            <select id='account_type_id' name='account_type_id' required class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                <option value=''>Selecteer type</option>";
        
        foreach ($accountTypes as $type) {
            $selected = $accountTypeIdValue == $type['id'] ? 'selected' : '';
            echo "<option value='{$type['id']}' {$selected}>" . htmlspecialchars($type['name']) . "</option>";
        }
        
        echo "              </select>
                            " . (!empty($errors['account_type_id']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['account_type_id']}</p>" : "") . "
                        </div>
                        
                        <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                            <div>
                                <label for='balance' class='block text-sm font-medium text-gray-700'>Beginsaldo</label>
                                <div class='mt-1 relative rounded-md shadow-sm'>
                                    <div class='absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none'>
                                        <span class='text-gray-500 sm:text-sm'>€</span>
                                    </div>
                                    <input type='number' name='balance' id='balance' step='0.01'
                                        class='block w-full pl-7 pr-12 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 p-2 border'
                                        placeholder='0,00'
                                        value='" . htmlspecialchars($balanceValue) . "'";
                                        
        // Bij bewerken, maak het saldoveld readonly
        if ($isEdit) {
            echo " readonly";
        }
                                        
        echo "              >
                                    " . ($isEdit ? "<p class='mt-1 text-xs text-gray-500'>Het saldo wordt automatisch bijgewerkt met transacties.</p>" : "") . "
                                </div>
                            </div>
                            
                            <div>
                                <label for='currency' class='block text-sm font-medium text-gray-700'>Valuta</label>
                                <input type='text' id='currency' name='currency' maxlength='3' required
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border uppercase'
                                    value='" . htmlspecialchars($currencyValue) . "'>
                                " . (!empty($errors['currency']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['currency']}</p>" : "") . "
                                <p class='mt-1 text-xs text-gray-500'>Bijv. EUR, USD, GBP</p>
                            </div>
                        </div>
                        
                        <div class='flex justify-end space-x-3 mt-6'>
                            <a href='/accounts' class='py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                Annuleren
                            </a>
                            <button type='submit' class='py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                " . ($isEdit ? 'Bijwerken' : 'Opslaan') . "
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
