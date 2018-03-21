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
namespace local_patientscore;
require_once($CFG->dirroot.'/mod/forum/lib.php');

defined('MOODLE_INTERNAL') || die();

class observer {

    public static function insert_patientscore(\mod_feedback\event\response_submitted $event) {
        global $CFG, $SITE,$DB;
        $labelvalue1 = 'adharnumber';
        $labelvalue2 ='pcity';
        $sessionid ='sessiondetails8';
      
        $eventdata = $event->get_data();
        //print_object($eventdata);
        $cid = $eventdata['courseid'];
        $contextid = $eventdata['contextid'];
        $user = \core_user::get_user($eventdata['relateduserid']);
        $username = $user->username; 
        $fcompid =  $eventdata['objectid'];
        $ressubmitted =$DB->get_record('feedback_completed',array('id' =>$fcompid));
        $fid = $ressubmitted->feedback;
        $feedbackvalue = $DB->get_record('feedback',array('id' =>$fid));
        /*$mysdqscore = getpatient_score($fid,$leb1);
        $myimpact1score = getpatient_score($fid,$leb2);
*/  
       // if($feedbackvalue->name == 'Heart Patient'){

        $fvalue = $DB->get_records('feedback_item',array('feedback' =>$fid));
        foreach ($fvalue as $value) {
          $leb1 = 'SDQ';
          $leb2 = 'Impact01';
          $leb3 = 'Impact02';

          $labelvalue = $value->label;
          $itemid = $value->id;
           //print_object($labelvalue);
          $value01 = strpos($labelvalue,$leb1);
          $value02 = strpos($labelvalue,$leb2);
          $value03 = strpos($labelvalue,$leb3);
          //if($labelvalue contains $leb1){}
         static $patientsdqscore = 0;
         static $patientimp1score = 0;
         static $patientimp2score = 0;

         if($value01 !== false){


             
              $stringvalue = explode("-", $labelvalue);
              $stval01 = $stringvalue[0];
              $stval02 = $stringvalue[1];
              $stval03 = $stringvalue[2];
              $stval04 = $stringvalue[3];
              $stval05 = $stringvalue[4];
              $stval06 = $stringvalue[5];
              $patientvaluerecord = $DB->get_record('feedback_value',array('item' => $itemid,'completed' => $fcompid));
              $patientvalue = $patientvaluerecord->value;
              $pstrval = $patientvalue +1;
              
              if($stringvalue[5] == $stringvalue[$pstrval]){

                $patientsdqscore = $patientsdqscore + $stval06;
              }elseif($stringvalue[4] == $stringvalue[$pstrval]){

                $patientsdqscore = $patientsdqscore + $stval05;

              }elseif($stringvalue[3] == $stringvalue[$pstrval]){

                $patientsdqscore = $patientsdqscore + $stval04;

              }elseif($stringvalue[2] == $stringvalue[$pstrval]){

                $patientsdqscore = $patientsdqscore + $stval03;
              }       

          }elseif($value02 !== false){

             
              $stringvalue = explode("-", $labelvalue);
              $stval01 = $stringvalue[0];
              $stval02 = $stringvalue[1];
              $stval03 = $stringvalue[2];
              $stval04 = $stringvalue[3];
              $stval05 = $stringvalue[4];
              $stval06 = $stringvalue[5];
              $patientvaluerecord = $DB->get_record('feedback_value',array('item' => $itemid,'completed' => $fcompid));
              $patientvalue = $patientvaluerecord->value;
              $pstrval = $patientvalue +1;
              
              if($stringvalue[5] == $stringvalue[$pstrval]){

                $patientimp1score = $patientimp1score + $stval06;
              }elseif($stringvalue[4] == $stringvalue[$pstrval]){

                $patientimp1score = $patientimp1score + $stval05;

              }elseif($stringvalue[3] == $stringvalue[$pstrval]){

                $patientimp1score = $patientimp1score + $stval04;

              }elseif($stringvalue[2] == $stringvalue[$pstrval]){

                $patientimp1score = $patientimp1score + $stval03;
              }       

          }elseif($value03 !== false){

              $stringvalue = explode("-", $labelvalue);
              $stval01 = $stringvalue[0];
              $stval02 = $stringvalue[1];
              $stval03 = $stringvalue[2];
              $stval04 = $stringvalue[3];
              $stval05 = $stringvalue[4];
              $stval06 = $stringvalue[5];
              $patientvaluerecord = $DB->get_record('feedback_value',array('item' => $itemid,'completed' => $fcompid));
              $patientvalue = $patientvaluerecord->value;
              $pstrval = $patientvalue +1;
              
              if($stringvalue[5] == $stringvalue[$pstrval]){

                $patientimp2score = $patientimp2score + $stval06;
              }elseif($stringvalue[4] == $stringvalue[$pstrval]){

                $patientimp2score = $patientimp2score + $stval05;

              }elseif($stringvalue[3] == $stringvalue[$pstrval]){

                $patientimp2score = $patientimp2score + $stval04;

              }elseif($stringvalue[2] == $stringvalue[$pstrval]){

                $patientimp2score = $patientimp2score + $stval03;
              }       

          }
        
        }

        $insertvalue = new \stdClass();
        $insertvalue->completed_id = $fcompid;
        $insertvalue->sdq = $patientsdqscore;
        $insertvalue->impact01 = $patientimp1score;
        $insertvalue->impact02 = $patientimp2score;
        $datainsert3 = $DB->insert_record('patient_score_details',$insertvalue,false);
    // }
        
    } 

}


  
       