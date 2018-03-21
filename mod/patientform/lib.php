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
 * Library of functions and constants for module patientform
 * includes the main-part of patientform-functions
 *
 * @package mod_patientform
 * @copyright Andreas Grabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Include eventslib.php */
require_once($CFG->libdir.'/eventslib.php');
// Include forms lib.
require_once($CFG->libdir.'/formslib.php');

define('PATIENTFORM_ANONYMOUS_YES', 1);
define('PATIENTFORM_ANONYMOUS_NO', 2);
define('PATIENTFORM_MIN_ANONYMOUS_COUNT_IN_GROUP', 2);
define('PATIENTFORM_DECIMAL', '.');
define('PATIENTFORM_THOUSAND', ',');
define('PATIENTFORM_RESETFORM_RESET', 'patientform_reset_data_');
define('PATIENTFORM_RESETFORM_DROP', 'patientform_drop_patientform_');
define('PATIENTFORM_MAX_PIX_LENGTH', '400'); //max. Breite des grafischen Balkens in der Auswertung
define('PATIENTFORM_DEFAULT_PAGE_COUNT', 20);

// Event types.
define('PATIENTFORM_EVENT_TYPE_OPEN', 'open');
define('PATIENTFORM_EVENT_TYPE_CLOSE', 'close');

/**
 * Returns all other caps used in module.
 *
 * @return array
 */
function patientform_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function patientform_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * this will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $patientform the object given by mod_patientform_mod_form
 * @return int
 */
function patientform_add_instance($patientform) {
    global $DB;

    $patientform->timemodified = time();
    $patientform->id = '';

    if (empty($patientform->site_after_submit)) {
        $patientform->site_after_submit = '';
    }

    //saving the patientform in db
    $patientformid = $DB->insert_record("patientform", $patientform);

    $patientform->id = $patientformid;

    patientform_set_events($patientform);

    if (!isset($patientform->coursemodule)) {
        $cm = get_coursemodule_from_id('patientform', $patientform->id);
        $patientform->coursemodule = $cm->id;
    }
    $context = context_module::instance($patientform->coursemodule);

    if (!empty($patientform->completionexpected)) {
        \core_completion\api::update_completion_date_event($patientform->coursemodule, 'patientform', $patientform->id,
                $patientform->completionexpected);
    }

    $editoroptions = patientform_get_editor_options();

    // process the custom wysiwyg editor in page_after_submit
    if ($draftitemid = $patientform->page_after_submit_editor['itemid']) {
        $patientform->page_after_submit = file_save_draft_area_files($draftitemid, $context->id,
                                                    'mod_patientform', 'page_after_submit',
                                                    0, $editoroptions,
                                                    $patientform->page_after_submit_editor['text']);

        $patientform->page_after_submitformat = $patientform->page_after_submit_editor['format'];
    }
    $DB->update_record('patientform', $patientform);

    return $patientformid;
}

/**
 * this will update a given instance
 *
 * @global object
 * @param object $patientform the object given by mod_patientform_mod_form
 * @return boolean
 */
function patientform_update_instance($patientform) {
    global $DB;

    $patientform->timemodified = time();
    $patientform->id = $patientform->instance;

    if (empty($patientform->site_after_submit)) {
        $patientform->site_after_submit = '';
    }

    //save the patientform into the db
    $DB->update_record("patientform", $patientform);

    //create or update the new events
   patientform_set_events($patientform);
    $completionexpected = (!empty($patientform->completionexpected)) ? $patientform->completionexpected : null;
    \core_completion\api::update_completion_date_event($patientform->coursemodule, 'patientform', $patientform->id, $completionexpected);

    $context = context_module::instance($patientform->coursemodule);

    $editoroptions = patientform_get_editor_options();

    // process the custom wysiwyg editor in page_after_submit
    if ($draftitemid = $patientform->page_after_submit_editor['itemid']) {
        $patientform->page_after_submit = file_save_draft_area_files($draftitemid, $context->id,
                                                    'mod_patientform', 'page_after_submit',
                                                    0, $editoroptions,
                                                    $patientform->page_after_submit_editor['text']);

        $patientform->page_after_submitformat = $patientform->page_after_submit_editor['format'];
    }
    $DB->update_record('patientform', $patientform);

    return true;
}

/**
 * Serves the files included in patientform items like label. Implements needed access control ;-)
 *
 * There are two situations in general where the files will be sent.
 * 1) filearea = item, 2) filearea = template
 *
 * @package  mod_patientform
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function patientform_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($filearea === 'item' or $filearea === 'template') {
        $itemid = (int)array_shift($args);
        //get the item what includes the file
        if (!$item = $DB->get_record('patientform_item', array('id'=>$itemid))) {
            return false;
        }
        $patientformid = $item->patientform;
        $templateid = $item->template;
    }

    if ($filearea === 'page_after_submit' or $filearea === 'item') {
        if (! $patientform = $DB->get_record("patientform", array("id"=>$cm->instance))) {
            return false;
        }

        $patientformid = $patientform->id;

        //if the filearea is "item" so we check the permissions like view/complete the patientform
        $canload = false;
        //first check whether the user has the complete capability
        if (has_capability('mod/patientform:complete', $context)) {
            $canload = true;
        }

        //now we check whether the user has the view capability
        if (has_capability('mod/patientform:view', $context)) {
            $canload = true;
        }

        //if the patientform is on frontpage and anonymous and the fullanonymous is allowed
        //so the file can be loaded too.
        if (isset($CFG->patientform_allowfullanonymous)
                    AND $CFG->patientform_allowfullanonymous
                    AND $course->id == SITEID
                    AND $patientform->anonymous == PATIENTFORM_ANONYMOUS_YES ) {
            $canload = true;
        }

        if (!$canload) {
            return false;
        }
    } else if ($filearea === 'template') { //now we check files in templates
        if (!$template = $DB->get_record('patientform_template', array('id'=>$templateid))) {
            return false;
        }

        //if the file is not public so the capability edititems has to be there
        if (!$template->ispublic) {
            if (!has_capability('mod/patientform:edititems', $context)) {
                return false;
            }
        } else { //on public templates, at least the user has to be logged in
            if (!isloggedin()) {
                return false;
            }
        }
    } else {
        return false;
    }

    if ($context->contextlevel == CONTEXT_MODULE) {
        if ($filearea !== 'item' and $filearea !== 'page_after_submit') {
            return false;
        }
    }

    if ($context->contextlevel == CONTEXT_COURSE || $context->contextlevel == CONTEXT_SYSTEM) {
        if ($filearea !== 'template') {
            return false;
        }
    }

    $relativepath = implode('/', $args);
    if ($filearea === 'page_after_submit') {
        $fullpath = "/{$context->id}/mod_patientform/$filearea/$relativepath";
    } else {
        $fullpath = "/{$context->id}/mod_patientform/$filearea/{$item->id}/$relativepath";
    }

    $fs = get_file_storage();

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!

    return false;
}

/**
 * this will delete a given instance.
 * all referenced data also will be deleted
 *
 * @global object
 * @param int $id the instanceid of patientform
 * @return boolean
 */
function patientform_delete_instance($id) {
    global $DB;

    //get all referenced items
    $patientformitems = $DB->get_records('patientform_item', array('patientform'=>$id));

    //deleting all referenced items and values
    if (is_array($patientformitems)) {
        foreach ($patientformitems as $patientformitem) {
            $DB->delete_records("patientform_value", array("item"=>$patientformitem->id));
            $DB->delete_records("patientform_valuetmp", array("item"=>$patientformitem->id));
        }
        if ($delitems = $DB->get_records("patientform_item", array("patientform"=>$id))) {
            foreach ($delitems as $delitem) {
                patientform_delete_item($delitem->id, false);
            }
        }
    }

    //deleting the completeds
    $DB->delete_records("patientform_completed", array("patientform"=>$id));

    //deleting the unfinished completeds
    $DB->delete_records("patientform_completedtmp", array("patientform"=>$id));

    //deleting old events
    $DB->delete_records('event', array('modulename'=>'patientform', 'instance'=>$id));
    return $DB->delete_records("patientform", array("id"=>$id));
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param cm_info|stdClass $mod
 * @param stdClass $patientform
 * @return stdClass
 */
function patientform_user_outline($course, $user, $mod, $patientform) {
    global $DB;
    $outline = (object)['info' => '', 'time' => 0];
    if ($patientform->anonymous != PATIENTFORM_ANONYMOUS_NO) {
        // Do not disclose any user info if patientform is anonymous.
        return $outline;
    }
    $params = array('userid' => $user->id, 'patientform' => $patientform->id,
        'anonymous_response' =>PATIENTFORM_ANONYMOUS_NO);
    $status = null;
    $context = context_module::instance($mod->id);
    if ($completed = $DB->get_record('patientform_completed', $params)) {
        // User has completed patientform
        $outline->info = get_string('completed', 'patientform');
        $outline->time = $completed->timemodified;
    } else if ($completedtmp = $DB->get_record('patientform_completedtmp', $params)) {
        // User has started but not completed patientform.
        $outline->info = get_string('started', 'patientform');
        $outline->time = $completedtmp->timemodified;
    } else if (has_capability('mod/patientform:complete', $context, $user)) {
        // User has not started patientform but has capability to do so.
        $outline->info = get_string('not_started', 'patientform');
    }

    return $outline;
}

/**
 * Returns all users who has completed a specified patientform since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 *
 * @global object
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @param array $activities Passed by reference
 * @param int $index Passed by reference
 * @param int $timemodified Timestamp
 * @param int $courseid
 * @param int $cmid
 * @param int $userid
 * @param int $groupid
 * @return void
 */
function patientform_get_recent_mod_activity(&$activities, &$index,
                                          $timemodified, $courseid,
                                          $cmid, $userid="", $groupid="") {

    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id'=>$courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];

    $sqlargs = array();

    $userfields = user_picture::fields('u', null, 'useridagain');
    $sql = " SELECT fk . * , fc . * , $userfields
                FROM {patientform_completed} fc
                    JOIN {patientform} fk ON fk.id = fc.patientform
                    JOIN {user} u ON u.id = fc.userid ";

    if ($groupid) {
        $sql .= " JOIN {groups_members} gm ON  gm.userid=u.id ";
    }

    $sql .= " WHERE fc.timemodified > ?
                AND fk.id = ?
                AND fc.anonymous_response = ?";
    $sqlargs[] = $timemodified;
    $sqlargs[] = $cm->instance;
    $sqlargs[] = PATIENTFORM_ANONYMOUS_NO;

    if ($userid) {
        $sql .= " AND u.id = ? ";
        $sqlargs[] = $userid;
    }

    if ($groupid) {
        $sql .= " AND gm.groupid = ? ";
        $sqlargs[] = $groupid;
    }

    if (!$patientformitems = $DB->get_records_sql($sql, $sqlargs)) {
        return;
    }

    $cm_context = context_module::instance($cm->id);

    if (!has_capability('mod/patientform:view', $cm_context)) {
        return;
    }

    $accessallgroups = has_capability('moodle/site:accessallgroups', $cm_context);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cm_context);
    $groupmode       = groups_get_activity_groupmode($cm, $course);

    $aname = format_string($cm->name, true);
    foreach ($patientformitems as $patientformitem) {
        if ($patientformitem->userid != $USER->id) {

            if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
                $usersgroups = groups_get_all_groups($course->id,
                                                     $patientformitem->userid,
                                                     $cm->groupingid);
                if (!is_array($usersgroups)) {
                    continue;
                }
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }

        $tmpactivity = new stdClass();

        $tmpactivity->type      = 'patientform';
        $tmpactivity->cmid      = $cm->id;
        $tmpactivity->name      = $aname;
        $tmpactivity->sectionnum= $cm->sectionnum;
        $tmpactivity->timestamp = $patientformitem->timemodified;

        $tmpactivity->content = new stdClass();
        $tmpactivity->content->patientformid = $patientformitem->id;
        $tmpactivity->content->patientformuserid = $patientformitem->userid;

        $tmpactivity->user = user_picture::unalias($patientformitem, null, 'useridagain');
        $tmpactivity->user->fullname = fullname($patientformitem, $viewfullnames);

        $activities[$index++] = $tmpactivity;
    }

    return;
}

/**
 * Prints all users who has completed a specified patientform since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 *
 * @global object
 * @param object $activity
 * @param int $courseid
 * @param string $detail
 * @param array $modnames
 * @return void Output is echo'd
 */
