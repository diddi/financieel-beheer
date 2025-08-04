<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.1.0"></script>

<div class="p-6 bg-white rounded-lg shadow-md mb-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Financiële Inzichten</h1>
        <div class="text-gray-500">
            <span class="font-semibold"><?= $currentMonthName ?></span>
            <span class="text-sm">(<?= $monthProgress ?>% voltooid)</span>
        </div>
    </div>
    
    <!-- Maandelijks Overzicht -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="p-5 bg-blue-50 rounded-lg border border-blue-100">
            <h3 class="text-md font-medium text-blue-800 mb-2">Inkomsten</h3>
            <div class="text-2xl font-bold text-blue-600">€<?= number_format($incomes, 2, ',', '.') ?></div>
            <div class="text-sm mt-2 <?= $monthlyComparison['income']['percentage'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                <?= $monthlyComparison['income']['percentage'] >= 0 ? '+' : '' ?><?= $monthlyComparison['income']['percentage'] ?>% 
                <span class="text-gray-500">t.o.v. <?= $previousMonthName ?></span>
            </div>
        </div>
        
        <div class="p-5 bg-red-50 rounded-lg border border-red-100">
            <h3 class="text-md font-medium text-red-800 mb-2">Uitgaven</h3>
            <div class="text-2xl font-bold text-red-600">€<?= number_format($expenses, 2, ',', '.') ?></div>
            <div class="text-sm mt-2 <?= $monthlyComparison['expenses']['percentage'] <= 0 ? 'text-green-600' : 'text-red-600' ?>">
                <?= $monthlyComparison['expenses']['percentage'] > 0 ? '+' : '' ?><?= $monthlyComparison['expenses']['percentage'] ?>% 
                <span class="text-gray-500">t.o.v. <?= $previousMonthName ?></span>
            </div>
        </div>
        
        <div class="p-5 bg-green-50 rounded-lg border border-green-100">
            <h3 class="text-md font-medium text-green-800 mb-2">Balans</h3>
            <div class="text-2xl font-bold <?= ($incomes - $expenses) >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                €<?= number_format($incomes - $expenses, 2, ',', '.') ?>
            </div>
            <div class="text-sm mt-2 text-gray-500">
                <?= ($incomes - $expenses) >= 0 ? 'Overschot' : 'Tekort' ?> deze maand
            </div>
        </div>
        
        <div class="p-5 bg-purple-50 rounded-lg border border-purple-100">
            <h3 class="text-md font-medium text-purple-800 mb-2">Gemiddelde uitgave</h3>
            <?php 
            $expenseTransactions = array_filter($currentMonthTransactions, function($t) { return $t['type'] === 'expense'; });
            $expenseCount = count($expenseTransactions);
            $avgExpense = ($expenseCount > 0) ? ($expenses / $expenseCount) : 0; 
            ?>
            <div class="text-2xl font-bold text-purple-600">€<?= number_format($avgExpense, 2, ',', '.') ?></div>
            <div class="text-sm mt-2 text-gray-500">
                Per transactie
            </div>
        </div>
    </div>
    
    <!-- Uitgaven per Categorie & Budgetvoortgang -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Uitgaven per Categorie -->
        <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Uitgaven per Categorie</h3>
            <div class="relative" style="height: 300px;">
                <canvas id="categoryPieChart"></canvas>
            </div>
        </div>
        
        <!-- Budget Voortgang -->
        <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Budget Voortgang</h3>
            <div class="space-y-4">
                <?php if (empty($budgetProgress)): ?>
                    <div class="text-gray-500 text-center py-6">Geen actieve budgetten gevonden</div>
                <?php else: ?>
                    <?php foreach (array_slice($budgetProgress, 0, 5) as $budget): ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium"><?= $budget['name'] ?></span>
                                <span class="text-sm text-gray-500">
                                    €<?= number_format($budget['spent'], 2, ',', '.') ?> / €<?= number_format($budget['budget'], 2, ',', '.') ?>
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full bg-<?= $budget['color'] ?>-500" style="width: <?= $budget['percentage'] ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($budgetProgress) > 5): ?>
                        <div class="text-center mt-2">
                            <a href="/budgets" class="text-blue-500 hover:text-blue-700 text-sm">Bekijk alle budgetten</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Uitgaven & Trendgrafiek -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Top Uitgaven -->
        <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Top Uitgaven</h3>
            <?php if (empty($topExpenses)): ?>
                <div class="text-gray-500 text-center py-6">Geen uitgaven gevonden</div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($topExpenses as $expense): ?>
                        <?php 
                            $category = \App\Models\Category::getById($expense['category_id']);
                            $categoryName = $category ? $category['name'] : 'Onbekend';
                        ?>
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-100">
                            <div>
                                <div class="font-medium"><?= htmlspecialchars($expense['description']) ?></div>
                                <div class="text-sm text-gray-500"><?= $categoryName ?> • <?= date('d-m-Y', strtotime($expense['date'])) ?></div>
                            </div>
                            <div class="text-lg font-semibold text-red-600">
                                -€<?= number_format(abs($expense['amount']), 2, ',', '.') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Uitgaventrend -->
        <div class="bg-gray-50 p-5 rounded-lg border border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Uitgaventrend (Laatste 30 dagen)</h3>
            <div class="relative" style="height: 300px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Taartdiagram voor uitgaven per categorie
const categoryData = <?= json_encode(array_values($categoryExpenses)) ?>;
const categoryLabels = <?= json_encode(array_keys($categoryExpenses)) ?>;
const categoryColors = <?= json_encode($categoryColors) ?>;

if (categoryData.length > 0) {
    const categoryPieChart = new Chart(
        document.getElementById('categoryPieChart'),
        {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: categoryColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                const total = context.dataset.data.reduce((total, value) => total + value, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `€${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        }
    );
}

// Lijndiagram voor uitgaventrend
const trendLabels = <?= $trendLabels ?>;
const trendData = <?= $trendData ?>;

const formattedLabels = trendLabels.map(date => {
    const d = new Date(date);
    return `${d.getDate()}-${d.getMonth() + 1}`;
});

const trendChart = new Chart(
    document.getElementById('trendChart'),
    {
        type: 'line',
        data: {
            labels: formattedLabels,
            datasets: [{
                label: 'Dagelijkse uitgaven',
                data: trendData,
                fill: false,
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `€${context.raw.toFixed(2)}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 10
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '€' + value;
                        }
                    }
                }
            }
        }
    }
);
</script> 