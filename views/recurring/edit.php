<!-- views/recurring/edit.php -->
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex items-center mb-6">
        <a href="/recurring" class="mr-4 text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <h1 class="text-2xl font-bold">Terugkerende Transactie Bewerken</h1>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="/recurring/update" method="POST" class="space-y-6">
            <input type="hidden" name="id" value="<?= $recurringTransaction['id'] ?>">
            
            <div class="flex flex-wrap -mx-3 mb-4">
                <div class="w-full px-3">
                    <div class="flex items-center space-x-6">
                        <label class="inline-flex items-center">
                            <input type="radio" name="type" value="expense" class="form-radio text-red-600" <?= $recurringTransaction['type'] === 'expense' ? 'checked' : '' ?>>
                            <span class="ml-2">Uitgave</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="type" value="income" class="form-radio text-green-600" <?= $recurringTransaction['type'] === 'income' ? 'checked' : '' ?>>
                            <span class="ml-2">Inkomst</span>
                        </label>
                    </div>
                    <?php if (!empty($errors['type'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['type'] ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="account_id" class="block text-sm font-medium text-gray-700">Rekening</label>
                    <select id="account_id" name="account_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                        <option value="">Selecteer rekening</option>
                        <?php foreach($accounts as $account): ?>
                            <option value="<?= $account['id'] ?>" <?= $recurringTransaction['account_id'] == $account['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($account['name']) ?> (€<?= number_format($account['balance'], 2, ',', '.') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['account_id'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['account_id'] ?></p>
                    <?php endif; ?>
                </div>
                
                <div id="category_container">
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Categorie</label>
                    <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                        <option value="">-- Selecteer categorie --</option>
                        <optgroup label="Uitgaven" id="expense_categories" class="<?= $recurringTransaction['type'] !== 'expense' ? 'hidden' : '' ?>">
                            <?php foreach($expenseCategories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $recurringTransaction['category_id'] == $category['id'] && $recurringTransaction['type'] === 'expense' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Inkomsten" id="income_categories" class="<?= $recurringTransaction['type'] !== 'income' ? 'hidden' : '' ?>">
                            <?php foreach($incomeCategories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $recurringTransaction['category_id'] == $category['id'] && $recurringTransaction['type'] === 'income' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
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
                            class="block w-full pl-7 pr-12 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 p-2 border"
                            placeholder="0,00"
                            value="<?= number_format($recurringTransaction['amount'], 2, '.', '') ?>">
                    </div>
                    <?php if (!empty($errors['amount'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['amount'] ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="frequency" class="block text-sm font-medium text-gray-700">Frequentie</label>
                    <select id="frequency" name="frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                        <option value="daily" <?= $recurringTransaction['frequency'] === 'daily' ? 'selected' : '' ?>>Dagelijks</option>
                        <option value="weekly" <?= $recurringTransaction['frequency'] === 'weekly' ? 'selected' : '' ?>>Wekelijks</option>
                        <option value="monthly" <?= $recurringTransaction['frequency'] === 'monthly' ? 'selected' : '' ?>>Maandelijks</option>
                        <option value="quarterly" <?= $recurringTransaction['frequency'] === 'quarterly' ? 'selected' : '' ?>>Per kwartaal</option>
                        <option value="yearly" <?= $recurringTransaction['frequency'] === 'yearly' ? 'selected' : '' ?>>Jaarlijks</option>
                    </select>
                    <?php if (!empty($errors['frequency'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['frequency'] ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Startdatum</label>
                    <input type="date" id="start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border bg-gray-100" readonly value="<?= $recurringTransaction['start_date'] ?>">
                    <p class="mt-1 text-sm text-gray-500">Startdatum kan niet worden gewijzigd</p>
                </div>
                
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">Einddatum (optioneel)</label>
                    <input type="date" name="end_date" id="end_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                        value="<?= $recurringTransaction['end_date'] ?>">
                    <p class="mt-1 text-sm text-gray-500">Laat leeg voor een doorlopende transactie</p>
                    <?php if (!empty($errors['end_date'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['end_date'] ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Beschrijving</label>
                <textarea id="description" name="description" rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border"
                    placeholder="Voeg een beschrijving toe"><?= htmlspecialchars($recurringTransaction['description']) ?></textarea>
            </div>
            
            <div class="mt-3">
                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input id="is_active" name="is_active" type="checkbox" value="1"
                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                            <?= $recurringTransaction['is_active'] ? 'checked' : '' ?>>
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_active" class="font-medium text-gray-700">Actief</label>
                        <p class="text-gray-500">Wanneer uitgeschakeld wordt deze terugkerende transactie niet meer uitgevoerd</p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <a href="/recurring" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Annuleren
                </a>
                <button type="submit" class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Bijwerken
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const expenseCategories = document.getElementById('expense_categories');
    const incomeCategories = document.getElementById('income_categories');
    
    // Verander categorieën op basis van type
    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'expense') {
                expenseCategories.classList.remove('hidden');
                incomeCategories.classList.add('hidden');
            } else if (this.value === 'income') {
                expenseCategories.classList.add('hidden');
                incomeCategories.classList.remove('hidden');
            }
        });
    });
    
    // Controleer einddatum
    const startDateValue = document.getElementById('start_date').value;
    const endDateInput = document.getElementById('end_date');
    
    endDateInput.addEventListener('change', function() {
        if (this.value && startDateValue) {
            const startDate = new Date(startDateValue);
            const endDate = new Date(this.value);
            
            if (endDate < startDate) {
                alert('De einddatum moet na de startdatum liggen');
                this.value = '';
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>