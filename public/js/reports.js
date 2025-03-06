/**
 * Reports.js - JavaScript voor rapportages en grafieken
 * 
 * Dit bestand bevat functies om verschillende soorten grafieken te genereren voor de rapportages
 */

/**
 * Genereert een tijdlijn grafiek met inkomsten en uitgaven
 * 
 * @param {string} elementId - ID van het canvas-element
 * @param {object} data - Object met labels, income en expenses arrays
 */
function createTimelineChart(elementId, data) {
    const ctx = document.getElementById(elementId).getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Inkomsten',
                    data: data.income,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Uitgaven',
                    data: data.expenses,
                    backgroundColor: 'rgba(239, 68, 68, 0.2)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '€' + value.toLocaleString('nl-NL');
                        }
                    }
                }
            }
        }
    });
}

/**
 * Genereert een taartdiagram voor een categorieweergave
 * 
 * @param {string} elementId - ID van het canvas-element
 * @param {Array} data - Array met data-objecten (name, amount, color)
 * @param {string} type - Type van de data ('expense' of 'income')
 * @param {number} total - Totaalbedrag
 */
function createCategoryPieChart(elementId, data, type, total) {
    const values = data.map(item => item.amount);
    const labels = data.map(item => item.name);
    const colors = data.map(item => item.color);
    
    const ctx = document.getElementById(elementId).getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
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
                        boxWidth: 15,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '€' + context.raw.toLocaleString('nl-NL', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                            
                            const percentage = ((context.raw / total) * 100).toFixed(1);
                            label += ` (${percentage}%)`;
                            
                            return label;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Genereert een staafdiagram voor een maandelijkse weergave
 * 
 * @param {string} elementId - ID van het canvas-element
 * @param {object} data - Object met labels en amounts arrays
 * @param {string} type - Type van de data ('expense' of 'income')
 */
function createMonthlyBarChart(elementId, data, type) {
    const color = type === 'expense' ? 'rgb(239, 68, 68)' : 'rgb(16, 185, 129)';
    const bgColor = type === 'expense' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(16, 185, 129, 0.2)';
    
    const ctx = document.getElementById(elementId).getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: type === 'expense' ? 'Uitgaven' : 'Inkomsten',
                data: data.amounts,
                backgroundColor: bgColor,
                borderColor: color,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '€' + value.toLocaleString('nl-NL');
                        }
                    }
                }
            }
        }
    });
}

/**
 * Genereert een gecombineerd staafdiagram met inkomsten en uitgaven per maand
 * 
 * @param {string} elementId - ID van het canvas-element
 * @param {object} data - Object met labels, income en expenses arrays
 */
function createMonthlyComparisonChart(elementId, data) {
    const ctx = document.getElementById(elementId).getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Inkomsten',
                    data: data.income,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1
                },
                {
                    label: 'Uitgaven',
                    data: data.expenses,
                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '€' + value.toLocaleString('nl-NL');
                        }
                    }
                }
            }
        }
    });
}

// Event listeners voor filters
document.addEventListener('DOMContentLoaded', function() {
    // Direct initaliseren van de datumvelden met defaults als ze leeg zijn
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput && !startDateInput.value) {
        // Standaard 6 maanden terug
        startDateInput.value = new Date(new Date().setMonth(new Date().getMonth() - 6)).toISOString().split('T')[0];
    }
    
    if (endDateInput && !endDateInput.value) {
        // Standaard huidige datum
        endDateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // Snelle datumfilters
    const quickFilterButtons = document.querySelectorAll('.quick-filter');
    if (quickFilterButtons.length > 0) {
        quickFilterButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const period = this.getAttribute('data-period');
                const today = new Date();
                let startDate = new Date();
                
                // Bereken startdatum op basis van de periode
                if (period === 'month') {
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                } else if (period === '3months') {
                    startDate = new Date(today.getFullYear(), today.getMonth() - 2, 1);
                } else if (period === '6months') {
                    startDate = new Date(today.getFullYear(), today.getMonth() - 5, 1);
                } else if (period === 'year') {
                    startDate = new Date(today.getFullYear(), 0, 1);
                } else if (period === 'ytd') {
                    startDate = new Date(today.getFullYear(), 0, 1);
                } else if (period === 'all') {
                    startDate = new Date(2000, 0, 1); // Zeer lange tijd terug
                }
                
                // Zet de datumvelden
                if (startDateInput) {
                    startDateInput.value = startDate.toISOString().split('T')[0];
                }
                
                if (endDateInput) {
                    endDateInput.value = today.toISOString().split('T')[0];
                }
                
                // Submit het formulier
                const form = document.querySelector('form');
                if (form) {
                    form.submit();
                }
            });
        });
    }
});
