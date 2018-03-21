<?php
// This file keeps track of upgrades to
// the assignment module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

defined('MOODLE_INTERNAL') || die();

function xmldb_local_patientrecord_upgrade($oldversion) {
    global $CFG,$DB;

    $dbman = $DB->get_manager();
         if ($oldversion < 2017070127) {

        // Define field patient_id to be added to patient_complete_details.
        $table = new xmldb_table('patient_complete_details');
        $field1 = new xmldb_field('consoler_username', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null, 'completed_id');
        $field2 = new xmldb_field('paadharnumber', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null, 'consoler_username');
        $field3 = new xmldb_field('pfname', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null, 'paadharnumber');
        $field4 = new xmldb_field('plname', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null, 'pfname');
        $field5 = new xmldb_field('pcity', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null, 'plname');
        $field6 = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'pcity');
        $field7 = new xmldb_field('status', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, null, 'patient_id');
        

        // Conditionally launch add field patient_id.
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }
        if (!$dbman->field_exists($table, $field3)) {
            $dbman->add_field($table, $field3);
        }
        if (!$dbman->field_exists($table, $field4)) {
            $dbman->add_field($table, $field4);
        }
         if (!$dbman->field_exists($table, $field5)) {
            $dbman->add_field($table, $field5);
        }
         if (!$dbman->field_exists($table, $field6)) {
            $dbman->add_field($table, $field6);
        }
         if (!$dbman->field_exists($table, $field7)) {
            $dbman->add_field($table, $field7);
        }


        // Patientrecord savepoint reached.
        upgrade_plugin_savepoint(true, 2017070127,'local', 'patientrecord');
    }



    return true;
}
