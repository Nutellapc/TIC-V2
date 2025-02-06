document.addEventListener("DOMContentLoaded", function () {
    // Verificar si ya existe el botón para evitar duplicados
    if (document.getElementById("ml-dashboard-button")) return;

    // Crear el botón flotante
    var btn = document.createElement("a");
    btn.id = "ml-dashboard-button";
    btn.href = "/local/ml_dashboard2/index.php"; // URL del dashboard
    btn.innerText = "📊 ML Dashboard";
    btn.classList.add("floating-dashboard-button");

    // Agregar el botón al body
    document.body.appendChild(btn);
});
