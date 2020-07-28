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
 * A scheduled task.
 *
 * @package
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace report_systemload\task;

require_once __DIR__.'/../../locallib.php';

use report_systemload\locallib;

class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('systemloadtask', 'report_systemload');
    }

    /**
     *
     */
    public function execute() {
        mtrace('REPORT_SYSTEMLOAD: Starting systemload cron...');
        $locallib = new locallib;

        mtrace('REPORT_SYSTEMLOAD: Call method "Record loadaverage"');
        $locallib->record_loadaverage();

        mtrace('REPORT_SYSTEMLOAD: Call method "Record responsetime"');
        $locallib->record_responsetime();

        mtrace('REPORT_SYSTEMLOAD: Call method "Record diskspace"');
        $locallib->record_diskspace();

        mtrace('REPORT_SYSTEMLOAD: Call method "Record login user count"');
        $locallib->record_loginusercount();
    }

}
