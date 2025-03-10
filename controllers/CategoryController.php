<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Category;

class CategoryController extends Controller {
    
    public function index() {
        $userId = $this->requireLogin();
        
        // Haal categorieën op
        $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
        $incomeCategories = Category::getAllByUserAndType($userId, 'income');
        
        // Render de pagina
        $render = $this->startBuffering('Categorieën');
        
        // Begin HTML output
        echo "<div class='max-w-7xl mx-auto'>";
        
        // Header sectie
        echo "
            <div class='flex justify-between items-center mb-6'>
                <h1 class='text-2xl font-bold'>Categorieën</h1>
                <a href='/categories/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                    <i class='material-icons mr-1 text-sm'>add</i> Nieuwe categorie
                </a>
            </div>";
        
        // Uitgaven categorieën
        echo "
            <div class='mb-8'>
                <div class='flex items-center mb-4'>
                    <h2 class='text-xl font-semibold'>Uitgaven categorieën</h2>
                    <span class='ml-2 px-2 py-1 text-xs bg-gray-200 text-gray-800 rounded-full'>" . count($expenseCategories) . "</span>
                </div>";
        
        if (empty($expenseCategories)) {
            echo "
                <div class='bg-white rounded-lg shadow-md p-6 text-center'>
                    <p class='text-gray-500'>Geen uitgaven categorieën gevonden.</p>
                    <a href='/categories/create?type=expense' class='mt-4 inline-block text-blue-600 hover:text-blue-800'>Voeg een uitgaven categorie toe</a>
                </div>";
        } else {
            echo "<div class='grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4'>";
            
            foreach ($expenseCategories as $category) {
                echo "
                    <div class='bg-white rounded-lg shadow-md p-4 flex justify-between items-center'>
                        <div class='flex items-center'>
                            <div class='w-8 h-8 rounded-full flex items-center justify-center' style='background-color: " . $category['color'] . "20'>
                                <i class='material-icons text-sm' style='color: " . $category['color'] . "'>" . ($category['icon'] ?? 'category') . "</i>
                            </div>
                            <span class='ml-3 font-medium'>" . htmlspecialchars($category['name']) . "</span>
                        </div>
                        <div class='flex space-x-2'>
                            <a href='/categories/edit?id=" . $category['id'] . "' class='text-blue-600 hover:text-blue-800'>
                                <i class='material-icons text-sm'>edit</i>
                            </a>
                            <a href='/categories/delete?id=" . $category['id'] . "' onclick='return confirm(\"Weet je zeker dat je deze categorie wilt verwijderen?\")' class='text-red-600 hover:text-red-800'>
                                <i class='material-icons text-sm'>delete</i>
                            </a>
                        </div>
                    </div>";
            }
            
            echo "</div>";
        }
        
        echo "</div>";
        
        // Inkomsten categorieën
        echo "
            <div>
                <div class='flex items-center mb-4'>
                    <h2 class='text-xl font-semibold'>Inkomsten categorieën</h2>
                    <span class='ml-2 px-2 py-1 text-xs bg-gray-200 text-gray-800 rounded-full'>" . count($incomeCategories) . "</span>
                </div>";
        
        if (empty($incomeCategories)) {
            echo "
                <div class='bg-white rounded-lg shadow-md p-6 text-center'>
                    <p class='text-gray-500'>Geen inkomsten categorieën gevonden.</p>
                    <a href='/categories/create?type=income' class='mt-4 inline-block text-blue-600 hover:text-blue-800'>Voeg een inkomsten categorie toe</a>
                </div>";
        } else {
            echo "<div class='grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4'>";
            
            foreach ($incomeCategories as $category) {
                echo "
                    <div class='bg-white rounded-lg shadow-md p-4 flex justify-between items-center'>
                        <div class='flex items-center'>
                            <div class='w-8 h-8 rounded-full flex items-center justify-center' style='background-color: " . $category['color'] . "20'>
                                <i class='material-icons text-sm' style='color: " . $category['color'] . "'>" . ($category['icon'] ?? 'category') . "</i>
                            </div>
                            <span class='ml-3 font-medium'>" . htmlspecialchars($category['name']) . "</span>
                        </div>
                        <div class='flex space-x-2'>
                            <a href='/categories/edit?id=" . $category['id'] . "' class='text-blue-600 hover:text-blue-800'>
                                <i class='material-icons text-sm'>edit</i>
                            </a>
                            <a href='/categories/delete?id=" . $category['id'] . "' onclick='return confirm(\"Weet je zeker dat je deze categorie wilt verwijderen?\")' class='text-red-600 hover:text-red-800'>
                                <i class='material-icons text-sm'>delete</i>
                            </a>
                        </div>
                    </div>";
            }
            
            echo "</div>";
        }
        
        echo "</div>";
        
        // Einde content div
        echo "</div>";
        
        // Render de pagina
        $render();
    }
    
