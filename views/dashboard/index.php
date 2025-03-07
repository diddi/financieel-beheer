<?php include __DIR__ . '/../layouts/header.php'; ?>
<?php include __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="flex-1 p-6 overflow-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <p class="text-gray-600">Welkom terug, <?= htmlspecialchars($user['username']) ?></p>
    </div>
    
    <!-- Account-overzichten -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php foreach ($accounts as $account): ?>
            <div class="bg-white rounded-lg shadow p-6 border-l-4 <?= $account['balance'] >= 0 ? 'border-green-500' : 'border-red-500' ?>">
                <h3 class="font-semibold text-lg"><?= htmlspecialchars($account['name']) ?></h3>
                <div class="mt-2">
                    <span class="text-2xl font-bold <?= $account['balance'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                        €<?= number_format($account['balance'], 2, ',', '.') ?>
                    </span>
                </div>
                <div class="flex justify-end mt-2">
                    <a href="/accounts/<?= $account['id'] ?>" class="text-blue-600 hover:underline text-sm">
                        Details →
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Inkomsten vs. Uitgaven (huidige maand) -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Maandoverzicht</h2>
        <div class="flex justify-between items-center mb-4">
            <div>
                <span class="font-semibold text-gray-600">Inkomsten</span>
                <div class="text-xl font-bold text-green-600">€<?= number_format($monthIncome, 2, ',', '.') ?></div>
            </div>
            <div>
                <span class="font-semibold text-gray-600">Uitgaven</span>
                <div class="text-xl font-bold text-red-600">€<?= number_format($monthExpenses, 2, ',', '.') ?></div>
            </div>
            <div>
                <span class="font-semibold text-gray-600">Balans</span>
                <div class="text-xl font-bold <?= ($monthIncome - $monthExpenses) >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                    €<?= number_format($monthIncome - $monthExpenses, 2, ',', '.') ?>
                </div>
            </div>
        </div>
        <div class="w-full h-64">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Recente transacties -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Recente transacties</h2>
                <a href="/transactions" class="text-blue-600 hover:underline text-sm">Alle bekijken →</a>
            </div>
            
            <div class="divide-y">
                <?php foreach($recentTransactions as $transaction): ?>
                    <div class="py-3 flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-2 h-8 rounded-full mr-3" style="background-color: <?= htmlspecialchars($transaction['color']) ?>"></div>
                            <div>
                                <div class="font-medium"><?= htmlspecialchars($transaction['description'] ?: $transaction['category_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($transaction['account_name']) ?> • <?= date('d-m-Y', strtotime($transaction['date'])) ?></div>
                            </div>
                        </div>
                        <div class="font-bold <?= $transaction['type'] === 'expense' ? 'text-red-600' : 'text-green-600' ?>">
                            <?= $transaction['type'] === 'expense' ? '-' : '+' ?>€<?= number_format($transaction['amount'], 2, ',', '.') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Budget voortgang -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Budget voortgang</h2>
                <a href="/budgets" class="text-blue-600 hover:underline text-sm">Alle budgetten →</a>
            </div>
            
            <?php foreach($budgets as $budget): ?>
                <?php 
                    $percentage = ($budget['spent'] / $budget['amount']) * 100;
                    $color = $percentage < 70 ? 'bg-green-500' : ($percentage < 90 ? 'bg-yellow-500' : 'bg-red-500');
                ?>
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-medium"><?= htmlspecialchars($budget['category_name']) ?></span>
                        <span class="text-sm">
                            €<?= number_format($budget['spent'], 2, ',', '.') ?> / 
                            €<?= number_format($budget['amount'], 2, ',', '.') ?>
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full <?= $color ?>" style="width: <?= min(100, $percentage) ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart data
        const chartData = <?= json_encode($chartData) ?>;
        
        // Monthly Chart
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Inkomsten',
                        data: chartData.income,
                        backgroundColor: 'rgba(34, 197, 94, 0.5)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1
                    },
                    {
                        label: 'Uitgaven',
                        data: chartData.expenses,
                        backgroundColor: 'rgba(239, 68, 68, 0.5)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + value.toLocaleString('nl-NL');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '€' + context.raw.toLocaleString('nl-NL', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
<?php if (!empty($upcomingRecurring)): ?>
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Aankomende terugkerende transacties</h2>
        <a href="/recurring" class="text-blue-600 hover:underline text-sm">Alle bekijken →</a>
    </div>
    
    <div class="divide-y">
        <?php foreach($upcomingRecurring as $transaction): ?>
            <div class="py-3 flex justify-between items-center">
                <div class="flex items-center">
                    <div class="w-2 h-8 rounded-full mr-3" style="background-color: <?= htmlspecialchars($transaction['color'] ?? '#9E9E9E') ?>"></div>
                    <div>
                        <div class="font-medium"><?= htmlspecialchars($transaction['description']) ?></div>
                        <div class="text-sm text-gray-500">
                            <?= htmlspecialchars($transaction['account_name']) ?> • 
                            <?= date('d-m-Y', strtotime($transaction['next_due_date'])) ?>
                            <?php
                                $today = date('Y-m-d');
                                $daysUntil = (strtotime($transaction['next_due_date']) - strtotime($today)) / (60 * 60 * 24);
                                if ($daysUntil <= 0) {
                                    echo '<span class="ml-2 px-2 py-0.5 bg-red-100 text-red-800 rounded-full text-xs">Vandaag</span>';
                                } elseif ($daysUntil <= 3) {
                                    echo '<span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded-full text-xs">Binnen ' . ceil($daysUntil) . ' dagen</span>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="font-bold <?= $transaction['type'] === 'expense' ? 'text-red-600' : 'text-green-600' ?>">
                    <?= $transaction['type'] === 'expense' ? '-' : '+' ?>€<?= number_format($transaction['amount'], 2, ',', '.') ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../layouts/footer.php'; ?>