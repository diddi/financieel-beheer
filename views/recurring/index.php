<!-- views/recurring/index.php -->
<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Terugkerende Transacties</h1>
        
        <a href="/recurring/create" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Nieuwe terugkerende transactie
        </a>
    </div>
    
    <?php if (empty($recurringTransactions)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-gray-500 mb-4">Je hebt nog geen terugkerende transacties ingesteld.</p>
            <a href="/recurring/create" class="text-blue-600 hover:underline">
                Stel je eerste terugkerende transactie in
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beschrijving</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bedrag</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rekening</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categorie</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequentie</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volgende datum</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recurringTransactions as $transaction): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($transaction['description']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?= $transaction['type'] === 'expense' ? 'text-red-600' : 'text-green-600' ?>">
                                <?= $transaction['type'] === 'expense' ? '-' : '+' ?>€<?= number_format($transaction['amount'], 2, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($transaction['account_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if (isset($transaction['category_name'])): ?>
                                    <div class="flex items-center">
                                        <div class="h-2.5 w-2.5 rounded-full mr-2" style="background-color: <?= htmlspecialchars($transaction['color']) ?>"></div>
                                        <?= htmlspecialchars($transaction['category_name']) ?>
                                    </div>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php
                                    switch ($transaction['frequency']) {
                                        case 'daily': echo 'Dagelijks'; break;
                                        case 'weekly': echo 'Wekelijks'; break;
                                        case 'monthly': echo 'Maandelijks'; break;
                                        case 'quarterly': echo 'Per kwartaal'; break;
                                        case 'yearly': echo 'Jaarlijks'; break;
                                    }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= date('d-m-Y', strtotime($transaction['next_due_date'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php if (strtotime($transaction['next_due_date']) <= time()): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Vandaag
                                    </span>
                                <?php elseif (strtotime($transaction['next_due_date']) <= strtotime('+3 days')): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Binnenkort
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Actief
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="/recurring/edit?id=<?= $transaction['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">Bewerken</a>
                                <a href="/recurring/delete?id=<?= $transaction['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Weet je zeker dat je deze terugkerende transactie wilt verwijderen?')">Verwijderen</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>