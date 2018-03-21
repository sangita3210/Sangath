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
 * Strings for component 'patientform', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package mod_patientform
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['add_item'] = 'Add question';
$string['add_pagebreak'] = 'Add a page break';
$string['adjustment'] = 'Adjustment';
$string['after_submit'] = 'After submission';
$string['allowfullanonymous'] = 'Allow full anonymous';
$string['analysis'] = 'Analysis';
$string['anonymous'] = 'Anonymous';
$string['anonymous_edit'] = 'Record user names';
$string['anonymous_entries'] = 'Anonymous entries ({$a})';
$string['anonymous_user'] = 'Anonymous user';
$string['answerquestions'] = 'Answer the questions';
$string['append_new_items'] = 'Append new items';
$string['autonumbering'] = 'Auto number questions';
$string['autonumbering_help'] = 'Enables or disables automated numbers for each question';
$string['average'] = 'Average';
$string['bold'] = 'Bold';
$string['calendarend'] = '{$a} closes';
$string['calendarstart'] = '{$a} opens';
$string['cannotaccess'] = 'You can only access this Patientform from a course';
$string['cannotsavetempl'] = 'saving templates is not allowed';
$string['captcha'] = 'Captcha';
$string['captchanotset'] = 'Captcha hasn\'t been set.';
$string['closebeforeopen'] = 'You have specified an end date before the start date.';
$string['completed_patientforms'] = 'submitted Answer';
$string['complete_the_form'] = 'Answer the questions...';
$string['completed'] = 'Completed';
$string['completedon'] = 'Completed on {$a}';
$string['completionsubmit'] = 'View as completed if the Patientform is submitted';
$string['configallowfullanonymous'] = 'If set to \'yes\', users can complete a Patientform activity on the front page without being required to log in.';
$string['confirmdeleteentry'] = 'Are you sure you want to delete this entry?';
$string['confirmdeleteitem'] = 'Are you sure you want to delete this element?';
$string['confirmdeletetemplate'] = 'Are you sure you want to delete this template?';
$string['confirmusetemplate'] = 'Are you sure you want to use this template?';
$string['continue_the_form'] = 'Continue answering the questions...';
$string['count_of_nums'] = 'Count of numbers';
$string['courseid'] = 'courseid';
$string['creating_templates'] = 'Save these questions as a new template';
$string['delete_entry'] = 'Delete entry';
$string['delete_item'] = 'Delete question';
$string['delete_old_items'] = 'Delete old items';
$string['delete_pagebreak'] = 'Delete page break';
$string['delete_template'] = 'Delete template';
$string['delete_templates'] = 'Delete template...';
$string['depending'] = 'Dependencies';
$string['depending_help'] = 'It is possible to show an item depending on the value of another item.<br />
<strong>Here is an example.</strong><br />
<ul>
<li>First, create an item on which another item will depend on.</li>
<li>Next, add a pagebreak.</li>
<li>Then add the items dependant on the value of the item created before. Choose the item from the list labelled "Dependence item" and write the required value in the textbox labelled "Dependence value".</li>
</ul>
<strong>The item structure should look like this.</strong>
<ol>
<li>Item Q: Do you have a car? A: yes/no</li>
<li>Pagebreak</li>
<li>Item Q: What colour is your car?<br />
(this item depends on item 1 with value = yes)</li>
<li>Item Q: Why don\'t you have a car?<br />
(this item depends on item 1 with value = no)</li>
<li> ... other items</li>
</ol>';
$string['dependitem'] = 'Dependence item';
$string['dependvalue'] = 'Dependence value';
$string['description'] = 'Description';
$string['do_not_analyse_empty_submits'] = 'Do not analyse empty submits';
$string['dropdown'] = 'Multiple choice - single answer allowed (dropdownlist)';
$string['dropdownlist'] = 'Multiple choice - single answer (dropdown)';
$string['dropdownrated'] = 'Dropdownlist (rated)';
$string['dropdown_values'] = 'Answers';
$string['drop_patientform'] = 'Remove from this course';
$string['edit_item'] = 'Edit question';
$string['edit_items'] = 'Edit questions';
$string['email_notification'] = 'Enable notification of submissions';
$string['email_notification_help'] = 'If enabled, teachers will receive notification of patientform submissions.';
$string['emailteachermail'] = '{$a->username} has completed patientform activity : \'{$a->patientform}\'