function patientform_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';

    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    echo $OUTPUT->user_picture($activity->user, array('courseid'=>$courseid));
    echo "</td><td>";

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo $OUTPUT->image_icon('icon', $modname, $activity->type);
        echo "<a href=\"$CFG->wwwroot/mod/patientform/view.php?id={$activity->cmid}\">{$activity->name}</a>";
        echo '</div>';
    }

    echo '<div class="title">';
    echo '</div>';

    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">"
         ."{$activity->user->fullname}</a> - ".userdate($activity->timestamp);
    echo '</div>';

    echo "</td></tr></table>";

    return;
}

/**
 * Obtains the automatic completion state for this patientform based on the condition
 * in patientform settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function patientform_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get patientform details
    $patientform = $DB->get_record('patientform', array('id'=>$cm->instance), '*', MUST_EXIST);

    // If completion option is enabled, evaluate it and return true/false
    if ($patientform->completionsubmit) {
        $params = array('userid'=>$userid, 'patientform'=>$patientform->id);
        return $DB->record_exists('patientform_completed', $params);
    } else {
        // Completion option is not enabled so just return $type
        return $type;
    }
}

/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param cm_info|stdClass $mod
 * @param stdClass $patientform
 */
function patientform_user_complete($course, $user, $mod, $patientform) {
    global $DB;
    if ($patientform->anonymous != PATIENTFORM_ANONYMOUS_NO) {
        // Do not disclose any user info if patientform is anonymous.
        return;
    }
    $params = array('userid' => $user->id, 'patientform' => $patientform->id,
        'anonymous_response' => PATIENTFORM_ANONYMOUS_NO);
    $url = $status = null;
    $context = context_module::instance($mod->id);
    if ($completed = $DB->get_record('patientform_completed', $params)) {
        // User has completed patientform
        if (has_capability('mod/patientform:viewreports', $context)) {
            $url = new moodle_url('/mod/patientform/show_entries.php',
                ['id' => $mod->id, 'userid' => $user->id,
                    'showcompleted' => $completed->id]);
        }
        $status = get_string('completedon', 'patientform', userdate($completed->timemodified));
    } else if ($completedtmp = $DB->get_record('patientform_completedtmp', $params)) {
        // User has started but not completed patientform.
        $status = get_string('startedon', 'patientform', userdate($completedtmp->timemodified));
    } else if (has_capability('mod/patientform:complete', $context, $user)) {
        // User has not started patientform but has capability to do so.
        $status = get_string('not_started', 'patientform');
    }

    if ($url && $status) {
        echo html_writer::link($url, $status);
    } else if ($status) {
        echo html_writer::div($status);
    }
}

/**
 * @return bool true
 */
function patientform_cron () {
    return true;
}

/**
 * @return bool false
 */
function patientform_scale_used ($patientformid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of patientform
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any assignment
 */
function patientform_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function patientform_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function patientform_get_post_actions() {
    return array('submit');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all responses from the specified patientform
 * and clean up any related data.
 *
 * @global object
 * @global object
 * @uses PATIENTFORM_RESETFORM_RESET
 * @uses PATIENTFORM_RESETFORM_DROP
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function patientform_reset_userdata($data) {
    global $CFG, $DB;

    $resetpatientforms = array();
    $droppatientforms = array();
    $status = array();
    $componentstr = get_string('modulenameplural', 'patientform');

    //get the relevant entries from $data
    foreach ($data as $key => $value) {
        switch(true) {
            case substr($key, 0, strlen(PATIENTFORM_RESETFORM_RESET)) == PATIENTFORM_RESETFORM_RESET:
                if ($value == 1) {
                    $templist = explode('_', $key);
                    if (isset($templist[3])) {
                        $resetpatientforms[] = intval($templist[3]);
                    }
                }
            break;
            case substr($key, 0, strlen(PATIENTFORM_RESETFORM_DROP)) == PATIENTFORM_RESETFORM_DROP:
                if ($value == 1) {
                    $templist = explode('_', $key);
                    if (isset($templist[3])) {
                        $droppatientforms[] = intval($templist[3]);
                    }
                }
            break;
        }
    }

    //reset the selected patientforms
    foreach ($resetpatientforms as $id) {
        $patientform = $DB->get_record('patientform', array('id'=>$id));
       patientform_delete_all_completeds($patientform);
        $status[] = array('component'=>$componentstr.':'.$patientform->name,
                        'item'=>get_string('resetting_data', 'patientform'),
                        'error'=>false);
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $shifterror = !shift_course_mod_dates('patientform', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => $shifterror);
    }

    return $status;
}

/**
 * Called by course/reset.php
 *
 * @global object
 * @uses PATIENTFORM_RESETFORM_RESET
 * @param object $mform form passed by reference
 */
function patientform_reset_course_form_definition(&$mform) {
    global $COURSE, $DB;

    $mform->addElement('header', 'patientformheader', get_string('modulenameplural', 'patientform'));

    if (!$patientforms = $DB->get_records('patientform', array('course'=>$COURSE->id), 'name')) {
        return;
    }

    $mform->addElement('static', 'hint', get_string('resetting_data', 'patientform'));
    foreach ($patientforms as $patientform) {
        $mform->addElement('checkbox', PATIENTFORM_RESETFORM_RESET.$patientform->id, $patientform->name);
    }
}

/**
 * Course reset form defaults.
 *
 * @global object
 * @uses PATIENTFORM_RESETFORM_RESET
 * @param object $course
 */
function patientform_reset_course_form_defaults($course) {
    global $DB;

    $return = array();
    if (!$patientforms = $DB->get_records('patientform', array('course'=>$course->id), 'name')) {
        return;
    }
    foreach ($patientforms as $patientform) {
        $return[PATIENTFORM_RESETFORM_RESET.$patientform->id] = true;
    }
    return $return;
}

/**
 * Called by course/reset.php and shows the formdata by coursereset.
 * it prints checkboxes for each patientform available at the given course
 * there are two checkboxes:
 * 1) delete userdata and keep the patientform
 * 2) delete userdata and drop the patientform
 *
 * @global object
 * @uses PATIENTFORM_RESETFORM_RESET
 * @uses PATIENTFORM_RESETFORM_DROP
 * @param object $course
 * @return void
 */
function patientform_reset_course_form($course) {
    global $DB, $OUTPUT;

    echo get_string('resetting_patientforms', 'patientform'); echo ':<br />';
    if (!$patientforms = $DB->get_records('patientform', array('course'=>$course->id), 'name')) {
        return;
    }

    foreach ($patientforms as $patientform) {
        echo '<p>';
        echo get_string('name', 'patientform').': '.$patientform->name.'<br />';
        echo html_writer::checkbox(PATIENTFORM_RESETFORM_RESET.$patientform->id,
                                1, true,
                                get_string('resetting_data', 'patientform'));
        echo '<br />';
        echo html_writer::checkbox(PATIENTFORM_RESETFORM_DROP.$patientform->id,
                                1, false,
                                get_string('drop_patientform', 'patientform'));
        echo '</p>';
    }
}

/**
 * This gets an array with default options for the editor
 *
 * @return array the options
 */
function patientform_get_editor_options() {
    return array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'trusttext'=>true);
}

/**
 * This creates new events given as timeopen and closeopen by $patientform.
 *
 * @global object
 * @param object $patientform
 * @return void
 */
function patientform_set_events($patientform) {
    global $DB, $CFG;

    // Include calendar/lib.php.
    require_once($CFG->dirroot.'/calendar/lib.php');

    // Get CMID if not sent as part of $patientform.
    if (!isset($patientform->coursemodule)) {
        $cm = get_coursemodule_from_instance('patientform', $patientform->id, $patientform->course);
        $patientform->coursemodule = $cm->id;
    }

    // patientform start calendar events.
    $eventid = $DB->get_field('event', 'id',
            array('modulename' => 'patientform', 'instance' => $patientform->id, 'eventtype' => PATIENTFORM_EVENT_TYPE_OPEN));

    if (isset($patientform->timeopen) && $patientform->timeopen > 0) {
        $event = new stdClass();
        $event->eventtype    = PATIENTFORM_EVENT_TYPE_OPEN;
        $event->type         = empty($patientform->timeclose) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
        $event->name         = get_string('calendarstart', 'patientform', $patientform->name);
        $event->description  = format_module_intro('patientform', $patientform, $patientform->coursemodule);
        $event->timestart    = $patientform->timeopen;
        $event->timesort     = $patientform->timeopen;
        $event->visible      = instance_is_visible('patientform', $patientform);
        $event->timeduration = 0;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            // Event doesn't exist so create one.
            $event->courseid     = $patientform->course;
            $event->groupid      = 0;
            $event->userid       = 0;
            $event->modulename   = 'patientform';
            $event->instance     = $patientform->id;
            $event->eventtype    = PATIENTFORM_EVENT_TYPE_OPEN;
            calendar_event::create($event);
        }
    } else if ($eventid) {
        // Calendar event is on longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }

    // patientform close calendar events.
    $eventid = $DB->get_field('event', 'id',
            array('modulename' => 'patientform', 'instance' => $patientform->id, 'eventtype' => PATIENTFORM_EVENT_TYPE_CLOSE));

    if (isset($patientform->timeclose) && $patientform->timeclose > 0) {
        $event = new stdClass();
        $event->type         = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype    = PATIENTFORM_EVENT_TYPE_CLOSE;
        $event->name         = get_string('calendarend', 'patientform', $patientform->name);
        $event->description  = format_module_intro('patientform', $patientform, $patientform->coursemodule);
        $event->timestart    = $patientform->timeclose;
        $event->timesort     = $patientform->timeclose;
        $event->visible      = instance_is_visible('patientform', $patientform);
        $event->timeduration = 0;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            // Event doesn't exist so create one.
            $event->courseid     = $patientform->course;
            $event->groupid      = 0;
            $event->userid       = 0;
            $event->modulename   = 'patientform';
            $event->instance     = $patientform->id;
            calendar_event::create($event);
        }
    } else if ($eventid) {
        // Calendar event is on longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every patientform event in the site is checked, else
 * only patientform events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param int $courseid
 * @param int|stdClass $instance patientform module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 */
function patientform_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('patientform', array('id' => $instance), '*', MUST_EXIST);
        }
        patientform_set_events($instance);
        return true;
    }

    if ($courseid) {
        if (! $patientforms = $DB->get_records("patientform", array("course" => $courseid))) {
            return true;
        }
    } else {
        if (! $patientforms = $DB->get_records("patientform")) {
            return true;
        }
    }

    foreach ($patientforms as $patientform) {
        patientform_set_events($patientform);
    }
    return true;
}

/**
 * this function is called by {@link patientform_delete_userdata()}
 * it drops the patientform-instance from the course_module table
 *
 * @global object
 * @param int $id the id from the coursemodule
 * @return boolean
 */
function patientform_delete_course_module($id) {
    global $DB;

    if (!$cm = $DB->get_record('course_modules', array('id'=>$id))) {
        return true;
    }
    return $DB->delete_records('course_modules', array('id'=>$cm->id));
}



////////////////////////////////////////////////
//functions to handle capabilities
////////////////////////////////////////////////

/**
 * returns the context-id related to the given coursemodule-id
 *
 * @deprecated since 3.1
 * @staticvar object $context
 * @param int $cmid the coursemodule-id
 * @return object $context
 */
function patientform_get_context($cmid) {
    debugging('Function patientform_get_context() is deprecated because it was not used.',
            DEBUG_DEVELOPER);
    static $context;

    if (isset($context)) {
        return $context;
    }

    $context = context_module::instance($cmid);
    return $context;
}

/**
 *  returns true if the current role is faked by switching role feature
 *
 * @global object
 * @return boolean
 */
function patientform_check_is_switchrole() {
    global $USER;
    if (isset($USER->switchrole) AND
            is_array($USER->switchrole) AND
            count($USER->switchrole) > 0) {

        return true;
    }
    return false;
}

/**
 * count users which have not completed the patientform
 *
 * @global object
 * @uses CONTEXT_MODULE
 * @param cm_info $cm Course-module object
 * @param int $group single groupid
 * @param string $sort
 * @param int $startpage
 * @param int $pagecount
 * @param bool $includestatus to return if the user started or not the patientform among the complete user record
 * @return array array of user ids or user objects when $includestatus set to true
 */
