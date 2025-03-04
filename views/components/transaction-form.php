<form action="<?= isset($transaction) ? '/transactions/update/' . $transaction['id'] : '/transactions/store' ?>" method="POST" enctype="multipart/form-data" id="transactionForm" class="space-y-6">
    <div class="flex items-center space-x-6 mb-4">
        <label class="inline-flex items-center">
            <input type="radio" name="type" value="expense" class="form-radio text-red-600" <?= (!isset($transaction) || $transaction['type'] === 'expense') ? 'checked' : '' ?>>
            <span class="ml-2">Uitgave</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="type" value="income" class="form-radio text-green-600" <?= (isset($transaction) && $transaction['type'] === 'income') ? 'checked' : '' ?>>
            <span class="ml-2">Inkomst</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="type" value="transfer" class="form-radio text-blue-600" <?= (isset($transaction) && $transaction['type'] === 'transfer') ? 'checked' : '' ?>>
            <span class="ml-2">Overschrijving</span>
        </label>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="account_id" class="block text-sm font-medium text-gray-700">Van rekening</label>
            <select id="account_id" name="account_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <?php foreach($accounts as $account): ?>
                    <option value="<?= $account['id'] ?>" <?= (isset($transaction) && $transaction['account_id'] == $account['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($account['name']) ?> (€<?= number_format($account['balance'], 2, ',', '.') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="to_account_container" class="<?= (!isset($transaction) || $transaction['type'] !== 'transfer') ? 'hidden' : '' ?>">
            <label for="to_account_id" class="block text-sm font-medium text-gray-700">Naar rekening</label>
            <select id="to_account_id" name="to_account_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <?php foreach($accounts as $account): ?>
                    <option value="<?= $account['id'] ?>" <?= (isset($transaction) && isset($transaction['to_account_id']) && $transaction['to_account_id'] == $account['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($account['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="amount" class="block text-sm font-medium text-gray-700">Bedrag</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">€</span>
                </div>
                <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                    class="block w-full pl-7 pr-12 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="0,00"
                    value="<?= isset($transaction) ? number_format($transaction['amount'], 2, '.', '') : '' ?>">
            </div>
        </div>
        
        <div>
            <label for="date" class="block text-sm font-medium text-gray-700">Datum</label>
            <input type="date" name="date" id="date" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                value="<?= isset($transaction) ? $transaction['date'] : date('Y-m-d') ?>">
        </div>
    </div>
    
    <div id="category_container" class="<?= (isset($transaction) && $transaction['type'] === 'transfer') ? 'hidden' : '' ?>">
        <label for="category_id" class="block text-sm font-medium text-gray-700">Categorie</label>
        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">-- Selecteer categorie --</option>
            <optgroup label="Uitgaven" id="expense_categories" class="<?= (isset($transaction) && $transaction['type'] === 'income') ? 'hidden' : '' ?>">
                <?php foreach($expenseCategories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= (isset($transaction) && $transaction['category_id'] == $category['id'] && $transaction['type'] === 'expense') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="Inkomsten" id="income_categories" class="<?= (!isset($transaction) || $transaction['type'] !== 'income') ? 'hidden' : '' ?>">
                <?php foreach($incomeCategories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= (isset($transaction) && $transaction['category_id'] == $category['id'] && $transaction['type'] === 'income') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
        </select>
    </div>
    
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">Beschrijving</label>
        <textarea id="description" name="description" rows="2"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Voeg een beschrijving toe"><?= isset($transaction) ? htmlspecialchars($transaction['description']) : '' ?></textarea>
    </div>
    
    <div>
        <label class="block text-sm font-medium text-gray-700">Bonnetje / Bewijs</label>
        <div class="mt-1 flex items-center">
            <?php if(isset($transaction) && $transaction['receipt_image']): ?>
                <div class="mr-4">
                    <a href="/uploads/receipts/<?= $transaction['receipt_image'] ?>" target="_blank" class="text-blue-600 hover:underline">
                        Huidige afbeelding bekijken
                    </a>
                </div>
            <?php endif; ?>
            <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                <span>Upload een bestand</span>
                <input id="receipt" name="receipt" type="file" class="sr-only" accept="image/*" capture>
            </label>
        </div>
        <p class="mt-2 text-sm text-gray-500">
            PNG, JPG, GIF tot 10MB
        </p>
        <div id="image_preview" class="mt-2 hidden">
            <img id="preview_img" src="#" alt="Voorbeeld" class="max-h-40 rounded">
        </div>
    </div>
    
    <div>
        <div class="relative flex items-start">
            <div class="flex items-center h-5">
                <input id="is_recurring" name="is_recurring" type="checkbox" 
                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                       <?= (isset($transaction) && $transaction['is_recurring']) ? 'checked' : '' ?>>
            </div>
            <div class="ml-3 text-sm">
                <label for="is_recurring" class="font-medium text-gray-700">Terugkerende transactie</label>
                <p class="text-gray-500">Deze transactie zal regelmatig terugkeren</p>
            </div>
        </div>
    </div>
    
    <div id="recurring_options" class="space-y-4 <?= (isset($transaction) && $transaction['is_recurring']) ? '' : 'hidden' ?>">
        <div>
            <label for="frequency" class="block text-sm font-medium text-gray-700">Frequentie</label>
            <select id="frequency" name="frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="daily">Dagelijks</option>
                <option value="weekly">Wekelijks</option>
                <option value="monthly" selected>Maandelijks</option>
                <option value="quarterly">Per kwartaal</option>
                <option value="yearly">Jaarlijks</option>
            </select>
        </div>
        
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700">Einddatum (optioneel)</label>
            <input type="date" name="end_date" id="end_date"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                value="<?= isset($transaction) && isset($transaction['end_date']) ? $transaction['end_date'] : '' ?>">
            <p class="mt-1 text-sm text-gray-500">Laat leeg voor een doorlopende transactie</p>
        </div>
    </div>
    
    <div class="flex justify-end space-x-3">
        <a href="/transactions" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Annuleren
        </a>
        <button type="submit" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <?= isset($transaction) ? 'Bijwerken' : 'Opslaan' ?>
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const categoryContainer = document.getElementById('category_container');
    const expenseCategories = document.getElementById('expense_categories');
    const incomeCategories = document.getElementById('income_categories');
    const toAccountContainer = document.getElementById('to_account_container');
    const isRecurringCheckbox = document.getElementById('is_recurring');
    const recurringOptions = document.getElementById('recurring_options');
    
    // Verander categorieën op basis van type
    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'expense') {
                categoryContainer.classList.remove('hidden');
                expenseCategories.classList.remove('hidden');
                incomeCategories.classList.add('hidden');
                toAccountContainer.classList.add('hidden');
            } else if (this.value === 'income') {
                categoryContainer.classList.remove('hidden');
                expenseCategories.classList.add('hidden');
                incomeCategories.classList.remove('hidden');
                toAccountContainer.classList.add('hidden');
            } else if (this.value === 'transfer') {
                categoryContainer.classList.add('hidden');
                toAccountContainer.classList.remove('hidden');
            }
        });
    });
    
    // Toon/verberg terugkerende opties
    isRecurringCheckbox.addEventListener('change', function() {
        if (this.checked) {
            recurringOptions.classList.remove('hidden');
        } else {
            recurringOptions.classList.add('hidden');
        }
    });
    
    // Afbeeldingsvoorbeeld
    const receiptInput = document.getElementById('receipt');
    const imagePreview = document.getElementById('image_preview');
    const previewImg = document.getElementById('preview_img');
    
    receiptInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                imagePreview.classList.remove('hidden');
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Camera of gallerij optie voor mobiel
    if ('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices) {
        const captureButton = document.createElement('button');
        captureButton.textContent = 'Camera gebruiken';
        captureButton.className = 'ml-3 py-1 px-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50';
        
        captureButton.addEventListener('click', function(e) {
            e.preventDefault();
            receiptInput.click();
        });
        
        receiptInput.parentNode.appendChild(captureButton);
    }
});
</script>