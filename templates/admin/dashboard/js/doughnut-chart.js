// doughnut-chart.js

document.addEventListener('DOMContentLoaded', function() {
    const data = [4305, 859, 482, 138];
    const labels2 = ["Mercados Lite", "Mercados Global", "VIP", "VIP PRO"];
    const colors2 = ["#9b8bfc", "#faca54", "#fa82f3", "#a8d0fa"];
    const total = data.reduce((a, b) => a + b, 0);
    const percentages = data.map(value => ((value / total) * 100).toFixed(2) + '%');

    const canvasDoughnut = document.getElementById('doughnut-chart');
    if (!canvasDoughnut) {
        console.error('Elemento <canvas id="doughnut-chart"> no encontrado.');
        return;
    }
    const ctx = canvasDoughnut.getContext('2d');
    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels2,
            datasets: [{
                backgroundColor: colors2,
                data: data
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ` ${context.raw} (${((context.raw / total) * 100).toFixed(2)}%)`;
                        }
                    }
                }
            },
            layout: {
                padding: {
                    left: 20,
                    right: 20,
                    top: 20,
                    bottom: 20
                }
            }
        }
    });

    const legendContainer = document.getElementById('chart-legend');
    if (!legendContainer) {
        console.error('Elemento <div id="chart-legend"> no encontrado.');
        return;
    }
    labels2.forEach((label, index) => {
        const legendItem = document.createElement('div');
        legendItem.classList.add('chart-legend-item');
        legendItem.innerHTML = `
        <div class="doughnut-info">
            <span style="background-color: ${colors2[index]}"></span>
            <p class="card-title">${label}</p>
        </div>
        <div class="legend-details">
            <p class="legend-numbers">${data[index]}</p>
            <p class="legend-percents">${percentages[index]}</p>
        </div>`;
        legendContainer.appendChild(legendItem);
    });
});
