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
 * prints the form so the user can fill out the feedback
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_feedback
 */
//require_once($CFG->dirroot.'/local/patientrecord/lib.php');

require_once("../../config.php");
require_once("lib.php");

$comid = required_param('id', PARAM_INT);
$cid = optional_param('courseid', '', PARAM_INT);
$type = optional_param('type', '', PARAM_INT);
$typeval = optional_param('typeval', '', PARAM_RAW);


 //print_object($typeval);
  $stringvalue = explode(',',$typeval);
  $sval01 =  $stringvalue[0];
  $sval02 =  $stringvalue[1];
  $sval03 =  $stringvalue[2];


  // print_object($stringvalue);
  // print_object($sval01);

  // print_object($sval02);
  // print_object($sval03);

 require_login($cid, true); //Use course 1 because this has nothing to do with an actual course, just like course 1

 global $CFG, $PAGE, $OUTPUT, $DB, $USER;
 $context = context_course::instance($cid);
 //$context = context_module::instance($cmid);
 $PAGE->set_pagelayout('incourse');
 $PAGE->set_url('/mod/feedback/scoretable.php', array('id' => $comid));
 $PAGE->set_title('Patient Score Details');
 $PAGE->set_heading('Table Shown ');
 $PAGE->set_context($context);
// // $PAGE->requires->css('/blocks/cci_course_search/styles.css');
$previewnode = $PAGE->navbar->add('Download');

 echo $OUTPUT->header();
 


//$htmlbody = '';

//$htmlbody .= html_writer::table($table);

/*$table = new html_table();
$table->head = array('Lastname', 'Firstname', 'ID Number');
$table->data[] = array('first row of data goes here ...');
$table->data[] = array( '... second row of data goes here ...');
echo html_writer::table($table);
//echo $OUTPUT-> $htmlbody;*/
//if($sval01 == 1){
	$patientdetails = $DB->get_record('patient_complete_details',array('completed_id' =>$comid));
	$patientid = $patientdetails->patient_id;
	echo $OUTPUT->heading('SDQ Score For Patient :'.$patientid, 3);
	$emolable =array('SDQ-ITEM3-0-1-2','SDQ-ITEM8-0-1-2','SDQ-ITEM13-0-1-2');
	$conlable =array('SDQ-ITEM5-0-1-2','SDQ-ITEM7-2-1-0','SDQ-ITEM12-0-1-2');
	$hyplable =array('SDQ-ITEM2-0-1-2','SDQ-ITEM10-0-1-2','SDQ-ITEM15-0-1-2');
	$peerlable =array('SDQ-ITEM6-0-1-2','SDQ-ITEM11-2-1-0','SDQ-ITEM14-2-1-0');
	$prolable =array('SDQ-ITEM1-0-1-2','SDQ-ITEM4-0-1-2','SDQ-ITEM9-0-1-2');	
	$valuereturn1 = score_calculation($comid,$emolable);
	$valuereturn2 = score_calculation($comid,$conlable);
	$valuereturn3 = score_calculation($comid,$hyplable);
	$valuereturn4 = score_calculation($comid,$peerlable);
	$valuereturn5 = score_calculation($comid,$prolable);
	$valuereturn6 = score_calculation($comid,$prolable);
	echo $valuereturn1;
	echo $valuereturn2;
	echo $valuereturn3;
	echo $valuereturn4;
	echo $valuereturn5;
	echo $OUTPUT->heading('SDQ Score Calculation Table ', 3);
	echo $valuereturn6;
	

//	 }elseif($sval02 == 2){
 	//echo "xxxxx";
	echo $OUTPUT->heading('Impact01 Score For Patient:'.$patientid, 3);
	$emolable =array('IMP1-ITEM3-0-1-2','IMP1-ITEM8-0-1-2','IMP1-ITEM13-0-1-2');
	$conlable =array('IMP1-ITEM5-0-1-2','IMP1-ITEM7-2-1-0','IMP1-ITEM12-0-1-2');
	$hyplable =array('IMP1-ITEM2-0-1-2','IMP1-ITEM10-0-1-2','IMP1-ITEM15-0-1-2');
	$peerlable =array('IMP1-ITEM6-0-1-2','IMP1-ITEM11-2-1-0','IMP1-ITEM14-2-1-0');
	$prolable =array('IMP1-ITEM1-0-1-2','IMP1-ITEM4-0-1-2','IMP1-ITEM9-0-1-2');	
	$valuereturn1 = score_calculation($comid,$emolable);
	$valuereturn2 = score_calculation($comid,$conlable);
	$valuereturn3 = score_calculation($comid,$hyplable);
	$valuereturn4 = score_calculation($comid,$peerlable);
	$valuereturn5 = score_calculation($comid,$prolable);
	$valuereturn6 = score_calculation($comid,$prolable);
	echo $valuereturn1;
	echo $valuereturn2;
	echo $valuereturn3;
	echo $valuereturn4;
	echo $valuereturn5;
	echo $OUTPUT->heading('Impact01 Score Calculation Table ', 3);
	echo $valuereturn6;

// }elseif($sval03 == 3){

	echo $OUTPUT->heading('Impact02 Score For Patient:'.$patientid, 3);
	$emolable =array('IMP2-ITEM3-0-1-2','IMP2-ITEM8-0-1-2','IMP2-ITEM13-0-1-2');
	$conlable =array('IMP2-ITEM5-0-1-2','IMP2-ITEM7-2-1-0','IMP2-ITEM12-0-1-2');
	$hyplable =array('IMP2-ITEM2-0-1-2','IMP2-ITEM10-0-1-2','IMP2-ITEM15-0-1-2');
	$peerlable =array('IMP2-ITEM6-0-1-2','IMP2-ITEM11-2-1-0','IMP2-ITEM14-2-1-0');
	$prolable =array('IMP2-ITEM1-0-1-2','IMP2-ITEM4-0-1-2','IMP2-ITEM9-0-1-2');	
	$valuereturn1 = score_calculation($comid,$emolable);
	$valuereturn2 = score_calculation($comid,$conlable);
	$valuereturn3 = score_calculation($comid,$hyplable);
	$valuereturn4 = score_calculation($comid,$peerlable);
	$valuereturn5 = score_calculation($comid,$prolable);
	$valuereturn6 = score_calculation($comid,$prolable);
	echo $valuereturn1;
	echo $valuereturn2;
	echo $valuereturn3;
	echo $valuereturn4;
	echo $valuereturn5;
	echo $OUTPUT->heading('Impact02 Score Calculation Table ', 3);
	echo $valuereturn6;
//}


// echo $valuereturn5['table1'];
// echo $valuereturn5['table2'];

echo $OUTPUT->footer();

