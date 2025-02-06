<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => 'core\hook\output\before_footer_html_generation',
        'callback' => 'local_ml_dashboard2\hook\output\before_footer_html_generation::execute',
        'priority' => 0,
    ],
];