You can view it here:

{$a->url}';
$string['emailteachermailhtml'] = '<p>{$a->username} has completed patientform activity : <i>\'{$a->patientform}\'</i>.</p>
<p>It is <a href="{$a->url}">available on the site</a>.</p>';
$string['entries_saved'] = 'Your answers have been saved. Thank you.';
$string['export_questions'] = 'Export questions';
$string['export_to_excel'] = 'Export to Excel';
$string['eventresponsedeleted'] = 'Response deleted';
$string['eventresponsesubmitted'] = 'Response submitted';
$string['patientformcompleted'] = '{$a->username} completed {$a->patientformname}';
$string['patientform:addinstance'] = 'Add a new Patient Form';
$string['patientformclose'] = 'Allow answers to';
$string['patientform:complete'] = 'Complete a Patient Form';
$string['patientform:createprivatetemplate'] = 'Create private template';
$string['patientform:createpublictemplate'] = 'Create public template';
$string['patientform:deletesubmissions'] = 'Delete completed submissions';
$string['patientform:deletetemplate'] = 'Delete template';
$string['patientform:edititems'] = 'Edit items';
$string['patientform_is_not_for_anonymous'] = 'Patient Form is not for anonymous';
$string['patientform_is_not_open'] = 'The Patient Form is not open';
$string['patientform:mapcourse'] = 'Map courses to global patientforms';
$string['patientformopen'] = 'Allow answers from';
$string['patientform:receivemail'] = 'Receive email notification';
$string['patientform:view'] = 'View a Patient Form';
$string['patientform:viewanalysepage'] = 'View the analysis page after submit';
$string['patientform:viewreports'] = 'View reports';
$string['file'] = 'File';
$string['filter_by_course'] = 'Filter by course';
$string['handling_error'] = 'Error occurred in Patient Form module action handling';
$string['hide_no_select_option'] = 'Hide the "Not selected" option';
$string['horizontal'] = 'horizontal';
$string['check'] = 'Multiple choice - multiple answers';
$string['checkbox'] = 'Multiple choice - multiple answers allowed (check boxes)';
$string['check_values'] = 'Possible responses';
$string['choosefile'] = 'Choose a file';
$string['chosen_patientform_response'] = 'Chosen Patient Form response';
$string['downloadresponseas'] = 'Download all responses as:';
$string['importfromthisfile'] = 'Import from this file';
$string['import_questions'] = 'Import questions';
$string['import_successfully'] = 'Import successfully';
$string['indicator:cognitivedepth'] = 'patientform cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in a patientform activity.';
$string['indicator:socialbreadth'] = 'patientform social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a patientform activity.';
$string['info'] = 'Information';
$string['infotype'] = 'Information type';
$string['insufficient_responses_for_this_group'] = 'There are insufficient responses for this group';
$string['insufficient_responses'] = 'insufficient responses';
$string['insufficient_responses_help'] = 'For the patientform to be anonymous, there must be at least 2 responses.';
$string['item_label'] = 'Label';
$string['item_name'] = 'Question';
$string['label'] = 'Label';
$string['labelcontents'] = 'Contents';
$string['mapcourseinfo'] = 'This is a site-wide patientform that is available to all courses using the patientform block. You can however limit the courses to which it will appear by mapping them. Search the course and map it to this patientform.';
$string['mapcoursenone'] = 'No courses mapped. patientform available to all courses';
$string['mapcourse'] = 'Map patientform to courses';
$string['mapcourse_help'] = 'By default, patientform forms created on your homepage are available site-wide
and will appear in all courses using the patientform block. You can force the patientform form to appear by making it a sticky block or limit the courses in which a patientform form will appear by mapping it to specific courses.';
$string['mapcourses'] = 'Map patientform to courses';
$string['mappedcourses'] = 'Mapped courses';
$string['mappingchanged'] = 'Course mapping has been changed';
$string['minimal'] = 'minimum';
$string['maximal'] = 'maximum';
$string['messageprovider:message'] = 'patientform reminder';
$string['messageprovider:submission'] = 'patientform notifications';
$string['mode'] = 'Mode';
$string['modulename'] = 'patientform';
$string['modulename_help'] = 'The patientform activity module enables a teacher to create a custom survey for collecting patientform from participants using a variety of question types including multiple choice, yes/no or text input.

