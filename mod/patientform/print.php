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
 * print a printview of patientform-items
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_patientform
 */

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT); // Course where this patientform is mapped to - used for return link.

$PAGE->set_url('/mod/patientform/print.php', array('id'=>$id));

list($course, $cm) = get_course_and_cm_from_cmid($id, 'patientform');
require_course_login($course, true, $cm);

$patientform = $PAGE->activityrecord;
$patientformstructure = new mod_patientform_structure($patientform, $cm, $courseid);

$PAGE->set_pagelayout('popup');

// Print the page header.
$strpatientforms = get_string("modulenameplural", "patientform");
$strpatientform  = get_string("modulename", "patientform");

$patientform_url = new moodle_url('/mod/patientform/index.php', array('id'=>$course->id));
$PAGE->navbar->add($strpatientforms, $patientform_url);
$PAGE->navbar->add(format_string($patientform->name));

$PAGE->set_title($patientform->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// Print the main part of the page.
echo $OUTPUT->heading(format_string($patientform->name));

$continueurl = new moodle_url('/mod/patientform/view.php', array('id' => $id));
if ($courseid) {
    $continueurl->param('courseid', $courseid);
}

$form = new mod_patientform_complete_form(mod_patientform_complete_form::MODE_PRINT,
        $patientformstructure, 'patientform_print_form');
echo $OUTPUT->continue_button($continueurl);
$form->display();
echo $OUTPUT->continue_button($continueurl);

// Finish the page.
echo $OUTPUT->footer();

