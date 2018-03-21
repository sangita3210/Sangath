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
defined('MOODLE_INTERNAL') || die();

/**
 * Search in a module content in the common fields (name, intro, content)
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param stdClass $module The module object
 * @param string $q The string searched
 * @return string Return the result in HTML
 */
function block_cci_course_search_search_module($courseid, $module, $q)
{
    global $CFG, $DB, $OUTPUT;

    $ret = '';
    $sqlWere = 'course=? AND (false';
    $sqlParams = array($courseid);
    //At least one search field is needed
    $onefield = false;
    $tagfields = false;
    //The DBman will be use to check if table and field exists
    $dbman = $DB->get_manager();
    //print_object($q);
    //print_object($module);
    //Check if the table exists
    // if ($dbman->table_exists($module->name))
    // {
        //Check if the fields exists
        if ($dbman->field_exists($module->name, 'name'))
        {
            $sqlWere .= " OR name LIKE ?";
            $sqlParams[] = "%$q%";
            $onefield = true;
            $tagfields = true;
        }
        if ($dbman->field_exists($module->name, 'intro'))
        {
            $sqlWere .= " OR intro LIKE ?";
            $sqlParams[] = "%$q%";
            $onefield = true;
            $tagfields = true;
        }
        if ($dbman->field_exists($module->name, 'content'))
        {
            $sqlWere .= " OR content LIKE ?";
            $sqlParams[] = "%$q%";
            $onefield = true;
            $tagfields = true;
        }


        //Do the search
        if ($onefield)
        {
             $sql = "SELECT * FROM {" . $module->name . "} WHERE $sqlWere)";
             $results = $DB->get_records_sql($sql, $sqlParams);
            $modid = $DB->get_record('modules', array('name' => $module->name));
            foreach ($results as $result)
            {
                $this_course_mod = $DB->get_record('course_modules', array('course' => $courseid, 'module' => $modid->id, 'instance' => $result->id));
                $ret .= "<li><a href='$CFG->wwwroot/mod/$module->name/view.php?id=$this_course_mod->id'><img src='" . $OUTPUT->pix_url('icon', $module->name) . "' alt='$module->name -'/>&nbsp;$result->name</a></li>";
            }
        }
    return $ret;
}

/**
 * Search in a module content in the common fields (name, intro, content)
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param string $q The string searched
 * @return string Return the result in HTML
 */
function block_cci_course_search_search_section($courseid, $q)
{
    global $CFG, $DB, $OUTPUT;

    $ret = '';
    $sqlParams = array($courseid, "%$q%", "%$q%");

    $sql = "SELECT * FROM {course_sections} WHERE course=? AND (summary LIKE ? OR name LIKE ?)";

    //get sql
    $results = $DB->get_records_sql($sql, $sqlParams);

    foreach ($results as $result)
    {
        $link = "<li><a href='$CFG->wwwroot/course/view.php?id=$courseid#section-$result->section'><img src='" . $OUTPUT->pix_url('icon', 'label') . "' alt='section - '/>&nbsp;$result->name</a></li>";
        $ret .= $link;
    }
    return $ret;
}

/**
 * Search in a module content in the common fields (name, intro, content)
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param stdClass $module The module object
 * @param string $q The string searched
 * @param stdClass $modinfo The modinfo object
 * @return string Return the result in HTML
 */
function block_cci_course_search_search_module_label($courseid, $module, $q, $modinfo)
{
    global $CFG, $DB, $OUTPUT;

    $ret = '';
    $sqlParams = array($courseid, "%$q%", "%$q%");

    $sql = "SELECT * FROM {label} WHERE course=? AND (intro LIKE ? OR name LIKE ?)";
    //get sql

    $results = $DB->get_records_sql($sql, $sqlParams);
    //To create the link we need more info
    //find modid
    $modid = $DB->get_record('modules', array('name' => 'label'));

    //Get All sections
    $sections = $modinfo->get_sections();
           
    foreach ($results as $result)
    {
        $sectionfounded = null;
        $this_course_mod = $DB->get_record('course_modules', array('course' => $courseid, 'module' => $modid->id, 'instance' => $result->id));

        foreach ($sections as $sectionnum => $section)
        {
            foreach ($section as $mod)
            {
                //If mod id == the course mod id
                if ($mod == $this_course_mod->id)
                {
                    //now find the name of the section
                    $sectionfounded = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $sectionnum));
                    break 2;
                }
            }
        }

        if ($sectionfounded != null)
        {
            $ret .= "<li><a href='$CFG->wwwroot/course/view.php?id=$courseid#section-$sectionfounded->section'><img src='" . $OUTPUT->pix_url('icon', 'label') . "' alt='label - '/>&nbsp;$result->name</a></li>";
        }

    }
    return $ret;
}

