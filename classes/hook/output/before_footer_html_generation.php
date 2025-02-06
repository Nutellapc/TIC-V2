<?php
namespace local_ml_dashboard2\hook\output;

use core\hook\output\before_footer_html_generation as base_hook;

defined('MOODLE_INTERNAL') || die();

class before_footer_html_generation {
    public static function execute(base_hook $hook) {
        global $PAGE;


        // Agregar el botón flotante si estamos en la vista del curso
        if ($PAGE->pagetype === 'course-view') {
            $plugin_url = new \moodle_url('/local/ml_dashboard2/index.php');

            $button_html = '<div id="ml-dashboard-button">
                                <a href="' . $plugin_url . '" class="floating-button">
                                    📊 ML Dashboard
                                </a>
                            </div>';

            $button_css = '<style>
                .floating-button {
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    background: #008CBA;
                    color: white;
                    padding: 12px 16px;
                    border-radius: 50%;
                    font-size: 16px;
                    text-decoration: none;
                    box-shadow: 0px 4px 6px rgba(0,0,0,0.2);
                    transition: 0.3s;
                }
                .floating-button:hover {
                    background: #005f75;
                    transform: scale(1.1);
                }
            </style>';

            $hook->add_html($button_html . $button_css);
        }
    }
}