function patientform_get_incomplete_users(cm_info $cm,
                                       $group = false,
                                       $sort = '',
                                       $startpage = false,
                                       $pagecount = false,
                                       $includestatus = false) {

    global $DB;

    $context = context_module::instance($cm->id);

    //first get all user who can complete this patientform
    $cap = 'mod/patientform:complete';
    $allnames = get_all_user_name_fields(true, 'u');
    $fields = 'u.id, ' . $allnames . ', u.picture, u.email, u.imagealt';
    if (!$allusers = get_users_by_capability($context,
                                            $cap,
                                            $fields,
                                            $sort,
                                            '',
                                            '',
                                            $group,
                                            '',
                                            true)) {
        return false;
    }
    // Filter users that are not in the correct group/grouping.
    $info = new \core_availability\info_module($cm);
    $allusersrecords = $info->filter_user_list($allusers);

    $allusers = array_keys($allusersrecords);

    //now get all completeds
    $params = array('patientform'=>$cm->instance);
    if ($completedusers = $DB->get_records_menu('patientform_completed', $params, '', 'id, userid')) {
        // Now strike all completedusers from allusers.
        $allusers = array_diff($allusers, $completedusers);
    }

    //for paging I use array_slice()
    if ($startpage !== false AND $pagecount !== false) {
        $allusers = array_slice($allusers, $startpage, $pagecount);
    }

    // Check if we should return the full users objects.
    if ($includestatus) {
        $userrecords = [];
        $startedusers = $DB->get_records_menu('patientform_completedtmp', ['patientform' => $cm->instance], '', 'id, userid');
        $startedusers = array_flip($startedusers);
        foreach ($allusers as $userid) {
            $allusersrecords[$userid]->patientformstarted = isset($startedusers[$userid]);
            $userrecords[] = $allusersrecords[$userid];
        }
        return $userrecords;
    } else {    // Return just user ids.
        return $allusers;
    }
}

/**
 * count users which have not completed the patientform
 *
 * @global object
 * @param object $cm
 * @param int $group single groupid
 * @return int count of userrecords
 */
function patientform_count_incomplete_users($cm, $group = false) {
    if ($allusers = patientform_get_incomplete_users($cm, $group)) {
        return count($allusers);
    }
    return 0;
}

/**
 * count users which have completed a patientform
 * @global object
 * @uses PATINTFORM_ANONYMOUS_NO
 * @param object $cm
 * @param int $group single groupid
 * @return int count of userrecords
 */
function patientform_count_complete_users($cm, $group = false) {
    global $DB;

    $params = array(PATIENTFORM_ANONYMOUS_NO, $cm->instance);

    $fromgroup = '';
    $wheregroup = '';
    if ($group) {
        $fromgroup = ', {groups_members} g';
        $wheregroup = ' AND g.groupid = ? AND g.userid = c.userid';
        $params[] = $group;
    }

    $sql = 'SELECT COUNT(u.id) FROM {user} u, {patientform_completed} c'.$fromgroup.'
              WHERE anonymous_response = ? AND u.id = c.userid AND c.patientform = ?
              '.$wheregroup;

    return $DB->count_records_sql($sql, $params);

}

/**
 * get users which have completed a patientform
 *
 * @global object
 * @uses CONTEXT_MODULE
 * @uses PATIENTFORM_ANONYMOUS_NO
 * @param object $cm
 * @param int $group single groupid
 * @param string $where a sql where condition (must end with " AND ")
 * @param array parameters used in $where
 * @param string $sort a table field
 * @param int $startpage
 * @param int $pagecount
 * @return object the userrecords
 */
function patientform_get_complete_users($cm,
                                     $group = false,
                                     $where = '',
                                     array $params = null,
                                     $sort = '',
                                     $startpage = false,
                                     $pagecount = false) {

    global $DB;

    $context = context_module::instance($cm->id);

    $params = (array)$params;

    $params['anon'] = PATIENTFORM_ANONYMOUS_NO;
    $params['instance'] = $cm->instance;

    $fromgroup = '';
    $wheregroup = '';
    if ($group) {
        $fromgroup = ', {groups_members} g';
        $wheregroup = ' AND g.groupid = :group AND g.userid = c.userid';
        $params['group'] = $group;
    }

    if ($sort) {
        $sortsql = ' ORDER BY '.$sort;
    } else {
        $sortsql = '';
    }

    $ufields = user_picture::fields('u');
    $sql = 'SELECT DISTINCT '.$ufields.', c.timemodified as completed_timemodified
            FROM {user} u, {patientform_completed} c '.$fromgroup.'
            WHERE '.$where.' anonymous_response = :anon
                AND u.id = c.userid
                AND c.patientform = :instance
              '.$wheregroup.$sortsql;

    if ($startpage === false OR $pagecount === false) {
        $startpage = false;
        $pagecount = false;
    }
    return $DB->get_records_sql($sql, $params, $startpage, $pagecount);
}

/**
 * get users which have the viewreports-capability
 *
 * @uses CONTEXT_MODULE
 * @param int $cmid
 * @param mixed $groups single groupid or array of groupids - group(s) user is in
 * @return object the userrecords
 */
function patientform_get_viewreports_users($cmid, $groups = false) {

    $context = context_module::instance($cmid);

    //description of the call below:
    //get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='',
    //                          $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context,
                            'mod/patientform:viewreports',
                            '',
                            'lastname',
                            '',
                            '',
                            $groups,
                            '',
                            false);
}

/**
 * get users which have the receivemail-capability
 *
 * @uses CONTEXT_MODULE
 * @param int $cmid
 * @param mixed $groups single groupid or array of groupids - group(s) user is in
 * @return object the userrecords
 */
function patientform_get_receivemail_users($cmid, $groups = false) {

    $context = context_module::instance($cmid);

    //description of the call below:
    //get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='',
    //                          $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context,
                            'mod/patientform:receivemail',
                            '',
                            'lastname',
                            '',
                            '',
                            $groups,
                            '',
                            false);
}

////////////////////////////////////////////////
//functions to handle the templates
////////////////////////////////////////////////
////////////////////////////////////////////////

/**
 * creates a new template-record.
 *
 * @global object
 * @param int $courseid
 * @param string $name the name of template shown in the templatelist
 * @param int $ispublic 0:privat 1:public
 * @return int the new templateid
 */
function patientform_create_template($courseid, $name, $ispublic = 0) {
    global $DB;

    $templ = new stdClass();
    $templ->course   = ($ispublic ? 0 : $courseid);
    $templ->name     = $name;
    $templ->ispublic = $ispublic;

    $templid = $DB->insert_record('patientform_template', $templ);
    return $DB->get_record('patientform_template', array('id'=>$templid));
}

/**
 * creates new template items.
 * all items will be copied and the attribute patientform will be set to 0
 * and the attribute template will be set to the new templateid
 *
 * @global object
 * @uses CONTEXT_MODULE
 * @uses CONTEXT_COURSE
 * @param object $patientform
 * @param string $name the name of template shown in the templatelist
 * @param int $ispublic 0:privat 1:public
 * @return boolean
 */
