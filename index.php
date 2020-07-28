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
 * Performance overview report
 *
 * @package
 * @copyright
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require '../../config.php';
require_once __DIR__.'/locallib.php';
require_once __DIR__.'/db/model.php';
require_once $CFG->libdir.'/adminlib.php';

require_login();

$tab = optional_param('tab', 'last24hours', PARAM_ALPHANUM);
$year  = optional_param('year', date('Y'), PARAM_INT);
$month = optional_param('month', date('m'), PARAM_INT);
$day   = optional_param('day', date('d'), PARAM_INT);

$timeto = time();
global $CFG, $DB;

$model = new report_systemload\db\model;
$locallib = new report_systemload\locallib;

$pluginsettings = get_config('report_systemload');

$tz = core_date::get_server_timezone();
date_default_timezone_set($tz);
//$usertzoffset = date('P');

switch ($tab) {
    default:
    case 'last24hours':
        $timefrom  = $locallib->adjust_time(time() - DAYSECS, '5min', 'floor');
        $timeto = $locallib->adjust_time(time(), '5min', 'floor');
        $interval  = 60 * 5;
        $type = 'all';
        $legends = array();
        $legends['loginuser'] = ['5min active', 'session'];
        $legends['loadaverage'] = ['1min', '5min', '15min'];
        $legends['responsetime'] = ['Response Time'];
        $legends['diskspace'] = ['Disk Total', 'Disk Used'];
        break;

    case 'lastweek':
        $timefrom = $locallib->adjust_time(time() - DAYSECS * 7, 'hour', 'ceil');
        $timeto = $locallib->adjust_time(time(), 'hour', 'ceil');
        $interval = 60 * 60;
        $type = 'hour';
        $legends = array();
        $legends['loginuser'] = ['5min active (MAX)', 'session (MAX)'];
        $legends['loadaverage'] = ['1min (MAX)', '5min (MAX)', '15min (MAX)'];
        $legends['responsetime'] = ['Response Time (MAX)'];
        $legends['diskspace'] = ['Disk Total (MAX)', 'Disk Used (MAX)'];
        break;

    case 'last4weeks':
        $timefrom = $locallib->adjust_time(time() - DAYSECS * 7 * 4, 'day', 'ceil');
        $timeto = $locallib->adjust_time(time(), 'day', 'ceil');
        $interval = 60 * 60 * 24;
        $type = 'day';
        $legends = array();
        $legends['loginuser'] = ['5min active (MAX)', 'session (MAX)'];
        $legends['loadaverage'] = ['1min (MAX)', '5min (MAX)', '15min (MAX)'];
        $legends['responsetime'] = ['Response Time (MAX)'];
        $legends['diskspace'] = ['Disk Total (MAX)', 'Disk Used (MAX)'];
        break;

    case 'last365days':
        $timefrom = $locallib->adjust_time(time() - DAYSECS * 365, 'day', 'ceil');
        $timeto = $locallib->adjust_time(time(), 'day', 'ceil');
        $interval = 60 * 60 * 24;
        $type = 'day';
        $legends = array();
        $legends['loginuser'] = ['5min active (MAX)', 'session (MAX)'];
        $legends['loadaverage'] = ['1min (MAX)', '5min (MAX)', '15min (MAX)'];
        $legends['responsetime'] = ['Response Time (MAX)'];
        $legends['diskspace'] = ['Disk Total (MAX)', 'Disk Used (MAX)'];
        break;
}

// create chart data - login user
$rows = $model->read_login_user($type, $timefrom, $timeto);
$series = ['login5', 'loginsess'];
$chartdata = $locallib->generate_chartdata($rows, $series, $timefrom, $timeto, $interval);
$chartlu = $locallib->get_chart($chartdata, 'line', $legends['loginuser'], 'Time', 'User');

// create chart data - load average
$rows = $model->read_load_average($type, $timefrom, $timeto, $pluginsettings->ignore_hours);
$series = ['load1', 'load5', 'load15'];
$chartdata = $locallib->generate_chartdata($rows, $series, $timefrom, $timeto, $interval, 'divideby100');
$chartla = $locallib->get_chart($chartdata, 'bar', $legends['loadaverage'], 'Time', 'Load Average', true);

//  create chart data - response time
$rows = $model->read_response_time($type, $timefrom, $timeto, $pluginsettings->ignore_hours);
$series = ['responsetime'];
$chartdata = $locallib->generate_chartdata($rows, $series, $timefrom, $timeto, $interval);
$chartrt = $locallib->get_chart($chartdata, 'bar', $legends['responsetime'], 'Time', 'Response Time [ms]');

//  create chart data - disk use
$rows = $model->read_disk_space($type, $timefrom, $timeto);
$series = ['disktotal', 'diskused'];
$chartdata = $locallib->generate_chartdata($rows, $series, $timefrom, $timeto, $interval, 'gigabyte');
$chartds = $locallib->get_chart($chartdata, 'line', $legends['diskspace'], 'Time', 'Disk Space [GB]');

// start output
admin_externalpage_setup('reportsystemloadlink', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

// display tab
$tabs = [];
$tabs[] = new tabobject(
    'last24hours',
    new moodle_url('/report/systemload/index.php?tab=last24hours'),
    get_string('last24hours', 'report_systemload')
);
$tabs[] = new tabobject(
    'lastweek',
    new moodle_url('/report/systemload/index.php?tab=lastweek'),
    get_string('lastweek', 'report_systemload')
);
$tabs[] = new tabobject(
    'last4weeks',
    new moodle_url('/report/systemload/index.php?tab=last4weeks'),
    get_string('last4weeks', 'report_systemload')
);
$tabs[] = new tabobject(
    'last365days',
    new moodle_url('/report/systemload/index.php?tab=last365days'),
    get_string('last365days', 'report_systemload')
);
echo $OUTPUT->tabtree($tabs, $tab);

echo $OUTPUT->heading(get_string('pluginname', 'report_systemload'));

echo $OUTPUT->heading(get_string('loginuser', 'report_systemload'), 3);
echo $OUTPUT->render($chartlu, false);

echo $OUTPUT->heading(get_string('loadaverage', 'report_systemload'), 3);
echo $OUTPUT->render($chartla, false);

echo $OUTPUT->heading(get_string('responsetime', 'report_systemload'), 3);
echo $OUTPUT->render($chartrt, false);

echo $OUTPUT->heading(get_string('diskspace', 'report_systemload'), 3);
echo $OUTPUT->render($chartds, false);

echo $OUTPUT->footer();
