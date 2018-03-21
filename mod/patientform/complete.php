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
 * prints the form so the user can fill out the patientform
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_patientform
 */

require_once("../../config.php");
require_once("lib.php");

patientform_init_patientform_session();

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$gopage = optional_param('gopage', 0, PARAM_INT);
$gopreviouspage = optional_param('gopreviouspage', null, PARAM_RAW);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'patientform');
$patientform = $DB->get_record("patientform", array("id" => $cm->instance), '*', MUST_EXIST);

$urlparams = array('id' => $cm->id, 'gopage' => $gopage, 'courseid' => $courseid);
$PAGE->set_url('/mod/patientform/complete.php', $urlparams);

require_course_login($course, true, $cm);
$PAGE->set_activity_record($patientform);

$context = context_module::instance($cm->id);
$patientformcompletion = new mod_patientform_completion($patientform, $cm, $courseid);

$courseid = $patientformcompletion->get_courseid();

// Check whether the patientform is mapped to the given courseid.
if (!has_capability('mod/patientform:edititems', $context) &&
        !$patientformcompletion->check_course_is_mapped()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('cannotaccess', 'mod_patientform'));
    echo $OUTPUT->footer();
    exit;
}

//check whether the given courseid exists
if ($courseid AND $courseid != SITEID) {
    require_course_login(get_course($courseid)); // This overwrites the object $COURSE .
}

if (!$patientformcompletion->can_complete()) {
    print_error('error');
}

$PAGE->navbar->add(get_string('patientform:complete', 'patientform'));
$PAGE->set_heading($course->fullname);
$PAGE->set_title($patientform->name);
$PAGE->set_pagelayout('incourse');

// Check if the patientform is open (timeopen, timeclose).
if (!$patientformcompletion->is_open()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($patientform->name));
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->notification(get_string('patientform_is_not_open', 'patientform'));
    echo $OUTPUT->continue_button(course_get_url($courseid ?: $patientform->course));
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

// Mark activity viewed for completion-tracking.
if (isloggedin() && !isguestuser()) {
    $patientformcompletion->set_module_viewed();
}

// Check if user is prevented from re-submission.
$cansubmit = $patientformcompletion->can_submit();

// Initialise the form processing patientform completion.
if (!$patientformcompletion->is_empty() && $cansubmit) {
    // Process the page via the form.
    $urltogo = $patientformcompletion->process_page($gopage, $gopreviouspage);

    if ($urltogo !== null) {
        redirect($urltogo);
    }
}

// Print the page header.
$strpatientforms = get_string("modulenameplural", "patientform");
$strpatientform  = get_string("modulename", "patientform");

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($patientform->name));

if ($patientformcompletion->is_empty()) {
    \core\notification::error(get_string('no_items_available_yet', 'patientform'));
} else if ($cansubmit) {
    if ($patientformcompletion->just_completed()) {
        // Display information after the submit.
        if ($patientform->page_after_submit) {
            echo $OUTPUT->box($patientformcompletion->page_after_submit(),
                    'generalbox boxaligncenter');
        }
        if ($patientformcompletion->can_view_analysis()) {
            echo '<p align="center">';
            $analysisurl = new moodle_url('/mod/patientform/analysis.php', array('id' => $cm->id, 'courseid' => $courseid));
            echo html_writer::link($analysisurl, get_string('completed_patientforms', 'patientform'));
            echo '</p>';
        }

        if ($patientform->site_after_submit) {
            $url = patientform_encode_target_url($patientform->site_after_submit);
        } else {
            $url = course_get_url($courseid ?: $course->id);
        }
        echo $OUTPUT->continue_button($url);
    } else {
        // Display the form with the questions.
        echo $patientformcompletion->render_items();
    }
} else {
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->notification(get_string('this_patientform_is_already_submitted', 'patientform'));
    echo $OUTPUT->continue_button(course_get_url($courseid ?: $course->id));
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();
