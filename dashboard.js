if (typeof activityViewsChartInstance === 'undefined') {
    var activityViewsChartInstance;
}
if (typeof gradesBarChartInstance === 'undefined') {
    var gradesBarChartInstance;
}


document.addEventListener('DOMContentLoaded', () => {
    // Animar los contadores
    const counters = document.querySelectorAll('.font-bold[data-target]');
    counters.forEach(counter => {
        let count = 0;
        const target = +counter.getAttribute('data-target');
        const increment = target / 100;

        const updateCounter = () => {
            if (count < target) {
                count += increment;
                counter.innerText = Math.ceil(count);
                setTimeout(updateCounter, 20);
            } else {
                counter.innerText = target;
            }
        };

        updateCounter();
    });

    // Configurar gráfico de vistas de actividades
    const activityViewsCtx = document.getElementById('activityViewsChart')?.getContext('2d');
    if (activityViewsCtx && dashboardData.activity_views) {
        const activityData = dashboardData.activity_views;

        new Chart(activityViewsCtx, {
            type: 'doughnut',
            data: {
                labels: activityData.map(activity => activity.name),
                datasets: [{
                    label: 'Vistas por Actividad',
                    data: activityData.map(activity => activity.views),
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                const label = tooltipItem.label || '';
                                const value = tooltipItem.raw || 0;
                                return `${label}: ${value} vistas`;
                            }
                        }
                    }
                }
            }
        });
    } else {
        console.warn('No se encontró el canvas o no hay datos para activityViewsChart');
    }


    // Configurar gráfico de calificaciones por estudiante
    const gradesBarChart = document.getElementById('gradesBarChart');
    if (gradesBarChart) {
        const gradesBarCtx = gradesBarChart.getContext('2d');
        new Chart(gradesBarCtx, {
            type: 'bar',
            data: {
                labels: dashboardData.student_grades.map(item => item.student),
                datasets: [{
                    label: 'Calificaciones',
                    data: dashboardData.student_grades.map(item => item.grade),
                    backgroundColor: '#4CAF50',
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true },
                    tooltip: { enabled: true }
                }
            }
        });
    }


});
