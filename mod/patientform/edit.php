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
 * prints the form to edit the patientform items such moving, deleting and so on
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_patientform
 */

require_once('../../config.php');
require_once('lib.php');
require_once('edit_form.php');

patientform_init_patientform_session();

$id = required_param('id', PARAM_INT);

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}

$do_show = optional_param('do_show', 'edit', PARAM_ALPHA);
$switchitemrequired = optional_param('switchitemrequired', false, PARAM_INT);
$deleteitem = optional_param('deleteitem', false, PARAM_INT);

$current_tab = $do_show;

$url = new moodle_url('/mod/patientform/edit.php', array('id'=>$id, 'do_show'=>$do_show));

list($course, $cm) = get_course_and_cm_from_cmid($id,'patientform');

$context = context_module::instance($cm->id);
require_login($course, false, $cm);
require_capability('mod/patientform:edititems', $context);
$patientform = $PAGE->activityrecord;
$patientformstructure = new mod_patientform_structure($patientform,$cm);

if ($switchitemrequired) {
    require_sesskey();
    $items = $patientformstructure->get_items();
    if (isset($items[$switchitemrequired])) {
        patientform_switch_item_required($items[$switchitemrequired]);
    }
    redirect($url);
}

if ($deleteitem) {
    require_sesskey();
    $items = $patientformstructure->get_items();
    if (isset($items[$deleteitem])) {
        patientform_delete_item($deleteitem);
    }
    redirect($url);
}

// Process the create template form.
$cancreatetemplates = has_capability('mod/patientform:createprivatetemplate', $context) ||
            has_capability('mod/patientform:createpublictemplate', $context);
$create_template_form = new patientform_edit_create_template_form(null, array('id' => $id));
if ($data = $create_template_form->get_data()) {
    // Check the capabilities to create templates.
    if (!$cancreatetemplates) {
        print_error('cannotsavetempl', 'patientform', $url);
    }
    $ispublic = !empty($data->ispublic) ? 1 : 0;
    if (!patientform_save_as_template($patientform, $data->templatename, $ispublic)) {
        redirect($url, get_string('saving_failed', 'patientform'), null, \core\output\notification::NOTIFY_ERROR);
    } else {
        redirect($url, get_string('template_saved', 'patientform'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

//Get the patientformitems
$lastposition = 0;
$patientformitems = $DB->get_records('patientform_item', array('patientform'=>$patientform->id), 'position');
if (is_array($patientformitems)) {
    $patientformitems = array_values($patientformitems);
    if (count($patientformitems) > 0) {
        $lastitem = $patientformitems[count($patientformitems)-1];
        $lastposition = $lastitem->position;
    } else {
        $lastposition = 0;
    }
}
$lastposition++;


//The use_template-form
$use_template_form = new patientform_edit_use_template_form('use_templ.php', array('course' => $course, 'id' => $id));

//Print the page header.
$strpatientforms = get_string('modulenameplural', 'patientform');
$strpatientform  = get_string('modulename', 'patientform');

$PAGE->set_url('/mod/patientform/edit.php', array('id'=>$cm->id, 'do_show'=>$do_show));
$PAGE->set_heading($course->fullname);
$PAGE->set_title($patientform->name);

//Adding the javascript module for the items dragdrop.
if (count($patientformitems) > 1) {
    if ($do_show == 'edit') {
        $PAGE->requires->strings_for_js(array(
               'pluginname',
               'move_item',
               'position',
            ), 'patientform');
        $PAGE->requires->yui_module('moodle-mod_patientform-dragdrop', 'M.mod_patientform.init_dragdrop',
                array(array('cmid' => $cm->id)));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($patientform->name));

/// print the tabs
require('tabs.php');

// Print the main part of the page.

if ($do_show == 'templates') {
    // Print the template-section.
    $use_template_form->display();

    if ($cancreatetemplates) {
        $deleteurl = new moodle_url('/mod/patientform/delete_template.php', array('id' => $id));
        $create_template_form->display();
        echo '<p><a href="'.$deleteurl->out().'">'.
             get_string('delete_templates', 'patientform').
             '</a></p>';
    } else {
        echo '&nbsp;';
    }

    if (has_capability('mod/patientform:edititems', $context)) {
        $urlparams = array('action'=>'exportfile', 'id'=>$id);
        $exporturl = new moodle_url('/mod/patientform/export.php', $urlparams);
        $importurl = new moodle_url('/mod/patientform/import.php', array('id'=>$id));
        echo '<p>
            <a href="'.$exporturl->out().'">'.get_string('export_questions', 'patientform').'</a>/
            <a href="'.$importurl->out().'">'.get_string('import_questions', 'patientform').'</a>
        </p>';
    }
}

if ($do_show == 'edit') {
    // Print the Item-Edit-section.

    $select = new single_select(new moodle_url('/mod/patientform/edit_item.php',
            array('cmid' => $id, 'position' => $lastposition, 'sesskey' => sesskey())),
        'typ', patientform_load_patientform_items_options());
    $select->label = get_string('add_item', 'mod_patientform');
    echo $OUTPUT->render($select);


    $form = new mod_patientform_complete_form(mod_patientform_complete_form::MODE_EDIT,
            $patientformstructure, 'patientform_edit_form');
    echo '<div id="patientform_dragarea">'; // The container for the dragging area.
    $form->display();
    echo '</div>';
}

echo $OUTPUT->footer();
