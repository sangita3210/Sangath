<?php

defined('MOODLE_INTERNAL') || die;

// Required for non-standard context constants definition.
//require_once($CFG->dirroot.'/local/metadata/lib.php');

if ($hassiteconfig) {

    $moderator = get_admin();
    $site = get_site();

    $settings = new admin_settingpage('local_patientrecord', get_string('usersettings','local_patientrecord'));
    $ADMIN->add('localplugins', $settings);

    $availablefields = new moodle_url('/local/patientrecord/index.php');
   

    $name = 'local_patientrecord/message_user_enabled';
    $title = get_string('message_user_enabled', 'local_patientrecord'); 
    $description = get_string('message_user_enabled_desc', 'local_patientrecord', $availablefields->out());
    $setting = new admin_setting_configcheckbox($name, $title, $description, 1);
    $settings->add($setting);

   

    $name = 'local_patientrecord/message_user_subject';
    $default = get_string('default_user_email_subject', 'local_patientrecord', $site->fullname);
    $title = get_string('message_user_subject', 'local_patientrecord');
    $description = get_string('message_user_subject_desc', 'local_patientrecord');
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    
    $name = 'local_patientrecord/message_user';
    $title = get_string('message_user', 'local_patientrecord');
    $description = get_string('message_user_desc', 'local_patientrecord');
    $setting = new admin_setting_confightmleditor($name, $title, $description, '');
    $settings->add($setting);

    //self registration
    
    $name = 'local_patientrecord/sender_email';
    $title = get_string('sender_email', 'local_patientrecord');
    $description = get_string('sender_email_desc', 'local_patientrecord');
    $setting = new admin_setting_configtext($name, $title, $description, $moderator->email);
    $settings->add($setting);

    $name = 'local_patientrecord/sender_firstname';
    $title = get_string('sender_firstname', 'local_patientrecord');
    $description = get_string('sender_firstname_desc', 'local_patientrecord');
    $setting = new admin_setting_configtext($name, $title, $description, $moderator->firstname);
    $settings->add($setting);

    $name = 'local_patientrecord/sender_lastname';
    $title = get_string('sender_lastname', 'local_patientrecord');
    $description = get_string('sender_lastname_desc', 'local_patientrecord');
    $setting = new admin_setting_configtext($name, $title, $description, $moderator->lastname);
    $settings->add($setting);

   } 