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
 * Dedication block definition.
 *
 * @package    block
 * @subpackage dedication
 * @copyright  2008 CICEI http://http://www.cicei.com
 * @author     2008 Borja Rubio Reyes
 *             2011 Aday Talavera Hierro (update to Moodle 2.x)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
"<link href='//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css' rel='stylesheet' id='bootstrap-css'>";
"<script src='//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js'></script>";
"<script src='//code.jquery.com/jquery-1.11.1.min.js'></script>";
"<script type='text/javascript' src='patientlist.js'></script>";

class block_patientlist extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_patientlist');
    }

    public function specialization() {
        // Previous block versions didn't have config settings.
        if ($this->config === null) {
            $this->config = new stdClass();
        }
        // Set always show_dedication config settings to avoid errors.
        if (!isset($this->config->show_patientlist)) {
            $this->config->show_patientlist = 0;
        }
    }

    public function get_content() {
        global $OUTPUT, $USER,$DB;

         //	print_object($USER);
        $uname = $USER->username;
        $uid = $USER->id;
        $totalpatient = $DB->get_records('patient_complete_details', array('consoler_username' => $uname ));
        $totalnumber = count($totalpatient);
       // print_object($totalnumber);
       // $totalactivepatient = $DB->get_records_sql('SELECT * FROM {patient_complete_details} WHERE consoler_username = ? AND status = ?', array( $uname , 0 ));
        $totalactivepatient = $DB->get_records('patient_complete_details',array( 'consoler_username' => $uname , 'status'=> 0));
        $totalactivenumber = count($totalactivepatient);
        //print_object($totalactivepatient);die();
       	//print_object($totalactivenumber);die();
        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        // if ($this->config->show_patientlist == 1) {
        //     require_once('patientlist_lib.php');
        //     $mintime = $this->page->course->startdate;
        //     $maxtime = time();
        //     $dm = new block_patientlist_manager($this->page->course, $mintime, $maxtime, $this->config->limit);
        //     $dedicationtime = $dm->get_user_dedication($USER, true);
        //     $this->content->text .= html_writer::tag('p', get_string('patientlist_estimation', 'block_patientlist'));
        //     $this->content->text .= html_writer::tag('p', block_patientlist_utils::format_dedication($dedicationtime));
        // }

        // if (has_capability('block/patientlist:use', context_block::instance($this->instance->id))) {
        //     $this->content->footer .= html_writer::tag('hr', null);
        //     $this->content->footer .= html_writer::tag('p', get_string('access_info', 'block_patientlist'));
        //     $url = new moodle_url('/blocks/patientlist/patientlist.php', array(
        //         'courseid' => $this->page->course->id,
        //         'instanceid' => $this->instance->id,
        //     ));
        //     $this->content->footer .= $OUTPUT->single_button($url, get_string('access_button', 'block_patientlist'), 'get');
       /* // }
        $this->content->text .= html_writer::start_span('badge') .$totalactivenumber. html_writer::end_span().html_writer::tag('p', 'Active Patient Details'); 
       // $this->content->text .= html_writer::tag('p', 'Active Patient Details');
        $this->content->text .= html_writer::start_span('badge') .$totalnumber. html_writer::end_span().html_writer::tag('p', 'All Patient Details'); */
        //$this->content->text .= html_writer::tag('p', 'All Patient Details');
        /*$this->content->text .= html_writer::start_div('container');
        $this->content->text .= html_writer::start_div('row');
        $this->content->text .= html_writer::start_div('col-xs-6 col-md-3');
        $this->content->text .= html_writer::start_div('panel status panel-danger');
        $this->content->text .= html_writer::start_div('panel-heading');
        $this->content->items.= html_writer::tag('hi','panel-title text-center');
        $this->content->text .= html_writer::start_span() .$totalnumber. html_writer::end_span();
        $this->content->text .= html_writer::end_div();
        $this->content->text .= html_writer::start_div('panel-body text-center');
        $this->content->text .= html_writer::tag('p', 'All Patient Details'); 
        $this->content->text .= html_writer::end_div();
        $this->content->text .= html_writer::end_div();style="width: 20rem;
        $this->content->text .= html_writer::end_div();
        $this->content->text .= html_writer::end_div();
        $this->content->text .= html_writer::end_div();*/

        /*$this->content->items.= html_writer::start_span('badge');						
        $this->content->items.= html_writer::tag($totalnumber);
        $this->content->items.=	html_writer::end_span();*/
        //$this->content->text.= html_writer::start_span('badge') .$totalnumber. html_writer::end_span();  
       // $
      //  $this->content->text .= html_writer::tag('p', block_patientlist_utils::format_dedication($dedicationtime));
        $details = 'Patient Details';
        $this->content->text .= html_writer::start_div('card',array('style' => 'width: 17rem'));
        $this->content->text .= html_writer::start_div('card-block'); 
        //$this->content->text .= html_writer::start_span('card-title') .$details. html_writer::end_span();
        $this->content->text .= html_writer:: tag('h4',$details,array('class'=>'card-title cardtitle'));
        $this->content->text .= html_writer::tag('p', 'This Block Show  the tolal number of patients',array('class' => "card-text"));
        //$this->content->text .= html_writer:: tag('h4','This Block Show  the tolal number of patients',array('class'=>'card-text'));
        $this->content->text .= html_writer::end_div();
        $list1 = "<li class ='list-group-item cardblockli'>".html_writer::start_span('badge') .$totalactivenumber. html_writer::end_span();"</li>";
        $list2 = "<li class ='list-group-item cardblockli'>".html_writer::tag('p class='pmargin'', 'All Active Patient');"</li>";

        $list3 = "<li class ='list-group-item cardblockli'>".html_writer::start_span('badge') .$totalnumber. html_writer::end_span();"</li>";
        $list4 = "<li class ='list-group-item cardblockli'>".html_writer::tag('p', 'Total Number of  Patient',array('class' =>'pmargin' ));"</li>";
        $completelist = $list1.$list2.$list3.$list4;
        $this->content->text .= html_writer::tag('ul', $completelist, array('class' => 'list-group list-group-flush card-block cardblockul'));
        $this->content->text .= html_writer::start_div('card-block'); 
        $url = new moodle_url('#');
        $this->content->text .=  html_writer::link($url, 'Add New Patient');   
        $this->content->text .= html_writer::end_div();
        $this->content->text .= html_writer::end_div();

        $this->content->footer .= $OUTPUT->single_button($url,'Add New Patient');
       
        return $this->content;
    }

    public function applicable_formats() {
        return array('course' => true);
    }

}
