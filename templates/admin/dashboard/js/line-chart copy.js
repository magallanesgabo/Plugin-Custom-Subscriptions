// line-chart.js

document.addEventListener('DOMContentLoaded', function() {
    const chartColors = {
        purple: {
            default: "rgba(149, 76, 233, 1)",
            half: "rgba(149, 76, 233, 0.5)",
            quarter: "rgba(149, 76, 233, 0.5)",
            zero: "rgba(149, 76, 233, 0)"
        },
        indigo: {
            default: "rgba(80, 102, 120, 1)",
            quarter: "rgba(80, 102, 120, 0.25)",
            quarterTransparent: "rgba(80, 102, 120, 0.13)"
        },
        tooltip: {
            backgroundColor: "rgba(255, 255, 255, 1)",
            titleColor: "rgba(23, 23, 23, 1)",
            bodyColor: "rgba(23, 23, 23, 0.9)",
            borderColor: "rgba(187, 187, 187, 1)",
            borderWidth: 1.2
        }
    };

    const weight = [60.0, 60.2, 59.1, 61.4, 59.9, 60.2, 59.8, 58.6, 59.6, 59.2, 60, 61];

    const labels = [
        "Week 1",
        "Week 2",
        "Week 3",
        "Week 4",
        "Week 5",
        "Week 6",
        "Week 7",
        "Week 8",
        "Week 9",
        "Week 10",
        "Week 11",
        "Week 12"
    ];

    const canvas = document.getElementById("canvas");
    if (!canvas) {
        console.error('Elemento <canvas id="canvas"> no encontrado.');
        return;
    }
    const ctx2 = canvas.getContext("2d");
    ctx2.canvas.height = 120;

    const gradientPurple = ctx2.createLinearGradient(0, 25, 0, 260);
    gradientPurple.addColorStop(0, chartColors.purple.half);
    gradientPurple.addColorStop(0.35, chartColors.purple.quarter);
    gradientPurple.addColorStop(1, chartColors.purple.zero);

    const options = {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                fill: true,
                backgroundColor: gradientPurple,
                borderColor: chartColors.purple.default,
                borderWidth: 2,
                data: weight,
                tension: 0.2,
                pointRadius: 0, // Oculta los puntos inicialmente
                pointHoverRadius: 3, // Tamaño de los puntos al pasar el cursor
                pointBackgroundColor: chartColors.purple.default // Color de los puntos
            }]
        },
        options: {
            layout: {
                padding: 10
            },
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: chartColors.tooltip.backgroundColor, // Fondo del tooltip
                    titleColor: chartColors.tooltip.titleColor, // Color del título
                    bodyColor: chartColors.tooltip.bodyColor, // Color del texto del cuerpo
                    borderColor: chartColors.tooltip.borderColor, // Color del borde
                    borderWidth: chartColors.tooltip.borderWidth, // Ancho del borde
                    titleFont: {
                        family: "Poppins",
                        size: 14,
                        weight: '500'
                    },
                    bodyFont: {
                        family: "Poppins",
                        size: 13,
                        weight: '600'
                    },
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y;
                            return label;
                        }
                    },
                    boxPadding: 8, // Padding alrededor de la caja del tooltip
                    displayColors: true // Mostrar el recuadro de color
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false, // Quita las líneas verticales
                        drawBorder: false
                    },
                    border: {
                        display: false // Quita el borde vertical izquierdo
                    },
                    ticks: {
                        padding: 10,
                        autoSkip: false,
                        maxRotation: 0,
                        minRotation: 0
                    }
                },
                y: {
                    grid: {
                        display: true,
                        color: chartColors.indigo.quarterTransparent, // Ajusta la opacidad aquí
                        drawTicks: false, // Oculta las líneas de ticks
                        drawOnChartArea: true, // Asegura que las líneas de la cuadrícula se dibujen solo dentro del área del gráfico
                        drawBorder: false // Quita el borde vertical izquierdo
                    },
                    border: {
                        display: false // Quita el borde vertical izquierdo
                    },
                    ticks: {
                        beginAtZero: false,
                        max: 63,
                        min: 57,
                        padding: 15
                    }
                }
            },
            hover: {
                mode: 'nearest',
                intersect: true
            }
        }
    };

    const myLine = new Chart(ctx2, options);
    Chart.defaults.font.color = chartColors.indigo.default;
    Chart.defaults.font.family = "Poppins";

    // Event listener for mouse enter
    ctx2.canvas.addEventListener('mouseenter', () => {
        myLine.data.datasets.forEach(dataset => {
            dataset.pointRadius = 2; // Mostrar puntos
        });
        myLine.update(); // Actualizar el gráfico
    });

    // Event listener for mouse leave
    ctx2.canvas.addEventListener('mouseleave', () => {
        myLine.data.datasets.forEach(dataset => {
            dataset.pointRadius = 0; // Ocultar puntos
        });
        myLine.update(); // Actualizar el gráfico
    });
});