/**
 * Search in a module tab content
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param stdClass $module The module object
 * @param string $q The string searched
 * @param stdClass $modinfo The modinfo object
 * @return string Return the result in HTML
 */
function block_cci_course_search_search_module_tab($courseid, $module, $q, $modinfo)
{
    global $CFG, $DB, $OUTPUT;

    $ret = '';
    $sqlParams = array($courseid, "%$q%", "%$q%", "%$q%", "%$q%");

    $sql = "SELECT {tab_content}.id as tabcontentid, {tab}.id as id,{tab}.name, {tab}.intro, {tab}.course, {tab_content}.tabname, {tab_content}.tabcontent
                    FROM {tab_content} 
                        INNER JOIN {tab} ON {tab_content}.tabid = {tab}.id AND {tab}.course = ?
                    WHERE {tab}.name LIKE ? OR {tab}.intro LIKE ?
                          OR {tab_content}.tabname LIKE ? OR {tab_content}.tabcontent LIKE ?";
    //get sql
    $results = $DB->get_records_sql($sql, $sqlParams);
    //To create the link we need more info
    //find modid
    $modid = $DB->get_record('modules', array('name' => 'tab'));
    $c = 1;
    foreach ($results as $result)
    {
        $this_course_mod = $DB->get_record('course_modules', array('course' => $courseid, 'module' => $modid->id, 'instance' => $result->id));
        $ret .= "<li><a href='$CFG->wwwroot/mod/tab/view.php?id=$this_course_mod->id'><img src='" . $OUTPUT->pix_url('icon', 'tab') . "' alt=''/>&nbsp;$result->name</a></li>";
        $c++;
    }

    return $ret;
}

/**
 * Search in a module content in the common fields (tags)
 * @global stdClass $CFG
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $courseid The course ID
 * @param string $q The string searched
 * @return string Return the result in HTML
 */
function block_cci_course_search_withtags($courseid, $q)
{
    global $CFG, $DB, $OUTPUT;
    $ret = '';
    $flag = 0;
    $cms = array();
    $tagsinmods = array();
    $tagsall = array();
    $modsall = array();
    $tagmodules = array();
    $result_course_mods = array();
    $sql2 = "SELECT * FROM mdl_course_modules WHERE course =".$courseid;
    $result_course_mods = $DB->get_records_sql($sql2);
    foreach($result_course_mods as $course_mod){
        $tagobj = $DB->get_records('tag_instance',array('itemid'=>$course_mod->id,'itemtype'=>'course_modules'),'id,itemid,tagid');
        foreach($tagobj as $tid => $tags){
            $tagnames = $DB->get_record('tag',array('id'=>$tags->tagid),'id,name');
            $tagsall[$tagnames->id] = $tagnames->name;
        }
    }
    foreach ($tagsall as $tagkey => $tagvalue) {
        //$check = array_search(strtolower($q), $tagsall);
        //updated for tags on 08022017
        if (strchr(strtolower($tagvalue),strtolower($q))) {
            $flag =1;
            $tagmodule = $DB->get_records('tag_instance',array('tagid'=>$tagkey,'itemtype'=>'course_modules'),'id,itemid');
            foreach($tagmodule as $tagm){
                $tagmodules[$tagm->id] = $tagm->itemid;
            }
        }
    }
    if($flag == 1){
        //updated for tags on 08022017
        $ret .= "<h3><i>Result(s) based on tag search <bolder>'".$q."'</bolder></i></h3>";
    
        foreach($tagmodules as $tagmods){
            $this_course_mod = $DB->get_records('course_modules',array('id'=>$tagmods,'course'=>$courseid),'id');
            foreach ($this_course_mod as $key3 => $value3) {
                $cms[$key3] = $value3;
            }
        }
        foreach ($cms as $key=> $mods) {
           
            $mod_type = $DB->get_record('modules',array('id'=>$mods->module),'name');
            $modtable = "'".$mod_type->name."'";
            $mod_name = $DB->get_record($mod_type->name,array('id'=>$mods->instance));
            $ret .= "<li><a href='$CFG->wwwroot/mod/$mod_type->name/view.php?id=$key'><img src='" . $OUTPUT->pix_url('icon', $mod_type->name) . "' alt='$mod_type->name -'/>&nbsp;$mod_name->name</a></li>";
        }
    }
    return $ret;
}