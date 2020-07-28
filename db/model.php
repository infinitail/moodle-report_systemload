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
namespace report_systemload\db;

/**
 * MySQL / MariaDB version
 */
class model {
    /**
     * @param int $record->time
     * @param int $record->load1
     * @param int $record->load5
     * @param int $record->load15
     */
    public function write_load_average(\stdClass $record)
    {
        global $DB;

        try {
            $DB->insert_record('report_sysload_loadaverage', $record);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param int $record->time
     * @param int $record->load1
     * @param int $record->load5
     * @param int $record->load15
     */
    public function write_response_time(\stdClass $record)
    {
        global $DB;

        try {
            $DB->insert_record('report_sysload_restime', $record);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     *
     */
    public function write_disk_space(\stdClass $record)
    {
        global $DB;

        try {
            $DB->insert_record('report_sysload_diskspace', $record);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param int $record->time
     * @param int $record->
     *
     *
     */
    public function write_login_user_count(\stdClass $record)
    {
        global $DB;

        try {
            $DB->insert_record('report_sysload_loginuser', $record);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function read_load_average($type = 'all', int $timefrom = 0, int $timeto = null, string $ignore_hours = '')
    {
        switch ($type) {
            default:
            case 'all':
                return $this->read_load_average_all($timefrom, $timeto);
                break;

            case 'hour':
                return $this->read_load_average_hourly($timefrom, $timeto);
                break;

            case 'day':
                return $this->read_load_average_daily($timefrom, $timeto, $ignore_hours);
                break;
        }
    }

    public function read_load_average_all(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time, load1, load5, load15
                FROM {report_sysload_loadaverage}
                WHERE time >= :timefrom AND time <= :timeto
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_load_average_hourly(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time,
                       FROM_UNIXTIME(time, '%Y%m%d%H') AS yyyymmddhh,
                       MAX(load1) AS load1, MAX(load5) AS load5, MAX(load15) AS load15
                FROM {report_sysload_loadaverage}
                WHERE time >= :timefrom AND time < :timeto
                GROUP BY yyyymmddhh
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_load_average_daily(int $timefrom = 0, int $timeto = null, string $ignore_hours = '')
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $ignore_hours_condition_query = self::_get_ignore_hours_condition_query($ignore_hours);

        $sql = "SELECT time,
                       FROM_UNIXTIME(time, '%Y%m%d') AS yyyymmdd,
                       MAX(load1) AS load1, MAX(load5) AS load5, MAX(load15) AS load15
                FROM {report_sysload_loadaverage}
                WHERE time >= :timefrom AND time < :timeto
                      {$ignore_hours_condition_query}
                GROUP BY yyyymmdd
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_login_user($type = 'all', int $timefrom = 0, int $timeto = null)
    {
        switch ($type) {
            default:
            case 'all':
                return $this->read_login_user_all($timefrom, $timeto);
                break;

            case 'hour':
                return $this->read_login_user_hourly($timefrom, $timeto);
                break;

            case 'day':
                return $this->read_login_user_daily($timefrom, $timeto);
                break;
        }
    }

    public function read_login_user_all(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time, login5, loginsess
                FROM {report_sysload_loginuser}
                WHERE time >= :timefrom AND time <= :timeto
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_login_user_hourly(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time,
                       FROM_UNIXTIME(time, '%Y%m%d%H') AS yyyymmddhh,
                       MAX(login5) AS login5,  MAX(loginsess) as loginsess
                FROM {report_sysload_loginuser}
                WHERE time >= :timefrom AND time <= :timeto
                GROUP BY yyyymmddhh
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_login_user_daily(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time,
                       FROM_UNIXTIME(time, '%Y%m%d') AS yyyymmdd,
                       MAX(login5) AS login5,  MAX(loginsess) as loginsess
                FROM {report_sysload_loginuser}
                WHERE time >= :timefrom AND time <= :timeto
                GROUP BY yyyymmdd
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }


    public function read_response_time($type = 'all', int $timefrom = 0, int $timeto = null, string $ignore_hours = '')
    {
        switch ($type) {
            default:
            case 'all':
                return $this->read_response_time_all($timefrom, $timeto);
                break;

            case 'hour':
                return $this->read_response_time_hourly($timefrom, $timeto);
                break;

            case 'day':
                return $this->read_response_time_daily($timefrom, $timeto, $ignore_hours);
                break;
        }
    }

    public function read_response_time_all(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time, responsetime
                FROM {report_sysload_restime}
                WHERE time >= :timefrom AND time <= :timeto
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_response_time_hourly(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time,
                       FROM_UNIXTIME(time, '%Y%m%d%H') AS yyyymmddhh,
                       MAX(responsetime) AS responsetime
                FROM {report_sysload_restime}
                WHERE time >= :timefrom AND time <= :timeto
                GROUP BY yyyymmddhh
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_response_time_daily(int $timefrom = 0, int $timeto = null, string $ignore_hours = '')
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $ignore_hours_condition_query = self::_get_ignore_hours_condition_query($ignore_hours);

        $sql = "SELECT time,
                       FROM_UNIXTIME(time, '%Y%m%d') AS yyyymmdd,
                       MAX(responsetime) AS responsetime
                FROM {report_sysload_restime}
                WHERE time >= :timefrom AND time <= :timeto
                      {$ignore_hours_condition_query}
                GROUP BY yyyymmdd
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_disk_space($type = 'all', int $timefrom = 0, int $timeto = null)
    {
        switch ($type) {
            default:
            case 'all':
                return $this->read_disk_space_all($timefrom, $timeto);
                break;

            case 'hour':
                return $this->read_disk_space_hourly($timefrom, $timeto);
                break;

            case 'day':
                return $this->read_disk_space_daily($timefrom, $timeto);
                break;
        }
    }

    public function read_disk_space_all(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time, disktotal, diskfree, diskused
                FROM {report_sysload_diskspace}
                WHERE time >= :timefrom AND time <= :timeto
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_disk_space_hourly(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time,
                        FROM_UNIXTIME(time, '%Y%m%d%H') AS yyyymmddhh,
                        MAX(disktotal) AS disktotal, MIN(diskfree) AS diskfree, MAX(diskused) AS diskused
                FROM {report_sysload_diskspace}
                WHERE time >= :timefrom AND time <= :timeto
                GROUP BY yyyymmddhh
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    public function read_disk_space_daily(int $timefrom = 0, int $timeto = null)
    {
        global $CFG, $DB;

        if (is_null($timeto)) {
            $timeto = time();
        }

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
        ];

        $sql = "SELECT time,
                        FROM_UNIXTIME(time, '%Y%m%d') AS yyyymmdd,
                        MAX(disktotal) AS disktotal, MIN(diskfree) AS diskfree, MAX(diskused) AS diskused
                FROM {report_sysload_diskspace}
                WHERE time >= :timefrom AND time <= :timeto
                GROUP BY yyyymmdd
            ";

        try {
            return $DB->get_records_sql($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get SQL condition which ignore unintended system peak
     *
     * @param string $ignore_hours
     *      '3,4'
     * @return string
     */
    private static function _get_ignore_hours_condition_query(string $ignore_hours)
    {
        if (empty($ignore_hours)) {
            return '';
        }

        // remove malicious input and deduplication
        $hours = explode(',', $ignore_hours);
        $hours = array_map(function($n) { return intval($n); }, $hours);
        $hours = array_unique($hours);

        return "AND FROM_UNIXTIME(time, '%h') NOT IN (" . join(',', $hours) . ")";
    }
}
