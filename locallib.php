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
 *
 *
 * @package
 * @author
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_systemload;

require_once __DIR__.'/db/model.php';
use report_systemload\db\model;

defined('MOODLE_INTERNAL') || die();

class locallib
{
    const ACTIVE_TIME = 300;        // Threshold of current active user (5 min)
    const RECORD_INTERVAL = 300;

    public function record_loadaverage()
    {
        global $DB;

        // WindowsOS not support this function
        if (stristr(PHP_OS, 'win')) {
            mtrace('REPORT_SYSTEMLOAD: WindowsOS does not support sys_getloadavg() function!');
            return false;
        }

        // init record
        $record = new \stdClass;
        $record->time = $this->adjust_time(time(), '5min');

        // get load average
        $load = sys_getloadavg();
        $record->load1  = floor($load[0] * 100);
        $record->load5  = floor($load[1] * 100);
        $record->load15 = floor($load[2] * 100);

        mtrace("REPORT_SYSTEMLOAD: Current load average is {$load[0]}/{$load[1]}/{$load[2]}");

        $model = new model;
        if (!$model->write_load_average($record)) {
            mtrace('REPORT_SYSTEMLOAD: Entry already exist!');
        }

        return true;
    }

    /**
     * Access moodle site top page to measure response time and store it.
     */
    public function record_responsetime()
    {
        global $DB, $CFG;

        $url = $CFG->wwwroot;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, 'moodle-report_systemload');
        curl_setopt($curl, CURLOPT_URL, $url);

        $html = curl_exec($curl);
        $time = curl_getinfo($curl, CURLINFO_TOTAL_TIME);

        // get disk space info
        $record = new \stdClass;
        $record->time         = $this->adjust_time(time(), '5min');
        $record->responsetime = floor($time * 1000);    // convert sec -> msec

        mtrace("REPORT_SYSTEMLOAD: Current response time - {$record->responsetime} [ms]");

        $model = new model;
        if(!$model->write_response_time($record)) {
            mtrace('REPORT_SYSTEMLOAD: Entry already exist!');
        }

