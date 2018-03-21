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
 * print the confirm dialog to use template and create new items from template
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_patientform
 */

require_once("../../config.php");
require_once("lib.php");
require_once('use_templ_form.php');

$id = required_param('id', PARAM_INT);
$templateid = optional_param('templateid', false, PARAM_INT);

if (!$templateid) {
    redirect('edit.php?id='.$id);
}

$url = new moodle_url('/mod/patientform/use_templ.php', array('id'=>$id, 'templateid'=>$templateid));
$PAGE->set_url($url);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'patientform');
$context = context_module::instance($cm->id);

require_login($course, true, $cm);

$patientform = $PAGE->activityrecord;
$patientformstructure = new mod_patientform_structure($patientform, $cm, 0, $templateid);

require_capability('mod/patientform:edititems', $context);

$mform = new mod_patientform_use_templ_form();
$mform->set_data(array('id' => $id, 'templateid' => $templateid));

if ($mform->is_cancelled()) {
    redirect('edit.php?id='.$id.'&do_show=templates');
} else if ($formdata = $mform->get_data()) {
    patientform_items_from_template($patientform, $templateid, $formdata->deleteolditems);
    redirect('edit.php?id=' . $id);
}

/// Print the page header
$strpatientform = get_string("modulenameplural", "patientform");
$strpatientform  = get_string("modulename", "patientform");

navigation_node::override_active_url(new moodle_url('/mod/patientform/edit.php',
        array('id' => $id, 'do_show' => 'templates')));
$PAGE->set_heading($course->fullname);
$PAGE->set_title($patientform->name);
echo $OUTPUT->header();

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
echo $OUTPUT->heading(format_string($patientform->name));

echo $OUTPUT->heading(get_string('confirmusetemplate', 'patientform'), 4);

$mform->display();

$form = new mod_patientform_complete_form(mod_patientform_complete_form::MODE_VIEW_TEMPLATE,
        $patientformstructure, 'patientform_preview_form', ['templateid' => $templateid]);
$form->display();

echo $OUTPUT->footer();

