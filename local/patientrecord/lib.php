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
 * @subpackage patientdetais
 * @copyright  2017 Bas Brands, basbrands.nl, bas@sonsbeekmedia.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//namespace local_autoemail;
require_once($CFG->dirroot.'/mod/forum/lib.php');
//require_once($CFG->dirroot.'/local/patientrecord/lib.php');

function patient_data_inserted($cid,$fcompid){
	global $USER,$DB;

	  $labelvalue1 = 'adharnumber';
    $labelvalue2 ='pcity';
    $labelvalue5 ='pfname';
    $labelvalue4 ='plname';
    $sessionid ='sessiondetails10';
    $username = $USER->username;
    $ressubmitted =$DB->get_record('feedback_completed',array('id' =>$fcompid));
        
    $fid = $ressubmitted->feedback;
    $fvalue = $DB->get_record('feedback',array('id' =>$fid));
       
        if($fvalue->name == 'Heart Patient'){

          $timestamp = $ressubmitted->timemodified;
          $datetimeFormat = 'Y-m-d';
          $date = new \DateTime();
          $date->setTimestamp($timestamp);
          $usercreatetime= $date->format($datetimeFormat);
          $submittedvalues = $DB->get_records('feedback_value',array('completed' => $fcompid));
         //  print_object($submittedvalues); die();
          foreach ($submittedvalues as  $value){
            
             $itemid = $value->item;
             $itemvalue = $DB->get_record('feedback_item',array('id' =>$itemid));
            //print_object($itemvalue);die();
             $labelvalue3 = $itemvalue->label;
            // print_object($labelvalue3);
             if($labelvalue3 == $labelvalue1){
            
              $patientadhar = $value->value; 
             // print_object($patientadhar);

             }elseif($labelvalue3 == $labelvalue2 ){

              $patientcity = $value->value;
              // print_object($patientcity); 

             }elseif($labelvalue3 == $labelvalue5 ){

               $firstname = $value->value;
               // print_object($firstname); 

             }elseif($labelvalue3 == $labelvalue4 ){

               $lastname = $value->value; 
               // print_object($lastname);

             }

      
           }
          // print_object($firstname);

           $uniquepatientid = $username.'_'.$patientadhar.'_'.$patientcity.'_'.$usercreatetime;
           $insertvalue = new \stdClass();
           $insertvalue->feedback_id = $fid;
           $insertvalue->completed_id = $fcompid;
           $insertvalue->consoler_username = $username;
           $insertvalue->paadharnumber = $patientadhar;
           $insertvalue->pfname = $firstname;
           $insertvalue->plname = $lastname;
           $insertvalue->pcity = $patientcity;
           $insertvalue->timemodified = $timestamp;
           $insertvalue->patient_id = $uniquepatientid;
           $insertvalue->status = 0;
         // print_object($insertvalue);die();
           $datainsert3 = $DB->insert_record('patient_complete_details',$insertvalue,false);
           $forumid = $DB->get_record('modules', array('name' => 'forum'));
           $sessionvalue =$DB->get_records_sql('SELECT * FROM {course_modules} WHERE course = ? AND module = ? AND idnumber = ?', 
           array($cid,$forumid->id, $sessionid));
           $sessionvalue1 =count($sessionvalue); 
          if ($sessionvalue1 > 1) {
             
            echo "more forum is present now";
          }else {
              //echo $sessionvalue['instance'];
              foreach ($sessionvalue as $nextvalue){
                $nextforum = $nextvalue->instance;
              }
              $forumdetails = $DB->get_record('forum',array('id' => $nextforum));
              $discussion = new \stdClass();
              $discussion->course          = $cid;
              $discussion->forum           = $forumdetails->id;
              $discussion->name            = $uniquepatientid;
              $discussion->assessed        = $forumdetails->assessed;
              $discussion->message         = $forumdetails->intro;
              $discussion->messageformat   = $forumdetails->introformat;
              $discussion->messagetrust    = true;
              $discussion->mailnow         = false;
              $discussion->groupid         = -1;
              $message = '';
               // print_object($discussion);
             $discussiondetails = forum_add_discussion($discussion, null, $message);
            // print_object($discussiondetails);die();
             $discussiondetailsid = $discussiondetails->id;
             $insertvalue1 = new \stdClass();
             $insertvalue1->userid = $user->id;
             $insertvalue1->tagcollid = 1;
             $insertvalue1->name = $uniquepatientid;
             $insertvalue1->rawname = $uniquepatientid;
             $insertvalue1->timemodified = $timestamp;
             $datainsert4 = $DB->insert_record('tag',$insertvalue1,false); 
            // print_object($discussiondetails);
             $tagvalue = $DB->get_record('tag',array('name' =>$uniquepatientid));
             $tagid = $tagvalue->id;
             $insertvalue2 = new \stdClass();
             $insertvalue2->tagid = $tagid;
             $insertvalue2->component = 'mod_forum';
             $insertvalue2->itemtype = 'forum_posts';
             $insertvalue2->itemid = $discussiondetails;
             $insertvalue2->contextid = $contextid;
             $insertvalue2->timecreated = $timestamp;
             $insertvalue2->timemodified = $timestamp;
           
             $datainsert5 = $DB->insert_record('tag_instance',$insertvalue2,false); 
            

              if (! $discussion = $DB->get_record('forum_discussions', array('forum'=>$forum->id))) {
                  print_error('cannotadd', 'forum');
              }
          }
      }


      
}

/*function get_patient_score($fcompid,$fid,$leb1){
		
	 	$fvalue = $DB->get_records('feedback_item',array('feedback' =>$fid));
        
        foreach ($fvalue as $value) {
          

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

          }
        
        }

        $insertvalue = new \stdClass();
        $insertvalue->completed_id = $fcompid;
        $insertvalue->sdq = $patientsdqscore;
        $insertvalue->impact01 = $patientimp1score;
        $insertvalue->impact02 = $patientimp2score;
        print_object($insertvalue);die();
        $datainsert3 = $DB->insert_record('patient_score_details',$insertvalue,false);

}
*/


