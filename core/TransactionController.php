<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Helpers\Validator;
use App\Helpers\FileUploadHelper;
use App\Services\OCRService;
use App\Services\NotificationService;

class TransactionController {
    
    public function index() {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Filters uit request
        $filters = [];
        if (isset($_GET['account_id']) && !empty($_GET['account_id'])) {
            $filters['account_id'] = $_GET['account_id'];
        }
        if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
            $filters['category_id'] = $_GET['category_id'];
        }
        if (isset($_GET['type']) && in_array($_GET['type'], ['expense', 'income', 'transfer'])) {
            $filters['type'] = $_GET['type'];
        }
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = $_GET['date_from'];
        }
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        // Paginatie
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $filters['limit'] = $limit;
        $filters['offset'] = $offset;
        
        // Data ophalen
        $transactions = Transaction::getAllByUser($userId, $filters);
        $accounts = Account::getAllByUser($userId);
        $categories = Category::getAllByUser($userId);
        
        // View weergeven
        include __DIR__ . '/../views/transactions/list.php';
    }
    
    public function create() {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        $accounts = Account::getAllByUser($userId);
        $expenseCategories = Category::getAllByUserAndType($userId, 'expense');
        $incomeCategories = Category::getAllByUserAndType($userId, 'income');
        
        // View weergeven
        include __DIR__ . '/../views/transactions/add.php';
    }
    
    public function store() {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = Auth::id();
        
        // Valideer input
        $validator = new Validator($_POST);
        $validator->required(['account_id', 'amount', 'type', 'date'])
                  ->numeric('amount')
                  ->date('date')
                  ->in('type', ['expense', 'income', 'transfer']);
        
        if (!$validator->passes()) {
            $_SESSION['errors'] = $validator->errors();
            $_SESSION['old_input'] = $_POST;
            header('Location: /transactions/create');
            exit;
        }
        
        // Verwerk afbeelding indien geüpload
        $receiptImage = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $receiptImage = FileUploadHelper::uploadFile(
                $_FILES['receipt'], 
                __DIR__ . '/../public/uploads/receipts/', 
                ['image/jpeg', 'image/png', 'image/gif']
            );
            
            // OCR uitvoeren als er een afbeelding is
            if ($receiptImage) {
                try {
                    $ocrService = new OCRService();
                    $ocrResult = $ocrService->processImage(__DIR__ . '/../public/uploads/receipts/' . $receiptImage);
                    
                    // Als OCR succesvol is, kunnen we hier automatisch velden vullen
                    if (!empty($ocrResult) && empty($_POST['description'])) {
                        $_POST['description'] = $ocrResult['merchant'] ?? '';
                        
                        // Als bedrag niet is ingevoerd maar wel gedetecteerd
                        if (isset($ocrResult['amount']) && empty($_POST['amount'])) {
                            $_POST['amount'] = $ocrResult['amount'];
                        }
                    }
                } catch (\Exception $e) {
                    // Log OCR error maar laat transactie doorgaan
                    error_log('OCR Error: ' . $e->getMessage());
                }
            }
        }
        
        // Bouw transactiedata
        $transactionData = [
            'user_id' => $userId,
            'account_id' => $_POST['account_id'],
            'category_id' => $_POST['category_id'] ?? null,
            'amount' => $_POST['amount'],
            'type' => $_POST['type'],
            'description' => $_POST['description'] ?? null,
            'date' => $_POST['date'],
            'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
            'receipt_image' => $receiptImage
        ];
        
        try {
            // Voeg transactie toe
            $transactionId = Transaction::create($transactionData);
            
            // Maak terugkerende transactie indien nodig
            if (isset($_POST['is_recurring']) && $_POST['is_recurring']) {
                $recurringData = [
                    'user_id' => $userId,
                    'account_id' => $_POST['account_id'],
                    'category_id' => $_POST['category_id'] ?? null,
                    'amount' => $_POST['amount'],
                    'type' => $_POST['type'],
                    'description' => $_POST['description'] ?? null,
                    'frequency' => $_POST['frequency'],
                    'start_date' => $_POST['date'],
                    'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                    'next_due_date' => $this->calculateNextDueDate($_POST['date'], $_POST['frequency'])
                ];
                
                // Sla terugkerende transactie op
                $recurringId = (new \App\Models\RecurringTransaction())->create($recurringData);
            }
            
            // Controleer budget en stuur notificatie indien nodig
            $notificationService = new NotificationService();
            $notificationService->checkBudgetLimits($userId, $_POST['category_id'], $_POST['type']);
            
            // Controleer op grote uitgave
            if ($_POST['type'] === 'expense') {
                $notificationService->checkLargeExpense($userId, $_POST['amount'], $_POST['category_id']);
            }
            
            $_SESSION['success'] = "Transactie succesvol toegevoegd!";
            header('Location: /transactions');
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = "Er is een fout opgetreden: " . $e->getMessage();
            $_SESSION['old_input'] = $_POST;
            header('Location: /transactions/create');
            exit;
        }
    }
    
    // Overige methoden voor edit, update, delete, etc.
    // ...
    
    private function calculateNextDueDate($startDate, $frequency) {
        $date = new \DateTime($startDate);
        
        switch ($frequency) {
            case 'daily':
                $date->add(new \DateInterval('P1D'));
                break;
            case 'weekly':
                $date->add(new \DateInterval('P1W'));
                break;
            case 'monthly':
                $date->add(new \DateInterval('P1M'));
                break;
            case 'quarterly':
                $date->add(new \DateInterval('P3M'));
                break;
            case 'yearly':
                $date->add(new \DateInterval('P1Y'));
                break;
        }
        
        return $date->format('Y-m-d');
    }
}