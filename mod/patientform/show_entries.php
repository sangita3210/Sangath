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
 * print the single entries
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_patientform
 */

require_once("../../config.php");
require_once("lib.php");

////////////////////////////////////////////////////////
//get the params
////////////////////////////////////////////////////////
$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', false, PARAM_INT);
$showcompleted = optional_param('showcompleted', false, PARAM_INT);
$deleteid = optional_param('delete', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);

////////////////////////////////////////////////////////
//get the objects
////////////////////////////////////////////////////////

list($course, $cm) = get_course_and_cm_from_cmid($id, 'patientform');

$baseurl = new moodle_url('/mod/patientform/show_entries.php', array('id' => $cm->id));
$PAGE->set_url(new moodle_url($baseurl, array('userid' => $userid, 'showcompleted' => $showcompleted,
        'delete' => $deleteid)));

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
$patientform = $PAGE->activityrecord;

require_capability('mod/patientform:viewreports', $context);

if ($deleteid) {
    // This is a request to delete a reponse.
    require_capability('mod/patientform:deletesubmissions', $context);
    require_sesskey();
    $patientformstructure = new mod_patientform_completion($patientform, $cm, 0, true, $deleteid);
    patientform_delete_completed($patientformstructure->get_completed(), $patientform, $cm);
    redirect($baseurl);
} else if ($showcompleted || $userid) {
    // Viewing individual response.
    $patientformstructure = new mod_patientform_completion($patientform, $cm, 0, true, $showcompleted, $userid);
} else {
    // Viewing list of reponses.
    $patientformstructure = new mod_patientform_structure($patientform, $cm, $courseid);
}

$responsestable = new mod_patientform_responses_table($patientformstructure);
$anonresponsestable = new mod_patientform_responses_anon_table($patientformstructure);

if ($responsestable->is_downloading()) {
    $responsestable->download();
}
if ($anonresponsestable->is_downloading()) {
    $anonresponsestable->download();
}

// Process course select form.
$courseselectform = new mod_patientform_course_select_form($baseurl, $patientformstructure, $patientform->course == SITEID);
if ($data = $courseselectform->get_data()) {
    redirect(new moodle_url($baseurl, ['courseid' => $data->courseid]));
}
// Print the page header.
navigation_node::override_active_url($baseurl);
$PAGE->set_heading($course->fullname);
$PAGE->set_title($patientform->name);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($patientform->name));

$current_tab = 'showentries';
require('tabs.php');

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

if ($userid || $showcompleted) {
    // Print the response of the given user.
    $completedrecord = $patientformstructure->get_completed();

    if ($userid) {
        $usr = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $responsetitle = userdate($completedrecord->timemodified) . ' (' . fullname($usr) . ')';
    } else {
        $responsetitle = get_string('response_nr', 'patientform') . ': ' .
                $completedrecord->random_response . ' (' . get_string('anonymous', 'patientform') . ')';
    }

    echo $OUTPUT->heading($responsetitle, 4);

    $form = new mod_patientform_complete_form(mod_patientform_complete_form::MODE_VIEW_RESPONSE,
            $patientformstructure, 'patientform_viewresponse_form');
    $form->display();

    list($prevresponseurl, $returnurl, $nextresponseurl) = $userid ?
            $responsestable->get_reponse_navigation_links($completedrecord) :
            $anonresponsestable->get_reponse_navigation_links($completedrecord);

    echo html_writer::start_div('response_navigation');

    $responsenavigation = [
        'col1content' => '',
        'col2content' => html_writer::link($returnurl, get_string('back'), ['class' => 'back_to_list']),
        'col3content' => '',
    ];

    if ($prevresponseurl) {
        $responsenavigation['col1content'] = html_writer::link($prevresponseurl, get_string('prev'), ['class' => 'prev_response']);
    }

    if ($nextresponseurl) {
        $responsenavigation['col3content'] = html_writer::link($nextresponseurl, get_string('next'), ['class' => 'next_response']);
    }

    echo $OUTPUT->render_from_template('core/columns-1to1to1', $responsenavigation);
    echo html_writer::end_div();

} else {
    // Print the list of responses.
    $courseselectform->display();

    // Show non-anonymous responses (always retrieve them even if current patientform is anonymous).
    $totalrows = $responsestable->get_total_responses_count();
    if (!$patientformstructure->is_anonymous() || $totalrows) {
        echo $OUTPUT->heading(get_string('non_anonymous_entries', 'patientform', $totalrows), 4);
        $responsestable->display();
    }

    // Show anonymous responses (always retrieve them even if current patientform is not anonymous).
    $patientformstructure->shuffle_anonym_responses();
    $totalrows = $anonresponsestable->get_total_responses_count();
    if ($patientformstructure->is_anonymous() || $totalrows) {
        echo $OUTPUT->heading(get_string('anonymous_entries', 'patientform', $totalrows), 4);
        $anonresponsestable->display();
    }

}

// Finish the page.
echo $OUTPUT->footer();

