document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = siteData.baseUrl;

    // Construir la URL del endpoint
    const endpoint = `${baseUrl}/wp-json/produ/v1/subscriptions-stats`;

    fetch(endpoint)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data); // Verificar los datos recibidos

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

            // Extraer las fechas y el número de usuarios de los datos recibidos
            const labels = data.map(item => item.date); // Fechas como etiquetas
            const subscribersData = data.map(item => item.count); // Número de usuarios como datos

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
                        data: subscribersData, // Número de usuarios por fecha
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
                        zoom: {
                            pan: {
                                enabled: true,
                                mode: 'x', // Solo pan horizontal
                                speed: 20, // Velocidad de desplazamiento
                            },
                            zoom: {
                                enabled: false // Deshabilitar zoom si solo quieres pan
                            }
                        },
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: chartColors.tooltip.backgroundColor,
                            titleColor: chartColors.tooltip.titleColor,
                            bodyColor: chartColors.tooltip.bodyColor,
                            borderColor: chartColors.tooltip.borderColor,
                            borderWidth: chartColors.tooltip.borderWidth,
                            padding: 10,
                        }
                    },
                    scales: {
                        x: {
                            type: 'category', // Mantener tipo 'category' para fechas
                            grid: { display: false, drawBorder: false },
                            ticks: {
                                padding: 10,
                                autoSkip: false, // No saltar etiquetas
                                maxRotation: 0, // Evitar rotación de etiquetas
                                maxTicksLimit: 12 // Mostrar solo 12 elementos en el eje X
                            }
                        },
                        y: {
                            grid: { display: true, color: chartColors.indigo.quarterTransparent }
                        }
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
        })
        .catch(error => console.error('Error fetching data:', error));
});