function patientform_save_as_template($patientform, $name, $ispublic = 0) {
    global $DB;
    $fs = get_file_storage();

    if (!$patientformitems = $DB->get_records('patientform_item', array('patientform'=>$patientform->id))) {
        return false;
    }

    if (!$newtempl = patientform_create_template($patientform->course, $name, $ispublic)) {
        return false;
    }

    //files in the template_item are in the context of the current course or
    //if the template is public the files are in the system context
    //files in the patientform_item are in the patientform_context of the patientform
    if ($ispublic) {
        $s_context = context_system::instance();
    } else {
        $s_context = context_course::instance($newtempl->course);
    }
    $cm = get_coursemodule_from_instance('patientform', $patientform->id);
    $f_context = context_module::instance($cm->id);

    //create items of this new template
    //depend items we are storing temporary in an mapping list array(new id => dependitem)
    //we also store a mapping of all items array(oldid => newid)
    $dependitemsmap = array();
    $itembackup = array();
    foreach ($patientformitems as $item) {

        $t_item = clone($item);

        unset($t_item->id);
        $t_item->patientform = 0;
        $t_item->template     = $newtempl->id;
        $t_item->id = $DB->insert_record('patientform_item', $t_item);
        //copy all included files to the patientform_template filearea
        $itemfiles = $fs->get_area_files($f_context->id,
                                    'mod_patientform',
                                    'item',
                                    $item->id,
                                    "id",
                                    false);
        if ($itemfiles) {
            foreach ($itemfiles as $ifile) {
                $file_record = new stdClass();
                $file_record->contextid = $s_context->id;
                $file_record->component = 'mod_patientform';
                $file_record->filearea = 'template';
                $file_record->itemid = $t_item->id;
                $fs->create_file_from_storedfile($file_record, $ifile);
            }
        }

        $itembackup[$item->id] = $t_item->id;
        if ($t_item->dependitem) {
            $dependitemsmap[$t_item->id] = $t_item->dependitem;
        }

    }

    //remapping the dependency
    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('patientform_item', array('id'=>$key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('patientform_item', $newitem);
    }

    return true;
}

/**
 * deletes all patientform_items related to the given template id
 *
 * @global object
 * @uses CONTEXT_COURSE
 * @param object $template the template
 * @return void
 */
function patientform_delete_template($template) {
    global $DB;

    //deleting the files from the item is done by patientform_delete_item
    if ($t_items = $DB->get_records("patientform_item", array("template"=>$template->id))) {
        foreach ($t_items as $t_item) {
            patientform_delete_item($t_item->id, false, $template);
        }
    }
    $DB->delete_records("patientform_template", array("id"=>$template->id));
}

/**
 * creates new patientform_item-records from template.
 * if $deleteold is set true so the existing items of the given patientform will be deleted
 * if $deleteold is set false so the new items will be appanded to the old items
 *
 * @global object
 * @uses CONTEXT_COURSE
 * @uses CONTEXT_MODULE
 * @param object $patientform
 * @param int $templateid
 * @param boolean $deleteold
 */
function patientform_items_from_template($patientform, $templateid, $deleteold = false) {
    global $DB, $CFG;

    require_once($CFG->libdir.'/completionlib.php');

    $fs = get_file_storage();

    if (!$template = $DB->get_record('patientform_template', array('id'=>$templateid))) {
        return false;
    }
    //get all templateitems
    if (!$templitems = $DB->get_records('patientform_item', array('template'=>$templateid))) {
        return false;
    }

    //files in the template_item are in the context of the current course
    //files in the patientform_item are in the patientform_context of the patientform
    if ($template->ispublic) {
        $s_context = context_system::instance();
    } else {
        $s_context = context_course::instance($patientform->course);
    }
    $course = $DB->get_record('course', array('id'=>$patientform->course));
    $cm = get_coursemodule_from_instance('patientform', $patientform->id);
    $f_context = context_module::instance($cm->id);

    //if deleteold then delete all old items before
    //get all items
    if ($deleteold) {
        if ($patientformitems = $DB->get_records('patientform_item', array('patientform'=>$patientform->id))) {
            //delete all items of this patientform
            foreach ($patientformitems as $item) {
                patientform_delete_item($item->id, false);
            }

            $params = array('patientform'=>$patientform->id);
            if ($completeds = $DB->get_records('patientform_completed', $params)) {
                $completion = new completion_info($course);
                foreach ($completeds as $completed) {
                    $DB->delete_records('patientform_completed', array('id' => $completed->id));
                    // Update completion state
                    if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC &&
                            $patientform->completionsubmit) {
                        $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
                    }
                }
            }
            $DB->delete_records('patientform_completedtmp', array('patientform'=>$patientform->id));
        }
        $positionoffset = 0;
    } else {
        //if the old items are kept the new items will be appended
        //therefor the new position has an offset
        $positionoffset = $DB->count_records('patientform_item', array('patientform'=>$patientform->id));
    }

    //create items of this new template
    //depend items we are storing temporary in an mapping list array(new id => dependitem)
    //we also store a mapping of all items array(oldid => newid)
    $dependitemsmap = array();
    $itembackup = array();
    foreach ($templitems as $t_item) {
        $item = clone($t_item);
        unset($item->id);
        $item->patientform = $patientform->id;
        $item->template = 0;
        $item->position = $item->position + $positionoffset;

        $item->id = $DB->insert_record('patientform_item', $item);

        //moving the files to the new item
        $templatefiles = $fs->get_area_files($s_context->id,
                                        'mod_patientform',
                                        'template',
                                        $t_item->id,
                                        "id",
                                        false);
        if ($templatefiles) {
            foreach ($templatefiles as $tfile) {
                $file_record = new stdClass();
                $file_record->contextid = $f_context->id;
                $file_record->component = 'mod_patientform';
                $file_record->filearea = 'item';
                $file_record->itemid = $item->id;
                $fs->create_file_from_storedfile($file_record, $tfile);
            }
        }

        $itembackup[$t_item->id] = $item->id;
        if ($item->dependitem) {
            $dependitemsmap[$item->id] = $item->dependitem;
        }
    }

    //remapping the dependency
    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('patientform_item', array('id'=>$key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('patientform_item', $newitem);
    }
}

/**
 * get the list of available templates.
 * if the $onlyown param is set true so only templates from own course will be served
 * this is important for droping templates
 *
 * @global object
 * @param object $course
 * @param string $onlyownorpublic
 * @return array the template recordsets
 */
function patientform_get_template_list($course, $onlyownorpublic = '') {
    global $DB, $CFG;

    switch($onlyownorpublic) {
        case '':
            $templates = $DB->get_records_select('patientform_template',
                                                 'course = ? OR ispublic = 1',
                                                 array($course->id),
                                                 'name');
            break;
        case 'own':
            $templates = $DB->get_records('patientform_template',
                                          array('course'=>$course->id),
                                          'name');
            break;
        case 'public':
            $templates = $DB->get_records('patientform_template', array('ispublic'=>1), 'name');
            break;
    }
    return $templates;
}

////////////////////////////////////////////////
//Handling der Items
////////////////////////////////////////////////
////////////////////////////////////////////////

/**
 * load the lib.php from item-plugin-dir and returns the instance of the itemclass
 *
 * @param string $typ
 * @return patientform_item_base the instance of itemclass
 */
function patientform_get_item_class($typ) {
    global $CFG;

    //get the class of item-typ
    $itemclass = 'patientform_item_'.$typ;
    //get the instance of item-class
    if (!class_exists($itemclass)) {
        require_once($CFG->dirroot.'/mod/patientform/item/'.$typ.'/lib.php');
    }
    return new $itemclass();
}

/**
 * load the available item plugins from given subdirectory of $CFG->dirroot
 * the default is "mod/patientform/item"
 *
 * @global object
 * @param string $dir the subdir
 * @return array pluginnames as string
 */
function patientform_load_patientform_items($dir = 'mod/patientform/item') {
    global $CFG;
    $names = get_list_of_plugins($dir);
    $ret_names = array();

    foreach ($names as $name) {
        require_once($CFG->dirroot.'/'.$dir.'/'.$name.'/lib.php');
        if (class_exists('patientform_item_'.$name)) {
            $ret_names[] = $name;
        }
    }
    return $ret_names;
}

/**
 * load the available item plugins to use as dropdown-options
 *
 * @global object
 * @return array pluginnames as string
 */
function patientform_load_patientform_items_options() {
    global $CFG;

    $patientform_options = array("pagebreak" => get_string('add_pagebreak', 'patientform'));

    if (!$patientform_names = patientform_load_patientform_items('mod/patientform/item')) {
        return array();
    }

    foreach ($patientform_names as $fn) {
        $patientform_options[$fn] = get_string($fn, 'patientform');
    }
    asort($patientform_options);
    return $patientform_options;
}

/**
 * load the available items for the depend item dropdown list shown in the edit_item form
 *
 * @global object
 * @param object $patientform
 * @param object $item the item of the edit_item form
 * @return array all items except the item $item, labels and pagebreaks
 */
function patientform_get_depend_candidates_for_item($patientform, $item) {
    global $DB;
    //all items for dependitem
    $where = "patientform = ? AND typ != 'pagebreak' AND hasvalue = 1";
    $params = array($patientform->id);
    if (isset($item->id) AND $item->id) {
        $where .= ' AND id != ?';
        $params[] = $item->id;
    }
    $dependitems = array(0 => get_string('choose'));
    $patientformitems = $DB->get_records_select_menu('patientform_item',
                                                  $where,
                                                  $params,
                                                  'position',
                                                  'id, label');

    if (!$patientformitems) {
        return $dependitems;
    }
    //adding the choose-option
    foreach ($patientformitems as $key => $val) {
        if (trim(strval($val)) !== '') {
            $dependitems[$key] = format_string($val);
        }
    }
    return $dependitems;
}

/**
 * creates a new item-record
 *
 * @deprecated since 3.1
 * @param object $data the data from edit_item_form
 * @return int the new itemid
 */
function patientform_create_item($data) {
    debugging('Functionpatientform_create_item() is deprecated because it was not used.',
            DEBUG_DEVELOPER);
    global $DB;

    $item = new stdClass();
    $item->patientform = $data->patientformid;

    $item->template=0;
    if (isset($data->templateid)) {
            $item->template = intval($data->templateid);
    }

    $itemname = trim($data->itemname);
    $item->name = ($itemname ? $data->itemname : get_string('no_itemname', 'patientform'));

    if (!empty($data->itemlabel)) {
        $item->label = trim($data->itemlabel);
    } else {
        $item->label = get_string('no_itemlabel', 'patientform');
    }

    $itemobj = patientform_get_item_class($data->typ);
    $item->presentation = ''; //the date comes from postupdate() of the itemobj

    $item->hasvalue = $itemobj->get_hasvalue();

    $item->typ = $data->typ;
    $item->position = $data->position;

    $item->required=0;
    if (!empty($data->required)) {
        $item->required = $data->required;
    }

    $item->id = $DB->insert_record('patientform_item', $item);

    //move all itemdata to the data
    $data->id = $item->id;
    $data->patientform = $item->patientform;
    $data->name = $item->name;
    $data->label = $item->label;
    $data->required = $item->required;
    return $itemobj->postupdate($data);
}

/**
 * save the changes of a given item.
 *
 * @global object
 * @param object $item
 * @return boolean
 */
function patientform_update_item($item) {
    global $DB;
    return $DB->update_record("patientform_item", $item);
}

/**
 * deletes an item and also deletes all related values
 *
 * @global object
 * @uses CONTEXT_MODULE
 * @param int $itemid
 * @param boolean $renumber should the kept items renumbered Yes/No
 * @param object $template if the template is given so the items are bound to it
 * @return void
 */
function patientform_delete_item($itemid, $renumber = true, $template = false) {
    global $DB;

    $item = $DB->get_record('patientform_item', array('id'=>$itemid));

    //deleting the files from the item
    $fs = get_file_storage();

    if ($template) {
        if ($template->ispublic) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($template->course);
        }
        $templatefiles = $fs->get_area_files($context->id,
                                    'mod_patientform',
                                    'template',
                                    $item->id,
                                    "id",
                                    false);

        if ($templatefiles) {
            $fs->delete_area_files($context->id, 'mod_patientform', 'template', $item->id);
        }
    } else {
        if (!$cm = get_coursemodule_from_instance('patientform', $item->patientform)) {
            return false;
        }
        $context = context_module::instance($cm->id);

        $itemfiles = $fs->get_area_files($context->id,
                                    'mod_patientform',
                                    'item',
                                    $item->id,
                                    "id", false);

        if ($itemfiles) {
            $fs->delete_area_files($context->id, 'mod_patientform', 'item', $item->id);
        }
    }

    $DB->delete_records("patientform_value", array("item"=>$itemid));
    $DB->delete_records("patientform_valuetmp", array("item"=>$itemid));

    //remove all depends
    $DB->set_field('patientform_item', 'dependvalue', '', array('dependitem'=>$itemid));
    $DB->set_field('patientform_item', 'dependitem', 0, array('dependitem'=>$itemid));

    $DB->delete_records("patientform_item", array("id"=>$itemid));
    if ($renumber) {
       patientform_renumber_items($item->patientform);
    }
}

/**
 * deletes all items of the given patientformid
 *
 * @global object
 * @param int $patientformid
 * @return void
 */
function patientform_delete_all_items($patientformid) {
    global $DB, $CFG;
    require_once($CFG->libdir.'/completionlib.php');

    if (!$patientform = $DB->get_record('patientform', array('id'=>$patientformid))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('patientform', $patientform->id)) {
        return false;
    }

    if (!$course = $DB->get_record('course', array('id'=>$patientform->course))) {
        return false;
    }

    if (!$items = $DB->get_records('patientform_item', array('patientform'=>$patientformid))) {
        return;
    }
    foreach ($items as $item) {
        patientform_delete_item($item->id, false);
    }
    if ($completeds = $DB->get_records('patientform_completed', array('patientform'=>$patientform->id))) {
        $completion = new completion_info($course);
        foreach ($completeds as $completed) {
            $DB->delete_records('patientform_completed', array('id' => $completed->id));
            // Update completion state
            if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC &&
                    $patientform->completionsubmit) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
            }
        }
    }

    $DB->delete_records('patientform_completedtmp', array('patientform'=>$patientformid));

}

/**
 * this function toggled the item-attribute required (yes/no)
 *
 * @global object
 * @param object $item
 * @return boolean
 */
function patientform_switch_item_required($item) {
    global $DB, $CFG;

    $itemobj = patientform_get_item_class($item->typ);

    if ($itemobj->can_switch_require()) {
        $new_require_val = (int)!(bool)$item->required;
        $params = array('id'=>$item->id);
        $DB->set_field('patientform_item', 'required', $new_require_val, $params);
    }
    return true;
}

/**
 * renumbers all items of the given patientformid
 *
 * @global object
 * @param int $patientformid
 * @return void
 */
function patientform_renumber_items($patientformid) {
    global $DB;

    $items = $DB->get_records('patientform_item', array('patientform'=>$patientformid), 'position');
    $pos = 1;
    if ($items) {
        foreach ($items as $item) {
            $DB->set_field('patientform_item', 'position', $pos, array('id'=>$item->id));
            $pos++;
        }
    }
}

/**
 * this decreases the position of the given item
 *
 * @global object
 * @param object $item
 * @return bool
 */
function patientform_moveup_item($item) {
    global $DB;

    if ($item->position == 1) {
        return true;
    }

    $params = array('patientform'=>$item->patientform);
    if (!$items = $DB->get_records('patientform_item', $params, 'position')) {
        return false;
    }

    $itembefore = null;
    foreach ($items as $i) {
        if ($i->id == $item->id) {
            if (is_null($itembefore)) {
                return true;
            }
            $itembefore->position = $item->position;
            $item->position--;
            patientform_update_item($itembefore);
            patientform_update_item($item);
            patientform_renumber_items($item->patientform);
            return true;
        }
        $itembefore = $i;
    }
    return false;
}

/**
 * this increased the position of the given item
 *
 * @global object
 * @param object $item
 * @return bool
 */
function patientform_movedown_item($item) {
    global $DB;

    $params = array('patientform'=>$item->patientform);
    if (!$items = $DB->get_records('patientform_item', $params, 'position')) {
        return false;
    }

    $movedownitem = null;
    foreach ($items as $i) {
        if (!is_null($movedownitem) AND $movedownitem->id == $item->id) {
            $movedownitem->position = $i->position;
            $i->position--;
            patientform_update_item($movedownitem);
            patientform_update_item($i);
            patientform_renumber_items($item->patientform);
            return true;
        }
        $movedownitem = $i;
    }
    return false;
}

/**
 * here the position of the given item will be set to the value in $pos
 *
 * @global object
 * @param object $moveitem
 * @param int $pos
 * @return boolean
 */
function patientform_move_item($moveitem, $pos) {
    global $DB;

    $params = array('patientform'=>$moveitem->patientform);
    if (!$allitems = $DB->get_records('patientform_item', $params, 'position')) {
        return false;
    }
    if (is_array($allitems)) {
        $index = 1;
        foreach ($allitems as $item) {
            if ($index == $pos) {
                $index++;
            }
            if ($item->id == $moveitem->id) {
                $moveitem->position = $pos;
                patientform_update_item($moveitem);
                continue;
            }
            $item->position = $index;
            patientform_update_item($item);
            $index++;
        }
        return true;
    }
    return false;
}

/**
 * prints the given item as a preview.
 * each item-class has an own print_item_preview function implemented.
 *
 * @deprecated since Moodle 3.1
 * @global object
 * @param object $item the item what we want to print out
 * @return void
 */
function patientform_print_item_preview($item) {
    debugging('Function patientform_print_item_preview() is deprecated and does nothing. '
            . 'Items must implement complete_form_element()', DEBUG_DEVELOPER);
}

/**
 * prints the given item in the completion form.
 * each item-class has an own print_item_complete function implemented.
 *
 * @deprecated since Moodle 3.1
 * @param object $item the item what we want to print out
 * @param mixed $value the value
 * @param boolean $highlightrequire if this set true and the value are false on completing so the item will be highlighted
 * @return void
 */
function patientform_print_item_complete($item, $value = false, $highlightrequire = false) {
    debugging('Function patientform_print_item_complete() is deprecated and does nothing. '
            . 'Items must implement complete_form_element()', DEBUG_DEVELOPER);
}

/**
 * prints the given item in the show entries page.
 * each item-class has an own print_item_show_value function implemented.
 *
 * @deprecated since Moodle 3.1
 * @param object $item the item what we want to print out
 * @param mixed $value
 * @return void
 */
function patientform_print_item_show_value($item, $value = false) {
    debugging('Function patientform_print_item_show_value() is deprecated and does nothing. '
            . 'Items must implement complete_form_element()', DEBUG_DEVELOPER);
}

