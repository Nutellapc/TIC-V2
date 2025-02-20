<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * PLUGIN VERSION INFORMATION DATA
 *
 * This file defines the current version of the plugin being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed (see lib/db/*.php)
 *
 * @package    local_ml_dashboard
 * @author     Tony
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_ml_dashboard2';   // Nombre único del plugin.
$plugin->version = 2024042203; // Versión del plugin más baja compatible con Moodle.
$plugin->requires = 2022041900;              // Versión mínima de Moodle requerida (ajusta según tu Moodle).
$plugin->maturity = MATURITY_STABLE;         // Estado del plugin: MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC o MATURITY_STABLE.
$plugin->release = '1.0';                    // Número de versión del plugin.


