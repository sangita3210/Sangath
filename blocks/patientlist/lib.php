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
 * Contains functions called by core.
 *
 * @package    block_patientlist
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function block_patientlist_display_numberof_patients($USER){

	global $DB,$OUTPUT,$COURSE;

	$uname = $USER->username;
    $uid = $USER->id;
    $totalpatient = $DB->get_records('patient_complete_details', array('consoler_username' => $uname ));
    $totalnumber = count($totalpatient);
  //  print_object($totalpatient);
   // print_object($totalnumber);
    $totalactivepatient = $DB->get_records('patient_complete_details',array( 'consoler_username' => $uname , 'status'=> 0));
    $totalactivenumber = count($totalactivepatient);
   // print_object($totalactivenumber);
    $details = 'Patient Details';
    $htmlbody = '';
    $htmlbody .= html_writer::start_div('card',array('style' => 'width: 17rem'));
    $htmlbody .= html_writer::start_div('card-block'); 
    $htmlbody .= html_writer:: tag('h4',$details,array('class'=>'card-title cardtitle'));
    $htmlbody .= html_writer::tag('p', 'This Block Show  the tolal number of patients',array('class' => "card-text"));
    $htmlbody .= html_writer::end_div();

    $list1 = "<li class ='list-group-item cardblockli'>".html_writer::start_span('badge') .$totalactivenumber. html_writer::end_span();"</li>";
    $list2 = "<li class ='list-group-item cardblockli'>".html_writer::tag('p', 'All Active Patient');"</li>";

    $list3 = "<li class ='list-group-item cardblockli'>".html_writer::start_span('badge') .$totalnumber. html_writer::end_span();"</li>";
    $list4 = "<li class ='list-group-item cardblockli'>".html_writer::tag('p', 'Total Number of  Patient',array('class' =>'pmargin' ));"</li>";
    $completelist = $list1.$list2.$list3.$list4;

    $htmlbody .= html_writer::tag('ul', $completelist, array('class' => 'list-group list-group-flush card-block cardblockul'));
    $htmlbody .= html_writer::start_div('card-block');
    $url = new moodle_url('#'); 
    $htmlbody .= html_writer::link($url, 'Add New Patient');
    $htmlbody .= html_writer::end_div();
    $htmlbody .= html_writer::end_div();

    //display the list of active patients
    $details1 = 'My Patient Details';
    $htmlbody .= html_writer::start_div('card',array('style' => 'width: 17rem'));
    $htmlbody .= html_writer::start_div('card-block');
    $htmlbody .= html_writer:: tag('h4',$details1,array('class'=>'card-title cardtitle'));
    $htmlbody .= html_writer::tag('p', 'This block section shows list of active patients',array('class' => "card-text"));                     
    $htmlbody .= html_writer::end_div();

    $htmlbody .= html_writer::start_tag('form', array('method' =>'post','action'=>''));
    $htmlbody .= html_writer::start_div();
    $htmlbody .= html_writer::empty_tag('input', array('type'=>'text', 'name'=>'textvalue', 'value'=>"Search"));
    $htmlbody .= html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'submit', 'value'=>"Find")); 
    $htmlbody .= html_writer::end_div();
    $htmlbody .= html_writer::end_tag('form');

    
    $table = new html_table();
   // $table->attributes['class'] = 'nameoftablecssclass';
    $table->width = '100%';
    $table->head = array('','');    
    $table->align = array('left','centre');
    $table->size = array('150%');

    $textvalue = $_POST['textvalue'];
 //print_object($textvalue);

    if(!empty($textvalue)){

        $session = 'session';
        $cname = $textvalue;
        $pid = $DB->get_record('patient_complete_details', array('patient_id' =>$cname));

        if(!empty($pid)){
        $feedid = $pid->feedback_id;
        $coursedetails = $DB->get_record('feedback',array('id' => $feedid));
        $cid =  $coursedetails->course;
        //print_object($cname);
        $cm=get_coursemodule_from_instance('feedback',$feedid,$cid,false,MUST_EXIST);
        $insid = $cm->id;
        $comid = $pid->completed_id;
       // print_object($comid);
        $patienturl = new moodle_url('/mod/feedback/show_entries.php', array('id'=>$insid,'showcompleted'=>$comid)); 
        //print_object($patienturl);
        $sessionvalue = $DB->get_record('forum_discussions', array('name' =>$cname));
        //print_object($sessionvalue);die();
        $sessionid = $sessionvalue->id;
        $sessionurl = new moodle_url('/mod/forum/discuss.php', array('d'=>$sessionid));

        //print_object($cm);
        $table->data[] = new html_table_row(array(implode(array(html_writer::link($patienturl, $cname),' ',html_writer::link($sessionurl, $session)))));
    }else{
         $htmlbody .= html_writer::tag('p', 'Patient Detail Does Not Exist');
        }
    }else{ 
        foreach ($totalactivepatient as $value) {
        //print_object($value);die();
        $session = 'session';
        $cname = $value->patient_id;
        //print_object($cname);
        $cm=get_coursemodule_from_instance('feedback',$value->feedback_id,$COURSE->id,false,MUST_EXIST);
        $insid = $cm->id;
        $comid = $value->completed_id;
       // print_object($comid);
        $patienturl = new moodle_url('/mod/feedback/show_entries.php', array('id'=>$insid,'showcompleted'=>$comid)); 
        //print_object($patienturl);
        $sessionvalue = $DB->get_record('forum_discussions', array('name' =>$cname));
        //print_object($sessionvalue);die();
        $sessionid = $sessionvalue->id;
        $sessionurl = new moodle_url('/mod/forum/discuss.php', array('d'=>$sessionid));

        //print_object($cm);
        $table->data[] = new html_table_row(array(implode(array(html_writer::link($patienturl, $cname),' ',html_writer::link($sessionurl, $session)))));
        //new html_table_row(array( implode(array($rec->firstname," ",$rec->lastname)), $rec->count));
        }

    }  
   
    

    //print_object($table);die();
    $htmlbody .= html_writer::table($table);
    $htmlbody .= html_writer::end_div();

   return($htmlbody);


}