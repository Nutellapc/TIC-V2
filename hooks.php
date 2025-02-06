<?php
namespace local_ml_dashboard2;

use core\hook\output\before_footer_html_generation;

/**
 * Hook para inyectar contenido antes del footer en Moodle.
 */
class hooks {
    /**
     * Devuelve el contenido HTML que se agregará antes del footer.
     *
     * @return string HTML a inyectar.
     */
    public static function output_before_footer_html_generation(): string {
        return '<div id="custom-footer-content" style="padding:10px; background:#f8f9fa; border-top:1px solid #ddd; text-align:center;">
                    <p>Este es un contenido personalizado antes del footer.</p>
                </div>';
    }
}
