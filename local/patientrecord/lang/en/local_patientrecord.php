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
 * Metadata user context plugin language file.
 *
 * @package local_metadata
 * @subpackage metadatacontext_user
 * @author Mike Churchward <mike.churchward@poetgroup.org>
 * @copyright 2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['autoemailtitle'] = 'User Automail';
$string['metadatadisabled'] = 'Metadata for users is currently disabled.';
$string['metadataenabled'] = 'Use metadata for users';
$string['usersettings'] = 'User Quiz Settings';

$string['pluginname'] = 'Insert Patient Records';
$string['fieldname'] = 'Fieldname';
$string['yourvalue'] = 'Your Value';
$string['customprofilefields'] = 'Custom profile fields';
$string['configtitle'] = 'Moodle Automail';
$string['message_user_enabled'] = 'Enable user email';
$string['message_user_enabled_desc'] = 'This tickbox enables the sending of welcome email to new users<br><br>Visit <a href="{$a}">this page to see the list of available fields</a>';
$string['message_user_subject'] = 'Email subject';
$string['message_user_self_subject'] = 'Email subject';
$string['message_user_subject_desc'] = 'This will be the subject of the email send to the user. Use [[fullname]] as a tag, this will be replaced with the users Firstname Lastname.';
$string['message_user_self_subject_desc'] = 'This will be the subject of the email send to the user. Use [[fullname]] as a tag, this will be replaced with the users Firstname Lastname.';
$string['message_user'] = 'User message';
$string['message_user_self'] = 'User message';
$string['message_user_desc'] = 'Email send to new users';
$string['message_user_self_desc'] = 'Email send to new self register users';
$string['sender_email'] = 'Sender email address';
$string['sender_email_desc'] = 'When new users log in this email address is used to send a notification message, users will be able to see this email address';
$string['sender_firstname'] = 'Welcome message sender firstname';
$string['sender_firstname_desc'] = 'First name used when sending mail to users.';
$string['sender_lastname'] = 'Moderator lastname';
$string['sender_lastname_desc'] = 'Last name used when sending mail to users.';
$string['globalhelp'] = 'This plugin for Moodle sends a configurable welcome message to new users.
<br><br>
The plugin uses the event system in Moodle and will be triggerd when a new
user is created, no matter if this was a manually created account or an
account created using self registration.<br>
<br>
The tables on this page show the available profile fields that can be used in the message template on this plugin\'s configuration page.
The values shown in this table are YOUR profile field values, they will be replaced by the recipients values when the welcome email is send.';
$string['configure'] = 'Configure this plugin';
$string['welcomefields'] = 'Additional template fields';

$string['defaultprofilefields'] = 'Default profile fields';
$string['default_user_email_subject'] = 'Hello [[fullname]] Welcome to [[sitename]]';
$string['default_user_email'] = '

<html>
<body>

<h3>Dear [[fullname]]</h3>
<p>You have successfully submitted the quiz,[[ Quiz link]]</p>
<h4>Thank You.</h4>
<b>Customer Suppor</b>
	<b> e-learning</b>

</body>
</html>
';

$string['resetpass'] = 'Reset your password here';