/**
 * if the user completes a patientform and there is a pagebreak so the values are saved temporary.
 * the values are not saved permanently until the user click on save button
 *
 * @global object
 * @param object $patientformcompleted
 * @return object temporary saved completed-record
 */
function patientform_set_tmp_values($patientformcompleted) {
    global $DB;

    //first we create a completedtmp
    $tmpcpl = new stdClass();
    foreach ($patientformcompleted as $key => $value) {
        $tmpcpl->{$key} = $value;
    }
    unset($tmpcpl->id);
    $tmpcpl->timemodified = time();
    $tmpcpl->id = $DB->insert_record('patientform_completedtmp', $tmpcpl);
    //get all values of original-completed
    if (!$values = $DB->get_records('patientform_value', array('completed'=>$patientformcompleted->id))) {
        return;
    }
    foreach ($values as $value) {
        unset($value->id);
        $value->completed = $tmpcpl->id;
        $DB->insert_record('patientform_valuetmp', $value);
    }
    return $tmpcpl;
}

/**
 * this saves the temporary saved values permanently
 *
 * @global object
 * @param object $patientformcompletedtmp the temporary completed
 * @param object $patientformcompleted the target completed
 * @return int the id of the completed
 */
function patientform_save_tmp_values($patientformcompletedtmp, $patientformcompleted) {
    global $DB;

    $tmpcplid = $patientformcompletedtmp->id;
    if ($patientformcompleted) {
        //first drop all existing values
        $DB->delete_records('patientform_value', array('completed'=>$patientformcompleted->id));
        //update the current completed
        $patientformcompleted->timemodified = time();
        $DB->update_record('patientform_completed', $patientformcompleted);
    } else {
        $patientformcompleted = clone($patientformcompletedtmp);
        $patientformcompleted->id = '';
        $patientformcompleted->timemodified = time();
        $patientformcompleted->id = $DB->insert_record('patientform_completed', $patientformcompleted);
    }

    $allitems = $DB->get_records('patientform_item', array('patientform' => $patientformcompleted->patientform));

    //save all the new values from patientform_valuetmp
    //get all values of tmp-completed
    $params = array('completed'=>$patientformcompletedtmp->id);
    $values = $DB->get_records('patientform_valuetmp', $params);
    foreach ($values as $value) {
        //check if there are depend items
        $item = $DB->get_record('patientform_item', array('id'=>$value->item));
        if ($item->dependitem > 0 && isset($allitems[$item->dependitem])) {
            $check = patientform_compare_item_value($tmpcplid,
                                        $allitems[$item->dependitem],
                                        $item->dependvalue,
                                        true);
        } else {
            $check = true;
        }
        if ($check) {
            unset($value->id);
            $value->completed = $patientformcompleted->id;
            $DB->insert_record('patientform_value', $value);
        }
    }
    //drop all the tmpvalues
    $DB->delete_records('patientform_valuetmp', array('completed'=>$tmpcplid));
    $DB->delete_records('patientform_completedtmp', array('id'=>$tmpcplid));

    // Trigger event for the delete action we performed.
    $cm = get_coursemodule_from_instance('patientform', $patientformcompleted->patientform);
    $event = \mod_patientform\event\response_submitted::create_from_record($patientformcompleted, $cm);
    $event->trigger();
    return $patientformcompleted->id;

}

/**
 * deletes the given temporary completed and all related temporary values
 *
 * @deprecated since Moodle 3.1
 *
 * @param int $tmpcplid
 * @return void
 */
function patientform_delete_completedtmp($tmpcplid) {
    global $DB;

    debugging('Function patientform_delete_completedtmp() is deprecated because it is no longer used',
            DEBUG_DEVELOPER);

    $DB->delete_records('patientform_valuetmp', array('completed'=>$tmpcplid));
    $DB->delete_records('patientform_completedtmp', array('id'=>$tmpcplid));
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the pagebreaks
////////////////////////////////////////////////

/**
 * this creates a pagebreak.
 * a pagebreak is a special kind of item
 *
 * @global object
 * @param int $patientformid
 * @return mixed false if there already is a pagebreak on last position or the id of the pagebreak-item
 */
function patientform_create_pagebreak($patientformid) {
    global $DB;

    //check if there already is a pagebreak on the last position
    $lastposition = $DB->count_records('patientform_item', array('patientform'=>$patientformid));
    if ($lastposition == patientform_get_last_break_position($patientformid)) {
        return false;
    }

    $item = new stdClass();
    $item->patientform = $patientformid;

    $item->template=0;

    $item->name = '';

    $item->presentation = '';
    $item->hasvalue = 0;

    $item->typ = 'pagebreak';
    $item->position = $lastposition + 1;

    $item->required=0;

    return $DB->insert_record('patientform_item', $item);
}

/**
 * get all positions of pagebreaks in the given patientform
 *
 * @global object
 * @param int $patientformid
 * @return array all ordered pagebreak positions
 */
function patientform_get_all_break_positions($patientformid) {
    global $DB;

    $params = array('typ'=>'pagebreak', 'patientform'=>$patientformid);
    $allbreaks = $DB->get_records_menu('patientform_item', $params, 'position', 'id, position');
    if (!$allbreaks) {
        return false;
    }
    return array_values($allbreaks);
}

/**
 * get the position of the last pagebreak
 *
 * @param int $patientformid
 * @return int the position of the last pagebreak
 */
function patientform_get_last_break_position($patientformid) {
    if (!$allbreaks = patientform_get_all_break_positions($patientformid)) {
        return false;
    }
    return $allbreaks[count($allbreaks) - 1];
}

/**
 * this returns the position where the user can continue the completing.
 *
 * @deprecated since Moodle 3.1
 * @global object
 * @global object
 * @global object
 * @param int $patientformid
 * @param int $courseid
 * @param string $guestid this id will be saved temporary and is unique
 * @return int the position to continue
 */
function patientform_get_page_to_continue($patientformid, $courseid = false, $guestid = false) {
    global $CFG, $USER, $DB;

    debugging('Function patientform_get_page_to_continue() is deprecated and since it is '
            . 'no longer used in mod_patientform', DEBUG_DEVELOPER);

    //is there any break?

    if (!$allbreaks = patientform_get_all_break_positions($patientformid)) {
        return false;
    }

    $params = array();
    if ($courseid) {
        $courseselect = "AND fv.course_id = :courseid";
        $params['courseid'] = $courseid;
    } else {
        $courseselect = '';
    }

    if ($guestid) {
        $userselect = "AND fc.guestid = :guestid";
        $usergroup = "GROUP BY fc.guestid";
        $params['guestid'] = $guestid;
    } else {
        $userselect = "AND fc.userid = :userid";
        $usergroup = "GROUP BY fc.userid";
        $params['userid'] = $USER->id;
    }

    $sql =  "SELECT MAX(fi.position)
               FROM {patientform_completedtmp} fc, {patientform_valuetmp} fv, {patientform_item} fi
              WHERE fc.id = fv.completed
                    $userselect
                    AND fc.patientform = :patientformid
                    $courseselect
                    AND fi.id = fv.item
         $usergroup";
    $params['patientformid'] = $patientformid;

    $lastpos = $DB->get_field_sql($sql, $params);

    //the index of found pagebreak is the searched pagenumber
    foreach ($allbreaks as $pagenr => $br) {
        if ($lastpos < $br) {
            return $pagenr;
        }
    }
    return count($allbreaks);
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the values
////////////////////////////////////////////////

/**
 * cleans the userinput while submitting the form.
 *
 * @deprecated since Moodle 3.1
 * @param mixed $value
 * @return mixed
 */
function patientform_clean_input_value($item, $value) {
    debugging('Function patientform_clean_input_value() is deprecated and does nothing. '
            . 'Items must implement complete_form_element()', DEBUG_DEVELOPER);
}

/**
 * this saves the values of an completed.
 * if the param $tmp is set true so the values are saved temporary in table patientform_valuetmp.
 * if there is already a completed and the userid is set so the values are updated.
 * on all other things new value records will be created.
 *
 * @deprecated since Moodle 3.1
 *
 * @param int $usrid
 * @param boolean $tmp
 * @return mixed false on error or the completeid
 */
function patientform_save_values($usrid, $tmp = false) {
    global $DB;

    debugging('Function patientform_save_values() was deprecated because it did not have '.
            'enough arguments, was not suitable for non-temporary table and was taking '.
            'data directly from input', DEBUG_DEVELOPER);

    $completedid = optional_param('completedid', 0, PARAM_INT);
    $tmpstr = $tmp ? 'tmp' : '';
    $time = time();
    $timemodified = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));

    if ($usrid == 0) {
        return patientform_create_values($usrid, $timemodified, $tmp);
    }
    $completed = $DB->get_record('patientform_completed'.$tmpstr, array('id'=>$completedid));
    if (!$completed) {
        return patientform_create_values($usrid, $timemodified, $tmp);
    } else {
        $completed->timemodified = $timemodified;
        return patientform_update_values($completed, $tmp);
    }
}

/**
 * this saves the values from anonymous user such as guest on the main-site
 *
 * @deprecated since Moodle 3.1
 *
 * @param string $guestid the unique guestidentifier
 * @return mixed false on error or the completeid
 */
function patientform_save_guest_values($guestid) {
    global $DB;

    debugging('Function patientform_save_guest_values() was deprecated because it did not have '.
            'enough arguments, was not suitable for non-temporary table and was taking '.
            'data directly from input', DEBUG_DEVELOPER);

    $completedid = optional_param('completedid', false, PARAM_INT);

    $timemodified = time();
    if (!$completed = $DB->get_record('patientform_completedtmp', array('id'=>$completedid))) {
        return patientform_create_values(0, $timemodified, true, $guestid);
    } else {
        $completed->timemodified = $timemodified;
        return patientform_update_values($completed, true);
    }
}

/**
 * get the value from the given item related to the given completed.
 * the value can come as temporary or as permanently value. the deciding is done by $tmp
 *
 * @global object
 * @param int $completeid
 * @param int $itemid
 * @param boolean $tmp
 * @return mixed the value, the type depends on plugin-definition
 */
function patientform_get_item_value($completedid, $itemid, $tmp = false) {
    global $DB;

    $tmpstr = $tmp ? 'tmp' : '';
    $params = array('completed'=>$completedid, 'item'=>$itemid);
    return $DB->get_field('patientform_value'.$tmpstr, 'value', $params);
}

/**
 * compares the value of the itemid related to the completedid with the dependvalue.
 * this is used if a depend item is set.
 * the value can come as temporary or as permanently value. the deciding is done by $tmp.
 *
 * @param int $completedid
 * @param stdClass|int $item
 * @param mixed $dependvalue
 * @param bool $tmp
 * @return bool
 */
function patientform_compare_item_value($completedid, $item, $dependvalue, $tmp = false) {
    global $DB;

    if (is_int($item)) {
        $item = $DB->get_record('patientform_item', array('id' => $item));
    }

    $dbvalue = patientform_get_item_value($completedid, $item->id, $tmp);

    $itemobj = patientform_get_item_class($item->typ);
    return $itemobj->compare_value($item, $dbvalue, $dependvalue); //true or false
}

/**
 * this function checks the correctness of values.
 * the rules for this are implemented in the class of each item.
 * it can be the required attribute or the value self e.g. numeric.
 * the params first/lastitem are given to determine the visible range between pagebreaks.
 *
 * @global object
 * @param int $firstitem the position of firstitem for checking
 * @param int $lastitem the position of lastitem for checking
 * @return boolean
 */
function patientform_check_values($firstitem, $lastitem) {
    debugging('Function patientform_check_values() is deprecated and does nothing. '
            . 'Items must implement complete_form_element()', DEBUG_DEVELOPER);
    return true;
}

/**
 * this function create a complete-record and the related value-records.
 * depending on the $tmp (true/false) the values are saved temporary or permanently
 *
 * @deprecated since Moodle 3.1
 *
 * @param int $userid
 * @param int $timemodified
 * @param boolean $tmp
 * @param string $guestid a unique identifier to save temporary data
 * @return mixed false on error or the completedid
 */
