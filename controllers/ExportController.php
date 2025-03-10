<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Services\ExportService;

class ExportController extends Controller {
    
    /**
     * Toon exportopties
     */
    public function index() {
        $userId = $this->requireLogin();
        
        // Haal rekeningen op
        $accounts = Account::getAllByUser($userId);
        
        // Render de pagina
        $render = $this->startBuffering('Export');
        
        // Begin HTML output
        echo "<div class='max-w-7xl mx-auto'>";
        
        // Header sectie
        echo "
            <div class='mb-6'>
                <h1 class='text-2xl font-bold'>Exporteren</h1>
                <p class='text-gray-500 mt-1'>Exporteer je financiële gegevens in verschillende formaten</p>
            </div>";
        
        // Export kaarten
        echo "
            <div class='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8'>
                <!-- Transacties export -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='mb-4'>
                        <div class='flex items-center mb-2'>
                            <i class='material-icons text-blue-600 mr-2'>receipt</i>
                            <h2 class='text-lg font-semibold'>Transacties</h2>
                        </div>
                        <p class='text-gray-500 text-sm'>Exporteer al je transacties of filter op rekening en datum</p>
                    </div>
                    
                    <form action='/export/transactions' method='post' class='space-y-4'>
                        <div>
                            <label for='account_id' class='block text-sm font-medium text-gray-700 mb-1'>Rekening</label>
                            <select id='account_id' name='account_id' class='block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                <option value=''>Alle rekeningen</option>";
        
        foreach ($accounts as $account) {
            echo "<option value='" . $account['id'] . "'>" . htmlspecialchars($account['name']) . "</option>";
        }
        
        echo "
                            </select>
                        </div>
                        
                        <div class='grid grid-cols-2 gap-4'>
                            <div>
                                <label for='date_from' class='block text-sm font-medium text-gray-700 mb-1'>Van datum</label>
                                <input type='date' id='date_from' name='date_from' class='block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                            </div>
                            
                            <div>
                                <label for='date_to' class='block text-sm font-medium text-gray-700 mb-1'>Tot datum</label>
                                <input type='date' id='date_to' name='date_to' class='block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                            </div>
                        </div>
                        
                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Formaat</label>
                            <div class='flex space-x-4'>
                                <div class='flex items-center'>
                                    <input type='radio' id='format_csv' name='format' value='csv' checked class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='format_csv' class='ml-2 block text-sm text-gray-700'>CSV</label>
                                </div>
                                <div class='flex items-center'>
                                    <input type='radio' id='format_xlsx' name='format' value='xlsx' class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='format_xlsx' class='ml-2 block text-sm text-gray-700'>Excel</label>
                                </div>
                                <div class='flex items-center'>
                                    <input type='radio' id='format_pdf' name='format' value='pdf' class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='format_pdf' class='ml-2 block text-sm text-gray-700'>PDF</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type='submit' class='w-full inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                            Exporteren
                        </button>
                    </form>
                </div>
                
                <!-- Budgetten export -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='mb-4'>
                        <div class='flex items-center mb-2'>
                            <i class='material-icons text-green-600 mr-2'>savings</i>
                            <h2 class='text-lg font-semibold'>Budgetten</h2>
                        </div>
                        <p class='text-gray-500 text-sm'>Exporteer je budgetgegevens en hun huidige status</p>
                    </div>
                    
                    <form action='/export/budgets' method='post' class='space-y-4'>                        
                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Formaat</label>
                            <div class='flex space-x-4'>
                                <div class='flex items-center'>
                                    <input type='radio' id='budget_format_csv' name='format' value='csv' checked class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='budget_format_csv' class='ml-2 block text-sm text-gray-700'>CSV</label>
                                </div>
                                <div class='flex items-center'>
                                    <input type='radio' id='budget_format_xlsx' name='format' value='xlsx' class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='budget_format_xlsx' class='ml-2 block text-sm text-gray-700'>Excel</label>
                                </div>
                                <div class='flex items-center'>
                                    <input type='radio' id='budget_format_pdf' name='format' value='pdf' class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='budget_format_pdf' class='ml-2 block text-sm text-gray-700'>PDF</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type='submit' class='w-full inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500'>
                            Exporteren
                        </button>
                    </form>
                </div>
                
                <!-- Categorieën export -->
                <div class='bg-white rounded-lg shadow-md p-6'>
                    <div class='mb-4'>
                        <div class='flex items-center mb-2'>
                            <i class='material-icons text-purple-600 mr-2'>category</i>
                            <h2 class='text-lg font-semibold'>Categorieën</h2>
                        </div>
                        <p class='text-gray-500 text-sm'>Exporteer je categorieën voor uitgaven en inkomsten</p>
                    </div>
                    
                    <form action='/export/categories' method='post' class='space-y-4'>
                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Type</label>
                            <div class='flex space-x-4'>
                                <div class='flex items-center'>
                                    <input type='radio' id='type_all' name='type' value='all' checked class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='type_all' class='ml-2 block text-sm text-gray-700'>Alle</label>
                                </div>
                                <div class='flex items-center'>
                                    <input type='radio' id='type_expense' name='type' value='expense' class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='type_expense' class='ml-2 block text-sm text-gray-700'>Uitgaven</label>
                                </div>
                                <div class='flex items-center'>
                                    <input type='radio' id='type_income' name='type' value='income' class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='type_income' class='ml-2 block text-sm text-gray-700'>Inkomsten</label>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class='block text-sm font-medium text-gray-700 mb-1'>Formaat</label>
                            <div class='flex space-x-4'>
                                <div class='flex items-center'>
                                    <input type='radio' id='cat_format_csv' name='format' value='csv' checked class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='cat_format_csv' class='ml-2 block text-sm text-gray-700'>CSV</label>
                                </div>
                                <div class='flex items-center'>
                                    <input type='radio' id='cat_format_xlsx' name='format' value='xlsx' class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='cat_format_xlsx' class='ml-2 block text-sm text-gray-700'>Excel</label>
                                </div>
                                <div class='flex items-center'>
                                    <input type='radio' id='cat_format_pdf' name='format' value='pdf' class='focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300'>
                                    <label for='cat_format_pdf' class='ml-2 block text-sm text-gray-700'>PDF</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type='submit' class='w-full inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500'>
                            Exporteren
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Import sectie -->
            <div class='bg-white rounded-lg shadow-md p-6'>
                <div class='mb-4'>
                    <div class='flex items-center mb-2'>
                        <i class='material-icons text-gray-600 mr-2'>upload_file</i>
                        <h2 class='text-lg font-semibold'>Importeren</h2>
                    </div>
                    <p class='text-gray-500 text-sm'>Importeer transacties vanuit CSV- of XLSX-bestanden</p>
                </div>
                
                <form action='/import/transactions' method='post' enctype='multipart/form-data' class='space-y-4'>
                    <div>
                        <label for='import_file' class='block text-sm font-medium text-gray-700 mb-1'>Bestand</label>
                        <input type='file' id='import_file' name='import_file' accept='.csv,.xlsx' class='block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100'>
                    </div>
                    
                    <div>
                        <label for='import_account' class='block text-sm font-medium text-gray-700 mb-1'>Rekening</label>
                        <select id='import_account' name='account_id' required class='block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                            <option value=''>Selecteer rekening</option>";
        
        foreach ($accounts as $account) {
            echo "<option value='" . $account['id'] . "'>" . htmlspecialchars($account['name']) . "</option>";
        }
        
        echo "
                        </select>
                    </div>
                    
                    <button type='submit' class='inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                        Importeren
                    </button>
                </form>
            </div>
        ";
        
        // Einde content div
        echo "</div>";
        
        // Render de pagina
        $render();
    }
    
