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

global $CFG;

require_once($CFG->libdir. "/externallib.php");
require_once($CFG->libdir . "/moodlelib.php");
require_once($CFG->libdir . "/modinfolib.php");
require_once($CFG->dirroot . "/availability/classes/info_module.php");
require_once($CFG->libdir . "/completionlib.php");
require_once($CFG->dirroot . '/local/botmanager/lib.php');

class get_feedback extends \external_api
{

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters():external_function_parameters {
        return new external_function_parameters(
            [
                'feedbackid' => new external_value(PARAM_INT, 'Feedback ID'),
                'userid' => new external_value(PARAM_INT, 'User ID')

            ]
        );
    }


    /**
     * Get Feedback
     *
     * @param int $feedbackid
     * @param int $userid
     * @return DB object
     * @throws moodle_exception
     */
    public static function execute($feedbackid, $userid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $data = array();

        $completed = $DB->get_field('feedback_completed', 'id', array('feedback' => $feedbackid, 'userid' => $userid), IGNORE_MULTIPLE);

        $feedback = $DB->get_record("feedback", array("id" => $feedbackid), '*', MUST_EXIST);

        if ($completed) {

            $sql = "SELECT fi.* FROM {feedback_item} AS fi
                                                 LEFT JOIN (SELECT *
                                                            FROM {feedback_value}
                                                            WHERE completed = :completed) AS fv
                                                        ON fv.item = fi.id
                                                        WHERE fv.item IS NULL AND fi.feedback = :feedback ORDER BY fi.position";

            $item = $DB->get_record_sql($sql, array('completed' => $completed, 'feedback' => $feedbackid), IGNORE_MULTIPLE);

        } else {

            // View Feedback for the first time.
            local_botmanager_view_module('feedback', $feedbackid, $userid);

            $item = $DB->get_record('feedback_item', array('feedback' => $feedbackid, 'position' => 1), '*', IGNORE_MULTIPLE);
        }

        // Get Course Module ID.
        $feedback_module_id = $DB->get_field('modules', 'id', array('name' => 'feedback'), IGNORE_MULTIPLE);
        $cmid = $DB->get_field('course_modules', 'id', array('module' => $feedback_module_id, 'instance' => $feedbackid), IGNORE_MULTIPLE);

        /*
        $activityimg = $DB->get_field(
            'local_kassai_image_url',
            'url',
            array('item_id' => $cmid, 'type' => 'mod'),
            IGNORE_MULTIPLE
        );
        */
        $transaction->allow_commit();

        $data['activitytitle'] = $feedback->name;
        //$data['activityimg'] = $activityimg;
        $data['itemid'] = $item->id;
        $data['itemname'] = $item->name;
        $data['itemlabel'] = $item->label;
        $data['itemtype'] = $item->typ;
        $data['itemposition'] = $item->position;
        $data['completed'] = is_null($item->id) ? 1 : 0;

        if ($item->typ == 'multichoice' || $item->typ == 'multichoicerated') {

            $answerslist = str_replace("r>>>>>", "",$item->presentation);
            $answers = explode('|', $answerslist);

            foreach ($answers as $key => $answer) {
                $data['answers'][$key]['answer'] = trim($answer);
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
                'activitytitle' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                //'activityimg' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'itemid' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'itemname' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'itemposition' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'itemlabel' => new external_value(PARAM_RAW, 'Standard Moodle primary key.'),
                'itemtype' => new external_value(PARAM_TEXT, 'Standard Moodle primary key.'),
                'completed' => new external_value(PARAM_INT, 'Standard Moodle primary key.'),
                'answers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'answer' => new external_value(PARAM_RAW, 'Standard Moodle primary key.')
                        )
                    ), VALUE_DEFAULT, array()
                )
            )
        );
    }

}