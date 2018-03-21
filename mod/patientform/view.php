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
 * the first page to view the patientform
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_patientform
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/patientform/lib.php');

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);

$current_tab = 'view';

list($course, $cm) = get_course_and_cm_from_cmid($id, 'patientform');
require_course_login($course, true, $cm);
$patientform = $PAGE->activityrecord;

$patientformcompletion = new mod_patientform_completion($patientform, $cm, $courseid);

$context = context_module::instance($cm->id);

if ($course->id == SITEID) {
    $PAGE->set_pagelayout('incourse');
}
$PAGE->set_url('/mod/patientform/view.php', array('id' => $cm->id));
$PAGE->set_title($patientform->name);
$PAGE->set_heading($course->fullname);

// Check access to the given courseid.
if ($courseid AND $courseid != SITEID) {
    require_course_login(get_course($courseid)); // This overwrites the object $COURSE .
}

// Check whether the patientform is mapped to the given courseid.
if (!has_capability('mod/patientform:edititems', $context) &&
        !$patientformcompletion->check_course_is_mapped()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('cannotaccess', 'mod_patientform'));
    echo $OUTPUT->footer();
    exit;
}

// Trigger module viewed event.
$patientformcompletion->trigger_module_viewed();

/// Print the page header
echo $OUTPUT->header();

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

$previewimg = $OUTPUT->pix_icon('t/preview', get_string('preview'));
$previewlnk = new moodle_url('/mod/patientform/print.php', array('id' => $id));
if ($courseid) {
    $previewlnk->param('courseid', $courseid);
}
$preview = html_writer::link($previewlnk, $previewimg);

echo $OUTPUT->heading(format_string($patientform->name) . $preview);

// Print the tabs.
require('tabs.php');

// Show description.
echo $OUTPUT->box_start('generalbox patientform_description');
$options = (object)array('noclean' => true);
echo format_module_intro('patientform', $patientform, $cm->id);
echo $OUTPUT->box_end();

//show some infos to the patientform
if (has_capability('mod/patientform:edititems', $context)) {

    echo $OUTPUT->heading(get_string('overview', 'patientform'), 3);

    //get the groupid
    $groupselect = groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/patientform/view.php?id='.$cm->id, true);
    $mygroupid = groups_get_activity_group($cm);

    echo $groupselect.'<div class="clearer">&nbsp;</div>';
    $summary = new mod_patientform\output\summary($patientformcompletion, $mygroupid, true);
    echo $OUTPUT->render_from_template('mod_patientform/summary', $summary->export_for_template($OUTPUT));

    if ($pageaftersubmit = $patientformcompletion->page_after_submit()) {
        echo $OUTPUT->heading(get_string("page_after_submit", "patientform"), 3);
        echo $OUTPUT->box($pageaftersubmit, 'generalbox patientform_after_submit');
    }
}

if (!has_capability('mod/patientform:viewreports', $context) &&
        $patientformcompletion->can_view_analysis()) {
    $analysisurl = new moodle_url('/mod/patientform/analysis.php', array('id' => $id));
    echo '<div class="mdl-align"><a href="'.$analysisurl->out().'">';
    echo get_string('completed_patientform', 'patientform').'</a>';
    echo '</div>';
}

if (has_capability('mod/patientform:mapcourse', $context) && $patientform->course == SITEID) {
    echo $OUTPUT->box_start('generalbox patientform_mapped_courses');
    echo $OUTPUT->heading(get_string("mappedcourses", "patientform"), 3);
    echo '<p>' . get_string('mapcourse_help', 'patientform') . '</p>';
    $mapurl = new moodle_url('/mod/patientform/mapcourse.php', array('id' => $id));
    echo '<p class="mdl-align">' . html_writer::link($mapurl, get_string('mapcourses', 'patientform')) . '</p>';
    echo $OUTPUT->box_end();
}

if ($patientformcompletion->can_complete()) {
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    if (!$patientformcompletion->is_open()) {
        // patientform is not yet open or is already closed.
        echo $OUTPUT->notification(get_string('patientform_is_not_open', 'patientform'));
        echo $OUTPUT->continue_button(course_get_url($courseid ?: $course->id));
    } else if ($patientformcompletion->can_submit()) {
        // Display a link to complete patientform or resume.
        $completeurl = new moodle_url('/mod/patientform/complete.php',
                ['id' => $id, 'courseid' => $courseid]);
        if ($startpage = $patientformcompletion->get_resume_page()) {
            $completeurl->param('gopage', $startpage);
            $label = get_string('continue_the_form', 'patientform');
        } else {
            $label = get_string('complete_the_form', 'patientform');
        }
        echo html_writer::div(html_writer::link($completeurl, $label), 'complete-patientform');
    } else {
        // patientform was already submitted.
        echo $OUTPUT->notification(get_string('this_patientform_is_already_submitted', 'patientform'));
        $OUTPUT->continue_button(course_get_url($courseid ?: $course->id));
    }
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();