        return true;
    }

    public function record_diskspace()
    {
        global $DB, $CFG;

        // get disk space info
        $record = new \stdClass;
        $record->time      = $this->adjust_time(time(), '5min');
        $record->disktotal = disk_total_space($CFG->dataroot);
        $record->diskfree  = disk_free_space($CFG->dataroot);
        $record->diskused  = $record->disktotal - $record->diskfree;

        mtrace("REPORT_SYSTEMLOAD: Current disk space - {$record->disktotal} (total) / {$record->diskfree} (free) / {$record->diskused} (used)");

        $model = new model;
        if (!$model->write_disk_space($record)) {
            mtrace('REPORT_SYSTEMLOAD: Entry already exist!');
        }

        return true;
    }

    public function record_loginusercount()
    {
        mtrace('REPORT_SYSTEMLOAD: Start record current login user');

        global $DB, $CFG;
        $now = time();

        $sql = "SELECT COUNT(u.id) AS usercount
                  FROM {user} u
                 WHERE u.lastaccess > :timefrom
                       AND u.lastaccess <= :timenow
                       AND u.deleted = 0
                ";

        mtrace('REPORT_SYSTEMLOAD: Get 5min login user count');
        $params = [];
        $params['timenow']  = $now;
        $params['timefrom'] = $now - (self::ACTIVE_TIME);
        $login5 = $DB->get_record_sql($sql, $params);

        mtrace('REPORT_SYSTEMLOAD: Get session login user count');
        $params['timefrom'] = time() - $CFG->sessiontimeout;
        $loginsess = $DB->get_record_sql($sql, $params);

        mtrace('REPORT_SYSTEMLOAD: Preparing record login user');
        $record = new \stdClass;
        $record->time      = $this->adjust_time(time(), '5min');
        $record->login5    = (int) $login5->usercount;
        $record->loginsess = (int) $loginsess->usercount;

        mtrace("REPORT_SYSTEMLOAD: Current login user - {$login5->usercount} (5 min) / {$loginsess->usercount} (session)");

        $model = new model;
        if (!$model->write_login_user_count($record)) {
            mtrace('REPORT_SYSTEMLOAD: Entry already exist!');
        }

        return true;
    }

    /**
     * return normalized time
     *
     * @param int $time
     * @param int $period (day|hour|5min|min)
     * @param string $adjust (floor|ceil)
     * @param string $timezone
     *
     * @return int
     */
    public function adjust_time(int $time = null, string $period = 'min', string $adjust = 'floor')
    {
        if (is_null($time)) {
            $time = time();
        }

        switch ($period) {
            // Return "Y-m-d H:i:00" with unixtime
            case 'min':
            default:
                $second = ($adjust === 'floor') ? 00 : 60;

                $timeadj = mktime(
                    date('H', $time), date('i', $time), $second,
                    date('n', $time), date('j', $time), date('Y', $time)
                );
                return $timeadj;
                break;

            // Return "Y-m-d H:(0|5|10|15...):00" with unixtime
            case '5min':
                $step = ($adjust === 'floor') ? -60 : 60;

                for ($i=0; $i<5; $i++) {
                    $sec = $time + $step * $i;
                    $secadj = $this->adjust_time($sec, 'min', $adjust);
                    $minadj = date('i', $secadj);
                    if ($minadj % 5 === 0) {
                        return $secadj;
                    }
                }
                break;

            // Return "Y-m-d H:00:00" with unixtime
            case 'hour':
                $min = ($adjust === 'floor') ? 00 : 60;

                $time = mktime(
                    date('H', $time), $min, 00,
                    date('n', $time), date('j', $time), date('Y', $time)
                );
                break;

            // Return "Y-m-d 00:00:00" with unixtime
            case 'day':
                $hour = ($adjust === 'floor') ? 00 : 24;

                $time = mktime(
                    $hour, 00., 00,
                    date('n', $time), date('j', $time), date('Y', $time)
                );
                break;
        }

        return $time;
    }

    public function generate_chartdata(
            array $rows, array $series, int $timefrom, int $timeto,
            int $interval = 300, $format = null
        )
    {
        $data = [];

        // define chart x-axis min value
        if ($interval > 3600) {
            $timefromadj = $this->adjust_time($timefrom, 'day');
        } elseif ($interval > 300) {
            $timefromadj = $this->adjust_time($timefrom, 'hour');
        } else {
            $timefromadj = $this->adjust_time($timefrom, '5min');
        }

        // fill empty data for data not recorded in db
        for ($i=$timefromadj; $i<$timeto; $i+=$interval) {
            $label = date(get_string('dateformat', 'report_systemload'), $i);
            $data[$i]['labels'] = $label;

            foreach ($series as $key => $value) {
              $seriesid = 'series' . ($key + 1);
              $data[$i][$seriesid] = null;
            }
        }

        foreach ($rows as $row) {
            // ignore irregular time data
            if ($interval === 300 && date('i', $row->time) % 5 !== 0) {
                continue;
            }

            if ($interval > 3600) {
                $time = $this->adjust_time($row->time, 'day');
            } elseif ($interval > 300) {
                $time = $this->adjust_time($row->time, 'hour');
            } else {
                $time = $row->time;
            }

            // Display with lang defined format
            $label = date(get_string('dateformat', 'report_systemload'), $time);
            $data[$time]['labels']  = $label;

            foreach ($series as $key => $value) {
                $seriesid = 'series' . ($key + 1);

                // scale value if needed
                $methodname = 'format_'.$format;
                if (method_exists($this, $methodname)) {
                    $formatval = $this->{$methodname}($row->{$value});
                } else {
                    $formatval = $row->{$value};
                }

                $data[$time][$seriesid] = $formatval;
            }
        }

        $chartdata = [];
        foreach ($data as $datum) {
            foreach ($datum as $key => $value) {
                $chartdata[$key][] = $value;
            }
        }

        return $chartdata;
    }

    public function get_chart(
            array $chartdata, string $type = 'bar',
            array $legends = ['notitle'], string $xtitle = 'xtitle', string $ytitle = 'ytitle',
            bool $stacked = false
        )
    {
        switch ($type) {
            case 'line':
                $chart = new \core\chart_line();
                break;

            case 'bar':
            default:
                $chart = new \core\chart_bar();
                break;
        }

        if ($type === 'bar' && $stacked === true){
            $chart->set_stacked(true);
        }

        foreach ($legends as $key => $value) {
            $serie = 'series' . ($key + 1);
            $chart->add_series(new \core\chart_series($value, $chartdata[$serie]));
        }
        $chart->set_labels($chartdata['labels']);
        $chart->get_xaxis(0, true)->set_label($xtitle);
        $chart->get_yaxis(0, true)->set_label($ytitle);
        $chart->get_yaxis(0, true)->set_min(0);

        return $chart;
    }

    /**
     * Convert byte -> gigabyte (%.2f format)
     */
    public function format_gigabyte($value)
    {
        return floor($value / 1024 / 1024 / 1024 * 100) / 100;
    }
}
