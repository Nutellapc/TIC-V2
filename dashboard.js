document.addEventListener('DOMContentLoaded', () => {
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

    // Configurar gráficos de tendencias
    const gradesCtx = document.getElementById('gradesChart').getContext('2d');
    const assignmentsCtx = document.getElementById('assignmentsChart').getContext('2d');

    new Chart(gradesCtx, {
        type: 'line',
        data: {
            labels: ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
            datasets: [{
                label: 'Promedio de Calificaciones',
                data: [75, 80, 85, 90],
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
            }]
        },
    });

    new Chart(assignmentsCtx, {
        type: 'bar',
        data: {
            labels: ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
            datasets: [{
                label: 'Tareas Enviadas',
                data: [10, 15, 20, 25],
                backgroundColor: '#F59E0B',
            }]
        },
    });
});