    /**
     * Exporteer transacties
     */
    public function exportTransactions() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Controleer of er een POST request is
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /export');
            exit;
        }
        
        // Haal filters op
        $startDate = isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01', strtotime('-1 year'));
        $endDate = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d');
        $accountId = isset($_POST['account_id']) && !empty($_POST['account_id']) ? $_POST['account_id'] : null;
        $categoryId = isset($_POST['category_id']) && !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        $type = isset($_POST['type']) && in_array($_POST['type'], ['all', 'expense', 'income', 'transfer']) ? $_POST['type'] : 'all';
        $format = isset($_POST['format']) && in_array($_POST['format'], ['pdf', 'excel']) ? $_POST['format'] : 'pdf';
        
        // Bouw filters voor query
        $filters = [
            'date_from' => $startDate,
            'date_to' => $endDate
        ];
        
        if ($accountId) {
            $filters['account_id'] = $accountId;
        }
        
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }
        
        if ($type !== 'all') {
            $filters['type'] = $type;
        }
        
        // Haal transacties op
        $transactions = Transaction::getAllByUser($userId, $filters);
        
        // Controleer of er transacties zijn
        if (empty($transactions)) {
            // Stuur terug met foutmelding
            $this->redirectWithError('/export', 'Geen transacties gevonden voor de opgegeven filters.');
            exit;
        }
        
        // Maak ExportService instance
        $exportService = new ExportService();
        
        // Genereer bestandsnaam met timestamp
        $timestamp = date('YmdHi');
        $typeStr = $type !== 'all' ? '_' . $type : '';
        $filename = 'transacties' . $typeStr . '_' . $timestamp . ($format === 'excel' ? '.xlsx' : '.pdf');
        
        try {
            // Exporteer naar het gewenste formaat
            if ($format === 'excel') {
                $filePath = $exportService->exportTransactionsToExcel($transactions, $filename);
            } else {
                // Bereid metadata voor
                $metadata = [
                    'title' => 'Transactie Overzicht',
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'user_name' => Auth::user()['username']
                ];
                
                $filePath = $exportService->exportTransactionsToPDF($transactions, $metadata, $filename);
            }
            
            // Bepaal de relatieve URL voor download
            $relativeUrl = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', $filePath);
            $downloadUrl = '/exports/' . basename($filePath);
            
            // Stuur door naar download-pagina
            $this->redirectToDownload($downloadUrl, basename($filePath));
            
        } catch (\Exception $e) {
            // Stuur terug met foutmelding
            $this->redirectWithError('/export', 'Er is een fout opgetreden bij het exporteren: ' . $e->getMessage());
        }
    }
    
    /**
     * Exporteer budgetoverzicht
     */
    public function exportBudgets() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Controleer of er een POST request is
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /export');
            exit;
        }
        
        // Alleen PDF export is beschikbaar voor budgetten
        $format = 'pdf';
        
        // Haal budgetstatus op
        $budgetStatus = Budget::getBudgetStatus($userId);
        
        // Controleer of er budgetten zijn
        if (empty($budgetStatus)) {
            $this->redirectWithError('/export', 'Geen budgetten gevonden om te exporteren.');
            exit;
        }
        
        // Maak ExportService instance
        $exportService = new ExportService();
        
        // Genereer bestandsnaam
        $timestamp = date('YmdHi');
        $filename = 'budgetten_' . $timestamp . '.pdf';
        
        try {
            // Bereid metadata voor
            $metadata = [
                'period' => date('F Y'), // Huidige maand en jaar
                'user_name' => Auth::user()['username']
            ];
            
            // Exporteer naar PDF
            $filePath = $exportService->exportBudgetsToPDF($budgetStatus, $metadata, $filename);
            
            // Bepaal de download URL
            $downloadUrl = '/exports/' . basename($filePath);
            
            // Stuur door naar download-pagina
            $this->redirectToDownload($downloadUrl, basename($filePath));
            
        } catch (\Exception $e) {
            // Stuur terug met foutmelding
            $this->redirectWithError('/export', 'Er is een fout opgetreden bij het exporteren: ' . $e->getMessage());
        }
    }
    
    /**
     * Exporteer rekeningoverzicht
     */
    public function exportAccounts() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Haal rekeningen op
        $accounts = Account::getAllByUser($userId);
        
        // Controleer of er rekeningen zijn
        if (empty($accounts)) {
            $this->redirectWithError('/export', 'Geen rekeningen gevonden om te exporteren.');
            exit;
        }
        
        // Maak ExportService instance
        $exportService = new ExportService();
        
        // Genereer bestandsnaam
        $timestamp = date('YmdHi');
        $filename = 'rekeningen_' . $timestamp . '.pdf';
        
        try {
            // Bereid metadata voor
            $metadata = [
                'user_name' => Auth::user()['username']
            ];
            
            // Exporteer naar PDF
            $filePath = $exportService->exportAccountsToPDF($accounts, $metadata, $filename);
            
            // Bepaal de download URL
            $downloadUrl = '/exports/' . basename($filePath);
            
            // Stuur door naar download-pagina
            $this->redirectToDownload($downloadUrl, basename($filePath));
            
        } catch (\Exception $e) {
            // Stuur terug met foutmelding
            $this->redirectWithError('/export', 'Er is een fout opgetreden bij het exporteren: ' . $e->getMessage());
        }
    }
    
    /**
     * Toon download pagina
     */
    public function download() {
        // Controleer of gebruiker is ingelogd
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        // Haal bestandsgegevens op
        $fileUrl = isset($_GET['file']) ? $_GET['file'] : null;
        $fileName = isset($_GET['name']) ? $_GET['name'] : 'bestand';
        
        if (!$fileUrl) {
            header('Location: /export');
            exit;
        }
        
        // Toon download pagina
        $this->renderDownloadPage($fileUrl, $fileName);
    }
    
    /**
     * Render de exportpagina
     */
    private function renderExportPage($accounts, $categories, $error = null) {
        // Definieer de huidige pagina
        $currentPage = 'export';
        
        // Begin output
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Export</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>";
        
        // Include het navigatiecomponent
        include_once __DIR__ . '/../views/components/navigation.php';
        
        // Hervat de output
        echo "
            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='md:flex md:items-center md:justify-between mb-6'>
                    <h1 class='text-2xl font-bold'>Gegevens Exporteren</h1>
                </div>";
                
        // Toon foutmelding indien aanwezig
        if ($error) {
            echo "
                <div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6'>
                    <p>{$error}</p>
                </div>";
        }
                
        echo "
                <div class='grid grid-cols-1 md:grid-cols-3 gap-6'>
                    <!-- Transacties exporteren -->
                    <div class='bg-white rounded-lg shadow-md p-6'>
                        <h2 class='text-lg font-semibold mb-4'>Transacties Exporteren</h2>
                        <form action='/export/transactions' method='POST' class='space-y-4'>
                            <div>
                                <label for='start_date' class='block text-sm font-medium text-gray-700'>Van datum</label>
                                <input type='date' id='start_date' name='start_date'
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                    value='" . date('Y-m-01', strtotime('-1 year')) . "'>
                            </div>
                            
                            <div>
                                <label for='end_date' class='block text-sm font-medium text-gray-700'>Tot datum</label>
                                <input type='date' id='end_date' name='end_date'
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'
                                    value='" . date('Y-m-d') . "'>
                            </div>
                            
                            <div>
                                <label for='account_id' class='block text-sm font-medium text-gray-700'>Rekening (optioneel)</label>
                                <select id='account_id' name='account_id' 
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                    <option value=''>Alle rekeningen</option>";
                                    
        foreach ($accounts as $account) {
            echo "<option value='" . $account['id'] . "'>" . htmlspecialchars($account['name']) . "</option>";
        }
                                    
        echo "              </select>
                            </div>
                            
                            <div>
                                <label for='category_id' class='block text-sm font-medium text-gray-700'>Categorie (optioneel)</label>
                                <select id='category_id' name='category_id' 
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                    <option value=''>Alle categorieën</option>";
                                    
        foreach ($categories as $category) {
            echo "<option value='" . $category['id'] . "'>" . htmlspecialchars($category['name']) . " (" . ($category['type'] === 'expense' ? 'Uitgave' : 'Inkomst') . ")</option>";
        }
                                    
        echo "              </select>
                            </div>
                            
                            <div>
                                <label for='type' class='block text-sm font-medium text-gray-700'>Type transactie</label>
                                <select id='type' name='type' 
                                    class='mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'>
                                    <option value='all'>Alle transacties</option>
                                    <option value='expense'>Alleen uitgaven</option>
                                    <option value='income'>Alleen inkomsten</option>
                                    <option value='transfer'>Alleen overschrijvingen</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for='format' class='block text-sm font-medium text-gray-700'>Bestandsformaat</label>
                                <div class='mt-2 space-x-4'>
                                    <label class='inline-flex items-center'>
                                        <input type='radio' name='format' value='pdf' class='form-radio text-blue-600' checked>
                                        <span class='ml-2'>PDF</span>
                                    </label>
                                    <label class='inline-flex items-center'>
                                        <input type='radio' name='format' value='excel' class='form-radio text-green-600'>
                                        <span class='ml-2'>Excel</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class='pt-2'>
                                <button type='submit' class='w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'>
                                    Transacties Exporteren
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Budgetten exporteren -->
                    <div class='bg-white rounded-lg shadow-md p-6'>
                        <h2 class='text-lg font-semibold mb-4'>Budgetoverzicht Exporteren</h2>
                        <p class='text-gray-600 mb-4'>
                            Exporteer een gedetailleerd overzicht van je huidige budgetten, voortgang en status naar PDF.
                        </p>
                        <form action='/export/budgets' method='POST' class='mt-8'>
                            <button type='submit' class='w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500'>
                                Budget Overzicht Exporteren
                            </button>
                        </form>
                    </div>
                    
                    <!-- Rekeningen exporteren -->
                    <div class='bg-white rounded-lg shadow-md p-6'>
                        <h2 class='text-lg font-semibold mb-4'>Rekeningen Exporteren</h2>
                        <p class='text-gray-600 mb-4'>
                            Exporteer een overzicht van je rekeningen met saldi en verdeling naar PDF.
                        </p>
                        <form action='/export/accounts' method='POST' class='mt-8'>
                            <button type='submit' class='w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500'>
                                Rekeningen Overzicht Exporteren
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class='mt-8 bg-blue-50 p-6 rounded-lg shadow-sm'>
                    <h2 class='text-lg font-semibold mb-2 text-blue-800'>Over Exporteren</h2>
                    <p class='text-blue-600 mb-2'>
                        Je kunt je financiële gegevens exporteren naar verschillende formaten voor persoonlijke administratie,
                        belastingaangifte of om te delen met een financieel adviseur.
                    </p>
                    <ul class='list-disc list-inside text-blue-600 ml-4'>
                        <li>PDF: Ideaal voor afdrukken of archiveren</li>
                        <li>Excel: Perfect voor verdere analyses of bewerking</li>
                    </ul>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Render de download pagina
     */
    private function renderDownloadPage($fileUrl, $fileName) {
        // Definieer de huidige pagina
        $currentPage = 'export';
        
        // Begin output
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Financieel Beheer - Download</title>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-gray-100 min-h-screen'>";
        
        // Include het navigatiecomponent
        include_once __DIR__ . '/../views/components/navigation.php';
        
        // Hervat de output
        echo "
            <div class='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8'>
                <div class='bg-white rounded-lg shadow-md p-8 text-center'>
                    <div class='mb-6'>
                        <svg xmlns='http://www.w3.org/2000/svg' class='mx-auto h-16 w-16 text-green-500' fill='none' viewBox='0 0 24 24' stroke='currentColor'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' />
                        </svg>
                    </div>
                    
                    <h1 class='text-2xl font-bold mb-4'>Bestand gereed voor download</h1>
                    <p class='text-gray-600 mb-6'>
                        Je bestand '{$fileName}' is succesvol aangemaakt en klaar om te downloaden.
                    </p>
                    
                    <div class='mt-8 flex flex-col items-center'>
                        <a href='{$fileUrl}' download='{$fileName}' 
                           class='py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-md transition-colors flex items-center'>
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5 mr-2' viewBox='0 0 20 20' fill='currentColor'>
                                <path fill-rule='evenodd' d='M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z' clip-rule='evenodd' />
                            </svg>
                            Download Bestand
                        </a>
                        
                        <div class='mt-4 text-sm text-gray-500'>
                            Het bestand wordt automatisch gedownload. Als dat niet gebeurt, klik op de knop hierboven.
                        </div>
                    </div>
                    
                    <div class='mt-8'>
                        <a href='/export' class='text-blue-600 hover:text-blue-800'>
                            Terug naar exportpagina
                        </a>
                    </div>
                </div>
            </div>
            
            <script>
                // Automatisch downloaden starten na 1 seconde
                setTimeout(function() {
                    const downloadLink = document.querySelector('a[download]');
                    downloadLink.click();
                }, 1000);
            </script>
        </body>
        </html>";
    }
    
    /**
     * Helper methode om door te sturen met een foutmelding
     */
    private function redirectWithError($url, $error) {
        session_start();
        $_SESSION['export_error'] = $error;
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Helper methode om door te sturen naar de downloadpagina
     */
    private function redirectToDownload($fileUrl, $fileName) {
        header('Location: /export/download?file=' . $fileUrl . '&name=' . urlencode($fileName));
        exit;
    }
}