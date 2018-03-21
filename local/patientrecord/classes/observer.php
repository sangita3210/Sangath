<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.


/**
 * This plugin sends users a welcome message after logging in
 * and notify a moderator a new user has been added
 * it has a settings page that allow you to configure the messages
 * send.
 *
 * @package    local
 * @subpackage welcome
 * @copyright  2017 Bas Brands, basbrands.nl, bas@sonsbeekmedia.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//namespace local_autoemail;
namespace local_patientrecord;
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->dirroot.'/local/patientrecord/lib.php');


defined('MOODLE_INTERNAL') || die();

class observer {

    public static function insert_patientrecord(\mod_feedback\event\response_submitted $event) {
        global $CFG, $SITE,$DB;
        
      
        $eventdata = $event->get_data();
        $cid = $eventdata['courseid'];
        $contextid = $eventdata['contextid'];
        $user = \core_user::get_user($eventdata['relateduserid']);
        $username = $user->username; 
        $fcompid =  $eventdata['objectid'];
        $ressubmitted =$DB->get_record('feedback_completed',array('id' =>$fcompid));
        $fid = $ressubmitted->feedback;
        patient_data_inserted($cid,$fcompid);

         
        
    } 

}


  
       