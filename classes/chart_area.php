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
 * Chart area.
 *
 * @package
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core;
defined('MOODLE_INTERNAL') || die();

/**
 * Chart area class.
 *
 * @package    core
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class chart_area extends chart_base {

    /** @var bool Whether the line should be smooth or not. */
    protected $smooth = false;

    /** @var bool Whether the chart should be stacked or not. */
    protected $stacked = null;
    
    /**
     * Add the smooth to the parent and return the serialized data.
     *
     * @return array
     */
    public function jsonSerialize() { // @codingStandardsIgnoreLine (CONTRIB-6469).
        $data = parent::jsonSerialize();
        $data['smooth'] = $this->get_smooth();
        return $data;
    }

    /**
     * Get whether a lines should be smooth or not.
     *
     * @return bool
     */
    public function get_smooth() {
        return $this->smooth;
    }

    /**
     * Set Whether the line should be smooth or not.
     *
     * @param bool $smooth True if the line chart should be smooth, false otherwise.
     */
    public function set_smooth($smooth) {
        $this->smooth = $smooth;
    }

    /**
     * Get whether the area should be stacked or not.
     *
     * @return bool
     */
    public function get_stacked() {
        return $this->stacked;
    }

    /**
     * Set whether the area should be stacked or not.
     *
     * @param bool $stacked True if the chart should be stacked or false otherwise.
     */
    public function set_stacked($stacked) {
        $this->stacked = $stacked;
    }
}