function patientform_create_values($usrid, $timemodified, $tmp = false, $guestid = false) {
    global $DB;

    debugging('Function patientform_create_values() was deprecated because it did not have '.
            'enough arguments, was not suitable for non-temporary table and was taking '.
            'data directly from input', DEBUG_DEVELOPER);

    $tmpstr = $tmp ? 'tmp' : '';
    //first we create a new completed record
    $completed = new stdClass();
    $completed->patientform           = $patientformid;
    $completed->userid             = $usrid;
    $completed->guestid            = $guestid;
    $completed->timemodified       = $timemodified;
    $completed->anonymous_response = $anonymous_response;

    $completedid = $DB->insert_record('patientform_completed'.$tmpstr, $completed);

    $completed = $DB->get_record('patientform_completed'.$tmpstr, array('id'=>$completedid));

    //the keys are in the form like abc_xxx
    //with explode we make an array with(abc, xxx) and (abc=typ und xxx=itemnr)

    //get the items of the patientform
    if (!$allitems = $DB->get_records('patientform_item', array('patientform'=>$completed->patientform))) {
        return false;
    }
    foreach ($allitems as $item) {
        if (!$item->hasvalue) {
            continue;
        }
        //get the class of item-typ
        $itemobj =patientform_get_item_class($item->typ);

        $keyname = $item->typ.'_'.$item->id;

        if ($item->typ === 'multichoice') {
            $itemvalue = optional_param_array($keyname, null, PARAM_INT);
        } else {
            $itemvalue = optional_param($keyname, null, PARAM_NOTAGS);
        }

        if (is_null($itemvalue)) {
            continue;
        }

        $value = new stdClass();
        $value->item = $item->id;
        $value->completed = $completed->id;
        $value->course_id = $courseid;

        //the kind of values can be absolutely different
        //so we run create_value directly by the item-class
        $value->value = $itemobj->create_value($itemvalue);
        $DB->insert_record('patientform_value'.$tmpstr, $value);
    }
    return $completed->id;
}

/**
 * this function updates a complete-record and the related value-records.
 * depending on the $tmp (true/false) the values are saved temporary or permanently
 *
 * @global object
 * @param object $completed
 * @param boolean $tmp
 * @return int the completedid
 */
function patientform_update_values($completed, $tmp = false) {
    global $DB;

    debugging('Function patientform_update_values() was deprecated because it did not have '.
            'enough arguments, was not suitable for non-temporary table and was taking '.
            'data directly from input', DEBUG_DEVELOPER);

    $courseid = optional_param('courseid', false, PARAM_INT);
    $tmpstr = $tmp ? 'tmp' : '';

    $DB->update_record('patientform_completed'.$tmpstr, $completed);
    //get the values of this completed
    $values = $DB->get_records('patientform_value'.$tmpstr, array('completed'=>$completed->id));

    //get the items of the patientform
    if (!$allitems = $DB->get_records('patientform_item', array('patientform'=>$completed->patientform))) {
        return false;
    }
    foreach ($allitems as $item) {
        if (!$item->hasvalue) {
            continue;
        }
        //get the class of item-typ
        $itemobj = patientform_get_item_class($item->typ);

        $keyname = $item->typ.'_'.$item->id;

        if ($item->typ === 'multichoice') {
            $itemvalue = optional_param_array($keyname, null, PARAM_INT);
        } else {
            $itemvalue = optional_param($keyname, null, PARAM_NOTAGS);
        }

        //is the itemvalue set (could be a subset of items because pagebreak)?
        if (is_null($itemvalue)) {
            continue;
        }

        $newvalue = new stdClass();
        $newvalue->item = $item->id;
        $newvalue->completed = $completed->id;
        $newvalue->course_id = $courseid;

        //the kind of values can be absolutely different
        //so we run create_value directly by the item-class
        $newvalue->value = $itemobj->create_value($itemvalue);

        //check, if we have to create or update the value
        $exist = false;
        foreach ($values as $value) {
            if ($value->item == $newvalue->item) {
                $newvalue->id = $value->id;
                $exist = true;
                break;
            }
        }
        if ($exist) {
            $DB->update_record('patientform_value'.$tmpstr, $newvalue);
        } else {
            $DB->insert_record('patientform_value'.$tmpstr, $newvalue);
        }
    }

    return $completed->id;
}

/**
 * get the values of an item depending on the given groupid.
 * if the patientform is anonymous so the values are shuffled
 *
 * @global object
 * @global object
 * @param object $item
 * @param int $groupid
 * @param int $courseid
 * @param bool $ignore_empty if this is set true so empty values are not delivered
 * @return array the value-records
 */
function patientform_get_group_values($item,
                                   $groupid = false,
                                   $courseid = false,
                                   $ignore_empty = false) {

    global $CFG, $DB;

    //if the groupid is given?
    if (intval($groupid) > 0) {
        $params = array();
        if ($ignore_empty) {
            $value = $DB->sql_compare_text('fbv.value');
            $ignore_empty_select = "AND $value != :emptyvalue AND $value != :zerovalue";
            $params += array('emptyvalue' => '', 'zerovalue' => '0');
        } else {
            $ignore_empty_select = "";
        }

        $query = 'SELECT fbv .  *
                    FROM {patientform_value} fbv, {patientform_completed} fbc, {groups_members} gm
                   WHERE fbv.item = :itemid
                         AND fbv.completed = fbc.id
                         AND fbc.userid = gm.userid
                         '.$ignore_empty_select.'
                         AND gm.groupid = :groupid
                ORDER BY fbc.timemodified';
        $params += array('itemid' => $item->id, 'groupid' => $groupid);
        $values = $DB->get_records_sql($query, $params);

    } else {
        $params = array();
        if ($ignore_empty) {
            $value = $DB->sql_compare_text('value');
            $ignore_empty_select = "AND $value != :emptyvalue AND $value != :zerovalue";
            $params += array('emptyvalue' => '', 'zerovalue' => '0');
        } else {
            $ignore_empty_select = "";
        }

        if ($courseid) {
            $select = "item = :itemid AND course_id = :courseid ".$ignore_empty_select;
            $params += array('itemid' => $item->id, 'courseid' => $courseid);
            $values = $DB->get_records_select('patientform_value', $select, $params);
        } else {
            $select = "item = :itemid ".$ignore_empty_select;
            $params += array('itemid' => $item->id);
            $values = $DB->get_records_select('patientform_value', $select, $params);
        }
    }
    $params = array('id'=>$item->patientform);
    if ($DB->get_field('patientform', 'anonymous', $params) == PATIENTFORM_ANONYMOUS_YES) {
        if (is_array($values)) {
            shuffle($values);
        }
    }
    return $values;
}

/**
 * check for multiple_submit = false.
 * if the patientform is global so the courseid must be given
 *
 * @global object
 * @global object
 * @param int $patientformid
 * @param int $courseid
 * @return boolean true if the patientform already is submitted otherwise false
 */
function patientform_is_already_submitted($patientformid, $courseid = false) {
    global $USER, $DB;

    if (!isloggedin() || isguestuser()) {
        return false;
    }

    $params = array('userid' => $USER->id, 'patientform' => $patientformid);
    if ($courseid) {
        $params['courseid'] = $courseid;
    }
    return $DB->record_exists('patientform_completed', $params);
}

/**
 * if the completion of a patientform will be continued eg.
 * by pagebreak or by multiple submit so the complete must be found.
 * if the param $tmp is set true so all things are related to temporary completeds
 *
 * @deprecated since Moodle 3.1
 * @param int $patientformid
 * @param boolean $tmp
 * @param int $courseid
 * @param string $guestid
 * @return int the id of the found completed
 */
function patientform_get_current_completed($patientformid,
                                        $tmp = false,
                                        $courseid = false,
                                        $guestid = false) {

    debugging('Function patientform_get_current_completed() is deprecated. Please use either '.
            'patientform_get_current_completed_tmp() or patientform_get_last_completed()',
            DEBUG_DEVELOPER);

    global $USER, $CFG, $DB;

    $tmpstr = $tmp ? 'tmp' : '';

    if (!$courseid) {
        if ($guestid) {
            $params = array('patientform'=>$patientformid, 'guestid'=>$guestid);
            return $DB->get_record('patientform_completed'.$tmpstr, $params);
        } else {
            $params = array('patientform'=>$patientformid, 'userid'=>$USER->id);
            return $DB->get_record('patientform_completed'.$tmpstr, $params);
        }
    }

    $params = array();

    if ($guestid) {
        $userselect = "AND fc.guestid = :guestid";
        $params['guestid'] = $guestid;
    } else {
        $userselect = "AND fc.userid = :userid";
        $params['userid'] = $USER->id;
    }
    //if courseid is set the patientform is global.
    //there can be more than one completed on one patientform
    $sql =  "SELECT DISTINCT fc.*
               FROM {patientform_value{$tmpstr}} fv, {patientform_completed{$tmpstr}} fc
              WHERE fv.course_id = :courseid
                    AND fv.completed = fc.id
                    $userselect
                    AND fc.patientform = :patientformid";
    $params['courseid']   = intval($courseid);
    $params['patientformid'] = $patientformid;

    if (!$sqlresult = $DB->get_records_sql($sql, $params)) {
        return false;
    }
    foreach ($sqlresult as $r) {
        return $DB->get_record('patientform_completed'.$tmpstr, array('id'=>$r->id));
    }
}

/**
 * get the completeds depending on the given groupid.
 *
 * @global object
 * @global object
 * @param object $patientform
 * @param int $groupid
 * @param int $courseid
 * @return mixed array of found completeds otherwise false
 */
function patientform_get_completeds_group($patientform, $groupid = false, $courseid = false) {
    global $CFG, $DB;

    if (intval($groupid) > 0) {
        $query = "SELECT fbc.*
                    FROM {patientform_completed} fbc, {groups_members} gm
                   WHERE fbc.patientform = ?
                         AND gm.groupid = ?
                         AND fbc.userid = gm.userid";
        if ($values = $DB->get_records_sql($query, array($patientform->id, $groupid))) {
            return $values;
        } else {
            return false;
        }
    } else {
        if ($courseid) {
            $query = "SELECT DISTINCT fbc.*
                        FROM {patientform_completed} fbc, {patientform_value} fbv
                        WHERE fbc.id = fbv.completed
                            AND fbc.patientform = ?
                            AND fbv.course_id = ?
                        ORDER BY random_response";
            if ($values = $DB->get_records_sql($query, array($patientform->id, $courseid))) {
                return $values;
            } else {
                return false;
            }
        } else {
            if ($values = $DB->get_records('patientform_completed', array('patientform'=>$patientform->id))) {
                return $values;
            } else {
                return false;
            }
        }
    }
}

/**
 * get the count of completeds depending on the given groupid.
 *
 * @global object
 * @global object
 * @param object $patientform
 * @param int $groupid
 * @param int $courseid
 * @return mixed count of completeds or false
 */
function patientform_get_completeds_group_count($patientform, $groupid = false, $courseid = false) {
    global $CFG, $DB;

    if ($courseid > 0 AND !$groupid <= 0) {
        $sql = "SELECT id, COUNT(item) AS ci
                  FROM {patientform_value}
                 WHERE course_id  = ?
              GROUP BY item ORDER BY ci DESC";
        if ($foundrecs = $DB->get_records_sql($sql, array($courseid))) {
            $foundrecs = array_values($foundrecs);
            return $foundrecs[0]->ci;
        }
        return false;
    }
    if ($values = patientform_get_completeds_group($patientform, $groupid)) {
        return count($values);
    } else {
        return false;
    }
}

/**
 * deletes all completed-recordsets from a patientform.
 * all related data such as values also will be deleted
 *
 * @param stdClass|int $patientform
 * @param stdClass|cm_info $cm
 * @param stdClass $course
 * @return void
 */
function patientform_delete_all_completeds($patientform, $cm = null, $course = null) {
    global $DB;

    if (is_int($patientform)) {
        $patientform = $DB->get_record('patientform', array('id' => $patientform));
    }

    if (!$completeds = $DB->get_records('patientform_completed', array('patientform' => $patientform->id))) {
        return;
    }

    if (!$course && !($course = $DB->get_record('course', array('id' => $patientform->course)))) {
        return false;
    }

    if (!$cm && !($cm = get_coursemodule_from_instance('patientform', $patientform->id))) {
        return false;
    }

    foreach ($completeds as $completed) {
        patientform_delete_completed($completed, $patientform, $cm, $course);
    }
}

/**
 * deletes a completed given by completedid.
 * all related data such values or tracking data also will be deleted
 *
 * @param int|stdClass $completed
 * @param stdClass $patientform
 * @param stdClass|cm_info $cm
 * @param stdClass $course
 * @return boolean
 */
function patientform_delete_completed($completed, $patientform = null, $cm = null, $course = null) {
    global $DB, $CFG;
    require_once($CFG->libdir.'/completionlib.php');

    if (!isset($completed->id)) {
        if (!$completed = $DB->get_record('patientform_completed', array('id' => $completed))) {
            return false;
        }
    }

    if (!$patientform && !($patientform = $DB->get_record('patientform', array('id' => $completed->patientform)))) {
        return false;
    }

    if (!$course && !($course = $DB->get_record('course', array('id' => $patientform->course)))) {
        return false;
    }

    if (!$cm && !($cm = get_coursemodule_from_instance('patientform', $patientform->id))) {
        return false;
    }

    //first we delete all related values
    $DB->delete_records('patientform_value', array('completed' => $completed->id));

    // Delete the completed record.
    $return = $DB->delete_records('patientform_completed', array('id' => $completed->id));

    // Update completion state
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC && $patientform->completionsubmit) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
    }
    // Trigger event for the delete action we performed.
    $event = \mod_patientform\event\response_deleted::create_from_record($completed, $cm, $patientform);
    $event->trigger();

    return $return;
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle sitecourse mapping
////////////////////////////////////////////////

