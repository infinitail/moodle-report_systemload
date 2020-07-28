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
 * Settings and links
 *
 * @package   report_systemload
 * @copyright
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


$ADMIN->add('reports', new admin_externalpage(
    'reportsystemloadlink',
    get_string('pluginname', 'report_systemload'),
    $CFG->wwwroot.'/report/systemload/index.php',
    'moodle/site:config'
));

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configmultiselect(
        'report_systemload/ignore_hours',
        get_string('ignorehours', 'report_systemload'),
        get_string('ignorehoursdesc', 'report_systemload'),
        null,
        range(0, 23)
    ));
}
