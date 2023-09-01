<?php

namespace local_botmanager\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use invalid_parameter_exception;
use external_multiple_structure;
use completion_info;
use lang_string;
use stdClass;

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/externallib.php");
require_once($CFG->libdir . "/moodlelib.php");
require_once($CFG->libdir . "/modinfolib.php");
require_once($CFG->dirroot . "/availability/classes/info_module.php");
require_once($CFG->libdir . "/completionlib.php");

class feedback_answer_question extends \external_api
{
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters():external_function_parameters {
        return new external_function_parameters(
            [
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'itemid' => new external_value(PARAM_INT, 'Item ID'),
                'value' => new external_value(PARAM_TEXT, 'Answer value')
            ]
        );
    }

    /**
     * Feedback Answer Question
     *
     * @param int $userid
     * @param int $itemid
     * @param string $value
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($userid, $itemid, $value) {

        global $DB;

        $data = array();

        $transaction = $DB->start_delegated_transaction();

        // Check if Item exists.
        $item = $DB->get_record('feedback_item', array('id' => $itemid), 'id, feedback, position, presentation, typ', MUST_EXIST);
        $feedbackid = $item->feedback;

        // Check if exists user (check for user lang is enough).
        $lang = $DB->get_field('user', 'lang', array('id' => $userid), MUST_EXIST);

        $completed = $DB->get_field('feedback_completed', 'id', array('userid' => $userid, 'feedback' => $feedbackid), IGNORE_MULTIPLE);

        if (!$completed) {

            $feedback_completed_record = new stdClass();
            $feedback_completed_record->feedback = $feedbackid;
            $feedback_completed_record->userid = $userid;
            $feedback_completed_record->timemodified = time();
            $feedback_completed_record->randon_response = 0; // TODO
            $feedback_completed_record->anonymous_response = 1; // TODO
            $feedback_completed_record->courseid = 0; // TODO Why it is always 0?

            $completed = $DB->insert_record('feedback_completed', $feedback_completed_record);
        }

        if ($item->typ == 'multichoice' || $item->typ == 'multichoicerated') {
            $answerslist = str_replace("r>>>>>", "",$item->presentation);
            $answers = explode('|', $answerslist);
            $answers = array_map('trim', $answers);

            if (!in_array($value, $answers, true)) {

                $incorrect_answer_ojt = new \lang_string('incorrect_answer', 'local_botmanager', null, $lang);
                $incorrect_answer = $incorrect_answer_ojt->out();

                throw new invalid_parameter_exception($incorrect_answer);
            }

            // Get Value "id".
            $value = array_search($value, $answers,true);
            $value++;
        }

        if ($DB->record_exists('feedback_value',
            array('item' => $itemid, 'completed' => $completed))) {

            $already_answered_ojt = new \lang_string('already_answered', 'local_botmanager', null, $lang);
            $already_answered = $already_answered_ojt->out();

            $data['message'] = $already_answered;
        } else {
            $feedback_value_record = new stdClass();
            $feedback_value_record->course_id = 0; // TODO Why it is always 0?
            $feedback_value_record->item = $itemid;
            $feedback_value_record->completed = $completed;
            $feedback_value_record->tmp_completed = 0;
            $feedback_value_record->value = $value; // TODO validate value.

            $DB->insert_record('feedback_value', $feedback_value_record);
        }

        // Is Last Question.
        $items_position = $DB->get_records('feedback_item', array('feedback' => $feedbackid), 'position', 'id, position');
        $lastitem = end($items_position);

        $transaction->allow_commit();

        $data['islastquestion'] = 0;
        $data['endmessage'] = '';

        if ($item->position == $lastitem->position) {
            // Feedback completed.
            $feedback = $DB->get_record("feedback", array("id" => $feedbackid), '*', MUST_EXIST);

            $data['islastquestion'] = 1;
            $data['endmessage'] = $feedback->page_after_submit;

            $cm = get_coursemodule_from_instance('feedback', $feedbackid, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC && $feedback->completionsubmit) {
                $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
            }
        }

        return $data;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns():external_single_structure {
        return new external_single_structure(
            array(
                'islastquestion' => new external_value(PARAM_INT, 'Is last question.'),
                'endmessage' => new external_value(PARAM_TEXT, 'Message when user finish the activity.'),
                'message' => new external_value(PARAM_TEXT, 'General Message.',VALUE_DEFAULT, '')
            )
        );
    }


}