/**
 * checks if the course and the patientform is in the table patientform_sitecourse_map.
 *
 * @deprecated since 3.1
 * @param int $patientformid
 * @param int $courseid
 * @return int the count of records
 */
function patientform_is_course_in_sitecourse_map($patientformid, $courseid) {
    debugging('Function patientform_is_course_in_sitecourse_map() is deprecated because it was not used.',
            DEBUG_DEVELOPER);
    global $DB;
    $params = array('patientformid'=>$patientformid, 'courseid'=>$courseid);
    return $DB->count_records('patientform_sitecourse_map', $params);
}

/**
 * checks if the patientform is in the table patientform_sitecourse_map.
 *
 * @deprecated since 3.1
 * @param int $patientformid
 * @return boolean
 */
function patientform_is_patientform_in_sitecourse_map($patientformid) {
    debugging('Function patientform_is_patientform_in_sitecourse_map() is deprecated because it was not used.',
            DEBUG_DEVELOPER);
    global $DB;
    return $DB->record_exists('patientform_sitecourse_map', array('patientformid'=>$patientformid));
}

/**
 * gets the patientforms from tablepatientform_sitecourse_map.
 * this is used to show the global patientforms on the patientform block
 * all patientforms with the following criteria will be selected:<br />
 *
 * 1) all patientforms which id are listed together with the courseid in sitecoursemap and<br />
 * 2) all patientforms which not are listed in sitecoursemap
 *
 * @global object
 * @param int $courseid
 * @return array the patientform-records
 */
function patientform_get_patientforms_from_sitecourse_map($courseid) {
    global $DB;

    //first get all patientforms listed in sitecourse_map with named courseid
    $sql = "SELECT f.id AS id,
                   cm.id AS cmid,
                   f.name AS name,
                   f.timeopen AS timeopen,
                   f.timeclose AS timeclose
            FROM {patientform} f, {course_modules} cm, {patientform_sitecourse_map} sm, {modules} m
            WHERE f.id = cm.instance
                   AND f.course = '".SITEID."'
                   AND m.id = cm.module
                   AND m.name = 'patientform'
                   AND sm.courseid = ?
                   AND sm.patientformid = f.id";

    if (!$patientforms1 = $DB->get_records_sql($sql, array($courseid))) {
        $patientforms1 = array();
    }

    //second get all patientforms not listed in sitecourse_map
    $patientforms2 = array();
    $sql = "SELECT f.id AS id,
                   cm.id AS cmid,
                   f.name AS name,
                   f.timeopen AS timeopen,
                   f.timeclose AS timeclose
            FROM {patientform} f, {course_modules} cm, {modules} m
            WHERE f.id = cm.instance
                   AND f.course = '".SITEID."'
                   AND m.id = cm.module
                   AND m.name = 'patientform'";
    if (!$allpatientforms = $DB->get_records_sql($sql)) {
        $allpatientforms = array();
    }
    foreach ($allpatientforms as $a) {
        if (!$DB->record_exists('patientform_sitecourse_map', array('patientformid'=>$a->id))) {
            $patientforms2[] = $a;
        }
    }

    $patientforms = array_merge($patientforms1, $patientforms2);
    $modinfo = get_fast_modinfo(SITEID);
    return array_filter($patientforms, function($f) use ($modinfo) {
        return ($cm = $modinfo->get_cm($f->cmid)) && $cm->uservisible;
    });

}

/**
 * Gets the courses from table patientform_sitecourse_map
 *
 * @param int $patientformid
 * @return array the course-records
 */
function patientform_get_courses_from_sitecourse_map($patientformid) {
    global $DB;

    $sql = "SELECT c.id, c.fullname, c.shortname
              FROM {patientform_sitecourse_map} f, {course} c
             WHERE c.id = f.courseid
                   AND f.patientformid = ?
          ORDER BY c.fullname";

    return $DB->get_records_sql($sql, array($patientformid));

}

/**
 * Updates the course mapping for the patientform
 *
 * @param stdClass $patientform
 * @param array $courses array of course ids
 */
function patientform_update_sitecourse_map($patientform, $courses) {
    global $DB;
    if (empty($courses)) {
        $courses = array();
    }
    $currentmapping = $DB->get_fieldset_select('patientform_sitecourse_map', 'courseid', 'patientformid=?', array($patientform->id));
    foreach (array_diff($courses, $currentmapping) as $courseid) {
        $DB->insert_record('patientform_sitecourse_map', array('patientformid' => $patientform->id, 'courseid' => $courseid));
    }
    foreach (array_diff($currentmapping, $courses) as $courseid) {
        $DB->delete_records('patientform_sitecourse_map', array('patientformid' => $patientform->id, 'courseid' => $courseid));
    }
    // TODO MDL-53574 add events.
}

/**
 * removes non existing courses or patientforms from sitecourse_map.
 * it shouldn't be called all too often
 * a good place for it could be the mapcourse.php or unmapcourse.php
 *
 * @deprecated since 3.1
 * @global object
 * @return void
 */
