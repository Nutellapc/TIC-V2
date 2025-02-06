define(['jquery'], function($) {
    return {
        init: function() {


            // Agregar funcionalidad al botón flotante
            $("#ml-dashboard-button").on("click", function() {
                alert("📊 Dashboard de Machine Learning");
            });
        }
    };
});
