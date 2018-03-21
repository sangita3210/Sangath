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
 * Tests for patient events.
 *
 * @package    mod_patient
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

global $CFG;

/**
 * Class mod_patient_events_testcase
 *
 * Class for tests related to patient events.
 *
 * @package    mod_patient
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class mod_patient_events_testcase extends advanced_testcase {

    /** @var  stdClass A user who likes to interact with patient activity. */
    private $eventuser;

    /** @var  stdClass A course used to hold patient activities for testing. */
    private $eventcourse;

    /** @var  stdClass A patient activity used for patient event testing. */
    private $eventpatient;

    /** @var  stdClass course module object . */
    private $eventcm;

    /** @var  stdClass A patient item. */
    private $eventpatientitem;

    /** @var  stdClass A patient activity response submitted by user. */
    private $eventpatientcompleted;

    /** @var  stdClass value associated with $eventpatientitem . */
    private $eventpatientvalue;

    public function setUp() {
        global $DB;

        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $this->eventuser = $gen->create_user(); // Create a user.
        $course = $gen->create_course(); // Create a course.
        // Assign manager role, so user can see reports.
        role_assign(1, $this->eventuser->id, context_course::instance($course->id));

        // Add a patient activity to the created course.
        $record = new stdClass();
        $record->course = $course->id;
        $patient = $gen->create_module('patient', $record);
        $this->eventpatient = $DB->get_record('patient', array('id' => $patient->id), '*', MUST_EXIST); // Get exact copy.
        $this->eventcm = get_coursemodule_from_instance('patient', $this->eventpatient->id, false, MUST_EXIST);

        // Create a patient item.
        $item = new stdClass();
        $item->patient = $this->eventpatient->id;
        $item->type = 'numeric';
        $item->presentation = '0|0';
        $itemid = $DB->insert_record('patient_item', $item);
        $this->eventpatientitem = $DB->get_record('patient_item', array('id' => $itemid), '*', MUST_EXIST);

        // Create a response from a user.
        $response = new stdClass();
        $response->patient = $this->eventpatient->id;
        $response->userid = $this->eventuser->id;
        $response->anonymous_response = PATIENTFORM_ANONYMOUS_YES;
        $completedid = $DB->insert_record('patient_completed', $response);
        $this->eventpatientcompleted = $DB->get_record('patient_completed', array('id' => $completedid), '*', MUST_EXIST);

        $value = new stdClass();
        $value->course_id = $course->id;
        $value->item = $this->eventpatientitem->id;
        $value->completed = $this->eventpatientcompleted->id;
        $value->value = 25; // User response value.
        $valueid = $DB->insert_record('patient_value', $value);
        $this->eventpatientvalue = $DB->get_record('patient_value', array('id' => $valueid), '*', MUST_EXIST);
        // Do this in the end to get correct sortorder and cacherev values.
        $this->eventcourse = $DB->get_record('course', array('id' => $course->id), '*', MUST_EXIST);

    }

    /**
     * Tests for event response_deleted.
     */
    public function test_response_deleted_event() {
        global $USER, $DB;
        $this->resetAfterTest();

        // Create and delete a module.
        $sink = $this->redirectEvents();
        patient_delete_completed($this->eventpatientcompleted->id);
        $events = $sink->get_events();
        $event = array_pop($events); // Delete feedback event.
        $sink->close();

        // Validate event data.
        $this->assertInstanceOf('\mod_patient\event\response_deleted', $event);
        $this->assertEquals($this->eventpatientcompleted->id, $event->objectid);
        $this->assertEquals($USER->id, $event->userid);
        $this->assertEquals($this->eventuser->id, $event->relateduserid);
        $this->assertEquals('patient_completed', $event->objecttable);
        $this->assertEquals(null, $event->get_url());
        $this->assertEquals($this->eventpatientcompleted, $event->get_record_snapshot('patient_completed', $event->objectid));
        $this->assertEquals($this->eventcourse, $event->get_record_snapshot('course', $event->courseid));
        $this->assertEquals($this->eventpatient, $event->get_record_snapshot('patient', $event->other['instanceid']));

        // Test legacy data.
        $arr = array($this->eventcourse->id, 'patient', 'delete', 'view.php?id=' . $this->eventcm->id, $this->eventpatient->id,
                $this->eventpatient->id);
        $this->assertEventLegacyLogData($arr, $event);
        $this->assertEventContextNotUsed($event);

        // Test can_view() .
        $this->setUser($this->eventuser);
        $this->assertFalse($event->can_view());
        $this->assertDebuggingCalled();
        $this->setAdminUser();
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();

        // Create a response, with anonymous set to no and test can_view().
        $response = new stdClass();
        $response->patient = $this->eventcm->instance;
        $response->userid = $this->eventuser->id;
        $response->anonymous_response = PATIENTFORM_ANONYMOUS_NO;
        $completedid = $DB->insert_record('patient_completed', $response);
        $DB->get_record('patient_completed', array('id' => $completedid), '*', MUST_EXIST);
        $value = new stdClass();
        $value->course_id = $this->eventcourse->id;
        $value->item = $this->eventpatientitem->id;
        $value->completed = $completedid;
        $value->value = 25; // User response value.
        $DB->insert_record('patient_valuetmp', $value);

        // Save the feedback.
        $sink = $this->redirectEvents();
        patient_delete_completed($completedid);
        $events = $sink->get_events();
        $event = array_pop($events); // Response submitted feedback event.
        $sink->close();

        // Test can_view() .
        $this->setUser($this->eventuser);
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();
        $this->setAdminUser();
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Tests for event validations related to feedback response deletion.
     */
    public function test_response_deleted_event_exceptions() {

        $this->resetAfterTest();

        $context = context_module::instance($this->eventcm->id);

        // Test not setting other['anonymous'].
        try {
            \mod_patient\event\response_submitted::create(array(
                'context'  => $context,
                'objectid' => $this->eventpatientcompleted->id,
                'relateduserid' => 2,
            ));
            $this->fail("Event validation should not allow \\mod_patient\\event\\response_deleted to be triggered without
                    other['anonymous']");
        } catch (coding_exception $e) {
            $this->assertContains("The 'anonymous' value must be set in other.", $e->getMessage());
        }
    }

    /**
     * Tests for event response_submitted.
     */
    public function test_response_submitted_event() {
        global $USER, $DB;
        $this->resetAfterTest();
        $this->setUser($this->eventuser);

        // Create a temporary response, with anonymous set to yes.
        $response = new stdClass();
        $response->patient = $this->eventcm->instance;
        $response->userid = $this->eventuser->id;
        $response->anonymous_response = PATIENTFORM_ANONYMOUS_YES;
        $completedid = $DB->insert_record('patient_completedtmp', $response);
        $completed = $DB->get_record('patient_completedtmp', array('id' => $completedid), '*', MUST_EXIST);
        $value = new stdClass();
        $value->course_id = $this->eventcourse->id;
        $value->item = $this->eventpatientitem->id;
        $value->completed = $completedid;
        $value->value = 25; // User response value.
        $DB->insert_record('patient_valuetmp', $value);

        // Save the feedback.
        $sink = $this->redirectEvents();
        $id = patient_save_tmp_values($completed, false);
        $events = $sink->get_events();
        $event = array_pop($events); // Response submitted feedback event.
        $sink->close();

        // Validate event data. Feedback is anonymous.
        $this->assertInstanceOf('\mod_patient\event\response_submitted', $event);
        $this->assertEquals($id, $event->objectid);
        $this->assertEquals($USER->id, $event->userid);
        $this->assertEquals($USER->id, $event->relateduserid);
        $this->assertEquals('patient_completed', $event->objecttable);
        $this->assertEquals(1, $event->anonymous);
        $this->assertEquals(PATIENTFORM_ANONYMOUS_YES, $event->other['anonymous']);
        $this->setUser($this->eventuser);
        $this->assertFalse($event->can_view());
        $this->assertDebuggingCalled();
        $this->setAdminUser();
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();

        // Test legacy data.
        $this->assertEventLegacyLogData(null, $event);

        // Create a temporary response, with anonymous set to no.
        $response = new stdClass();
        $response->patient = $this->eventcm->instance;
        $response->userid = $this->eventuser->id;
        $response->anonymous_response = PATIENTFORM_ANONYMOUS_NO;
        $completedid = $DB->insert_record('patient_completedtmp', $response);
        $completed = $DB->get_record('patient_completedtmp', array('id' => $completedid), '*', MUST_EXIST);
        $value = new stdClass();
        $value->course_id = $this->eventcourse->id;
        $value->item = $this->eventpatientitem->id;
        $value->completed = $completedid;
        $value->value = 25; // User response value.
        $DB->insert_record('patient_valuetmp', $value);

        // Save the feedback.
        $sink = $this->redirectEvents();
        patient_save_tmp_values($completed, false);
        $events = $sink->get_events();
        $event = array_pop($events); // Response submitted feedback event.
        $sink->close();

        // Test legacy data.
        $arr = array($this->eventcourse->id, 'patient', 'submit', 'view.php?id=' . $this->eventcm->id, $this->eventpatient->id,
                     $this->eventcm->id, $this->eventuser->id);
        $this->assertEventLegacyLogData($arr, $event);

        // Test can_view().
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();
        $this->setAdminUser();
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Tests for event validations related to feedback response submission.
     */
    public function test_response_submitted_event_exceptions() {

        $this->resetAfterTest();

        $context = context_module::instance($this->eventcm->id);

        // Test not setting instanceid.
        try {
            \mod_patient\event\response_submitted::create(array(
                'context'  => $context,
                'objectid' => $this->eventpatientcompleted->id,
                'relateduserid' => 2,
                'anonymous' => 0,
                'other'    => array('cmid' => $this->eventcm->id, 'anonymous' => 2)
            ));
            $this->fail("Event validation should not allow \\mod_patient\\event\\response_deleted to be triggered without
                    other['instanceid']");
        } catch (coding_exception $e) {
            $this->assertContains("The 'instanceid' value must be set in other.", $e->getMessage());
        }

        // Test not setting cmid.
        try {
            \mod_patient\event\response_submitted::create(array(
                'context'  => $context,
                'objectid' => $this->eventpatientcompleted->id,
                'relateduserid' => 2,
                'anonymous' => 0,
                'other'    => array('instanceid' => $this->eventpatient->id, 'anonymous' => 2)
            ));
            $this->fail("Event validation should not allow \\mod_patient\\event\\response_deleted to be triggered without
                    other['cmid']");
        } catch (coding_exception $e) {
            $this->assertContains("The 'cmid' value must be set in other.", $e->getMessage());
        }

        // Test not setting anonymous.
        try {
            \mod_patient\event\response_submitted::create(array(
                 'context'  => $context,
                 'objectid' => $this->eventpatientcompleted->id,
                 'relateduserid' => 2,
                 'other'    => array('cmid' => $this->eventcm->id, 'instanceid' => $this->eventpatient->id)
            ));
            $this->fail("Event validation should not allow \\mod_patient\\event\\response_deleted to be triggered without
                    other['anonymous']");
        } catch (coding_exception $e) {
            $this->assertContains("The 'anonymous' value must be set in other.", $e->getMessage());
        }
    }

    /**
     * Test that event observer is executed on course deletion and the templates are removed.
     */
    public function test_delete_course() {
        global $DB;
        $this->resetAfterTest();
        patient_save_as_template($this->eventpatient, 'my template', 0);
        $courseid = $this->eventcourse->id;
        $this->assertNotEmpty($DB->get_records('patient_template', array('course' => $courseid)));
        delete_course($this->eventcourse, false);
        $this->assertEmpty($DB->get_records('patient_template', array('course' => $courseid)));
    }
}