function patientform_clean_up_sitecourse_map() {
    global $DB;
    debugging('Function patientform_clean_up_sitecourse_map() is deprecated because it was not used.',
            DEBUG_DEVELOPER);

    $maps = $DB->get_records('patientform_sitecourse_map');
    foreach ($maps as $map) {
        if (!$DB->get_record('course', array('id'=>$map->courseid))) {
            $params = array('courseid'=>$map->courseid, 'patientformid'=>$map->patientformid);
            $DB->delete_records('patientform_sitecourse_map', $params);
            continue;
        }
        if (!$DB->get_record('patientform', array('id'=>$map->patientformid))) {
            $params = array('courseid'=>$map->courseid, 'patientformid'=>$map->patientformid);
            $DB->delete_records('patientform_sitecourse_map', $params);
            continue;
        }

    }
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//not relatable functions
////////////////////////////////////////////////

/**
 * prints the option items of a selection-input item (dropdownlist).
 * @deprecated since 3.1
 * @param int $startval the first value of the list
 * @param int $endval the last value of the list
 * @param int $selectval which item should be selected
 * @param int $interval the stepsize from the first to the last value
 * @return void
 */
function patientform_print_numeric_option_list($startval, $endval, $selectval = '', $interval = 1) {
    debugging('Function patientform_print_numeric_option_list() is deprecated because it was not used.',
            DEBUG_DEVELOPER);
    for ($i = $startval; $i <= $endval; $i += $interval) {
        if ($selectval == ($i)) {
            $selected = 'selected="selected"';
        } else {
            $selected = '';
        }
        echo '<option '.$selected.'>'.$i.'</option>';
    }
}

/**
 * sends an email to the teachers of the course where the given patientform is placed.
 *
 * @global object
 * @global object
 * @uses PATIENTFORM_ANONYMOUS_NO
 * @uses FORMAT_PLAIN
 * @param object $cm the coursemodule-record
 * @param object $patientform
 * @param object $course
 * @param stdClass|int $user
 * @param stdClass $completed record from patientform_completed if known
 * @return void
 */
function patientform_send_email($cm, $patientform, $course, $user, $completed = null) {
    global $CFG, $DB;

    if ($patientform->email_notification == 0) {  // No need to do anything
        return;
    }

    if (is_int($user)) {
        $user = $DB->get_record('user', array('id' => $user));
    }

    if (isset($cm->groupmode) && empty($course->groupmodeforce)) {
        $groupmode =  $cm->groupmode;
    } else {
        $groupmode = $course->groupmode;
    }

    if ($groupmode == SEPARATEGROUPS) {
        $groups = $DB->get_records_sql_menu("SELECT g.name, g.id
                                               FROM {groups} g, {groups_members} m
                                              WHERE g.courseid = ?
                                                    AND g.id = m.groupid
                                                    AND m.userid = ?
                                           ORDER BY name ASC", array($course->id, $user->id));
        $groups = array_values($groups);

        $teachers = patientform_get_receivemail_users($cm->id, $groups);
    } else {
        $teachers = patientform_get_receivemail_users($cm->id);
    }

    if ($teachers) {

        $strpatientforms = get_string('modulenameplural', 'patientform');
        $strpatientform  = get_string('modulename', 'patientform');

        if ($patientform->anonymous == PATIENTFORM_ANONYMOUS_NO) {
            $printusername = fullname($user);
        } else {
            $printusername = get_string('anonymous_user', 'patientform');
        }

        foreach ($teachers as $teacher) {
            $info = new stdClass();
            $info->username = $printusername;
            $info->patientform = format_string($patientform->name, true);
            $info->url = $CFG->wwwroot.'/mod/patientform/show_entries.php?'.
                            'id='.$cm->id.'&'.
                            'userid=' . $user->id;
            if ($completed) {
                $info->url .= '&showcompleted=' . $completed->id;
                if ($patientform->course == SITEID) {
                    // Course where patientform was completed (for site patientforms only).
                    $info->url .= '&courseid=' . $completed->courseid;
                }
            }

            $a = array('username' => $info->username, 'patientformname' => $patientform->name);

            $postsubject = get_string('patientformcompleted', 'patientform', $a);
            $posttext = patientform_send_email_text($info, $course);

            if ($teacher->mailformat == 1) {
                $posthtml = patientform_send_email_html($info, $course, $cm);
            } else {
                $posthtml = '';
            }

            if ($patientform->anonymous == PATIENTFORM_ANONYMOUS_NO) {
                $eventdata = new \core\message\message();
                $eventdata->courseid         = $course->id;
                $eventdata->name             = 'submission';
                $eventdata->component        = 'mod_patientform';
                $eventdata->userfrom         = $user;
                $eventdata->userto           = $teacher;
                $eventdata->subject          = $postsubject;
                $eventdata->fullmessage      = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml  = $posthtml;
                $eventdata->smallmessage     = '';
                $eventdata->courseid         = $course->id;
                $eventdata->contexturl       = $info->url;
                $eventdata->contexturlname   = $info->patientform;
                message_send($eventdata);
            } else {
                $eventdata = new \core\message\message();
                $eventdata->courseid         = $course->id;
                $eventdata->name             = 'submission';
                $eventdata->component        = 'mod_patientform';
                $eventdata->userfrom         = $teacher;
                $eventdata->userto           = $teacher;
                $eventdata->subject          = $postsubject;
                $eventdata->fullmessage      = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml  = $posthtml;
                $eventdata->smallmessage     = '';
                $eventdata->courseid         = $course->id;
                $eventdata->contexturl       = $info->url;
                $eventdata->contexturlname   = $info->patientform;
                message_send($eventdata);
            }
        }
    }
}

/**
 * sends an email to the teachers of the course where the given patientform is placed.
 *
 * @global object
 * @uses FORMAT_PLAIN
 * @param object $cm the coursemodule-record
 * @param object $patientform
 * @param object $course
 * @return void
 */
function patientform_send_email_anonym($cm, $patientform, $course) {
    global $CFG;

    if ($patientform->email_notification == 0) { // No need to do anything
        return;
    }

    $teachers = patientform_get_receivemail_users($cm->id);

    if ($teachers) {

        $strpatientforms = get_string('modulenameplural', 'patientform');
        $strpatientform  = get_string('modulename', 'patientform');
        $printusername = get_string('anonymous_user', 'patientform');

        foreach ($teachers as $teacher) {
            $info = new stdClass();
            $info->username = $printusername;
            $info->patientform = format_string($patientform->name, true);
            $info->url = $CFG->wwwroot.'/mod/patientform/show_entries.php?id=' . $cm->id;

            $a = array('username' => $info->username, 'patientformname' => $patientform->name);

            $postsubject = get_string('patientformcompleted', 'patientform', $a);
            $posttext = patientform_send_email_text($info, $course);

            if ($teacher->mailformat == 1) {
                $posthtml = patientform_send_email_html($info, $course, $cm);
            } else {
                $posthtml = '';
            }

            $eventdata = new \core\message\message();
            $eventdata->courseid         = $course->id;
            $eventdata->name             = 'submission';
            $eventdata->component        = 'mod_patientform';
            $eventdata->userfrom         = $teacher;
            $eventdata->userto           = $teacher;
            $eventdata->subject          = $postsubject;
            $eventdata->fullmessage      = $posttext;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml  = $posthtml;
            $eventdata->smallmessage     = '';
            $eventdata->courseid         = $course->id;
            $eventdata->contexturl       = $info->url;
            $eventdata->contexturlname   = $info->patientform;
            message_send($eventdata);
        }
    }
}

/**
 * send the text-part of the email
 *
 * @param object $info includes some infos about the patientform you want to send
 * @param object $course
 * @return string the text you want to post
 */
function patientform_send_email_text($info, $course) {
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
    $posttext  = $courseshortname.' -> '.get_string('modulenameplural', 'patientform').' -> '.
                    $info->patientform."\n";
    $posttext .= '---------------------------------------------------------------------'."\n";
    $posttext .= get_string("emailteachermail", "patientform", $info)."\n";
    $posttext .= '---------------------------------------------------------------------'."\n";
    return $posttext;
}


/**
 * send the html-part of the email
 *
 * @global object
 * @param object $info includes some infos about the patientform you want to send
 * @param object $course
 * @return string the text you want to post
 */
function patientform_send_email_html($info, $course, $cm) {
    global $CFG;
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
    $course_url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
    $patientform_all_url = $CFG->wwwroot.'/mod/patientform/index.php?id='.$course->id;
    $patientform_url = $CFG->wwwroot.'/mod/patientform/view.php?id='.$cm->id;

    $posthtml = '<p><font face="sans-serif">'.
            '<a href="'.$course_url.'">'.$courseshortname.'</a> ->'.
            '<a href="'.$patientform_all_url.'">'.get_string('modulenameplural', 'patientform').'</a> ->'.
            '<a href="'.$patientform_url.'">'.$info->patientform.'</a></font></p>';
    $posthtml .= '<hr /><font face="sans-serif">';
    $posthtml .= '<p>'.get_string('emailteachermailhtml', 'patientform', $info).'</p>';
    $posthtml .= '</font><hr />';
    return $posthtml;
}

/**
 * @param string $url
 * @return string
 */
function patientform_encode_target_url($url) {
    if (strpos($url, '?')) {
        list($part1, $part2) = explode('?', $url, 2); //maximal 2 parts
        return $part1 . '?' . htmlentities($part2);
    } else {
        return $url;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $patientformnode The node to add module settings to
 */
function patientform_extend_settings_navigation(settings_navigation $settings,
                                             navigation_node $patientformnode) {

    global $PAGE;

    if (!$context = context_module::instance($PAGE->cm->id, IGNORE_MISSING)) {
        print_error('badcontext');
    }

    if (has_capability('mod/patientform:edititems', $context)) {
        $questionnode = $patientformnode->add(get_string('questions', 'patientform'));

        $questionnode->add(get_string('edit_items', 'patientform'),
                    new moodle_url('/mod/patientform/edit.php',
                                    array('id' => $PAGE->cm->id,
                                          'do_show' => 'edit')));

        $questionnode->add(get_string('export_questions', 'patientform'),
                    new moodle_url('/mod/patientform/export.php',
                                    array('id' => $PAGE->cm->id,
                                          'action' => 'exportfile')));

        $questionnode->add(get_string('import_questions', 'patientform'),
                    new moodle_url('/mod/patientform/import.php',
                                    array('id' => $PAGE->cm->id)));

        $questionnode->add(get_string('templates', 'patientform'),
                    new moodle_url('/mod/patientform/edit.php',
                                    array('id' => $PAGE->cm->id,
                                          'do_show' => 'templates')));
    }

    if (has_capability('mod/patientform:mapcourse', $context) && $PAGE->course->id == SITEID) {
        $patientformnode->add(get_string('mappedcourses', 'patientform'),
                    new moodle_url('/mod/patientform/mapcourse.php',
                                    array('id' => $PAGE->cm->id)));
    }

    if (has_capability('mod/patientform:viewreports', $context)) {
        $patientform = $PAGE->activityrecord;
        if ($patientform->course == SITEID) {
            $patientformnode->add(get_string('analysis', 'patientform'),
                    new moodle_url('/mod/patientform/analysis_course.php',
                                    array('id' => $PAGE->cm->id)));
        } else {
            $patientformnode->add(get_string('analysis', 'patientform'),
                    new moodle_url('/mod/patientform/analysis.php',
                                    array('id' => $PAGE->cm->id)));
        }

        $patientformnode->add(get_string('show_entries', 'patientform'),
                    new moodle_url('/mod/patientform/show_entries.php',
                                    array('id' => $PAGE->cm->id)));

        if ($patientform->anonymous == PATIENTFORM_ANONYMOUS_NO AND $patientform->course != SITEID) {
            $patientformnode->add(get_string('show_nonrespondents', 'patientform'),
                        new moodle_url('/mod/patientform/show_nonrespondents.php',
                                        array('id' => $PAGE->cm->id)));
        }
    }
}

function patientform_init_patientform_session() {
    //initialize the patientform-Session - not nice at all!!
    global $SESSION;
    if (!empty($SESSION)) {
        if (!isset($SESSION->patientform) OR !is_object($SESSION->patientform)) {
            $SESSION->patientform = new stdClass();
        }
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function patientform_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-patientform-*'=>get_string('page-mod-patientform-x', 'patientform'));
    return $module_pagetype;
}

/**
 * Move save the items of the given $patientform in the order of $itemlist.
 * @param string $itemlist a comma separated list with item ids
 * @param stdClass $patientform
 * @return bool true if success
 */
function patientform_ajax_saveitemorder($itemlist, $patientform) {
    global $DB;

    $result = true;
    $position = 0;
    foreach ($itemlist as $itemid) {
        $position++;
        $result = $result && $DB->set_field('patientform_item',
                                            'position',
                                            $position,
                                            array('id'=>$itemid, 'patientform'=>$patientform->id));
    }
    return $result;
}

/**
 * Checks if current user is able to view patientform on this course.
 *
 * @param stdClass $patientform
 * @param context_module $context
 * @param int $courseid
 * @return bool
 */
function patientform_can_view_analysis($patientform, $context, $courseid = false) {
    if (has_capability('mod/patientform:viewreports', $context)) {
        return true;
    }

    if (intval($patientform->publish_stats) != 1 ||
            !has_capability('mod/patientform:viewanalysepage', $context)) {
        return false;
    }

    if (!isloggedin() || isguestuser()) {
        // There is no tracking for the guests, assume that they can view analysis if condition above is satisfied.
        return $patientform->course == SITEID;
    }

    return patientform_is_already_submitted($patientform->id, $courseid);
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_patientform_get_fontawesome_icon_map() {
    return [
        'mod_patientform:required' => 'fa-exclamation-circle',
        'mod_patientform:notrequired' => 'fa-question-circle-o',
    ];
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.3
 */
function patientform_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER, $CFG;

    $updates = course_check_module_updates_since($cm, $from, array(), $filter);

    // Check for new attempts.
    $updates->attemptsfinished = (object) array('updated' => false);
    $updates->attemptsunfinished = (object) array('updated' => false);
    $select = 'patientform = ? AND userid = ? AND timemodified > ?';
    $params = array($cm->instance, $USER->id, $from);

    $attemptsfinished = $DB->get_records_select('patientform_completed', $select, $params, '', 'id');
    if (!empty($attemptsfinished)) {
        $updates->attemptsfinished->updated = true;
        $updates->attemptsfinished->itemids = array_keys($attemptsfinished);
    }
    $attemptsunfinished = $DB->get_records_select('patientform_completedtmp', $select, $params, '', 'id');
    if (!empty($attemptsunfinished)) {
        $updates->attemptsunfinished->updated = true;
        $updates->attemptsunfinished->itemids = array_keys($attemptsunfinished);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/patientform:viewreports', $cm->context)) {
        $select = 'patientform = ? AND timemodified > ?';
        $params = array($cm->instance, $from);

        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->userattemptsfinished = (object) array('updated' => false);
        $attemptsfinished = $DB->get_records_select('patientform_completed', $select, $params, '', 'id');
        if (!empty($attemptsfinished)) {
            $updates->userattemptsfinished->updated = true;
            $updates->userattemptsfinished->itemids = array_keys($attemptsfinished);
        }

        $updates->userattemptsunfinished = (object) array('updated' => false);
        $attemptsunfinished = $DB->get_records_select('patientform_completedtmp', $select, $params, '', 'id');
        if (!empty($attemptsunfinished)) {
            $updates->userattemptsunfinished->updated = true;
            $updates->userattemptsunfinished->itemids = array_keys($attemptsunfinished);
        }
    }

    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_patientform_core_calendar_provide_event_action(calendar_event $event,
                                                         \core_calendar\action_factory $factory) {

    $cm = get_fast_modinfo($event->courseid)->instances['patientform'][$event->instance];
    $patientformcompletion = new mod_patientform_completion(null, $cm, 0);

    if (!empty($cm->customdata['timeclose']) && $cm->customdata['timeclose'] < time()) {
        // patientform is already closed, do not display it even if it was never submitted.
        return null;
    }

    if (!$patientformcompletion->can_complete()) {
        // The user can't complete the patientform so there is no action for them.
        return null;
    }

    // The patientform is actionable if it does not have timeopen or timeopen is in the past.
    $actionable = $patientformcompletion->is_open();

    if ($actionable && $patientformcompletion->is_already_submitted()) {
        // There is no need to display anything if the user has already submitted the patientform
        return null;
    }

    return $factory->create_instance(
        get_string('answerquestions', 'patientform'),
        new \moodle_url('/mod/patientform/view.php', ['id' => $cm->id]),
        1,
        $actionable
    );
}

/**
 * Add a get_coursemodule_info function in case any patientform type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function patientform_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionsubmit, timeopen, timeclose, anonymous';
    if (!$patientform = $DB->get_record('patientform', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $patientform->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('patientform', $patientform, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionsubmit'] = $patientform->completionsubmit;
    }
    // Populate some other values that can be used in calendar or on dashboard.
    if ($patientform->timeopen) {
        $result->customdata['timeopen'] = $patientform->timeopen;
    }
    if ($patientform->timeclose) {
        $result->customdata['timeclose'] = $patientform->timeclose;
    }
    if ($patientform->anonymous) {
        $result->customdata['anonymous'] = $patientform->anonymous;
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_patientform_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (empty($val)) {
                    continue;
                }
                $descriptions[] = get_string('completionsubmit', 'patientform');
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The due date must be after the sbumission start date'],
 *     [1506741172, 'The due date must be before the cutoff date']
 * ]
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $instance The module instance to get the range from
 * @return array
 */
function mod_patientform_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $instance) {
    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == PATIENTFORM_EVENT_TYPE_OPEN) {
        // The start time of the open event can't be equal to or after the
        // close time of the choice activity.
        if (!empty($instance->timeclose)) {
            $maxdate = [
                $instance->timeclose,
                get_string('openafterclose', 'patientform')
            ];
        }
    } else if ($event->eventtype == PATIENTFORM_EVENT_TYPE_CLOSE) {
        // The start time of the close event can't be equal to or earlier than the
        // open time of the choice activity.
        if (!empty($instance->timeopen)) {
            $mindate = [
                $instance->timeopen,
                get_string('closebeforeopen', 'patientform')
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * This function will update the patientform module according to the
 * event that has been modified.
 *
 * It will set the timeopen or timeclose value of the patientform instance
 * according to the type of event provided.
 *
 * @throws \moodle_exception
 * @param \calendar_event $event
 * @param stdClass $patientform The module instance to get the range from
 */
function mod_patientform_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $patientform) {
    global $CFG, $DB;

    if (empty($event->instance) || $event->modulename != 'patientform') {
        return;
    }

    if ($event->instance != $patientform->id) {
        return;
    }

    if (!in_array($event->eventtype, [PATIENTFORM_EVENT_TYPE_OPEN, PATIENTFORM_EVENT_TYPE_CLOSE])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == PATIENTFORM_EVENT_TYPE_OPEN) {
        // If the event is for the patientform activity opening then we should
        // set the start time of the patientform activity to be the new start
        // time of the event.
        if ($patientform->timeopen != $event->timestart) {
            $patientform->timeopen = $event->timestart;
            $patientform->timemodified = time();
            $modified = true;
        }
    } else if ($event->eventtype == PATIENTFORM_EVENT_TYPE_CLOSE) {
        // If the event is for the patientform activity closing then we should
        // set the end time of the patientform activity to be the new start
        // time of the event.
        if ($patientform->timeclose != $event->timestart) {
            $patientform->timeclose = $event->timestart;
            $modified = true;
        }
    }

    if ($modified) {
        $patientform->timemodified = time();
        $DB->update_record('patientform', $patientform);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}
