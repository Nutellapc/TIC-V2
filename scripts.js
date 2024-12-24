document.addEventListener("DOMContentLoaded", () => {

function setupCounters(connectedTime) {
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

    // Configurar el contador de Tiempo Conectado (en horas)
    const connectedTimeElement = document.querySelector('[data-target="average_connected_time"]');
    if (connectedTimeElement) {
        // Convertir segundos a horas y redondear a dos decimales
        const hours = (connectedTime / 3600).toFixed(2);
        connectedTimeElement.setAttribute('data-target', hours);
        connectedTimeElement.innerText = hours;
    }
}