patientform responses may be anonymous if desired, and results may be shown to all participants or restricted to teachers only. Any patientform activities on the site front page may also be completed by non-logged-in users.

patientform activities may be used

* For course evaluations, helping improve the content for later participants
* To enable participants to sign up for course modules, events etc.
* For guest surveys of course choices, school policies etc.
* For anti-bullying surveys in which students can report incidents anonymously';
$string['modulename_link'] = 'mod/patientform/view';
$string['modulenameplural'] = 'Patientform';
$string['move_item'] = 'Move this question';
$string['multichoice'] = 'Multiple choice';
$string['multichoicerated'] = 'Multiple choice (rated)';
$string['multichoicetype'] = 'Multiple choice type';
$string['multichoice_values'] = 'Multiple choice values';
$string['multiplesubmit'] = 'Allow multiple submissions';
$string['multiplesubmit_help'] = 'If enabled for anonymous surveys, users can submit patientform an unlimited number of times.';
$string['name'] = 'Name';
$string['name_required'] = 'Name required';
$string['next_page'] = 'Next page';
$string['no_handler'] = 'No action handler exists for';
$string['no_itemlabel'] = 'No label';
$string['no_itemname'] = 'No itemname';
$string['no_items_available_yet'] = 'No questions have been set up yet';
$string['non_anonymous'] = 'User\'s name will be logged and shown with answers';
$string['non_anonymous_entries'] = 'Non anonymous entries ({$a})';
$string['non_respondents_students'] = 'Non respondents students ({$a})';
$string['not_completed_yet'] = 'Not completed yet';
$string['not_started'] = 'Not started';
$string['no_templates_available_yet'] = 'No templates available yet';
$string['not_selected'] = 'Not selected';
$string['numberoutofrange'] = 'Number out of range';
$string['numeric'] = 'Numeric answer';
$string['numeric_range_from'] = 'Range from';
$string['numeric_range_to'] = 'Range to';
$string['of'] = 'of';
$string['oldvaluespreserved'] = 'All old questions and the assigned values will be preserved';
$string['oldvalueswillbedeleted'] = 'Current questions and all responses will be deleted.';
$string['only_one_captcha_allowed'] = 'Only one captcha is allowed in a patientform';
$string['openafterclose'] = 'You have specified an open date after the close date';
$string['overview'] = 'Overview';
$string['page'] = 'Page';
$string['page-mod-patientform-x'] = 'Any patientform module page';
$string['page_after_submit'] = 'Completion message';
$string['pagebreak'] = 'Page break';
$string['pluginadministration'] = 'Patientform administration';
$string['pluginname'] = 'Patientform';
$string['position'] = 'Position';
$string['previous_page'] = 'Previous page';
$string['public'] = 'Public';
$string['question'] = 'Question';
$string['questionandsubmission'] = 'Question and submission settings';
$string['questions'] = 'Questions';
$string['questionslimited'] = 'Showing only {$a} first questions, view individual answers or download table data to view all.';
$string['radio'] = 'Multiple choice - single answer';
$string['radio_values'] = 'Responses';
$string['ready_patientforms'] = 'Ready patientforms';
$string['required'] = 'Required';
$string['resetting_data'] = 'Reset patientform responses';
$string['resetting_patientforms'] = 'Resetting patientforms';
$string['response_nr'] = 'Response number';
$string['responses'] = 'Responses';
$string['responsetime'] = 'Responsestime';
$string['save_as_new_item'] = 'Save as new question';
$string['save_as_new_template'] = 'Save as new template';
$string['save_entries'] = 'Submit your answers';
$string['save_item'] = 'Save question';
$string['saving_failed'] = 'Saving failed';
$string['search:activity'] = 'Patientform - activity information';
$string['search_course'] = 'Search course';
$string['searchcourses'] = 'Search courses';
$string['searchcourses_help'] = 'Search for the code or name of the course(s) that you wish to associate with this patientform.';
$string['selected_dump'] = 'Selected indexes of $SESSION variable are dumped below:';
$string['send'] = 'send';
$string['send_message'] = 'send message';
$string['show_all'] = 'Show all';
$string['show_analysepage_after_submit'] = 'Show analysis page';
$string['show_entries'] = 'Show responses';
$string['show_entry'] = 'Show response';
$string['show_nonrespondents'] = 'Show non-respondents';
$string['site_after_submit'] = 'Site after submit';
$string['sort_by_course'] = 'Sort by course';
$string['started'] = 'Started';
$string['startedon'] = 'Started on {$a}';
$string['subject'] = 'Subject';
$string['switch_item_to_not_required'] = 'Set as not required';
$string['switch_item_to_required'] = 'Set as required';
$string['template'] = 'Template';
$string['templates'] = 'Templates';
$string['template_deleted'] = 'Template deleted';
$string['template_saved'] = 'Template saved';
$string['textarea'] = 'Longer text answer';
$string['textarea_height'] = 'Number of lines';
$string['textarea_width'] = 'Width';
$string['textfield'] = 'Short text answer';
$string['textfield_maxlength'] = 'Maximum characters accepted';
$string['textfield_size'] = 'Textfield width';
$string['there_are_no_settings_for_recaptcha'] = 'There are no settings for captcha';
$string['this_patientform_is_already_submitted'] = 'You\'ve already completed this activity.';
$string['typemissing'] = 'missing value "type"';
$string['update_item'] = 'Save changes to question';
$string['url_for_continue'] = 'Link to next activity';
$string['url_for_continue_help'] = 'After submitting the patientform, a continue button is displayed, which links to the course page. Alternatively, it may link to the next activity if the URL of the activity is entered here.';
$string['use_one_line_for_each_value'] = 'Use one line for each answer!';
$string['use_this_template'] = 'Use this template';
$string['using_templates'] = 'Use a template';
$string['vertical'] = 'vertical';
// Deprecated since Moodle 3.1.
$string['cannotmappatientform'] = 'Database problem, unable to map patientform to course';
$string['line_values'] = 'Rating';
$string['mapcourses_help'] = 'Once you have selected the relevant course(s) from your search,
you can associate them with this patientform using map course(s). Multiple courses may be selected by holding down the Apple or Ctrl key whilst clicking on the course names. A course may be disassociated from a patientform at any time.';
$string['max_args_exceeded'] = 'Max 6 arguments can be handled, too many arguments for';
$string['cancel_moving'] = 'Cancel moving';
$string['movedown_item'] = 'Move this question down';
$string['move_here'] = 'Move here';
$string['moveup_item'] = 'Move this question up';
$string['notavailable'] = 'this patientform is not available';
$string['saving_failed_because_missing_or_false_values'] = 'Saving failed because missing or false values';
$string['cannotunmap'] = 'Database problem, unable to unmap';
$string['viewcompleted'] = 'completed patientforms';
$string['viewcompleted_help'] = 'You may view completed patientform forms, searchable by course and/or by question.
Patientform responses may be exported to Excel.';
$string['parameters_missing'] = 'Parameters missing from';
$string['picture'] = 'Picture';
$string['picture_file_list'] = 'List of pictures';
$string['picture_values'] = 'Choose one or more<br />picture files from the list:';
$string['preview'] = 'Preview';
$string['preview_help'] = 'In the preview you can change the order of questions.';
$string['switch_group'] = 'Switch group';
$string['separator_decimal'] = '.';
$string['separator_thousand'] = ',';
$string['relateditemsdeleted'] = 'All responses for this question will also be deleted.';
$string['radiorated'] = 'Radiobutton (rated)';
$string['radiobutton'] = 'Multiple choice - single answer allowed (radio buttons)';
$string['radiobutton_rated'] = 'Radiobutton (rated)';
// Deprecated since Moodle 3.2.
$string['start'] = 'Start';
$string['stop'] = 'End';