    public function create() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        // Geef het formulier weer
        $this->renderCategoryForm();
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
        $type = $_POST['type'] ?? '';
        $color = $_POST['color'] ?? '#000000';
        
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Voer een naam in voor de categorie';
        }
        
        if (empty($type) || !in_array($type, ['expense', 'income'])) {
            $errors['type'] = 'Selecteer een geldig type categorie';
        }
        
        if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            $errors['color'] = 'Kies een geldige kleur (HEX-formaat)';
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $this->renderCategoryForm(null, $errors, $_POST);
            return;
        }
        
        // Sla de categorie op
        $categoryData = [
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'color' => $color
        ];
        
        $categoryId = Category::create($categoryData);
        
        // Redirect naar categorieën overzicht
        header('Location: /categories');
        exit;
    }
    
    public function edit($id = null) {
        // ID uit URL halen als het niet als parameter is doorgegeven
        if ($id === null) {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
        }
        
        if (!$id) {
            header('Location: /categories');
            exit;
        }
        
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal categorie op
        $category = Category::getById($id, $userId);
        
        if (!$category) {
            header('Location: /categories');
            exit;
        }
        
        // Geef het formulier weer
        $this->renderCategoryForm($category);
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
            header('Location: /categories');
            exit;
        }
        
        // Valideer input (zelfde als bij store)
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $color = $_POST['color'] ?? '#000000';
        
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Voer een naam in voor de categorie';
        }
        
        if (empty($type) || !in_array($type, ['expense', 'income'])) {
            $errors['type'] = 'Selecteer een geldig type categorie';
        }
        
        if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            $errors['color'] = 'Kies een geldige kleur (HEX-formaat)';
        }
        
        // Haal de huidige categorie op
        $category = Category::getById($id, $userId);
        
        if (!$category) {
            header('Location: /categories');
            exit;
        }
        
        // Als er fouten zijn, toon het formulier opnieuw
        if (!empty($errors)) {
            $this->renderCategoryForm($category, $errors, $_POST);
            return;
        }
        
        // Update de categorie
        $categoryData = [
            'name' => $name,
            'type' => $type,
            'color' => $color
        ];
        
        Category::update($id, $categoryData, $userId);
        
        // Redirect naar categorieën overzicht
        header('Location: /categories');
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
            header('Location: /categories');
            exit;
        }
        
        // Verwijder de categorie
        try {
            Category::delete($id, $userId);
            
            // Redirect naar categorieën overzicht
            header('Location: /categories');
            exit;
        } catch (\Exception $e) {
            // Als er een fout optreedt
            $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
            $incomeCategories = Category::getAllByUserAndType($userId, 'income');
            
            $this->renderCategoriesList($expenseCategories, $incomeCategories, $e->getMessage());
        }
    }
    
    // Hulpmethoden voor het weergeven van views
    
    private function renderCategoriesList($expenseCategories, $incomeCategories, $error = null) {
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Categorieën</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>";
    
    // Sluit de echo, voeg het navigatiecomponent toe
    include_once __DIR__ . '/../views/components/navigation.php';
    
    // Hervat de echo voor de rest van de HTML
    echo " <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <h1 class='text-2xl font-bold'>Categorieën</h1>
                    <div class='mt-4 md:mt-0'>
                        <a href='/categories/create' class='inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Nieuwe categorie
                        </a>
                    </div>
                </div>";
                
        if ($error) {
            echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6' role='alert'>
                    <p>{$error}</p>
                </div>";
        }
                
        // Uitgaven categorieën
        echo "<div class='mb-8'>
                    <h2 class='text-xl font-semibold mb-4'>Uitgaven</h2>
                    <div class='bg-white shadow-md rounded-lg overflow-hidden'>
                        <table class='min-w-full divide-y divide-gray-200'>
                            <thead class='bg-gray-50'>
                                <tr>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Kleur</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Naam</th>
                                    <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>Acties</th>
                                </tr>
                            </thead>
                            <tbody class='bg-white divide-y divide-gray-200'>";
        
        if (empty($expenseCategories)) {
            echo "<tr><td colspan='3' class='px-6 py-4 text-center text-gray-500'>Geen uitgavencategorieën gevonden</td></tr>";
        } else {
            foreach ($expenseCategories as $category) {
                echo "<tr>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='w-6 h-6 rounded-full' style='background-color: " . htmlspecialchars($category['color']) . "'></div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($category['name']) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                            <a href='/categories/edit?id=" . $category['id'] . "' class='text-blue-600 hover:text-blue-900 mr-3'>Bewerken</a>
                            <a href='/categories/delete?id=" . $category['id'] . "' class='text-red-600 hover:text-red-900' onclick='return confirm(\"Weet je zeker dat je deze categorie wilt verwijderen?\")'>Verwijderen</a>
                        </td>
                    </tr>";
            }
        }
        
        echo "      </tbody>
                        </table>
                    </div>
                </div>";
                
        // Inkomsten categorieën
        echo "<div>
                    <h2 class='text-xl font-semibold mb-4'>Inkomsten</h2>
                    <div class='bg-white shadow-md rounded-lg overflow-hidden'>
                        <table class='min-w-full divide-y divide-gray-200'>
                            <thead class='bg-gray-50'>
                                <tr>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Kleur</th>
                                    <th scope='col' class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Naam</th>
                                    <th scope='col' class='px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider'>Acties</th>
                                </tr>
                            </thead>
                            <tbody class='bg-white divide-y divide-gray-200'>";
        
        if (empty($incomeCategories)) {
            echo "<tr><td colspan='3' class='px-6 py-4 text-center text-gray-500'>Geen inkomstencategorieën gevonden</td></tr>";
        } else {
            foreach ($incomeCategories as $category) {
                echo "<tr>
                        <td class='px-6 py-4 whitespace-nowrap'>
                            <div class='w-6 h-6 rounded-full' style='background-color: " . htmlspecialchars($category['color']) . "'></div>
                        </td>
                        <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($category['name']) . "</td>
                        <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                            <a href='/categories/edit?id=" . $category['id'] . "' class='text-blue-600 hover:text-blue-900 mr-3'>Bewerken</a>
                            <a href='/categories/delete?id=" . $category['id'] . "' class='text-red-600 hover:text-red-900' onclick='return confirm(\"Weet je zeker dat je deze categorie wilt verwijderen?\")'>Verwijderen</a>
                        </td>
                    </tr>";
            }
        }
        
        echo "      </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function renderCategoryForm($category = null, $errors = [], $oldInput = []) {
        $isEdit = $category !== null;
        $title = $isEdit ? 'Categorie bewerken' : 'Nieuwe categorie';
        $action = $isEdit ? '/categories/update' : '/categories/store';
        
        // Bepaal de waarden voor het formulier
        $nameValue = $isEdit ? $category['name'] : ($oldInput['name'] ?? '');
        $typeValue = $isEdit ? $category['type'] : ($oldInput['type'] ?? 'expense');
        $colorValue = $isEdit ? $category['color'] : ($oldInput['color'] ?? '#4CAF50');
        
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
                                <a href='/accounts' class='px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700'>Rekeningen</a>
                                <a href='/categories' class='px-3 py-2 rounded-md text-sm font-medium bg-blue-700'>Categorieën</a>
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
                        " . ($isEdit ? "<input type='hidden' name='id' value='{$category['id']}'>" : "") . "
                        
                        <div>
                            <label for='name' class='block text-sm font-medium text-gray-700'>Naam</label>
                            <input type='text' id='name' name='name' required
                                class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                value='" . htmlspecialchars($nameValue) . "'>
                            " . (!empty($errors['name']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['name']}</p>" : "") . "
                        </div>
                        
                        <div>
                            <label class='block text-sm font-medium text-gray-700'>Type</label>
                            <div class='mt-2 space-x-4'>
                                <label class='inline-flex items-center'>
                                    <input type='radio' name='type' value='expense' class='form-radio text-blue-600' " . ($typeValue === 'expense' ? 'checked' : '') . ">
                                    <span class='ml-2'>Uitgave</span>
                                </label>
                                <label class='inline-flex items-center'>
                                    <input type='radio' name='type' value='income' class='form-radio text-green-600' " . ($typeValue === 'income' ? 'checked' : '') . ">
                                    <span class='ml-2'>Inkomst</span>
                                </label>
                            </div>
                            " . (!empty($errors['type']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['type']}</p>" : "") . "
                        </div>
                        
                        <div>
                            <label for='color' class='block text-sm font-medium text-gray-700'>Kleur</label>
                            <div class='mt-1 flex items-center'>
                                <input type='color' id='color' name='color'
                                    class='h-8 w-8 rounded border-gray-300'
                                    value='" . htmlspecialchars($colorValue) . "'>
                                <span class='ml-2 text-xs text-gray-500'>" . htmlspecialchars($colorValue) . "</span>
                            </div>
                            " . (!empty($errors['color']) ? "<p class='mt-1 text-sm text-red-600'>{$errors['color']}</p>" : "") . "
                        </div>
                        
                        <div class='flex justify-end space-x-3 mt-6'>
                            <a href='/categories' class='py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                Annuleren
                            </a>
                            <button type='submit' class='py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                " . ($isEdit ? 'Bijwerken' : 'Opslaan') . "
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const colorInput = document.getElementById('color');
                const colorText = colorInput.nextElementSibling;
                
                colorInput.addEventListener('input', function() {
                    colorText.textContent = this.value;
                });
            });
            </script>
        </body>
        </html>
        ";
    }
}
