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
 * Dedication block definition.
 *
 * @package    block
 * @subpackage dedication
 * @copyright  2008 CICEI http://http://www.cicei.com
 * @author     2008 Borja Rubio Reyes
 *             2011 Aday Talavera Hierro (update to Moodle 2.x)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
"<link href='//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css' rel='stylesheet' id='bootstrap-css'>";
"<script src='//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js'></script>";
"<script src='//code.jquery.com/jquery-1.11.1.min.js'></script>";
"<script type='text/javascript' src='patientlist.js'></script>";
require_once($CFG->dirroot . '/blocks/patientlist/lib.php');

class block_patientlist extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_patientlist');
    }

    public function specialization() {
        // Previous block versions didn't have config settings.
        if ($this->config === null) {
            $this->config = new stdClass();
        }
        // Set always show_dedication config settings to avoid errors.
        if (!isset($this->config->show_patientlist)) {
            $this->config->show_patientlist = 0;
        }
    }

    public function get_content() {
        global $OUTPUT, $USER,$DB;

       /*  //	print_object($USER);
        $uname = $USER->username;
        $uid = $USER->id;
        $totalpatient = $DB->get_records('patient_complete_details', array('consoler_username' => $uname ));
        $totalnumber = count($totalpatient);
       // print_object($totalnumber);
       // $totalactivepatient = $DB->get_records_sql('SELECT * FROM {patient_complete_details} WHERE consoler_username = ? AND status = ?', array( $uname , 0 ));
        $totalactivepatient = $DB->get_records('patient_complete_details',array( 'consoler_username' => $uname , 'status'=> 0));
        $totalactivenumber = count($totalactivepatient);
        //print_object($totalactivepatient);die();
       	//print_object($totalactivenumber);die()*/;
        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

      
        $details = 'Patient Details';
        $this->content->text .= block_patientlist_display_numberof_patients($USER);
        return $this->content;
    }

    public function applicable_formats() {
        return array('course' => true);
    }

}
