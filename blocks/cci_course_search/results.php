<?php

/**
 * *************************************************************************
 * *                       CCI - Course Search                          **
 * *************************************************************************
 * @package     block                                                   **
 * @subpackage  cci coursesearch                                        **
 * @name        CCI Course Search                                       **
 * @copyright   CCI                                                     **
 * @author      CCI                                                     **
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later  **
 * *************************************************************************
 * ************************************************************************ */
require('../../config.php');
require('lib.php');


$courseid = required_param('courseid', PARAM_INT);
$q = required_param('q', PARAM_TEXT);

require_login($courseid, true); //Use course 1 because this has nothing to do with an actual course, just like course 1

global $CFG, $PAGE, $OUTPUT, $DB, $USER;
$context = context_course::instance($courseid);
$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/blocks/cci_course_search/results.php', array('id' => $courseid));
$PAGE->set_title(get_string('pagetitle', 'block_cci_course_search'));
$PAGE->set_heading(get_string('pagetitle', 'block_cci_course_search'));
$PAGE->set_context($context);
$PAGE->requires->css('/blocks/cci_course_search/styles.css');

echo $OUTPUT->header();

//get all mods regardless if it's in the course or not.
$modules = $DB->get_records_sql('SELECT name FROM {modules}');

echo "<div class=\"clearfix\">";
echo "<div class=\"info\">";
echo "<h2>" . get_string('results', 'block_cci_course_search') . " $q</h2>";
$course = $DB->get_record('course', array('id' => $courseid));
$modinfo = get_fast_modinfo($course);

echo "<ul class='block_cci_course_search_result_list'>";
$i = 1;
$res = 1;
$printed = false;
foreach ($modules as $module)
{
    //$functionname = "block_cci_course_search_search_module_" . $module->name;
    $module_result = '';
    //If a specific function exists called
    // if (function_exists($functionname)){
    //     $module_result = call_user_func($functionname, $courseid, $module, $q, $modinfo);
    // }else{
    $module_result = block_cci_course_search_search_module($courseid, $module, $q);
    //}
    if ($module_result != ''){
        $printed = true;
        $tag_result = block_cci_course_search_withtags($courseid, $q);
        if(count($tag_result) > 0){
            $module_result = $module_result.' '.$tag_result;
        }
    }else{
        $res = 0;
    }
    echo $module_result;
    $i++;
}
if (!$printed){   
    if($res == 0){
        echo '<i>' . get_string('no_result', 'block_cci_course_search') . '</i>';
    }
    $tag_result = block_cci_course_search_withtags($courseid, $q);
    if(count($tag_result) > 0){
        echo $tag_result;
    }else{
        echo '<i>' . get_string('no_result', 'block_cci_course_search') . '</i>';
    }
}
echo "</ul>";
echo "<hr/>";
echo "<h3>" . get_string('new_search', 'block_cci_course_search') . "</h3>";
echo "<div class=\"searchform\">";
echo "<form style='display:inline;' name='cci_course_search_form' id='block_cci_course_search_form' action='$CFG->wwwroot/blocks/cci_course_search/results.php' method='post'>";
echo "<fieldset class=\"invisiblefieldset\">";
echo get_string('searchfor', 'block_cci_course_search') . "<br>";
echo "<input type='hidden' name='courseid' id='courseid' value='$course->id'/>";
echo "<input type='text' name='q' id='q' value=''/>";
echo "<br><input type='submit' id='searchform_button' value='" . get_string('submit', 'block_cci_course_search') . "'>";
echo "</fieldset>";
echo "</form>";
echo "</div>";

echo "<br><button onclick='window.location = \"" . $CFG->wwwroot . "/course/view.php?id=" . $courseid . "\"'>" . get_string('return_course', 'block_cci_course_search') . "</button>";
echo "</div>";
echo "</div>";
echo $OUTPUT->footer();